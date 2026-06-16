<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Services\Inventory\LowStockAlertMailer;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TenantCurrencySetting;
use App\Support\CurrencyRegistry;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class PurchaseController extends Controller
{
    private const STATUS_OPTIONS = [
        'pending' => 'Pending',
        'partially_received' => 'Partially Received',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ];

    private const CALCULATION_TYPES = [
        'fixed' => 'Fixed Amount',
        'percentage' => 'Percentage',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Inventory/Purchase/Index');
    }

   public function getData(Request $request)
{
    $purchases = Purchase::query()
        ->with(['branch:id,name', 'supplier:id,name'])
        ->where('tenant_id', $this->tenantId())
        ->select('purchases.*');

    return DataTables::of($purchases)

        ->filter(function ($query) use ($request) {
            $search = $request->input('search.value');

            if (filled($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference_no', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('branch', function ($bq) use ($search) {
                          $bq->where('name', 'like', "%{$search}%");
                      });
                });
            }
        })

        ->addColumn('branch_name', function ($row) {
            return $row->branch?->name ?? '-';
        })

        ->addColumn('supplier_name', function ($row) {
            return $row->supplier?->name ?? '-';
        })

        ->addColumn('status_badge', function ($row) {

    $label = self::STATUS_OPTIONS[$row->status] ?? ucfirst((string) $row->status);

    return match ($row->status) {

        'received' => '
            <span class="badge rounded-pill bg-success-subtle text-success border border-success d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-check-circle"></i>
                ' . e($label) . '
            </span>
        ',

        'partially_received' => '
            <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-hourglass-split"></i>
                ' . e($label) . '
            </span>
        ',

        'cancelled' => '
            <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-x-circle"></i>
                ' . e($label) . '
            </span>
        ',

        default => '
            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-clock"></i>
                ' . e($label) . '
            </span>
        ',
    };
})

        ->editColumn('total', function ($row) {
            $currency = $row->base_currency_code ?: ($row->currency ?: 'LKR');

            return '
                <span style="font-weight:600; color:#1a202c;">
                    ' . $this->money($row->total, $currency) . '
                </span>
            ';
        })

        ->addColumn('secondary_total', function ($row) {

            if (!$row->secondary_currency_code || $row->secondary_total === null) {
                return '-';
            }

            return '
                <span style="font-weight:600; color:#6b7280;">
                    ' . $this->money($row->secondary_total, $row->secondary_currency_code) . '
                </span>
            ';
        })

        // ->editColumn('expected_at', function ($row) {
        //     return optional($row->expected_at)?->format('Y-m-d') ?: '-';
        // })

        // ->editColumn('created_at', function ($row) {
        //     return optional($row->created_at)?->format('Y-m-d h:i A') ?: '-';
        // })

        ->rawColumns(['status_badge', 'total', 'secondary_total'])
        ->make(true);
}

    public function create()
    {
        return Inertia::render('VendorAdmin/Inventory/Purchase/CreateUpdate', [
            'purchase' => null,
            'branches' => $this->branches(),
            'suppliers' => $this->suppliers(),
            'ingredients' => $this->ingredients(),
            'calculationTypes' => self::CALCULATION_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $purchase = new Purchase();
            $purchase->tenant_id = $this->tenantId();
            $purchase->reference_no = $this->generatePurchaseReference();
            $this->fillPurchase($purchase, $validated);
            $purchase->save();

            $this->syncItems($purchase, $validated['items']);

            DB::commit();

            return redirect()
                ->route('vendor.purchases.index')
                ->with('success', 'Purchase created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Purchase store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'Unable to save purchase.']);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Purchase store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => $ex->getMessage() ?: 'Something went wrong while creating purchase.']);
        }
    }

    public function show($id)
    {
        $purchase = Purchase::query()
            ->with([
                'branch:id,name',
                'supplier:id,name',
                'items.ingredient:id,name,unit_id',
                'items.ingredient.unit:id,name,symbol',
                'receipts.items.ingredient:id,name',
            ])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        return Inertia::render('VendorAdmin/Inventory/Purchase/Show', [
            'purchase' => $purchase,
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function edit($id)
    {
        $purchase = Purchase::query()
            ->with([
                'items.ingredient:id,name,unit_id',
                'items.ingredient.unit:id,name,symbol',
            ])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        if (in_array($purchase->status, ['partially_received', 'received'], true)) {
            return redirect()
                ->route('vendor.purchases.show', $purchase->id)
                ->withErrors(['general' => 'Received purchases cannot be edited.']);
        }

        return Inertia::render('VendorAdmin/Inventory/Purchase/CreateUpdate', [
            'purchase' => $purchase,
            'branches' => $this->branches(),
            'suppliers' => $this->suppliers(),
            'ingredients' => $this->ingredients(),
            'calculationTypes' => self::CALCULATION_TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchase::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        if (in_array($purchase->status, ['partially_received', 'received'], true)) {
            return back()->withErrors(['general' => 'Received purchases cannot be edited.']);
        }

        $validated = $request->validate($this->rules($purchase->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillPurchase($purchase, $validated);
            $purchase->save();

            $purchase->items()->delete();
            $this->syncItems($purchase, $validated['items']);

            DB::commit();

            return redirect()
                ->route('vendor.purchases.index')
                ->with('success', 'Purchase updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Purchase update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'Unable to update purchase.']);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Purchase update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => $ex->getMessage() ?: 'Something went wrong while updating purchase.']);
        }
    }

    public function markReceived($id)
    {
        try {
            DB::beginTransaction();

            $purchase = Purchase::query()
                ->with(['items.ingredient.unit'])
                ->where('tenant_id', $this->tenantId())
                ->findOrFail($id);

            if ($purchase->status === 'received') {
                return back()->withErrors(['general' => 'Purchase already fully received.']);
            }

            $remainingItems = $purchase->items->filter(function ($item) {
                return ((float) $item->quantity - (float) $item->received_quantity) > 0;
            });

            if ($remainingItems->isEmpty()) {
                return back()->withErrors(['general' => 'No remaining quantity to receive.']);
            }

            $receipt = PurchaseReceipt::create([
                'purchase_id' => $purchase->id,
                'receipt_no' => $this->generateReceiptNo(),
                'reference_no' => 'GRN-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
                'received_at' => now(),
                'received_by_name' => optional(auth('vendor')->user())->name,
                'note' => 'Full remaining quantity received.',
            ]);

            foreach ($remainingItems as $item) {
                $remainingQty = max(0, (float) $item->quantity - (float) $item->received_quantity);

                if ($remainingQty <= 0) {
                    continue;
                }

                PurchaseReceiptItem::create([
                    'purchase_receipt_id' => $receipt->id,
                    'ingredient_id' => $item->ingredient_id,
                    'quantity' => $remainingQty,
                    'unit_name' => $item->ingredient?->unit?->name,
                    'unit_symbol' => $item->ingredient?->unit?->symbol,
                ]);

                $ingredient = Ingredient::query()
                    ->where('tenant_id', $this->tenantId())
                    ->findOrFail($item->ingredient_id);

                $previousStock = (float) $ingredient->current_stock;
                $previousAlertQuantity = (float) $ingredient->alert_quantity;
                $ingredient->current_stock = (float) $ingredient->current_stock + $remainingQty;
                $ingredient->save();

                app(LowStockAlertMailer::class)->notifyIfLow($ingredient, $previousStock, $previousAlertQuantity);

                StockMovement::create([
                    'tenant_id' => $this->tenantId(),
                    'branch_id' => $purchase->branch_id,
                    'ingredient_id' => $item->ingredient_id,
                    'type' => 'in',
                    'quantity' => $remainingQty,
                    'note' => 'Received from purchase ' . $purchase->reference_no,
                    'source_id' => $purchase->id,
                    'source_name' => 'Purchase',
                ]);

                $item->received_quantity = (float) $item->received_quantity + $remainingQty;
                $item->save();
            }

            $purchase->refresh();

            $allReceived = $purchase->items->every(function ($item) {
                return (float) $item->received_quantity >= (float) $item->quantity;
            });

            $purchase->status = $allReceived ? 'received' : 'partially_received';
            $purchase->save();

            DB::commit();

            return redirect()
                ->route('vendor.purchases.show', $purchase->id)
                ->with('success', 'Purchase marked as received successfully.');
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Purchase receive failed', [
                'message' => $ex->getMessage(),
            ]);

            return back()->withErrors([
                'general' => $ex->getMessage() ?: 'Unable to mark purchase as received.',
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $purchase = Purchase::query()
                ->where('tenant_id', $this->tenantId())
                ->findOrFail($id);

            if (in_array($purchase->status, ['partially_received', 'received'], true)) {
                return back()->withErrors(['general' => 'Received or partially received purchases cannot be deleted.']);
            }

            $purchase->delete();

            return redirect()
                ->route('vendor.purchases.index')
                ->with('success', 'Purchase deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Purchase delete failed', [
                'message' => $ex->getMessage(),
            ]);

            return back()->withErrors([
                'general' => $ex->getMessage() ?: 'Unable to delete purchase.',
            ]);
        }
    }

    private function fillPurchase(Purchase $purchase, array $validated): void
    {
        $currencySettings = $this->currencySettings();

        $baseCode = $currencySettings['base']['code'] ?? ($purchase->base_currency_code ?: 'LKR');
        $secondaryCode = $currencySettings['secondary']['code'] ?? null;

        $purchase->branch_id = $validated['branch_id'] ?? null;
        $purchase->supplier_id = $validated['supplier_id'];

        $purchase->base_currency_code = $baseCode;
        $purchase->secondary_currency_code = $secondaryCode;

        // keep legacy field for backward compatibility
        $purchase->currency = $baseCode;

        $purchase->status = $purchase->status ?: 'pending';
        $purchase->expected_at = $validated['expected_at'] ?? null;
        $purchase->notes = $validated['notes'] ?? null;

        $purchase->tax_type = $validated['tax_type'] ?? 'fixed';
        $purchase->tax_value = $validated['tax_value'] ?? 0;
        $purchase->secondary_tax_value = $validated['secondary_tax_value'] ?? null;

        $purchase->discount_type = $validated['discount_type'] ?? 'fixed';
        $purchase->discount_value = $validated['discount_value'] ?? 0;
        $purchase->secondary_discount_value = $validated['secondary_discount_value'] ?? null;

        // base amounts
        $purchase->subtotal = $validated['subtotal'];
        $purchase->tax = $validated['tax_amount'] ?? 0;
        $purchase->discount = $validated['discount_amount'] ?? 0;
        $purchase->total = $validated['total'];

        // secondary amounts
        $purchase->secondary_subtotal = $validated['secondary_subtotal'] ?? null;
        $purchase->secondary_tax = $validated['secondary_tax_amount'] ?? null;
        $purchase->secondary_discount = $validated['secondary_discount_amount'] ?? null;
        $purchase->secondary_total = $validated['secondary_total'] ?? null;
    }

    private function syncItems(Purchase $purchase, array $items): void
    {
        foreach ($items as $item) {
            $baseUnitCost = (float) $item['unit_cost'];
            $quantity = (float) $item['quantity'];
            $secondaryUnitCost = isset($item['secondary_unit_cost']) && $item['secondary_unit_cost'] !== null
                ? (float) $item['secondary_unit_cost']
                : null;

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'ingredient_id' => $item['ingredient_id'],
                'quantity' => $quantity,
                'received_quantity' => 0,

                // base
                'unit_cost' => $baseUnitCost,
                'line_total' => $quantity * $baseUnitCost,

                // secondary
                'secondary_unit_cost' => $secondaryUnitCost,
                'secondary_line_total' => $secondaryUnitCost !== null ? ($quantity * $secondaryUnitCost) : null,
            ]);
        }
    }

    private function rules($purchaseId = null): array
    {
        return [
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'expected_at' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($purchaseId) {
                    if ($value) {
                        $date = \Carbon\Carbon::parse($value)->startOfDay();
                        $today = \Carbon\Carbon::today();
                        if ($date->lt($today)) {
                            if ($purchaseId) {
                                $purchase = Purchase::find($purchaseId);
                                if ($purchase && $purchase->expected_at && \Carbon\Carbon::parse($purchase->expected_at)->startOfDay()->equalTo($date)) {
                                    return;
                                }
                            }
                            $fail('The expected date must be today or a future date.');
                        }
                    }
                }
            ],
            'notes' => ['nullable', 'string'],

            'tax_type' => ['nullable', 'string', Rule::in(array_keys(self::CALCULATION_TYPES))],
            'tax_value' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'secondary_tax_value' => ['nullable', 'numeric', 'min:0'],
            'secondary_tax_amount' => ['nullable', 'numeric', 'min:0'],

            'discount_type' => ['nullable', 'string', Rule::in(array_keys(self::CALCULATION_TYPES))],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'secondary_discount_value' => ['nullable', 'numeric', 'min:0'],
            'secondary_discount_amount' => ['nullable', 'numeric', 'min:0'],

            'subtotal' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'secondary_subtotal' => ['nullable', 'numeric', 'min:0'],
            'secondary_total' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => [
                'required',
                'integer',
                Rule::exists('ingredients', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.secondary_unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'items.required' => 'Please add at least one item.',
            'items.*.ingredient_id.required' => 'Please select an ingredient.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.gt' => 'Quantity must be greater than zero.',
            'items.*.unit_cost.required' => 'Base unit cost is required.',
        ];
    }

    private function generatePurchaseReference(): string
    {
        do {
            $reference = 'PO-' . random_int(10000, 99999);
        } while (Purchase::where('reference_no', $reference)->exists());

        return $reference;
    }

    private function generateReceiptNo(): string
    {
        do {
            $receipt = 'REC-' . random_int(10000, 99999);
        } while (PurchaseReceipt::where('receipt_no', $receipt)->exists());

        return $receipt;
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function suppliers()
    {
        return Supplier::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name', 'branch_id')
            ->orderBy('name')
            ->get();
    }

    private function ingredients()
    {
        return Ingredient::query()
            ->with('unit:id,name,symbol')
            ->where('tenant_id', $this->tenantId())
            ->select(
                'id',
                'name',
                'unit_id',
                'branch_ids',
                'cost_per_unit',
                'secondary_cost_per_unit'
            )
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'branch_ids' => $item->branch_ids,
                'cost_per_unit' => $item->cost_per_unit,
                'secondary_cost_per_unit' => $item->secondary_cost_per_unit,
                'unit_name' => $item->unit?->name,
                'unit_symbol' => $item->unit?->symbol,
            ]);
    }

    private function currencySettings(): array
    {
        $setting = TenantCurrencySetting::query()
            ->where('tenant_id', $this->tenantId())
            ->first();

        $baseCode = $setting?->base_currency_code ?: 'LKR';
        $secondaryCode = $setting?->secondary_currency_code;

        return [
            'base' => CurrencyRegistry::find($baseCode),
            'secondary' => CurrencyRegistry::find($secondaryCode),
        ];
    }

    private function money($amount, ?string $currency = null): string
    {
        $currency = $currency ?: 'LKR';
        return $currency . ' ' . number_format((float) $amount, 3);
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
