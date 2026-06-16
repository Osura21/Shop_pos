<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorSites\ThemeHomeController;
use App\Http\Controllers\VendorSites\ThemeStockController;
use App\Http\Controllers\VendorSites\ThemeAboutUsController;
use App\Http\Controllers\VendorSites\ThemeContactUsController;
use App\Http\Controllers\VendorSites\ThemeInquiryController;

// Route::get('/', [ThemeHomeController::class, 'home'])->name('vendorsite.home');

Route::get('/', function () {
    return redirect()->route('vendor.login');
});

Route::get('/about', [ThemeAboutUsController::class, 'index'])->name('vendorsite.about');
Route::get('/about-us', [ThemeAboutUsController::class, 'index'])->name('vendorsite.aboutus');
Route::get('/aboutus', [ThemeAboutUsController::class, 'index']);

Route::get('/contact', [ThemeContactUsController::class, 'index'])->name('vendorsite.contact');
Route::get('/contact-us', [ThemeContactUsController::class, 'index'])->name('vendorsite.contactus');
Route::get('/contactus', [ThemeContactUsController::class, 'index']);
Route::post('/contact-inquiries', [ThemeInquiryController::class, 'store'])->name('vendorsite.inquiries.store');

Route::get('/brands', [ThemeHomeController::class, 'brands'])->name('vendorsite.brands');
Route::get('/models', [ThemeHomeController::class, 'models'])->name('vendorsite.models');
Route::get('/types', [ThemeHomeController::class, 'types'])->name('vendorsite.types');

Route::get('/vehicles/featured', [ThemeHomeController::class, 'featuredVehicles'])->name('vendorsite.vehicles.featured');
Route::get('/vehicles/brand-new', [ThemeHomeController::class, 'brandNewVehicles'])->name('vendorsite.vehicles.brand-new');
Route::get('/vehicles/used', [ThemeHomeController::class, 'usedVehicles'])->name('vendorsite.vehicles.used');

Route::get('/vehicles', [ThemeStockController::class, 'index'])->name('vendorsite.vehicles.index');
Route::get('/vehicles-count', [ThemeStockController::class, 'count'])->name('vendorsite.vehicles.count');
Route::get('/ad/{slug}', [ThemeStockController::class, 'product'])->name('vendorsite.product');