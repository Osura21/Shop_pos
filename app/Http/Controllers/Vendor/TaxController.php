<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Tax;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class TaxController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Tax/Index');
    }

    public function getData(Request $request)
{
    $taxes = Tax::query()
        ->with(['branch:id,name'])
        ->where('tenant_id', $this->tenantId())
        ->select('taxes.*');

    return DataTables::of($taxes)

            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
                }
            })

            ->addColumn('branch_name', function ($row) {
                return $row->is_global ? 'Global' : ($row->branch?->name ?? '-');
            })

            ->addColumn('compound_badge', function ($row) {

                if ($row->is_compound) {
                    return '
            <span class="badge rounded-pill border border-warning text-warning d-inline-flex align-items-center gap-1 px-2 py-1">
                <i class="bi bi-layers"></i>
                Compound
            </span>
        ';
                }

                return '
        <span class="badge rounded-pill border text-secondary d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-circle"></i>
            Uncompounded
        </span>
    ';
            })

            ->addColumn('type_badge', function ($row) {

                $label = Tax::TYPES[$row->type] ?? ucfirst($row->type);

                return '
        <span class="badge rounded-pill text-primary d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-percent"></i>
            ' . e($label) . '
        </span>
    ';
            })

            ->addColumn('status_badge', function ($row) {

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

            ->editColumn('rate', function ($row) {
                return number_format((float) $row->rate, 3);
            })

            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)?->format('Y-m-d h:i A') ?: '-';
            })

            ->editColumn('updated_at', function ($row) {
                return optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-';
            })

            ->rawColumns([
                'branch_name',
                'compound_badge',
                'type_badge',
                'status_badge',
                'rate'
            ])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Tax/CreateUpdate', [
            'tax' => null,
            'branches' => $this->branches(),
            'typeOptions' => Tax::TYPES,
            'orderTypeOptions' => Tax::ORDER_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $tax = new Tax();
            $tax->tenant_id = $this->tenantId();

            $this->fillTax($tax, $validated);
            $tax->save();

            DB::commit();

            return redirect()
                ->route('vendor.taxes.index')
                ->with('success', 'Tax created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Tax store query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to save tax.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Tax store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while creating tax.',
            ]);
        }
    }

    public function edit($id)
    {
        $tax = Tax::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        return Inertia::render('VendorAdmin/Tax/CreateUpdate', [
            'tax' => $tax,
            'branches' => $this->branches(),
            'typeOptions' => Tax::TYPES,
            'orderTypeOptions' => Tax::ORDER_TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $tax = Tax::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        $validated = $request->validate($this->rules($tax->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillTax($tax, $validated);
            $tax->save();

            DB::commit();

            return redirect()
                ->route('vendor.taxes.index')
                ->with('success', 'Tax updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();

            Log::error('Tax update query failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => 'Unable to update tax.',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();

            Log::error('Tax update failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()->withInput()->withErrors([
                'general' => $ex->getMessage() ?: 'Something went wrong while updating tax.',
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $tax = Tax::query()
                ->where('tenant_id', $this->tenantId())
                ->findOrFail($id);

            $tax->delete();

            return redirect()
                ->route('vendor.taxes.index')
                ->with('success', 'Tax deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Tax delete failed', ['message' => $ex->getMessage()]);

            return back()->withErrors([
                'general' => 'Unable to delete tax.',
            ]);
        }
    }

    public function toggleStatus($id)
    {
        $tax = Tax::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        $tax->is_active = ! $tax->is_active;
        $tax->save();

        return back()->with('success', 'Tax status updated successfully.');
    }

    private function fillTax(Tax $tax, array $validated): void
    {
        $isGlobal = (bool) ($validated['is_global'] ?? false);

        $tax->name = $validated['name'];
        $tax->code = $validated['code'];
        $tax->branch_id = $isGlobal ? null : ($validated['branch_id'] ?? null);
        $tax->rate = $validated['rate'] ?? 0;
        $tax->type = $validated['type'] ?? 'exclusive';
        $tax->is_compound = (bool) ($validated['is_compound'] ?? false);
        $tax->is_global = $isGlobal;
        $tax->is_active = (bool) ($validated['is_active'] ?? true);
        $tax->order_types = array_values($validated['order_types'] ?? []);
    }

    private function rules(?int $taxId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taxes', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($taxId),
            ],
            'branch_id' => [
                Rule::requiredIf(fn () => !request()->boolean('is_global')),
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'rate' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(array_keys(Tax::TYPES))],
            'is_compound' => ['nullable', 'boolean'],
            'is_global' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'order_types' => ['nullable', 'array'],
            'order_types.*' => ['string', Rule::in(array_keys(Tax::ORDER_TYPES))],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Tax name is required.',
            'code.required' => 'Tax code is required.',
            'code.unique' => 'This tax code already exists.',
            'branch_id.required' => 'Please select a branch or enable global.',
            'rate.required' => 'Rate is required.',
            'type.required' => 'Please select a tax type.',
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