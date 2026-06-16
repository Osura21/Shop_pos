<?php

use App\Support\Tenancy\TenantContext;

if (!function_exists('tenant')) {
    function tenant()
    {
        return app(TenantContext::class)->get();
    }
}
