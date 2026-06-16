<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\LoyaltyCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyCustomerController extends BaseLoyaltyController
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Loyalty/Customers/Index');
    }

    public function getData(Request $request)
    {
        $rows = LoyaltyCustomer::query()
            ->with(['customer:id,name', 'program:id,name', 'tier:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->select('loyalty_customers.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (filled($search)) {
                    $query->where(function ($query) use ($search) {
                        $query->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('program', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('tier', fn ($t) => $t->where('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->addColumn('customer_name', fn ($row) => $row->customer?->name ?? '-')
            ->addColumn('program_name', fn ($row) => $row->program?->name ?? '-')
            ->addColumn('tier_name', fn ($row) => $row->tier?->name ?? '-')
            ->editColumn('last_earned_at', fn ($row) => optional($row->last_earned_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('last_redeemed_at', fn ($row) => optional($row->last_redeemed_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->make(true);
    }
}
