<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorPanelSubscriptionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (!$tenant) {
            return $next($request);
        }

        $status = $tenant->vendor_subscription_status ?? 'active';
        $panelEnabled = (bool) ($tenant->vendor_panel_enabled ?? true);
        $plan = $tenant->vendorSubscriptionPlan;

        if (
            !$plan ||
            $plan->status !== 'active' ||
            !$panelEnabled ||
            !in_array($status, ['active', 'trialing'], true)
        ) {
            Auth::guard('vendor')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('vendor.login')
                ->withErrors([
                    'email' => 'Vendor panel access is disabled for this subscription plan. Please contact support.',
                ]);
        }

        if ($blockedFeature = $this->blockedFeatureForRoute($request, $tenant)) {
            return redirect()
                ->route('vendor.dashboard')
                ->with('error', "Your current subscription does not include {$blockedFeature}.");
        }

        return $next($request);
    }

    private function blockedFeatureForRoute(Request $request, $tenant): ?string
    {
        $plan = $tenant->vendorSubscriptionPlan?->loadMissing('features');

        if (!$plan) {
            return null;
        }

        $routeName = (string) $request->route()?->getName();

        $featureRoutes = [
            'seating_plan' => [
                'vendor.floors.',
                'vendor.zones.',
                'vendor.tables.',
                'vendor.table-merges.',
                'vendor.pos.tables.',
            ],
            'kitchen' => [
                'vendor.pos.kitchen.',
                'vendor.pos.send-to-kitchen',
                'vendor.settings.kitchen-alert',
            ],
            'loyalty_points' => [
                'vendor.loyalty.',
                'vendor.pos.loyalty.',
            ],
            'gift_cards' => [
                'vendor.gift-cards.',
            ],
            'promotions' => [
                'vendor.promotions.',
                'vendor.pos.promotions',
            ],
            'reports' => [
                'vendor.reports.',
            ],
            'activity_log' => [
                'vendor.activities.',
            ],
        ];

        $featureLabels = [
            'seating_plan' => 'Seating Plan',
            'kitchen' => 'Kitchen',
            'loyalty_points' => 'Loyalty',
            'gift_cards' => 'Gift Cards',
            'promotions' => 'Promotions',
            'reports' => 'Reports',
            'activity_log' => 'Activity Logs',
        ];

        foreach ($featureRoutes as $featureKey => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($routeName, $prefix) && !$this->featureEnabled($plan, $featureKey)) {
                    return $featureLabels[$featureKey] ?? 'this feature';
                }
            }
        }

        return null;
    }

    private function featureEnabled($plan, string $featureKey): bool
    {
        return (bool) $plan->features
            ->firstWhere('feature_key', $featureKey)
            ?->enabled;
    }
}
