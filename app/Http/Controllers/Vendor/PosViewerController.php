<?php

namespace App\Http\Controllers\Vendor;

use App\Events\KitchenOrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenantContext;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\Menu;
use App\Models\PosRegister;
use App\Models\PosSession;
use App\Models\PosSessionItem;
use App\Models\PosSessionItemOption;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\User;
use App\Models\PosKitchenTicket;
use App\Models\PosKitchenTicketItem;
use App\Models\PosKitchenTicketItemOption;
use App\Models\PosTransaction;
use App\Models\PosTransactionPayment;
use App\Models\PosCashMovement;
use App\Models\PosInvoice;
use App\Models\PosInvoiceItem;    
use App\Models\ProductStockMovement;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Models\TableMerge;
use App\Models\TableMergeItem;
use App\Models\LoyaltyCustomer;
use App\Models\LoyaltyGift;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyPromotion;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTransaction;
use App\Models\PromotionDiscount;
use App\Models\PromotionRedemption;
use App\Models\PromotionVoucher;
use App\Models\PmsIntegrationSetting;
use App\Services\Pms\PmsRoomChargePoster;
use App\Services\Pms\PmsOrderPoster;
use App\Services\Pms\PmsClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PosViewerController extends Controller
{
    use ResolvesTenantContext;
    public function open()
    {
        $register = PosRegister::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->first();

        if (!$register) {
            return redirect()->route('vendor.pos.registers.index')
                ->withErrors(['general' => 'Please create a POS register first.']);
        }

        return redirect()->route('vendor.pos.viewer', $register->id);
    }

    public function show(Request $request, ?PosRegister $register = null)
    {
        if ($register) {
            abort_unless((int) $register->tenant_id === (int) $this->tenantId(), 404);
        }

        $branches = $this->branches();
        $menus = $this->menus();
        $registers = $this->registers();

       $selectedBranchId = (int) (
    $request->input('branch_id')
    ?: ($register?->branch_id ?: optional($branches->first())->id)
);

$branchRegisters = $registers->where('branch_id', $selectedBranchId)->values();
$branchMenus = $menus->filter(function ($menu) use ($selectedBranchId) {
    $branchIds = array_filter(array_map('intval', $menu->branch_ids ?? []));

    return !$branchIds || in_array($selectedBranchId, $branchIds, true);
})->values();

if (!$register || (int) $register->branch_id !== $selectedBranchId) {
    $register = $branchRegisters->first() ?: $registers->first();
}

$requestedMenuId = $request->input('menu_id');
$selectedMenuId = $requestedMenuId
    ?: $this->preferredMenuId($branchMenus->pluck('id')->map(fn ($id) => (int) $id)->all());

        $session = null;
$selectedCurrencyMode = $request->input('currency_mode', 'base');

if ($selectedCurrencyMode === 'secondary' && !$this->secondaryCurrencyCode()) {
    $selectedCurrencyMode = 'base';
}
        if ($register) {
            $session = PosSession::query()
                ->with(['items.options', 'table:id,name', 'register:id,name,code,branch_id'])
                ->where('tenant_id', $this->tenantId())
                ->where('pos_register_id', $register->id)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();

            if ($session) {
                if (
                    !$requestedMenuId &&
                    $selectedMenuId &&
                    (int) $session->menu_id !== (int) $selectedMenuId &&
                    !$session->items()->exists()
                ) {
                    $session->update(['menu_id' => $selectedMenuId]);
                    $session->refresh();
                }

                $session->loadMissing('items.product.ingredients.ingredient:id,tenant_id,branch_ids,name,current_stock,alert_quantity,is_active');
                $cartIngredientUsage = $this->calculateCartIngredientUsage($session);
                foreach ($session->items as $item) {
                    if ($item->product) {
                        $item->setAttribute('recipe_stock', $this->recipeAvailabilityForProduct($item->product, $session->branch_id, $cartIngredientUsage));
                    }
                }
            }
        }

       return Inertia::render('VendorAdmin/POS/Viewer', [
    'session' => $session,
    'selectedRegister' => $register,
    'selectedBranchId' => $selectedBranchId,
    'selectedMenuId' => $selectedMenuId ? (int) $selectedMenuId : null,
    'selectedCurrencyMode' => $session?->currency_mode ?: $selectedCurrencyMode,
    'branches' => $branches,
    'menus' => $menus,
    'registers' => $registers,
    'categories' => $this->categories(),
    'waiters' => $this->waiters(),
    'customers' => $this->customers(),
    'tables' => $this->tables(),
    'orderTypes' => [
        ['key' => 'takeaway', 'label' => 'Takeaway'],
        ['key' => 'dine_in', 'label' => 'Dine-In'],
        ['key' => 'pick_up', 'label' => 'Pick-up'],
        ['key' => 'drive_thru', 'label' => 'Drive-Thru'],
        ['key' => 'pre_order', 'label' => 'Pre-Order'],
        ['key' => 'catering', 'label' => 'Catering'],
    ],
    'discountPresets' => $this->availablePromotions($session, 'discount'),
    'voucherPresets' => $this->availablePromotions($session, 'voucher'),
    'baseCurrencyCode' => $this->baseCurrencyCode(),
    'secondaryCurrencyCode' => $this->secondaryCurrencyCode(),
    'hasSecondaryCurrency' => (bool) $this->secondaryCurrencyCode(),
    'currencyCode' => $session?->currency_code ?: $this->baseCurrencyCode(),
    'hasActiveSession' => (bool) $session,
]);
    }

    public function fetchProducts(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
            'search' => ['nullable', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'menu_id' => ['nullable', 'integer'],
        ]);

        $cartIngredientUsage = $this->calculateCartIngredientUsage($session);

        $products = $this->products(
            $validated['search'] ?? null,
            $validated['category_id'] ?? null,
            $validated['menu_id'] ?? ($session->menu_id ?: null),
            $validated['per_page'] ?? 18,
            $session->branch_id,
            $cartIngredientUsage,
            $validated['barcode'] ?? null
        );

        return response()->json([
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_more' => $products->hasMorePages(),
            ],
            'currency_mode' => $session->currency_mode,
            'currency_code' => $session->currency_code ?: $this->baseCurrencyCode(),
        ]);
    }

    public function fetchPromotions(PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $session->load('items');

        return response()->json([
            'discounts' => $this->availablePromotions($session, 'discount'),
            'vouchers' => $this->availablePromotions($session, 'voucher'),
        ]);
    }

    public function fetchTables(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $branchId = $request->integer('branch_id') ?: $session->branch_id;

        $orders = PosKitchenTicket::query()
            ->with(['items.options', 'customer:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->whereNotNull('dining_table_id')
            ->whereNotIn('status', ['served', 'cancelled'])
            ->where('payment_status', '!=', 'paid')
            ->get()
            ->groupBy('dining_table_id');

        $merges = TableMerge::query()
            ->with(['items.table:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->whereNull('closed_at')
            ->get();

        $mergeByTable = [];
        foreach ($merges as $merge) {
            $members = $merge->items->map(fn ($item) => [
                'id' => $item->dining_table_id,
                'name' => $item->table?->name,
                'is_primary' => (bool) $item->is_primary,
            ])->values()->all();

            foreach ($members as $member) {
                $mergeByTable[$member['id']] = [
                    'id' => $merge->id,
                    'type' => $merge->type,
                    'members' => $members,
                ];
            }
        }

        $tables = DiningTable::query()
            ->with(['floor:id,name', 'zone:id,name'])
            ->where('tenant_id', $this->tenantId())
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('is_active', true)
            ->select('id', 'branch_id', 'floor_id', 'zone_id', 'name', 'capacity', 'status')
            ->orderBy('name')
            ->get()
            ->map(function ($table) use ($orders, $mergeByTable) {
                $tableOrders = ($orders->get($table->id) ?? collect())->values();
                $merge = $mergeByTable[$table->id] ?? null;
                $status = $merge ? 'merged' : ($tableOrders->isNotEmpty() ? 'occupied' : ($table->status ?: 'available'));

                return [
                    'id' => $table->id,
                    'branch_id' => $table->branch_id,
                    'name' => $table->name,
                    'capacity' => (int) ($table->capacity ?? 0),
                    'status' => $status,
                    'floor' => $table->floor?->name,
                    'zone' => $table->zone?->name,
                    'merge' => $merge,
                   'orders' => $tableOrders->map(fn ($order) => [
    'id' => $order->id,
    'uuid' => $order->uuid,
    'customer_name' => $order->customer_name ?: $order->customer?->name,
    'channel' => $order->channel,
    'waiter_name' => $order->waiter_name,
    'status' => $order->status,
    'payment_status' => $order->payment_status,
    'currency_code' => $order->currency_code,
    'grand_total' => (float) $order->grand_total,
    'created_at' => $order->created_at,
    'sent_to_kitchen_at' => $order->sent_to_kitchen_at,
    'items' => $order->items->map(fn ($item) => [
        'product_name' => $item->product_name,
        'qty' => (float) $item->qty,
    ])->values()->all(),
])->values()->all(),
                ];
            })
            ->values();

        return response()->json([
            'tables' => $tables,
        ]);
    }

    public function mergeTables(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'primary_table_id' => ['required', 'integer', Rule::exists('dining_tables', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))],
            'member_table_ids' => ['required', 'array', 'min:1'],
            'member_table_ids.*' => ['integer', Rule::exists('dining_tables', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId()))],
            'type' => ['required', Rule::in(array_keys(TableMerge::TYPES))],
        ]);

        $tableIds = array_values(array_unique(array_merge(
            [(int) $validated['primary_table_id']],
            array_map('intval', $validated['member_table_ids'])
        )));

        if (count($tableIds) < 2) {
            return response()->json(['message' => 'Please select at least two tables.'], 422);
        }

        DB::transaction(function () use ($session, $validated, $tableIds) {
            $merge = TableMerge::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $session->branch_id,
                'type' => $validated['type'],
                'created_by_name' => optional(auth('vendor')->user())->name,
            ]);

            foreach ($tableIds as $tableId) {
                TableMergeItem::create([
                    'table_merge_id' => $merge->id,
                    'dining_table_id' => $tableId,
                    'is_primary' => (int) $tableId === (int) $validated['primary_table_id'],
                ]);
            }

            DiningTable::query()
                ->whereIn('id', $tableIds)
                ->update(['status' => 'occupied']);
        });

        return response()->json(['message' => 'Tables merged successfully.']);
    }

    public function updateMeta(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'menu_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'dining_table_id' => ['nullable', 'integer'],
            'channel' => ['nullable', Rule::in(['takeaway', 'dine_in', 'pick_up', 'drive_thru', 'pre_order', 'catering', 'pms'])],
            'waiter_name' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'car_plate' => ['nullable', 'string', 'max:255'],
            'car_description' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'currency_mode' => ['nullable', Rule::in(['base', 'secondary'])],
            'pms_guest' => ['nullable', 'array'],
            'pms_guest.booking_id' => ['required_with:pms_guest', 'string', 'max:255'],
            'pms_guest.booking_reference' => ['nullable', 'string', 'max:255'],
            'pms_guest.customer_id' => ['nullable'],
            'pms_guest.guest_name' => ['nullable', 'string', 'max:255'],
            'pms_guest.room_key_id' => ['required_with:pms_guest', 'string', 'max:255'],
            'pms_guest.room_no' => ['nullable', 'string', 'max:255'],
            'pms_guest.room_name' => ['nullable', 'string', 'max:255'],
            'pms_guest.room_type' => ['nullable', 'string', 'max:255'],
            'pms_guest.currency_code' => ['nullable', 'string', 'max:10'],
        ]);

        $requestedMode = $validated['currency_mode'] ?? $session->currency_mode ?? 'base';

        if (
            isset($validated['currency_mode']) &&
            $validated['currency_mode'] !== $session->currency_mode &&
            $session->items()->exists()
        ) {
            return back()->withErrors([
                'currency_mode' => 'Change currency before adding items.',
            ]);
        }

        $resolvedCurrencyCode = $requestedMode === 'secondary'
            ? ($this->secondaryCurrencyCode() ?: $this->baseCurrencyCode())
            : $this->baseCurrencyCode();

        $channelChanged = ($validated['channel'] ?? $session->channel) !== $session->channel;
        
        $session->update([
            'menu_id' => $validated['menu_id'] ?? $session->menu_id,
            'customer_id' => $validated['customer_id'] ?? $session->customer_id,
            'dining_table_id' => $validated['dining_table_id'] ?? $session->dining_table_id,
            'channel' => $validated['channel'] ?? $session->channel,
            'currency_mode' => $requestedMode,
            'currency_code' => $resolvedCurrencyCode,
            'waiter_name' => $validated['waiter_name'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'guest_count' => $validated['guest_count'] ?? 1,
            'pms_guest_snapshot' => $this->normalizePmsGuestSnapshot($validated['pms_guest'] ?? null),
            'car_plate' => in_array($validated['channel'] ?? $session->channel, ['drive_thru']) ? ($validated['car_plate'] ?? null) : null,
            'car_description' => in_array($validated['channel'] ?? $session->channel, ['drive_thru']) ? ($validated['car_description'] ?? null) : null,
            'scheduled_at' => in_array($validated['channel'] ?? $session->channel, ['pre_order', 'catering']) ? ($validated['scheduled_at'] ?? null) : null,
        ]);

        // Recalculate taxes if channel changed
        if ($channelChanged && $session->items()->exists()) {
            $this->recalculateSessionItemTaxes($session->fresh());
            $this->refreshSessionTotals($session->fresh());
        }

        return back();
    }

    public function addItem(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['nullable', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string'],
            'selected_options' => ['nullable', 'array'],
            'selected_options.*.option_name' => ['required', 'string'],
            'selected_options.*.option_type' => ['required', 'string'],
            'selected_options.*.value_label' => ['nullable', 'string'],
            'selected_options.*.value_input' => ['nullable', 'string'],
            'selected_options.*.price' => ['nullable', 'numeric', 'min:0'],
            'selected_options.*.price_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
        ]);

        $product = Product::query()
            ->with([
                'taxes:id,name,rate,type,is_compound,is_active,order_types',
                'productOptions.rows',
                'ingredients.ingredient:id,tenant_id,branch_ids,name,current_stock,alert_quantity,is_active',
            ])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($validated['product_id']);

        DB::transaction(function () use ($session, $product, $validated) {
            $qty = (float) ($validated['qty'] ?? 1);
            $recipeAvailability = $this->recipeAvailabilityForProduct($product, $session->branch_id);
            $existingQty = (float) $session->items()
                ->where('product_id', $product->id)
                ->sum('qty');
            $requestedRecipeQty = $existingQty + $qty;
            $this->ensureProductStockAvailable($product, $requestedRecipeQty, 'product_id');

            if (
                $recipeAvailability['tracked'] &&
                $recipeAvailability['can_make'] !== null &&
                $requestedRecipeQty > (int) $recipeAvailability['can_make']
            ) {
                throw ValidationException::withMessages([
                    'product_id' => $recipeAvailability['can_make'] <= 0
                        ? "{$product->name} is unavailable from recipe stock."
                        : "Only {$recipeAvailability['can_make']} {$product->name} can be made from current recipe stock.",
                ]);
            }

            $useSecondary = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode();
            $currencyCode = $useSecondary ? $this->secondaryCurrencyCode() : $this->baseCurrencyCode();
            $activeSpecialPrice = $product->getActiveSpecialPrice($session->currency_mode ?: 'base');
            $productUnitPrice = $activeSpecialPrice !== null
                ? $activeSpecialPrice
                : ($useSecondary
                    ? (float) ($product->secondary_price ?? $product->base_price ?? 0)
                    : (float) ($product->base_price ?? 0));

            $optionTotalPerUnit = 0;

            $optionRows = collect($validated['selected_options'] ?? [])->map(function ($payload) use ($product, $useSecondary, $productUnitPrice, &$optionTotalPerUnit) {
                $option = $product->productOptions->first(function ($opt) use ($payload) {
                    return $opt->name === $payload['option_name']
                        && $opt->type === $payload['option_type'];
                });

                $computed = 0;
                $priceType = 'fixed';

                if ($option) {
                    if (in_array($option->type, ['select', 'multiple_select', 'checkbox', 'radio_button'], true)) {
                        $row = $option->rows->firstWhere('label', $payload['value_label']);
                        $rawPrice = $useSecondary
                            ? (float) ($row?->secondary_price ?? $row?->base_price ?? 0)
                            : (float) ($row?->base_price ?? 0);
                        $priceType = $row?->price_type ?? 'fixed';
                    } else {
                        $rawPrice = $useSecondary
                            ? (float) ($option->secondary_price ?? $option->base_price ?? 0)
                            : (float) ($option->base_price ?? 0);
                        $priceType = $option->price_type ?? 'fixed';
                    }

                    $computed = $priceType === 'percentage'
                        ? ($productUnitPrice * $rawPrice) / 100
                        : $rawPrice;
                }

                $optionTotalPerUnit += $computed;

                return [
                    'option_name' => $payload['option_name'],
                    'option_type' => $payload['option_type'],
                    'value_label' => $payload['value_label'] ?? null,
                    'value_input' => $payload['value_input'] ?? null,
                    'price' => $computed,
                    'price_type' => $priceType,
                ];
            });

            $existingItem = $session->items()
                ->with('options')
                ->where('product_id', $product->id)
                ->whereNull('loyalty_gift_id')
                ->where('notes', $validated['notes'] ?? null)
                ->get()
                ->first(function ($item) use ($optionRows) {
                    $itemOptions = $item->options;
                    
                    if ($itemOptions->count() !== count($optionRows)) {
                        return false;
                    }
                    
                    foreach ($optionRows as $row) {
                        $match = $itemOptions->contains(function ($opt) use ($row) {
                            return $opt->option_name === $row['option_name']
                                && $opt->option_type === $row['option_type']
                                && $opt->value_label === ($row['value_label'] ?? null)
                                && $opt->value_input === ($row['value_input'] ?? null);
                        });
                        
                        if (!$match) {
                            return false;
                        }
                    }
                    
                    return true;
                });

            if ($existingItem) {
                $newQty = $existingItem->qty + $qty;
                $unitWithOptions = $productUnitPrice + $optionTotalPerUnit;
                $lineSubtotal = $unitWithOptions * $newQty;
                $taxBreakdown = $this->calculateTaxes($lineSubtotal, $product, $session->channel);
                $lineTax = $taxBreakdown['tax_total'];
                $lineTotal = $lineSubtotal + $lineTax;

                $existingItem->update([
                    'qty' => $newQty,
                    'unit_price' => $productUnitPrice,
                    'option_total' => $optionTotalPerUnit,
                    'line_subtotal' => $lineSubtotal,
                    'tax_total' => $lineTax,
                    'line_total' => $lineTotal,
                    'tax_snapshot' => $taxBreakdown['rows'],
                ]);
            } else {
                $unitWithOptions = $productUnitPrice + $optionTotalPerUnit;
                $lineSubtotal = $unitWithOptions * $qty;
                $taxBreakdown = $this->calculateTaxes($lineSubtotal, $product, $session->channel);
                $lineTax = $taxBreakdown['tax_total'];
                $lineTotal = $lineSubtotal + $lineTax;

                $item = PosSessionItem::create([
                    'pos_session_id' => $session->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'image_url' => $product->image_url,
                    'currency_mode' => $session->currency_mode,
                    'currency_code' => $currencyCode,
                    'qty' => $qty,
                    'unit_price' => $productUnitPrice,
                    'option_total' => $optionTotalPerUnit,
                    'line_subtotal' => $lineSubtotal,
                    'tax_total' => $lineTax,
                    'line_total' => $lineTotal,
                    'tax_snapshot' => $taxBreakdown['rows'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($optionRows as $row) {
                    PosSessionItemOption::create([
                        'pos_session_item_id' => $item->id,
                        'option_name' => $row['option_name'],
                        'option_type' => $row['option_type'],
                        'value_label' => $row['value_label'],
                        'value_input' => $row['value_input'],
                        'price' => $row['price'],
                        'price_type' => $row['price_type'],
                    ]);
                }
            }

            $this->refreshSessionTotals($session->fresh());
        });

        return back();
    }

    public function removeItem(PosSession $session, int $itemId)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $item = $session->items()->where('id', $itemId)->firstOrFail();
        
        if ($item->loyalty_gift_id) {
            LoyaltyGift::where('id', $item->loyalty_gift_id)
                ->where('status', 'claimed')
                ->update(['status' => 'available']);
        }

        $item->options()->delete();
        $item->delete();
        $this->refreshSessionTotals($session->fresh());

        $session->refresh();
        if ($session->items->count() === 0 && $session->editing_ticket_id) {
            DB::transaction(function () use ($session) {
                $this->cancelAssociatedTicket($session);
                $this->resetSessionForNextOrder($session);
            });
        }

        return back();
    }

    public function updateItemQty(Request $request, PosSession $session, int $itemId)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'min:0.001'],
        ]);

        $item = $session->items()->where('id', $itemId)->firstOrFail();
        $product = Product::query()
            ->with([
                'taxes:id,name,rate,type,is_compound,is_active,order_types',
                'ingredients.ingredient:id,tenant_id,branch_ids,name,current_stock,alert_quantity,is_active',
            ])
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($item->product_id);

        $qtyDiff = $validated['qty'] - $item->qty;

        if ($qtyDiff > 0) {
            $recipeAvailability = $this->recipeAvailabilityForProduct($product, $session->branch_id);
            $existingQty = (float) $session->items()
                ->where('product_id', $product->id)
                ->sum('qty');
            $requestedRecipeQty = $existingQty + $qtyDiff;
            $this->ensureProductStockAvailable($product, $requestedRecipeQty, 'qty');

            if (
                $recipeAvailability['tracked'] &&
                $recipeAvailability['can_make'] !== null &&
                $requestedRecipeQty > (int) $recipeAvailability['can_make']
            ) {
                throw ValidationException::withMessages([
                    'qty' => $recipeAvailability['can_make'] <= 0
                        ? "{$product->name} is unavailable from recipe stock."
                        : "Only {$recipeAvailability['can_make']} {$product->name} can be made from current recipe stock.",
                ]);
            }
        }

        $newQty = (float) $validated['qty'];
        $unitWithOptions = $item->unit_price + $item->option_total;
        $lineSubtotal = $unitWithOptions * $newQty;
        $taxBreakdown = $this->calculateTaxes($lineSubtotal, $product, $session->channel);
        $lineTax = $taxBreakdown['tax_total'];
        $lineTotal = $lineSubtotal + $lineTax;

        $item->update([
            'qty' => $newQty,
            'line_subtotal' => $lineSubtotal,
            'tax_total' => $lineTax,
            'line_total' => $lineTotal,
            'tax_snapshot' => $taxBreakdown['rows'],
        ]);

        $this->refreshSessionTotals($session->fresh());

        return back();
    }

    private function ensureProductStockAvailable(Product $product, float $requestedQty, string $field = 'product_id'): void
    {
        $currentStock = (float) ($product->current_stock ?? 0);

        if ($currentStock + 0.0001 >= $requestedQty) {
            return;
        }

        $unit = $product->unit_type ?: 'pcs';
        $available = rtrim(rtrim(number_format($currentStock, 3, '.', ''), '0'), '.');

        throw ValidationException::withMessages([
            $field => "Only {$available} {$unit} of {$product->name} is available in stock.",
        ]);
    }

    private function deductProductStockForSession(PosSession $session): void
    {
        $items = $session->items()
            ->select('product_id', DB::raw('SUM(qty) as qty'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->get();

        foreach ($items as $item) {
            $product = Product::query()
                ->where('tenant_id', $this->tenantId())
                ->lockForUpdate()
                ->find($item->product_id);

            if (!$product) {
                continue;
            }

            $qty = (float) $item->qty;
            $this->recordProductStockOut(
                $product,
                $qty,
                $session->branch_id,
                'POS sale from session #' . $session->id,
                'payments'
            );
        }
    }

    private function deductProductStockForTicket(PosKitchenTicket $ticket): void
    {
        $items = $ticket->items()
            ->select('product_id', DB::raw('SUM(qty) as qty'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->get();

        foreach ($items as $item) {
            $product = Product::query()
                ->where('tenant_id', $this->tenantId())
                ->lockForUpdate()
                ->find($item->product_id);

            if (!$product) {
                continue;
            }

            $qty = (float) $item->qty;
            $this->recordProductStockOut(
                $product,
                $qty,
                $ticket->branch_id,
                'POS order #' . $ticket->id,
                'payments'
            );
        }
    }

    private function recordProductStockOut(Product $product, float $qty, ?int $branchId, string $note, string $field = 'payments'): void
    {
        $this->ensureProductStockAvailable($product, $qty, $field);

        $before = (float) ($product->current_stock ?? 0);
        $after = max(0, $before - $qty);

        ProductStockMovement::create([
            'tenant_id' => $this->tenantId(),
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => $qty,
            'stock_before' => $before,
            'stock_after' => $after,
            'note' => $note,
        ]);

        $product->current_stock = $after;
        $product->save();
    }

    public function applyDiscount(Request $request, PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        $validated = $request->validate([
            'discount_mode' => ['nullable', Rule::in(['discount', 'voucher'])],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'promotion_id' => ['nullable', 'integer'],
            'promotion_code' => ['nullable', 'string', 'max:80'],
        ]);

        $promotion = null;
        if (!empty($validated['promotion_id']) && !empty($validated['discount_mode'])) {
            $promotion = $this->resolvePromotionForSession(
                $session->fresh(['items']),
                $validated['discount_mode'],
                (int) $validated['promotion_id']
            );
            $useSecondary = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode();
            $validated['discount_type'] = $promotion->type;
            $validated['discount_value'] = $this->promotionValue($promotion, $useSecondary);
            $validated['promotion_code'] = $promotion->code ?? null;
        }

        $session->update([
            'discount_mode' => $validated['discount_mode'] ?? null,
            'discount_type' => $validated['discount_type'] ?? null,
            'discount_value' => $validated['discount_value'] ?? null,
            'promotion_discount_id' => ($validated['discount_mode'] ?? null) === 'discount' ? ($promotion?->id) : null,
            'promotion_voucher_id' => ($validated['discount_mode'] ?? null) === 'voucher' ? ($promotion?->id) : null,
            'promotion_code' => $validated['promotion_code'] ?? null,
        ]);

        $this->refreshSessionTotals($session->fresh());

        return back();
    }

    public function hold(PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

        if (!$session->items()->count()) {
            return back()->withErrors([
                'general' => 'Add at least one item before holding order.',
            ]);
        }

        $ticketId = null;

        DB::transaction(function () use ($session, &$ticketId) {
            $session->refresh();

            // Restore loyalty redemption if any
            $this->restoreLoyaltyRedemptionFromOrder($session, 'POS order held');

            $ticket = $this->createHeldOrderFromSession($session);
            $ticketId = $ticket->id;

            // Clear session items and reset for next order while keeping the register session open.
            $this->resetSessionForNextOrder($session);
        });

        $ticket = $ticketId
            ? PosKitchenTicket::query()->where('tenant_id', $this->tenantId())->find($ticketId)
            : null;

        if ($ticket) {
            $this->postPmsOrder($ticket->fresh(['items.options', 'customer', 'register.branch', 'table']));
        }

        return back()->with('success', 'Order held successfully. Session ready for new order.');
    }


    private function cancelAssociatedTicket(PosSession $session): void
    {
        if (!$session->editing_ticket_id) {
            return;
        }

        $ticket = PosKitchenTicket::query()
            ->where('tenant_id', $this->tenantId())
            ->find($session->editing_ticket_id);

        if ($ticket) {
            $this->restoreLoyaltyRedemptionFromOrder($ticket, 'kitchen order cancellation via empty cart/POS reset');

            $ticket->update([
                'status' => 'cancelled',
                'cancel_reason' => 'All items removed or order cancelled in POS',
                'cancel_note' => 'System cancelled due to empty cart or POS reset',
            ]);

            $this->postPmsOrder($ticket->fresh(['items.options', 'customer', 'register.branch', 'table']));
        }
    }

  public function cancel(PosSession $session)
{
    abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

    DB::transaction(function () use ($session) {
        $session->refresh();

        $this->cancelAssociatedTicket($session);

        $this->restoreLoyaltyRedemptionFromOrder($session, 'POS order cancellation');

        $this->resetSessionForNextOrder($session);
    });

    return back()->with('success', 'Order cancelled and POS has been reset.');
}

private function restoreLoyaltyRedemptionFromOrder($order, string $reason): void
{
    $customerId = (int) ($order->customer_id ?? 0);
    $rewardId = (int) ($order->loyalty_reward_id ?? 0);
    $points = (int) ($order->loyalty_points_redeemed ?? 0);

    if (!$customerId || !$rewardId || $points <= 0) {
        return;
    }

    $reward = LoyaltyReward::query()
        ->where('tenant_id', $this->tenantId())
        ->find($rewardId);

    if (!$reward) {
        return;
    }

    $source = class_basename($order) . ' #' . ($order->id ?? 'new');

    $alreadyRestored = LoyaltyTransaction::query()
        ->where('tenant_id', $this->tenantId())
        ->where('customer_id', $customerId)
        ->where('loyalty_reward_id', $reward->id)
        ->where('type', 'adjust')
        ->where('description', 'like', "%{$source}%")
        ->exists();

    if ($alreadyRestored) {
        return;
    }

    $account = LoyaltyCustomer::query()
        ->where('tenant_id', $this->tenantId())
        ->where('customer_id', $customerId)
        ->where('loyalty_program_id', $reward->loyalty_program_id)
        ->lockForUpdate()
        ->first();

    if (!$account) {
        return;
    }

    $before = (int) $account->points_balance;
    $after = $before + $points;

    $account->update([
        'points_balance' => $after,
    ]);

    LoyaltyTransaction::create([
        'uuid' => (string) Str::uuid(),
        'tenant_id' => $this->tenantId(),
        'customer_id' => $customerId,
        'loyalty_program_id' => $reward->loyalty_program_id,
        'loyalty_reward_id' => $reward->id,
        'type' => 'adjust',
        'description' => "Restored {$points} points after {$reason} ({$source}).",
        'points' => $points,
        'balance_before' => $before,
        'balance_after' => $after,
        'amount' => (float) ($order->loyalty_discount_total ?? 0),
        'currency_mode' => $order->currency_mode ?? null,
        'currency_code' => $order->currency_code ?? null,
    ]);

    if ((int) $reward->redeemed_count > 0) {
        $reward->decrement('redeemed_count');
    }
}
    public function payFire(PosSession $session)
    {
        abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);
        $session->update(['status' => 'paid']);
        return back()->with('success', 'Order paid and fired successfully.');
    }

    private function availablePromotions(?PosSession $session, string $mode): array
    {
        $model = $mode === 'voucher' ? PromotionVoucher::class : PromotionDiscount::class;
        $useSecondary = $session?->currency_mode === 'secondary' && $this->secondaryCurrencyCode();

        return $model::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->orderBy('name')
            ->get()
            ->filter(function ($promotion) use ($session) {
                return !$session || $this->promotionRedeemFailureReason($promotion, $session) === null;
            })
            ->map(fn ($promotion) => [
                'id' => $promotion->id,
                'label' => $mode === 'voucher'
                    ? "{$promotion->name} ({$promotion->code})"
                    : $promotion->name,
                'code' => $promotion->code ?? null,
                'type' => $promotion->type,
                'value' => $this->promotionValue($promotion, $useSecondary),
                'mode' => $mode,
            ])
            ->values()
            ->prepend(['id' => null, 'label' => $mode === 'voucher' ? 'No Voucher' : 'No Discount', 'type' => null, 'value' => null, 'mode' => $mode])
            ->all();
    }

    private function resolvePromotionForSession(PosSession $session, string $mode, int $id): PromotionDiscount|PromotionVoucher
    {
        $model = $mode === 'voucher' ? PromotionVoucher::class : PromotionDiscount::class;
        $promotion = $model::query()
            ->where('tenant_id', $this->tenantId())
            ->whereKey($id)
            ->firstOrFail();

        $failureReason = $this->promotionRedeemFailureReason($promotion, $session);

        if ($failureReason !== null) {
            throw ValidationException::withMessages([
                'discount' => $failureReason,
            ]);
        }

        return $promotion;
    }

    private function promotionRedeemFailureReason(PromotionDiscount|PromotionVoucher $promotion, PosSession $session): ?string
    {
        if (!$promotion->is_active) {
            return 'This promotion is inactive.';
        }

        if ($promotion->starts_at && $promotion->starts_at->isFuture()) {
            return 'This promotion has not started yet.';
        }

        if ($promotion->ends_at && $promotion->ends_at->isPast()) {
            return 'This promotion has already expired.';
        }

        if ($promotion->usage_limit && $promotion->used_count >= $promotion->usage_limit) {
            return 'This promotion has reached its usage limit.';
        }

        if ($promotion->per_customer_limit && $session->customer_id) {
            $redeemed = PromotionRedemption::query()
                ->where('tenant_id', $this->tenantId())
                ->where('customer_id', $session->customer_id)
                ->when($promotion instanceof PromotionDiscount, fn ($query) => $query->where('promotion_discount_id', $promotion->id))
                ->when($promotion instanceof PromotionVoucher, fn ($query) => $query->where('promotion_voucher_id', $promotion->id))
                ->count();

            if ($redeemed >= $promotion->per_customer_limit) {
                return 'This customer has already used this promotion the maximum allowed times.';
            }
        }

        return $this->promotionSessionMismatchReason($promotion, $session);
    }

    private function promotionMatchesSession(PromotionDiscount|PromotionVoucher $promotion, PosSession $session): bool
    {
        return $this->promotionSessionMismatchReason($promotion, $session) === null;
    }

    private function promotionSessionMismatchReason(PromotionDiscount|PromotionVoucher $promotion, PosSession $session): ?string
    {
        if ($promotion->branch_id && (int) $promotion->branch_id !== (int) $session->branch_id) {
            return 'This promotion is not available for the selected branch.';
        }

        if ($promotion->order_types && !in_array($session->channel, $promotion->order_types, true)) {
            return 'This promotion is not available for the current order type.';
        }

        $today = strtolower(now()->format('l'));
        if ($promotion->available_days && !in_array($today, $promotion->available_days, true)) {
            return 'This promotion is not available today.';
        }

        $useSecondary = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode();
        $subtotal = (float) $session->subtotal;
        $minSpend = $useSecondary ? $promotion->secondary_min_spend : $promotion->min_spend;
        $maxSpend = $useSecondary ? $promotion->secondary_max_spend : $promotion->max_spend;
        $currencyCode = $useSecondary ? $this->secondaryCurrencyCode() : $this->baseCurrencyCode();

        if ($minSpend !== null && $subtotal + 0.0001 < (float) $minSpend) {
            return 'This promotion requires a minimum order subtotal of ' . $currencyCode . ' ' . number_format((float) $minSpend, 2) . '.';
        }

        if ($maxSpend !== null && $subtotal - 0.0001 > (float) $maxSpend) {
            return 'This promotion only applies up to an order subtotal of ' . $currencyCode . ' ' . number_format((float) $maxSpend, 2) . '.';
        }

        $productIds = $session->items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->values();
        if ($promotion->product_ids && $productIds->intersect(array_map('intval', $promotion->product_ids))->isEmpty()) {
            return 'Add an eligible product to use this promotion.';
        }

        if ($promotion->category_ids) {
            if ($productIds->isEmpty()) {
                return 'Add a product from an eligible category to use this promotion.';
            }

            $hasCategory = Product::query()
                ->where('tenant_id', $this->tenantId())
                ->whereIn('id', $productIds)
                ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', array_map('intval', $promotion->category_ids)))
                ->exists();

            if (!$hasCategory) {
                return 'Add a product from an eligible category to use this promotion.';
            }
        }

        return null;
    }

    private function promotionValue(PromotionDiscount|PromotionVoucher $promotion, bool $useSecondary): float
    {
        if ($promotion->type === 'percentage') {
            return (float) $promotion->value;
        }

        return (float) ($useSecondary ? ($promotion->secondary_value ?? $promotion->value) : $promotion->value);
    }

    private function recordPromotionRedemption(PosSession $session, PosTransaction $transaction): void
    {
        if (!$session->promotion_discount_id && !$session->promotion_voucher_id) {
            return;
        }

        $promotion = $session->promotion_discount_id
            ? PromotionDiscount::lockForUpdate()->find($session->promotion_discount_id)
            : PromotionVoucher::lockForUpdate()->find($session->promotion_voucher_id);

        if (!$promotion) {
            return;
        }

        $promotion->increment('used_count');

        PromotionRedemption::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'branch_id' => $session->branch_id,
            'promotion_discount_id' => $session->promotion_discount_id,
            'promotion_voucher_id' => $session->promotion_voucher_id,
            'pos_session_id' => $session->id,
            'pos_transaction_id' => $transaction->id,
            'customer_id' => $session->customer_id,
            'created_by' => auth('vendor')->id(),
            'promotion_type' => $session->discount_mode,
            'promotion_code' => $session->promotion_code,
            'currency_mode' => $session->currency_mode ?: 'base',
            'currency_code' => $session->currency_code ?: $this->baseCurrencyCode(),
            'subtotal' => $session->subtotal,
            'discount_amount' => $session->discount_total,
            'redeemed_at' => now(),
        ]);
    }

    private function refreshSessionTotals(PosSession $session): void
    {
        $session->load('items');

        $subtotal = (float) $session->items->sum(fn ($item) => (float) $item->line_subtotal);
        $taxTotal = (float) $session->items->sum(fn ($item) => (float) $item->tax_total);

        $discountTotal = 0;
        if ($session->discount_type && $session->discount_value !== null) {
            $promotion = $session->promotion_discount_id
                ? PromotionDiscount::find($session->promotion_discount_id)
                : ($session->promotion_voucher_id ? PromotionVoucher::find($session->promotion_voucher_id) : null);

            if ($promotion && !$this->promotionMatchesSession($promotion, $session)) {
                $session->update([
                    'discount_mode' => null,
                    'discount_type' => null,
                    'discount_value' => null,
                    'promotion_discount_id' => null,
                    'promotion_voucher_id' => null,
                    'promotion_code' => null,
                ]);
                $session->refresh();
                $promotion = null;
            }

            $discountTotal = $session->discount_type === 'percentage'
                ? ($subtotal * (float) $session->discount_value) / 100
                : (float) $session->discount_value;

            if ($promotion) {
                $useSecondary = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode();
                $maxDiscount = $useSecondary ? $promotion->secondary_max_discount : $promotion->max_discount;
                if ($maxDiscount !== null) {
                    $discountTotal = min($discountTotal, (float) $maxDiscount);
                }
            }
        }

        $loyaltyDiscount = 0;
        if ($session->loyalty_gift_id) {
            $gift = LoyaltyGift::find($session->loyalty_gift_id);
            if ($gift && $gift->status === 'claimed') {
                $payload = $gift->payload ?? [];
                $valueType = $payload['value_type'] ?? 'fixed';
                $value = (float) ($payload['value'] ?? 0);
                
                $reward = $gift->reward ?: ($gift->loyalty_reward_id ? LoyaltyReward::find($gift->loyalty_reward_id) : null);
                if ($reward && $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()) {
                    $value = (float) ($reward->secondary_value ?? $reward->value ?? 0);
                }
                
                $loyaltyDiscount = $valueType === 'percentage' ? ($subtotal * $value / 100) : $value;
                $loyaltyDiscount = min($subtotal + $taxTotal, max(0, $loyaltyDiscount));
                
                $session->update(['loyalty_discount_total' => $loyaltyDiscount]);
            }
        } elseif ($session->loyalty_reward_id) {
            $reward = LoyaltyReward::query()
                ->where('tenant_id', $this->tenantId())
                ->find($session->loyalty_reward_id);

            if ($reward && $reward->is_active && $reward->type === 'discount') {
                $value = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()
                    ? (float) ($reward->secondary_value ?? $reward->value ?? 0)
                    : (float) ($reward->value ?? 0);
                
                $loyaltyDiscount = $reward->value_type === 'percentage' ? ($subtotal * $value / 100) : $value;
                $loyaltyDiscount = min($subtotal + $taxTotal, max(0, $loyaltyDiscount));
                
                $session->update(['loyalty_discount_total' => $loyaltyDiscount]);
            } else {
                $session->update([
                    'loyalty_reward_id' => null,
                    'loyalty_points_redeemed' => 0,
                    'loyalty_discount_total' => 0,
                ]);
                $session->refresh();
                $loyaltyDiscount = 0;
            }
        }

        $discountTotal += min($loyaltyDiscount, max(0, ($subtotal + $taxTotal) - $discountTotal));
        $grandTotal = max(0, ($subtotal + $taxTotal) - $discountTotal);

        $session->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    private function calculateTaxes(float $lineSubtotal, Product $product, ?string $orderChannel = null): array
    {
        $activeTaxes = collect($product->taxes ?? [])->filter(function ($tax) use ($orderChannel) {
            if (!(bool) $tax->is_active) {
                return false;
            }
            
            // If order channel is specified, check if tax applies to this channel
            if ($orderChannel && isset($tax->order_types) && is_array($tax->order_types)) {
                // Tax applies if it has no specific order types or if current channel is in the list
                $hasOrderTypes = !empty($tax->order_types);
                if ($hasOrderTypes && !in_array($orderChannel, $tax->order_types, true)) {
                    return false; // Tax doesn't apply to this channel
                }
            }
            
            return true;
        });
        
        $base = $lineSubtotal;
        $taxTotal = 0;
        $rows = [];

        foreach ($activeTaxes->where('type', 'exclusive')->where('is_compound', false) as $tax) {
            $amount = ($base * (float) $tax->rate) / 100;
            $taxTotal += $amount;
            $rows[] = ['name' => $tax->name, 'rate' => $tax->rate, 'amount' => $amount];
        }

        $compoundBase = $base + $taxTotal;

        foreach ($activeTaxes->where('type', 'exclusive')->where('is_compound', true) as $tax) {
            $amount = ($compoundBase * (float) $tax->rate) / 100;
            $taxTotal += $amount;
            $rows[] = ['name' => $tax->name, 'rate' => $tax->rate, 'amount' => $amount];
        }

        return [
            'tax_total' => $taxTotal,
            'rows' => $rows,
        ];
    }

    private function recalculateSessionItemTaxes(PosSession $session): void
    {
        $session->load(['items.product.taxes' => function ($query) {
            $query->select('taxes.id', 'name', 'rate', 'type', 'is_compound', 'is_active', 'order_types');
        }]);

        foreach ($session->items as $item) {
            if ($item->product) {
                $taxBreakdown = $this->calculateTaxes($item->line_subtotal, $item->product, $session->channel);
                $item->update([
                    'tax_total' => $taxBreakdown['tax_total'],
                    'line_total' => $item->line_subtotal + $taxBreakdown['tax_total'],
                    'tax_snapshot' => $taxBreakdown['rows'],
                ]);
            }
        }
    }


    private function branches()
    {
        return Branch::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function menus()
    {
        return Menu::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->select('id', 'name', 'branch_ids')
            ->orderBy('name')
            ->get();
    }

    private function preferredMenuId(array $menuIds = []): ?int
    {
        $menuIds = array_values(array_filter(array_map('intval', $menuIds)));

        $productMenuId = Product::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->whereNotNull('menu_id')
            ->when($menuIds, fn ($query) => $query->whereIn('menu_id', $menuIds))
            ->orderBy('menu_id')
            ->value('menu_id');

        if ($productMenuId) {
            return (int) $productMenuId;
        }

        return $menuIds[0] ?? null;
    }

    private function categories()
    {
        return Category::query()
            ->with('foodCategory:id,name,slug')
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->select('id', 'name', 'menu_id', 'parent_id', 'food_category_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'menu_id' => $category->menu_id,
                'parent_id' => $category->parent_id,
                'food_category_id' => $category->food_category_id,
                'food_category_name' => $category->foodCategory?->name,
                'food_category_slug' => $category->foodCategory?->slug,
            ])
            ->values();
    }

    private function products(?string $search = null, ?int $categoryId = null, ?int $menuId = null, int $perPage = 18, ?int $branchId = null, array $cartIngredientUsage = [], ?string $barcode = null)
{
    $products = Product::query()
        ->with([
            'taxes:id,name,rate,type,is_compound,is_active,order_types',
            'categories:id,name,food_category_id',
            'productOptions.rows',
            'ingredients.ingredient:id,tenant_id,branch_ids,name,current_stock,alert_quantity,is_active',
        ])
        ->where('tenant_id', $this->tenantId())
        ->where('is_active', true)
        ->when($menuId, fn ($query) => $query->where('menu_id', $menuId))
        ->when($barcode, fn ($query) => $query->where('sku', trim($barcode)))
        ->when(!$barcode && $search, fn ($query) => $query->where(function ($searchQuery) use ($search) {
            $searchQuery->where('name', 'like', '%' . $search . '%')
                ->orWhere('sku', 'like', '%' . $search . '%');
        }))
        ->when($categoryId, fn ($query) => $query->whereHas(
            'categories',
            fn ($categoryQuery) => $categoryQuery->where('categories.id', $categoryId)
        ))
        ->select('id', 'menu_id', 'name', 'sku', 'brand', 'unit_type', 'base_price', 'secondary_price', 'cost_price', 'current_stock', 'reorder_level', 'special_price_type', 'base_special_price', 'secondary_special_price', 'special_price_start', 'special_price_end', 'image_path')
        ->orderBy('name')
        ->paginate($perPage);

    $products->getCollection()->transform(function ($product) use ($branchId, $cartIngredientUsage) {
            $recipeAvailability = $this->recipeAvailabilityForProduct($product, $branchId, $cartIngredientUsage);

            return [
                'id' => $product->id,
                'menu_id' => $product->menu_id,
                'category_ids' => $product->categories->pluck('id')->values()->all(),
                'food_category_ids' => $product->categories->pluck('food_category_id')->filter()->unique()->values()->all(),
                'name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand,
                'unit_type' => $product->unit_type ?: 'pcs',
                'base_price' => (float) ($product->base_price ?? 0),
                'secondary_price' => (float) ($product->secondary_price ?? $product->base_price ?? 0),
                'cost_price' => (float) ($product->cost_price ?? 0),
                'current_stock' => (float) ($product->current_stock ?? 0),
                'reorder_level' => (float) ($product->reorder_level ?? 0),
                'special_price_type' => $product->special_price_type,
                'base_special_price' => $product->base_special_price !== null ? (float) $product->base_special_price : null,
                'secondary_special_price' => $product->secondary_special_price !== null ? (float) $product->secondary_special_price : null,
                'special_price_start' => $product->special_price_start ? $product->special_price_start->toIso8601String() : null,
                'special_price_end' => $product->special_price_end ? $product->special_price_end->toIso8601String() : null,
                'image_url' => $product->image_url,
                'taxes' => $product->taxes->map(function ($tax) {
                    return [
                        'id' => $tax->id,
                        'name' => $tax->name,
                        'rate' => (float) $tax->rate,
                        'type' => $tax->type,
                        'is_compound' => (bool) $tax->is_compound,
                        'is_active' => (bool) $tax->is_active,
                        'order_types' => $tax->order_types ?? [],
                    ];
                })->values()->all(),
                'options' => $product->productOptions->map(function ($option) {
                    return [
                        'name' => $option->name,
                        'type' => $option->type,
                        'is_required' => (bool) $option->is_required,
                        'base_price' => (float) ($option->base_price ?? 0),
                        'secondary_price' => (float) ($option->secondary_price ?? $option->base_price ?? 0),
                        'price_type' => $option->price_type ?? 'fixed',
                        'rows' => $option->rows->map(function ($row) {
                            return [
                                'label' => $row->label,
                                'base_price' => (float) ($row->base_price ?? 0),
                                'secondary_price' => (float) ($row->secondary_price ?? $row->base_price ?? 0),
                                'price_type' => $row->price_type ?? 'fixed',
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
                'has_options' => $product->productOptions->isNotEmpty(),
                'recipe_stock' => $recipeAvailability,
            ];
        });

    return $products;
}

private function recipeAvailabilityForProduct(Product $product, ?int $branchId = null, array $cartIngredientUsage = []): array
{
    $recipeRows = $product->ingredients ?? collect();
    $limits = [];

    foreach ($recipeRows as $recipeRow) {
        $ingredient = $recipeRow->ingredient;

        if (!$ingredient || !(bool) $ingredient->is_active) {
            continue;
        }

        $branchIds = array_filter(array_map('intval', $ingredient->branch_ids ?? []));
        if ($branchId && $branchIds && !in_array((int) $branchId, $branchIds, true)) {
            continue;
        }

        $requiredPerProduct = $this->recipeIngredientQuantityPerProduct($recipeRow);
        if ($requiredPerProduct <= 0) {
            continue;
        }

        $currentStock = max(0, (float) $ingredient->current_stock);
        
        // Deduct quantity of this ingredient currently held in the cart
        if (isset($cartIngredientUsage[$ingredient->id])) {
            $currentStock = max(0, $currentStock - $cartIngredientUsage[$ingredient->id]);
        }

        $canMake = (int) floor($currentStock / $requiredPerProduct);

        $limits[] = [
            'ingredient_id' => $ingredient->id,
            'ingredient_name' => $ingredient->name,
            'current_stock' => round($currentStock, 3),
            'required_per_product' => round($requiredPerProduct, 4),
            'alert_quantity' => (float) ($ingredient->alert_quantity ?? 0),
            'can_make' => $canMake,
        ];
    }

    if (!$limits) {
        return [
            'tracked' => false,
            'status' => 'untracked',
            'can_make' => null,
            'message' => null,
            'limiting_ingredient' => null,
        ];
    }

    $limiting = collect($limits)->sortBy('can_make')->first();
    $canMake = (int) ($limiting['can_make'] ?? 0);
    $lowByRecipe = $canMake <= 10;
    $lowByIngredientAlert = collect($limits)->contains(function ($limit) {
        return (float) $limit['alert_quantity'] > 0
            && (float) $limit['current_stock'] <= (float) $limit['alert_quantity'];
    });

    $status = 'available';
    $message = null;

    if ($canMake <= 0) {
        $status = 'unavailable';
        $message = 'Unavailable from recipe stock';
    } elseif ($lowByRecipe || $lowByIngredientAlert) {
        $status = 'low';
        $message = 'Only ' . $canMake . ' can be made';
    }

    return [
        'tracked' => true,
        'status' => $status,
        'can_make' => $canMake,
        'message' => $message,
        'limiting_ingredient' => $limiting['ingredient_name'] ?? null,
    ];
}

private function calculateCartIngredientUsage(PosSession $session): array
{
    $cartIngredientUsage = [];
    
    $session->loadMissing('items.product.ingredients');
    
    foreach ($session->items as $item) {
        $product = $item->product;
        if (!$product) {
            continue;
        }
        foreach ($product->ingredients as $recipeRow) {
            $ingredientId = $recipeRow->ingredient_id;
            $qtyPerProduct = $this->recipeIngredientQuantityPerProduct($recipeRow);
            $totalQty = $item->qty * $qtyPerProduct;
            
            if (!isset($cartIngredientUsage[$ingredientId])) {
                $cartIngredientUsage[$ingredientId] = 0.0;
            }
            $cartIngredientUsage[$ingredientId] += $totalQty;
        }
    }
    
    return $cartIngredientUsage;
}

private function recipeIngredientQuantityPerProduct(ProductIngredient $recipeRow): float
{
    $baseQuantity = (float) $recipeRow->quantity;
    $lossQuantity = $baseQuantity * ((float) $recipeRow->loss_pct / 100);

    return round($baseQuantity + $lossQuantity, 4);
}

    private function tables()
    {
        return DiningTable::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name', 'branch_id', 'status')
            ->orderBy('name')
            ->get();
    }

    private function waiters()
    {
        return User::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function customers()
    {
        return Customer::query()
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function registers()
    {
        return PosRegister::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->select('id', 'name', 'code', 'branch_id')
            ->orderBy('name')
            ->get();
    }
public function sendToKitchen(Request $request, PosSession $session)
{
    abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

    $session->load(['items.options']);

    if (!$session->items->count()) {
        return back()->withErrors([
            'general' => 'Add at least one item before sending to kitchen.',
        ]);
    }

    $validated = $request->validate([
        'customer_id' => ['nullable', 'integer'],
        'dining_table_id' => ['nullable', 'integer'],
        'channel' => ['nullable', Rule::in(['takeaway', 'dine_in', 'pick_up', 'drive_thru', 'pre_order', 'catering', 'pms'])],
        'waiter_name' => ['nullable', 'string', 'max:255'],
        'customer_name' => ['nullable', 'string', 'max:255'],
        'car_plate' => ['required_if:channel,drive_thru', 'nullable', 'string', 'max:255'],
        'car_description' => ['required_if:channel,drive_thru', 'nullable', 'string'],
        'scheduled_at' => ['required_if:channel,catering,pre_order','nullable', 'date'],
        'notes' => ['nullable', 'string'],
        'guest_count' => ['nullable', 'integer', 'min:1'],
        'pms_guest' => ['nullable', 'array'],
        'pms_guest.booking_id' => ['required_with:pms_guest', 'string', 'max:255'],
        'pms_guest.booking_reference' => ['nullable', 'string', 'max:255'],
        'pms_guest.customer_id' => ['nullable'],
        'pms_guest.guest_name' => ['nullable', 'string', 'max:255'],
        'pms_guest.room_key_id' => ['required_with:pms_guest', 'string', 'max:255'],
        'pms_guest.room_no' => ['nullable', 'string', 'max:255'],
        'pms_guest.room_name' => ['nullable', 'string', 'max:255'],
        'pms_guest.room_type' => ['nullable', 'string', 'max:255'],
        'pms_guest.currency_code' => ['nullable', 'string', 'max:10'],
    ]);

    $pmsGuestSnapshot = $this->normalizePmsGuestSnapshot($validated['pms_guest'] ?? $session->pms_guest_snapshot);
    $shouldPostPmsOrder = false;

    $ticketId = DB::transaction(function () use ($session, $validated, $pmsGuestSnapshot, &$shouldPostPmsOrder) {
        $session->update([
            'customer_id' => $validated['customer_id'] ?? $session->customer_id,
            'dining_table_id' => $validated['dining_table_id'] ?? $session->dining_table_id,
            'channel' => $validated['channel'] ?? $session->channel,
            'waiter_name' => $validated['waiter_name'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'car_plate' => $validated['car_plate'] ?? null,
            'car_description' => $validated['car_description'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'guest_count' => $validated['guest_count'] ?? 1,
            'pms_guest_snapshot' => $pmsGuestSnapshot,
        ]);

        $ticket = null;

        if ($session->editing_ticket_id) {
            $ticket = PosKitchenTicket::query()
                ->where('tenant_id', $this->tenantId())
                ->find($session->editing_ticket_id);
        }

        if ($ticket) {
            $ticket->load(['items.options']);

            $ticket->update([
                'pos_session_id' => $session->id,
                'pos_register_id' => $session->pos_register_id,
                'branch_id' => $session->branch_id,
                'customer_id' => $session->customer_id,
                'dining_table_id' => $session->dining_table_id,
                'channel' => $session->channel,
                'waiter_name' => $session->waiter_name,
                'customer_name' => $session->customer_name,
                'car_plate' => $session->car_plate,
                'car_description' => $session->car_description,
                'scheduled_at' => $session->scheduled_at,
                'notes' => $session->notes,
                'guest_count' => $session->guest_count,
                'pms_guest_snapshot' => $session->pms_guest_snapshot,
                'pms_booking_id' => $pmsGuestSnapshot['booking_id'] ?? null,
                'pms_room_key_id' => $pmsGuestSnapshot['room_key_id'] ?? null,
                'pms_posting_status' => $pmsGuestSnapshot ? 'pending' : null,
                'currency_mode' => $session->currency_mode,
                'currency_code' => $session->currency_code,
                'subtotal' => $session->subtotal,
                'tax_total' => $session->tax_total,
                'discount_total' => $session->discount_total,
                'grand_total' => $session->grand_total,
                'status' => 'pending',
                'sent_to_kitchen_at' => now(),
            ]);

            foreach ($ticket->items as $oldItem) {
                $oldItem->options()->delete();
                $oldItem->delete();
            }
        } else {
            $ticket = PosKitchenTicket::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $session->tenant_id,
                'pos_session_id' => $session->id,
                'pos_register_id' => $session->pos_register_id,
                'branch_id' => $session->branch_id,
                'customer_id' => $session->customer_id,
                'dining_table_id' => $session->dining_table_id,
                'channel' => $session->channel,
                'waiter_name' => $session->waiter_name,
                'customer_name' => $session->customer_name,
                'car_plate' => $session->car_plate,
                'car_description' => $session->car_description,
                'scheduled_at' => $session->scheduled_at,
                'notes' => $session->notes,
                'loyalty_reward_id' => $session->loyalty_reward_id,
'loyalty_points_redeemed' => (int) ($session->loyalty_points_redeemed ?? 0),
'loyalty_discount_total' => (float) ($session->loyalty_discount_total ?? 0),
                'guest_count' => $session->guest_count,
                'pms_guest_snapshot' => $session->pms_guest_snapshot,
                'pms_booking_id' => $pmsGuestSnapshot['booking_id'] ?? null,
                'pms_room_key_id' => $pmsGuestSnapshot['room_key_id'] ?? null,
                'pms_posting_status' => $pmsGuestSnapshot ? 'pending' : null,
                'paid_amount' => 0,
                'currency_mode' => $session->currency_mode,
                'currency_code' => $session->currency_code,
                'subtotal' => $session->subtotal,
                'tax_total' => $session->tax_total,
                'discount_total' => $session->discount_total,
                'grand_total' => $session->grand_total,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'sent_to_kitchen_at' => now(),
            ]);
        }

        foreach ($session->items as $sessionItem) {
            $ticketItem = PosKitchenTicketItem::create([
                'pos_kitchen_ticket_id' => $ticket->id,
                'product_id' => $sessionItem->product_id,
                'product_name' => $sessionItem->product_name,
                'image_url' => $sessionItem->image_url,
                'qty' => $sessionItem->qty,
                'unit_price' => $sessionItem->unit_price,
                'option_total' => $sessionItem->option_total,
                'line_subtotal' => $sessionItem->line_subtotal,
                'tax_total' => $sessionItem->tax_total,
                'line_total' => $sessionItem->line_total,
                'tax_snapshot' => $sessionItem->tax_snapshot,
                'notes' => $sessionItem->notes,
                'currency_mode' => $sessionItem->currency_mode,
                'currency_code' => $sessionItem->currency_code,
            ]);

            foreach ($sessionItem->options as $option) {
                PosKitchenTicketItemOption::create([
                    'pos_kitchen_ticket_item_id' => $ticketItem->id,
                    'option_name' => $option->option_name,
                    'option_type' => $option->option_type,
                    'value_label' => $option->value_label,
                    'value_input' => $option->value_input,
                    'price' => $option->price,
                    'price_type' => $option->price_type,
                ]);
            }
        }

        $shouldPostPmsOrder = (bool) $pmsGuestSnapshot;

        $this->resetSessionForNextOrder($session->fresh());

        return $ticket->id;
    });

    $this->broadcastKitchenOrderPlaced($ticketId);

    if ($shouldPostPmsOrder) {
        $this->postPmsOrder(PosKitchenTicket::query()->where('tenant_id', $this->tenantId())->find($ticketId));
    }

    return back()->with('success', 'Order has been sent to kitchen successfully.');
}

private function createKitchenTicketFromPaidSession(PosSession $session, array $payload): PosKitchenTicket
{
    $session->load(['items.options']);

    $session->update([
        'customer_id' => $payload['customer_id'] ?? $session->customer_id,
        'dining_table_id' => $payload['dining_table_id'] ?? $session->dining_table_id,
        'channel' => $payload['channel'] ?? $session->channel,
        'waiter_name' => $payload['waiter_name'] ?? $session->waiter_name,
        'customer_name' => $payload['customer_name'] ?? $session->customer_name,
        'car_plate' => $payload['car_plate'] ?? $session->car_plate,
        'car_description' => $payload['car_description'] ?? $session->car_description,
        'scheduled_at' => $payload['scheduled_at'] ?? $session->scheduled_at,
        'notes' => $payload['notes'] ?? $session->notes,
        'guest_count' => $payload['guest_count'] ?? $session->guest_count ?? 1,
        'pms_guest_snapshot' => $this->normalizePmsGuestSnapshot($payload['pms_guest'] ?? $session->pms_guest_snapshot),
    ]);

    $session = $session->fresh(['items.options']);
    $pmsGuestSnapshot = $this->normalizePmsGuestSnapshot($session->pms_guest_snapshot);

    $ticket = PosKitchenTicket::create([
        'uuid' => (string) Str::uuid(),
        'tenant_id' => $session->tenant_id,
        'loyalty_reward_id' => $session->loyalty_reward_id,
'loyalty_points_redeemed' => (int) ($session->loyalty_points_redeemed ?? 0),
'loyalty_discount_total' => (float) ($session->loyalty_discount_total ?? 0),
        'pos_session_id' => $session->id,
        'pos_register_id' => $session->pos_register_id,
        'branch_id' => $session->branch_id,
        'customer_id' => $session->customer_id,
        'dining_table_id' => $session->dining_table_id,
        'channel' => $session->channel,
        'waiter_name' => $session->waiter_name,
        'customer_name' => $session->customer_name,
        'car_plate' => $session->car_plate,
        'car_description' => $session->car_description,
        'scheduled_at' => $session->scheduled_at,
        'notes' => $session->notes,
        'guest_count' => $session->guest_count,
        'pms_guest_snapshot' => $session->pms_guest_snapshot,
        'pms_booking_id' => $pmsGuestSnapshot['booking_id'] ?? null,
        'pms_room_key_id' => $pmsGuestSnapshot['room_key_id'] ?? null,
        'pms_posting_status' => $pmsGuestSnapshot ? 'pending' : null,
        'currency_mode' => $session->currency_mode,
        'currency_code' => $session->currency_code,
        'subtotal' => $session->subtotal,
        'tax_total' => $session->tax_total,
        'discount_total' => $session->discount_total,
        'grand_total' => $session->grand_total,
        'status' => 'pending',
        'payment_status' => 'paid',
        'paid_amount' => $session->grand_total,
        'sent_to_kitchen_at' => now(),
    ]);

    foreach ($session->items as $sessionItem) {
        $ticketItem = PosKitchenTicketItem::create([
            'pos_kitchen_ticket_id' => $ticket->id,
            'product_id' => $sessionItem->product_id,
            'product_name' => $sessionItem->product_name,
            'image_url' => $sessionItem->image_url,
            'qty' => $sessionItem->qty,
            'unit_price' => $sessionItem->unit_price,
            'option_total' => $sessionItem->option_total,
            'line_subtotal' => $sessionItem->line_subtotal,
            'tax_total' => $sessionItem->tax_total,
            'line_total' => $sessionItem->line_total,
            'tax_snapshot' => $sessionItem->tax_snapshot,
            'notes' => $sessionItem->notes,
            'currency_mode' => $sessionItem->currency_mode,
            'currency_code' => $sessionItem->currency_code,
        ]);

        foreach ($sessionItem->options as $option) {
            PosKitchenTicketItemOption::create([
                'pos_kitchen_ticket_item_id' => $ticketItem->id,
                'option_name' => $option->option_name,
                'option_type' => $option->option_type,
                'value_label' => $option->value_label,
                'value_input' => $option->value_input,
                'price' => $option->price,
                'price_type' => $option->price_type,
            ]);
        }
    }

    return $ticket;
}

private function createHeldOrderFromSession(PosSession $session): PosKitchenTicket
{
    $session->load(['items.options']);
    $pmsGuestSnapshot = $this->normalizePmsGuestSnapshot($session->pms_guest_snapshot);

    $ticket = null;

    if ($session->editing_ticket_id) {
        $ticket = PosKitchenTicket::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $session->editing_ticket_id)
            ->where('payment_status', '!=', 'paid')
            ->first();
    }

    $payload = [
        'pos_session_id' => $session->id,
        'pos_register_id' => $session->pos_register_id,
        'branch_id' => $session->branch_id,
        'customer_id' => $session->customer_id,
        'dining_table_id' => $session->dining_table_id,
        'channel' => $session->channel,
        'waiter_name' => $session->waiter_name,
        'customer_name' => $session->customer_name,
        'car_plate' => $session->car_plate,
        'car_description' => $session->car_description,
        'scheduled_at' => $session->scheduled_at,
        'notes' => $session->notes,
        'guest_count' => $session->guest_count,
        'pms_guest_snapshot' => $session->pms_guest_snapshot,
        'pms_booking_id' => $pmsGuestSnapshot['booking_id'] ?? null,
        'pms_room_key_id' => $pmsGuestSnapshot['room_key_id'] ?? null,
        'pms_posting_status' => $pmsGuestSnapshot ? 'pending' : null,
        'paid_amount' => 0,
        'currency_mode' => $session->currency_mode,
        'currency_code' => $session->currency_code,
        'subtotal' => $session->subtotal,
        'tax_total' => $session->tax_total,
        'discount_total' => $session->discount_total,
        'grand_total' => $session->grand_total,
        'status' => 'held',
        'payment_status' => 'unpaid',
    ];

    if ($ticket) {
        $ticket->load('items.options');

        foreach ($ticket->items as $oldItem) {
            $oldItem->options()->delete();
            $oldItem->delete();
        }

        $ticket->update($payload);
    } else {
        $ticket = PosKitchenTicket::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $session->tenant_id,
        ], $payload));
    }

    foreach ($session->items as $sessionItem) {
        $ticketItem = PosKitchenTicketItem::create([
            'pos_kitchen_ticket_id' => $ticket->id,
            'product_id' => $sessionItem->product_id,
            'product_name' => $sessionItem->product_name,
            'image_url' => $sessionItem->image_url,
            'qty' => $sessionItem->qty,
            'unit_price' => $sessionItem->unit_price,
            'option_total' => $sessionItem->option_total,
            'line_subtotal' => $sessionItem->line_subtotal,
            'tax_total' => $sessionItem->tax_total,
            'line_total' => $sessionItem->line_total,
            'tax_snapshot' => $sessionItem->tax_snapshot,
            'notes' => $sessionItem->notes,
            'currency_mode' => $sessionItem->currency_mode,
            'currency_code' => $sessionItem->currency_code,
        ]);

        foreach ($sessionItem->options as $option) {
            PosKitchenTicketItemOption::create([
                'pos_kitchen_ticket_item_id' => $ticketItem->id,
                'option_name' => $option->option_name,
                'option_type' => $option->option_type,
                'value_label' => $option->value_label,
                'value_input' => $option->value_input,
                'price' => $option->price,
                'price_type' => $option->price_type,
            ]);
        }
    }

    return $ticket;
}

private function resetSessionForNextOrder(PosSession $session): void
{
    $session->load(['items.options']);

    $appliedGiftIds = [];
    if ($session->loyalty_gift_id) {
        $appliedGiftIds[] = $session->loyalty_gift_id;
    }
    foreach ($session->items as $item) {
        if ($item->loyalty_gift_id) {
            $appliedGiftIds[] = $item->loyalty_gift_id;
        }
    }

    if (!empty($appliedGiftIds)) {
        LoyaltyGift::whereIn('id', array_unique($appliedGiftIds))
            ->where('status', 'claimed')
            ->update(['status' => 'available']);
    }

    foreach ($session->items as $item) {
        $item->options()->delete();
        $item->delete();
    }

    $session->update([
        'editing_ticket_id' => null,
        'customer_id' => null,
        'dining_table_id' => null,
        'channel' => 'takeaway',
        'waiter_name' => null,
        'customer_name' => null,
        'car_plate' => null,
        'car_description' => null,
        'scheduled_at' => null,
        'notes' => null,
        'guest_count' => 1,
        'pms_guest_snapshot' => null,
        'discount_mode' => null,
        'discount_type' => null,
        'discount_value' => null,
        'promotion_discount_id' => null,
        'promotion_voucher_id' => null,
        'promotion_code' => null,
        'loyalty_reward_id' => null,
        'loyalty_gift_id' => null,
        'loyalty_points_redeemed' => 0,
        'loyalty_discount_total' => 0,
        'subtotal' => 0,
        'tax_total' => 0,
        'discount_total' => 0,
        'grand_total' => 0,
        'status' => 'open',
    ]);
}
public function fetchOrders(Request $request)
{
    $registerId = $request->integer('register_id');
    $branchId = $request->integer('branch_id');
    $search = trim((string) $request->string('search'));

    $query = PosKitchenTicket::query()
        ->with([
            'items.options',
            'register:id,name,branch_id',
            'register.branch:id,name',
            'table:id,name',
              'customer:id,name',
        ])
        ->where('tenant_id', $this->tenantId())
        ->whereNotIn('status', ['served', 'cancelled']);

    if ($registerId) {
        $query->where('pos_register_id', $registerId);
    } elseif ($branchId) {
        $query->where('branch_id', $branchId);
    }

    if ($request->filled('channel')) {
        $query->where('channel', (string) $request->string('channel'));
    }

    if ($request->filled('status')) {
        $query->where('status', (string) $request->string('status'));
    }

    if ($request->filled('payment_status')) {
        $query->where('payment_status', (string) $request->string('payment_status'));
    }

    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('uuid', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('waiter_name', 'like', "%{$search}%")
                ->orWhere('channel', 'like', "%{$search}%")
                ->orWhereHas('register', fn ($r) => $r->where('name', 'like', "%{$search}%"))
                ->orWhereHas('table', fn ($t) => $t->where('name', 'like', "%{$search}%"))
                ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', "%{$search}%"));
        });
    }

    $orders = $query->latest('sent_to_kitchen_at')->get();

    $heldOrders = $orders
        ->filter(fn ($order) => $order->status === 'held')
        ->values();

    $activeOrders = $orders
        ->filter(function ($order) {
            return $order->status !== 'held'
                && (!in_array($order->channel, ['pre_order', 'catering'], true)
                || !$order->scheduled_at
                || optional($order->scheduled_at)->isPast());
        })
        ->values();

    $upcomingOrders = $orders
        ->filter(function ($order) {
            return $order->status !== 'held'
                && in_array($order->channel, ['pre_order', 'catering'], true)
                && $order->scheduled_at
                && optional($order->scheduled_at)->isFuture();
        })
        ->values();

    return response()->json([
        'active_orders' => $activeOrders,
        'upcoming_orders' => $upcomingOrders,
        'held_orders' => $heldOrders,
    ]);
}

public function fetchOrderDetails(PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

    $ticket->load([
        'items.options',
        'register:id,name,branch_id',
        'register.branch:id,name',
        'table:id,name',
    ]);

    return response()->json([
        'order' => [
            'id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'order_number' => $ticket->id,
            'reference_no' => 'ORD-' . strtoupper(substr($ticket->uuid, 0, 8)),
            'branch' => $ticket->register?->branch?->name ?? '-',
            'status' => $ticket->status,
            'type' => $ticket->channel,
            'payment_status' => $ticket->payment_status ?? 'unpaid',
            'paid_amount' => $ticket->paid_amount ?? 0,
            'pms_posting_status' => $ticket->pms_posting_status,
            'pms_posted_at' => $ticket->pms_posted_at,
            'pms_response' => $ticket->pms_response,
            'pms_booking_id' => $ticket->pms_booking_id,
            'pms_room_key_id' => $ticket->pms_room_key_id,
            'pms_guest_snapshot' => $ticket->pms_guest_snapshot,
            'guest_count' => $ticket->guest_count,
            'created_by' => 'Super Admin',
            'waiter' => $ticket->waiter_name ?: '-',
            'cashier' => '-',
            'pos_register' => $ticket->register?->name ?? '-',
            'pos_session_id' => $ticket->pos_session_id,
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
            'customer_name' => $ticket->customer_name ?: 'Walk-In Customer',
            'customer_phone' => '-',
            'currency' => $ticket->currency_code,
            'currency_rate' => '1.0000',
            'cost_price' => 0,
            'revenue' => 0,
            'subtotal' => $ticket->subtotal,
            'tax_total' => $ticket->tax_total,
            'grand_total' => $ticket->grand_total,
            'notes' => $ticket->notes,
            'payments' => [],
            'items' => $ticket->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'status' => 'pending',
                    'unit_price' => $item->unit_price,
                    'qty' => $item->qty,
                    'subtotal' => $item->line_subtotal,
                    'tax_total' => $item->tax_total,
                    'line_total' => $item->line_total,
                    'cost_price' => 0,
                    'revenue' => 0,
                    'options' => $item->options->map(function ($option) {
                        return [
                            'label' => $option->option_name,
                            'value' => $option->value_label ?: $option->value_input ?: '-',
                            'price' => $option->price,
                        ];
                    })->values(),
                ];
            })->values(),
        ],
    ]);
}

public function checkPmsOrderPaymentStatus(Request $request, PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);
    $forceCheck = $request->boolean('force');

    if ($ticket->channel !== 'pms') {
        return response()->json([
            'status' => true,
            'continue_payment' => true,
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'message' => 'This is not a PMS order.',
        ]);
    }

    if (!$forceCheck && $ticket->payment_status === 'paid') {
        return response()->json([
            'status' => true,
            'continue_payment' => false,
            'payment_status' => 'paid',
            'message' => 'This order is already paid in POS.',
            'order' => $ticket->fresh(),
        ]);
    }

    $setting = PmsIntegrationSetting::query()
        ->where('vendor_id', $this->tenantId())
        ->where('active', true)
        ->first();

    if (!$setting) {
        return response()->json([
            'status' => false,
            'continue_payment' => true,
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'message' => 'No active PMS integration is configured for this vendor.',
        ]);
    }

    $guest = $this->normalizePmsGuestSnapshot($ticket->pms_guest_snapshot);
    $pmsResponse = $ticket->pms_response ?: [];
    $orderReference = data_get($pmsResponse, 'data.order_reference')
        ?: data_get($pmsResponse, 'order_reference')
        ?: 'POS-' . $ticket->id;
    $bookingId = $ticket->pms_booking_id ?: ($guest['booking_id'] ?? null);

    if (!filled($bookingId)) {
        return response()->json([
            'status' => false,
            'continue_payment' => true,
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'message' => 'Unable to sync PMS payment status: booking id is missing.',
        ]);
    }

    try {
        $response = (new PmsClient($setting))->getBooking($bookingId);
    } catch (RequestException $exception) {
        return response()->json([
            'status' => false,
            'continue_payment' => true,
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'message' => 'Unable to confirm PMS payment status. Continue POS payment.',
            'pms_response' => $exception->response?->json() ?: $exception->response?->body(),
        ]);
    } catch (\Throwable $exception) {
        return response()->json([
            'status' => false,
            'continue_payment' => true,
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'message' => 'Unable to confirm PMS payment status. Continue POS payment.',
        ]);
    }

    $posOrders = collect(data_get($response, 'data.pos_orders', []));
    $matchedOrder = $posOrders->first(function ($order) use ($ticket, $orderReference) {
        return (filled($orderReference) && (string) data_get($order, 'order_reference') === (string) $orderReference)
            || (filled($ticket->uuid) && (string) data_get($order, 'pos_order_uuid') === (string) $ticket->uuid)
            || (filled($ticket->id) && (string) data_get($order, 'pos_order_id') === (string) $ticket->id);
    });

    if (!$matchedOrder) {
        return response()->json([
            'status' => false,
            'continue_payment' => true,
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'message' => 'PMS booking found, but matching POS order was not found.',
            'pms_response' => $response,
        ]);
    }

    $paymentStatus = data_get($matchedOrder, 'payment_status');

    if (!in_array($paymentStatus, ['paid', 'unpaid', 'partial'], true)) {
        return response()->json([
            'status' => false,
            'continue_payment' => true,
            'payment_status' => $ticket->payment_status ?: 'unpaid',
            'message' => 'PMS returned an unknown payment status.',
            'pms_response' => $response,
        ]);
    }

    $paidAmount = round((float) (data_get($matchedOrder, 'paid_amount') ?? 0), 3);

    if ($paymentStatus === 'paid' && $paidAmount <= 0) {
        $paidAmount = round((float) (data_get($matchedOrder, 'total_amount') ?? $ticket->grand_total ?? 0), 3);
    }

    $ticket->update([
        'payment_status' => $paymentStatus,
        'paid_amount' => $paidAmount,
        'pms_response' => array_merge($ticket->pms_response ?: [], [
            'payment_status_check' => $response,
            'payment_status_order' => $matchedOrder,
            'payment_status_checked_at' => now()->toIso8601String(),
        ]),
    ]);

    return response()->json([
        'status' => true,
        'continue_payment' => $paymentStatus !== 'paid',
        'payment_status' => $paymentStatus,
        'message' => $paymentStatus === 'paid'
            ? 'PMS already marked this order as paid.'
            : 'PMS order is not paid yet. Continue payment.',
        'order' => $ticket->fresh(),
        'pms_response' => $response,
    ]);
}
public function cancelKitchenOrder(Request $request, PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

    $validated = $request->validate([
        'cancel_reason' => ['required', 'string', 'max:255'],
        'cancel_note' => ['nullable', 'string'],
    ]);

    DB::transaction(function () use ($ticket, $validated) {
        $ticket->refresh();

        $this->restoreLoyaltyRedemptionFromOrder($ticket, 'kitchen order cancellation');

        $ticket->update([
            'status' => 'cancelled',
            'cancel_reason' => $validated['cancel_reason'],
            'cancel_note' => $validated['cancel_note'] ?? null,
        ]);
    });

    $this->postPmsOrder($ticket->fresh(['items.options', 'customer', 'register.branch', 'table']));

    $freshTicket = $ticket->fresh();

    return response()->json([
        'message' => $freshTicket->pms_posting_status === 'failed'
            ? 'Order cancelled successfully, but PMS order sync failed and can be retried.'
            : 'Order cancelled successfully and loyalty points restored.',
    ]);
}
public function payKitchenOrder(PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

    $pmsGuestSnapshot = $this->normalizePmsGuestSnapshot($ticket->pms_guest_snapshot);

    DB::transaction(function () use ($ticket, $pmsGuestSnapshot) {
        $ticket = PosKitchenTicket::query()
            ->where('tenant_id', $this->tenantId())
            ->lockForUpdate()
            ->findOrFail($ticket->id);

        $wasPaid = $ticket->payment_status === 'paid';

        if (!$wasPaid) {
            $this->deductProductStockForTicket($ticket);
        }

        $ticket->update([
            'payment_status' => 'paid',
            'paid_amount' => $ticket->grand_total,
            'pms_booking_id' => $ticket->pms_booking_id ?: ($pmsGuestSnapshot['booking_id'] ?? null),
            'pms_room_key_id' => $ticket->pms_room_key_id ?: ($pmsGuestSnapshot['room_key_id'] ?? null),
            'pms_posting_status' => $pmsGuestSnapshot ? 'pending' : $ticket->pms_posting_status,
        ]);
    });

    $this->postPmsOrder($ticket->fresh(['items.options', 'customer', 'register.branch', 'table']));

    return response()->json([
        'message' => $ticket->fresh()->pms_posting_status === 'failed'
            ? 'Order paid successfully, but PMS order sync failed and can be retried.'
            : 'Order paid successfully.',
    ]);
}
private function clearSessionItemsOnly(PosSession $session): void
{
    $session->load(['items.options']);

    foreach ($session->items as $item) {
        $item->options()->delete();
        $item->delete();
    }
}

private function fillSessionFromTicket(PosSession $session, PosKitchenTicket $ticket): void
{
    $ticket->load(['items.options']);

    $session->update([
        'editing_ticket_id' => $ticket->id,
        'customer_id' => $ticket->customer_id,
        'dining_table_id' => $ticket->dining_table_id,
        'channel' => $ticket->channel ?: 'takeaway',
        'waiter_name' => $ticket->waiter_name,
        'customer_name' => $ticket->customer_name,
        'car_plate' => $ticket->car_plate,
        'car_description' => $ticket->car_description,
        'scheduled_at' => $ticket->scheduled_at,
        'notes' => $ticket->notes,
        'guest_count' => $ticket->guest_count ?: 1,
        'pms_guest_snapshot' => $ticket->pms_guest_snapshot,
        'discount_mode' => null,
        'discount_type' => null,
        'discount_value' => null,
        'promotion_discount_id' => null,
        'promotion_voucher_id' => null,
        'promotion_code' => null,
        'loyalty_reward_id' => null,
        'loyalty_points_redeemed' => 0,
        'loyalty_discount_total' => 0,
    ]);

    foreach ($ticket->items as $ticketItem) {
        $sessionItem = PosSessionItem::create([
            'pos_session_id' => $session->id,
            'product_id' => $ticketItem->product_id,
            'product_name' => $ticketItem->product_name,
            'image_url' => $ticketItem->image_url,
            'qty' => $ticketItem->qty,
            'unit_price' => $ticketItem->unit_price,
            'option_total' => $ticketItem->option_total,
            'line_subtotal' => $ticketItem->line_subtotal,
            'tax_total' => $ticketItem->tax_total,
            'line_total' => $ticketItem->line_total,
            'tax_snapshot' => $ticketItem->tax_snapshot,
            'notes' => $ticketItem->notes,
            'currency_mode' => $ticketItem->currency_mode ?: $session->currency_mode,
            'currency_code' => $ticketItem->currency_code ?: $session->currency_code,
        ]);

        foreach ($ticketItem->options as $option) {
            PosSessionItemOption::create([
                'pos_session_item_id' => $sessionItem->id,
                'option_name' => $option->option_name,
                'option_type' => $option->option_type,
                'value_label' => $option->value_label,
                'value_input' => $option->value_input,
                'price' => $option->price,
                'price_type' => $option->price_type,
            ]);
        }
    }

    $this->refreshSessionTotals($session->fresh());
}
public function editKitchenOrder(Request $request, PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

    $validated = $request->validate([
        'session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
    ]);

    $session = PosSession::query()
        ->where('tenant_id', $this->tenantId())
        ->where('id', $validated['session_id'])
        ->where('status', 'open')
        ->firstOrFail();

    if ((int) $session->pos_register_id !== (int) $ticket->pos_register_id) {
        return response()->json([
            'message' => 'This order belongs to another register.',
        ], 422);
    }

    DB::transaction(function () use ($session, $ticket) {
        $this->clearSessionItemsOnly($session);
        $this->fillSessionFromTicket($session, $ticket);
    });

    return response()->json([
        'message' => 'Order loaded into POS for editing.',
        'session_id' => $session->id,
        'session' => $session->fresh(['items.options', 'table:id,name', 'register:id,name,code,branch_id']),
    ]);
}
public function finalizePayment(Request $request, PosSession $session)
{
    abort_unless((int) $session->tenant_id === (int) $this->tenantId(), 404);

    $session->load(['items.options']);

    if (!$session->items->count()) {
        return back()->withErrors([
            'general' => 'Add at least one item before finalizing payment.',
        ]);
    }

    $this->refreshSessionTotals($session->fresh());
    $session = $session->fresh(['items']);

    $validated = $request->validate([
        'payment_mode' => ['required', Rule::in(['full', 'partial'])],
        'customer_given_amount' => ['nullable', 'numeric', 'min:0'],
        'print_bill' => ['nullable', 'boolean'],
        'fire_after_payment' => ['nullable', 'boolean'],
        'customer_id' => ['nullable', 'integer'],
        'dining_table_id' => ['nullable', 'integer'],
        'channel' => ['nullable', Rule::in(['takeaway', 'dine_in', 'pick_up', 'drive_thru', 'pre_order', 'catering', 'pms'])],
        'waiter_name' => ['nullable', 'string', 'max:255'],
        'customer_name' => ['nullable', 'string', 'max:255'],
        'car_plate' => ['required_if:channel,drive_thru', 'nullable', 'string', 'max:255'],
        'car_description' => ['required_if:channel,drive_thru', 'nullable', 'string'],
        'scheduled_at' => ['nullable', 'date'],
        'notes' => ['nullable', 'string'],
        'guest_count' => ['nullable', 'integer', 'min:1'],
        'payments' => ['required', 'array', 'min:1'],
        'payments.*.payment_method' => ['required', Rule::in(['cash', 'mobile_wallet', 'card', 'bank_transfer', 'gift_card', 'room_charge'])],
        'payments.*.amount' => ['required', 'numeric', 'min:0.001'],
        'payments.*.transaction_id' => ['nullable', 'string', 'max:255'],
        'payments.*.gift_card_code' => ['nullable', 'string', 'max:80'],
        'room_charge' => ['nullable', 'array'],
        'room_charge.booking_id' => ['required_with:room_charge', 'string', 'max:255'],
        'room_charge.room_key_id' => ['required_with:room_charge', 'string', 'max:255'],
        'room_charge.booking_reference' => ['nullable', 'string', 'max:255'],
        'room_charge.customer_id' => ['nullable'],
        'room_charge.guest_name' => ['nullable', 'string', 'max:255'],
        'room_charge.room_no' => ['nullable', 'string', 'max:255'],
        'room_charge.room_name' => ['nullable', 'string', 'max:255'],
        'room_charge.room_type' => ['nullable', 'string', 'max:255'],
        'room_charge.currency_code' => ['nullable', 'string', 'max:10'],
        'pms_guest' => ['nullable', 'array'],
        'pms_guest.booking_id' => ['required_with:pms_guest', 'string', 'max:255'],
        'pms_guest.booking_reference' => ['nullable', 'string', 'max:255'],
        'pms_guest.customer_id' => ['nullable'],
        'pms_guest.guest_name' => ['nullable', 'string', 'max:255'],
        'pms_guest.room_key_id' => ['required_with:pms_guest', 'string', 'max:255'],
        'pms_guest.room_no' => ['nullable', 'string', 'max:255'],
        'pms_guest.room_name' => ['nullable', 'string', 'max:255'],
        'pms_guest.room_type' => ['nullable', 'string', 'max:255'],
        'pms_guest.currency_code' => ['nullable', 'string', 'max:10'],
    ]);

    $payments = collect($validated['payments'])
        ->map(function ($row, $index) {
            return [
                'payment_method' => $row['payment_method'],
                'amount' => (float) $row['amount'],
                'transaction_id' => $row['transaction_id'] ?? null,
                'gift_card_code' => $row['gift_card_code'] ?? null,
                'sort_order' => $index,
            ];
        })
        ->filter(fn ($row) => $row['amount'] > 0)
        ->values();

    if ($payments->isEmpty()) {
        return back()->withErrors([
            'payments' => 'Add at least one payment row.',
        ]);
    }

    $grandTotal = (float) $session->grand_total;
    $totalPaid = (float) $payments->sum('amount');

    if ($totalPaid + 0.0001 < $grandTotal) {
        return back()->withErrors([
            'payments' => 'Current total paid must be equal to or greater than grand total.',
        ]);
    }

    $customerGivenAmount = max(
        (float) ($validated['customer_given_amount'] ?? $totalPaid),
        $totalPaid
    );

    $changeReturn = max(0, $customerGivenAmount - $grandTotal);
    $cashPaid = (float) $payments
        ->where('payment_method', 'cash')
        ->sum('amount');

    $usesRoomCharge = $payments->contains(fn ($payment) => $payment['payment_method'] === 'room_charge');
    $roomChargeAmount = (float) $payments
        ->where('payment_method', 'room_charge')
        ->sum('amount');

    if ($usesRoomCharge && empty($validated['room_charge'])) {
        return back()->withErrors([
            'payments' => 'Select a checked-in guest or room before charging to room.',
        ]);
    }

    $pmsGuestSnapshot = $this->normalizePmsGuestSnapshot(
        $validated['room_charge'] ?? $validated['pms_guest'] ?? $session->pms_guest_snapshot
    );

    $pmsSetting = null;

    if ($usesRoomCharge) {
        $pmsSetting = PmsIntegrationSetting::query()
            ->where('vendor_id', $this->tenantId())
            ->where('active', true)
            ->first();

        if (!$pmsSetting) {
            return back()->withErrors([
                'payments' => 'No active PMS integration is configured for this vendor.',
            ]);
        }
    }

 
    $firedTicketId = null;
    $invoiceForPms = null;
    $orderTicketForPms = null;

    DB::transaction(function () use (
        $session,
        $validated,
        $payments,
        $grandTotal,
        $totalPaid,
        $customerGivenAmount,
        $changeReturn,
        $cashPaid,
        $usesRoomCharge,
        $roomChargeAmount,
        $pmsGuestSnapshot,
        &$firedTicketId,
        &$invoiceForPms,
        &$orderTicketForPms
    ) {
        $transaction = PosTransaction::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'branch_id' => $session->branch_id,
            'customer_id' => $session->customer_id,
            'pos_register_id' => $session->pos_register_id,
            'pos_session_id' => $session->id,
            'user_id' => auth('vendor')->id(),
            'currency_mode' => $session->currency_mode,
            'currency_code' => $session->currency_code,
            'payment_mode' => $validated['payment_mode'],
            'total_products' => (int) $session->items->count(),
            'subtotal' => $session->subtotal,
            'discount_total' => $session->discount_total,
            'promotion_discount_id' => $session->promotion_discount_id,
            'promotion_voucher_id' => $session->promotion_voucher_id,
            'promotion_code' => $session->promotion_code,
            'loyalty_reward_id' => $session->loyalty_reward_id,
            'loyalty_points_redeemed' => (int) ($session->loyalty_points_redeemed ?? 0),
            'loyalty_discount_total' => (float) ($session->loyalty_discount_total ?? 0),
            'loyalty_points_earned' => 0,
            'tax_total' => $session->tax_total,
            'grand_total' => $grandTotal,
            'total_paid' => $totalPaid,
            'due_amount' => 0,
            'customer_given_amount' => $customerGivenAmount,
            'change_return' => $changeReturn,
            'print_bill' => (bool) ($validated['print_bill'] ?? false),
            'status' => 'paid',
            'paid_at' => now(),
            'notes' => $session->notes,
        ]);

        // Process applied loyalty gifts (session-level and item-level)
        $appliedGiftIds = [];
        if ($session->loyalty_gift_id) {
            $appliedGiftIds[] = $session->loyalty_gift_id;
        }
        foreach ($session->items as $item) {
            if ($item->loyalty_gift_id) {
                $appliedGiftIds[] = $item->loyalty_gift_id;
            }
        }

        if (!empty($appliedGiftIds)) {
            LoyaltyGift::whereIn('id', array_unique($appliedGiftIds))
                ->update([
                    'status' => 'used',
                    'pos_transaction_id' => $transaction->id,
                    'used_at' => now(),
                ]);
        }

        foreach ($payments as $payment) {
            $giftCard = null;
            $giftCardBefore = null;
            $giftCardAfter = null;

            if ($payment['payment_method'] === 'gift_card') {
                $code = trim((string) ($payment['gift_card_code'] ?? ''));

                if ($code === '') {
                    throw ValidationException::withMessages([
                        'payments' => 'Enter the gift card code.',
                    ]);
                }

                $giftCard = GiftCard::query()
                    ->where('tenant_id', $this->tenantId())
                    ->where('code', $code)
                    ->lockForUpdate()
                    ->first();

                if (!$giftCard) {
                    throw ValidationException::withMessages([
                        'payments' => "Gift card {$code} was not found.",
                    ]);
                }

                if ($giftCard->status !== 'active' || ($giftCard->expires_at && $giftCard->expires_at->isPast())) {
                    throw ValidationException::withMessages([
                        'payments' => "Gift card {$code} is not active.",
                    ]);
                }

                $usingSecondaryGiftCard = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode();
                $giftCardBalanceColumn = $usingSecondaryGiftCard ? 'secondary_current_balance' : 'current_balance';
                $giftCardBefore = (float) $giftCard->{$giftCardBalanceColumn};

                if ($giftCardBefore + 0.0001 < (float) $payment['amount']) {
                    throw ValidationException::withMessages([
                        'payments' => "Gift card {$code} does not have enough balance.",
                    ]);
                }

                $giftCardAfter = max(0, $giftCardBefore - (float) $payment['amount']);
                $giftCard->{$giftCardBalanceColumn} = $giftCardAfter;
                $giftCard->used_at = now();

                $baseBalanceAfter = (float) ($usingSecondaryGiftCard ? $giftCard->current_balance : $giftCardAfter);
                $secondaryBalanceAfter = (float) ($usingSecondaryGiftCard ? $giftCardAfter : ($giftCard->secondary_current_balance ?? 0));

                if ($baseBalanceAfter <= 0 && (!$giftCard->secondary_currency_code || $secondaryBalanceAfter <= 0)) {
                    $giftCard->status = 'used';
                }
                $giftCard->save();

                if ($giftCard->status === 'used' && $giftCard->gift_card_batch_id) {
                    $giftCard->batch()->increment('cards_used');
                }
            }

            $paymentModel = PosTransactionPayment::create([
                'pos_transaction_id' => $transaction->id,
                'payment_method' => $payment['payment_method'],
                'amount' => $payment['amount'],
                'transaction_id' => $payment['transaction_id'],
                'gift_card_id' => $giftCard?->id,
                'gift_card_code' => $giftCard?->code,
                'sort_order' => $payment['sort_order'],
            ]);

            if ($giftCard) {
                GiftCardTransaction::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $this->tenantId(),
                    'branch_id' => $session->branch_id,
                    'gift_card_id' => $giftCard->id,
                    'pos_transaction_id' => $transaction->id,
                    'pos_transaction_payment_id' => $paymentModel->id,
                    'created_by' => auth('vendor')->id(),
                    'currency_mode' => $session->currency_mode ?: 'base',
                    'currency_code' => $session->currency_code ?: ($usingSecondaryGiftCard ? $this->secondaryCurrencyCode() : $this->baseCurrencyCode()),
                    'type' => 'redeem',
                    'amount' => $payment['amount'],
                    'balance_before' => $giftCardBefore,
                    'balance_after' => $giftCardAfter,
                    'note' => 'POS sale payment',
                    'occurred_at' => now(),
                ]);
            }
        }

        $this->recordPromotionRedemption($session, $transaction);
        $this->recordLoyaltyEarn($session, $transaction);

        $editingTicket = null;

        if (!empty($session->editing_ticket_id)) {
            $editingTicket = PosKitchenTicket::query()
                ->where('tenant_id', $this->tenantId())
                ->lockForUpdate()
                ->find($session->editing_ticket_id);
        }

        if ($cashPaid > 0) {
            $before = (float) $session->current_balance;
            $after = $before + $cashPaid;

            PosCashMovement::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $this->tenantId(),
                'branch_id' => $session->branch_id,
                'pos_register_id' => $session->pos_register_id,
                'pos_session_id' => $session->id,
                'user_id' => auth('vendor')->id(),
                'direction' => 'in',
                'reason' => 'sale',
                'amount' => $cashPaid,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference' => $transaction->uuid,
                'notes' => 'POS sale payment',
                'currency_mode' => $session->currency_mode,
                'currency_code' => $session->currency_code,
                'occurred_at' => now(),
            ]);

            $session->update([
                'current_balance' => $after,
            ]);
        }

        $firedTicket = null;

        if ((bool) ($validated['fire_after_payment'] ?? false) && !$editingTicket) {
            $firedTicket = $this->createKitchenTicketFromPaidSession($session, $validated);
            $firedTicketId = $firedTicket->id;
            $orderTicketForPms = $firedTicket;
            $session = $session->fresh(['items.options', 'register.branch']);
        }

        $buyerName = $session->customer_name;

        if (!$buyerName && $session->customer_id) {
            $buyerName = Customer::query()
                ->where('tenant_id', $this->tenantId())
                ->whereKey($session->customer_id)
                ->value('name');
        }

        if (!$buyerName && $pmsGuestSnapshot) {
            $buyerName = $pmsGuestSnapshot['guest_name'] ?? null;
        }

        $paymentsPayload = $payments->map(fn ($payment) => [
            'method' => $payment['payment_method'],
            'amount' => $payment['amount'],
            'transaction_id' => $payment['transaction_id'] ?? null,
            'gift_card_code' => $payment['gift_card_code'] ?? null,
        ])->values()->all();

        $invoice = PosInvoice::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'pos_kitchen_ticket_id' => $editingTicket?->id ?? $firedTicket?->id,
            'pos_transaction_id' => $transaction->id,
            'branch_id' => $session->branch_id ?? null,
            'customer_id' => $session->customer_id ?? null,
            'invoice_no' => 'INV-' . str_pad((string) ((PosInvoice::max('id') ?? 0) + 1), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) ($transaction->id + 499), 5, '0', STR_PAD_LEFT),
            'seller_name' => optional(optional($session->register)->branch)->name ?? 'Company',
            'buyer_name' => $buyerName ?: 'Walk-In Customer',
            'type' => 'simplified',
            'status' => 'issued',
            'purpose' => 'original',
            'kind' => 'standard',
            'currency_code' => $session->currency_code ?: 'LKR',
            'currency_rate' => 1,
            'cost_price' => 0,
            'revenue' => $session->grand_total ?? 0,
            'subtotal' => $session->subtotal ?? 0,
            'tax_total' => $session->tax_total ?? 0,
            'discount_total' => $session->discount_total ?? 0,
            'discount_mode' => $session->discount_mode ?? null,
            'discount_type' => $session->discount_type ?? null,
            'discount_value' => $session->discount_value ?? null,
            'promotion_discount_id' => $session->promotion_discount_id ?? null,
            'promotion_voucher_id' => $session->promotion_voucher_id ?? null,
            'promotion_code' => $session->promotion_code ?? null,
            'loyalty_reward_id' => $session->loyalty_reward_id ?? null,
            'loyalty_points_redeemed' => (int) ($session->loyalty_points_redeemed ?? 0),
            'loyalty_discount_total' => (float) ($session->loyalty_discount_total ?? 0),
            'loyalty_points_earned' => (int) $transaction->fresh()->loyalty_points_earned,
            'total' => $session->grand_total ?? 0,
            'paid_amount' => $totalPaid ?? ($session->grand_total ?? 0),
            'refunded_amount' => 0,
            'net_paid' => $totalPaid ?? ($session->grand_total ?? 0),
            'payments' => $paymentsPayload,
            'notes' => $session->notes,
            'pms_posting_status' => $usesRoomCharge ? 'pending' : null,
            'pms_booking_id' => $usesRoomCharge ? ($validated['room_charge']['booking_id'] ?? null) : null,
            'pms_room_key_id' => $usesRoomCharge ? ($validated['room_charge']['room_key_id'] ?? null) : null,
            'pms_room_charge_amount' => $usesRoomCharge ? $roomChargeAmount : 0,
            'pms_guest_snapshot' => $usesRoomCharge ? $pmsGuestSnapshot : $this->normalizePmsGuestSnapshot($validated['pms_guest'] ?? $session->pms_guest_snapshot),
            'issued_at' => now(),
        ]);

        if ($usesRoomCharge) {
            $invoiceForPms = $invoice;
        }

        foreach ($session->items as $item) {
            PosInvoiceItem::create([
                'pos_invoice_id' => $invoice->id,
                'product_name' => $item->product_name,
                'status' => $firedTicket ? 'pending' : $session->status,
                'unit_price' => $item->unit_price,
                'qty' => $item->qty,
                'subtotal' => $item->line_subtotal,
                'tax_total' => $item->tax_total,
                'line_total' => $item->line_total,
                'cost_price' => 0,
                'revenue' => $item->line_total,
                'options' => collect($item->options ?? [])->map(function ($option) {
                    return [
                        'label' => $option->option_name,
                        'value' => $option->value_label ?: $option->value_input,
                        'price' => $option->price,
                    ];
                })->values()->all(),
            ]);
        }

        $this->deductProductStockForSession($session);

        $this->resetSessionForNextOrder($session->fresh());

        if ($editingTicket) {
            $editingTicket->update([
                'payment_status' => 'paid',
                'paid_amount' => $totalPaid ?? ($editingTicket->grand_total ?? 0),
                'pms_booking_id' => $editingTicket->pms_booking_id ?: ($pmsGuestSnapshot['booking_id'] ?? null),
                'pms_room_key_id' => $editingTicket->pms_room_key_id ?: ($pmsGuestSnapshot['room_key_id'] ?? null),
                'pms_posting_status' => $pmsGuestSnapshot ? 'pending' : $editingTicket->pms_posting_status,
            ]);
            $orderTicketForPms = $editingTicket;
        }
    });

    $this->broadcastKitchenOrderPlaced($firedTicketId);

    if ($invoiceForPms && $pmsSetting) {
        app(PmsRoomChargePoster::class)->post($invoiceForPms, $pmsSetting);
    }

    if ($orderTicketForPms) {
        $this->postPmsOrder($orderTicketForPms->fresh(['items.options', 'customer', 'register.branch', 'table']));
    }

    $message = 'Payment completed successfully.';

    if ($invoiceForPms && $invoiceForPms->fresh()->pms_posting_status === 'failed') {
        $message .= ' PMS room charge posting failed and can be retried from the invoice.';
    }

    if ($orderTicketForPms && $orderTicketForPms->fresh()->pms_posting_status === 'failed') {
        $message .= ' PMS order sync failed and can be retried from the order.';
    }

    return back()->with('success', $message);
}

private function recordLoyaltyEarn(PosSession $session, PosTransaction $transaction): void
{
    if (!$session->customer_id) {
        return;
    }

    $program = LoyaltyProgram::query()
        ->where('tenant_id', $this->tenantId())
        ->where('is_active', true)
        ->oldest()
        ->first();

    if (!$program || (float) $program->earning_rate <= 0) {
        return;
    }

    $account = LoyaltyCustomer::firstOrCreate(
        [
            'tenant_id' => $this->tenantId(),
            'customer_id' => $session->customer_id,
            'loyalty_program_id' => $program->id,
        ],
        [
            'loyalty_tier_id' => $this->tierForSpend($program, 0, $session->currency_mode)?->id,
            'points_balance' => 0,
            'lifetime_points' => 0,
            'lifetime_spend' => 0,
            'secondary_lifetime_spend' => 0,
        ]
    );

    $tier = $account->tier ?: $this->tierForSpend($program, (float) $account->lifetime_spend, $session->currency_mode);
    $multiplier = (float) ($tier?->multiplier ?? 1);
    $bonusPoints = 0;

    foreach ($this->activeLoyaltyPromotions($program, $session) as $promotion) {
        if ($promotion->type === 'multiplier') {
            $multiplier *= (float) ($promotion->multiplier ?: 1);
        }

        if (in_array($promotion->type, ['bonus_points', 'new_member'], true)) {
            $bonusPoints += (int) ($promotion->bonus_points ?: 0);
        }

        $promotion->increment('used_count');
    }

    $earned = (int) floor(((float) $session->grand_total * (float) $program->earning_rate * $multiplier) + $bonusPoints);
    if ($earned <= 0) {
        return;
    }

    $before = (int) $account->points_balance;
    $after = $before + $earned;
    $lifetimeSpendColumn = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()
        ? 'secondary_lifetime_spend'
        : 'lifetime_spend';
    $newSpend = (float) $account->{$lifetimeSpendColumn} + (float) $session->grand_total;
    $nextTier = $this->tierForSpend($program, $newSpend, $session->currency_mode);

    $account->update([
        'points_balance' => $after,
        'lifetime_points' => (int) $account->lifetime_points + $earned,
        $lifetimeSpendColumn => $newSpend,
        'loyalty_tier_id' => $nextTier?->id ?: $account->loyalty_tier_id,
        'last_earned_at' => now(),
    ]);

    LoyaltyTransaction::create([
        'uuid' => (string) Str::uuid(),
        'tenant_id' => $this->tenantId(),
        'customer_id' => $session->customer_id,
        'loyalty_program_id' => $program->id,
        'pos_transaction_id' => $transaction->id,
        'type' => 'earn',
        'description' => 'Points earned from completing order #' . $transaction->uuid . '.',
        'points' => $earned,
        'balance_before' => $before,
        'balance_after' => $after,
        'amount' => $session->grand_total,
        'currency_mode' => $session->currency_mode,
        'currency_code' => $session->currency_code,
        'expires_at' => $program->points_expire_after_days ? now()->addDays($program->points_expire_after_days) : null,
    ]);

    $transaction->update(['loyalty_points_earned' => $earned]);
}

private function tierForSpend(LoyaltyProgram $program, float $spend, ?string $currencyMode)
{
    $column = $currencyMode === 'secondary' && $this->secondaryCurrencyCode()
        ? 'secondary_minimum_spend'
        : 'minimum_spend';

    return $program->tiers()
        ->where('tenant_id', $this->tenantId())
        ->where('is_active', true)
        ->where(function ($query) use ($column, $spend) {
            $query->where($column, '<=', $spend);
            if ($column !== 'minimum_spend') {
                $query->orWhere(function ($query) use ($column, $spend) {
                    $query->whereNull($column)
                        ->where('minimum_spend', '<=', $spend);
                });
            }
        })
        ->orderByDesc($column)
        ->orderByDesc('minimum_spend')
        ->first();
}

private function activeLoyaltyPromotions(LoyaltyProgram $program, PosSession $session)
{
    return LoyaltyPromotion::query()
        ->where('tenant_id', $this->tenantId())
        ->where('loyalty_program_id', $program->id)
        ->where('is_active', true)
        ->where(function ($query) {
            $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })
        ->where(function ($query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })
        ->where(function ($query) {
            $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
        })
        ->get()
        ->filter(function ($promotion) use ($session) {
            $branchIds = array_filter(array_map('intval', $promotion->branch_ids ?? []));
            if ($branchIds && !in_array((int) $session->branch_id, $branchIds, true)) {
                return false;
            }

            $days = $promotion->available_days ?? [];
            if ($days && !in_array(strtolower(now()->format('l')), $days, true)) {
                return false;
            }

            $minimumSpend = $session->currency_mode === 'secondary' && $this->secondaryCurrencyCode()
                ? ($promotion->secondary_minimum_spend ?? $promotion->minimum_spend)
                : $promotion->minimum_spend;

            return $minimumSpend === null || (float) $session->grand_total + 0.0001 >= (float) $minimumSpend;
        });
}

private function broadcastKitchenOrderPlaced(?int $ticketId): void
{
    if (!$ticketId) {
        return;
    }

    $ticket = PosKitchenTicket::query()
        ->where('tenant_id', $this->tenantId())
        ->with([
            'items.options',
            'register:id,name,branch_id',
            'register.branch:id,name',
            'table:id,name',
        ])
        ->find($ticketId);

    if ($ticket) {
        try {
            event(new KitchenOrderPlaced($ticket));
        } catch (\Throwable $exception) {
            Log::warning('Kitchen order broadcast failed.', [
                'ticket_id' => $ticket->id,
                'tenant_id' => $ticket->tenant_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

public function confirmKitchenOrder(PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

    if ($ticket->status === 'cancelled') {
        return response()->json([
            'message' => 'Cancelled orders cannot be confirmed.',
        ], 422);
    }

    if ($ticket->status === 'served') {
        return response()->json([
            'message' => 'Completed orders cannot be confirmed again.',
        ], 422);
    }

    $ticket->update([
        'status' => 'confirmed',
    ]);

    return response()->json([
        'message' => 'Order confirmed successfully.',
    ]);
}
public function printOrder(PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

    $copy = request('copy', 'bill');
    $copy = in_array($copy, ['bill', 'waiter'], true) ? $copy : 'bill';

    $download = request()->boolean('download');
    $autoPrint = request()->boolean('print');

    $ticket->load([
        'items.options',
        'register.branch',
        'customer',
        'table',
    ]);

    $view = $copy === 'waiter'
        ? 'vendor.pos.prints.waiter'
        : 'vendor.pos.prints.bill';

    $payload = $this->buildReceiptPayload($ticket, $copy, $autoPrint);

    if ($download) {
        $filename = 'order-' . $ticket->id . '-' . $copy . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $payload);
            $pdf->setPaper([0, 0, 226.77, 1000], 'portrait'); // 80mm receipt width
            return $pdf->download($filename);
        }

        $html = view($view, $payload)->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="order-' . $ticket->id . '-' . $copy . '.html"',
        ]);
    }

    return response()->view($view, $payload);
}

private function normalizePmsGuestSnapshot(?array $guest): ?array
{
    if (!$guest) {
        return null;
    }

    $bookingId = $guest['booking_id'] ?? $guest['bookingId'] ?? null;
    $roomKeyId = $guest['room_key_id'] ?? $guest['roomKeyId'] ?? $guest['room_id'] ?? null;

    if (!filled($bookingId) || !filled($roomKeyId)) {
        return null;
    }

    return [
        'booking_id' => (string) $bookingId,
        'booking_reference' => isset($guest['booking_reference']) && filled($guest['booking_reference'])
            ? (string) $guest['booking_reference']
            : (string) $bookingId,
        'customer_id' => $guest['customer_id'] ?? null,
        'guest_name' => $guest['guest_name'] ?? $guest['name'] ?? $guest['customer_name'] ?? null,
        'room_key_id' => (string) $roomKeyId,
        'room_no' => $guest['room_no'] ?? $guest['room_number'] ?? null,
        'room_name' => $guest['room_name'] ?? null,
    ];
}

private function postPmsOrder(?PosKitchenTicket $ticket): void
{
    if (!$ticket) {
        return;
    }

    $guest = $this->normalizePmsGuestSnapshot($ticket->pms_guest_snapshot);

    if (!$guest || !filled($guest['booking_id']) || !filled($guest['room_key_id'])) {
        return;
    }

    $setting = PmsIntegrationSetting::query()
        ->where('vendor_id', $this->tenantId())
        ->where('active', true)
        ->first();

    if (!$setting) {
        $ticket->update([
            'pms_posting_status' => 'failed',
            'pms_response' => [
                'message' => 'No active PMS integration is configured for this vendor.',
            ],
        ]);

        return;
    }

    $ticket->update([
        'pms_booking_id' => $ticket->pms_booking_id ?: $guest['booking_id'],
        'pms_room_key_id' => $ticket->pms_room_key_id ?: $guest['room_key_id'],
        'pms_posting_status' => 'pending',
    ]);

    app(PmsOrderPoster::class)->post($ticket->fresh(['items.options', 'customer', 'register.branch', 'table']), $setting);
}

private function pmsGuestPrintData(?array $guest): array
{
    $guest = $this->normalizePmsGuestSnapshot($guest);

    if (!$guest) {
        return [
            'pmsGuestName' => null,
            'pmsRoomLabel' => null,
            'pmsBookingReference' => null,
        ];
    }

    $room = collect([
        $guest['room_no'] ?? null,
        $guest['room_name'] ?? null,
    ])->filter()->implode(' / ');

    return [
        'pmsGuestName' => $guest['guest_name'] ?? null,
        'pmsRoomLabel' => $room ?: null,
        'pmsBookingReference' => $guest['booking_reference'] ?? $guest['booking_id'] ?? null,
    ];
}

private function buildReceiptPayload(PosKitchenTicket $ticket, string $copy, bool $autoPrint = false): array
{
    $branch = data_get($ticket, 'register.branch');
    $customer = $ticket->customer;
    $pmsPrintData = $this->pmsGuestPrintData($ticket->pms_guest_snapshot);

    $items = $ticket->items->map(function ($item) {
        return [
            'product_name' => $item->product_name,
            'qty' => (float) $item->qty,
            'unit_price' => (float) $item->unit_price,
            'option_total' => (float) $item->option_total,
            'line_subtotal' => (float) $item->line_subtotal,
            'tax_total' => (float) $item->tax_total,
            'line_total' => (float) $item->line_total,
            'notes' => $item->notes,
            'options' => $item->options->map(function ($option) {
                return [
                    'label' => $option->option_name,
                    'value' => $option->value_label ?: $option->value_input ?: '-',
                    'price' => (float) $option->price,
                ];
            })->values(),
        ];
    })->values();

    return [
        'ticket' => $ticket,
        'copyType' => $copy,
        'autoPrint' => $autoPrint,
        'companyName' => $branch?->name ?: config('app.name', 'Restaurant POS'),
        'branchName' => $branch?->name ?: '',
        'addressLine' => collect([
            data_get($branch, 'address'),
            data_get($branch, 'city'),
            data_get($branch, 'country'),
        ])->filter()->implode(', '),
        'phone' => data_get($branch, 'phone'),
        'email' => data_get($branch, 'email'),
        'currency' => $ticket->currency_code ?: 'LKR',
        'customerName' => $ticket->customer_name ?: ($customer?->name ?: 'Walk-In Customer'),
        'customerPhone' => $customer?->phone,
        'pmsGuestName' => $pmsPrintData['pmsGuestName'],
        'pmsRoomLabel' => $pmsPrintData['pmsRoomLabel'],
        'pmsBookingReference' => $pmsPrintData['pmsBookingReference'],
        'items' => $items,
        'subtotal' => (float) $ticket->subtotal,
        'taxTotal' => (float) $ticket->tax_total,
        'discountTotal' => (float) $ticket->discount_total,
        'grandTotal' => (float) $ticket->grand_total,
        'dueAmount' => (float) $ticket->grand_total,
    ];
}

public function updateTicketWaiter(Request $request, PosKitchenTicket $ticket)
{
    abort_unless((int) $ticket->tenant_id === (int) $this->tenantId(), 404);

    $validated = $request->validate([
        'waiter_name' => ['nullable', 'string', 'max:255'],
    ]);

    $ticket->update([
        'waiter_name' => $validated['waiter_name'] ?? null,
    ]);

    return response()->json([
        'message' => 'Waiter has been successfully assigned to the table.',
        'ticket' => [
            'id' => $ticket->id,
            'waiter_name' => $ticket->waiter_name,
        ],
    ]);
}

}
