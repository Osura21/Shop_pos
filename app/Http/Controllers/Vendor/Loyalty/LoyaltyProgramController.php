<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\LoyaltyProgram;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyProgramController extends BaseLoyaltyController
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Loyalty/Programs/Index');
    }

    public function getData(Request $request)
    {
        $rows = LoyaltyProgram::query()->where('tenant_id', $this->tenantId())->select('loyalty_programs.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (filled($search)) {
                    $query->where('name', 'like', "%{$search}%");
                }
            })
            ->addColumn('status_badge', fn ($row) => $this->activeBadge((bool) $row->is_active))
            ->editColumn('earning_rate', fn ($row) => $this->baseCurrencySymbol() . ' ' . $this->money($row->earning_rate))
            ->editColumn('points_expire_after_days', fn ($row) => $row->points_expire_after_days ?: '-')
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn ($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Loyalty/Programs/CreateUpdate', ['program' => null]);
    }

    public function store(Request $request)
    {
        LoyaltyProgram::create($this->payload($request));

        return redirect()->route('vendor.loyalty.programs.index')->with('success', 'Loyalty program created successfully.');
    }

    public function edit($id)
    {
        return Inertia::render('VendorAdmin/Loyalty/Programs/CreateUpdate', [
            'program' => LoyaltyProgram::where('tenant_id', $this->tenantId())->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        LoyaltyProgram::where('tenant_id', $this->tenantId())->findOrFail($id)->update($this->payload($request));

        return redirect()->route('vendor.loyalty.programs.index')->with('success', 'Loyalty program updated successfully.');
    }

    public function destroy($id)
    {
        LoyaltyProgram::where('tenant_id', $this->tenantId())->findOrFail($id)->delete();

        return redirect()->route('vendor.loyalty.programs.index')->with('success', 'Loyalty program deleted successfully.');
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'earning_rate' => ['required', 'numeric', 'min:0'],
            'points_expire_after_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        return [
            'tenant_id' => $this->tenantId(),
            'name' => $validated['name'],
            'earning_rate' => $validated['earning_rate'],
            'points_expire_after_days' => $validated['points_expire_after_days'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
