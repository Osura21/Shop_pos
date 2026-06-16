<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\LoyaltyPromotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyPromotionController extends BaseLoyaltyController
{
    private const TYPES = [
        'multiplier' => 'Multiplier',
        'bonus_points' => 'Bonus Points',
        'new_member' => 'New Member',
        'category_boost' => 'Category Boost',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Loyalty/Promotions/Index');
    }

    public function getData(Request $request)
    {
        $rows = LoyaltyPromotion::query()
            ->with('program:id,name')
            ->where('tenant_id', $this->tenantId())
            ->select('loyalty_promotions.*');

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
            ->addColumn('program_name', fn ($row) => $row->program?->name ?? '-')
            ->addColumn('type_badge', fn ($row) => $this->typeBadge($row->type))
            ->addColumn('value', fn ($row) => $row->type === 'multiplier' ? $row->multiplier . 'X' : (($row->bonus_points ?: 0) . ' Pts'))
            ->addColumn('status_badge', fn ($row) => $this->activeBadge((bool) $row->is_active))
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn ($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['type_badge', 'status_badge'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Loyalty/Promotions/CreateUpdate', $this->formProps(null));
    }

    public function store(Request $request)
    {
        LoyaltyPromotion::create($this->payload($request));

        return redirect()->route('vendor.loyalty.promotions.index')->with('success', 'Loyalty promotion created successfully.');
    }

    public function edit($id)
    {
        $promotion = LoyaltyPromotion::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Loyalty/Promotions/CreateUpdate', $this->formProps($promotion));
    }

    public function update(Request $request, $id)
    {
        LoyaltyPromotion::where('tenant_id', $this->tenantId())->findOrFail($id)->update($this->payload($request));

        return redirect()->route('vendor.loyalty.promotions.index')->with('success', 'Loyalty promotion updated successfully.');
    }

    public function destroy($id)
    {
        LoyaltyPromotion::where('tenant_id', $this->tenantId())->findOrFail($id)->delete();

        return redirect()->route('vendor.loyalty.promotions.index')->with('success', 'Loyalty promotion deleted successfully.');
    }

    private function formProps(?LoyaltyPromotion $promotion): array
    {
        return [
            'promotion' => $promotion,
            'programs' => $this->programs(),
            'branches' => $this->branches(),
            'availableDays' => $this->days(),
            'promotionTypes' => collect(self::TYPES)->map(fn ($label, $value) => compact('value', 'label'))->values(),
        ];
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'loyalty_program_id' => ['required', Rule::exists('loyalty_programs', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'multiplier' => ['nullable', 'numeric', 'min:0'],
            'bonus_points' => ['nullable', 'integer', 'min:1'],
            'minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'secondary_minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
            'available_days' => ['nullable', 'array'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        return [
            'tenant_id' => $this->tenantId(),
            'loyalty_program_id' => $validated['loyalty_program_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'multiplier' => $validated['type'] === 'multiplier' ? ($validated['multiplier'] ?? 1) : null,
            'bonus_points' => in_array($validated['type'], ['bonus_points', 'new_member'], true) ? ($validated['bonus_points'] ?? 0) : null,
            'minimum_spend' => $validated['minimum_spend'] ?? null,
            'secondary_minimum_spend' => $this->secondaryCurrencyCode() ? ($validated['secondary_minimum_spend'] ?? null) : null,
            'branch_ids' => array_values($validated['branch_ids'] ?? []),
            'available_days' => array_values($validated['available_days'] ?? []),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function typeBadge(string $type): string
    {
        return '<span class="badge rounded-pill bg-info-subtle text-info">' . e(self::TYPES[$type] ?? $type) . '</span>';
    }
}
