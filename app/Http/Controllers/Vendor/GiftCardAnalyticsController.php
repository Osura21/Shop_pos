<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\GiftCard;
use App\Models\GiftCardBatch;
use App\Models\GiftCardTransaction;
use Carbon\CarbonPeriod;
use Inertia\Inertia;

class GiftCardAnalyticsController extends Controller
{
    use ResolvesTenantContext;

    public function index()
    {
        $tenantId = $this->tenantId();
        $start = now()->subDays(120)->startOfDay();
        $end = now()->endOfDay();

        $cards = GiftCard::query()->where('tenant_id', $tenantId);
        $transactions = GiftCardTransaction::query()->where('tenant_id', $tenantId);
        $batches = GiftCardBatch::query()->where('tenant_id', $tenantId);

        $salesSeries = $this->dailySeries(['purchase', 'recharge'], $start, $end);
        $redeemSeries = $this->dailySeries(['redeem'], $start, $end);

        return Inertia::render('VendorAdmin/GiftCards/Analytics', [
            'stats' => [
                'total_cards' => (clone $cards)->count(),
                'outstanding_balance' => (float) (clone $cards)->sum('current_balance'),
                'secondary_outstanding_balance' => (float) (clone $cards)->sum('secondary_current_balance'),
                'sold_value' => (float) (clone $transactions)->whereIn('type', ['purchase', 'recharge'])->sum('amount'),
                'redeemed_value' => (float) (clone $transactions)->where('type', 'redeem')->sum('amount'),
                'total_transactions' => (clone $transactions)->count(),
                'total_batches' => (clone $batches)->count(),
                'batch_face_value' => (float) (clone $batches)->selectRaw('COALESCE(SUM(quantity * value), 0) as total')->value('total'),
                'secondary_batch_face_value' => (float) (clone $batches)->selectRaw('COALESCE(SUM(quantity * secondary_value), 0) as total')->value('total'),
                'expiring_soon' => (clone $cards)->where('status', 'active')->whereBetween('expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()])->count(),
            ],
            'charts' => [
                'cards_health' => [
                    'labels' => ['Active', 'Used', 'Disabled', 'Expired', 'Branch'],
                    'status' => [
                        (clone $cards)->where('status', 'active')->where('current_balance', '>', 0)->count(),
                        GiftCard::where('tenant_id', $tenantId)
                            ->where(fn ($q) => $q->where('status', 'used')->orWhere('current_balance', '<=', 0))
                            ->count(),
                        (clone $cards)->where('status', 'disabled')->count(),
                        (clone $cards)->where('expires_at', '<', now()->toDateString())->count(),
                        0,
                    ],
                    'scope' => [0, 0, 0, 0, (clone $cards)->whereNotNull('branch_id')->count()],
                ],
                'transaction_mix' => $this->transactionMix(),
                'sales_over_time' => $salesSeries,
                'redemption_over_time' => $redeemSeries,
                'usage_by_day' => $this->usageByDay(),
            ],
            'lists' => [
                'top_balances' => GiftCard::with('branch:id,name')
                    ->where('tenant_id', $tenantId)
                    ->orderByDesc('current_balance')
                    ->limit(6)
                    ->get(['id', 'code', 'branch_id', 'current_balance']),
                'recent_transactions' => GiftCardTransaction::with(['card:id,code', 'branch:id,name'])
                    ->where('tenant_id', $tenantId)
                    ->latest('occurred_at')
                    ->limit(8)
                    ->get(),
                'batch_activity' => GiftCardBatch::with('branch:id,name')
                    ->where('tenant_id', $tenantId)
                    ->latest()
                    ->limit(6)
                    ->get(),
                'expiring_cards' => GiftCard::with(['branch:id,name', 'customer:id,name'])
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addDays(30)->toDateString())
                    ->orderBy('expires_at')
                    ->limit(8)
                    ->get(),
            ],
            'summary' => [
                'sales_total' => array_sum($salesSeries['values']),
                'sales_points' => count($salesSeries['values']),
                'redemption_total' => array_sum($redeemSeries['values']),
                'redemption_points' => count($redeemSeries['values']),
            ],
        ]);
    }

    private function dailySeries(array $types, $start, $end): array
    {
        $rows = GiftCardTransaction::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('type', $types)
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('DATE(occurred_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $values[] = (float) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function transactionMix(): array
    {
        $labels = ['Purchase', 'Redeem', 'Refund', 'Recharge', 'Adjustment'];
        $types = ['purchase', 'redeem', 'refund', 'recharge', 'adjustment'];
        $counts = [];
        $amounts = [];

        foreach ($types as $type) {
            $base = GiftCardTransaction::where('tenant_id', $this->tenantId())->where('type', $type);
            $counts[] = (clone $base)->count();
            $amounts[] = (float) (clone $base)->sum('amount');
        }

        return ['labels' => $labels, 'counts' => $counts, 'amounts' => $amounts];
    }

    private function usageByDay(): array
    {
        $rows = GiftCardTransaction::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'redeem')
            ->selectRaw('DAYOFWEEK(occurred_at) as day_no, COUNT(*) as total')
            ->groupBy('day_no')
            ->pluck('total', 'day_no');

        $labels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $values = [];

        foreach (range(1, 7) as $day) {
            $values[] = (int) ($rows[$day] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

}
