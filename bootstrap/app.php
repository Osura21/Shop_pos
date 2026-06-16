<?php

use App\Http\Middleware\InertiaMultiVendorRoot;
use App\Http\Middleware\InertiaSuperAdminRoot;
use App\Http\Middleware\InertiaVendorAdminRoot;
use App\Http\Middleware\SetSessionPerContext;
use App\Http\Middleware\EnsureVendorPanelSubscriptionIsActive;
use App\Http\Middleware\ApplyVendorMailSettings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('web', SetSessionPerContext::class);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'inertia.superadmin' => InertiaSuperAdminRoot::class,
            'inertia.multivendor' => InertiaMultiVendorRoot::class,
            'inertia.vendoradmin' => InertiaVendorAdminRoot::class,
            'vendor.subscription.active' => EnsureVendorPanelSubscriptionIsActive::class,
            'vendor.mail.settings' => ApplyVendorMailSettings::class,

            // Spatie Permission middleware
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($request->is('vendor-admin') || $request->is('vendor-admin/*')) {
                return route('vendor.login');
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return route('superadmin.login');
            }

            return \Illuminate\Support\Facades\Route::has('login')
                ? route('login')
                : route('superadmin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
