<?php

use App\Http\Controllers\Vendor\VendorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VendorLoginController;
use App\Http\Controllers\Vendor\BranchController;
use App\Http\Controllers\Vendor\UserController;
use App\Http\Controllers\Vendor\CustomerController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\RoleController;
use App\Http\Controllers\Vendor\MenuController;
use App\Http\Controllers\Vendor\OnlineMenuController;
use App\Http\Controllers\Vendor\CategoryController;
use App\Http\Controllers\Vendor\UnitController;
use App\Http\Controllers\Vendor\SupplierController;
use App\Http\Controllers\Vendor\IngredientController;
use App\Http\Controllers\Vendor\StockMovementController;
use App\Http\Controllers\Vendor\StockManagementController;
use App\Http\Controllers\Vendor\PurchaseController;
use App\Http\Controllers\Vendor\InventoryAnalyticsController;
use App\Http\Controllers\Vendor\SettingController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\TaxController;
use App\Http\Controllers\Vendor\OptionController;
use App\Http\Controllers\Vendor\FloorController;
use App\Http\Controllers\Vendor\ZoneController;
use App\Http\Controllers\Vendor\DiningTableController;
use App\Http\Controllers\Vendor\TableMergeController;
use App\Http\Controllers\Vendor\PosViewerController;
use App\Http\Controllers\Vendor\PosRegisterController;
use App\Http\Controllers\Vendor\PosSessionController;
use App\Http\Controllers\Vendor\PosCashMovementController;
use App\Http\Controllers\Vendor\KitchenViewerController;
use App\Http\Controllers\Vendor\KitchenAlertSettingController;
use App\Http\Controllers\Vendor\PmsIntegrationController;
use App\Http\Controllers\Vendor\MailSettingController;
use App\Http\Controllers\Vendor\ReportController;
use App\Http\Controllers\Vendor\SalesOrderController;
use App\Http\Controllers\Vendor\SalesInvoiceController;
use App\Http\Controllers\Vendor\SalesPaymentController;
use App\Http\Controllers\Vendor\SaleReasonController;
use App\Http\Controllers\Vendor\GiftCardAnalyticsController;
use App\Http\Controllers\Vendor\GiftCardBatchController;
use App\Http\Controllers\Vendor\GiftCardController;
use App\Http\Controllers\Vendor\GiftCardTransactionController;
use App\Http\Controllers\Vendor\ActivityLogController;
use App\Http\Controllers\Vendor\PromotionDiscountController;
use App\Http\Controllers\Vendor\PromotionVoucherController;
use App\Http\Controllers\Vendor\Loyalty\LoyaltyCustomerController;
use App\Http\Controllers\Vendor\Loyalty\LoyaltyGiftController;
use App\Http\Controllers\Vendor\Loyalty\LoyaltyProgramController;
use App\Http\Controllers\Vendor\Loyalty\LoyaltyPromotionController;
use App\Http\Controllers\Vendor\Loyalty\LoyaltyRewardController;
use App\Http\Controllers\Vendor\Loyalty\LoyaltyTierController;
use App\Http\Controllers\Vendor\Loyalty\LoyaltyTransactionController;
use App\Http\Controllers\Vendor\Loyalty\PosLoyaltyController;
use App\Http\Controllers\Auth\VendorImpersonationController;

Route::prefix('vendor-admin')->name('vendor.')->group(function () {

    Route::get('/login', [VendorLoginController::class, 'show'])->name('login');
    Route::post('/login', [VendorLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [VendorLoginController::class, 'logout'])->name('logout');
    Route::get('/impersonate/{token}', [VendorImpersonationController::class, 'accept'])->name('impersonate.accept');

    Route::middleware(['auth:vendor', 'vendor.subscription.active', 'vendor.mail.settings', 'inertia.vendoradmin'])->group(function () {
        Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('activities')
            ->name('activities.')
            ->group(function () {
                Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
                Route::get('/activity-logs/getdata', [ActivityLogController::class, 'getData'])->name('activity-logs.getdata');
                Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
                Route::get('/authentication-logs', [ActivityLogController::class, 'authenticationIndex'])->name('authentication-logs.index');
                Route::get('/authentication-logs/getdata', [ActivityLogController::class, 'authenticationData'])->name('authentication-logs.getdata');
                Route::get('/authentication-logs/{authenticationLog}', [ActivityLogController::class, 'authenticationShow'])->name('authentication-logs.show');
            });

       Route::prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
    });


        Route::get('/profile', [VendorController::class, 'profile'])->name('profile');
        Route::patch('/profile', [VendorController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [VendorController::class, 'updatePassword'])->name('password.update');

        Route::resource('roles', RoleController::class)
            ->except(['show'])
            ->names('roles');

        Route::prefix('branches')
            ->name('branches.')
            ->group(function () {
                Route::get('/', [BranchController::class, 'index'])->name('index');
                Route::get('/getdata', [BranchController::class, 'getData'])->name('getdata');
                Route::get('/create', [BranchController::class, 'create'])->name('create');
                Route::post('/', [BranchController::class, 'store'])->name('store');
                Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
                Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
                Route::delete('/{branch}', [BranchController::class, 'destroy'])->name('destroy');
            });
        Route::prefix('customers')
            ->name('customers.')
            ->group(function () {
                Route::get('/', [CustomerController::class, 'index'])->name('index');
                Route::get('/getdata', [CustomerController::class, 'getData'])->name('getdata');
                Route::get('/create', [CustomerController::class, 'create'])->name('create');
                Route::post('/', [CustomerController::class, 'store'])->name('store');
                Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
                Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
                Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('menus')
            ->name('menus.')
            ->group(function () {
                Route::get('/', [MenuController::class, 'index'])->name('index');
                Route::get('/getdata', [MenuController::class, 'getData'])->name('getdata');
                Route::get('/create', [MenuController::class, 'create'])->name('create');
                Route::post('/', [MenuController::class, 'store'])->name('store');
                Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
                Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
                Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('online-menus')
            ->name('online-menus.')
            ->group(function () {
                Route::get('/', [OnlineMenuController::class, 'index'])->name('index');
                Route::get('/getdata', [OnlineMenuController::class, 'getData'])->name('getdata');
                Route::get('/create', [OnlineMenuController::class, 'create'])->name('create');
                Route::post('/', [OnlineMenuController::class, 'store'])->name('store');
                Route::get('/{onlineMenu}/edit', [OnlineMenuController::class, 'edit'])->name('edit');
                Route::put('/{onlineMenu}', [OnlineMenuController::class, 'update'])->name('update');
                Route::delete('/{onlineMenu}', [OnlineMenuController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('categories')
            ->name('categories.')
            ->group(function () {
                Route::get('/', [CategoryController::class, 'index'])->name('index');
                Route::post('/', [CategoryController::class, 'store'])->name('store');
                Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
                Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
            });
        Route::prefix('units')
            ->name('units.')
            ->group(function () {
                Route::get('/', [UnitController::class, 'index'])->name('index');
                Route::get('/getdata', [UnitController::class, 'getData'])->name('getdata');
                Route::get('/create', [UnitController::class, 'create'])->name('create');
                Route::post('/', [UnitController::class, 'store'])->name('store');
                Route::get('/{unit}/edit', [UnitController::class, 'edit'])->name('edit');
                Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
                Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('suppliers')
            ->name('suppliers.')
            ->group(function () {
                Route::get('/', [SupplierController::class, 'index'])->name('index');
                Route::get('/getdata', [SupplierController::class, 'getData'])->name('getdata');
                Route::get('/create', [SupplierController::class, 'create'])->name('create');
                Route::post('/', [SupplierController::class, 'store'])->name('store');
                Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
                Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
                Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('ingredients')
            ->name('ingredients.')
            ->group(function () {
                Route::get('/', [IngredientController::class, 'index'])->name('index');
                Route::get('/getdata', [IngredientController::class, 'getData'])->name('getdata');
                Route::get('/create', [IngredientController::class, 'create'])->name('create');
                Route::post('/', [IngredientController::class, 'store'])->name('store');
                Route::get('/{ingredient}/edit', [IngredientController::class, 'edit'])->name('edit');
                Route::put('/{ingredient}', [IngredientController::class, 'update'])->name('update');
                Route::delete('/{ingredient}', [IngredientController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('stock-movements')
            ->name('stock-movements.')
            ->group(function () {
                Route::get('/', [StockMovementController::class, 'index'])->name('index');
                Route::get('/getdata', [StockMovementController::class, 'getData'])->name('getdata');
                Route::get('/create', [StockMovementController::class, 'create'])->name('create');
                Route::post('/', [StockMovementController::class, 'store'])->name('store');
                Route::get('/{stockMovement}/edit', [StockMovementController::class, 'edit'])->name('edit');
                Route::put('/{stockMovement}', [StockMovementController::class, 'update'])->name('update');
                Route::delete('/{stockMovement}', [StockMovementController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('stock-management')
            ->name('stock-management.')
            ->group(function () {
                Route::get('/', [StockManagementController::class, 'index'])->name('index');
                Route::get('/getdata', [StockManagementController::class, 'getData'])->name('getdata');
                Route::post('/movements', [StockManagementController::class, 'store'])->name('store');
            });

        Route::prefix('purchases')
            ->name('purchases.')
            ->group(function () {
                Route::get('/', [PurchaseController::class, 'index'])->name('index');
                Route::get('/getdata', [PurchaseController::class, 'getData'])->name('getdata');
                Route::get('/create', [PurchaseController::class, 'create'])->name('create');
                Route::post('/', [PurchaseController::class, 'store'])->name('store');
                Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
                Route::get('/{purchase}/edit', [PurchaseController::class, 'edit'])->name('edit');
                Route::put('/{purchase}', [PurchaseController::class, 'update'])->name('update');
                Route::delete('/{purchase}', [PurchaseController::class, 'destroy'])->name('destroy');
                Route::post('/{purchase}/mark-received', [PurchaseController::class, 'markReceived'])->name('mark-received');
            });

        Route::prefix('products')
            ->name('products.')
            ->group(function () {
                Route::get('/', [ProductController::class, 'index'])->name('index');
                Route::get('/getdata', [ProductController::class, 'getData'])->name('getdata');
                Route::get('/create', [ProductController::class, 'create'])->name('create');
                Route::post('/', [ProductController::class, 'store'])->name('store');
                Route::get('/{product}', [ProductController::class, 'show'])->name('show');
                Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
                Route::put('/{product}', [ProductController::class, 'update'])->name('update');
                Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
                Route::patch('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('toggle-status');
            });

        Route::prefix('taxes')
            ->name('taxes.')
            ->group(function () {
                Route::get('/', [TaxController::class, 'index'])->name('index');
                Route::get('/getdata', [TaxController::class, 'getData'])->name('getdata');
                Route::get('/create', [TaxController::class, 'create'])->name('create');
                Route::post('/', [TaxController::class, 'store'])->name('store');
                Route::get('/{tax}/edit', [TaxController::class, 'edit'])->name('edit');
                Route::put('/{tax}', [TaxController::class, 'update'])->name('update');
                Route::delete('/{tax}', [TaxController::class, 'destroy'])->name('destroy');
                Route::patch('/{tax}/toggle-status', [TaxController::class, 'toggleStatus'])->name('toggle-status');

            });
        Route::prefix('options')
            ->name('options.')
            ->group(function () {
                Route::get('/', [OptionController::class, 'index'])->name('index');
                Route::get('/getdata', [OptionController::class, 'getData'])->name('getdata');
                Route::get('/create', [OptionController::class, 'create'])->name('create');
                Route::post('/', [OptionController::class, 'store'])->name('store');
                Route::get('/{option}/edit', [OptionController::class, 'edit'])->name('edit');
                Route::put('/{option}', [OptionController::class, 'update'])->name('update');
                Route::delete('/{option}', [OptionController::class, 'destroy'])->name('destroy');
                Route::patch('/{option}/toggle-status', [OptionController::class, 'toggleStatus'])->name('toggle-status');

            });
        Route::prefix('floors')
            ->name('floors.')
            ->group(function () {
                Route::get('/', [FloorController::class, 'index'])->name('index');
                Route::get('/getdata', [FloorController::class, 'getData'])->name('getdata');
                Route::get('/create', [FloorController::class, 'create'])->name('create');
                Route::post('/', [FloorController::class, 'store'])->name('store');
                Route::get('/{floor}/edit', [FloorController::class, 'edit'])->name('edit');
                Route::put('/{floor}', [FloorController::class, 'update'])->name('update');
                Route::delete('/{floor}', [FloorController::class, 'destroy'])->name('destroy');
            });
        Route::prefix('users')->name('users.')->group(function () {

            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');

            Route::get('/getdata', [UserController::class, 'getData'])->name('getdata');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('zones')
            ->name('zones.')
            ->group(function () {
                Route::get('/', [ZoneController::class, 'index'])->name('index');
                Route::get('/getdata', [ZoneController::class, 'getData'])->name('getdata');
                Route::get('/create', [ZoneController::class, 'create'])->name('create');
                Route::post('/', [ZoneController::class, 'store'])->name('store');
                Route::get('/{zone}/edit', [ZoneController::class, 'edit'])->name('edit');
                Route::put('/{zone}', [ZoneController::class, 'update'])->name('update');
                Route::delete('/{zone}', [ZoneController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('tables')
            ->name('tables.')
            ->group(function () {
                Route::get('/', [DiningTableController::class, 'index'])->name('index');
                Route::get('/getdata', [DiningTableController::class, 'getData'])->name('getdata');
                Route::get('/create', [DiningTableController::class, 'create'])->name('create');
                Route::post('/', [DiningTableController::class, 'store'])->name('store');
                Route::get('/{table}/edit', [DiningTableController::class, 'edit'])->name('edit');
                Route::put('/{table}', [DiningTableController::class, 'update'])->name('update');
                Route::delete('/{table}', [DiningTableController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('table-merges')
            ->name('table-merges.')
            ->group(function () {
                Route::get('/', [TableMergeController::class, 'index'])->name('index');
                Route::get('/getdata', [TableMergeController::class, 'getData'])->name('getdata');
                Route::get('/create', [TableMergeController::class, 'create'])->name('create');
                Route::post('/', [TableMergeController::class, 'store'])->name('store');
                Route::get('/{tableMerge}/edit', [TableMergeController::class, 'edit'])->name('edit');
                Route::put('/{tableMerge}', [TableMergeController::class, 'update'])->name('update');
                Route::patch('/{tableMerge}/close', [TableMergeController::class, 'close'])->name('close');
                Route::delete('/{tableMerge}', [TableMergeController::class, 'destroy'])->name('destroy');
            });
        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/open', [PosViewerController::class, 'open'])->name('open');
            Route::get('/viewer/{register?}', [PosViewerController::class, 'show'])->name('viewer');

            Route::prefix('registers')->name('registers.')->group(function () {
                Route::get('/', [PosRegisterController::class, 'index'])->name('index');
                Route::get('/getdata', [PosRegisterController::class, 'getData'])->name('getdata');
                Route::get('/create', [PosRegisterController::class, 'create'])->name('create');
                Route::post('/', [PosRegisterController::class, 'store'])->name('store');
                Route::get('/{register}/edit', [PosRegisterController::class, 'edit'])->name('edit');
                Route::put('/{register}', [PosRegisterController::class, 'update'])->name('update');
                Route::delete('/{register}', [PosRegisterController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('sessions')->name('sessions.')->group(function () {
                Route::get('/', [PosSessionController::class, 'index'])->name('index');
                Route::post('/', [PosSessionController::class, 'store'])->name('store');
                Route::get('/getdata', [PosSessionController::class, 'getData'])->name('getdata');
                Route::patch('/{session}/close', [PosSessionController::class, 'close'])->name('close');
            });

            Route::prefix('cash-movements')->name('cash-movements.')->group(function () {
                Route::get('/', [PosCashMovementController::class, 'index'])->name('index');
                Route::get('/getdata', [PosCashMovementController::class, 'getData'])->name('getdata');
                Route::post('/', [PosCashMovementController::class, 'store'])->name('store');
                Route::put('/{movement}', [PosCashMovementController::class, 'update'])->name('update');
                Route::delete('/{movement}', [PosCashMovementController::class, 'destroy'])->name('destroy');
            });

            Route::patch('/sessions/{session}/meta', [PosViewerController::class, 'updateMeta'])->name('meta');
            Route::get('/sessions/{session}/promotions', [PosViewerController::class, 'fetchPromotions'])->name('promotions');
            Route::get('/sessions/{session}/tables', [PosViewerController::class, 'fetchTables'])->name('tables.list');
            Route::post('/sessions/{session}/tables/merge', [PosViewerController::class, 'mergeTables'])->name('tables.merge');
            Route::get('/sessions/{session}/loyalty/summary', [PosLoyaltyController::class, 'summary'])->name('loyalty.summary');
            Route::post('/sessions/{session}/loyalty/redeem', [PosLoyaltyController::class, 'redeem'])->name('loyalty.redeem');
            Route::delete('/sessions/{session}/loyalty/redeem', [PosLoyaltyController::class, 'cancelRedeem'])->name('loyalty.redeem.cancel');
            Route::get('/sessions/{session}/loyalty/gifts', [PosLoyaltyController::class, 'gifts'])->name('loyalty.gifts');
            Route::post('/sessions/{session}/loyalty/gifts/{gift}/apply', [PosLoyaltyController::class, 'applyGift'])->name('loyalty.gifts.apply');
            Route::get('/sessions/{session}/products', [PosViewerController::class, 'fetchProducts'])->name('products');
            Route::post('/sessions/{session}/items', [PosViewerController::class, 'addItem'])->name('add-item');
            Route::patch('/sessions/{session}/items/{itemId}/qty', [PosViewerController::class, 'updateItemQty'])->name('update-item-qty');
            Route::delete('/sessions/{session}/items/{itemId}', [PosViewerController::class, 'removeItem'])->name('remove-item');
            Route::patch('/sessions/{session}/discount', [PosViewerController::class, 'applyDiscount'])->name('discount');
            Route::patch('/sessions/{session}/hold', [PosViewerController::class, 'hold'])->name('hold');
            Route::patch('/sessions/{session}/cancel', [PosViewerController::class, 'cancel'])->name('cancel');
            Route::patch('/sessions/{session}/pay-fire', [PosViewerController::class, 'payFire'])->name('pay-fire');
            Route::patch('/sessions/{session}/send-to-kitchen', [PosViewerController::class, 'sendToKitchen'])
                ->name('send-to-kitchen');
            Route::get('/orders/list', [PosViewerController::class, 'fetchOrders'])->name('orders.list');
            Route::get('/orders/{ticket}/details', [PosViewerController::class, 'fetchOrderDetails'])->name('orders.details');
            Route::get('/orders/{ticket}/pms-payment-status', [PosViewerController::class, 'checkPmsOrderPaymentStatus'])->name('orders.pms-payment-status');

            Route::patch('/orders/{ticket}/confirm', [PosViewerController::class, 'confirmKitchenOrder'])
                ->name('orders.confirm');
            Route::get('/orders/{ticket}/print', [PosViewerController::class, 'printOrder'])
                ->name('orders.print');
            Route::patch('/orders/{ticket}/cancel', [PosViewerController::class, 'cancelKitchenOrder'])->name('orders.cancel-ticket');
            Route::patch('/orders/{ticket}/pay', [PosViewerController::class, 'payKitchenOrder'])->name('orders.pay-ticket');
            Route::patch('/orders/{ticket}/edit', [PosViewerController::class, 'editKitchenOrder'])
                ->name('orders.edit-ticket');

                Route::patch('/orders/{ticket}/waiter', [PosViewerController::class, 'updateTicketWaiter'])
    ->name('orders.waiter');
            Route::post('/sessions/{session}/finalize-payment', [PosViewerController::class, 'finalizePayment'])
                ->name('finalize-payment');
            Route::prefix('kitchen')->name('kitchen.')->group(function () {
                Route::get('/', [KitchenViewerController::class, 'index'])->name('index');
                Route::patch('/items/{item}/start-preparing', [KitchenViewerController::class, 'startItemPreparing'])->name('items.start-preparing');
                Route::patch('/items/{item}/mark-ready', [KitchenViewerController::class, 'markItemReady'])->name('items.mark-ready');
                Route::patch('/items/{item}/undo-status', [KitchenViewerController::class, 'undoItemStatus'])->name('items.undo-status');
                Route::patch('/{ticket}/start-preparing', [KitchenViewerController::class, 'startPreparing'])->name('start-preparing');
                Route::patch('/{ticket}/mark-ready', [KitchenViewerController::class, 'markReady'])->name('mark-ready');
                Route::patch('/{ticket}/mark-served', [KitchenViewerController::class, 'markServed'])->name('mark-served');
                Route::patch('/{ticket}/undo-status', [KitchenViewerController::class, 'undoStatus'])->name('undo-status');
            });
        });
        Route::prefix('gift-cards')->name('gift-cards.')->group(function () {
            Route::get('/analytics', [GiftCardAnalyticsController::class, 'index'])->name('analytics');
            Route::post('/lookup', [GiftCardController::class, 'lookup'])->name('lookup');

            Route::prefix('transactions')->name('transactions.')->group(function () {
                Route::get('/', [GiftCardTransactionController::class, 'index'])->name('index');
                Route::get('/getdata', [GiftCardTransactionController::class, 'getData'])->name('getdata');
            });

            Route::prefix('batches')->name('batches.')->group(function () {
                Route::get('/', [GiftCardBatchController::class, 'index'])->name('index');
                Route::get('/getdata', [GiftCardBatchController::class, 'getData'])->name('getdata');
                Route::get('/create', [GiftCardBatchController::class, 'create'])->name('create');
                Route::post('/', [GiftCardBatchController::class, 'store'])->name('store');
                Route::get('/{batch}/edit', [GiftCardBatchController::class, 'edit'])->name('edit');
                Route::put('/{batch}', [GiftCardBatchController::class, 'update'])->name('update');
                Route::delete('/{batch}', [GiftCardBatchController::class, 'destroy'])->name('destroy');
            });

            Route::get('/', [GiftCardController::class, 'index'])->name('index');
            Route::get('/getdata', [GiftCardController::class, 'getData'])->name('getdata');
            Route::get('/create', [GiftCardController::class, 'create'])->name('create');
            Route::post('/', [GiftCardController::class, 'store'])->name('store');
            Route::get('/{giftCard}/edit', [GiftCardController::class, 'edit'])->name('edit');
            Route::put('/{giftCard}', [GiftCardController::class, 'update'])->name('update');
            Route::delete('/{giftCard}', [GiftCardController::class, 'destroy'])->name('destroy');
        });
        Route::prefix('promotions')->name('promotions.')->group(function () {
            Route::prefix('discounts')->name('discounts.')->group(function () {
                Route::get('/', [PromotionDiscountController::class, 'index'])->name('index');
                Route::get('/getdata', [PromotionDiscountController::class, 'getData'])->name('getdata');
                Route::get('/create', [PromotionDiscountController::class, 'create'])->name('create');
                Route::post('/', [PromotionDiscountController::class, 'store'])->name('store');
                Route::get('/{discount}/edit', [PromotionDiscountController::class, 'edit'])->name('edit');
                Route::put('/{discount}', [PromotionDiscountController::class, 'update'])->name('update');
                Route::delete('/{discount}', [PromotionDiscountController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('vouchers')->name('vouchers.')->group(function () {
                Route::get('/', [PromotionVoucherController::class, 'index'])->name('index');
                Route::get('/getdata', [PromotionVoucherController::class, 'getData'])->name('getdata');
                Route::get('/create', [PromotionVoucherController::class, 'create'])->name('create');
                Route::post('/', [PromotionVoucherController::class, 'store'])->name('store');
                Route::get('/{voucher}/edit', [PromotionVoucherController::class, 'edit'])->name('edit');
                Route::put('/{voucher}', [PromotionVoucherController::class, 'update'])->name('update');
                Route::delete('/{voucher}', [PromotionVoucherController::class, 'destroy'])->name('destroy');
            });
        });
        Route::prefix('loyalty')->name('loyalty.')->group(function () {
            Route::prefix('programs')->name('programs.')->group(function () {
                Route::get('/', [LoyaltyProgramController::class, 'index'])->name('index');
                Route::get('/getdata', [LoyaltyProgramController::class, 'getData'])->name('getdata');
                Route::get('/create', [LoyaltyProgramController::class, 'create'])->name('create');
                Route::post('/', [LoyaltyProgramController::class, 'store'])->name('store');
                Route::get('/{program}/edit', [LoyaltyProgramController::class, 'edit'])->name('edit');
                Route::put('/{program}', [LoyaltyProgramController::class, 'update'])->name('update');
                Route::delete('/{program}', [LoyaltyProgramController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('tiers')->name('tiers.')->group(function () {
                Route::get('/', [LoyaltyTierController::class, 'index'])->name('index');
                Route::get('/getdata', [LoyaltyTierController::class, 'getData'])->name('getdata');
                Route::get('/create', [LoyaltyTierController::class, 'create'])->name('create');
                Route::post('/', [LoyaltyTierController::class, 'store'])->name('store');
                Route::get('/{tier}/edit', [LoyaltyTierController::class, 'edit'])->name('edit');
                Route::put('/{tier}', [LoyaltyTierController::class, 'update'])->name('update');
                Route::delete('/{tier}', [LoyaltyTierController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('rewards')->name('rewards.')->group(function () {
                Route::get('/', [LoyaltyRewardController::class, 'index'])->name('index');
                Route::get('/getdata', [LoyaltyRewardController::class, 'getData'])->name('getdata');
                Route::get('/create', [LoyaltyRewardController::class, 'create'])->name('create');
                Route::post('/', [LoyaltyRewardController::class, 'store'])->name('store');
                Route::get('/{reward}/edit', [LoyaltyRewardController::class, 'edit'])->name('edit');
                Route::put('/{reward}', [LoyaltyRewardController::class, 'update'])->name('update');
                Route::delete('/{reward}', [LoyaltyRewardController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('promotions')->name('promotions.')->group(function () {
                Route::get('/', [LoyaltyPromotionController::class, 'index'])->name('index');
                Route::get('/getdata', [LoyaltyPromotionController::class, 'getData'])->name('getdata');
                Route::get('/create', [LoyaltyPromotionController::class, 'create'])->name('create');
                Route::post('/', [LoyaltyPromotionController::class, 'store'])->name('store');
                Route::get('/{promotion}/edit', [LoyaltyPromotionController::class, 'edit'])->name('edit');
                Route::put('/{promotion}', [LoyaltyPromotionController::class, 'update'])->name('update');
                Route::delete('/{promotion}', [LoyaltyPromotionController::class, 'destroy'])->name('destroy');
            });

            Route::get('/customers', [LoyaltyCustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/getdata', [LoyaltyCustomerController::class, 'getData'])->name('customers.getdata');
            Route::get('/gifts', [LoyaltyGiftController::class, 'index'])->name('gifts.index');
            Route::get('/gifts/getdata', [LoyaltyGiftController::class, 'getData'])->name('gifts.getdata');
            Route::get('/transactions', [LoyaltyTransactionController::class, 'index'])->name('transactions.index');
            Route::get('/transactions/getdata', [LoyaltyTransactionController::class, 'getData'])->name('transactions.getdata');
        });
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/getdata', [SalesOrderController::class, 'getData'])->name('orders.getdata');
            Route::post('/orders/sync-pms-payment-statuses', [SalesOrderController::class, 'syncPmsPaymentStatuses'])->name('orders.sync-pms-payment-statuses');
            Route::get('/orders/{ticket}', [SalesOrderController::class, 'show'])->name('orders.show');
            Route::patch('/orders/{ticket}/cancel', [SalesOrderController::class, 'cancel'])->name('orders.cancel');

            Route::get('/invoices', [SalesInvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/getdata', [SalesInvoiceController::class, 'getData'])->name('invoices.getdata');
            Route::get('/invoices/{invoice}', [SalesInvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/print', [SalesInvoiceController::class, 'print'])->name('invoices.print');
            Route::get('/invoices/{invoice}/download', [SalesInvoiceController::class, 'download'])->name('invoices.download');

            Route::get('/payments', [SalesPaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/getdata', [SalesPaymentController::class, 'getData'])->name('payments.getdata');

            Route::get('/reasons', [SaleReasonController::class, 'index'])->name('reasons.index');
            Route::get('/reasons/getdata', [SaleReasonController::class, 'getData'])->name('reasons.getdata');
            Route::get('/reasons/create', [SaleReasonController::class, 'create'])->name('reasons.create');
            Route::post('/reasons', [SaleReasonController::class, 'store'])->name('reasons.store');
            Route::get('/reasons/{reason}/edit', [SaleReasonController::class, 'edit'])->name('reasons.edit');
            Route::put('/reasons/{reason}', [SaleReasonController::class, 'update'])->name('reasons.update');
            Route::delete('/reasons/{reason}', [SaleReasonController::class, 'destroy'])->name('reasons.destroy');

        });

        Route::prefix('inventory-analytics')
            ->name('inventory-analytics.')
            ->group(function () {
                Route::get('/', [InventoryAnalyticsController::class, 'index'])->name('index');
            });
        Route::prefix('settings')
            ->name('settings.')
            ->group(function () {
                Route::get('/currency', [SettingController::class, 'currency'])->name('currency');
                Route::put('/currency', [SettingController::class, 'updateCurrency'])->name('currency.update');
                Route::get('/logo', [SettingController::class, 'logo'])->name('logo');
                Route::post('/logo', [SettingController::class, 'updateLogo'])->name('logo.update');
                Route::get('/pms', [PmsIntegrationController::class, 'edit'])->name('pms');
                Route::put('/pms', [PmsIntegrationController::class, 'update'])->name('pms.update');
                Route::get('/mail', [MailSettingController::class, 'edit'])->name('mail');
                Route::put('/mail', [MailSettingController::class, 'update'])->name('mail.update');
                Route::post('/mail/test', [MailSettingController::class, 'test'])->name('mail.test');
                Route::get('/mail-recipients', [MailSettingController::class, 'recipients'])->name('mail.recipients');
                Route::put('/mail-recipients', [MailSettingController::class, 'updateRecipients'])->name('mail.recipients.update');
                Route::post('/mail-recipients/test', [MailSettingController::class, 'testRecipients'])->name('mail.recipients.test');
                Route::get('/kitchen-alert', [KitchenAlertSettingController::class, 'edit'])->name('kitchen-alert');
                Route::put('/kitchen-alert', [KitchenAlertSettingController::class, 'update'])->name('kitchen-alert.update');
            });

        Route::prefix('pms')->name('pms.')->group(function () {
            Route::get('/customers', [PmsIntegrationController::class, 'customers'])->name('customers');
            Route::get('/rooms', [PmsIntegrationController::class, 'rooms'])->name('rooms');
            Route::get('/checked-in-guests', [PmsIntegrationController::class, 'checkedInGuests'])->name('checked-in-guests');
            Route::post('/room-charge', [PmsIntegrationController::class, 'roomCharge'])->name('room-charge');
            Route::post('/room-charge/{invoice}/retry', [PmsIntegrationController::class, 'retryRoomCharge'])->name('room-charge.retry');
            Route::post('/order/{ticket}/retry', [PmsIntegrationController::class, 'retryOrder'])->name('order.retry');
        });

        
    });
});
