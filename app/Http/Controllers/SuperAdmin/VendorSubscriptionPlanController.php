<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VendorSubscriptionPlanController extends Controller
{
    public function index()
    {
        $planModels = VendorSubscriptionPlan::query()
            ->with(['features', 'vendors:id,vendor_subscription_plan_id'])
            ->withCount('vendors')
            ->orderBy('display_order')
            ->orderBy('monthly_price')
            ->get();

        $plans = $planModels->map(fn (VendorSubscriptionPlan $plan) => $this->serializePlan($plan));

        return Inertia::render('SuperAdmin/VendorSubscriptions/Index', [
            'plans' => $plans,
            'featureCatalog' => $this->featureCatalog(),
            'stats' => [
                'total' => $planModels->count(),
                'active' => $planModels->where('status', 'active')->count(),
                'default' => $planModels->firstWhere('is_default', true)?->plan_name,
                'assigned_vendors' => $planModels->sum('vendors_count'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/VendorSubscriptions/CreateUpdate', [
            'plan' => null,
            'featureCatalog' => $this->featureCatalog(),
            'statusOptions' => $this->statusOptions(),
            'iconOptions' => $this->iconOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($data) {
            if (!empty($data['is_default'])) {
                VendorSubscriptionPlan::query()->update(['is_default' => false]);
            }

            $plan = VendorSubscriptionPlan::create($this->planPayload($data));
            $this->syncFeatures($plan, $data['features'] ?? []);
        });

        return redirect()
            ->route('vendor-subscriptions.index')
            ->with('success', 'Vendor subscription plan created successfully.');
    }

    public function edit(VendorSubscriptionPlan $vendorSubscription)
    {
        $vendorSubscription->load('features');

        return Inertia::render('SuperAdmin/VendorSubscriptions/CreateUpdate', [
            'plan' => $this->serializePlan($vendorSubscription),
            'featureCatalog' => $this->featureCatalog(),
            'statusOptions' => $this->statusOptions(),
            'iconOptions' => $this->iconOptions(),
        ]);
    }

    public function update(Request $request, VendorSubscriptionPlan $vendorSubscription)
    {
        $data = $request->validate($this->rules($vendorSubscription->id), $this->messages());

        DB::transaction(function () use ($data, $vendorSubscription) {
            if (!empty($data['is_default'])) {
                VendorSubscriptionPlan::query()
                    ->where('id', '!=', $vendorSubscription->id)
                    ->update(['is_default' => false]);
            }

            $vendorSubscription->update($this->planPayload($data));
            $this->syncFeatures($vendorSubscription, $data['features'] ?? []);
        });

        return redirect()
            ->route('vendor-subscriptions.index')
            ->with('success', 'Vendor subscription plan updated successfully.');
    }

    public function destroy(VendorSubscriptionPlan $vendorSubscription)
    {
        if ($vendorSubscription->vendors()->exists()) {
            return back()->with('error', 'This plan is assigned to vendors and cannot be deleted.');
        }

        $vendorSubscription->delete();

        return redirect()
            ->route('vendor-subscriptions.index')
            ->with('success', 'Vendor subscription plan deleted successfully.');
    }

    public function toggleStatus(VendorSubscriptionPlan $vendorSubscription)
    {
        $vendorSubscription->update([
            'status' => $vendorSubscription->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Vendor subscription plan status updated.');
    }

    public function setDefault(VendorSubscriptionPlan $vendorSubscription)
    {
        DB::transaction(function () use ($vendorSubscription) {
            VendorSubscriptionPlan::query()->update(['is_default' => false]);
            $vendorSubscription->update([
                'is_default' => true,
                'status' => 'active',
            ]);
        });

        return back()->with('success', 'Default vendor subscription plan updated.');
    }

    private function rules(?int $planId = null): array
    {
        return [
            'plan_name' => ['required', 'string', 'max:150'],
            'plan_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('vendor_subscription_plans', 'plan_code')->ignore($planId),
            ],
            'short_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,active,inactive,archived'],
            'is_default' => ['nullable', 'boolean'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'yearly_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'yearly_discount_type' => ['nullable', 'in:percentage,fixed'],
            'yearly_discount_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency_code' => ['required', 'string', 'max:10'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'auto_renew' => ['nullable', 'boolean'],
            'cancellation_policy' => ['nullable', 'string', 'max:3000'],
            'refund_policy' => ['nullable', 'string', 'max:3000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'badge' => ['nullable', 'string', 'max:80'],
            'highlight_plan' => ['nullable', 'boolean'],
            'most_popular' => ['nullable', 'boolean'],
            'plan_card_color' => ['required', 'string', 'max:20'],
            'icon_key' => ['required', 'string', 'max:80'],
            'features' => ['nullable', 'array'],
            'features.*.feature_key' => ['required', 'string', 'max:100'],
            'features.*.feature_name' => ['required', 'string', 'max:150'],
            'features.*.feature_group' => ['required', 'string', 'max:80'],
            'features.*.value_type' => ['required', 'in:boolean,limit'],
            'features.*.enabled' => ['nullable', 'boolean'],
            'features.*.is_unlimited' => ['nullable', 'boolean'],
            'features.*.limit_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'features.*.unit' => ['nullable', 'string', 'max:40'],
            'features.*.notes' => ['nullable', 'string', 'max:500'],
            'features.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return [
            'plan_name.required' => 'Plan name is required.',
            'plan_code.required' => 'Plan code is required.',
            'plan_code.unique' => 'This plan code is already used.',
            'monthly_price.required' => 'Monthly price is required.',
        ];
    }

    private function planPayload(array $data): array
    {
        return [
            'plan_name' => $data['plan_name'],
            'plan_code' => strtoupper($data['plan_code']),
            'short_description' => $data['short_description'] ?? null,
            'status' => $data['status'],
            'is_default' => (bool) ($data['is_default'] ?? false),
            'monthly_price' => (float) ($data['monthly_price'] ?? 0),
            'yearly_price' => isset($data['yearly_price']) ? (float) $data['yearly_price'] : null,
            'yearly_discount_type' => $data['yearly_discount_type'] ?? null,
            'yearly_discount_value' => isset($data['yearly_discount_value']) ? (float) $data['yearly_discount_value'] : null,
            'currency_code' => strtoupper($data['currency_code'] ?? 'LKR'),
            'trial_days' => (int) ($data['trial_days'] ?? 0),
            'auto_renew' => (bool) ($data['auto_renew'] ?? true),
            'cancellation_policy' => $data['cancellation_policy'] ?? null,
            'refund_policy' => $data['refund_policy'] ?? null,
            'display_order' => (int) ($data['display_order'] ?? 0),
            'badge' => $data['badge'] ?? null,
            'highlight_plan' => (bool) ($data['highlight_plan'] ?? false),
            'most_popular' => (bool) ($data['most_popular'] ?? false),
            'plan_card_color' => $data['plan_card_color'] ?? '#5C2D80',
            'icon_key' => $data['icon_key'] ?? 'bi-gem',
        ];
    }

    private function syncFeatures(VendorSubscriptionPlan $plan, array $features): void
    {
        $catalog = collect($this->featureCatalog())->keyBy('feature_key');

        $plan->features()->delete();

        foreach ($features as $index => $feature) {
            $definition = $catalog->get($feature['feature_key']);

            if (!$definition) {
                continue;
            }

            $plan->features()->create([
                'feature_key' => $definition['feature_key'],
                'feature_name' => $feature['feature_name'] ?? $definition['feature_name'],
                'feature_group' => $feature['feature_group'] ?? $definition['feature_group'],
                'value_type' => $feature['value_type'] ?? $definition['value_type'],
                'enabled' => (bool) ($feature['enabled'] ?? false),
                'is_unlimited' => (bool) ($feature['is_unlimited'] ?? false),
                'limit_value' => isset($feature['limit_value']) && $feature['limit_value'] !== ''
                    ? (float) $feature['limit_value']
                    : null,
                'unit' => $feature['unit'] ?? $definition['unit'] ?? null,
                'notes' => $feature['notes'] ?? null,
                'sort_order' => (int) ($feature['sort_order'] ?? $definition['sort_order'] ?? $index),
            ]);
        }
    }

    private function serializePlan(VendorSubscriptionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'plan_name' => $plan->plan_name,
            'plan_code' => $plan->plan_code,
            'short_description' => $plan->short_description,
            'status' => $plan->status,
            'is_default' => (bool) $plan->is_default,
            'monthly_price' => $plan->monthly_price,
            'yearly_price' => $plan->yearly_price,
            'yearly_discount_type' => $plan->yearly_discount_type,
            'yearly_discount_value' => $plan->yearly_discount_value,
            'currency_code' => $plan->currency_code,
            'trial_days' => $plan->trial_days,
            'auto_renew' => (bool) $plan->auto_renew,
            'cancellation_policy' => $plan->cancellation_policy,
            'refund_policy' => $plan->refund_policy,
            'display_order' => $plan->display_order,
            'badge' => $plan->badge,
            'highlight_plan' => (bool) $plan->highlight_plan,
            'most_popular' => (bool) $plan->most_popular,
            'plan_card_color' => $plan->plan_card_color,
            'icon_key' => $plan->icon_key,
            'vendors_count' => $plan->vendors_count ?? $plan->vendors()->count(),
            'features' => $plan->features->map(fn ($feature) => [
                'id' => $feature->id,
                'feature_key' => $feature->feature_key,
                'feature_name' => $feature->feature_name,
                'feature_group' => $feature->feature_group,
                'value_type' => $feature->value_type,
                'enabled' => (bool) $feature->enabled,
                'is_unlimited' => (bool) $feature->is_unlimited,
                'limit_value' => $feature->limit_value,
                'unit' => $feature->unit,
                'notes' => $feature->notes,
                'sort_order' => $feature->sort_order,
            ])->values(),
        ];
    }

    private function statusOptions(): array
    {
        return [
            ['id' => 'draft', 'name' => 'Draft'],
            ['id' => 'active', 'name' => 'Active'],
            ['id' => 'inactive', 'name' => 'Inactive'],
            ['id' => 'archived', 'name' => 'Archived'],
        ];
    }

    private function iconOptions(): array
    {
        return [
            ['id' => 'bi-gem', 'name' => 'Gem'],
            ['id' => 'bi-shop', 'name' => 'Store'],
            ['id' => 'bi-cup-hot', 'name' => 'Restaurant'],
            ['id' => 'bi-lightning-charge', 'name' => 'Growth'],
            ['id' => 'bi-stars', 'name' => 'Premium'],
            ['id' => 'bi-rocket-takeoff', 'name' => 'Enterprise'],
        ];
    }

    private function featureCatalog(): array
    {
        return [
            [
                'feature_key' => 'products_count',
                'feature_name' => 'Products Count',
                'feature_group' => 'Catalog',
                'value_type' => 'limit',
                'unit' => 'products',
                'sort_order' => 10,
                'description' => 'Maximum menu/product records allowed for the vendor.',
            ],
            [
                'feature_key' => 'vendor_panel',
                'feature_name' => 'Vendor Panel Access',
                'feature_group' => 'Access',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 20,
                'description' => 'Controls whether the vendor admin panel can be enabled.',
            ],
            [
                'feature_key' => 'loyalty_points',
                'feature_name' => 'Loyalty Points',
                'feature_group' => 'Customer Growth',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 30,
                'description' => 'Loyalty program, rewards, tiers, and point transactions.',
            ],
            [
                'feature_key' => 'gift_cards',
                'feature_name' => 'Gift Cards',
                'feature_group' => 'Customer Growth',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 40,
                'description' => 'Gift card batches, balances, sales, and redemption tracking.',
            ],
            [
                'feature_key' => 'promotions',
                'feature_name' => 'Promotions',
                'feature_group' => 'Customer Growth',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 50,
                'description' => 'Discounts, vouchers, and campaign rules.',
            ],
            [
                'feature_key' => 'reports',
                'feature_name' => 'Reports',
                'feature_group' => 'Insights',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 60,
                'description' => 'Sales, inventory, POS, gift card, loyalty, and export reports.',
            ],
            [
                'feature_key' => 'activity_log',
                'feature_name' => 'Activity Log',
                'feature_group' => 'Governance',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 70,
                'description' => 'Activity and authentication log visibility for auditing.',
            ],
            [
                'feature_key' => 'seating_plan',
                'feature_name' => 'Seating Plan',
                'feature_group' => 'Restaurant Operations',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 80,
                'description' => 'Floors, zones, dining tables, and table merges.',
            ],
            [
                'feature_key' => 'kitchen',
                'feature_name' => 'Kitchen',
                'feature_group' => 'Restaurant Operations',
                'value_type' => 'boolean',
                'unit' => null,
                'sort_order' => 90,
                'description' => 'Kitchen viewer, KOT flow, preparation, ready, and served statuses.',
            ],
        ];
    }
}
