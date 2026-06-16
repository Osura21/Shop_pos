<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryAnalyticsController extends Controller
{
    private const OUTFLOW_TYPES = [
        'out',
        'spoil',
        'adjust_subtract',
        'transfer_out',
        'return_supplier',
    ];

    private const WASTE_TYPES = [
        'adjust_subtract',
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
                'total_purchase_amount' => $this->totalPurchaseAmount($branchId, $from, $to),
                'total_purchase_orders' => $this->totalPurchaseOrders($branchId, $from, $to),
                'total_movements' => $this->totalMovements($branchId, $from, $to),
                'low_stock_count' => $this->lowStockIngredients($branchId)->count(),
            ],
            'charts' => [
                'fast_moving' => $this->fastMovingIngredientsChart($branchId, $from, $to, $dateLabels),
                'top_suppliers' => $this->topSuppliersChart($branchId, $from, $to),
                'most_wasted' => $this->mostWastedChart($branchId, $from, $to),
                'purchase_status' => $this->purchaseStatusChart($branchId, $from, $to),
                'stock_movement_summary' => $this->stockMovementSummaryChart($branchId, $from, $to),
                'wastage_spoilage' => $this->wastageSpoilageChart($branchId, $from, $to),
            ],
            'lists' => [
                'ingredient_purchases_summary' => $this->ingredientPurchasesSummary($branchId, $from, $to),
                'low_stock_ingredients' => $this->lowStockIngredients($branchId),
            ],
        ]);
    }

    private function totalPurchaseAmount(?int $branchId, Carbon $from, Carbon $to): float
    {
        return (float) Purchase::query()
            ->where('tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->sum('total');
    }

    private function totalPurchaseOrders(?int $branchId, Carbon $from, Carbon $to): int
    {
        return (int) Purchase::query()
            ->where('tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function totalMovements(?int $branchId, Carbon $from, Carbon $to): int
    {
        return (int) StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function fastMovingIngredientsChart(?int $branchId, Carbon $from, Carbon $to, Collection $dateLabels): array
    {
        $topIngredientIds = StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('type', self::OUTFLOW_TYPES)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->select('ingredient_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('ingredient_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->pluck('ingredient_id');

        if ($topIngredientIds->isEmpty()) {
            return [
                'labels' => $dateLabels->values()->all(),
                'datasets' => [],
            ];
        }

        $ingredients = Ingredient::query()
            ->with('unit:id,symbol')
            ->whereIn('id', $topIngredientIds)
            ->get()
            ->keyBy('id');

        $grouped = StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('type', self::OUTFLOW_TYPES)
            ->whereIn('ingredient_id', $topIngredientIds)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->select(
                'ingredient_id',
                DB::raw('DATE(created_at) as movement_date'),
                DB::raw('SUM(quantity) as total_qty')
            )
            ->groupBy('ingredient_id', DB::raw('DATE(created_at)'))
            ->get()
            ->groupBy('ingredient_id');

        $datasets = [];

        foreach ($topIngredientIds as $ingredientId) {
            $ingredient = $ingredients->get($ingredientId);
            if (!$ingredient) {
                continue;
            }

            $rows = collect($grouped->get($ingredientId, []))->keyBy('movement_date');

            $datasets[] = [
                'label' => $ingredient->name,
                'unit_symbol' => $ingredient->unit?->symbol,
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

    private function topSuppliersChart(?int $branchId, Carbon $from, Carbon $to): array
    {
        $rows = Purchase::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchases.tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('purchases.branch_id', $branchId))
            ->whereBetween('purchases.created_at', [$from, $to])
            ->select('suppliers.name', DB::raw('SUM(purchases.total) as total_amount'))
            ->groupBy('suppliers.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total_amount')->map(fn ($v) => (float) $v)->all(),
        ];
    }

    private function ingredientPurchasesSummary(?int $branchId, Carbon $from, Carbon $to): array
    {
        return PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('ingredients', 'ingredients.id', '=', 'purchase_items.ingredient_id')
            ->leftJoin('units', 'units.id', '=', 'ingredients.unit_id')
            ->where('purchases.tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('purchases.branch_id', $branchId))
            ->whereBetween('purchases.created_at', [$from, $to])
            ->select(
                'ingredients.name',
                'units.symbol as unit_symbol',
                DB::raw('SUM(purchase_items.quantity) as total_qty')
            )
            ->groupBy('ingredients.name', 'units.symbol')
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

    private function mostWastedChart(?int $branchId, Carbon $from, Carbon $to): array
    {
        $rows = StockMovement::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_movements.ingredient_id')
            ->where('stock_movements.tenant_id', $this->tenantId())
            ->where('stock_movements.type', 'spoil')
            ->when($branchId, fn ($q) => $q->where('stock_movements.branch_id', $branchId))
            ->whereBetween('stock_movements.created_at', [$from, $to])
            ->select('ingredients.name', DB::raw('SUM(stock_movements.quantity) as total_qty'))
            ->groupBy('ingredients.name')
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

    private function scopeIngredientBranch($query, int $branchId): void
    {
        $query->where(function ($q) use ($branchId) {
            $q->whereNull('branch_ids')
                ->orWhereRaw('JSON_LENGTH(branch_ids) = 0')
                ->orWhereJsonContains('branch_ids', $branchId)
                ->orWhereJsonContains('branch_ids', (string) $branchId);
        });
    }

    private function purchaseStatusChart(?int $branchId, Carbon $from, Carbon $to): array
    {
        $rows = Purchase::query()
            ->where('tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as total_count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $labels = ['pending', 'partially_received', 'received', 'cancelled'];

        return [
            'labels' => collect($labels)->map(fn ($status) => match ($status) {
                'pending' => 'Pending',
                'partially_received' => 'Partially Received',
                'received' => 'Received',
                'cancelled' => 'Cancelled',
                default => ucfirst($status),
            })->all(),
            'data' => collect($labels)->map(fn ($status) => (int) optional($rows->get($status))->total_count ?: 0)->all(),
        ];
    }

    private function stockMovementSummaryChart(?int $branchId, Carbon $from, Carbon $to): array
    {
        $rows = StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->select('type', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $types = [
            'spoil',
            'transfer_out',
            'adjust_add',
            'adjust_subtract',
            'in',
            'transfer_in',
            'waste',
            'sample',
            'out',
            'return_supplier',
        ];

        return [
            'labels' => collect($types)->map(fn ($type) => match ($type) {
                'spoil' => 'Spoil',
                'transfer_out' => 'Transfer Out',
                'adjust_add' => 'Adjust Add',
                'adjust_subtract' => 'Adjust Subtract',
                'in' => 'In',
                'transfer_in' => 'Transfer In',
                'waste' => 'Waste',
                'sample' => 'Sample',
                'out' => 'Out',
                'return_supplier' => 'Return Supplier',
                default => ucfirst(str_replace('_', ' ', $type)),
            })->all(),
            'data' => collect($types)->map(fn ($type) => (float) optional($rows->get($type))->total_qty ?: 0)->all(),
        ];
    }

    private function wastageSpoilageChart(?int $branchId, Carbon $from, Carbon $to): array
    {
        $spoil = (float) StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'spoil')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->sum('quantity');

        $waste = (float) StockMovement::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('type', self::WASTE_TYPES)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$from, $to])
            ->sum('quantity');

        return [
            'labels' => ['Spoil', 'Waste'],
            'data' => [$spoil, $waste],
        ];
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
