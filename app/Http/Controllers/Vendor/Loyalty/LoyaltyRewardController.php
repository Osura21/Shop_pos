<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\LoyaltyReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyRewardController extends BaseLoyaltyController
{
    private const TYPES = [
        'discount' => 'Discount',
        'voucher_code' => 'Voucher Code',
        'free_item' => 'Free Item',
        'tier_upgrade' => 'Tier Upgrade',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Loyalty/Rewards/Index');
    }

    public function getData(Request $request)
    {
        $rows = LoyaltyReward::query()
            ->with(['program:id,name', 'tier:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->select('loyalty_rewards.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (filled($search)) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhereHas('program', fn ($program) => $program->where('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->addColumn('icon_url', fn ($row) => $row->icon_url)
            ->addColumn('program_name', fn ($row) => $row->program?->name ?? '-')
            ->addColumn('tier_name', fn ($row) => $row->tier?->name ?? '-')
            ->addColumn('type_badge', fn ($row) => $this->typeBadge($row->type))
            ->addColumn('status_badge', fn ($row) => $this->activeBadge((bool) $row->is_active))
            ->editColumn('points_cost', fn ($row) => number_format((int) $row->points_cost).' Pts')
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn ($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['type_badge', 'status_badge'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Loyalty/Rewards/CreateUpdate', $this->formProps(null));
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $reward = LoyaltyReward::create($this->payload($request));

            $this->syncIconMedia($request, $reward);
        });

        return redirect()->route('vendor.loyalty.rewards.index')->with('success', 'Loyalty reward created successfully.');
    }

    public function edit($id)
    {
        $reward = LoyaltyReward::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Loyalty/Rewards/CreateUpdate', $this->formProps($reward));
    }

    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $reward = LoyaltyReward::where('tenant_id', $this->tenantId())->findOrFail($id);

            $reward->update($this->payload($request));

            $this->syncIconMedia($request, $reward);
        });

        return redirect()->route('vendor.loyalty.rewards.index')->with('success', 'Loyalty reward updated successfully.');
    }

    public function destroy($id)
    {
        $reward = LoyaltyReward::where('tenant_id', $this->tenantId())->findOrFail($id);

        $reward->clearMediaCollection('Loyalty_Reward_Images');
        $reward->delete();

        return redirect()->route('vendor.loyalty.rewards.index')->with('success', 'Loyalty reward deleted successfully.');
    }

    private function formProps(?LoyaltyReward $reward): array
    {
        return [
            'reward' => $reward,
            'programs' => $this->programs(),
            'tiers' => $this->tiers(),
            'products' => $this->products(),
            'branches' => $this->branches(),
            'availableDays' => $this->days(),
            'rewardTypes' => collect(self::TYPES)->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'valueTypes' => [
                ['value' => 'fixed', 'label' => 'Fixed'],
                ['value' => 'percentage', 'label' => 'Percentage'],
            ],
        ];
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'loyalty_program_id' => ['required', Rule::exists('loyalty_programs', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'loyalty_tier_id' => ['nullable', Rule::exists('loyalty_tiers', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'points_cost' => ['required', 'integer', 'min:1'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'value_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'secondary_value' => ['nullable', 'numeric', 'min:0'],
            'product_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'target_tier_id' => ['nullable', 'integer'],
            'code_prefix' => ['nullable', 'string', 'max:30'],
            'expires_in_days' => ['nullable', 'integer', 'min:1'],
            'minimum_order_total' => ['nullable', 'numeric', 'min:0'],
            'secondary_minimum_order_total' => ['nullable', 'numeric', 'min:0'],
            'maximum_order_total' => ['nullable', 'numeric', 'min:0'],
            'secondary_maximum_order_total' => ['nullable', 'numeric', 'min:0'],
            'minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'secondary_minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
            'available_days' => ['nullable', 'array'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_redemptions_per_order' => ['nullable', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_icon' => ['nullable', 'boolean'],
        ]);

        $type = $validated['type'];

        return [
            'tenant_id' => $this->tenantId(),
            'loyalty_program_id' => $validated['loyalty_program_id'],
            'loyalty_tier_id' => $validated['loyalty_tier_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $type,
            'points_cost' => $validated['points_cost'],
            'icon_path' => $validated['icon_path'] ?? null,
            'value_type' => in_array($type, ['discount', 'voucher_code'], true) ? ($validated['value_type'] ?? 'fixed') : null,
            'value' => in_array($type, ['discount', 'voucher_code'], true) ? ($validated['value'] ?? 0) : null,
            'secondary_value' => $this->secondaryCurrencyCode() && in_array($type, ['discount', 'voucher_code'], true) ? ($validated['secondary_value'] ?? null) : null,
            'product_id' => $type === 'free_item' ? ($validated['product_id'] ?? null) : null,
            'quantity' => $type === 'free_item' ? ($validated['quantity'] ?? 1) : null,
            'target_tier_id' => $type === 'tier_upgrade' ? ($validated['target_tier_id'] ?? null) : null,
            'code_prefix' => $type === 'voucher_code' ? ($validated['code_prefix'] ?? 'LOY') : null,
            'expires_in_days' => $validated['expires_in_days'] ?? null,
            'minimum_order_total' => $validated['minimum_order_total'] ?? null,
            'secondary_minimum_order_total' => $this->secondaryCurrencyCode() ? ($validated['secondary_minimum_order_total'] ?? null) : null,
            'maximum_order_total' => $validated['maximum_order_total'] ?? null,
            'secondary_maximum_order_total' => $this->secondaryCurrencyCode() ? ($validated['secondary_maximum_order_total'] ?? null) : null,
            'minimum_spend' => $validated['minimum_spend'] ?? null,
            'secondary_minimum_spend' => $this->secondaryCurrencyCode() ? ($validated['secondary_minimum_spend'] ?? null) : null,
            'branch_ids' => array_values($validated['branch_ids'] ?? []),
            'available_days' => array_values($validated['available_days'] ?? []),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'max_redemptions_per_order' => $validated['max_redemptions_per_order'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function typeBadge(string $type): string
    {
        $classes = [
            'discount' => 'bg-primary-subtle text-primary',
            'voucher_code' => 'bg-info-subtle text-info',
            'free_item' => 'bg-success-subtle text-success',
            'tier_upgrade' => 'bg-warning-subtle text-warning',
        ];

        return '<span class="badge rounded-pill '.($classes[$type] ?? 'bg-secondary-subtle text-secondary').'">'.e(self::TYPES[$type] ?? $type).'</span>';
    }

    private function syncIconMedia(Request $request, LoyaltyReward $reward): void
    {
        if ($request->boolean('remove_icon') && ! $request->hasFile('icon')) {
            $reward->clearMediaCollection('Loyalty_Reward_Images');

            $reward->update([
                'icon_path' => null,
            ]);

            return;
        }

        if ($request->hasFile('icon')) {
            $reward->clearMediaCollection('Loyalty_Reward_Images');

            $reward->addMedia($request->file('icon'))
                ->toMediaCollection('Loyalty_Reward_Images');

            $reward->update([
                'icon_path' => $reward->getFirstMediaUrl('Loyalty_Reward_Images'),
            ]);
        }
    }
}
