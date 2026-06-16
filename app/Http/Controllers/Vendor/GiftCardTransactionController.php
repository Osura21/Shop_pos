<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\GiftCardTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class GiftCardTransactionController extends Controller
{
    public function index()
    {
        return Inertia::render('VendorAdmin/GiftCards/Transactions/Index');
    }

    public function getData(Request $request)
    {
        $transactions = GiftCardTransaction::query()
            ->with(['card:id,code', 'branch:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->select('gift_card_transactions.*');

        return DataTables::of($transactions)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (!filled($search)) {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('card', fn ($c) => $c->where('code', 'like', "%{$search}%"))
                        ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                });
            })
            ->addColumn('gift_card_code', fn ($row) => $row->card?->code ?? '-')
            ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
            ->addColumn('type_badge', fn ($row) => $this->typeBadge($row->type))
            ->addColumn('currency_label', fn ($row) => $row->currency_code ?: '-')
            ->editColumn('amount', fn ($row) => number_format((float) $row->amount, 3))
            ->editColumn('balance_before', fn ($row) => number_format((float) $row->balance_before, 3))
            ->editColumn('balance_after', fn ($row) => number_format((float) $row->balance_after, 3))
            ->editColumn('occurred_at', fn ($row) => optional($row->occurred_at)?->format('Y-m-d h:i A') ?: optional($row->created_at)?->format('Y-m-d h:i A'))
            ->rawColumns(['type_badge'])
            ->make(true);
    }

    private function typeBadge(string $type): string
    {
        $classes = [
            'purchase' => 'bg-secondary-subtle text-secondary',
            'redeem' => 'bg-danger-subtle text-danger',
            'refund' => 'bg-warning-subtle text-warning',
            'recharge' => 'bg-success-subtle text-success',
            'adjustment' => 'bg-info-subtle text-info',
            'expired' => 'bg-dark-subtle text-dark',
        ];

        return '<span class="badge rounded-pill ' . ($classes[$type] ?? 'bg-secondary-subtle text-secondary') . '">' . e(ucfirst($type)) . '</span>';
    }

    private function tenantId(): int
    {
        return (int) auth('vendor')->user()->tenant_id;
    }
}
