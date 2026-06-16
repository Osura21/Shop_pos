<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ReportController extends Controller
{
    use ResolvesTenantContext;

   public function index(Request $request)
{
    $sections = $this->visibleSections($request->user('vendor'));

    return Inertia::render('VendorAdmin/Reports/Index', [
        'sections' => $sections,
    ]);
}
 public function show(Request $request, string $report)
{
    $definition = $this->definition($report);
    abort_if(! $definition, 404);

    abort_unless($request->user('vendor')?->can('reports.view'), 403);
    abort_unless($request->user('vendor')?->can($definition['section_permission'] ?? ''), 403);
    abort_unless($request->user('vendor')?->can($definition['permission'] ?? ''), 403);

    $filters = $this->filters($request);
    $rows = $this->rows($report, $filters);

    return Inertia::render('VendorAdmin/Reports/Show', [
        'report' => $definition,
        'rows' => $rows,
        'filters' => $filters,
        'filterOptions' => $this->filterOptions(),
    ]);
}
private function visibleSections($user): array
{
    if (! $user || ! $user->can('reports.view')) {
        return [];
    }

    return collect($this->sections())
        ->filter(fn ($section) => $user->can($section['permission'] ?? ''))
        ->map(function ($section) use ($user) {
            $section['items'] = collect($section['items'] ?? [])
                ->filter(fn ($item) => $user->can($item['permission'] ?? ''))
                ->values()
                ->all();

            return $section;
        })
        ->filter(fn ($section) => count($section['items'] ?? []) > 0)
        ->values()
        ->all();
}

private function definition(string $report): ?array
{
    foreach ($this->sections() as $section) {
        foreach ($section['items'] as $item) {
            if ($item['slug'] === $report) {
                return array_merge($item, [
                    'section_title' => $section['title'],
                    'section_key' => $section['key'],
                    'section_permission' => $section['permission'],
                ]);
            }
        }
    }

    return null;
}
   private function sections(): array
{
    return [
        [
            'key' => 'restaurant-sales',
            'title' => 'Restaurant Sales Reports',
            'permission' => 'reports.restaurant-sales.view',
            'items' => [
                $this->card('sales', 'Sales Report', 'A detailed breakdown of sales performance within a selected time range.', 'ReceiptText'),
                $this->card('upcoming-orders', 'Upcoming Orders Report', 'Displays scheduled orders that are set to be prepared or served at a future time.', 'CalendarClock'),
                $this->card('products-purchase', 'Products Purchase Report', 'Analyze product procurement performance across different periods.', 'Package'),
                $this->card('sales-by-creator', 'Sales By Creator Report', 'Shows total sales grouped by the user who created each order.', 'UserRoundCog'),
                $this->card('sales-by-cashier', 'Sales By Cashier Report', 'Summarizes total sales handled by each cashier, excluding canceled and refunded orders.', 'MonitorCog'),
                $this->card('tax', 'Tax Report', 'Summarizes collected taxes by order status, type, and payment over a selected period.', 'BadgeDollarSign'),
                $this->card('product-tax', 'Product Tax Report', 'Displays the total tax amounts applied to specific products within a selected period.', 'ScrollText'),
                $this->card('branch-performance', 'Branch Performance Report', 'Shows key sales and order metrics for each branch to compare performance over time.', 'Store'),
                $this->card('payments', 'Payments Report', 'Breaks down received payments by method to track revenue collection.', 'WalletCards'),
                $this->card('cost-revenue-order', 'Cost & Revenue Report by Order', 'Show each order total cost, total revenue, and profit.', 'ReceiptText'),
                $this->card('cost-revenue-product', 'Cost & Revenue Report by Product', 'Overview of product performance in terms of quantity sold, cost, and revenue.', 'Package'),
                $this->card('discounts-vouchers', 'Discounts & Vouchers Report', 'Shows redeemed discounts and vouchers with usage counts and total discounted amounts.', 'BadgePercent'),
            ],
        ],

        [
            'key' => 'pos',
            'title' => 'POS Reports',
            'permission' => 'reports.pos.view',
            'items' => [
                $this->card('register-summary', 'Register Summary Report', 'Financial summary of POS registers including sessions, sales, cash movements, and balances.', 'MonitorCog'),
                $this->card('cash-movement', 'Cash Movement Report', 'Tracks cash-in and cash-out transactions across POS registers, including users and reasons.', 'HandCoins'),
            ],
        ],

        [
            'key' => 'gift-card',
            'title' => 'Gift Card Reports',
            'permission' => 'reports.gift-card.view',
            'items' => [
                $this->card('gift-card-sales', 'Gift Card Sales Report', 'Shows all gift cards that were sold within a selected period.', 'Gift'),
                $this->card('gift-card-redemption', 'Gift Card Redemption Report', 'Shows gift cards used for payment in orders.', 'WalletCards'),
                $this->card('gift-card-outstanding-balance', 'Gift Card Outstanding Balance Report', 'Displays all active gift cards and their remaining balances.', 'Wallet'),
                $this->card('gift-card-liability', 'Gift Card Liability Report', 'Financial report showing the remaining liability of gift cards.', 'ClipboardDollar'),
                $this->card('gift-card-expiry', 'Gift Card Expiry Report', 'Lists gift cards that are close to expiration or already expired.', 'ClockAlert'),
                $this->card('gift-card-transactions', 'Gift Card Transactions Report', 'Displays all transactions related to gift cards.', 'ArrowLeftRight'),
                $this->card('gift-card-branch-performance', 'Gift Card Branch Performance Report', 'Compares gift card activity across branches.', 'Store'),
                $this->card('gift-card-batch', 'Gift Card Batch Report', 'Shows statistics for gift card batches generated by the system.', 'Layers'),
            ],
        ],

        [
            'key' => 'inventory',
            'title' => 'Inventory Reports',
            'permission' => 'reports.inventory.view',
            'items' => [
                $this->card('ingredient-usage', 'Ingredient Usage Report', 'Tracks total quantity of each ingredient used based on sold products during a selected period.', 'Utensils'),
                $this->card('low-stock-alerts', 'Low Stock Alerts', 'Identifies ingredients that are nearing depletion or already finished.', 'TriangleAlert'),
            ],
        ],

        [
            'key' => 'loyalty-overview',
            'title' => 'Loyalty Overview Reports',
            'permission' => 'reports.loyalty-overview.view',
            'items' => [
                $this->card('loyalty-program-summary', 'Loyalty Program Summary', 'Overview of each loyalty program with customer count and points lifecycle totals.', 'ClipboardList'),
                $this->card('total-earned-points', 'Total Earned Points', 'Total loyalty points earned within the selected period.', 'CircleDollarSign'),
                $this->card('total-redeemed-points', 'Total Redeemed Points', 'Total loyalty points redeemed within the selected period.', 'BadgeDollarSign'),
                $this->card('total-expired-points', 'Total Expired Points', 'Total loyalty points that expired without redemption.', 'ClockAlert'),
                $this->card('system-points-balance', 'System Points Balance', 'Current points balance across active loyalty customers.', 'Wallet'),
                $this->card('redemption-rate', 'Redemption Rate', 'Ratio of redeemed points to earned points per program.', 'PieChart'),
                $this->card('average-points-program', 'Average Points per Program', 'Average points balance for customers in each program.', 'Calculator'),
                $this->card('points-lifecycle-timeline', 'Points Lifecycle Timeline', 'Daily earned vs redeemed vs expired points across loyalty programs.', 'ChartNoAxesCombined'),
            ],
        ],

        [
            'key' => 'loyalty-customers',
            'title' => 'Loyalty Customer Reports',
            'permission' => 'reports.loyalty-customers.view',
            'items' => [
                $this->card('last-loyalty-activity', 'Last Loyalty Activity', 'Latest loyalty activity per customer with last earn/redeem timestamps.', 'Activity'),
                $this->card('inactive-loyalty-customers', 'Inactive Loyalty Customers', 'Customers with no recent loyalty activity and inactivity duration.', 'UserRoundX'),
                $this->card('customers-no-redemptions', 'Customers with No Redemptions', 'Customers who have never redeemed loyalty points.', 'Hand'),
                $this->card('top-customers-points', 'Top Customers by Points', 'Customers ranked by lifetime points and current balance.', 'Trophy'),
            ],
        ],

        [
            'key' => 'loyalty-tiers',
            'title' => 'Loyalty Tier Reports',
            'permission' => 'reports.loyalty-tiers.view',
            'items' => [
                $this->card('customers-tier-distribution', 'Customers by Tier Distribution', 'How loyalty customers are distributed across tiers.', 'UsersRound'),
                $this->card('redemption-rate-tier', 'Redemption Rate per Tier', 'Earned vs redeemed points segmented by tier.', 'PieChart'),
            ],
        ],

        [
            'key' => 'loyalty-rewards',
            'title' => 'Loyalty Rewards Reports',
            'permission' => 'reports.loyalty-rewards.view',
            'items' => [
                $this->card('most-redeemed-rewards', 'Most Redeemed Rewards', 'Rewards with the highest redemption count.', 'Trophy'),
                $this->card('least-used-rewards', 'Least Used Rewards', 'Rewards with minimal redemption counts.', 'TrendingDown'),
                $this->card('never-redeemed-rewards', 'Never Redeemed Rewards', 'Rewards that were never redeemed.', 'CircleOff'),
                $this->card('rewards-by-type', 'Rewards by Type', 'Rewards grouped by type with redemption totals.', 'BoxSelect'),
                $this->card('rewards-by-tier', 'Rewards by Tier', 'Rewards available per loyalty tier.', 'BadgePlus'),
                $this->card('rewards-by-program', 'Rewards by Program', 'Rewards grouped by loyalty program.', 'LayoutGrid'),
            ],
        ],

        [
            'key' => 'loyalty-gifts',
            'title' => 'Loyalty Gifts Reports',
            'permission' => 'reports.loyalty-gifts.view',
            'items' => [
                $this->card('available-gifts', 'Available Gifts', 'Gifts that are available for use.', 'Gift'),
                $this->card('used-gifts', 'Used Gifts', 'Gifts that have been redeemed.', 'TicketCheck'),
                $this->card('expired-gifts', 'Expired Gifts', 'Gifts that expired unused.', 'ClockAlert'),
                $this->card('gift-usage-rate', 'Gift Usage Rate', 'Usage rate of issued gifts.', 'PieChart'),
                $this->card('unused-gifts-customer', 'Unused Gifts per Customer', 'Unused gifts grouped by customer.', 'UserRoundHelp'),
            ],
        ],

        [
            'key' => 'loyalty-redemptions',
            'title' => 'Loyalty Redemptions Reports',
            'permission' => 'reports.loyalty-redemptions.view',
            'items' => [
                $this->card('redemptions-by-status', 'Redemptions by Status', 'Redemptions grouped by status.', 'ListChecks'),
                $this->card('redemptions-by-program', 'Redemptions by Program', 'Redemptions grouped by loyalty program.', 'LayoutGrid'),
                $this->card('average-points-redemption', 'Average Points per Redemption', 'Average points spent per redemption.', 'Calculator'),
            ],
        ],

        [
            'key' => 'loyalty-promotions',
            'title' => 'Loyalty Promotions Reports',
            'permission' => 'reports.loyalty-promotions.view',
            'items' => [
                $this->card('active-promotions', 'Active Promotions', 'Currently active promotions.', 'Zap'),
                $this->card('expired-promotions', 'Expired Promotions', 'Promotions that ended.', 'ClockAlert'),
                $this->card('promotion-usage', 'Promotion Usage', 'Usage count per promotion.', 'ChartColumn'),
                $this->card('highest-impact-promotions', 'Highest Impact Promotions', 'Promotions with highest points generated.', 'Trophy'),
                $this->card('bonus-vs-multiplier-comparison', 'Bonus vs Multiplier Comparison', 'Compares bonus and multiplier promotions.', 'Route'),
                $this->card('category-boost-promotions', 'Category Boost Promotions', 'Promotions boosting specific categories.', 'LayoutGrid'),
                $this->card('new-member-promotions', 'New Member Promotions', 'Promotions for new loyalty members.', 'UserPlus'),
            ],
        ],

        [
            'key' => 'loyalty-financial-roi',
            'title' => 'Loyalty Financial & ROI Reports',
            'permission' => 'reports.loyalty-financial-roi.view',
            'items' => [
                $this->card('loyalty-average-order-value', 'Average Order Value (Loyalty Customers)', 'Average order value for loyalty customers.', 'ReceiptText'),
                $this->card('loyalty-customer-revenue', 'Revenue from Loyalty Customers', 'Revenue generated by loyalty customers.', 'WalletCards'),
                $this->card('revenue-before-after-loyalty', 'Revenue Before vs After Loyalty', 'Revenue comparison for loyalty vs non-loyalty orders.', 'ArrowLeftRight'),
                $this->card('free-items-cost', 'Free Items Cost', 'Cost of free items issued.', 'Package'),
            ],
        ],

        [
            'key' => 'system',
            'title' => 'System Reports',
            'permission' => 'reports.system.view',
            'items' => [
                $this->card('categorized-products', 'Categorized Products Report', 'Shows each category with its total product count for reviewing product distribution.', 'Folders'),
            ],
        ],
    ];
}
  private function card(string $slug, string $title, string $description, string $icon): array
{
    return [
        'slug' => $slug,
        'title' => $title,
        'description' => $description,
        'icon' => $icon,
        'permission' => "reports.{$slug}.view",
    ];
}
   

    private function filters(Request $request): array
    {
        return [
            'date_from' => $request->input('date_from', now()->subDays(30)->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'branch_id' => $request->input('branch_id'),
            'currency_mode' => $request->input('currency_mode', 'all'),
            'search' => $request->input('search', ''),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'branches' => $this->tableExists('branches')
                ? DB::table('branches')->where('tenant_id', $this->tenantId())->select('id', 'name')->orderBy('name')->get()
                : [],
            'baseCurrency' => $this->baseCurrencyCode(),
            'secondaryCurrency' => $this->secondaryCurrencyCode(),
            'baseCurrencySymbol' => $this->baseCurrencySymbol(),
            'secondaryCurrencySymbol' => $this->secondaryCurrencySymbol(),
        ];
    }

    private function rows(string $report, array $filters): array
    {
        return match ($report) {
            'sales', 'sales-by-cashier', 'sales-by-creator' => $this->salesRows($report, $filters),
            'upcoming-orders' => $this->upcomingOrders($filters),
            'products-purchase' => $this->productPurchaseRows($filters),
            'tax', 'product-tax' => $this->taxRows($report, $filters),
            'branch-performance' => $this->branchPerformanceRows($filters),
            'payments' => $this->paymentRows($filters),
            'cost-revenue-order', 'cost-revenue-product' => $this->costRevenueRows($report, $filters),
            'discounts-vouchers' => $this->discountRows($filters),
            'register-summary' => $this->registerSummaryRows($filters),
            'cash-movement' => $this->cashMovementRows($filters),
            'ingredient-usage' => $this->ingredientUsageRows($filters),
            'low-stock-alerts' => $this->lowStockRows(),
            'categorized-products' => $this->categorizedProductRows(),
            default => $this->catalogRows($report, $filters),
        };
    }

    private function salesRows(string $report, array $filters): array
    {
        if (! $this->tableExists('pos_transactions')) {
            return [];
        }

        $query = DB::table('pos_transactions as t')
            ->where('t.tenant_id', $this->tenantId())
            ->where('t.status', '!=', 'cancelled');

        $this->dateRange($query, 't.paid_at', $filters);
        $this->branchFilter($query, 't.branch_id', $filters);
        $this->currencyFilter($query, 't.currency_mode', $filters);

        if ($report === 'sales-by-cashier' || $report === 'sales-by-creator') {
            $query->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->selectRaw('MIN(DATE(t.paid_at)) as start_date, MAX(DATE(t.paid_at)) as end_date, COALESCE(u.name, "Unknown") as person, COUNT(*) as total_orders, SUM(t.total_products) as total_products')
                ->selectRaw($this->moneySelect('t.subtotal', 'subtotal'))
                ->selectRaw($this->moneySelect('t.tax_total', 'tax'))
                ->selectRaw($this->moneySelect('t.grand_total', 'total'))
                ->groupBy('person')
                ->orderByDesc('total_base');
        } else {
            $query->selectRaw('DATE(t.paid_at) as period, COUNT(*) as total_orders, SUM(t.total_products) as total_products')
                ->selectRaw($this->moneySelect('t.subtotal', 'subtotal'))
                ->selectRaw($this->moneySelect('t.tax_total', 'tax'))
                ->selectRaw($this->moneySelect('t.grand_total', 'total'))
                ->groupByRaw('DATE(t.paid_at)')
                ->orderByDesc('period');
        }

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function upcomingOrders(array $filters): array
    {
        if (! $this->tableExists('pos_kitchen_tickets')) {
            return [];
        }

        $query = DB::table('pos_kitchen_tickets as t')
            ->where('t.tenant_id', $this->tenantId())
            ->whereNotNull('t.scheduled_at')
            ->where('t.scheduled_at', '>=', now())
            ->selectRaw('DATE(t.scheduled_at) as scheduled_date, TIME(t.scheduled_at) as scheduled_time, COALESCE(t.customer_name, "Walk-in") as customer, t.status, COUNT(*) as total_orders')
            ->selectRaw($this->moneySelect('t.grand_total', 'total'))
            ->groupByRaw('DATE(t.scheduled_at), TIME(t.scheduled_at), customer, t.status')
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');

        $this->branchFilter($query, 't.branch_id', $filters);
        $this->currencyFilter($query, 't.currency_mode', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function productPurchaseRows(array $filters): array
    {
        if (! $this->tableExists('purchase_items') || ! $this->tableExists('purchases')) {
            return [];
        }

        $query = DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->leftJoin('ingredients as g', 'g.id', '=', 'i.ingredient_id')
            ->where('p.tenant_id', $this->tenantId())
            ->selectRaw('MIN(DATE(p.created_at)) as start_date, MAX(DATE(p.created_at)) as end_date, COALESCE(g.name, "Item") as product, SUM(i.quantity) as quantity, SUM(i.line_total) as total_base, SUM(COALESCE(i.secondary_line_total, 0)) as total_secondary')
            ->groupBy('product')
            ->orderByDesc('total_base');

        $this->dateRange($query, 'p.created_at', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function taxRows(string $report, array $filters): array
    {
        if (! $this->tableExists('pos_kitchen_ticket_items')) {
            return [];
        }

        $query = DB::table('pos_kitchen_ticket_items as i')
            ->join('pos_kitchen_tickets as t', 't.id', '=', 'i.pos_kitchen_ticket_id')
            ->where('t.tenant_id', $this->tenantId());

        $this->dateRange($query, 't.created_at', $filters);
        $this->branchFilter($query, 't.branch_id', $filters);
        $this->currencyFilter($query, 't.currency_mode', $filters);

        if ($report === 'product-tax') {
            $query->selectRaw('COALESCE(i.product_name, "Product") as product, COUNT(DISTINCT t.id) as total_orders')
                ->selectRaw($this->moneySelect('i.tax_total', 'tax', 't.currency_mode'))
                ->groupBy('product')
                ->orderByDesc('tax_base');
        } else {
            $query->selectRaw('COALESCE(JSON_UNQUOTE(JSON_EXTRACT(i.tax_snapshot, "$[0].name")), "Tax") as tax_name, COUNT(DISTINCT t.id) as total_orders')
                ->selectRaw($this->moneySelect('i.tax_total', 'total', 't.currency_mode'))
                ->groupBy('tax_name')
                ->orderByDesc('total_base');
        }

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function branchPerformanceRows(array $filters): array
    {
        if (! $this->tableExists('pos_transactions')) {
            return [];
        }

        $query = DB::table('pos_transactions as t')
            ->leftJoin('branches as b', 'b.id', '=', 't.branch_id')
            ->where('t.tenant_id', $this->tenantId())
            ->selectRaw('COALESCE(b.name, "No Branch") as branch, COUNT(*) as total_orders')
            ->selectRaw($this->moneySelect('t.grand_total', 'total'))
            ->groupBy('branch')
            ->orderByDesc('total_base');

        $this->dateRange($query, 't.paid_at', $filters);
        $this->branchFilter($query, 't.branch_id', $filters);
        $this->currencyFilter($query, 't.currency_mode', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function paymentRows(array $filters): array
    {
        $table = $this->tableExists('pos_transaction_payments') ? 'pos_transaction_payments' : null;
        if (! $table || ! $this->tableExists('pos_transactions')) {
            return [];
        }

        $query = DB::table("$table as p")
            ->join('pos_transactions as t', 't.id', '=', 'p.pos_transaction_id')
            ->where('t.tenant_id', $this->tenantId())
            ->selectRaw('COALESCE(p.payment_method, "Payment") as payment_method, COUNT(*) as total_paid')
            ->selectRaw($this->moneySelect('p.amount', 'total', 't.currency_mode'))
            ->groupBy('payment_method')
            ->orderByDesc('total_base');

        $this->dateRange($query, 't.paid_at', $filters);
        $this->branchFilter($query, 't.branch_id', $filters);
        $this->currencyFilter($query, 't.currency_mode', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function costRevenueRows(string $report, array $filters): array
    {
        if (! $this->tableExists('pos_invoice_items') || ! $this->tableExists('pos_invoices')) {
            return [];
        }

        $query = DB::table('pos_invoice_items as i')
            ->join('pos_invoices as v', 'v.id', '=', 'i.pos_invoice_id')
            ->where('v.tenant_id', $this->tenantId());

        $this->dateRange($query, 'v.issued_at', $filters);
        $this->branchFilter($query, 'v.branch_id', $filters);

        if ($report === 'cost-revenue-product') {
            $query->selectRaw('COALESCE(i.product_name, "Product") as product, SUM(i.qty) as quantity, SUM(i.cost_price) as cost_base, SUM(i.revenue) as revenue_base, SUM(i.revenue - i.cost_price) as profit_base')
                ->groupBy('product')
                ->orderByDesc('revenue_base');
        } else {
            $query->selectRaw('DATE(v.issued_at) as period, COUNT(DISTINCT v.id) as total_orders, SUM(i.qty) as total_products, SUM(i.cost_price) as cost_base, SUM(i.revenue) as revenue_base, SUM(i.revenue - i.cost_price) as profit_base')
                ->groupByRaw('DATE(v.issued_at)')
                ->orderByDesc('period');
        }

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function discountRows(array $filters): array
    {
        if (! $this->tableExists('promotion_redemptions')) {
            return [];
        }

        $query = DB::table('promotion_redemptions as r')
            ->where('r.tenant_id', $this->tenantId())
            ->selectRaw('COALESCE(r.promotion_code, "Discount / Voucher") as promotion, COALESCE(r.promotion_type, "Promotion") as type, COUNT(*) as total_orders')
            ->selectRaw($this->moneySelect('r.discount_amount', 'discount'))
            ->groupBy('promotion', 'type')
            ->orderByDesc('discount_base');

        $this->dateRange($query, 'r.created_at', $filters);
        $this->currencyFilter($query, 'r.currency_mode', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function registerSummaryRows(array $filters): array
    {
        if (! $this->tableExists('pos_registers')) {
            return [];
        }

        $query = DB::table('pos_registers as r')
            ->leftJoin('pos_transactions as t', 't.pos_register_id', '=', 'r.id')
            ->where('r.tenant_id', $this->tenantId())
            ->selectRaw('COALESCE(r.name, "Register") as register_name, COUNT(DISTINCT t.pos_session_id) as sessions_count, COUNT(t.id) as orders_count')
            ->selectRaw('SUM(CASE WHEN t.currency_mode = "base" THEN t.grand_total ELSE 0 END) as total_sales_base')
            ->selectRaw('SUM(CASE WHEN t.currency_mode = "secondary" THEN t.grand_total ELSE 0 END) as total_sales_secondary')
            ->groupBy('register_name')
            ->orderByDesc('total_sales_base');

        $this->dateRange($query, 't.paid_at', $filters);
        $this->currencyFilter($query, 't.currency_mode', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function cashMovementRows(array $filters): array
    {
        if (! $this->tableExists('pos_cash_movements')) {
            return [];
        }

        $query = DB::table('pos_cash_movements as m')
            ->leftJoin('pos_registers as r', 'r.id', '=', 'm.pos_register_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.tenant_id', $this->tenantId())
            ->selectRaw('DATE(m.created_at) as period, COALESCE(r.name, "Register") as register_name, COALESCE(u.name, "User") as user_name, m.direction, m.reason')
            ->selectRaw($this->moneySelect('m.amount', 'amount'))
            ->groupByRaw('DATE(m.created_at), register_name, user_name, m.direction, m.reason')
            ->orderByDesc('period');

        $this->dateRange($query, 'm.created_at', $filters);
        $this->currencyFilter($query, 'm.currency_mode', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function ingredientUsageRows(array $filters): array
    {
        if (! $this->tableExists('stock_movements')) {
            return [];
        }

        $query = DB::table('stock_movements as s')
            ->leftJoin('ingredients as i', 'i.id', '=', 's.ingredient_id')
            ->where('s.tenant_id', $this->tenantId())
            ->whereIn('s.type', ['out', 'used', 'sale', 'consume', 'consumption'])
            ->selectRaw('MIN(DATE(s.created_at)) as start_date, MAX(DATE(s.created_at)) as end_date, COALESCE(i.name, "Ingredient") as ingredient, ROUND(SUM(s.quantity), 3) as total_used')
            ->groupBy('ingredient')
            ->orderByDesc(DB::raw('SUM(s.quantity)'));

        $this->dateRange($query, 's.created_at', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function lowStockRows(): array
    {
        if (! $this->tableExists('ingredients')) {
            return [];
        }

        return DB::table('ingredients')
            ->where('tenant_id', $this->tenantId())
            ->whereColumn('current_stock', '<=', 'alert_quantity')
            ->selectRaw('name as ingredient, ROUND(current_stock, 3) as current_stock, ROUND(alert_quantity, 3) as alert_quantity')
            ->orderBy('current_stock')
            ->limit(1000)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row))
            ->all();
    }

    private function categorizedProductRows(): array
    {
        if (! $this->tableExists('categories') || ! $this->tableExists('products') || ! $this->tableExists('category_product')) {
            return [];
        }

        return DB::table('categories as c')
            ->leftJoin('category_product as cp', 'cp.category_id', '=', 'c.id')
            ->leftJoin('products as p', 'p.id', '=', 'cp.product_id')
            ->where('c.tenant_id', $this->tenantId())
            ->selectRaw('c.name as category, COUNT(p.id) as products_count')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('products_count')
            ->limit(1000)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row))
            ->all();
    }

    private function catalogRows(string $report, array $filters): array
    {
        $tableMap = [
            'gift-card-sales' => ['gift_cards', 'code', 'initial_balance'],
            'gift-card-redemption' => ['gift_card_transactions', 'type', 'amount'],
            'gift-card-outstanding-balance' => ['gift_cards', 'code', 'current_balance'],
            'gift-card-liability' => ['gift_cards', 'status', 'current_balance'],
            'gift-card-expiry' => ['gift_cards', 'code', 'current_balance'],
            'gift-card-transactions' => ['gift_card_transactions', 'type', 'amount'],
            'gift-card-batch' => ['gift_card_batches', 'name', 'value'],
            'gift-card-branch-performance' => ['gift_card_transactions', 'branch_id', 'amount'],
            'loyalty-program-summary' => ['loyalty_programs', 'name', null],
            'active-promotions' => ['loyalty_promotions', 'name', null],
            'expired-promotions' => ['loyalty_promotions', 'name', null],
            'available-gifts' => ['loyalty_gifts', 'name', null],
            'used-gifts' => ['loyalty_gifts', 'name', null],
            'expired-gifts' => ['loyalty_gifts', 'name', null],
            'most-redeemed-rewards' => ['loyalty_rewards', 'name', 'points_cost'],
            'least-used-rewards' => ['loyalty_rewards', 'name', 'points_cost'],
            'never-redeemed-rewards' => ['loyalty_rewards', 'name', 'points_cost'],
            'rewards-by-type' => ['loyalty_rewards', 'type', 'points_cost'],
            'rewards-by-tier' => ['loyalty_rewards', 'tier_id', 'points_cost'],
            'rewards-by-program' => ['loyalty_rewards', 'program_id', 'points_cost'],
            'top-customers-points' => ['loyalty_customers', 'customer_name', 'points_balance'],
            'system-points-balance' => ['loyalty_customers', 'status', 'points_balance'],
        ];

        [$table, $labelColumn, $amountColumn] = $tableMap[$report] ?? [null, null, null];
        if (! $table && (str_contains($report, 'points') || str_contains($report, 'redemption') || str_contains($report, 'loyalty'))) {
            return $this->loyaltySummaryRows($report, $filters);
        }

        if (! $table || ! $this->tableExists($table)) {
            return [];
        }

        $label = $this->hasColumn($table, $labelColumn) ? $labelColumn : 'id';
        $query = DB::table($table)
            ->where('tenant_id', $this->tenantId())
            ->selectRaw("$label as item, COUNT(*) as records_count");

        if ($amountColumn && $this->hasColumn($table, $amountColumn)) {
            $secondaryColumn = $this->secondaryAmountColumn($table, $amountColumn);

            if ($this->hasColumn($table, 'currency_mode')) {
                $query->selectRaw("SUM(CASE WHEN COALESCE(currency_mode, 'base') = 'secondary' THEN 0 ELSE $amountColumn END) as amount_base")
                    ->selectRaw("SUM(CASE WHEN currency_mode = 'secondary' THEN $amountColumn ELSE 0 END) as amount_secondary");
            } elseif ($secondaryColumn) {
                $query->selectRaw("SUM($amountColumn) as amount_base")
                    ->selectRaw("SUM($secondaryColumn) as amount_secondary");
            } else {
                $query->selectRaw("SUM($amountColumn) as amount_base");
            }
        }

        if ($this->hasColumn($table, 'created_at')) {
            $this->dateRange($query, "$table.created_at", $filters);
        }

        return $query->groupBy($label)->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function loyaltySummaryRows(string $report, array $filters): array
    {
        if (! $this->tableExists('loyalty_transactions')) {
            return [];
        }

        $query = DB::table('loyalty_transactions as t')
            ->where('t.tenant_id', $this->tenantId())
            ->selectRaw('COALESCE(t.type, "' . str_replace('-', ' ', $report) . '") as activity, COUNT(*) as records_count, SUM(COALESCE(t.points, 0)) as points')
            ->groupBy('activity')
            ->orderByDesc('points');

        $this->dateRange($query, 't.created_at', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => $this->formatRow((array) $row))->all();
    }

    private function dateRange($query, string $column, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', Carbon::parse($filters['date_from'])->toDateString());
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', Carbon::parse($filters['date_to'])->toDateString());
        }
    }

    private function branchFilter($query, string $column, array $filters): void
    {
        if (! empty($filters['branch_id'])) {
            $query->where($column, $filters['branch_id']);
        }
    }

    private function currencyFilter($query, string $column, array $filters): void
    {
        if (($filters['currency_mode'] ?? 'all') !== 'all') {
            $query->where($column, $filters['currency_mode']);
        }
    }

    private function moneySelect(string $column, string $alias, string $modeColumn = 'currency_mode'): string
    {
        return "SUM(CASE WHEN COALESCE($modeColumn, 'base') = 'secondary' THEN 0 ELSE $column END) as {$alias}_base, SUM(CASE WHEN $modeColumn = 'secondary' THEN $column ELSE 0 END) as {$alias}_secondary";
    }

    private function formatRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $row[$key] = $value->format('Y-m-d');
            }
        }

        return $row;
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function hasColumn(string $table, ?string $column): bool
    {
        return $column && Schema::hasColumn($table, $column);
    }

    private function secondaryAmountColumn(string $table, string $baseColumn): ?string
    {
        $candidates = [
            "secondary_$baseColumn",
            str_replace('initial_balance', 'secondary_initial_balance', $baseColumn),
            str_replace('current_balance', 'secondary_current_balance', $baseColumn),
        ];

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== $baseColumn && $this->hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
