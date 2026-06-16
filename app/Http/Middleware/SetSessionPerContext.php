<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SetSessionPerContext
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $mainDomain = config('app.main_domain');

        $path = $request->path();

        // Defaults → CUSTOMER
        $cookie = 'session_vendorsite';
        $sessionPath = '/';
        $domain = null;

        // 🔥 SUPER ADMIN (veyogo.test/admin)
        if ($host === $mainDomain && str_starts_with($path, 'admin')) {
            $cookie = 'session_superadmin';
            $sessionPath = '/admin';
            $domain = '.' . $mainDomain;
        }

        // 🔥 VENDOR ADMIN (subdomain OR custom domain)
        elseif (str_starts_with($path, 'vendor-admin')) {
            $cookie = 'session_vendor_' . Str::slug($host, '_');
            $sessionPath = '/vendor-admin';
            $domain = null;
        }

        // 🔥 CUSTOMER (veyogo.test)
        else {
            $cookie = 'session_vendorsite';
            $sessionPath = '/';
            $domain = '.' . $mainDomain;
        }

        config([
            'session.cookie' => $cookie,
            'session.path' => $sessionPath,
            'session.domain' => $domain,
        ]);

        return $next($request);
    }
}
