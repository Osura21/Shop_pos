<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\Floor;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class DiningTableController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/SeatingPlan/Table/Index');
    }

    public function getData(Request $request)
{
    $rows = DiningTable::query()
        ->with(['branch:id,name', 'floor:id,name', 'zone:id,name'])
        ->where('tenant_id', $this->tenantId())
        ->select('dining_tables.*');

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

        ->addColumn('zone_name', function ($row) {
            return $row->zone?->name ?? '-';
        })

        ->addColumn('activation_badge', function ($row) {

    $isActive = (int) $row->is_active === 1;

    if ($isActive) {
        return '
            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-check-circle-fill"></i>
                Active
            </span>
        ';
    }

    return '
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
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
        return Inertia::render('VendorAdmin/SeatingPlan/Table/CreateUpdate', [
            'table' => null,
            'branches' => $this->branches(),
            'floors' => $this->floors(),
            'zones' => $this->zones(),
            'shapeOptions' => DiningTable::SHAPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        DiningTable::create([
            'tenant_id' => $this->tenantId(),
            'branch_id' => $validated['branch_id'],
            'floor_id' => $validated['floor_id'],
            'zone_id' => $validated['zone_id'],
            'name' => $validated['name'],
            'shape' => $validated['shape'],
            'capacity' => $validated['capacity'],
            'status' => $validated['status'] ?? 'available',
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('vendor.tables.index')->with('success', 'Table created successfully.');
    }

    public function edit($id)
    {
        $table = DiningTable::query()->where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/SeatingPlan/Table/CreateUpdate', [
            'table' => $table,
            'branches' => $this->branches(),
            'floors' => $this->floors(),
            'zones' => $this->zones(),
            'shapeOptions' => DiningTable::SHAPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $table = DiningTable::query()->where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($table->id), $this->messages());

        $table->update([
            'branch_id' => $validated['branch_id'],
            'floor_id' => $validated['floor_id'],
            'zone_id' => $validated['zone_id'],
            'name' => $validated['name'],
            'shape' => $validated['shape'],
            'capacity' => $validated['capacity'],
            'status' => $validated['status'] ?? $table->status,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('vendor.tables.index')->with('success', 'Table updated successfully.');
    }

    public function destroy($id)
    {
        $table = DiningTable::query()->where('tenant_id', $this->tenantId())->findOrFail($id);
        $table->delete();

        return back()->with('success', 'Table deleted successfully.');
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dining_tables', 'name')
                    ->where('tenant_id', $this->tenantId())
                    ->where('branch_id', request('branch_id'))
                    ->ignore($id),
            ],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'floor_id' => ['required', 'integer', Rule::exists('floors', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'zone_id' => ['required', 'integer', Rule::exists('zones', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'shape' => ['required', Rule::in(array_keys(DiningTable::SHAPES))],
            'capacity' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(array_keys(DiningTable::STATUSES))],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Table name is required.',
            'name.unique' => 'The table name has already been taken for this branch.',
            'branch_id.required' => 'Branch is required.',
            'floor_id.required' => 'Floor is required.',
            'zone_id.required' => 'Zone is required.',
            'shape.required' => 'Shape is required.',
            'capacity.required' => 'Capacity is required.',
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

    private function zones()
    {
        return Zone::query()->where('tenant_id', $this->tenantId())->select('id', 'branch_id', 'floor_id', 'name')->orderBy('name')->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}