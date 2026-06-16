<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Ingredient;
use App\Models\PosInvoice;
use App\Models\PosInvoiceItem;
use App\Models\PosKitchenTicket;
use App\Models\Product;
use App\Models\Theme;
use App\Models\TenantCurrencySetting;
use App\Models\User;
use App\Models\Tenant;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/Dashboard', [
            'dashboardDataUrl' => route('superadmin.dashboard.data'),
        ]);
    }

    public function data()
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $weekStart = $today->copy()->startOfWeek();
        $previousMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $today->copy()->subMonthNoOverflow()->endOfMonth();

        $monthlyRevenue = (float) PosInvoice::query()
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$monthStart, $today])
            ->sum('total');

        $previousMonthlyRevenue = (float) PosInvoice::query()
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total');

        $ordersThisMonth = (int) PosKitchenTicket::query()
            ->whereBetween('created_at', [$monthStart, $today])
            ->count();

        $pendingOrders = (int) PosKitchenTicket::query()
            ->where('status', 'pending')
            ->count();

        $productsCount = (int) Product::query()->count();

        $newProductsThisWeek = (int) Product::query()
            ->where('created_at', '>=', $weekStart)
            ->count();

        $staffCount = (int) User::query()->count();

        $activeStaffCount = (int) User::query()->where('status', true)->count();

        return response()->json([
            'currencyCode' => config('app.currency', 'USD'),
            'stats' => [
                [
                    'title' => 'Vendors',
                    'value' => (int) Tenant::query()->count(),
                    'subtitle' => Tenant::query()->where('status', 'active')->count() . ' active',
                    'icon' => 'shop',
                    'accent' => 'orange',
                ],
                [
                    'title' => 'Orders',
                    'value' => $ordersThisMonth,
                    'subtitle' => $pendingOrders . ' pending',
                    'icon' => 'shopping-cart',
                    'accent' => 'blue',
                ],
                [
                    'title' => 'Revenue',
                    'value' => $monthlyRevenue,
                    'subtitle' => $this->percentageChange($monthlyRevenue, $previousMonthlyRevenue) . ' vs last month',
                    'icon' => 'wallet',
                    'accent' => 'green',
                ],
                [
                    'title' => 'Staff',
                    'value' => $staffCount,
                    'subtitle' => $activeStaffCount . ' active',
                    'icon' => 'users',
                    'accent' => 'purple',
                ],
            ],
            'summary' => [
                'branches' => Branch::query()->count(),
                'active_branches' => Branch::query()->where('is_active', true)->count(),
                'domains' => Domain::query()->count(),
                'customers' => Customer::query()->count(),
                'products' => $productsCount,
                'new_products_this_week' => $newProductsThisWeek,
                'low_stock' => Ingredient::query()->where('alert_quantity', '>', 0)->whereColumn('current_stock', '<=', 'alert_quantity')->count(),
                'average_order_value' => $ordersThisMonth > 0 ? round($monthlyRevenue / $ordersThisMonth, 2) : 0,
                'themes' => Theme::query()->count(),
            ],
            'charts' => [
                'revenue' => $this->revenueSeries(),
                'orders' => $this->ordersSeries(),
                'statuses' => $this->statusBreakdown(),
            ],
            'recentOrders' => $this->recentOrders(),
            'topProducts' => $this->topProducts(),
            'lowStockItems' => $this->lowStockItems(),
            'vendorOptions' => $this->vendorOptions(),
            'vendorDetails' => $this->vendorDetails(),
        ]);
    }

    private function revenueSeries(?int $tenantId = null): array
    {
        $from = now()->copy()->subDays(6)->startOfDay();
        $to = now()->copy()->endOfDay();

        $rows = PosInvoice::query()
            ->where('status', 'issued')
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('DATE(issued_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->dateLabels($from, $to, fn (Carbon $date) => (float) ($rows[$date->toDateString()] ?? 0));
    }

    private function ordersSeries(?int $tenantId = null): array
    {
        $from = now()->copy()->subDays(6)->startOfDay();
        $to = now()->copy()->endOfDay();

        $rows = PosKitchenTicket::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->dateLabels($from, $to, fn (Carbon $date) => (int) ($rows[$date->toDateString()] ?? 0));
    }

    private function dateLabels(Carbon $from, Carbon $to, callable $valueResolver): array
    {
        $labels = [];
        $values = [];

        foreach (CarbonPeriod::create($from, '1 day', $to) as $date) {
            $labels[] = $date->format('D');
            $values[] = $valueResolver($date);
        }

        return compact('labels', 'values');
    }

    private function statusBreakdown(?int $tenantId = null): array
    {
        $labels = [
            'pending' => 'Pending',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'served' => 'Served',
            'cancelled' => 'Cancelled',
        ];

        $rows = PosKitchenTicket::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('created_at', '>=', now()->copy()->subDays(30))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => array_values($labels),
            'values' => collect(array_keys($labels))->map(fn ($status) => (int) ($rows[$status] ?? 0))->values()->all(),
        ];
    }

    private function recentOrders(?int $tenantId = null, int $limit = 6)
    {
        return PosKitchenTicket::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->latest('created_at')
            ->limit($limit)
            ->get(['id', 'customer_name', 'channel', 'status', 'grand_total', 'created_at'])
            ->map(fn ($order) => [
                'id' => $order->id,
                'customer' => $order->customer_name ?: 'Walk-In Customer',
                'channel' => str($order->channel ?: 'takeaway')->replace('_', ' ')->title()->toString(),
                'status' => str($order->status)->replace('_', ' ')->title()->toString(),
                'amount' => (float) $order->grand_total,
                'date' => $order->created_at?->diffForHumans() ?: '-',
            ]);
    }

    private function topProducts(?int $tenantId = null, int $limit = 5)
    {
        return PosInvoiceItem::query()
            ->join('pos_invoices', 'pos_invoices.id', '=', 'pos_invoice_items.pos_invoice_id')
            ->where('pos_invoices.status', 'issued')
            ->when($tenantId, fn ($query) => $query->where('pos_invoices.tenant_id', $tenantId))
            ->where('pos_invoices.issued_at', '>=', now()->copy()->subDays(30))
            ->select('pos_invoice_items.product_name', DB::raw('SUM(pos_invoice_items.qty) as qty'), DB::raw('SUM(pos_invoice_items.line_total) as total'))
            ->groupBy('pos_invoice_items.product_name')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'qty' => (float) $row->qty,
                'total' => (float) $row->total,
            ]);
    }

    private function lowStockItems(?int $tenantId = null, int $limit = 5)
    {
        return Ingredient::query()
            ->with('unit:id,symbol')
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('alert_quantity', '>', 0)
            ->whereColumn('current_stock', '<=', 'alert_quantity')
            ->orderByRaw('(alert_quantity - current_stock) DESC')
            ->limit($limit)
            ->get(['id', 'name', 'unit_id', 'current_stock', 'alert_quantity'])
            ->map(fn ($ingredient) => [
                'name' => $ingredient->name,
                'current_stock' => (float) $ingredient->current_stock,
                'alert_quantity' => (float) $ingredient->alert_quantity,
                'unit' => $ingredient->unit?->symbol,
            ]);
    }

    private function vendorOptions()
    {
        return Tenant::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status'])
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => str($tenant->status)->replace('_', ' ')->title()->toString(),
            ]);
    }

    private function vendorDetails()
    {
        return Tenant::query()
            ->with(['theme:id,name', 'domains:id,tenant_id,domain,is_primary'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'theme_id', 'status', 'address', 'contact', 'created_at'])
            ->mapWithKeys(function (Tenant $tenant) {
                $tenantId = (int) $tenant->id;
                $monthStart = now()->copy()->startOfMonth();
                $today = now();
                $monthlyRevenue = (float) PosInvoice::query()
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'issued')
                    ->whereBetween('issued_at', [$monthStart, $today])
                    ->sum('total');
                $ordersThisMonth = (int) PosKitchenTicket::query()
                    ->where('tenant_id', $tenantId)
                    ->whereBetween('created_at', [$monthStart, $today])
                    ->count();
                $currencySetting = TenantCurrencySetting::query()
                    ->where('tenant_id', $tenantId)
                    ->first(['base_currency_code', 'secondary_currency_code']);
                $baseCurrencyCode = $currencySetting?->base_currency_code ?: config('app.currency', 'USD');

                $branches = Branch::query()
                    ->where('tenant_id', $tenantId)
                    ->orderByDesc('is_active')
                    ->orderBy('name')
                    ->get(['id', 'name', 'city', 'country', 'currency', 'is_active'])
                    ->map(fn ($branch) => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'location' => collect([$branch->city, $branch->country])->filter()->join(', ') ?: '-',
                        'currency' => $branch->currency ?: config('app.currency', 'USD'),
                        'status' => $branch->is_active ? 'Active' : 'Inactive',
                    ]);

                return [
                    $tenantId => [
                        'id' => $tenantId,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'status' => str($tenant->status)->replace('_', ' ')->title()->toString(),
                        'address' => $tenant->address ?: '-',
                        'contact' => $tenant->contact ?: '-',
                        'theme' => $tenant->theme?->name ?: '-',
                        'currencies' => [
                            'base' => $baseCurrencyCode,
                            'secondary' => $currencySetting?->secondary_currency_code,
                        ],
                        'primary_domain' => $tenant->domains->firstWhere('is_primary', true)?->domain
                            ?: $tenant->domains->first()?->domain
                            ?: '-',
                        'created_at' => $tenant->created_at?->format('M d, Y') ?: '-',
                        'stats' => [
                            'branches' => $branches->count(),
                            'active_branches' => $branches->where('status', 'Active')->count(),
                            'domains' => $tenant->domains->count(),
                            'customers' => Customer::query()->where('tenant_id', $tenantId)->count(),
                            'products' => Product::query()->where('tenant_id', $tenantId)->count(),
                            'active_products' => Product::query()->where('tenant_id', $tenantId)->where('is_active', true)->count(),
                            'staff' => User::query()->where('tenant_id', $tenantId)->count(),
                            'active_staff' => User::query()->where('tenant_id', $tenantId)->where('status', true)->count(),
                            'orders_this_month' => $ordersThisMonth,
                            'pending_orders' => PosKitchenTicket::query()->where('tenant_id', $tenantId)->where('status', 'pending')->count(),
                            'revenue_this_month' => round($monthlyRevenue, 2),
                            'average_order_value' => $ordersThisMonth > 0 ? round($monthlyRevenue / $ordersThisMonth, 2) : 0,
                            'low_stock' => Ingredient::query()
                                ->where('tenant_id', $tenantId)
                                ->where('alert_quantity', '>', 0)
                                ->whereColumn('current_stock', '<=', 'alert_quantity')
                                ->count(),
                        ],
                        'charts' => [
                            'revenue' => $this->revenueSeries($tenantId),
                            'orders' => $this->ordersSeries($tenantId),
                            'statuses' => $this->statusBreakdown($tenantId),
                        ],
                        'branches' => $branches,
                        'recent_orders' => $this->recentOrders($tenantId, 5),
                        'top_products' => $this->topProducts($tenantId, 5),
                        'low_stock_items' => $this->lowStockItems($tenantId, 5),
                    ],
                ];
            });
    }

    private function percentageChange(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $change = (($current - $previous) / $previous) * 100;

        return ($change >= 0 ? '+' : '') . number_format($change, 1) . '%';
    }
}
