<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\LoyaltyTier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyTierController extends BaseLoyaltyController
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Loyalty/Tiers/Index');
    }

    public function getData(Request $request)
    {
        $rows = LoyaltyTier::query()
            ->with('program:id,name')
            ->where('tenant_id', $this->tenantId())
            ->select('loyalty_tiers.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (filled($search)) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhereHas('program', fn ($program) => $program->where('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->addColumn('icon_url', fn ($row) => $row->icon_url)
            ->addColumn('program_name', fn ($row) => $row->program?->name ?? '-')
            ->addColumn('status_badge', fn ($row) => $this->activeBadge((bool) $row->is_active))
            ->editColumn('minimum_spend', fn ($row) => $this->baseCurrencySymbol() . ' ' . $this->money($row->minimum_spend))
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn ($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Loyalty/Tiers/CreateUpdate', [
            'tier' => null,
            'programs' => $this->programs(),
        ]);
    }

   public function store(Request $request)
{
    DB::transaction(function () use ($request) {
        $tier = LoyaltyTier::create($this->payload($request));

        $this->syncIconMedia($request, $tier);
    });

    return redirect()->route('vendor.loyalty.tiers.index')->with('success', 'Loyalty tier created successfully.');
}

    public function edit($id)
    {
        return Inertia::render('VendorAdmin/Loyalty/Tiers/CreateUpdate', [
            'tier' => LoyaltyTier::where('tenant_id', $this->tenantId())->findOrFail($id),
            'programs' => $this->programs(),
        ]);
    }

  public function update(Request $request, $id)
{
    DB::transaction(function () use ($request, $id) {
        $tier = LoyaltyTier::where('tenant_id', $this->tenantId())->findOrFail($id);

        $tier->update($this->payload($request));

        $this->syncIconMedia($request, $tier);
    });

    return redirect()->route('vendor.loyalty.tiers.index')->with('success', 'Loyalty tier updated successfully.');
}

  public function destroy($id)
{
    $tier = LoyaltyTier::where('tenant_id', $this->tenantId())->findOrFail($id);

    $tier->clearMediaCollection('Loyalty_Tier_Images');
    $tier->delete();

    return redirect()->route('vendor.loyalty.tiers.index')->with('success', 'Loyalty tier deleted successfully.');
}

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'loyalty_program_id' => ['required', Rule::exists('loyalty_programs', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'name' => ['required', 'string', 'max:255'],
            'benefits' => ['nullable', 'string'],
            'minimum_spend' => ['required', 'numeric', 'min:0'],
            'secondary_minimum_spend' => ['nullable', 'numeric', 'min:0'],
            'multiplier' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
           'icon_path' => ['nullable', 'string', 'max:255'],
'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
'remove_icon' => ['nullable', 'boolean'],
'is_active' => ['boolean'],
        ]);

        return [
            'tenant_id' => $this->tenantId(),
            'loyalty_program_id' => $validated['loyalty_program_id'],
            'name' => $validated['name'],
            'benefits' => $validated['benefits'] ?? null,
            'minimum_spend' => $validated['minimum_spend'],
            'secondary_minimum_spend' => $this->secondaryCurrencyCode() ? ($validated['secondary_minimum_spend'] ?? null) : null,
            'multiplier' => $validated['multiplier'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'icon_path' => $validated['icon_path'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function syncIconMedia(Request $request, LoyaltyTier $tier): void
{
    if ($request->boolean('remove_icon') && !$request->hasFile('icon')) {
        $tier->clearMediaCollection('Loyalty_Tier_Images');

        $tier->update([
            'icon_path' => null,
        ]);

        return;
    }

    if ($request->hasFile('icon')) {
        $tier->clearMediaCollection('Loyalty_Tier_Images');

        $tier->addMedia($request->file('icon'))
            ->toMediaCollection('Loyalty_Tier_Images');

        $tier->update([
            'icon_path' => $tier->getFirstMediaUrl('Loyalty_Tier_Images'),
        ]);
    }
}
}
