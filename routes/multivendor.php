

<?php

use App\Http\Controllers\MultiVendor\AdController;
use App\Http\Controllers\MultiVendor\AuthController;
use App\Http\Controllers\MultiVendor\CustomerProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MultiVendor\HomeController;
use App\Http\Controllers\MultiVendor\PageController;
use App\Http\Controllers\MultiVendor\StockController;
use App\Http\Controllers\MultiVendor\CustomerPhoneController;
use App\Http\Controllers\MultiVendor\VendorRegistrationController;

Route::middleware('inertia.multivendor')->group(function () {

    Route::get('/', [HomeController::class, 'home'])->name('multivendor.home');
   
    Route::get('/brands', [HomeController::class, 'brands'])->name('multivendor.brands');
    Route::get('/models', [HomeController::class, 'models'])->name('multivendor.models');
    Route::get('/types', [HomeController::class, 'types'])->name('multivendor.types');
    Route::get('multivendor/vehicles', [HomeController::class, 'vehicles'])->name('multivendor.vehicles');
    Route::get('multivendor/vehicles/brand-new', [HomeController::class, 'brandNewVehicles'])->name('multivendor.vehicles.brand-new');
    Route::get('multivendor/vehicles/used', [HomeController::class, 'usedVehicles'])->name('multivendor.vehicles.used');

    Route::get('/cars', [HomeController::class, 'cars'])->name('multivendor.cars');

    Route::get('/login', [AuthController::class, 'login'])->name('multivendor.login');
    Route::post('/make-login', [AuthController::class, 'makeLogin'])->name('multivendor.make.login');
    Route::get('/register', [AuthController::class, 'register'])->name('multivendor.register');
    Route::post('/make-register', [AuthController::class, 'makeRegister'])->name('multivendor.make.register');

    Route::post('/auth/send-otp', [AuthController::class, 'send'])->name('auth.send-otp');
    Route::post('/auth/verify-otp', [AuthController::class, 'verify'])->name('auth.verify-otp');

    Route::get('/otp/register', [AuthController::class, 'showRegister'])->name('otp.register');
    Route::post('/otp/store-register', [AuthController::class, 'storeRegister'])->name('otp.register.store');


    Route::get('/about-us', [PageController::class, 'about'])->name('multivendor.about');
    Route::get('/contact', [PageController::class, 'contact'])->name('multivendor.contact');
    Route::get('/contact-us', [PageController::class, 'contact'])->name('multivendor.contactus');
    Route::get('/resturents', [PageController::class, 'restaurants'])->name('multivendor.resturents');
    Route::get('/resturents/{filters}', [PageController::class, 'restaurants'])
        ->where('filters', '.*')
        ->name('multivendor.resturents.filters');
    Route::get('/features', [PageController::class, 'features'])->name('multivendor.features');
    Route::get('/pricing', [PageController::class, 'pricing'])->name('multivendor.pricing');
    Route::get('/why-us', [PageController::class, 'whyUs'])->name('multivendor.why-us');

    Route::get('/account', [CustomerProfileController::class, 'index'])->name('customer.account');

    Route::get('/seller/register', [VendorRegistrationController::class, 'index'])->name('seller.register');
    Route::get('/seller/register/locations', [VendorRegistrationController::class, 'locations'])->name('seller.register.locations');
    Route::get('/seller/register/location-details', [VendorRegistrationController::class, 'locationDetails'])->name('seller.register.location-details');
    Route::post('/seller/register/send-otp', [VendorRegistrationController::class, 'sendOtp'])->name('seller.register.send-otp');
    Route::post('/seller/register/verify-otp', [VendorRegistrationController::class, 'verifyOtp'])->name('seller.register.verify-otp');
    Route::post('/seller/register/store', [VendorRegistrationController::class, 'store'])->name('seller.register.store');
    Route::get('/seller/request-status/{slug}', [VendorRegistrationController::class, 'status'])
        ->name('seller.register.status');


    Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);

    Route::get('/auth/facebook/redirect', [AuthController::class, 'facebookRedirect']);
    Route::get('/auth/facebook/callback', [AuthController::class, 'facebookCallback']);



 

});
