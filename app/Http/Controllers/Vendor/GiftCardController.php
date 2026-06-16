<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class GiftCardController extends Controller
{
    use ResolvesTenantContext;

    private const STATUS_OPTIONS = [
        'active' => 'Active',
        'used' => 'Used',
        'expired' => 'Expired',
        'disabled' => 'Disabled',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/GiftCards/Cards/Index');
    }

    public function getData(Request $request)
    {
        $cards = GiftCard::query()
            ->with(['branch:id,name', 'customer:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->select('gift_cards.*');

        return DataTables::of($cards)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (!filled($search)) {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
            ->addColumn('customer_name', fn ($row) => $row->customer?->name ?? '-')
            ->addColumn('status_badge', fn ($row) => $this->statusBadge($this->effectiveStatus($row)))
            ->editColumn('initial_balance', fn ($row) => $this->money($row->initial_balance))
            ->editColumn('current_balance', fn ($row) => $this->money($row->current_balance))
            ->editColumn('secondary_initial_balance', fn ($row) => $row->secondary_currency_code ? $this->money($row->secondary_initial_balance) : null)
            ->editColumn('secondary_current_balance', fn ($row) => $row->secondary_currency_code ? $this->money($row->secondary_current_balance) : null)
            ->editColumn('expires_at', fn ($row) => optional($row->expires_at)?->format('Y-m-d') ?: '-')
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/GiftCards/Cards/CreateUpdate', [
            'card' => null,
            'branches' => $this->branches(),
            'customers' => $this->customers(),
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($validated) {
            $initialBalance = (float) $validated['initial_balance'];
            $secondaryBalance = $this->secondaryCurrencyCode()
                ? (float) ($validated['secondary_initial_balance'] ?? 0)
                : null;
            $card = GiftCard::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $validated['branch_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'base_currency_code' => $this->baseCurrencyCode(),
                'secondary_currency_code' => $this->secondaryCurrencyCode(),
                'code' => $validated['code'] ?: $this->generateCode(),
                'initial_balance' => $initialBalance,
                'current_balance' => $initialBalance,
                'secondary_initial_balance' => $secondaryBalance,
                'secondary_current_balance' => $secondaryBalance,
                'status' => $validated['status'],
                'expires_at' => $validated['expires_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'purchased_at' => $initialBalance > 0 ? now() : null,
                'used_at' => $initialBalance <= 0 ? now() : null,
                'disabled_at' => $validated['status'] === 'disabled' ? now() : null,
            ]);

            if ($initialBalance > 0) {
                $this->recordTransaction($card, 'purchase', $initialBalance, 0, $initialBalance, 'Gift card created', 'base', $this->baseCurrencyCode());
            }
        });

        return redirect()->route('vendor.gift-cards.index')->with('success', 'Gift card created successfully.');
    }

    public function edit($id)
    {
        $card = GiftCard::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/GiftCards/Cards/CreateUpdate', [
            'card' => $card,
            'branches' => $this->branches(),
            'customers' => $this->customers(),
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function update(Request $request, $id)
    {
        $card = GiftCard::where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($card->id));

        $card->update([
            'branch_id' => $validated['branch_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'code' => $validated['code'] ?: $card->code,
            'status' => $validated['status'],
            'expires_at' => $validated['expires_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'disabled_at' => $validated['status'] === 'disabled' ? ($card->disabled_at ?: now()) : null,
            'used_at' => $validated['status'] === 'used' ? ($card->used_at ?: now()) : $card->used_at,
        ]);

        return redirect()->route('vendor.gift-cards.index')->with('success', 'Gift card updated successfully.');
    }

    public function destroy($id)
    {
        $card = GiftCard::where('tenant_id', $this->tenantId())->findOrFail($id);
        $card->delete();

        return redirect()->route('vendor.gift-cards.index')->with('success', 'Gift card deleted successfully.');
    }

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
        ]);

        $card = GiftCard::query()
            ->where('tenant_id', $this->tenantId())
            ->where('code', $validated['code'])
            ->first();

        if (!$card) {
            return response()->json(['message' => 'Gift card not found.'], 404);
        }

        return response()->json([
            'id' => $card->id,
            'code' => $card->code,
            'status' => $this->effectiveStatus($card),
            'base_currency_code' => $card->base_currency_code ?: $this->baseCurrencyCode(),
            'secondary_currency_code' => $card->secondary_currency_code,
            'current_balance' => (float) $card->current_balance,
            'secondary_current_balance' => $card->secondary_current_balance !== null ? (float) $card->secondary_current_balance : null,
            'expires_at' => optional($card->expires_at)?->format('Y-m-d'),
        ]);
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('gift_cards', 'code')->ignore($ignoreId),
            ],
            'initial_balance' => [$ignoreId ? 'sometimes' : 'required', 'numeric', 'gt:0'],
            'secondary_initial_balance' => ['nullable', 'numeric', 'min:0'],
            'expires_at' => ['required', 'date'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'status' => ['required', Rule::in(array_keys(self::STATUS_OPTIONS))],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function recordTransaction(GiftCard $card, string $type, float $amount, float $before, float $after, ?string $note = null, string $currencyMode = 'base', ?string $currencyCode = null): void
    {
        GiftCardTransaction::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'branch_id' => $card->branch_id,
            'gift_card_id' => $card->id,
            'created_by' => auth('vendor')->id(),
            'currency_mode' => $currencyMode,
            'currency_code' => $currencyCode ?: $card->base_currency_code,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'note' => $note,
            'occurred_at' => now(),
        ]);
    }

    private function effectiveStatus(GiftCard $card): string
    {
        $hasBalance = (float) $card->current_balance > 0
            || ($card->secondary_currency_code && (float) $card->secondary_current_balance > 0);

        if ($card->expires_at && $card->expires_at->isPast() && $hasBalance && $card->status === 'active') {
            return 'expired';
        }

        if (!$hasBalance && $card->status === 'active') {
            return 'used';
        }

        return $card->status;
    }

    private function statusBadge(string $status): string
    {
        $classes = [
            'active' => 'bg-success-subtle text-success',
            'used' => 'bg-info-subtle text-info',
            'expired' => 'bg-danger-subtle text-danger',
            'disabled' => 'bg-secondary-subtle text-secondary',
        ];

        return '<span class="badge rounded-pill ' . ($classes[$status] ?? 'bg-secondary-subtle text-secondary') . '">' . e(ucfirst($status)) . '</span>';
    }

    private function generateCode(?string $prefix = 'GC'): string
    {
        do {
            $code = strtoupper($prefix . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }

    private function branches()
    {
        return Branch::where('tenant_id', $this->tenantId())->select('id', 'name')->orderBy('name')->get();
    }

    private function customers()
    {
        return Customer::where('tenant_id', $this->tenantId())->select('id', 'name')->orderBy('name')->get();
    }

    private function money($value): string
    {
        return number_format((float) $value, 3);
    }

}
