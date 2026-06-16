<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Models\LoyaltyGift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class LoyaltyGiftController extends BaseLoyaltyController
{
    public function index()
    {
        return Inertia::render('VendorAdmin/Loyalty/Gifts/Index');
    }

    public function getData(Request $request)
    {
        $rows = LoyaltyGift::query()
            ->with(['customer:id,name', 'program:id,name', 'reward:id,name,type'])
            ->where('tenant_id', $this->tenantId())
            ->select('loyalty_gifts.*');

        return DataTables::of($rows)
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if (filled($search)) {
                    $query->where(function ($query) use ($search) {
                        $query->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('reward', fn ($r) => $r->where('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->addColumn('customer_name', fn ($row) => $row->customer?->name ?? '-')
            ->addColumn('program_name', fn ($row) => $row->program?->name ?? '-')
            ->addColumn('reward_name', fn ($row) => $row->reward?->name ?? '-')
            ->addColumn('type_badge', fn ($row) => '<span class="badge rounded-pill bg-info-subtle text-info">' . e(str($row->type)->replace('_', ' ')->title()) . '</span>')
            ->addColumn('status_badge', fn ($row) => $this->statusBadge($row->status))
            ->editColumn('points_spent', fn ($row) => number_format((int) $row->points_spent) . ' Pts')
            ->editColumn('valid_until', fn ($row) => optional($row->valid_until)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('used_at', fn ($row) => optional($row->used_at)?->format('Y-m-d h:i A') ?: '-')
            ->editColumn('created_at', fn ($row) => optional($row->created_at)?->format('Y-m-d h:i A') ?: '-')
            ->rawColumns(['type_badge', 'status_badge'])
            ->make(true);
    }

    private function statusBadge(string $status): string
    {
        $classes = [
            'available' => 'bg-success-subtle text-success',
            'used' => 'bg-info-subtle text-info',
            'expired' => 'bg-danger-subtle text-danger',
        ];

        return '<span class="badge rounded-pill ' . ($classes[$status] ?? 'bg-secondary-subtle text-secondary') . '">' . e(ucfirst($status)) . '</span>';
    }
}
