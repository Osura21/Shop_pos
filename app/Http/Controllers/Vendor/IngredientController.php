<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Unit;
use App\Services\Inventory\LowStockAlertMailer;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class IngredientController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Inventory/Ingredient/Index');
    }

    public function getData(Request $request)
    {
        $allBranches = Branch::where('tenant_id', $this->tenantId())
        ->pluck('name', 'id');

        $ingredients = Ingredient::query()
            ->with(['unit:id,name,symbol'])
            ->where('tenant_id', $this->tenantId())
            ->select('ingredients.*');

        return DataTables::of($ingredients)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('branch_name', function ($row) use ($allBranches) {
            if (!$row->branch_ids) return '-';

                $names = collect($row->branch_ids)
                    ->map(fn($id) => $allBranches[$id] ?? null)
                    ->filter()
                    ->values()
                    ->toArray();

                return count($names) ? implode(', ', $names) : '-';
            })
            ->addColumn('unit_name', fn ($row) => $row->unit?->name ?? '-')
            ->addColumn('current_stock_label', function ($row) {
                $symbol = $row->unit?->symbol ? ' ' . $row->unit->symbol : '';
                return number_format((float) $row->current_stock, 3) . $symbol;
            })
            ->editColumn('cost_per_unit', fn ($row) => number_format((float) $row->cost_per_unit, 3))
            ->editColumn('alert_quantity', fn ($row) => number_format((float) $row->alert_quantity, 3))
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn ($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Inventory/Ingredient/CreateUpdate', [
            'ingredient' => null,
            'branches' => $this->branches(),
            'units' => $this->units(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $ingredient = new Ingredient();
            $ingredient->tenant_id = $this->tenantId();
            $this->fillIngredient($ingredient, $validated);
            $ingredient->save();

            DB::commit();

            app(LowStockAlertMailer::class)->notifyIfLow($ingredient);

            return redirect()->route('vendor.ingredients.index')->with('success', 'Ingredient created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Ingredient store query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Unable to save ingredient.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Ingredient store failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Something went wrong while creating the ingredient.']);
        }
    }

    public function edit($id)
    {
        $ingredient = Ingredient::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Inventory/Ingredient/CreateUpdate', [
            'ingredient' => $ingredient,
            'branches' => $this->branches(),
            'units' => $this->units(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($ingredient->id), $this->messages());

        try {
            DB::beginTransaction();

            $previousStock = (float) $ingredient->current_stock;
            $previousAlertQuantity = (float) $ingredient->alert_quantity;
            $this->fillIngredient($ingredient, $validated);
            $ingredient->save();

            DB::commit();

            app(LowStockAlertMailer::class)->notifyIfLow($ingredient, $previousStock, $previousAlertQuantity);

            return redirect()->route('vendor.ingredients.index')->with('success', 'Ingredient updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Ingredient update query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Unable to update ingredient.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Ingredient update failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Something went wrong while updating the ingredient.']);
        }
    }

    public function destroy($id)
    {
        try {
            $ingredient = Ingredient::where('tenant_id', $this->tenantId())->findOrFail($id);
            $ingredient->delete();

            return redirect()->route('vendor.ingredients.index')->with('success', 'Ingredient deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Ingredient delete failed', ['message' => $ex->getMessage()]);
            return back()->withErrors(['general' => 'Unable to delete ingredient.']);
        }
    }

    private function fillIngredient(Ingredient $ingredient, array $validated): void
    {
        $ingredient->branch_ids = $validated['branch_ids'] ?? null;
        $ingredient->unit_id = $validated['unit_id'];
        $ingredient->name = $validated['name'];
        $ingredient->current_stock = $validated['current_stock'] ?? 0;
        $ingredient->alert_quantity = $validated['alert_quantity'] ?? 0;
        $ingredient->cost_per_unit = $validated['cost_per_unit'] ?? 0;
          $ingredient->secondary_cost_per_unit = $validated['secondary_cost_per_unit'] ?? null;
        $ingredient->is_active = (bool) ($validated['is_active'] ?? true);
    }

    private function rules($ingredientId = null): array
    {
        return [
            'branch_ids' => [
                'nullable',
                'array',
            ],

            'branch_ids.*' => [
                'integer',
                Rule::exists('branches', 'id')
                    ->where(fn($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(fn($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'name' => ['required', 'string', 'max:255'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'alert_quantity' => ['nullable', 'numeric', 'min:0'],
          'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
'secondary_cost_per_unit' => ['nullable', 'numeric', 'min:0'],
'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Ingredient name is required.',
            'unit_id.required' => 'Please select a unit.',
        ];
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function units()
    {
        return Unit::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name', 'symbol')
            ->orderBy('name')
            ->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
