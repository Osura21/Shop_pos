<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Supplier;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Inventory/Supplier/Index');
    }

    public function getData(Request $request)
    {
        $suppliers = Supplier::query()
            ->with('branch:id,name')
            ->where('tenant_id', $this->tenantId())
            ->select('suppliers.*');

        return DataTables::of($suppliers)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    });
                }
            })
            ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('updated_at', fn ($row) => optional($row->updated_at)?->format('Y-m-d h:i A') ?: '-')
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Inventory/Supplier/CreateUpdate', [
            'supplier' => null,
            'branches' => $this->branches(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            DB::beginTransaction();

            $supplier = new Supplier;
            $supplier->tenant_id = $this->tenantId();
            $this->fillSupplier($supplier, $validated);
            $supplier->save();

            DB::commit();

            return redirect()->route('vendor.suppliers.index')->with('success', 'Supplier created successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Supplier store query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);

            return back()->withInput()->withErrors(['general' => 'Unable to save supplier.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Supplier store failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);

            return back()->withInput()->withErrors(['general' => 'Something went wrong while creating the supplier.']);
        }
    }

    public function edit($id)
    {
        $supplier = Supplier::where('tenant_id', $this->tenantId())->findOrFail($id);

        return Inertia::render('VendorAdmin/Inventory/Supplier/CreateUpdate', [
            'supplier' => $supplier,
            'branches' => $this->branches(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::where('tenant_id', $this->tenantId())->findOrFail($id);
        $validated = $request->validate($this->rules($supplier->id), $this->messages());

        try {
            DB::beginTransaction();

            $this->fillSupplier($supplier, $validated);
            $supplier->save();

            DB::commit();

            return redirect()->route('vendor.suppliers.index')->with('success', 'Supplier updated successfully.');
        } catch (QueryException $ex) {
            DB::rollBack();
            Log::error('Supplier update query failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);

            return back()->withInput()->withErrors(['general' => 'Unable to update supplier.']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Supplier update failed', ['message' => $ex->getMessage(), 'payload' => $request->all()]);

            return back()->withInput()->withErrors(['general' => 'Something went wrong while updating the supplier.']);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = Supplier::where('tenant_id', $this->tenantId())->findOrFail($id);
            $supplier->delete();

            return redirect()->route('vendor.suppliers.index')->with('success', 'Supplier deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Supplier delete failed', ['message' => $ex->getMessage()]);

            return back()->withErrors(['general' => 'Unable to delete supplier.']);
        }
    }

    private function fillSupplier(Supplier $supplier, array $validated): void
    {
        $supplier->branch_id = $validated['branch_id'] ?? null;
        $supplier->name = $validated['name'];
        $supplier->email = $validated['email'] ?? null;
        $supplier->phone = $validated['phone'] ?? null;
        $supplier->address = $validated['address'] ?? null;
        $supplier->is_active = (bool) ($validated['is_active'] ?? true);
    }

    private function rules($supplierId = null): array
    {
        return [
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $this->tenantId())),
            ],
            'name' => ['required', 'string', 'max:255', 'not_regex:/^[0-9]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'min:9', 'max:30', 'regex:/^[0-9]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Supplier name is required.',
            'name.not_regex' => 'The supplier name cannot contain only numbers.',
            'phone.regex' => 'The phone number must contain only numeric values.',
            'phone.min' => 'The phone number must be more than 8 digits.',
            'phone.max' => 'The phone number must be less than 30 digits.',
            'email.email' => 'Please enter a valid email address.',
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
