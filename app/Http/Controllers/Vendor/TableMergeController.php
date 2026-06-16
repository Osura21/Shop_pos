<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\TableMerge;
use App\Models\TableMergeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class TableMergeController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/SeatingPlan/TableMerge/Index');
    }

    public function getData(Request $request)
    {
        $rows = TableMerge::query()
            ->with(['branch:id,name', 'items.table:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->select('table_merges.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('merge_code', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhereHas('branch', function ($qb) use ($search) {
                                $qb->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('items.table', function ($qt) use ($search) {
                                $qt->where('name', 'like', "%{$search}%");
                            });
                    });
                }
            })

            ->addColumn('branch_name', function ($row) {
                return $row->branch?->name ?? '-';
            })

            ->addColumn('members_badges', function ($row) {

                return $row->items->map(function ($item) {

                    $isPrimary = (bool) $item->is_primary;

                    if ($isPrimary) {
                        return '
                <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1 m-1">
                    <i class="bi bi-star-fill"></i>
                    '.e($item->table?->name ?? '-').'
                </span>
            ';
                    }

                    return '
            <span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary d-inline-flex align-items-center px-2 py-1 m-1">
                '.e($item->table?->name ?? '-').'
            </span>
        ';
                })->implode(' ');
            })

            ->addColumn('type_badge', function ($row) {

                $label = TableMerge::TYPES[$row->type] ?? ucfirst($row->type);
                $isBilling = $row->type === 'billing';

                if ($isBilling) {
                    return '
            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1 min-width-85">
                <i class="bi bi-receipt"></i>
                '.e($label).'
            </span>
        ';
                }

                return '
        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary d-inline-flex align-items-center gap-1 px-2 py-1 min-width-85">
            <i class="bi bi-arrows-expand"></i>
            '.e($label).'
        </span>
    ';
            })

            ->editColumn('closed_at', function ($row) {
                return optional($row->closed_at)?->format('Y-m-d h:i A') ?: '-';
            })

            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)?->format('Y-m-d h:i A') ?: '-';
            })

            ->editColumn('updated_at', function ($row) {
                return optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-';
            })

            ->rawColumns(['members_badges', 'type_badge'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/SeatingPlan/TableMerge/CreateUpdate', [
            'merge' => null,
            'branches' => $this->branches(),
            'tables' => $this->tables(),
            'typeOptions' => TableMerge::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($validated) {
            $merge = TableMerge::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $validated['branch_id'],
                'type' => $validated['type'],
                'created_by_name' => optional(auth('vendor')->user())->name,
            ]);

            foreach (array_values($validated['member_table_ids']) as $index => $tableId) {
                TableMergeItem::create([
                    'table_merge_id' => $merge->id,
                    'dining_table_id' => $tableId,
                    'is_primary' => $index === 0,
                ]);
            }

            DiningTable::query()
                ->whereIn('id', $validated['member_table_ids'])
                ->update(['status' => 'occupied']);
        });

        return redirect()->route('vendor.table-merges.index')->with('success', 'Table merge created successfully.');
    }

    public function edit($id)
    {
        $merge = TableMerge::query()
            ->with(['items'])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        return Inertia::render('VendorAdmin/SeatingPlan/TableMerge/CreateUpdate', [
            'merge' => [
                'id' => $merge->id,
                'branch_id' => $merge->branch_id,
                'type' => $merge->type,
                'member_table_ids' => $merge->items->pluck('dining_table_id')->values()->all(),
            ],
            'branches' => $this->branches(),
            'tables' => $this->tables(),
            'typeOptions' => TableMerge::TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $merge = TableMerge::query()->where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($merge->id), $this->messages());

        DB::transaction(function () use ($merge, $validated) {
            $oldIds = $merge->items()->pluck('dining_table_id')->all();

            DiningTable::query()->whereIn('id', $oldIds)->update(['status' => 'available']);

            $merge->update([
                'branch_id' => $validated['branch_id'],
                'type' => $validated['type'],
            ]);

            $merge->items()->delete();

            foreach (array_values($validated['member_table_ids']) as $index => $tableId) {
                TableMergeItem::create([
                    'table_merge_id' => $merge->id,
                    'dining_table_id' => $tableId,
                    'is_primary' => $index === 0,
                ]);
            }

            DiningTable::query()->whereIn('id', $validated['member_table_ids'])->update(['status' => 'occupied']);
        });

        return redirect()->route('vendor.table-merges.index')->with('success', 'Table merge updated successfully.');
    }

    public function close($id)
    {
        $merge = TableMerge::query()
            ->with(['items'])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        DB::transaction(function () use ($merge) {
            $merge->update([
                'closed_by_name' => optional(auth('vendor')->user())->name,
                'closed_at' => Carbon::now(),
            ]);

            DiningTable::query()
                ->whereIn('id', $merge->items->pluck('dining_table_id')->all())
                ->update(['status' => 'available']);
        });

        return back()->with('success', 'Table merge closed successfully.');
    }

    public function destroy($id)
    {
        $merge = TableMerge::query()
            ->with(['items'])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        DB::transaction(function () use ($merge) {
            DiningTable::query()
                ->whereIn('id', $merge->items->pluck('dining_table_id')->all())
                ->update(['status' => 'available']);

            $merge->items()->delete();
            $merge->delete();
        });

        return back()->with('success', 'Table merge deleted successfully.');
    }

    private function rules(?int $id = null): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
            'type' => ['required', Rule::in(array_keys(TableMerge::TYPES))],
            'member_table_ids' => [
                'required',
                'array',
                'min:2',
                function ($attribute, $value, $fail) use ($id) {
                    if (! is_array($value)) {
                        return;
                    }

                    $alreadyMergedTables = DiningTable::query()
                        ->whereIn('id', $value)
                        ->whereHas('mergeItems.merge', function ($q) use ($id) {
                            $q->whereNull('closed_at');
                            if ($id) {
                                $q->where('table_merges.id', '!=', $id);
                            }
                        })
                        ->pluck('name')
                        ->toArray();

                    if (! empty($alreadyMergedTables)) {
                        $names = implode(', ', $alreadyMergedTables);
                        if (count($alreadyMergedTables) === 1) {
                            $fail("The table {$names} is already merged in another active merge.");
                        } else {
                            $fail("The following tables are already merged in another active merge: {$names}.");
                        }
                    }
                },
            ],
            'member_table_ids.*' => ['integer', Rule::exists('dining_tables', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))],
        ];
    }

    private function messages(): array
    {
        return [
            'branch_id.required' => 'Branch is required.',
            'type.required' => 'Merge type is required.',
            'member_table_ids.required' => 'Please select at least two tables.',
            'member_table_ids.min' => 'Please select at least two tables.',
        ];
    }

    private function branches()
    {
        return Branch::query()->where('tenant_id', $this->tenantId())->select('id', 'name')->orderBy('name')->get();
    }

    private function tables()
    {
        return DiningTable::query()
            ->with(['branch:id,name', 'floor:id,name', 'zone:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'branch_id', 'floor_id', 'zone_id', 'name', 'status')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'branch_id' => $row->branch_id,
                'name' => $row->name,
                'status' => $row->status,
                'label' => $row->name.' · '.($row->floor?->name ?? '-').' · '.($row->zone?->name ?? '-'),
            ]);
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}
