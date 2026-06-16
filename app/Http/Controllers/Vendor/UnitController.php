<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    private const TYPE_OPTIONS = [
        'custom' => 'Custom',
        'count' => 'Count',
        'weight' => 'Weight',
        'volume' => 'Volume',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Inventory/Unit/Index');
    }

public function getData(Request $request)
{
    $units = Unit::query()
        ->where('tenant_id', $this->tenantId())
        ->select('units.*');

    return DataTables::of($units)

        ->filter(function ($query) use ($request) {
            $search = $request->input('search.value');

            if (filled($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('symbol', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            }
        })

        ->addColumn('type_label', function ($row) {

    $label = self::TYPE_OPTIONS[$row->type] ?? ucfirst($row->type);

    return '
        <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-box"></i>
            ' . e($label) . '
        </span>
    ';
})

        ->editColumn('created_at', function ($row) {
            return optional($row->created_at)?->format('Y-m-d h:i A') ?: '-';
        })

        ->editColumn('updated_at', function ($row) {
            return optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-';
        })

        ->rawColumns(['type_label', 'symbol_badge'])
        ->make(true);
}

    public function create()
    {
        return Inertia::render('VendorAdmin/Inventory/Unit/CreateUpdate', [
            'unit' => null,
            'typeOptions' => self::TYPE_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $unit = new Unit();
            $unit->tenant_id = $this->tenantId();
            $this->fillUnit($unit, $validated);
            $unit->save();

            DB::commit();

            return redirect()->route('vendor.units.index')->with('success', 'Unit created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Unit store query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Unable to save unit.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Unit store failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Something went wrong while creating the unit.']);
        }
    }

    public function edit($id)
    {
        $unit = Unit::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Inventory/Unit/CreateUpdate', [
            'unit' => $unit,
            'typeOptions' => self::TYPE_OPTIONS,
        ]);
    }

    public function update(Request $request, $id)
    {
        $unit = Unit::where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($unit->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillUnit($unit, $validated);
            $unit->save();

            DB::commit();

            return redirect()->route('vendor.units.index')->with('success', 'Unit updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Unit update query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Unable to update unit.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Unit update failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);
            return back()->withInput()->withErrors(['general' => 'Something went wrong while updating the unit.']);
        }
    }

    public function destroy($id)
    {
        try {
            $unit = Unit::where('tenant_id', $this->tenantId())->findOrFail($id);
            $unit->delete();

            return redirect()->route('vendor.units.index')->with('success', 'Unit deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Unit delete failed', ['message' => $ex->getMessage()]);
            return back()->withErrors(['general' => 'Unable to delete unit.']);
        }
    }

    private function fillUnit(Unit $unit, array $validated): void
    {
        $unit->name = $validated['name'];
        $unit->symbol = $validated['symbol'];
        $unit->type = $validated['type'];
        $unit->is_active = (bool) ($validated['is_active'] ?? true);
    }

    private function rules($unitId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($unitId),
            ],
            'symbol' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'symbol')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($unitId),
            ],
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPE_OPTIONS))],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Unit name is required.',
            'symbol.required' => 'Unit symbol is required.',
            'type.required' => 'Type is required.',
            'name.unique' => 'This unit name already exists.',
            'symbol.unique' => 'This unit symbol already exists.',
        ];
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}