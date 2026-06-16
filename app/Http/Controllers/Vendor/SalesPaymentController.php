<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Models\PosTransactionPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SalesPaymentController extends Controller
{
    use ResolvesTenantContext;

    public function index()
    {
        return Inertia::render('VendorAdmin/Sales/Payments/Index');
    }

    public function getData(Request $request)
    {
        $table = (new PosTransactionPayment())->getTable();

        $payments = PosTransactionPayment::query()
            ->with([
                'transaction',
                'transaction.session.register.branch',
            ])
            ->whereHas('transaction.session', function ($query) {
                $query->where('tenant_id', $this->tenantId());
            })
            ->select("{$table}.*");

        return DataTables::of($payments)
            ->filter(function ($query) use ($request, $table) {
                $search = trim((string) $request->input('search.value', ''));

                if ($search === '') {
                    return;
                }

                $query->where(function ($q) use ($search, $table) {
                    $q->where("{$table}.payment_method", 'like', "%{$search}%")
                        ->orWhere("{$table}.currency_code", 'like', "%{$search}%")
                        ->orWhere("{$table}.amount", 'like', "%{$search}%")
                        ->orWhereHas('transaction', function ($transactionQuery) use ($search) {
                            $transactionQuery
                                ->where('order_no', 'like', "%{$search}%")
                                ->orWhere('cashier_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('transaction.session.register.branch', function ($branchQuery) use ($search) {
                            $branchQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->addColumn('order_no', function ($row) {
                return optional($row->transaction)->order_no ?: 'ORD-' . $row->id;
            })
            ->addColumn('branch_name', function ($row) {
                return optional(optional(optional(optional($row->transaction)->session)->register)->branch)->name ?: '-';
            })
            ->addColumn('cashier_name', function ($row) {
                return optional($row->transaction)->cashier_name ?: 'System';
            })
            ->addColumn('method_label', function ($row) {
                return $this->pretty($row->payment_method ?: 'cash');
            })
            ->addColumn('method_badge', function ($row) {
                return $this->badge(
                    $this->pretty($row->payment_method ?: 'cash'),
                    $this->methodVariant($row->payment_method)
                );
            })
            ->addColumn('amount_display', function ($row) {
                return $this->currencySymbol($row->currency_code ?: 'LKR') . ' ' . $this->money($row->amount);
            })
            ->editColumn('created_at', function ($row) {
                return optional($row->created_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->editColumn('updated_at', function ($row) {
                return optional($row->updated_at)?->copy()->timezone('UTC')->format('Y-m-d h:i A') ?: '-';
            })
            ->rawColumns(['method_badge'])
            ->make(true);
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 3, '.', ',');
    }

    private function pretty($value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }

    private function methodVariant($method): string
    {
        return match ((string) $method) {
            'cash' => 'success',
            'card' => 'info',
            'credit' => 'purple',
            'split' => 'purple',
            'gift_card' => 'orange',
            'bank_transfer' => 'teal',
            'mobile_wallet' => 'warning',
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
