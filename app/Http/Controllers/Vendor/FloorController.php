<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Floor;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class FloorController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/SeatingPlan/Floor/Index');
    }

    public function getData(Request $request)
{
    $rows = Floor::query()
        ->with(['branch:id,name'])
        ->where('tenant_id', $this->tenantId())
        ->select('floors.*');

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
        return Inertia::render('VendorAdmin/SeatingPlan/Floor/CreateUpdate', [
            'floor' => null,
            'branches' => $this->branches(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            $floor = Floor::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $validated['branch_id'],
                'name' => $validated['name'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            return redirect()->route('vendor.floors.index')->with('success', 'Floor created successfully.');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['general' => 'Unable to save floor.']);
        }
    }

    public function edit($id)
    {
        $floor = Floor::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        return Inertia::render('VendorAdmin/SeatingPlan/Floor/CreateUpdate', [
            'floor' => $floor,
            'branches' => $this->branches(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $floor = Floor::query()->where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($floor->id), $this->messages());

        $floor->update([
            'branch_id' => $validated['branch_id'],
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('vendor.floors.index')->with('success', 'Floor updated successfully.');
    }

    public function destroy($id)
    {
        $floor = Floor::query()->where('tenant_id', $this->tenantId())->findOrFail($id);
        $floor->delete();

        return back()->with('success', 'Floor deleted successfully.');
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Floor name is required.',
            'branch_id.required' => 'Branch is required.',
        ];
    }

    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}