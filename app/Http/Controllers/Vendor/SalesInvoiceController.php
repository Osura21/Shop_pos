<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\PosInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
{
    use ResolvesTenantContext;

    public function index()
    {
        return Inertia::render('VendorAdmin/Sales/Invoices/Index');
    }

    public function getData(Request $request)
    {
        $table = (new PosInvoice())->getTable();

        $invoices = PosInvoice::query()
            ->with(['branch', 'customer'])
            ->where("{$table}.tenant_id", $this->tenantId())
            ->select("{$table}.*");

        return DataTables::of($invoices)
            ->filter(function ($query) use ($request, $table) {
                $search = trim((string) $request->input('search.value', ''));

                if ($search === '') {
                    return;
                }

                $query->where(function ($q) use ($search, $table) {
                    $q->where("{$table}.invoice_no", 'like', "%{$search}%")
                        ->orWhere("{$table}.uuid", 'like', "%{$search}%")
                        ->orWhere("{$table}.seller_name", 'like', "%{$search}%")
                        ->orWhere("{$table}.buyer_name", 'like', "%{$search}%")
                        ->orWhere("{$table}.type", 'like', "%{$search}%")
                        ->orWhere("{$table}.status", 'like', "%{$search}%")
                        ->orWhere("{$table}.pms_posting_status", 'like', "%{$search}%")
                        ->orWhere("{$table}.purpose", 'like', "%{$search}%")
                        ->orWhere("{$table}.kind", 'like', "%{$search}%")
                        ->orWhere("{$table}.currency_code", 'like', "%{$search}%")
                        ->orWhereHas('branch', function ($branchQuery) use ($search) {
                            $branchQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->addColumn('branch_name', function ($row) {
                return optional($row->branch)->name ?: '-';
            })
            ->addColumn('seller_display', function ($row) {
                return $row->seller_name ?: '-';
            })
            ->addColumn('buyer_display', function ($row) {
    return $this->buyerDisplay($row);
})
            ->addColumn('type_label', function ($row) {
                return $this->pretty($row->type ?: '-');
            })
            ->addColumn('type_badge', function ($row) {
                return $this->badge($this->pretty($row->type ?: '-'), 'orange');
            })
            ->addColumn('status_label', function ($row) {
                return $this->pretty($row->status ?: '-');
            })
            ->addColumn('status_badge', function ($row) {
                return $this->badge(
                    $this->pretty($row->status ?: '-'),
                    $this->statusVariant($row->status)
                );
            })
            ->addColumn('pms_posting_badge', function ($row) {
                return $row->pms_posting_status
                    ? $this->badge($this->pretty($row->pms_posting_status), $this->pmsVariant($row->pms_posting_status))
                    : '-';
            })
            ->addColumn('purpose_label', function ($row) {
                return $this->pretty($row->purpose ?: '-');
            })
            ->addColumn('purpose_badge', function ($row) {
                return $this->badge(
                    $this->pretty($row->purpose ?: '-'),
                    $this->purposeVariant($row->purpose)
                );
            })
            ->addColumn('kind_label', function ($row) {
                return $this->pretty($row->kind ?: '-');
            })
            ->addColumn('kind_badge', function ($row) {
                return $this->badge($this->pretty($row->kind ?: '-'), 'info');
            })
            ->addColumn('total_display', function ($row) {
                return $this->currencySymbol($row->currency_code ?: 'LKR') . ' ' . $this->money($row->total);
            })
            ->editColumn('issued_at', function ($row) {
                return optional($row->issued_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->rawColumns([
                'type_badge',
                'status_badge',
                'pms_posting_badge',
                'purpose_badge',
                'kind_badge',
            ])
            ->make(true);
    }

    public function show(PosInvoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === (int) $this->tenantId(), 404);

        $invoice->load([
            'items',
            'branch',
            'customer',
        ]);

        return Inertia::render('VendorAdmin/Sales/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

   public function print(PosInvoice $invoice)
{
    abort_unless((int) $invoice->tenant_id === (int) $this->tenantId(), 404);

    $invoice->load([
        'items',
        'branch',
        'customer',
    ]);

    return view('vendor.sales.invoice-print', [
        'invoice' => $invoice,
        'autoPrint' => true,
    ]);
}

public function download(PosInvoice $invoice)
{
    abort_unless((int) $invoice->tenant_id === (int) $this->tenantId(), 404);

    $invoice->load([
        'items',
        'branch',
        'customer',
    ]);

    $pdf = Pdf::loadView('vendor.sales.invoice-print', [
        'invoice' => $invoice,
        'autoPrint' => false,
    ])->setPaper('a4', 'portrait');

    return $pdf->download(($invoice->invoice_no ?: 'invoice') . '.pdf');
}

private function buyerDisplay($invoice): string
{
    $customerName = optional($invoice->customer)->name;

    if (filled($customerName)) {
        return $customerName;
    }

    if (filled($invoice->buyer_name)) {
        return $invoice->buyer_name;
    }

    return 'Walk-In Customer';
}
    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 3, '.', ',');
    }

    private function pretty($value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }

    private function statusVariant($status): string
    {
        return match ((string) $status) {
            'paid', 'completed', 'active', 'issued' => 'success',
            'draft', 'pending' => 'warning',
            'cancelled', 'void', 'refunded' => 'danger',
            default => 'success',
        };
    }

    private function purposeVariant($purpose): string
    {
        return match ((string) $purpose) {
            'sale', 'sales', 'standard' => 'success',
            'refund', 'return' => 'danger',
            'exchange' => 'purple',
            default => 'success',
        };
    }

    private function pmsVariant($status): string
    {
        return match ((string) $status) {
            'posted' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
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
