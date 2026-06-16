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

class PosSessionController extends Controller
{
    use ResolvesTenantContext;
    public function index(Request $request)
    {
        $search = trim((string) $request->string('search'));

        $sessions = PosSession::query()
            ->with([
                'register:id,name,branch_id',
                'register.branch:id,name',
                'opener:id,name',
                'closer:id,name',
            ])
            ->where('tenant_id', $this->tenantId())
            ->latest('opened_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('VendorAdmin/POS/Session/Index', [
            'filters' => [
                'search' => $search,
            ],
            'sessions' => $sessions,
            'registers' => PosRegister::query()
                ->where('tenant_id', $this->tenantId())
                ->where('is_active', true)
                ->with('branch:id,name')
                ->orderBy('name')
                ->get(['id', 'branch_id', 'name', 'code']),
        ]);
    }

    public function getData(Request $request)
    {
        $PosSessions = PosSession::with(['register', 'branch'])
            ->where('tenant_id', $this->tenantId());

        return DataTables::of($PosSessions)

            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->WhereHas('register', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('closer', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('opener', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            })
                            ->orWhere('opened_at', 'like', "%{$search}%")
                            ->orWhere('closed_at', 'like', "%{$search}%");
                    });
                }
            })

            ->addColumn('session_info', function ($row) {
                return $row->register?->name ?? '—';
            })

            ->addColumn('opener', fn($row) => $row->opener?->name ?? '—')
            ->addColumn('closer', fn($row) => $row->closer?->name ?? '—')

            ->addColumn('branch', function ($row) {
                return $row->branch?->name ?? '—';
            })

            ->addColumn('opening_float', fn($row) => $row->opening_float)
            ->addColumn('current_balance', fn($row) => $row->current_balance)
            ->addColumn('status', fn($row) => $row->status)

            ->editColumn('opened_at', fn($row) => optional($row->opened_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('closed_at', fn($row) => optional($row->closed_at)?->format('Y-m-d h:i A') ?: '-')

            ->rawColumns(['status'])
            ->make(true);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'pos_register_id' => ['required', 'integer', 'exists:pos_registers,id'],
            'menu_id' => ['nullable', 'integer', 'exists:menus,id'],
            'opening_float' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'currency_mode' => ['nullable', 'in:base,secondary'],
        ]);

        $register = PosRegister::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($validated['pos_register_id']);

        if ($register->sessions()->where('status', 'open')->exists()) {
            return back()->withErrors([
                'general' => 'This register already has an open session.',
            ]);
        }

        $currencyMode = ($validated['currency_mode'] ?? 'base') === 'secondary' && $this->secondaryCurrencyCode()
            ? 'secondary'
            : 'base';

        $currencyCode = $currencyMode === 'secondary'
            ? $this->secondaryCurrencyCode()
            : $this->baseCurrencyCode();

        $session = null;

        DB::transaction(function () use (&$session, $validated, $register, $currencyMode, $currencyCode) {
            $session = PosSession::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $this->tenantId(),
                'pos_register_id' => $register->id,
                'user_id' => auth('vendor')->id(),
                'opened_by' => auth('vendor')->id(),
                'branch_id' => $register->branch_id,
                'menu_id' => $validated['menu_id'] ?? null,
                'channel' => 'takeaway',
                'currency_mode' => $currencyMode,
                'currency_code' => $currencyCode,
                'guest_count' => 1,
                'status' => 'open',
                'opening_float' => $validated['opening_float'],
                'current_balance' => $validated['opening_float'],
                'notes' => $validated['notes'] ?? null,
                'opened_at' => now(),
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'grand_total' => 0,
            ]);

            PosCashMovement::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $this->tenantId(),
                'branch_id' => $register->branch_id,
                'pos_register_id' => $register->id,
                'pos_session_id' => $session->id,
                'user_id' => auth('vendor')->id(),
                'direction' => 'in',
                'reason' => 'opening_float',
                'amount' => $validated['opening_float'],
                'balance_before' => 0,
                'balance_after' => $validated['opening_float'],
                'reference' => null,
                'notes' => $validated['notes'] ?? 'Opening cash',
                'currency_mode' => $currencyMode,
                'currency_code' => $currencyCode,
                'occurred_at' => now(),
            ]);
        });

        return redirect()
            ->route('vendor.pos.viewer', $register->id)
            ->with('success', 'Session opened successfully.');
    }

    public function close(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'closing_note' => ['nullable', 'string'],
        ]);

        if ($session->status !== 'open') {
            return back()->withErrors(['general' => 'Only open sessions can be closed.']);
        }

        $session->update([
            'status' => 'cancelled',
            'closed_by' => auth('vendor')->id(),
            'closed_at' => now(),
            'closing_note' => $validated['closing_note'] ?? null,
        ]);

        return back()->with('success', 'Session closed successfully.');
    }
}