<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ZoneController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/SeatingPlan/Zone/Index');
    }

    public function getData(Request $request)
{
    $rows = Zone::query()
        ->with(['branch:id,name', 'floor:id,name'])
        ->where('tenant_id', $this->tenantId())
        ->select('zones.*');

    return DataTables::of($rows)
        ->filter(function ($query) use ($request) {
            $search = $request->input('search.value');

            if (filled($search)) {
                $query->where('name', 'like', "%{$search}%");
            }
        })

        ->addColumn('branch_name', function ($row) {
            return $row->branch?->name ?? '-';
        })

        ->addColumn('floor_name', function ($row) {
            return $row->floor?->name ?? '-';
        })

        ->addColumn('activation_badge', function ($row) {

    $isActive = (int) $row->is_active === 1;

    if ($isActive) {
        return '
            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1 min-width-85">
                <i class="bi bi-check-circle-fill"></i>
                Active
            </span>
        ';
    }

    return '
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1 min-width-85">
            <i class="bi bi-x-circle-fill text-danger"></i>
            Inactive
        </span>
    ';
})

        ->editColumn('created_at', function ($row) {
            return optional($row->created_at)?->format('Y-m-d h:i A') ?: '-';
        })

        ->editColumn('updated_at', function ($row) {
            return optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-';
        })

        ->rawColumns(['activation_badge'])
        ->make(true);
}

    public function create()
    {
        return Inertia::render('VendorAdmin/SeatingPlan/Zone/CreateUpdate', [
            'zone' => null,
            'branches' => $this->branches(),
            'floors' => $this->floors(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        Zone::create([
            'tenant_id' => $this->tenantId(),
            'branch_id' => $validated['branch_id'],
            'floor_id' => $validated['floor_id'],
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('vendor.zones.index')->with('success', 'Zone created successfully.');
    }

    public function edit($id)
    {
        $zone = Zone::query()->where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/SeatingPlan/Zone/CreateUpdate', [
            'zone' => $zone,
            'branches' => $this->branches(),
            'floors' => $this->floors(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $zone = Zone::query()->where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($zone->id), $this->messages());

        $zone->update([
            'branch_id' => $validated['branch_id'],
            'floor_id' => $validated['floor_id'],
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('vendor.zones.index')->with('success', 'Zone updated successfully.');
    }

    public function destroy($id)
    {
        $zone = Zone::query()->where('tenant_id', $this->tenantId())->findOrFail($id);
        $zone->delete();

        return back()->with('success', 'Zone deleted successfully.');
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('zones', 'name')
                    ->where('tenant_id', $this->tenantId())
                    ->where('floor_id', request('floor_id'))
                    ->ignore($id),
            ],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'floor_id' => ['required', 'integer', Rule::exists('floors', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Zone name is required.',
            'name.unique' => 'The zone name has already been taken for this floor.',
            'branch_id.required' => 'Branch is required.',
            'floor_id.required' => 'Floor is required.',
        ];
    }

    private function branches()
    {
        return Branch::query()->where('tenant_id', $this->tenantId())->select('id', 'name')->orderBy('name')->get();
    }

    private function floors()
    {
        return Floor::query()->where('tenant_id', $this->tenantId())->select('id', 'branch_id', 'name')->orderBy('name')->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}