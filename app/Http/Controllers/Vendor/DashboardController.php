<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\PosInvoice;
use App\Models\PosInvoiceItem;
use App\Models\PosKitchenTicket;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class DashboardController extends Controller
{
    use ResolvesTenantContext;

    public function index()
    {
        return Inertia::render('VendorAdmin/Dashboard', [
            'dashboardDataUrl' => route('vendor.dashboard.data'),
        ]);
    }

    public function data()
    {
        $tenantId = $this->tenantId();
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $weekStart = $today->copy()->startOfWeek();
        $previousMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $today->copy()->subMonthNoOverflow()->endOfMonth();

        $monthlyRevenue = (float) PosInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$monthStart, $today])
            ->sum('total');

        $previousMonthlyRevenue = (float) PosInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('total');

        $receiptsThisMonth = (int) PosInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$monthStart, $today])
            ->count();

        $receiptsToday = (int) PosInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued')
            ->whereDate('issued_at', $today->toDateString())
            ->count();

        $productsCount = (int) Product::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $newProductsThisWeek = (int) Product::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $weekStart)
            ->count();

        $staffCount = (int) User::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $activeStaffCount = (int) User::query()
            ->where('tenant_id', $tenantId)
            ->where('status', true)
            ->count();

        return response()->json([
            'currencyCode' => $this->baseCurrencyCode(),
            'stats' => [
                [
                    'title' => 'Products',
                    'value' => $productsCount,
                    'permission' => 'products.view',
                    'subtitle' => '+' . $newProductsThisWeek . ' this week',
                    'icon' => 'Package',
                    'tone' => 'amber',
                ],
                [
                    'title' => 'Receipts',
                    'value' => $receiptsThisMonth,
                    'permission' => 'sales-invoices.view',
                    'subtitle' => $receiptsToday . ' today',
                    'icon' => 'ClipboardList',
                    'tone' => 'blue',
                ],
                [
                    'title' => 'Revenue',
                    'value' => $monthlyRevenue,
                    'permission' => 'sales-invoices.view',
                    'subtitle' => $this->percentageChange($monthlyRevenue, $previousMonthlyRevenue) . ' vs last month',
                    'icon' => 'WalletCards',
                    'tone' => 'green',
                    'money' => true,
                ],
                [
                    'title' => 'Staff Users',
                    'value' => $staffCount,
                    'permission' => 'users.view',
                    'subtitle' => $activeStaffCount . ' active',
                    'icon' => 'Users',
                    'tone' => 'purple',
                ],
            ],
            'summary' => [
                'branches' => Branch::query()->where('tenant_id', $tenantId)->count(),
                'customers' => Customer::query()->where('tenant_id', $tenantId)->count(),
                'sales_this_month' => $monthlyRevenue,
                'receipts_this_month' => $receiptsThisMonth,
                'active_staff' => $activeStaffCount,
                'low_stock' => Ingredient::query()
                    ->where('tenant_id', $tenantId)
                    ->where('alert_quantity', '>', 0)
                    ->whereColumn('current_stock', '<=', 'alert_quantity')
                    ->count(),
                'average_order_value' => $receiptsThisMonth > 0 ? round($monthlyRevenue / $receiptsThisMonth, 2) : 0,
            ],
            'charts' => [
                'revenue' => $this->revenueSeries($tenantId),
                'orders' => $this->ordersSeries($tenantId),
                'statuses' => $this->statusBreakdown($tenantId),
                'channels' => $this->channelBreakdown($tenantId),
            ],
            'recentOrders' => $this->recentOrders($tenantId),
            'topProducts' => $this->topProducts($tenantId),
            'lowStockItems' => $this->lowStockItems($tenantId),
        ]);
    }

    private function revenueSeries(int $tenantId): array
    {
        $from = now()->copy()->subDays(6)->startOfDay();
        $to = now()->copy()->endOfDay();

        $rows = PosInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('DATE(issued_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->dateLabels($from, $to, fn (Carbon $date) => (float) ($rows[$date->toDateString()] ?? 0));
    }

    private function ordersSeries(int $tenantId): array
    {
        $from = now()->copy()->subDays(6)->startOfDay();
        $to = now()->copy()->endOfDay();

        $rows = PosInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued')
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('DATE(issued_at) as day, COUNT(*) as total')
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

    private function statusBreakdown(int $tenantId): array
    {
        $labels = [
            'pending' => 'Pending',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'served' => 'Served',
            'cancelled' => 'Cancelled',
        ];

        $rows = PosKitchenTicket::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->copy()->subDays(30))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => array_values($labels),
            'values' => collect(array_keys($labels))->map(fn ($status) => (int) ($rows[$status] ?? 0))->values()->all(),
        ];
    }

    private function channelBreakdown(int $tenantId): array
    {
        $labels = [
            'takeaway' => 'Takeaway',
            'dine_in' => 'Dine-In',
            'pick_up' => 'Pick-Up',
            'drive_thru' => 'Drive-Thru',
            'pre_order' => 'Pre-Order',
            'catering' => 'Catering',
        ];

        $rows = PosKitchenTicket::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->copy()->subDays(30))
            ->select('channel', DB::raw('COUNT(*) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel');

        return [
            'labels' => array_values($labels),
            'values' => collect(array_keys($labels))->map(fn ($channel) => (int) ($rows[$channel] ?? 0))->values()->all(),
        ];
    }

    private function recentOrders(int $tenantId)
    {
        return PosInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued')
            ->latest('issued_at')
            ->limit(6)
            ->get(['id', 'invoice_no', 'buyer_name', 'status', 'total', 'issued_at'])
            ->map(fn ($invoice) => [
                'id' => $invoice->id,
                'customer' => $invoice->buyer_name ?: 'Walk-In Customer',
                'channel' => $invoice->invoice_no ?: 'Receipt',
                'status' => $this->prettyLabel($invoice->status ?: 'issued'),
                'amount' => (float) $invoice->total,
                'date' => $invoice->issued_at?->diffForHumans() ?: '-',
            ]);
    }

    private function topProducts(int $tenantId)
    {
        return PosInvoiceItem::query()
            ->join('pos_invoices', 'pos_invoices.id', '=', 'pos_invoice_items.pos_invoice_id')
            ->where('pos_invoices.tenant_id', $tenantId)
            ->where('pos_invoices.issued_at', '>=', now()->copy()->subDays(30))
            ->select('pos_invoice_items.product_name', DB::raw('SUM(pos_invoice_items.qty) as qty'), DB::raw('SUM(pos_invoice_items.line_total) as total'))
            ->groupBy('pos_invoice_items.product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'qty' => (float) $row->qty,
                'total' => (float) $row->total,
            ]);
    }

    private function lowStockItems(int $tenantId)
    {
        return Ingredient::query()
            ->with('unit:id,symbol')
            ->where('tenant_id', $tenantId)
            ->where('alert_quantity', '>', 0)
            ->whereColumn('current_stock', '<=', 'alert_quantity')
            ->orderByRaw('(alert_quantity - current_stock) DESC')
            ->limit(5)
            ->get(['id', 'name', 'unit_id', 'current_stock', 'alert_quantity'])
            ->map(fn ($ingredient) => [
                'name' => $ingredient->name,
                'current_stock' => (float) $ingredient->current_stock,
                'alert_quantity' => (float) $ingredient->alert_quantity,
                'unit' => $ingredient->unit?->symbol,
            ]);
    }

    private function percentageChange(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $change = (($current - $previous) / $previous) * 100;

        return ($change >= 0 ? '+' : '') . number_format($change, 1) . '%';
    }

    private function prettyLabel(?string $value): string
    {
        return str($value ?: '-')->replace('_', ' ')->title()->toString();
    }
}
