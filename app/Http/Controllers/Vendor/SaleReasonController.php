<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\SaleReason;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SaleReasonController extends Controller
{
    use ResolvesTenantContext;

    private const TYPE_OPTIONS = [
        'refund'  => 'Refund',
        'cancel'  => 'Cancel',
        'payment' => 'Payment',
        'other'   => 'Other',
    ];

    public function index()
    {
        return Inertia::render('VendorAdmin/Sales/Reasons/Index');
    }

    public function getData(Request $request)
    {
        $table = (new SaleReason())->getTable();

        $reasons = SaleReason::query()
            ->where("{$table}.tenant_id", $this->tenantId())
            ->select("{$table}.*");

        return DataTables::of($reasons)
            ->filter(function ($query) use ($request, $table) {
                $search = trim((string) $request->input('search.value', ''));

                if ($search === '') {
                    return;
                }

                $query->where(function ($q) use ($search, $table) {
                    $q->where("{$table}.name", 'like', "%{$search}%")
                        ->orWhere("{$table}.type", 'like', "%{$search}%");
                });
            })
            ->addColumn('type_label', function ($row) {
                return $this->pretty($row->type ?: '-');
            })
            ->addColumn('type_badge', function ($row) {
                return $this->badge(
                    $this->pretty($row->type ?: '-'),
                    $this->typeVariant($row->type)
                );
            })
            ->addColumn('activation_label', function ($row) {
                return (int) $row->is_active === 1 ? 'Active' : 'Inactive';
            })
            ->addColumn('activation_badge', function ($row) {
                $active = (int) $row->is_active === 1;

                return $this->badge(
                    $active ? 'Active' : 'Inactive',
                    $active ? 'success' : 'danger'
                );
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->editColumn('updated_at', function ($row) {
                return optional($row->updated_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->rawColumns([
                'type_badge',
                'activation_badge',
            ])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Sales/Reasons/CreateUpdate', [
            'reason' => null,
            'typeOptions' => self::TYPE_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            SaleReason::create([
                'tenant_id' => $this->tenantId(),
                'name' => $validated['name'],
                'type' => $validated['type'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            return redirect()
                ->route('vendor.sales.reasons.index')
                ->with('success', 'Reason created successfully.');
        } catch (Exception $ex) {
            Log::error('Sale reason store failed', [
                'message' => $ex->getMessage(),
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while creating the reason.',
                ]);
        }
    }

    public function edit(SaleReason $reason)
    {
        abort_unless((int) $reason->tenant_id === (int) $this->tenantId(), 404);

        return Inertia::render('VendorAdmin/Sales/Reasons/CreateUpdate', [
            'reason' => $reason,
            'typeOptions' => self::TYPE_OPTIONS,
        ]);
    }

    public function update(Request $request, SaleReason $reason)
    {
        abort_unless((int) $reason->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate($this->rules($reason->id), $this->messages());

        try {
            $reason->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            return redirect()
                ->route('vendor.sales.reasons.index')
                ->with('success', 'Reason updated successfully.');
        } catch (Exception $ex) {
            Log::error('Sale reason update failed', [
                'message' => $ex->getMessage(),
                'reason_id' => $reason->id,
                'payload' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while updating the reason.',
                ]);
        }
    }

    public function destroy(SaleReason $reason)
    {
        abort_unless((int) $reason->tenant_id === (int) $this->tenantId(), 404);

        try {
            $reason->delete();

            return redirect()
                ->route('vendor.sales.reasons.index')
                ->with('success', 'Reason deleted successfully.');
        } catch (Exception $ex) {
            Log::error('Sale reason delete failed', [
                'message' => $ex->getMessage(),
                'reason_id' => $reason->id,
            ]);

            return back()->withErrors([
                'general' => 'Unable to delete reason.',
            ]);
        }
    }

    private function rules($reasonId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sale_reasons', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))
                    ->ignore($reasonId),
            ],
            'type' => ['required', Rule::in(array_keys(self::TYPE_OPTIONS))],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Reason name is required.',
            'name.unique' => 'This reason name already exists.',
            'type.required' => 'Reason type is required.',
            'type.in' => 'Please select a valid reason type.',
        ];
    }

    private function pretty($value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }

    private function typeVariant($type): string
    {
        return match ((string) $type) {
            'refund' => 'danger',
            'cancel' => 'warning',
            'payment' => 'info',
            'other' => 'purple',
            default => 'info',
        };
    }

    private function badge(string $label, string $variant = 'info'): string
    {
        $styles = [
            'warning' => 'background:#fff5dc;color:#edae24;border:1px solid rgba(237,174,36,.25);',
            'success' => 'background:#dcfce7;color:#22c55e;border:1px solid rgba(34,197,94,.25);',
            'danger' => 'background:#ffe4e0;color:#ff6b63;border:1px solid rgba(255,107,99,.25);',
            'info' => 'background:#dce8ff;color:#4b83ff;border:1px solid rgba(75,131,255,.25);',
            'purple' => 'background:#efe5ff;color:#9a62ff;border:1px solid rgba(154,98,255,.25);',
            'teal' => 'background:#d7f6f1;color:#14b8a6;border:1px solid rgba(20,184,166,.25);',
            'orange' => 'background:#fde7c9;color:#f59e0b;border:1px solid rgba(245,158,11,.25);',
        ];

        $style = $styles[$variant] ?? $styles['info'];

        return '<span style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:24px;
            padding:0 10px;
            border-radius:8px;
            font-size:12px;
            font-weight:700;
            white-space:nowrap;
            ' . $style . '
        ">' . e($label) . '</span>';
    }
}