<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryAnalyticsController extends Controller
{
    private const OUTFLOW_TYPES = [
        'out',
        'wastage',
    ];

    public function index(Request $request)
    {
        $branchId = $request->integer('branch_id') ?: null;

        $from = $request->filled('from')
            ? Carbon::parse($request->string('from'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->string('to'))->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $branches = Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $dateLabels = $this->dateLabels($from, $to);

        return Inertia::render('VendorAdmin/Inventory/Analytics/Index', [
            'branches' => $branches,
            'filters' => [
                'branch_id' => $branchId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'stats' => [
                'total_stock_in_qty' => $this->totalStockInQty($branchId, $from, $to),
                'total_restocked_products' => $this->totalRestockedProducts($branchId, $from, $to),
                'total_movements' => $this->totalMovements($branchId, $from, $to),
                'total_wastage_qty' => $this->totalWastageQty($branchId, $from, $to),
                'low_stock_count' => $this->lowStockProducts()->count(),
            ],
            'charts' => [
                'fast_moving' => $this->fastMovingProductsChart($branchId, $from, $to, $dateLabels),
                'top_products' => $this->topRestockedProductsChart($branchId, $from, $to),
                'most_wasted' => $this->mostWastedProductsChart($branchId, $from, $to),
            ],
            'lists' => [
                'product_purchases_summary' => $this->productStockInSummary($branchId, $from, $to),
                'low_stock_products' => $this->lowStockProducts(),
            ],
        ]);
    }

    private function totalStockInQty(?int $branchId, Carbon $from, Carbon $to): float
    {
        return (float) ProductStockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'in')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->sum('quantity');
    }

    private function totalRestockedProducts(?int $branchId, Carbon $from, Carbon $to): int
    {
        return (int) ProductStockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'in')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->distinct('product_id')
            ->count('product_id');
    }

    private function totalMovements(?int $branchId, Carbon $from, Carbon $to): int
    {
        return (int) ProductStockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function totalWastageQty(?int $branchId, Carbon $from, Carbon $to): float
    {
        return (float) ProductStockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'wastage')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->sum('quantity');
    }

    private function fastMovingProductsChart(?int $branchId, Carbon $from, Carbon $to, Collection $dateLabels): array
    {
        $topProductIds = ProductStockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('type', self::OUTFLOW_TYPES)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->pluck('product_id');

        if ($topProductIds->isEmpty()) {
            return [
                'labels' => $dateLabels->values()->all(),
                'datasets' => [],
            ];
        }

        $products = Product::query()
            ->whereIn('id', $topProductIds)
            ->get()
            ->keyBy('id');

        $grouped = ProductStockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('type', self::OUTFLOW_TYPES)
            ->whereIn('product_id', $topProductIds)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->select(
                'product_id',
                DB::raw('DATE(created_at) as movement_date'),
                DB::raw('SUM(quantity) as total_qty')
            )
            ->groupBy('product_id', DB::raw('DATE(created_at)'))
            ->get()
            ->groupBy('product_id');

        $datasets = [];

        foreach ($topProductIds as $productId) {
            $product = $products->get($productId);
            if (!$product) {
                continue;
            }

            $rows = collect($grouped->get($productId, []))->keyBy('movement_date');

            $datasets[] = [
                'label' => $product->name,
                'unit_symbol' => $product->unit_type ?: 'pcs',
                'data' => $dateLabels->map(function ($date) use ($rows) {
                    return (float) optional($rows->get($date))->total_qty ?: 0;
                })->values()->all(),
            ];
        }

        return [
            'labels' => $dateLabels->values()->all(),
            'datasets' => $datasets,
        ];
    }

    private function topRestockedProductsChart(?int $branchId, Carbon $from, Carbon $to): array
    {
        $rows = ProductStockMovement::query()
            ->join('products', 'products.id', '=', 'product_stock_movements.product_id')
            ->where('product_stock_movements.tenant_id', $this->tenantId())
            ->where('product_stock_movements.type', 'in')
            ->when($branchId, fn ($q) => $q->where('product_stock_movements.branch_id', $branchId))
            ->whereBetween('product_stock_movements.created_at', [$from, $to])
            ->select('products.name', DB::raw('SUM(product_stock_movements.quantity) as total_qty'))
            ->groupBy('products.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total_qty')->map(fn ($v) => (float) $v)->all(),
        ];
    }

    private function productStockInSummary(?int $branchId, Carbon $from, Carbon $to): array
    {
        return ProductStockMovement::query()
            ->join('products', 'products.id', '=', 'product_stock_movements.product_id')
            ->where('product_stock_movements.tenant_id', $this->tenantId())
            ->where('product_stock_movements.type', 'in')
            ->when($branchId, fn ($q) => $q->where('product_stock_movements.branch_id', $branchId))
            ->whereBetween('product_stock_movements.created_at', [$from, $to])
            ->select(
                'products.name',
                'products.unit_type as unit_symbol',
                DB::raw('SUM(product_stock_movements.quantity) as total_qty')
            )
            ->groupBy('products.name', 'products.unit_type')
            ->orderByDesc('total_qty')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'quantity' => round((float) $row->total_qty, 3),
                'unit_symbol' => $row->unit_symbol,
            ])
            ->values()
            ->all();
    }

    private function mostWastedProductsChart(?int $branchId, Carbon $from, Carbon $to): array
    {
        $rows = ProductStockMovement::query()
            ->join('products', 'products.id', '=', 'product_stock_movements.product_id')
            ->where('product_stock_movements.tenant_id', $this->tenantId())
            ->where('product_stock_movements.type', 'wastage')
            ->when($branchId, fn ($q) => $q->where('product_stock_movements.branch_id', $branchId))
            ->whereBetween('product_stock_movements.created_at', [$from, $to])
            ->select('products.name', DB::raw('SUM(product_stock_movements.quantity) as total_qty'))
            ->groupBy('products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total_qty')->map(fn ($v) => (float) $v)->all(),
        ];
    }

    private function lowStockIngredients(?int $branchId): Collection
    {
        return Ingredient::query()
            ->with('unit:id,symbol')
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $this->scopeIngredientBranch($q, $branchId))
            ->whereColumn('current_stock', '<', 'alert_quantity')
            ->orderByRaw('(alert_quantity - current_stock) DESC')
            ->limit(12)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'current_stock' => round((float) $item->current_stock, 3),
                'alert_quantity' => round((float) $item->alert_quantity, 3),
                'unit_symbol' => $item->unit?->symbol,
            ]);
    }

    private function lowStockProducts(): Collection
    {
        return Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderByRaw('(reorder_level - current_stock) DESC')
            ->limit(12)
            ->get(['name', 'unit_type', 'current_stock', 'reorder_level'])
            ->map(fn ($item) => [
                'name' => $item->name,
                'current_stock' => round((float) $item->current_stock, 3),
                'alert_quantity' => round((float) $item->reorder_level, 3),
                'unit_symbol' => $item->unit_type ?: 'pcs',
            ]);
    }

    private function dateLabels(Carbon $from, Carbon $to): Collection
    {
        $labels = collect();
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $labels->push($cursor->toDateString());
            $cursor->addDay();
        }

        return $labels;
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
