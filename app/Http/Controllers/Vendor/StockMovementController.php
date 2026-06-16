<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Services\Inventory\LowStockAlertMailer;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class StockMovementController extends Controller
{
    private const TYPE_OPTIONS = [
        'in' => 'In',
        'out' => 'Out',
        'spoil' => 'Spoil',
        'adjust_add' => 'Adjust Add',
        'adjust_subtract' => 'Adjust Subtract',
        'transfer_in' => 'Transfer In',
        'transfer_out' => 'Transfer Out',
        'return_supplier' => 'Return Supplier',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Inventory/StockMovement/Index');
    }

    public function getData(Request $request)
    {
        $movements = StockMovement::query()
            ->with([
                'branch:id,name',
                'ingredient:id,name,unit_id',
                'ingredient.unit:id,name,symbol'
            ])
            ->where('tenant_id', $this->tenantId())
            ->select('stock_movements.*');

        return DataTables::of($movements)

            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->whereHas('ingredient', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }
            })

            ->addColumn('branch_name', function ($row) {
                return $row->branch?->name ?? '-';
            })

            ->addColumn('ingredient_name', function ($row) {
                return $row->ingredient?->name ?? '-';
            })

            ->addColumn('type_badge', function ($row) {

                $label = self::TYPE_OPTIONS[$row->type] ?? ucfirst($row->type);

                $isDanger = in_array(
                    $row->type,
                    ['out', 'spoil', 'adjust_subtract', 'transfer_out', 'return_supplier'],
                    true
                );

                if ($isDanger) {
                    return '
            <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-dash-circle"></i>
                ' . e($label) . '
            </span>
        ';
                }

                return '
        <span class="badge rounded-pill bg-success-subtle text-success border border-success d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-plus-circle"></i>
            ' . e($label) . '
        </span>
    ';
            })

            ->addColumn('quantity_label', function ($row) {

                $symbol = $row->ingredient?->unit?->symbol
                    ? ' ' . $row->ingredient->unit->symbol
                    : '';

                return '
        <span class="fw-semibold text-dark">
            ' . number_format((float) $row->quantity, 3) . $symbol . '
        </span>
    ';
            })

            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)?->format('Y-m-d h:i A') ?: '-';
            })

            ->editColumn('updated_at', function ($row) {
                return optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-';
            })

            ->rawColumns(['type_badge', 'quantity_label'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Inventory/StockMovement/CreateUpdate', [
            'movement' => null,
            'branches' => $this->branches(),
            'ingredients' => $this->ingredients(),
            'typeOptions' => self::TYPE_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $ingredient = Ingredient::where('tenant_id', $this->tenantId())->findOrFail($validated['ingredient_id']);

            $this->applyStockChange($ingredient, $validated['type'], (float) $validated['quantity']);

            $movement = new StockMovement();
            $movement->tenant_id = $this->tenantId();
            $this->fillMovement($movement, $validated);
            $movement->save();

            DB::commit();

            return redirect()->route('vendor.stock-movements.index')->with('success', 'Stock movement created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Stock movement store query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Unable to save stock movement.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Stock movement store failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => $ex->getMessage() ?: 'Something went wrong while creating the stock movement.']);
        }
    }

    public function edit($id)
    {
        $movement = StockMovement::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Inventory/StockMovement/CreateUpdate', [
            'movement' => $movement,
            'branches' => $this->branches(),
            'ingredients' => $this->ingredients(),
            'typeOptions' => self::TYPE_OPTIONS,
        ]);
    }

    public function update(Request $request, $id)
    {
        $movement = StockMovement::where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($movement->id), $this->messages());

        try {
            DB::beginTransaction();

            $oldIngredient = Ingredient::where('tenant_id', $this->tenantId())->findOrFail($movement->ingredient_id);
            $this->revertStockChange($oldIngredient, $movement->type, (float) $movement->quantity);

            $newIngredient = Ingredient::where('tenant_id', $this->tenantId())->findOrFail($validated['ingredient_id']);
            $this->applyStockChange($newIngredient, $validated['type'], (float) $validated['quantity']);

            $this->fillMovement($movement, $validated);
            $movement->save();

            DB::commit();

            return redirect()->route('vendor.stock-movements.index')->with('success', 'Stock movement updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Stock movement update query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Unable to update stock movement.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Stock movement update failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => $ex->getMessage() ?: 'Something went wrong while updating the stock movement.']);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $movement = StockMovement::where('tenant_id', $this->tenantId())->findOrFail($id);
            $ingredient = Ingredient::where('tenant_id', $this->tenantId())->findOrFail($movement->ingredient_id);

            $this->revertStockChange($ingredient, $movement->type, (float) $movement->quantity);
            $movement->delete();

            DB::commit();

            return redirect()->route('vendor.stock-movements.index')->with('success', 'Stock movement deleted successfully.');
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Stock movement delete failed', ['message' => $ex->getMessage()]);
            return back()->withErrors(['general' => 'Unable to delete stock movement.']);
        }
    }

    private function fillMovement(StockMovement $movement, array $validated): void
    {
        $movement->branch_id = $validated['branch_id'] ?? null;
        $movement->ingredient_id = $validated['ingredient_id'];
        $movement->type = $validated['type'];
        $movement->quantity = $validated['quantity'];
        $movement->note = $validated['note'] ?? null;
        $movement->source_id = $validated['source_id'] ?? null;
        $movement->source_name = $validated['source_name'] ?? null;
    }

    private function rules($movementId = null): array
    {
        return [
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'ingredient_id' => [
                'required',
                'integer',
                Rule::exists('ingredients', 'id')->where(fn($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPE_OPTIONS))],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
            'source_id' => ['nullable', 'integer'],
            'source_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function messages(): array
    {
        return [
            'ingredient_id.required' => 'Please select an ingredient.',
            'type.required' => 'Please select a movement type.',
            'quantity.required' => 'Quantity is required.',
            'quantity.gt' => 'Quantity must be greater than zero.',
        ];
    }

    private function applyStockChange(Ingredient $ingredient, string $type, float $quantity): void
    {
        $previousStock = (float) $ingredient->current_stock;
        $previousAlertQuantity = (float) $ingredient->alert_quantity;
        $delta = $this->signedQuantity($type, $quantity);
        $newStock = (float) $ingredient->current_stock + $delta;

        if ($newStock < 0) {
            throw new Exception('Stock cannot go below zero.');
        }

        $ingredient->current_stock = $newStock;
        $ingredient->save();

        app(LowStockAlertMailer::class)->notifyIfLow($ingredient, $previousStock, $previousAlertQuantity);
    }

    private function revertStockChange(Ingredient $ingredient, string $type, float $quantity): void
    {
        $reverseDelta = $this->signedQuantity($type, $quantity) * -1;
        $newStock = (float) $ingredient->current_stock + $reverseDelta;

        if ($newStock < 0) {
            throw new Exception('Stock cannot go below zero.');
        }

        $ingredient->current_stock = $newStock;
        $ingredient->save();
    }

    private function signedQuantity(string $type, float $quantity): float
    {
        return match ($type) {
            'in', 'adjust_add', 'transfer_in' => $quantity,
            'out', 'spoil', 'adjust_subtract', 'transfer_out', 'return_supplier' => $quantity * -1,
            default => 0,
        };
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function ingredients()
    {
        return Ingredient::query()
            ->with('unit:id,name,symbol')
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name', 'unit_id', 'branch_ids')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'unit_name' => $item->unit?->name,
                'unit_symbol' => $item->unit?->symbol,
                'branch_ids' => $item->branch_ids,
            ]);
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
