<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GiftCard;
use App\Models\GiftCardBatch;
use App\Models\GiftCardTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class GiftCardBatchController extends Controller
{
    use ResolvesTenantContext;

    public function index()
    {
        return Inertia::render('VendorAdmin/GiftCards/Batches/Index');
    }

    public function getData(Request $request)
    {
        $batches = GiftCardBatch::query()
            ->with('branch:id,name')
            ->where('tenant_id', $this->tenantId())
            ->select('gift_card_batches.*');

        return DataTables::of($batches)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (!filled($search)) {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('prefix', 'like', "%{$search}%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                });
            })
            ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
            ->addColumn('cards_remaining', fn ($row) => max(0, (int) $row->cards_generated - (int) $row->cards_used))
            ->editColumn('value', fn ($row) => number_format((float) $row->value, 3))
            ->editColumn('secondary_value', fn ($row) => $row->secondary_currency_code ? number_format((float) $row->secondary_value, 3) : null)
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/GiftCards/Batches/CreateUpdate', [
            'batch' => null,
            'branches' => $this->branches(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($validated) {
            $quantity = (int) $validated['quantity'];
            $value = (float) $validated['value'];
            $secondaryValue = $this->secondaryCurrencyCode()
                ? (float) ($validated['secondary_value'] ?? 0)
                : null;
            $prefix = strtoupper($validated['prefix']);

            $batch = GiftCardBatch::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $validated['branch_id'] ?? null,
                'base_currency_code' => $this->baseCurrencyCode(),
                'secondary_currency_code' => $this->secondaryCurrencyCode(),
                'name' => $validated['name'],
                'prefix' => $prefix,
                'quantity' => $quantity,
                'value' => $value,
                'secondary_value' => $secondaryValue,
                'cards_generated' => 0,
                'cards_used' => 0,
            ]);

            for ($i = 0; $i < $quantity; $i++) {
                $card = GiftCard::create([
                    'tenant_id' => $this->tenantId(),
                    'branch_id' => $batch->branch_id,
                    'gift_card_batch_id' => $batch->id,
                    'base_currency_code' => $batch->base_currency_code,
                    'secondary_currency_code' => $batch->secondary_currency_code,
                    'code' => $this->generateCode($prefix),
                    'initial_balance' => $value,
                    'current_balance' => $value,
                    'secondary_initial_balance' => $secondaryValue,
                    'secondary_current_balance' => $secondaryValue,
                    'status' => 'active',
                    'purchased_at' => now(),
                ]);

                GiftCardTransaction::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $this->tenantId(),
                    'branch_id' => $card->branch_id,
                    'gift_card_id' => $card->id,
                    'created_by' => auth('vendor')->id(),
                    'currency_mode' => 'base',
                    'currency_code' => $batch->base_currency_code,
                    'type' => 'purchase',
                    'amount' => $value,
                    'balance_before' => 0,
                    'balance_after' => $value,
                    'note' => 'Gift card batch generated',
                    'occurred_at' => now(),
                ]);
            }

            $batch->update(['cards_generated' => $quantity]);
        });

        return redirect()->route('vendor.gift-cards.batches.index')->with('success', 'Gift card batch created successfully.');
    }

    public function edit($id)
    {
        $batch = GiftCardBatch::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/GiftCards/Batches/CreateUpdate', [
            'batch' => $batch,
            'branches' => $this->branches(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $batch = GiftCardBatch::where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($batch->id, false));

        $batch->update([
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'prefix' => strtoupper($validated['prefix']),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('vendor.gift-cards.batches.index')->with('success', 'Gift card batch updated successfully.');
    }

    public function destroy($id)
    {
        $batch = GiftCardBatch::where('tenant_id', $this->tenantId())->findOrFail($id);
        $batch->delete();

        return redirect()->route('vendor.gift-cards.batches.index')->with('success', 'Gift card batch deleted successfully.');
    }

    private function rules(?int $ignoreId = null, bool $includeGeneration = true): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'prefix' => ['required', 'string', 'max:20'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'quantity' => [$includeGeneration ? 'required' : 'nullable', 'integer', 'min:1', 'max:500'],
            'value' => [$includeGeneration ? 'required' : 'nullable', 'numeric', 'min:0.001'],
            'secondary_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function generateCode(string $prefix): string
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

}
