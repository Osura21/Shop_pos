<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/product-view', function () {
    return Inertia::render('Themes/modern/Product_view/Index');
})->name('product-view');

Route::domain(config('app.main_domain'))->group(function () {
    require __DIR__.'/super.php';
    require __DIR__.'/multivendor.php';
});

Route::middleware(['tenant', \App\Http\Middleware\ShareTenantWithInertia::class])->group(function () {
    require __DIR__.'/tenant.php';
       require __DIR__.'/vendor.php';
});



