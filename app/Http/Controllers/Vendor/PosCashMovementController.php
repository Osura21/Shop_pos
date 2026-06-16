<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;

use App\Models\PosCashMovement;
use App\Models\PosRegister;
use App\Models\PosSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\DataTables;

class PosCashMovementController extends Controller
{
    use ResolvesTenantContext;
    public function index(Request $request)
    {
        abort_unless($request->user('vendor')?->can('pos-cash-movements.view'), 403);

        $search = trim((string) $request->string('search'));

        $movements = PosCashMovement::query()
            ->with([
                'register:id,name,branch_id',
                'register.branch:id,name',
                'session:id,uuid',
            ])
            ->where('tenant_id', $this->tenantId())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhere('reason', 'like', "%{$search}%")
                      ->orWhereHas('register', fn ($r) => $r->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('occurred_at')
            ->paginate(10)
            ->withQueryString();

            $openSessions = PosSession::query()
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'open')
            ->with('register:id,name')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'register_name' => $session->register?->name ?? 'Unknown Register',
                'currency_code' => $session->currency_code,
            ]);

        return Inertia::render('VendorAdmin/POS/CashMovement/Index', [
            'filters' => [
                'search' => $search,
            ],
            'movements' => $movements,
            'openSessions' => $openSessions,
        ]);
    }

    
    public function getData(Request $request)
    {
        abort_unless($request->user('vendor')?->can('pos-cash-movements.view'), 403);

        $posCashMovement = PosCashMovement::with(['register', 'branch'])
            ->where('tenant_id', $this->tenantId());

        return DataTables::of($posCashMovement)

            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->WhereHas('register', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('branch', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            })
                            ->orWhere('occurred_at', 'like', "%{$search}%");
                    });
                }
            })

            ->addColumn('register_name', fn($row) => $row->register?->name ?? '—')

            ->addColumn('branch_name', function ($row) {
                return $row->branch?->name ?? '—';
            })

            ->addColumn('direction', fn($row) => $row->direction)
            ->addColumn('reason', fn($row) => $row->reason)
            ->addColumn('balance_before', fn($row) => $row->balance_before)

            ->editColumn('occurred_at', fn($row) => optional($row->occurred_at)?->format('Y-m-d h:i A') ?: '-')
            ->make(true);
    }

    public function store(Request $request)
    {
        abort_unless($request->user('vendor')?->can('pos-cash-movements.create'), 403);

        $validated = $request->validate([
            'pos_session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
            'direction' => ['required', 'in:in,out'],
            'reason' => ['required', 'in:pay_in,tip_in,refund,pay_out,tip_out,cash_drop,correction,sale'],
            'amount' => ['required', 'numeric', 'min:0.001'],            
            'notes' => ['nullable', 'string'],
        ]);

        $session = PosSession::query()
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'open')
            ->findOrFail($validated['pos_session_id']);

        $register = PosRegister::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($session->pos_register_id);

        DB::transaction(function () use ($validated, $session, $register) {
            $before = (float) $session->current_balance;
            $amount = (float) $validated['amount'];
            $after = $validated['direction'] === 'in'
                ? $before + $amount
                : $before - $amount;

           PosCashMovement::create([
    'uuid' => (string) Str::uuid(),
                'tenant_id' => $this->tenantId(),
                'branch_id' => $session->branch_id,
                'pos_register_id' => $register->id,
                'pos_session_id' => $session->id,
                'user_id' => auth('vendor')->id(),
                'direction' => $validated['direction'],
                'reason' => $validated['reason'],
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'currency_mode' => $session->currency_mode,
                'currency_code' => $session->currency_code,
                'occurred_at' => now(),
            ]);
            $session->update([
                'current_balance' => $after,
            ]);
        });

        return back()->with('success', 'Cash movement recorded successfully.');
    }

    public function update(Request $request, PosCashMovement $movement)
    {
        abort_unless((int) $movement->tenant_id === (int) $this->tenantId(), 404);
        abort_unless($request->user('vendor')?->can('pos-cash-movements.edit'), 403);
        $validated = $request->validate([
            'pos_session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
            'direction' => ['required', 'in:in,out'],
            'reason' => ['required', 'in:pay_in,tip_in,refund,pay_out,tip_out,cash_drop,correction,sale'],
            'amount' => ['required', 'numeric', 'min:0.001'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $session = PosSession::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($validated['pos_session_id']);
        if ($session->status !== 'open') {
            return back()->withErrors([
                'general' => 'You cannot modify a cash movement for a closed or cancelled session.',
            ]);
        }
        DB::transaction(function () use ($validated, $session, $movement) {
            // Revert original effect
            $origAmount = (float) $movement->amount;
            $origDirection = $movement->direction;
            $origEffect = $origDirection === 'in' ? $origAmount : -$origAmount;
            // Calculate new effect
            $newAmount = (float) $validated['amount'];
            $newDirection = $validated['direction'];
            $newEffect = $newDirection === 'in' ? $newAmount : -$newAmount;
            $diff = $newEffect - $origEffect;
            $movement->update([
                'direction' => $validated['direction'],
                'reason' => $validated['reason'],
                'amount' => $newAmount,
                'balance_after' => $movement->balance_before + $newEffect,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            $session->update([
                'current_balance' => (float) $session->current_balance + $diff,
            ]);
        });
        return back()->with('success', 'Cash movement updated successfully.');
    }
    public function destroy(PosCashMovement $movement)
    {
        abort_unless((int) $movement->tenant_id === (int) $this->tenantId(), 404);
        abort_unless(auth('vendor')->user()?->can('pos-cash-movements.delete'), 403);
        $session = PosSession::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($movement->pos_session_id);
        if ($session->status !== 'open') {
            return back()->withErrors([
                'general' => 'You cannot delete a cash movement for a closed or cancelled session.',
            ]);
        }
        DB::transaction(function () use ($session, $movement) {
            $amount = (float) $movement->amount;
            $direction = $movement->direction;
            $effect = $direction === 'in' ? $amount : -$amount;
            $session->update([
                'current_balance' => (float) $session->current_balance - $effect,
            ]);
            $movement->delete();
        });
        return back()->with('success', 'Cash movement deleted successfully.');
    }
}