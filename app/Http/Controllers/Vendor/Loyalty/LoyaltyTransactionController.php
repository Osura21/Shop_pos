<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\LoyaltyTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyTransactionController extends BaseLoyaltyController
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Loyalty/Transactions/Index');
    }

    public function getData(Request $request)
    {
        $rows = LoyaltyTransaction::query()
            ->with(['customer:id,name', 'program:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->select('loyalty_transactions.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (filled($search)) {
                    $query->where(function ($query) use ($search) {
                        $query->where('description', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->addColumn('customer_name', fn ($row) => $row->customer?->name ?? '-')
            ->addColumn('type_badge', fn ($row) => $this->typeBadge($row->type))
            ->editColumn('points', fn ($row) => number_format((int) abs($row->points)) . ' Pts')
            ->editColumn('amount', fn ($row) => $row->amount !== null ? ($this->currencySymbol($row->currency_code ?: $this->baseCurrencyCode()) . ' ' . $this->money($row->amount)) : '-')
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['type_badge'])
            ->make(true);
    }

    private function typeBadge(string $type): string
    {
        $classes = [
            'earn' => 'bg-success-subtle text-success',
            'redeem' => 'bg-warning-subtle text-warning',
            'adjust' => 'bg-info-subtle text-info',
            'expire' => 'bg-danger-subtle text-danger',
        ];

        return '<span class="badge rounded-pill ' . ($classes[$type] ?? 'bg-secondary-subtle text-secondary') . '">' . e(ucfirst($type)) . '</span>';
    }
}
