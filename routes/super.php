<?php

use App\Http\Controllers\SuperAdmin\AdminController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SuperAdminLoginController;
use App\Http\Controllers\SuperAdmin\VendorController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\SuperAdmin\StaffController;
use App\Http\Controllers\SuperAdmin\MailSettingController;
use App\Http\Controllers\SuperAdmin\FoodCategoryController;

use App\Http\Controllers\SuperAdmin\VehicleTypeController;
use App\Http\Controllers\SuperAdmin\VehicleBrandController;
use App\Http\Controllers\SuperAdmin\VehicleModelController;
use App\Http\Controllers\SuperAdmin\VehicleController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\CustomerController;
use App\Http\Controllers\SuperAdmin\RequestVendorController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\SeoFooterLinkController;
use App\Http\Controllers\SuperAdmin\VendorSubscriptionPlanController;

Route::prefix('admin')->group(function () {

    Route::get('/login', [SuperAdminLoginController::class, 'show'])->name('superadmin.login');
    Route::post('/login', [SuperAdminLoginController::class, 'login'])->name('superadmin.login.submit');
    Route::post('/superadmin/logout', [SuperAdminLoginController::class, 'logout'])->name('superadmin.logout');

    Route::middleware(['auth:superadmin', 'inertia.superadmin'])->group(function () {

        Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('superadmin.dashboard.data');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('superadmin.dashboard');

        Route::patch('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [AdminController::class, 'updatePassword'])->name('password.update');

        Route::get('vendors/getdata', [VendorController::class, 'getData'])->name('vendors.getdata');
        Route::get('vendors/locations', [VendorController::class, 'locations'])->name('vendors.locations');
        Route::get('vendors/location-details', [VendorController::class, 'locationDetails'])->name('vendors.location-details');
        Route::patch('vendors/{vendor}/subscription', [VendorController::class, 'updateSubscription'])->name('vendors.updateSubscription');
        Route::patch('vendors/{vendor}/profile-details', [VendorController::class, 'updateProfileDetails'])->name('vendors.updateProfileDetails');
        Route::patch('vendors/{vendor}/toggle-status', [VendorController::class, 'toggleStatus'])->name('vendors.toggleStatus');
        Route::get('vendors/{vendor}/impersonate', [VendorController::class, 'impersonate'])->name('vendors.impersonate');
        Route::resource('vendors', VendorController::class);

        Route::get('requested-vendors/getdata', [RequestVendorController::class, 'getData'])->name('requested-vendors.getdata');
        Route::get('requested-vendors', [RequestVendorController::class, 'index'])->name('requested-vendors.index');
        Route::get('requested-vendors/{requestedVendor}', [RequestVendorController::class, 'show'])->name('requested-vendors.show');
        Route::post('requested-vendors/{requestedVendor}/approve', [RequestVendorController::class, 'approve'])->name('requested-vendors.approve');
        Route::post('requested-vendors/{requestedVendor}/reject', [RequestVendorController::class, 'reject'])->name('requested-vendors.reject');

        Route::get('food-categories/getdata', [FoodCategoryController::class, 'getData'])->name('food-categories.getdata');
        Route::resource('food-categories', FoodCategoryController::class)->except(['show']);

        Route::get('seo-footer-links/getdata', [SeoFooterLinkController::class, 'getData'])->name('seo-footer-links.getdata');
        Route::get('seo-footer-links/locations', [SeoFooterLinkController::class, 'locations'])->name('seo-footer-links.locations');
        Route::resource('seo-footer-links', SeoFooterLinkController::class)
            ->parameters(['seo-footer-links' => 'seoFooterLink'])
            ->except(['show']);

        Route::patch('vendor-subscriptions/{vendorSubscription}/toggle-status', [VendorSubscriptionPlanController::class, 'toggleStatus'])
            ->name('vendor-subscriptions.toggle-status');
        Route::patch('vendor-subscriptions/{vendorSubscription}/set-default', [VendorSubscriptionPlanController::class, 'setDefault'])
            ->name('vendor-subscriptions.set-default');
        Route::resource('vendor-subscriptions', VendorSubscriptionPlanController::class)
            ->parameters(['vendor-subscriptions' => 'vendorSubscription'])
            ->except(['show']);

    
                // CUSTOMERS
        Route::get('customers/getdata', [CustomerController::class, 'getData'])->name('customers.getdata');
        Route::get('customers/{customer}/view', [CustomerController::class, 'view'])->name('customers.view');
        Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus');
        Route::patch('/customers/{customer}/subscription', [CustomerController::class, 'updateSubscription'])->name('customers.updateSubscription');
        Route::resource('customers', CustomerController::class)->only(['index', 'destroy']);

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('mail', [MailSettingController::class, 'edit'])->name('mail.edit');
            Route::put('mail', [MailSettingController::class, 'update'])->name('mail.update');
            Route::post('mail/test', [MailSettingController::class, 'test'])->name('mail.test');
            Route::resource('roles', RoleController::class);
            Route::get('staff/getdata', [StaffController::class, 'getData'])->name('staff.getdata');
            Route::resource('staff', StaffController::class);
            Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');
            Route::post('staff/{staff}/status', [StaffController::class, 'toggleStatus'])->name('staff.status.toggle');
        });

     
    });

    
});
