<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PosRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\DataTables;

class PosRegisterController extends Controller
{
    use ResolvesTenantContext;

    public function index(Request $request)
    {
        $search = trim((string) $request->string('search'));

        $registers = PosRegister::query()
            ->with('branch:id,name')
            ->where('tenant_id', $this->tenantId())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('VendorAdmin/POS/Register/Index', [
            'filters' => [
                'search' => $search,
            ],
            'registers' => $registers,
        ]);
    }

    public function getData(Request $request)
    {
        $PosRegisters = PosRegister::query()
            ->with('branch:id,name')
            ->where('tenant_id', $this->tenantId())
            ->select('pos_registers.*');

        return DataTables::of($PosRegisters)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                            ->orWhere('invoice_printer', 'like', "%{$search}%")
                            ->orWhere('bill_printer', 'like', "%{$search}%");
                    });
                }
            })

            ->addColumn('name', fn ($row) => $row->name)
            ->addColumn('code', fn ($row) => $row->code)
            ->addColumn('branch', fn ($row) => $row->branch->name)
            ->addColumn('invoice_printer', fn ($row) => $row->invoice_printer)
            ->addColumn('bill_printer', fn ($row) => $row->bill_printer)

            ->addColumn('status', function ($row) {

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

            ->rawColumns(['status'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/POS/Register/CreateUpdate', [
            'register' => null,
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        PosRegister::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'branch_id' => $validated['branch_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'invoice_printer' => $validated['invoice_printer'] ?? null,
            'bill_printer' => $validated['bill_printer'] ?? null,
            'note' => $validated['note'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => auth('vendor')->id(),
            'updated_by' => auth('vendor')->id(),
        ]);

        return redirect()->route('vendor.pos.registers.index')->with('success', 'POS register created successfully.');
    }

    public function edit(PosRegister $register)
    {
        abort_unless((int) $register->tenant_id === (int) $this->tenantId(), 404);

        return Inertia::render('VendorAdmin/POS/Register/CreateUpdate', [
            'register' => $register,
            'branches' => $this->branchOptions(),
        ]);
    }

    public function update(Request $request, PosRegister $register)
    {
        abort_unless((int) $register->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate($this->rules($register->id));

        $register->update([
            'branch_id' => $validated['branch_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'invoice_printer' => $validated['invoice_printer'] ?? null,
            'bill_printer' => $validated['bill_printer'] ?? null,
            'note' => $validated['note'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'updated_by' => auth('vendor')->id(),
        ]);

        return redirect()->route('vendor.pos.registers.index')->with('success', 'POS register updated successfully.');
    }

    public function destroy(PosRegister $register)
    {
        abort_unless((int) $register->tenant_id === (int) $this->tenantId(), 404);

        if ($register->sessions()->where('status', 'open')->exists()) {
            return back()->withErrors([
                'general' => 'You cannot delete a register with an open session.',
            ]);
        }

        $register->delete();

        return redirect()->route('vendor.pos.registers.index')->with('success', 'POS register deleted successfully.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pos_registers', 'code')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($ignoreId),
            ],
            'invoice_printer' => ['nullable', 'string', 'max:255'],
            'bill_printer' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function branchOptions()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
