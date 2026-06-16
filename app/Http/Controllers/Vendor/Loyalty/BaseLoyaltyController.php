<?php

namespace App\Http\Controllers\Vendor\Loyalty;

use App\Http\Controllers\Concerns\ResolvesTenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTier;
use App\Models\Product;

abstract class BaseLoyaltyController extends Controller
{
    use ResolvesTenantContext;

    protected function programs()
    {
        return LoyaltyProgram::where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    protected function tiers(?int $programId = null)
    {
        return LoyaltyTier::where('tenant_id', $this->tenantId())
            ->when($programId, fn ($query) => $query->where('loyalty_program_id', $programId))
            ->select('id', 'loyalty_program_id', 'name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function branches()
    {
        return Branch::where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    protected function products()
    {
        return Product::where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    protected function days(): array
    {
        return collect(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])
            ->map(fn ($day) => ['value' => $day, 'label' => ucfirst($day)])
            ->all();
    }

    protected function activeBadge(bool $active): string
    {
        return $active
            ? '<span class="badge rounded-pill bg-success-subtle text-success"><i class="bi bi-check-lg"></i> Active</span>'
            : '<span class="badge rounded-pill bg-danger-subtle text-danger"><i class="bi bi-x-lg"></i> Inactive</span>';
    }

    protected function money($value): string
    {
        return number_format((float) $value, 3);
    }
}
