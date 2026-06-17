<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductOption;
use App\Models\ProductOptionRow;
use App\Models\ProductStockMovement;
use App\Models\OptionTemplate;
use App\Models\Tax;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

use Inertia\Inertia;

class ProductController extends Controller
{
    private const OPTION_TYPES = [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'select' => 'Select',
        'multiple_select' => 'Multiple Select',
        'checkbox' => 'Checkbox',
        'radio_button' => 'Radio Button',
        'date' => 'Date',
        'time' => 'Time',
    ];

    private const PRICE_TYPES = [
        'fixed' => 'Fixed',
        'percentage' => 'Percentage',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Menu/Product/Index');
    }

    public function getData(Request $request)
{
    $products = Product::query()
        ->where('tenant_id', $this->tenantId())
        ->select('products.*');

    return DataTables::of($products)

        ->filter(function ($query) use ($request) {
            $search = $request->input('search.value');

            if (filled($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
        })

        ->addColumn('image_url', function ($row) {
            return $row->image_url;
        })

        ->addColumn('price_display', function ($row) {
            $base = number_format((float) $row->base_price, 2);

            if (!is_null($row->secondary_price)) {
                $secondary = number_format((float) $row->secondary_price, 2);
                return $base . ' / ' . $secondary;
            }

            return $base;
        })

        ->addColumn('current_stock_display', function ($row) {
            $unit = trim((string) ($row->unit_type ?? ''));

            return number_format((float) $row->current_stock, 2) . ($unit !== '' ? ' ' . $unit : '');
        })

        ->addColumn('activation_badge', function ($row) {

    $isActive = (int) $row->is_active === 1;

    if ($isActive) {
        return '
            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-check-circle-fill"></i>
                Active
            </span>
        ';
    }

    return '
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-x-circle-fill text-danger"></i>
            Inactive
        </span>
    ';
})

        ->editColumn('created_at', function ($row) {
            return optional($row->created_at)?->format('Y-m-d H:i') ?: '-';
        })

        ->editColumn('updated_at', function ($row) {
            return optional($row->updated_at)?->format('Y-m-d H:i') ?: '-';
        })

        ->rawColumns(['activation_badge', 'image_url', 'price_display'])
        ->make(true);
}
    public function create()
    {
        return Inertia::render('VendorAdmin/Menu/Product/CreateUpdate', [
            'product' => null,
            'categories' => $this->categoryOptions(),
            'taxes' => $this->taxes(),
            'ingredients' => $this->ingredients(),
            'optionTypes' => self::OPTION_TYPES,
            'priceTypes' => self::PRICE_TYPES,
            'templates' => $this->optionTemplates(),
        ]);
    }

    public function store(Request $request)
    {

    // dd($request->all());
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $product = new Product();
            $product->tenant_id = $this->tenantId();

            $this->fillProduct($product, $validated, $request);
            $product->save();
            $this->recordOpeningProductStock($product);

            $this->syncCategories($product, $validated['category_ids'] ?? []);
            $this->syncTaxes($product, $validated['tax_ids'] ?? []);
            $this->syncIngredients($product, $validated['ingredients'] ?? []);
            $this->syncOptions($product, $validated['options'] ?? []);

            DB::commit();

            return redirect()
                ->route('vendor.products.index')
                ->with('success', 'Product created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Product store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to save product.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Product store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while creating the product.',
            ]);
        }
    }

    public function edit($id)
    {
        $product = Product::query()
            ->with([
                'categories:id,name',
                'taxes:id,name',
                'ingredients.ingredient:id,name,unit_id',
                'ingredients.ingredient.unit:id,name,symbol',
                'options.rows',
            ])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        return Inertia::render('VendorAdmin/Menu/Product/CreateUpdate', [
            'product' => $this->mapProductForForm($product),
            'categories' => $this->categoryOptions(),
            'taxes' => $this->taxes(),
            'ingredients' => $this->ingredients(),
            'optionTypes' => self::OPTION_TYPES,
            'priceTypes' => self::PRICE_TYPES,
            'templates' => $this->optionTemplates(),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless((int) $product->tenant_id === $this->tenantId(), 404);

        $product->load([
            'categories:id,name',
            'taxes:id,name,rate',
            'ingredients.ingredient:id,tenant_id,name,current_stock,alert_quantity,unit_id,cost_per_unit,secondary_cost_per_unit,is_active',
            'ingredients.ingredient.unit:id,name,symbol',
            'options.rows',
        ]);

        return response()->json($this->mapProductForDetails($product));
    }

    public function update(Request $request, $id)
    {
        $product = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        $validated = $request->validate($this->rules($product->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillProduct($product, $validated, $request);
            $product->save();

            $this->syncCategories($product, $validated['category_ids'] ?? []);
            $this->syncTaxes($product, $validated['tax_ids'] ?? []);
            $this->syncIngredients($product, $validated['ingredients'] ?? []);
            $this->syncOptions($product, $validated['options'] ?? []);

            DB::commit();

            return redirect()
                ->route('vendor.products.index')
                ->with('success', 'Product updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Product update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to update product.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Product update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while updating the product.',
            ]);
        }
    }

    public function destroy($id)
    {
        $product = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()
            ->route('vendor.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function fillProduct(Product $product, array $validated, Request $request): void
    {
        $firstCategory = null;

        if (!empty($validated['category_ids'][0])) {
            $firstCategory = Category::query()
                ->where('tenant_id', $this->tenantId())
                ->find($validated['category_ids'][0]);
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->image_path = $request->file('image')->store('products', 'public');
        }

        if (!empty($validated['remove_image']) && $validated['remove_image']) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->image_path = null;
        }

        $product->menu_id = $firstCategory?->menu_id;
        $product->name = $validated['name'];
        $product->sku = $validated['sku'];
        $product->brand = $validated['brand'] ?? null;
        $product->unit_type = $validated['unit_type'] ?? 'pcs';
        $product->is_loose_item = (bool) ($validated['is_loose_item'] ?? false);
        $product->description = $validated['description'] ?? null;

        $product->base_price = $validated['base_price'] ?? 0;
        $product->secondary_price = $validated['secondary_price'] ?? null;
        $product->cost_price = $validated['cost_price'] ?? 0;
        if (! $product->exists) {
            $product->current_stock = $validated['current_stock'] ?? 0;
        }
        $product->reorder_level = $validated['reorder_level'] ?? 0;

        $product->special_price_type = $validated['special_price_type'] ?? 'fixed';
        $product->base_special_price = $validated['base_special_price'] ?? null;
        $product->secondary_special_price = $validated['secondary_special_price'] ?? null;

        $product->special_price_start = $validated['special_price_start'] ?? null;
        $product->special_price_end = $validated['special_price_end'] ?? null;

        $product->new_from = $validated['new_from'] ?? null;
        $product->new_to = $validated['new_to'] ?? null;

        $product->is_active = (bool) ($validated['is_active'] ?? true);
    }

    private function syncCategories(Product $product, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $product->categories()->sync($ids);
    }

    private function recordOpeningProductStock(Product $product): void
    {
        $quantity = (float) ($product->current_stock ?? 0);

        if ($quantity <= 0) {
            return;
        }

        ProductStockMovement::create([
            'tenant_id' => $this->tenantId(),
            'branch_id' => null,
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => $quantity,
            'stock_before' => 0,
            'stock_after' => $quantity,
            'note' => 'Opening stock from product create',
        ]);
    }

    private function syncTaxes(Product $product, array $taxIds): void
    {
        $ids = collect($taxIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $product->taxes()->sync($ids);
    }

    private function syncIngredients(Product $product, array $ingredients): void
    {
        $product->ingredients()->delete();

        foreach ($ingredients as $index => $item) {
            if (empty($item['ingredient_id']) || (float) ($item['quantity'] ?? 0) <= 0) {
                continue;
            }

            ProductIngredient::create([
                'product_id' => $product->id,
                'ingredient_id' => $item['ingredient_id'],
                'quantity' => $item['quantity'],
                'loss_pct' => $item['loss_pct'] ?? 0,
                'note' => $item['note'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncOptions(Product $product, array $options): void
    {
        $product->options()->delete();

        foreach ($options as $index => $option) {
            if (blank($option['name'] ?? null)) {
                continue;
            }

            $productOption = ProductOption::create([
                'product_id' => $product->id,
                'template_key' => $option['template_key'] ?? null,
                'name' => $option['name'],
                'type' => $option['type'],
                'is_required' => (bool) ($option['is_required'] ?? false),
                'base_price' => $option['base_price'] ?? 0,
                'secondary_price' => $option['secondary_price'] ?? null,
                'price_type' => $option['price_type'] ?? 'fixed',
                'sort_order' => $index,
                'is_collapsed' => (bool) ($option['is_collapsed'] ?? false),
            ]);

            foreach (($option['rows'] ?? []) as $rowIndex => $row) {
                if (blank($row['label'] ?? null)) {
                    continue;
                }

                ProductOptionRow::create([
                    'product_option_id' => $productOption->id,
                    'label' => $row['label'],
                    'base_price' => $row['base_price'] ?? 0,
                    'secondary_price' => $row['secondary_price'] ?? null,
                    'price_type' => $row['price_type'] ?? 'fixed',
                    'sort_order' => $rowIndex,
                ]);
            }
        }
    }

    private function mapProductForForm(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'brand' => $product->brand,
            'unit_type' => $product->unit_type,
            'is_loose_item' => (bool) $product->is_loose_item,
            'description' => $product->description,
            'image_url' => $product->image_url,
            'base_price' => $product->base_price,
            'secondary_price' => $product->secondary_price,
            'cost_price' => $product->cost_price,
            'current_stock' => $product->current_stock,
            'reorder_level' => $product->reorder_level,
            'special_price_type' => $product->special_price_type,
            'base_special_price' => $product->base_special_price,
            'secondary_special_price' => $product->secondary_special_price,
            'special_price_start' => optional($product->special_price_start)?->format('Y-m-d\TH:i'),
            'special_price_end' => optional($product->special_price_end)?->format('Y-m-d\TH:i'),
            'new_from' => optional($product->new_from)?->format('Y-m-d\TH:i'),
            'new_to' => optional($product->new_to)?->format('Y-m-d\TH:i'),
            'is_active' => (bool) $product->is_active,
            'category_ids' => $product->categories->pluck('id')->values()->all(),
            'tax_ids' => $product->taxes->pluck('id')->values()->all(),
            'ingredients' => $product->ingredients->map(fn ($item) => [
                'ingredient_id' => $item->ingredient_id,
                'quantity' => $item->quantity,
                'loss_pct' => $item->loss_pct,
                'note' => $item->note,
            ])->values()->all(),
            'options' => $product->options->map(fn ($option) => [
                'template_key' => $option->template_key,
                'name' => $option->name,
                'type' => $option->type,
                'is_required' => (bool) $option->is_required,
                'base_price' => $option->base_price,
                'secondary_price' => $option->secondary_price,
                'price_type' => $option->price_type,
                'is_collapsed' => (bool) $option->is_collapsed,
                'rows' => $option->rows->map(fn ($row) => [
                    'label' => $row->label,
                    'base_price' => $row->base_price,
                    'secondary_price' => $row->secondary_price,
                    'price_type' => $row->price_type,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function mapProductForDetails(Product $product): array
    {
        $ingredients = $product->ingredients
            ->map(fn (ProductIngredient $item) => $this->mapIngredientAnalysisRow($item))
            ->filter()
            ->values();

        $canMakeValues = $ingredients
            ->pluck('can_make')
            ->filter(fn ($value) => $value !== null)
            ->values();

        $canMake = $canMakeValues->isNotEmpty() ? (int) $canMakeValues->min() : null;
        $limiting = $ingredients
            ->filter(fn ($row) => $row['can_make'] !== null)
            ->sortBy('can_make')
            ->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'brand' => $product->brand,
            'unit_type' => $product->unit_type,
            'is_loose_item' => (bool) $product->is_loose_item,
            'description' => $product->description,
            'image_url' => $product->image_url,
            'base_price' => $this->moneyValue($product->base_price),
            'secondary_price' => $product->secondary_price === null ? null : $this->moneyValue($product->secondary_price),
            'cost_price' => $this->moneyValue($product->cost_price ?? 0),
            'current_stock' => $this->moneyValue($product->current_stock ?? 0),
            'reorder_level' => $this->moneyValue($product->reorder_level ?? 0),
            'special_price_type' => $product->special_price_type,
            'base_special_price' => $product->base_special_price === null ? null : $this->moneyValue($product->base_special_price),
            'secondary_special_price' => $product->secondary_special_price === null ? null : $this->moneyValue($product->secondary_special_price),
            'special_price_start' => optional($product->special_price_start)?->format('Y-m-d H:i'),
            'special_price_end' => optional($product->special_price_end)?->format('Y-m-d H:i'),
            'new_from' => optional($product->new_from)?->format('Y-m-d H:i'),
            'new_to' => optional($product->new_to)?->format('Y-m-d H:i'),
            'is_active' => (bool) $product->is_active,
            'created_at' => optional($product->created_at)?->format('Y-m-d H:i'),
            'updated_at' => optional($product->updated_at)?->format('Y-m-d H:i'),
            'categories' => $product->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values(),
            'taxes' => $product->taxes->map(fn ($tax) => [
                'id' => $tax->id,
                'name' => $tax->name,
                'rate' => $tax->rate ?? null,
            ])->values(),
            'options' => $product->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'type' => $option->type,
                'is_required' => (bool) $option->is_required,
                'base_price' => $this->moneyValue($option->base_price),
                'secondary_price' => $option->secondary_price === null ? null : $this->moneyValue($option->secondary_price),
                'price_type' => $option->price_type,
                'rows' => $option->rows->map(fn ($row) => [
                    'label' => $row->label,
                    'base_price' => $this->moneyValue($row->base_price),
                    'secondary_price' => $row->secondary_price === null ? null : $this->moneyValue($row->secondary_price),
                    'price_type' => $row->price_type,
                ])->values(),
            ])->values(),
            'analysis' => [
                'ingredient_count' => $ingredients->count(),
                'low_ingredient_count' => $ingredients->where('is_low', true)->count(),
                'unavailable_ingredient_count' => $ingredients->where('is_unavailable', true)->count(),
                'can_make' => $canMake,
                'limiting_ingredient' => $limiting['name'] ?? null,
                'estimated_base_cost' => round($ingredients->sum('base_cost_total'), 3),
                'estimated_secondary_cost' => round($ingredients->sum('secondary_cost_total'), 3),
                'ingredients' => $ingredients,
            ],
        ];
    }

    private function mapIngredientAnalysisRow(ProductIngredient $item): ?array
    {
        $ingredient = $item->ingredient;

        if (! $ingredient) {
            return null;
        }

        $required = $this->recipeQuantityPerProduct($item);
        $currentStock = max(0, (float) $ingredient->current_stock);
        $alertQuantity = (float) $ingredient->alert_quantity;
        $canMake = $required > 0 ? (int) floor($currentStock / $required) : null;
        $unit = $ingredient->unit?->symbol ?: $ingredient->unit?->name ?: '';
        $baseCost = (float) $ingredient->cost_per_unit;
        $secondaryCost = (float) $ingredient->secondary_cost_per_unit;

        return [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'unit' => $unit,
            'is_active' => (bool) $ingredient->is_active,
            'quantity' => round((float) $item->quantity, 4),
            'loss_pct' => round((float) $item->loss_pct, 2),
            'required_per_product' => round($required, 4),
            'current_stock' => round($currentStock, 3),
            'alert_quantity' => round($alertQuantity, 3),
            'can_make' => $canMake,
            'is_low' => $alertQuantity > 0 && $currentStock <= $alertQuantity,
            'is_unavailable' => $required > 0 && $currentStock < $required,
            'base_cost_per_unit' => round($baseCost, 3),
            'secondary_cost_per_unit' => round($secondaryCost, 3),
            'base_cost_total' => round($baseCost * $required, 3),
            'secondary_cost_total' => round($secondaryCost * $required, 3),
            'note' => $item->note,
        ];
    }

    private function recipeQuantityPerProduct(ProductIngredient $item): float
    {
        $baseQuantity = (float) $item->quantity;
        $lossQuantity = $baseQuantity * ((float) $item->loss_pct / 100);

        return round($baseQuantity + $lossQuantity, 4);
    }

    private function moneyValue($value): string
    {
        return number_format((float) $value, 2);
    }

    private function rules(?int $productId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($productId),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'brand' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['required', Rule::in(['pcs', 'kg', 'g', 'l', 'ml', 'pack', 'box'])],
            'is_loose_item' => ['nullable', 'boolean'],

            'base_price' => ['required', 'numeric', 'min:0'],
            'secondary_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],

            'special_price_type' => ['nullable', Rule::in(array_keys(self::PRICE_TYPES))],
            'base_special_price' => ['nullable', 'numeric', 'min:0'],
            'secondary_special_price' => ['nullable', 'numeric', 'min:0'],

            'special_price_start' => ['nullable', 'date', 'after_or_equal:today'],
            'special_price_end' => ['nullable', 'date', 'after_or_equal:special_price_start'],
            'new_from' => ['nullable', 'date', 'after_or_equal:today'],
            'new_to' => ['nullable', 'date', 'after_or_equal:new_from'],

            'is_active' => ['nullable', 'boolean'],

            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],

            'tax_ids' => ['nullable', 'array'],
            'tax_ids.*' => ['integer', Rule::exists('taxes', 'id')],

            'ingredients' => ['nullable', 'array'],
            'ingredients.*.ingredient_id' => [
                'required_with:ingredients.*.quantity',
                'integer',
                Rule::exists('ingredients', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.loss_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],

            'options' => ['nullable', 'array'],
            'options.*.template_key' => ['nullable', 'string', 'max:100'],
            'options.*.name' => ['required_with:options.*.type', 'string', 'max:255'],
            'options.*.type' => ['required_with:options.*.name', Rule::in(array_keys(self::OPTION_TYPES))],
            'options.*.is_required' => ['nullable', 'boolean'],
            'options.*.base_price' => ['nullable', 'numeric', 'min:0'],
            'options.*.secondary_price' => ['nullable', 'numeric', 'min:0'],
            'options.*.price_type' => ['nullable', Rule::in(array_keys(self::PRICE_TYPES))],
            'options.*.is_collapsed' => ['nullable', 'boolean'],

            'options.*.rows' => ['nullable', 'array'],
            'options.*.rows.*.label' => ['nullable', 'string', 'max:255'],
            'options.*.rows.*.base_price' => ['nullable', 'numeric', 'min:0'],
            'options.*.rows.*.secondary_price' => ['nullable', 'numeric', 'min:0'],
            'options.*.rows.*.price_type' => ['nullable', Rule::in(array_keys(self::PRICE_TYPES))],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'sku.required' => 'Barcode / SKU is required.',
            'sku.unique' => 'This barcode / SKU already exists.',
            'base_price.required' => 'Base Price is required.',
        ];
    }

    private function categoryOptions(): Collection
    {
        $items = Category::query()
            ->with('foodCategory:id,name')
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'menu_id', 'parent_id', 'food_category_id', 'name', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $result = [];
        $this->appendCategoryOptions($items, null, 0, $result);

        return collect($result);
    }

    private function appendCategoryOptions(Collection $items, $parentId, int $depth, array &$result): void
    {
        $children = $items
            ->where('parent_id', $parentId)
            ->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ]);

        foreach ($children as $item) {
            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';

            $result[] = [
                'id' => $item->id,
                'menu_id' => $item->menu_id,
                'parent_id' => $item->parent_id,
                'food_category_id' => $item->food_category_id,
                'food_category_name' => $item->foodCategory?->name,
                'name' => $item->name,
                'display_name' => $prefix . $item->name,
                'depth' => $depth,
            ];

            $this->appendCategoryOptions($items, $item->id, $depth + 1, $result);
        }
    }

   private function taxes()
{
    return Tax::query()
        ->where('tenant_id', $this->tenantId())
        ->where('is_active', true)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();
}

    private function ingredients(): Collection
    {
        return Ingredient::query()
            ->with('unit:id,name,symbol')
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name', 'unit_id', 'cost_per_unit', 'secondary_cost_per_unit')
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'unit_name' => $item->unit?->name,
                'unit_symbol' => $item->unit?->symbol,
                'base_cost' => $item->cost_per_unit,
                'secondary_cost' => $item->secondary_cost_per_unit,
            ]);
    }

   private function optionTemplates(): array
{
    return OptionTemplate::query()
        ->with(['values'])
        ->where('tenant_id', $this->tenantId())
        ->orderBy('name')
        ->get()
        ->map(fn ($option) => [
            'key' => 'option-' . $option->id,
            'label' => $option->name,
            'name' => $option->name,
            'type' => $option->type,
            'is_required' => (bool) $option->is_required,
            'base_price' => $option->base_price,
            'secondary_price' => $option->secondary_price,
            'price_type' => $option->price_type,
            'rows' => $option->values->map(fn ($row) => [
                'label' => $row->label,
                'base_price' => $row->base_price,
                'secondary_price' => $row->secondary_price,
                'price_type' => $row->price_type,
            ])->values()->all(),
        ])
        ->values()
        ->all();
}
    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
public function toggleStatus(Product $product)
{
    abort_unless((int) $product->tenant_id === (int) optional(auth('vendor')->user())->tenant_id, 403);

    $product->is_active = ! $product->is_active;
    $product->save();

    return back()->with('success', 'Product status updated successfully.');
}

}
