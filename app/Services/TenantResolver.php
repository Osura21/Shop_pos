<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;

class TenantResolver
{
    public function resolveByHost(string $host): ?Tenant
    {
        $domain = Domain::where('domain', $host)->first();

        return $domain?->tenant;
    }
}

