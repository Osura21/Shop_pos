<template>

    <Head title="POS Viewer" />

    <div class="pos-viewer">
        <div v-if="!selectedRegister" class="viewer-empty-card">
            <h2>No register found</h2>
            <p>Create a POS register first.</p>
            <Link :href="route('vendor.pos.registers.create')" class="viewer-primary-btn">Create Register</Link>
        </div>

        <template v-else-if="!session">
            <div class="viewer-empty-state">
                <div class="viewer-empty-state__card">
                    <div class="viewer-empty-state__icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>

                    <div class="viewer-empty-state__content">
                        <div class="viewer-empty-state__badge">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>Register Ready</span>
                        </div>

                        <h2>No Active Session for This Register</h2>
                        <p>
                            The selected POS register does not currently have an open session.
                            Open a new session with an opening float to start taking orders.
                        </p>

                        <div class="viewer-empty-state__meta">
                            <div class="viewer-empty-state__meta-item">
                                <span class="label">Register</span>
                                <strong>{{ selectedRegister?.name || '-' }}</strong>
                            </div>

                            <div class="viewer-empty-state__meta-item">
                                <span class="label">Branch</span>
                                <strong>
                                    {{
                                        branches.find((branch) => Number(branch.id) ===
                                            Number(selectedRegister?.branch_id))?.name || '-'
                                    }}
                                </strong>
                            </div>
                        </div>

                        <div class="viewer-empty-state__actions">
                            <button type="button" class="viewer-primary-btn" @click="showOpenSession = true">
                                <i class="bi bi-plus-circle"></i>
                                <span>Open Session</span>
                            </button>

                            <Link :href="route('vendor.pos.sessions.index')" class="viewer-secondary-btn">
                            <i class="bi bi-clock-history"></i>
                            <span>View Sessions</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="pos-grid">
                <section class="catalog-panel pos-card">
                    <div class="catalog-toolbar">
                        <div class="barcode-box" :class="{ 'barcode-box--loading': scannerLoading }">
                            <i class="bi bi-upc-scan"></i>
                            <input ref="barcodeInput" v-model="searchQuery" type="text" class="barcode-box__input"
                                placeholder="Scan barcode or search products..." autocomplete="off"
                                @keyup.enter="handleBarcodeScan" />
                            <span v-if="scannerLoading" class="barcode-box__status">Scanning</span>
                        </div>

                        <div class="category-strip">
                            <button type="button" class="category-chip"
                                :class="{ 'category-chip--active': !selectedCategoryId }"
                                @click="selectedCategoryId = null">
                                All Categories
                            </button>

                            <button v-for="category in filteredCategories" :key="category.id" type="button"
                                class="category-chip"
                                :class="{ 'category-chip--active': Number(selectedCategoryId) === Number(category.id) }"
                                @click="selectedCategoryId = category.id">
                                {{ category.name }}
                            </button>
                        </div>
                    </div>

                    <div v-if="productsLoading && (!loadedProducts.length || !filteredProducts.length)"
                        class="catalog-scroll">
                        <div class="product-grid">
                            <div v-for="n in 9" :key="'skeleton-' + n" class="product-card product-card--skeleton">
                                <div class="product-card__media skeleton-box"></div>
                                <div class="product-card__body">
                                    <div class="skeleton-line skeleton-line--title"></div>
                                    <div class="skeleton-line skeleton-line--price"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="filteredProducts.length" ref="catalogScroll" class="catalog-scroll"
                        @scroll.passive="handleCatalogScroll">
                        <div class="product-grid">
                            <button v-for="product in filteredProducts" :key="product.id" type="button"
                                class="product-card"
                                :class="[
                                    { 'product-card--loading': addingProductId === product.id },
                                    recipeStockClass(product),
                                ]"
                                :disabled="addItemForm.processing || isProductUnavailable(product)" @click="openProduct(product)">
                                <div class="product-card__media">
                                    <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                                        class="product-card__image" />
                                    <div v-else class="product-card__placeholder"><i class="bi bi-image"></i></div>
                                    <span v-if="productRecipeStockLabel(product)" class="product-card__stock-alert">
                                        {{ productRecipeStockLabel(product) }}
                                    </span>
                                </div>
                                <div class="product-card__body">
                                    <h3 class="product-card__name">{{ product.name }}</h3>
                                    <div class="product-card__price-row">
                                        <template v-if="isSpecialPriceActive(product)">
                                            <strong class="product-card__price product-card__price--special">
                                                {{ getCurrencySymbol(activeCurrencyCode) }} {{ money(productPrice(product)) }}
                                            </strong>
                                            <span class="product-card__price-original">
                                                {{ getCurrencySymbol(activeCurrencyCode) }} {{ money(normalPrice(product)) }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            <strong class="product-card__price">
                                                {{ getCurrencySymbol(activeCurrencyCode) }} {{ money(productPrice(product)) }}
                                            </strong>
                                        </template>
                                    </div>
                                    <p v-if="product.sku" class="product-card__stock-note">
                                        Barcode: {{ product.sku }}
                                    </p>
                                    <p class="product-card__stock-note" :class="{ 'product-card__stock-note--low': isShopStockLow(product) }">
                                        Stock: {{ trimQty(product.current_stock) }} {{ product.unit_type || 'pcs' }}
                                    </p>
                                </div>
                                <span v-if="addingProductId === product.id"
                                    class="product-card__loading">Adding...</span>
                            </button>

                            <template v-if="productsLoadingMore">
                                <div v-for="n in 6" :key="'scroll-skeleton-' + n"
                                    class="product-card product-card--skeleton">
                                    <div class="product-card__media skeleton-box"></div>
                                    <div class="product-card__body">
                                        <div class="skeleton-line skeleton-line--title"></div>
                                        <div class="skeleton-line skeleton-line--price"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div v-else class="empty-state empty-state--catalog">
                        <i class="bi bi-basket2"></i>
                        <h3>No products found</h3>
                        <p>Try another search or category.</p>
                    </div>
                </section>

                <section class="order-panel pos-card">
                    <div class="order-toolbar">
                        <div v-if="false" class="channel-strip-wrap">
                            <!-- Shop POS: restaurant order channels are hidden. -->
                            <div class="channel-strip" ref="channelStrip">
                                <button v-for="type in visibleOrderTypes" :key="type.key" type="button" class="channel-chip"
                                    :class="[
                                        channelChipClass(type.key),
                                        { 'channel-chip--active': metaForm.channel === type.key }
                                    ]" @click="setChannel(type.key)">
                                    <i :class="channelIcon(type.key)"></i>
                                    <span>{{ type.label }}</span>
                                </button>
                            </div>

                            <button v-if="showChannelLeftArrow" type="button" class="channel-strip-arrow-left"
                                @click="scrollChannelLeft" aria-label="Scroll order types left">
                                <i class="bi bi-chevron-left"></i>
                            </button>

                            <button v-if="showChannelRightArrow" type="button" class="channel-strip-arrow-right"
                                @click="scrollChannelRight" aria-label="Scroll order types right">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>

                        <div class="order-meta-row">
                            <div v-if="false" class="field-wrap field-wrap--meta">
                                <!-- Shop POS: waiter selection is hidden. -->
                                <SelectInput v-model="metaForm.waiter_name" label="Waiter" :options="waiterOptions"
                                    labelKey="label" valueKey="value" placeholder="Select Waiter"
                                    @update:modelValue="saveMeta" />
                            </div>

                            <div v-if="metaForm.channel !== 'pms'" class="field-wrap field-wrap--meta">
                                <SelectInput v-model="metaForm.customer_id" label="Customer" :options="customerOptions"
                                    labelKey="label" valueKey="value" placeholder="Select Customer"
                                    @update:modelValue="saveMeta" />
                            </div>

                            <button
                                v-if="metaForm.channel !== 'pms'"
                                type="button"
                                class="pos-customer-view-btn"
                                title="View customer details"
                                :disabled="!metaForm.customer_id"
                                @click="openCustomerDetails"
                            >
                                <i class="bi bi-eye"></i>
                            </button>

                            <button v-if="metaForm.channel !== 'pms'" type="button" class="pos-add-customer-btn"
                                title="Add customer" @click="openQuickCustomerModal">
                                <i class="bi bi-person-plus"></i>
                            </button>

                            <!-- <div v-if="metaForm.channel === 'dine_in'"
                                class="field-wrap field-wrap--meta field-wrap--table">
                                <SelectInput v-model="metaForm.dining_table_id" label="Dining Table"
                                    :options="tableOptions" labelKey="label" valueKey="value" placeholder="Select Table"
                                    @update:modelValue="saveMeta" />
                            </div> -->
                        </div>

                        <div v-if="selectedPmsGuest" class="pms-selected-bar">
                            <div class="pms-selected-bar__icon">
                                <i class="bi bi-building-check"></i>
                            </div>
                            <div class="pms-selected-bar__body">
                                <strong>{{ pmsGuestName(selectedPmsGuest) }}</strong>
                                <span>{{ pmsRoomLabel(selectedPmsGuest) }}</span>
                            </div>
                            <button type="button" class="pms-selected-bar__clear" @click="clearPmsGuest">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="order-stage">
                        <div v-if="!session.items || !session.items.length" class="empty-order">
                            <div class="empty-order__icon"><i class="bi bi-cup-hot"></i></div>
                            <h3>No Items in the Order</h3>
                            <p>Select dishes from the menu to start your order</p>
                        </div>

                        <div v-else class="order-list">
                            <article v-for="item in session.items" :key="item.id" class="order-item-card">
                                <div class="order-item-card__media">
                                    <img v-if="item.image_url" :src="item.image_url" :alt="item.product_name"
                                        class="order-item-card__image" />
                                    <div v-else class="order-item-card__placeholder"><i class="bi bi-image"></i></div>
                                </div>

                                <div class="order-item-card__content">
                                    <div class="order-item-card__head">
                                        <div>
                                            <h3 class="order-item-card__name">
                                                {{ item.product_name }}
                                                <span v-if="item.loyalty_gift_id" class="loyalty-item-tag">
                                                    <i class="bi bi-gift-fill"></i> Loyalty Reward
                                                </span>
                                            </h3>
                                            <div class="order-item-card__meta">
                                                <div class="order-item-qty-control">
                                                    <button type="button" @click="updateItemQty(item, Number(item.qty) - cartQtyStep(item))" :disabled="actionLoading.updatingItemId === item.id || Number(item.qty) <= minCartQty(item)">
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                    <span>{{ trimQty(item.qty) }}</span>
                                                    <button type="button" @click="updateItemQty(item, Number(item.qty) + cartQtyStep(item))" :disabled="actionLoading.updatingItemId === item.id || !canIncreaseCartItem(item)">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                                <span class="meta-dot">â€¢</span>
                                                Unit: {{ getCurrencySymbol(item.currency_code || activeCurrencyCode) }} {{
                                                    money(unitPriceWithOptions(item)) }}
                                            </div>
                                        </div>
                                        <strong class="order-item-card__total">{{ getCurrencySymbol(item.currency_code || activeCurrencyCode) }} {{ money(item.line_total) }}</strong>
                                    </div>

                                    <div class="order-item-card__sub">
                                        <span>Tax: {{ getCurrencySymbol(item.currency_code || activeCurrencyCode) }} {{
                                            money(item.tax_total) }}</span>
                                    </div>

                                    <div v-if="item.options && item.options.length" class="option-list">
                                        <span v-for="option in item.options" :key="option.id" class="option-pill">
                                            {{ option.option_name }}: {{ option.value_label || option.value_input || '-'
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <button type="button" class="remove-btn"
                                    :disabled="actionLoading.removingItemId === item.id" @click="removeItem(item.id)">
                                    <i
                                        :class="actionLoading.removingItemId === item.id ? 'bi bi-arrow-clockwise' : 'bi bi-trash'"></i>
                                </button>
                            </article>
                        </div>
                    </div>
                </section>

                <aside class="summary-panel pos-card">
                    <div class="summary-utility-grid">
                        <button v-if="false" type="button" class="summary-utility-card" @click="openTableViewer">
                        <!-- Shop POS: table viewer shortcut is hidden. -->
                        <i class="bi bi-grid-3x3-gap"></i>
                        <span>Table Viewer</span>
                        </button>
                        <button v-if="false" type="button" class="summary-utility-card" @click="showOrders = true">
                            <i class="bi bi-basket"></i>
                            <span>Orders</span>
                        </button>
                        <button v-if="false" type="button" class="summary-utility-card" @click="showCashMovement = true">
                            <i class="bi bi-cash-stack"></i>
                            <span>Cash Movement</span>
                        </button>
                    </div>

                       <div class="summary-form">
    <div
        v-if="false && isDineIn && selectedOrderTable"
        class="selected-table-strip"
    >
        <button
            type="button"
            class="selected-table-strip__body"
            @click="openTableViewer"
        >
            <div>
                <strong>{{ selectedOrderTable.name }}</strong>
                <span>{{ selectedOrderTable.zone || '-' }} Â· {{ selectedOrderTable.floor || '-' }}</span>
            </div>
        </button>

        <button
            type="button"
            class="selected-table-strip__remove"
            title="Remove table"
            @click="clearSelectedDiningTable"
        >
            <i class="bi bi-trash"></i>
        </button>
    </div>

    <button
        v-else-if="false && isDineIn"
        type="button"
        class="selected-table-strip selected-table-strip--empty"
        @click="openTableViewer"
    >
        <div>
            <strong>Select Table</strong>
            <span>No table selected</span>
        </div>

        <i class="bi bi-plus-lg"></i>
    </button>

    <div v-if="false && isDriveThru" class="field-wrap">
        <input v-model="metaForm.car_plate" type="text" class="field-control"
            placeholder="Car Plate" @blur="saveMeta" />
    </div>

                        <div v-if="false && isDriveThru" class="field-wrap">
                            <input v-model="metaForm.car_description" type="text" class="field-control"
                                placeholder="Car Description" @blur="saveMeta" />
                        </div>

                        <div v-if="false && isScheduledChannel" class="field-wrap">
                            <!-- <input v-model="metaForm.scheduled_at" type="datetime-local" class="field-control"
                                @change="saveMeta" /> -->
                                <DatePicker v-model="metaForm.scheduled_at" timeEnabled />
                        </div>

                        <div class="field-wrap">
                            <textarea v-model="metaForm.notes" class="field-control field-control--textarea"
                                placeholder="Notes" @blur="saveMeta"></textarea>
                        </div>

                        <div v-if="false && isDineIn" class="field-wrap">
                            <input v-model="metaForm.guest_count" type="number" min="1" class="field-control"
                                placeholder="Guest count" @blur="saveMeta" />
                        </div>
                    </div>

                    <div class="discount-switcher">
                        <button type="button" class="discount-switcher__btn"
                            :class="{ 'discount-switcher__btn--active': discountForm.discount_mode === 'discount' }"
                            @click="setDiscountMode('discount')">
                            <i class="bi bi-bag"></i><span>Discount</span>
                        </button>
                        <button type="button" class="discount-switcher__btn"
                            :class="{ 'discount-switcher__btn--active': discountForm.discount_mode === 'voucher' }"
                            @click="setDiscountMode('voucher')">
                            <i class="bi bi-ticket-perforated"></i><span>Voucher</span>
                        </button>
                    </div>

                    <div class="discount-row">
                        <div class="discount-row__field">
                            <SelectInput v-model="discountPresetIndex"
                                :label="discountForm.discount_mode === 'voucher' ? 'Voucher' : 'Discount'"
                                :options="discountPresetOptions" labelKey="label" valueKey="value"
                                :placeholder="discountForm.discount_mode === 'voucher' ? 'Select Voucher' : 'Select Discount'"
                                @change="onDiscountDropdownChange" />
                        </div>
                        <button type="button" class="apply-btn" :class="{ 'apply-btn--ready': hasSelectedPromotion }"
                            :disabled="actionLoading.discount || discountForm.processing" @click="applyDiscountPreset">
                            {{ actionLoading.discount || discountForm.processing ? 'Applying...' : 'Apply' }}
                        </button>
                    </div>

                   <div class="totals-card">
    <div class="totals-row">
        <span>Subtotal</span>
        <strong>{{ getCurrencySymbol(activeCurrencyCode) }} {{ money(session.subtotal) }}</strong>
    </div>

    <div class="totals-row">
        <span>{{ taxBreakdownRows.length ? 'Tax Total' : 'VAT' }}</span>
        <strong>{{ getCurrencySymbol(activeCurrencyCode) }} {{ money(session.tax_total) }}</strong>
    </div>

    <div v-if="taxBreakdownRows.length" class="tax-breakdown">
        <div v-for="tax in taxBreakdownRows" :key="tax.key" class="totals-row totals-row--tax-detail">
            <span>{{ tax.label }}</span>
            <strong>{{ getCurrencySymbol(activeCurrencyCode) }} {{ money(tax.amount) }}</strong>
        </div>
    </div>

    <div v-if="promotionDiscountTotal > 0" class="totals-row totals-row--discount">
        <span>{{session.discount_mode === 'discount' ? 'Discount' : 'Voucher Discount'}}</span>
        <strong>
            -{{ getCurrencySymbol(activeCurrencyCode) }} {{ money(promotionDiscountTotal) }}
            <button type="button" class="remove-deduction-btn" @click="clearPromotionDiscount" title="Remove Promotion">
                <i class="bi bi-trash"></i>
            </button>
        </strong>
    </div>

    <div v-if="loyaltyDiscountTotal > 0" class="totals-row totals-row--loyalty">
        <span>
            Loyalty Discount
            <small v-if="loyaltyPointsRedeemed > 0">({{ loyaltyPointsRedeemed }} Pts)</small>
        </span>
        <strong>
            -{{ getCurrencySymbol(activeCurrencyCode) }} {{ money(loyaltyDiscountTotal) }}
            <button type="button" class="remove-deduction-btn" @click="removeLoyaltyDiscount" title="Remove Loyalty Reward">
                <i class="bi bi-trash"></i>
            </button>
        </strong>
    </div>

    <div class="totals-row totals-row--grand">
        <span>Total</span>
        <strong>{{ getCurrencySymbol(activeCurrencyCode) }} {{ money(session.grand_total) }}</strong>
    </div>
</div>

                 <div class="summary-actions" :class="{ 'summary-actions--dine-in': isDineIn }">
    <button
        type="button"
        class="summary-action summary-action--cash"
        :disabled="!hasOrderItems || actionLoading.openPayment"
        :class="{ 'summary-action--disabled': !hasOrderItems || actionLoading.openPayment }"
        title="Cash payment (F12)"
        @click="openFinalizePayment('cash')"
    >
        <i class="bi bi-cash-stack"></i>
        <span>{{ actionLoading.openPayment ? 'Opening...' : 'Cash F12' }}</span>
    </button>

    <button
        type="button"
        class="summary-action summary-action--card"
        :disabled="!hasOrderItems || actionLoading.openPayment"
        :class="{ 'summary-action--disabled': !hasOrderItems || actionLoading.openPayment }"
        title="Card payment (F10)"
        @click="openFinalizePayment('card')"
    >
        <i class="bi bi-credit-card-2-front"></i>
        <span>{{ actionLoading.openPayment ? 'Opening...' : 'Card F10' }}</span>
    </button>

    <button
        type="button"
        class="summary-action summary-action--credit"
        :disabled="!hasOrderItems || actionLoading.openPayment"
        :class="{ 'summary-action--disabled': !hasOrderItems || actionLoading.openPayment }"
        title="Customer credit payment (F9)"
        @click="openFinalizePayment('credit')"
    >
        <i class="bi bi-person-vcard"></i>
        <span>{{ actionLoading.openPayment ? 'Opening...' : 'Credit F9' }}</span>
    </button>

    <button
        type="button"
        class="summary-action summary-action--cancel"
        :disabled="!hasOrderItems || actionLoading.cancel"
        :class="{ 'summary-action--disabled': !hasOrderItems || actionLoading.cancel }"
        @click="cancelOrder"
    >
        <i class="bi bi-trash"></i>
        <span>{{ actionLoading.cancel ? 'Cancelling...' : 'Cancel Order' }}</span>
    </button>

    <!-- <button
        type="button"
        class="summary-action summary-action--hold"
        :disabled="!hasOrderItems || actionLoading.hold"
        :class="{ 'summary-action--disabled': !hasOrderItems || actionLoading.hold }"
        @click="holdOrder"
    >
        <i class="bi bi-pause-circle"></i>
        <span>{{ actionLoading.hold ? 'Holding...' : 'Hold Order' }}</span>
    </button> -->
</div>
                </aside>
            </div>
        </template>

        <ProductOptionsModal :show="modalOpen" :product="selectedProduct" :currency-code="activeCurrencyCode"
            :currency-mode="metaForm.currency_mode" :submitting="addItemForm.processing" @close="closeModal"
            :initial-qty="selectedProductInitialQty" @confirm="addToCart" />

        <CashMovementOffcanvas :show="showCashMovement" :session-id="session?.id" :currency-code="activeCurrencyCode"
            @close="showCashMovement = false" />
        <CustomerDetailsOffcanvas
            :show="showCustomerDetails"
            :customer-id="metaForm.customer_id"
            :session-id="session?.id"
            :currency-code="activeCurrencyCode"
            @close="closeCustomerDetails"
            @toast="handleCustomerDrawerToast"
            @updated="refreshSessionSnapshot"
        />
        <OrdersOffcanvas ref="ordersOffcanvasRef" :show="showOrders" :session-id="session?.id"
            :register-id="selectedRegister?.id" :branch-id="metaForm.branch_id" @close="showOrders = false"
            @pay-order="restoreOrderAndOpenPayment" @edit-order="editExistingOrder" @view-order="openOrderDetails" />

        <div v-if="showTableViewer" class="table-viewer-backdrop" @click.self="closeTableViewer">
            <aside class="table-viewer-panel">
                <header class="table-viewer-header">
                    <div class="table-viewer-title">
                        <i class="bi bi-box-seam"></i>
                        <h3>Table Viewer</h3>
                    </div>
                    <button type="button" class="table-viewer-close" @click="closeTableViewer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </header>

                <div class="table-viewer-tools">
                    <div class="table-viewer-search">
                        <i class="bi bi-search"></i>
                        <input v-model="tableSearch" type="text" placeholder="Search here..." />
                    </div>
                    <button type="button" class="table-viewer-filter" :class="{ 'table-viewer-filter--active': showTableFilters }" @click="toggleTableFilters">
                        <i class="bi bi-funnel"></i>
                        <span>Filters</span>
                    </button>
                </div>

                <!-- Collapsible Filters Panel -->
                <div v-if="showTableFilters" class="table-viewer-filter-panel">
                    <div class="filter-group">
                        <SelectInput id="tableFilterStatus" v-model="tableFilterStatus" label="Status" :options="tableFilterStatusOptions" labelKey="label" valueKey="value" placeholder="Select Status" :clearable="false" />
                    </div>
                    <div class="filter-group">
                        <SelectInput id="tableFilterZone" v-model="tableFilterZone" label="Zone" :options="tableFilterZoneOptions" labelKey="label" valueKey="value" placeholder="Select Zone" :clearable="false" />
                    </div>
                    <div class="filter-group">
                        <SelectInput id="tableFilterFloor" v-model="tableFilterFloor" label="Floor" :options="tableFilterFloorOptions" labelKey="label" valueKey="value" placeholder="Select Floor" :clearable="false" />
                    </div>
                    <div class="table-viewer-filter-actions">
                        <button type="button" class="btn-clear-filters" @click="resetTableFilters">
                            Clear
                        </button>
                    </div>
                </div>

               <div v-if="tableViewerLoading" class="table-viewer-grid table-viewer-grid--skeleton">
    <div
        v-for="n in 10"
        :key="`table-skeleton-${n}`"
        class="table-card table-card--skeleton"
    >
        <div class="table-card__top">
            <div class="table-skeleton-line table-skeleton-line--name"></div>
            <div class="table-skeleton-pill"></div>
        </div>

        <div class="table-skeleton-line table-skeleton-line--meta"></div>
    </div>
</div>

<div v-else-if="filteredViewerTables.length" class="table-viewer-grid">
    <button
        v-for="table in filteredViewerTables"
        :key="table.id"
        type="button"
        class="table-card"
        @click="openTableDetails(table)"
    >
        <div class="table-card__top">
            <strong>{{ table.name }}</strong>

            <span class="table-status" :class="'table-status--' + table.status">
                {{ formatTableStatus(table.status) }}
            </span>
        </div>

        <p>{{ table.zone || '-' }} &bull; {{ table.floor || '-' }}</p>
    </button>
</div>

<div v-else class="table-viewer-empty">
    <i class="bi bi-grid-3x3-gap"></i>
    <strong>No tables found</strong>
    <span>Try another search or branch filter.</span>
</div>
            </aside>
        </div>



        <div v-if="showMergeModal" class="table-detail-backdrop" @click.self="showMergeModal = false">
            <div class="merge-modal">
                <header class="table-detail-header">
                    <div class="table-viewer-title">
                        <i class="bi bi-bezier2"></i>
                        <h3>Merge Table</h3>
                    </div>
                </header>
                <select v-model="mergeForm.type" class="merge-input">
                    <option value="">Merge Type</option>
                    <option value="billing">Billing</option>
                    <option value="capacity">Capacity</option>
                </select>
                <div class="merge-table-list">
                    <label v-for="table in mergeCandidateTables" :key="table.id" class="merge-table-option">
                        <input type="checkbox" :value="table.id" v-model="mergeForm.member_table_ids" />
                        <span>{{ table.name }} - {{ table.zone || '-' }} / {{ table.floor || '-' }}</span>
                    </label>
                </div>
                <div class="merge-modal-actions">
                    <button type="button" @click="showMergeModal = false">Cancel</button>
                    <button type="button" :disabled="mergeSubmitting" @click="submitTableMerge">
                        {{ mergeSubmitting ? 'Merging...' : 'Merge' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showPmsGuests" class="pms-offcanvas-backdrop" @click.self="showPmsGuests = false">
            <aside class="pms-offcanvas">
                <header class="pms-offcanvas__header">
                    <div>
                        <p class="pms-offcanvas__eyebrow">PMS</p>
                        <h3>Checked-In Guests</h3>
                        <span v-if="!pmsGuestsLoading && !pmsGuestsError" class="pms-offcanvas__count">
                            {{ filteredPmsGuests.length }} available
                        </span>
                    </div>

                    <button type="button" class="pms-offcanvas__close" @click="showPmsGuests = false">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </header>

                <div class="pms-offcanvas__tools">
                    <div class="pms-search">
                        <i class="bi bi-search"></i>
                        <input v-model="pmsGuestSearch" type="text" placeholder="Search guest, room or booking" />
                    </div>

                    <button type="button" class="pms-refresh" :disabled="pmsGuestsLoading" @click="loadPmsGuests(true)">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>{{ pmsGuestsLoading ? 'Loading' : 'Refresh' }}</span>
                    </button>
                </div>

                <div v-if="pmsGuestsLoading" class="pms-guest-list" aria-label="Loading checked-in guests">
                    <article v-for="n in 6" :key="`pms-guest-skeleton-${n}`" class="pms-guest-card pms-guest-card--skeleton">
                        <div class="pms-guest-card__icon pms-skeleton-block"></div>
                        <div class="pms-guest-card__content">
                            <div class="pms-skeleton-line pms-skeleton-line--name"></div>
                            <div class="pms-guest-card__details">
                                <span class="pms-skeleton-line"></span>
                                <span class="pms-skeleton-line pms-skeleton-line--wide"></span>
                                <span class="pms-skeleton-line"></span>
                                <span class="pms-skeleton-line"></span>
                            </div>
                        </div>
                        <div class="pms-skeleton-button"></div>
                    </article>
                </div>

                <div v-else-if="pmsGuestsError" class="pms-empty pms-empty--error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>{{ pmsGuestsError }}</span>
                </div>

                <div v-else-if="filteredPmsGuests.length" class="pms-guest-list">
                    <article v-for="guest in filteredPmsGuests" :key="pmsGuestKey(guest)" class="pms-guest-card"
                        :class="{ 'pms-guest-card--selected': selectedPmsGuestKey === pmsGuestKey(guest) }">
                        <div class="pms-guest-card__icon">
                            <i class="bi bi-building-check"></i>
                        </div>
                        <div class="pms-guest-card__content">
                            <strong>{{ pmsGuestName(guest) }}</strong>
                            <div class="pms-guest-card__details">
                                <span><b>Room Key</b> {{ pmsRoomKeyLabel(guest) }}</span>
                                <span class="pms-guest-card__room-type" :title="pmsRoomTypeLabel(guest)">
                                    <b>Room Type</b> {{ pmsRoomTypeLabel(guest) }}
                                </span>
                                <span><b>Booking ID</b> {{ pmsBookingLabel(guest) }}</span>
                                <span><b>Currency</b> {{ pmsCurrencyCode(guest) || '-' }}</span>
                            </div>
                            <em v-if="pmsCurrencyMismatch(guest)" class="pms-currency-warning">
                                Cannot place order: PMS currency {{ pmsCurrencyCode(guest) }} is different from POS currency {{ activeCurrencyCode }}.
                            </em>
                        </div>
                        <button type="button" class="pms-select-btn" :disabled="pmsCurrencyMismatch(guest)" @click="selectPmsGuest(guest)">
                            {{ pmsCurrencyMismatch(guest) ? 'Disabled' : (selectedPmsGuestKey === pmsGuestKey(guest) ? 'Selected' : 'Select') }}
                        </button>
                    </article>
                </div>

                <div v-else class="pms-empty">No checked-in guests found.</div>
            </aside>
        </div>

        <div v-if="showOpenSession" class="open-session-backdrop" @click.self="showOpenSession = false">
            <div class="open-session-modal">
                <div class="open-session-modal__header">
                    <div class="open-session-modal__header-content">
                        <div class="open-session-modal__badge">
                            <i class="bi bi-play-circle"></i>
                            <span>Start POS Session</span>
                        </div>

                        <h3 class="open-session-modal__title">Open Session</h3>
                        <p class="open-session-modal__subtitle">
                            Enter the opening float and an optional note to begin using this register.
                        </p>
                    </div>

                    <button type="button" class="open-session-modal__close" @click="showOpenSession = false">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="open-session-modal__body">
                    <div class="open-session-summary">
                        <div class="open-session-summary__item">
                            <span class="open-session-summary__label">Register</span>
                            <strong class="open-session-summary__value">
                                {{ selectedRegister?.name || '-' }}
                            </strong>
                        </div>

                        <div class="open-session-summary__item">
                            <span class="open-session-summary__label">Branch</span>
                            <strong class="open-session-summary__value">
                                {{
                                    branches.find(
                                        (branch) => Number(branch.id) === Number(selectedRegister?.branch_id)
                                    )?.name || '-'
                                }}
                            </strong>
                        </div>
                    </div>

                    <div class="open-session-field">
                        <label class="open-session-field__label">Opening Cash</label>
                        <div class="open-session-field__input-wrap">
                            <span class="open-session-field__prefix">{{ openingCashCurrencyCode }}</span>
                            <input v-model="openSessionForm.opening_float" type="number" step="0.001" min="0"
                                class="open-session-field__input" placeholder="0.000" />
                        </div>
                    </div>

                    <div class="open-session-field">
                        <label class="open-session-field__label">Notes</label>
                        <textarea v-model="openSessionForm.notes" class="open-session-field__textarea"
                            placeholder="Add a note for this opening session"></textarea>
                    </div>
                </div>

                <div class="open-session-modal__footer">
                    <button type="button" class="open-session-btn open-session-btn--secondary"
                        @click="showOpenSession = false">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancel</span>
                    </button>

                    <button type="button" class="open-session-btn open-session-btn--primary" @click="submitOpenSession"
                        :disabled="openSessionForm.processing">
                        <i class="bi bi-check2-circle"></i>
                        <span>
                            {{ openSessionForm.processing ? 'Submitting...' : 'Open Session' }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <FinalizePaymentModal :show="showFinalizePayment" :session="paymentSessionOverride || session" :currency-code="activeCurrencyCode"
        :fire-after-payment="finalizeShouldFireToKitchen" :fire-payload="paymentFirePayload"
        :selected-pms-guest="selectedPmsGuest" :initial-payment-method="selectedPaymentMethod"
        @close="closeFinalizePayment" @paid="onPaymentFinished" />

    <div v-if="showLoyaltySummary" class="loyalty-modal-backdrop" @click.self="showLoyaltySummary = false">
        <div class="loyalty-modal loyalty-modal--wide">
            <div class="loyalty-modal__header">
                <h3><i class="bi bi-gift"></i> Rewards & Points Summary</h3>
                <button type="button" aria-label="Close rewards modal" @click="showLoyaltySummary = false"><i class="bi bi-x-lg"></i></button>
            </div>
            <div v-if="loyaltyLoading" class="loyalty-empty">
                <span class="loyalty-loader"></span>
                <strong>Loading rewards...</strong>
            </div>
            <template v-else>
                <div v-if="loyaltySummary.customer" class="loyalty-hero">
                    <div class="loyalty-hero__customer">
                        <span class="loyalty-tier-avatar">
                            <img v-if="loyaltyTierIconUrl" :src="loyaltyTierIconUrl" :alt="loyaltyTierName" />
                            <i v-else class="bi bi-award-fill"></i>
                        </span>
                        <div>
                            <strong>{{ loyaltySummary.customer.name }}</strong>
                            <span>{{ loyaltySummary.customer.phone || 'No phone' }}</span>
                        </div>
                    </div>
                    <div class="loyalty-hero__level">
                        <span>Current Level</span>
                        <strong>{{ loyaltyTierName }}</strong>
                        <img v-if="loyaltyTierIconUrl" :src="loyaltyTierIconUrl" :alt="loyaltyTierName" />
                        <i v-else class="bi bi-award-fill"></i>
                    </div>
                    <div class="loyalty-hero__points">
                        <span>Points Balance</span>
                        <strong>{{ loyaltySummary.account?.points_balance || 0 }} <small>Pts</small></strong>
                    </div>
                </div>
                <div v-else class="loyalty-empty">Select a customer to view loyalty points.</div>

                <div v-if="loyaltySummary.rewards?.length" class="loyalty-rewards">
                    <button v-for="reward in loyaltySummary.rewards" :key="reward.id" type="button" class="loyalty-reward-card" @click="redeemReward(reward.id)">
                        <div>
                            <strong>{{ reward.name }}</strong>
                            <span>{{ reward.description || reward.type }}</span>
                        </div>
                        <em>{{ reward.points_cost }} Pts</em>
                        <i class="bi bi-arrow-right-short"></i>
                    </button>
                </div>
                <div v-else-if="loyaltySummary.customer" class="loyalty-empty">No rewards available for this customer.</div>
            </template>
        </div>
    </div>

    <div v-if="showLoyaltyGifts" class="loyalty-modal-backdrop" @click.self="showLoyaltyGifts = false">
        <div class="loyalty-modal">
            <div class="loyalty-modal__header">
                <h3><i class="bi bi-gift"></i> View Available Gifts</h3>
                <button type="button" aria-label="Close gifts modal" @click="showLoyaltyGifts = false"><i class="bi bi-x-lg"></i></button>
            </div>
            <div v-if="loyaltyLoading" class="loyalty-empty">
                <span class="loyalty-loader"></span>
                <strong>Loading gifts...</strong>
            </div>
            <div v-else-if="loyaltyGifts.length" class="loyalty-rewards">
                <button v-for="gift in loyaltyGifts" :key="gift.id" type="button" class="loyalty-reward-card" @click="applyLoyaltyGift(gift.id)">
                    <div><strong>{{ gift.reward }}</strong><span>{{ gift.code || gift.type }} Â· {{ gift.valid_until || 'No expiry' }} ({{ gift.points_spent }} Pts)</span></div>
                    <em>Claim</em>
                </button>
            </div>
            <div v-else class="loyalty-empty loyalty-empty--large">
                <i class="bi bi-gift"></i>
                <strong>No Available Gifts</strong>
                <span>There are currently no rewards available for this customer.</span>
            </div>
        </div>
    </div>

    <div v-if="showQuickCustomerModal" class="quick-customer-backdrop" @click.self="closeQuickCustomerModal">
        <form class="quick-customer-modal" @submit.prevent="submitQuickCustomer">
            <div class="quick-customer-modal__header">
                <h3><i class="bi bi-person-plus"></i> Add Customer</h3>
                <button type="button" aria-label="Close add customer modal" @click="closeQuickCustomerModal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="quick-customer-modal__body">
                <div class="quick-customer-field">
                    <input id="quick_customer_name" v-model="quickCustomerForm.name" type="text"
                        placeholder="Name" autocomplete="name" />
                    <span v-if="quickCustomerErrors.name" class="quick-customer-error">{{ quickCustomerErrors.name }}</span>
                </div>

                <div class="quick-customer-phone">
                    <PhoneInput id="quick_customer_phone" v-model="quickCustomerForm.phone" label="Phone"
                        :error="quickCustomerErrors.phone || ''" />
                </div>
            </div>

            <div v-if="quickCustomerErrors.general" class="quick-customer-error quick-customer-error--general">
                {{ quickCustomerErrors.general }}
            </div>

            <div class="quick-customer-modal__footer">
                <button type="button" class="quick-customer-cancel" @click="closeQuickCustomerModal">Cancel</button>
                <button type="submit" class="quick-customer-create" :disabled="quickCustomerSaving">
                    {{ quickCustomerSaving ? 'Creating...' : 'Create' }}
                </button>
            </div>
        </form>
    </div>

    <TableDetailsOffcanvas
    :show="!!selectedTable"
    :table="selectedTable"
    :session-id="session?.id"
    :waiter-options="waiterOptions"
    :active-currency-code="activeCurrencyCode"
    @close="closeTableDetails"
    @updated="refreshSelectedTable"
    @pay-order="handleTablePayOrder"
    @edit-order="handleTableEditOrder"
    @view-order="handleTableViewOrder"
    @merge-table="handleTableMerge"
    @create-order="handleTableCreateOrder"
/>
</template>

<script>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import ProductOptionsModal from './ProductOptionsModal.vue'
import SelectInput from '@/Components/SelectInput.vue'
import CashMovementOffcanvas from './CashMovement/CashMovementOffcanvas.vue'
import CustomerDetailsOffcanvas from './CustomerDetailsOffcanvas.vue'
import OrdersOffcanvas from './OrdersOffcanvas.vue'
import DatePicker from '@/Components/DatePicker.vue'
import PhoneInput from '@/Components/PhoneInput.vue'
import FinalizePaymentModal from './FinalizePaymentModal.vue'
import TableDetailsOffcanvas from './TableDetailsOffcanvas.vue'
import { currencySymbol, normalizeCurrencyCode } from '@/Utils/currency'
import { confirmPrompt, error as alertError, selectPrompt, success as alertSuccess } from '@/Utils/modernAlert'

import axios from 'axios'

export default {
    name: 'PosViewer',
    layout: VendorAdminLayout,
    components: { Head, Link, ProductOptionsModal, DatePicker, SelectInput, PhoneInput, CashMovementOffcanvas, CustomerDetailsOffcanvas, OrdersOffcanvas, FinalizePaymentModal, TableDetailsOffcanvas },
    props: {
        session: { type: Object, default: null },
        selectedRegister: { type: Object, default: null },
        selectedBranchId: { type: [Number, String], default: null },
        selectedMenuId: { type: [Number, String], default: null },
        branches: { type: Array, default: () => [] },
        menus: { type: Array, default: () => [] },
        registers: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
        waiters: { type: Array, default: () => [] },
        customers: { type: Array, default: () => [] },
        tables: { type: Array, default: () => [] },
        orderTypes: { type: Array, default: () => [] },
        discountPresets: { type: Array, default: () => [] },
        voucherPresets: { type: Array, default: () => [] },
        currencyCode: { type: String, default: 'LKR' },
        baseCurrencyCode: { type: String, default: 'LKR' },
        secondaryCurrencyCode: { type: String, default: '' },
        hasSecondaryCurrency: { type: Boolean, default: false },
        selectedCurrencyMode: { type: String, default: 'base' },
    },
    data() {
        return {
            searchQuery: '',
            scannerLoading: false,
            selectedCategoryId: null,
            selectedProduct: null,
            selectedProductInitialQty: 1,
            modalOpen: false,
            discountPresetIndex: '',
            localDiscountPresets: [...(this.discountPresets || [])],
            localVoucherPresets: [...(this.voucherPresets || [])],
            localCustomers: [...(this.customers || [])],
            loadedProducts: [],
            productsLoading: false,
            productsLoadingMore: false,
            productsPage: 1,
            productsLastPage: 1,
            productsHasMore: false,
            productSearchTimer: null,
            productRequestKey: 0,
            showCashMovement: false,
            showOpenSession: false,
            showOrders: false,
            showTableViewer: false,
            tableViewerTables: [],
            tableViewerLoading: false,
            tableSearch: '',
            showTableFilters: false,
            tableFilterStatus: 'all',
            tableFilterZone: 'all',
            tableFilterFloor: 'all',
            selectedTable: null,
            showMergeModal: false,
            mergeSubmitting: false,
            mergeForm: {
                type: '',
                member_table_ids: [],
            },
            showPmsGuests: false,
            pmsGuests: [],
            pmsGuestsLoading: false,
            pmsGuestsLoaded: false,
            pmsGuestsError: '',
            pmsGuestSearch: '',
            selectedPmsGuest: this.session?.pms_guest_snapshot ?? null,
            showFinalizePayment: false,
            selectedPaymentMethod: 'cash',
            showCustomerDetails: false,
            paymentSessionOverride: null,
            showLoyaltyMenu: false,
            showLoyaltySummary: false,
            showLoyaltyGifts: false,
            showQuickCustomerModal: false,
            quickCustomerSaving: false,
            quickCustomerErrors: {},
            quickCustomerForm: {
                name: '',
                phone: '',
            },
            loyaltyLoading: false,
            loyaltySummary: { customer: null, account: null, rewards: [] },
            loyaltyGifts: [],
            showChannelRightArrow: false,
            showChannelLeftArrow: false,
            addingProductId: null,
          actionLoading: {
    sendToKitchen: false,
    openPayment: false,
    cancel: false,
    hold: false,
    discount: false,
    removingItemId: null,
    updatingItemId: null,
},

            metaForm: useForm({
                branch_id: this.session?.branch_id ?? this.selectedBranchId ?? '',
                menu_id: this.session?.menu_id ?? this.selectedMenuId ?? '',
                customer_id: this.session?.customer_id ?? '',
                dining_table_id: this.session?.dining_table_id ?? '',
                channel: this.session?.channel ?? 'takeaway',
                currency_mode: this.session?.currency_mode ?? this.selectedCurrencyMode ?? 'base',
                waiter_name: this.session?.waiter_name ?? '',
                customer_name: this.session?.customer_name ?? '',
                car_plate: this.session?.car_plate ?? '',
                car_description: this.session?.car_description ?? '',
                scheduled_at: this.formatDateTimeLocal(this.session?.scheduled_at),
                notes: this.session?.notes ?? '',
                guest_count: this.session?.guest_count && this.session.guest_count > 1 ? this.session.guest_count : '',

            }),
            discountForm: useForm({
                discount_mode: this.session?.discount_mode ?? 'discount',
                discount_type: this.session?.discount_type ?? null,
                discount_value: this.session?.discount_value ?? null,
            }),
            addItemForm: useForm({
                product_id: null,
                qty: 1,
                notes: '',
                selected_options: [],
            }),
            openSessionForm: useForm({
                pos_register_id: this.selectedRegister?.id ?? null,
                menu_id: this.selectedMenuId ?? '',
                opening_float: '',
                notes: '',
                currency_mode: this.selectedCurrencyMode ?? 'base',
                currency_code:
                    (this.selectedCurrencyMode === 'secondary' && this.secondaryCurrencyCode)
                        ? this.secondaryCurrencyCode
                        : this.baseCurrencyCode,
            }),
        }
    },
    computed: {

        selectedOrderTable() {
    const id = this.metaForm.dining_table_id || this.session?.dining_table_id

    if (!id) return null

    const allTables = [
        ...(this.tableViewerTables || []),
        ...(this.tables || []),
    ]

    return allTables.find((table) => Number(table.id) === Number(id)) || {
        id,
        name: `Table #${id}`,
        floor: '-',
        zone: '-',
        status: 'selected',
    }
},
        flash() {
            return this.$page.props.flash;
        },
        loyaltyDiscountTotal() {
            return Number(this.session?.loyalty_discount_total || 0)
        },
        loyaltyGiftItems() {
            return Array.isArray(this.session?.items) ? this.session.items.filter(item => !!item.loyalty_gift_id) : []
        },
        hasLoyaltyGiftItems() {
            return this.loyaltyGiftItems.length > 0
        },
        loyaltyPointsRedeemed() {
            return Number(this.session?.loyalty_points_redeemed || 0)
        },

promotionDiscountTotal() {
    const discountTotal = Number(this.session?.discount_total || 0)
    return Math.max(0, discountTotal - this.loyaltyDiscountTotal)
},
        hasOrderItems() {
            return Array.isArray(this.session?.items) && this.session.items.length > 0
        },
        openingCashCurrencyCode() {
            if (
                this.openSessionForm.currency_mode === 'secondary' &&
                this.secondaryCurrencyCode
            ) {
                return this.secondaryCurrencyCode
            }

            return this.baseCurrencyCode
        },
        isDriveThru() {
            return false
        },

        isDineIn() {
            return false
        },
        isScheduledChannel() {
            return false
        },
        visibleOrderTypes() {
            const allowed = ['takeaway', 'pick_up']
            const visible = Array.isArray(this.orderTypes)
                ? this.orderTypes.filter((type) => allowed.includes(type.key))
                : []

            return visible.length ? visible : this.orderTypes
        },
        canUseLoyaltyActions() {
            return this.metaForm.channel !== 'pms' && !!this.metaForm.customer_id
        },
        loyaltyTierName() {
            return this.loyaltySummary.account?.tier || 'Member'
        },
        loyaltyTierIconUrl() {
            return this.loyaltySummary.account?.tier_icon_url || ''
        },
        activeCurrencyCode() {
            if (this.metaForm.currency_mode === 'secondary' && this.secondaryCurrencyCode) return this.secondaryCurrencyCode
            return this.baseCurrencyCode || this.currencyCode
        },
        finalizeShouldFireToKitchen() {
            return !!this.session?.id && !this.session?.editing_ticket_id
        },
        paymentFirePayload() {
            return {
                customer_id: this.metaForm.customer_id || null,
                dining_table_id: this.metaForm.dining_table_id || null,
                channel: this.metaForm.channel,
                waiter_name: this.metaForm.waiter_name || null,
                customer_name: this.metaForm.customer_name || null,
                car_plate: this.metaForm.car_plate || null,
                car_description: this.metaForm.car_description || null,
                scheduled_at: this.metaForm.scheduled_at || null,
                notes: this.metaForm.notes || null,
                guest_count: this.metaForm.guest_count || 1,
                pms_guest: this.pmsGuestPayload(),
            }
        },
        selectedPmsGuestKey() {
            return this.selectedPmsGuest ? this.pmsGuestKey(this.selectedPmsGuest) : ''
        },
        waiterOptions() { return (this.waiters || []).map((waiter) => ({ label: waiter.name, value: waiter.name })) },
        customerOptions() {
            return (this.localCustomers || []).map((customer) => ({
                label: [customer.name, customer.phone ? `(${customer.phone})` : ''].filter(Boolean).join(' '),
                value: customer.id,
            }))
        },
        tableOptions() { return this.availableTables.map((table) => ({ label: table.name, value: table.id })) },
        filteredViewerTables() {
            const search = String(this.tableSearch || '').trim().toLowerCase()
            const status = this.tableFilterStatus || 'all'
            const zone = this.tableFilterZone || 'all'
            const floor = this.tableFilterFloor || 'all'

            return this.tableViewerTables.filter((table) => {
                const matchesSearch = !search || [
                    table.name,
                    table.floor,
                    table.zone,
                    table.status,
                ].some((value) => String(value || '').toLowerCase().includes(search))
                const matchesBranch = !this.metaForm.branch_id || Number(table.branch_id) === Number(this.metaForm.branch_id)
                const matchesStatus = status === 'all' || String(table.status || '').toLowerCase() === status.toLowerCase()
                const matchesZone = zone === 'all' || String(table.zone || '').toLowerCase() === zone.toLowerCase()
                const matchesFloor = floor === 'all' || String(table.floor || '').toLowerCase() === floor.toLowerCase()

                return matchesSearch && matchesBranch && matchesStatus && matchesZone && matchesFloor
            })
        },
        uniqueZones() {
            const zones = this.tableViewerTables
                .map(t => t.zone)
                .filter(Boolean)
            return [...new Set(zones)]
        },
        uniqueFloors() {
            const floors = this.tableViewerTables
                .map(t => t.floor)
                .filter(Boolean)
            return [...new Set(floors)]
        },
        tableFilterStatusOptions() {
            return [
                { label: 'All Statuses', value: 'all' },
                { label: 'Available', value: 'available' },
                { label: 'Occupied', value: 'occupied' },
                { label: 'Merged', value: 'merged' }
            ]
        },
        tableFilterZoneOptions() {
            return [
                { label: 'All Zones', value: 'all' },
                ...this.uniqueZones.map(z => ({ label: z, value: z }))
            ]
        },
        tableFilterFloorOptions() {
            return [
                { label: 'All Floors', value: 'all' },
                ...this.uniqueFloors.map(f => ({ label: f, value: f }))
            ]
        },
        mergeCandidateTables() {
            if (!this.selectedTable) return []
            return this.tableViewerTables.filter((table) =>
                Number(table.id) !== Number(this.selectedTable.id) &&
                ['available', 'occupied'].includes(String(table.status || '').toLowerCase())
            )
        },
        activePromotionPresets() {
            return this.discountForm.discount_mode === 'voucher'
                ? (this.localVoucherPresets || [])
                : (this.localDiscountPresets || [])
        },
        hasAvailablePromotions() {
            return this.activePromotionPresets.length > 1
        },
        hasSelectedPromotion() {
            const selected = this.activePromotionPresets?.[this.discountPresetIndex === '' ? -1 : Number(this.discountPresetIndex)]
            return !!selected?.id
        },
        discountPresetOptions() { return this.activePromotionPresets.map((preset, index) => ({ label: preset.label, value: String(index) })) },
        taxBreakdownRows() {
            const rows = new Map()

            ;(this.session?.items || []).forEach((item) => {
                ;(item.tax_snapshot || []).forEach((tax) => {
                    const name = tax?.name || 'Tax'
                    const rate = tax?.rate ?? ''
                    const key = `${name}:${rate}`
                    const existing = rows.get(key) || {
                        key,
                        label: rate !== '' ? `${name} ${Number(rate).toFixed(3).replace(/\.?0+$/, '')}%` : name,
                        amount: 0,
                    }

                    existing.amount += Number(tax?.amount || 0)
                    rows.set(key, existing)
                })
            })

            return Array.from(rows.values()).filter((row) => row.amount > 0)
        },
        filteredMenus() {
            return this.menus.filter(menu =>
                !this.metaForm.branch_id ||
                !Array.isArray(menu.branch_ids) ||
                !menu.branch_ids.length ||
                menu.branch_ids.map(Number).includes(Number(this.metaForm.branch_id))
            );
        },
        filteredCategories() {
            return this.categories.filter((category) => !this.metaForm.menu_id || Number(category.menu_id) === Number(this.metaForm.menu_id))
        },
        filteredProducts() {
            const search = String(this.searchQuery || '').trim().toLowerCase()

            return this.loadedProducts.filter((product) => {
                const matchesMenu = !this.metaForm.menu_id || Number(product.menu_id) === Number(this.metaForm.menu_id)
                const categoryIds = Array.isArray(product.category_ids) ? product.category_ids.map(Number) : []
                const matchesCategory = !this.selectedCategoryId || categoryIds.includes(Number(this.selectedCategoryId))
                const matchesSearch = !search || [
                    product.name,
                    product.sku,
                    product.barcode,
                ].some((value) => String(value || '').toLowerCase().includes(search))

                return matchesMenu && matchesCategory && matchesSearch
            })
        },
        availableTables() {
            return this.tables.filter((table) => {
                const matchesBranch = !this.metaForm.branch_id || Number(table.branch_id) === Number(this.metaForm.branch_id)
                const isCurrent = Number(table.id) === Number(this.metaForm.dining_table_id)
                const isOpen = !table.status || ['available', 'free', 'open'].includes(String(table.status).toLowerCase())
                return matchesBranch && (isOpen || isCurrent)
            })
        },
        filteredPmsGuests() {
            const search = String(this.pmsGuestSearch || '').trim().toLowerCase()

            if (!search) return this.pmsGuests

            return this.pmsGuests.filter((guest) => [
                guest?.guest_name,
                guest?.name,
                guest?.room_no,
                guest?.room_name,
                guest?.room_key_id,
                guest?.room_type,
                guest?.currency_code,
                guest?.booking_reference,
                guest?.booking_id,
            ].some((value) => String(value || '').toLowerCase().includes(search)))
        },
    },
    watch: {
        selectedCurrencyMode: {
            immediate: true,
            handler(value) {
                if (this.session?.id) return

                const mode =
                    value === 'secondary' && this.secondaryCurrencyCode ? 'secondary' : 'base'

                this.metaForm.currency_mode = mode
                this.openSessionForm.currency_mode = mode
                this.openSessionForm.currency_code =
                    mode === 'secondary' ? this.secondaryCurrencyCode : this.baseCurrencyCode
            },
        },
        discountPresets: {
            deep: true,
            handler(value) {
                this.localDiscountPresets = [...(value || [])]
            },
        },
        voucherPresets: {
            deep: true,
            handler(value) {
                this.localVoucherPresets = [...(value || [])]
            },
        },
        customers: {
            deep: true,
            handler(value) {
                this.localCustomers = [...(value || [])]
            },
        },
        'metaForm.customer_id'(value) {
            if (!value) {
                this.showLoyaltyMenu = false
                this.showCustomerDetails = false
            }
        },
        'metaForm.channel'(value) {
            if (value === 'pms') {
                this.showLoyaltyMenu = false
            }
        },
        searchQuery() {
            clearTimeout(this.productSearchTimer)
            this.productSearchTimer = setTimeout(() => {
                this.reloadProducts()
            }, 250)
        },
        selectedCategoryId() {
            this.reloadProducts()
        },
        'metaForm.menu_id'() {
            this.selectedCategoryId = null
            this.reloadProducts()
        },
        selectedRegister: {
            immediate: true,
            deep: true,
            handler(value) {
                this.openSessionForm.pos_register_id = value?.id ?? null
            },
        },
        selectedMenuId: {
            immediate: true,
            handler(value) {
                this.openSessionForm.menu_id = value ?? ''
            },
        },
        session: {
            immediate: true,
            deep: true,
            handler(newSession, oldSession) {
                if (!newSession?.id) {
                    this.loadedProducts = [];
                    this.productsLoading = false;
                    this.productsLoadingMore = false;
                    this.productsPage = 1;
                    this.productsLastPage = 1;
                    this.productsHasMore = false;
                    return;
                }

                this.syncFormsFromSession();

                const sessionChanged =
                    !oldSession?.id || Number(oldSession.id) !== Number(newSession.id);

                const menuChanged =
                    Number(oldSession?.menu_id || 0) !== Number(newSession?.menu_id || 0);

                if (sessionChanged || menuChanged || !this.loadedProducts.length) {
                    this.loadProducts();
                }
            },
        },
    },
    mounted() {
        if (this.session?.id) this.loadProducts()

        this.$nextTick(() => {
            this.checkChannelArrow()
            this.focusBarcodeInput()

            const el = this.$refs.channelStrip
            if (el) {
                el.addEventListener('scroll', this.handleChannelStripScroll, { passive: true })
            }
        })

        window.addEventListener('resize', this.checkChannelArrow)
        window.addEventListener('keydown', this.handlePosShortcut)
    },
    beforeUnmount() {
        const el = this.$refs.channelStrip
        if (el) {
            el.removeEventListener('scroll', this.handleChannelStripScroll)
        }

        clearTimeout(this.productSearchTimer)
        window.removeEventListener('resize', this.checkChannelArrow)
        window.removeEventListener('keydown', this.handlePosShortcut)
    },
    methods: {
  handlePosShortcut(event) {
    if (event.defaultPrevented || this.showFinalizePayment) return

    const key = String(event.key || '').toLowerCase()
    const target = event.target
    const isTyping = target && ['input', 'textarea', 'select'].includes(String(target.tagName || '').toLowerCase())

    if (isTyping && !['f9', 'f10', 'f12'].includes(key)) return

    const shortcuts = {
        f12: 'cash',
        f10: 'card',
        f9: 'credit',
    }

    if (!shortcuts[key]) return

    event.preventDefault()
    this.openFinalizePayment(shortcuts[key])
},
  clearSelectedDiningTable() {
    this.metaForm.dining_table_id = ''
    this.metaForm.channel = 'takeaway'

    this.saveMeta()

    this.$nextTick(() => {
        const active = this.$refs.channelStrip?.querySelector('.channel-chip--active')

        active?.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest',
        })

        this.checkChannelArrow()
    })
},
formatTableOrderStatus(value) {
    return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
},

tableNextStatusAction(order) {
    const status = String(order?.status || '').toLowerCase()

    if (status === 'pending') {
        return {
            label: 'Prepare',
            class: 'table-order-action--prepare',
            routeName: 'vendor.pos.kitchen.start-preparing',
        }
    }

    if (status === 'preparing') {
        return {
            label: 'Ready',
            class: 'table-order-action--ready',
            routeName: 'vendor.pos.kitchen.mark-ready',
        }
    }

    if (status === 'ready') {
        return {
            label: 'Complete',
            class: 'table-order-action--ready',
            routeName: 'vendor.pos.kitchen.mark-served',
        }
    }

    return null
},

async tableEditOrder(order) {
    if (!this.session?.id || !order?.id) return

    try {
        await axios.patch(route('vendor.pos.orders.edit-ticket', order.id), {
            session_id: this.session.id,
        })

        this.selectedTable = null
        this.showTableViewer = false

        router.reload({
            only: [
                'session',
                'selectedRegister',
                'selectedBranchId',
                'selectedMenuId',
                'currencyCode',
                'hasActiveSession',
            ],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                this.toastSuccess('Order loaded for editing.')
            },
        })
    } catch (error) {
        this.toastError(error?.response?.data?.message || 'Unable to load order for editing.')
    }
},

async tablePayOrder(order) {
    this.selectedTable = null
    this.showTableViewer = false
    await this.restoreOrderAndOpenPayment(order)
},

async tableCancelOrder(order) {
    if (!order?.id) return

    const result = await selectPrompt({
        title: 'Cancel Order',
        text: 'Select a reason to cancel this order.',
        options: {
            'Customer Request': 'Customer Request',
            'Item Not Available': 'Item Not Available',
            'Duplicate Order': 'Duplicate Order',
            'Kitchen Delay': 'Kitchen Delay',
            'Wrong Order': 'Wrong Order',
        },
        placeholder: 'Reason',
        confirmText: 'Submit',
        cancelText: 'Cancel',
    })

    if (!result.isConfirmed) return

    this.actionLoading.tableOrderActionId = order.id

    try {
        const { data } = await axios.patch(
            route('vendor.pos.orders.cancel-ticket', order.id),
            {
                cancel_reason: result.value,
                cancel_note: null,
            },
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            }
        )

        await this.refreshSelectedTable()
        this.toastSuccess(data?.message || 'Order cancelled successfully.')
    } catch (error) {
        this.toastError(error?.response?.data?.message || 'Unable to cancel order.')
    } finally {
        this.actionLoading.tableOrderActionId = null
    }
},

closeTableDetails() {
    this.selectedTable = null
    this.showMergeModal = false
},

openTableDetails(table) {
    this.selectedTable = table
},

async refreshSelectedTable() {
    const selectedId = this.selectedTable?.id

    await this.loadTableViewer()

    if (!selectedId) return

    const fresh = this.tableViewerTables.find(
        (table) => Number(table.id) === Number(selectedId)
    )

    this.selectedTable = fresh || null
},

handleTableViewOrder(order) {
    this.showOrders = true

    this.$nextTick(() => {
        this.$refs.ordersOffcanvasRef?.openViewModal?.(order)
    })
},

async handleTableEditOrder(order) {
    if (!this.session?.id || !order?.id) {
        this.toastError('No active POS session found for editing.')
        return
    }

    try {
        await axios.patch(route('vendor.pos.orders.edit-ticket', order.id), {
            session_id: this.session.id,
        })

        this.selectedTable = null
        this.showTableViewer = false

        router.reload({
            only: [
                'session',
                'selectedRegister',
                'selectedBranchId',
                'selectedMenuId',
                'currencyCode',
                'hasActiveSession',
            ],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                this.toastSuccess('Order loaded for editing.')
            },
        })
    } catch (error) {
        this.toastError(error?.response?.data?.message || 'Unable to load order for editing.')
    }
},

async handleTablePayOrder(order) {
    if (!order?.id) return

    this.selectedTable = null
    this.showTableViewer = false

    await this.restoreOrderAndOpenPayment(order)
},

handleTableMerge(table) {
    this.selectedTable = table || this.selectedTable
    this.showMergeModal = true
},

handleTableCreateOrder(table) {
    this.selectedTable = null
    this.selectTableForOrder(table)
},
        async onPaymentFinished() {
            this.showFinalizePayment = false
            this.selectedPaymentMethod = 'cash'
            this.paymentSessionOverride = null
            this.showOrders = true
            this.resetPosDetailsOnly()

            router.reload({
                only: ['session'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: async () => {
                    await this.$refs.ordersOffcanvasRef?.loadOrders?.(false)
                    this.reloadProducts()
                },
            })
        },
        closeFinalizePayment() {
            this.showFinalizePayment = false
            this.selectedPaymentMethod = 'cash'
            this.paymentSessionOverride = null
        },
        async openPmsGuests() {
            this.metaForm.channel = 'pms'
            this.showPmsGuests = true
            await this.loadPmsGuests()
        },
        async loadPmsGuests(force = false) {
            if (this.pmsGuestsLoading || (this.pmsGuestsLoaded && !force)) return

            this.pmsGuestsLoading = true
            this.pmsGuestsError = ''

            try {
                const { data } = await axios.get(route('vendor.pms.checked-in-guests'))
                this.pmsGuests = Array.isArray(data) ? data : (data?.data || data?.guests || [])
                this.pmsGuestsLoaded = true
            } catch (error) {
                this.pmsGuestsError = error?.response?.data?.message || 'Unable to load checked-in guests.'
            } finally {
                this.pmsGuestsLoading = false
            }
        },
        pmsGuestKey(guest) {
            return `${guest?.booking_id || guest?.booking_reference || 'booking'}:${guest?.room_key_id || guest?.room_id || guest?.room_no || 'room'}`
        },
        pmsGuestName(guest) {
            return guest?.guest_name || guest?.name || guest?.customer_name || 'Guest'
        },
        pmsRoomLabel(guest) {
            const room = [
                guest?.room_no || guest?.room_number || guest?.room?.number,
                guest?.room_name || guest?.room?.name,
            ].filter(Boolean).join(' / ')
            const booking = guest?.booking_reference || guest?.booking_id || ''

            return `${room || 'Room'}${booking ? ` (${booking})` : ''}`
        },
        pmsRoomKeyLabel(guest) {
            return guest?.room_key_id ?? guest?.roomKeyId ?? guest?.room_id ?? guest?.room?.id ?? '-'
        },
        pmsRoomTypeLabel(guest) {
            return guest?.room_type ?? guest?.roomType ?? guest?.room?.type ?? guest?.room?.room_type ?? guest?.room_name ?? guest?.room?.name ?? '-'
        },
        pmsBookingLabel(guest) {
            return guest?.booking_id ?? guest?.bookingId ?? guest?.id ?? '-'
        },
        pmsCurrencyCode(guest) {
            return String(
                guest?.currency_code ??
                guest?.currencyCode ??
                guest?.booking_currency_code ??
                guest?.bookingCurrencyCode ??
                guest?.booking_currency ??
                guest?.currency ??
                ''
            ).toUpperCase()
        },
        normalizeCurrencyCode,
        getCurrencySymbol(currencyCode) {
            return currencySymbol(currencyCode)
        },
        pmsCurrencyMismatch(guest) {
            const pmsCurrency = this.normalizeCurrencyCode(this.pmsCurrencyCode(guest))
            const posCurrency = this.normalizeCurrencyCode(this.activeCurrencyCode)

            return Boolean(pmsCurrency && posCurrency && pmsCurrency !== posCurrency)
        },
        pmsCurrencyMismatchMessage(guest) {
            return `Cannot place order: PMS currency ${this.pmsCurrencyCode(guest)} is different from POS currency ${this.activeCurrencyCode}.`
        },
        normalizePmsGuest(guest) {
            if (!guest) return null

            const bookingId = guest?.booking_id ?? guest?.bookingId ?? guest?.id ?? ''
            const roomKeyId = guest?.room_key_id ?? guest?.roomKeyId ?? guest?.room_id ?? guest?.room?.id ?? ''

            if (!bookingId || !roomKeyId) return null

            return {
                booking_id: String(bookingId),
                booking_reference: String(guest?.booking_reference ?? guest?.booking_no ?? bookingId),
                customer_id: guest?.customer_id ?? null,
                guest_name: this.pmsGuestName(guest),
                room_key_id: String(roomKeyId),
                room_no: String(guest?.room_no ?? guest?.room_number ?? guest?.room?.number ?? ''),
                room_name: String(guest?.room_name ?? guest?.room?.name ?? ''),
                room_type: String(this.pmsRoomTypeLabel(guest) === '-' ? '' : this.pmsRoomTypeLabel(guest)),
                currency_code: this.pmsCurrencyCode(guest),
            }
        },
        pmsGuestPayload() {
            return this.normalizePmsGuest(this.selectedPmsGuest)
        },
        selectPmsGuest(guest) {
            const normalized = this.normalizePmsGuest(guest)
            if (!normalized) return

            this.selectedPmsGuest = normalized
            this.metaForm.channel = 'pms'
            this.metaForm.customer_id = ''
            this.metaForm.customer_name = normalized.guest_name || this.metaForm.customer_name
            this.showPmsGuests = false
            this.saveMeta()
        },
        clearPmsGuest() {
            this.selectedPmsGuest = null
            if (this.metaForm.channel === 'pms') {
                this.metaForm.channel = 'takeaway'
            }
            this.saveMeta()
        },
      async restoreOrderAndOpenPayment(order, done = null) {
    if (!this.session?.id) {
        this.toastError('No active POS session found.')
        if (typeof done === 'function') done()
        return
    }

    if (!order?.id) {
        this.toastError('Invalid order selected.')
        if (typeof done === 'function') done()
        return
    }

    if (this.actionLoading.openPayment) {
        if (typeof done === 'function') done()
        return
    }

    this.actionLoading.openPayment = true

    try {
        if (order.channel === 'pms') {
            const { data } = await axios.get(route('vendor.pos.orders.pms-payment-status', order.id), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })

            if (data?.payment_status === 'paid' || data?.continue_payment === false) {
                await this.$refs.ordersOffcanvasRef?.loadOrders?.(false)
                this.toastSuccess(data?.message || 'PMS already marked this order as paid.')
                return
            }
        }

        const { data } = await axios.patch(route('vendor.pos.orders.edit-ticket', order.id), {
            session_id: this.session.id,
        })

        this.paymentSessionOverride = data?.session || this.session
        this.selectedPaymentMethod = 'cash'
        this.showOrders = false

        this.$nextTick(() => {
            this.showFinalizePayment = true
        })
    } catch (error) {
        this.toastError(error?.response?.data?.message || 'Unable to load order for payment.')
    } finally {
        this.actionLoading.openPayment = false
        if (typeof done === 'function') done()
    }
},
        openFinalizePayment(method = 'cash') {
            if (!this.session?.id || !this.hasOrderItems || this.actionLoading.openPayment) return
            if (method === 'credit' && !this.metaForm.customer_id) {
                this.toastError('Select a customer before using credit payment.')
                return
            }
            if (this.isDriveThru && (!this.metaForm.car_plate?.trim() || !this.metaForm.car_description?.trim())) {
                this.toastError('Car plate and car description are required for Drive Thru orders.')
                return
            }
            if(this.isScheduledChannel && !this.metaForm.scheduled_at) {
                const channelLabel = this.metaForm.channel === 'pre_order' ? 'Pre-Order' : 'Catering'
                this.toastError(`Scheduled date and time are required for ${channelLabel} orders.`)
                return
            }
            if(this.isScheduledChannel && this.metaForm.scheduled_at) {
                const selectedDate = new Date(this.metaForm.scheduled_at)
                const today = new Date()
                today.setHours(0, 0, 0, 0)

                if (!isNaN(selectedDate.getTime()) && selectedDate < today) {
                    this.toastError('Scheduled date must be today or future.')
                    return
                }
            }
            this.selectedPaymentMethod = method
            this.actionLoading.openPayment = true
            this.$nextTick(() => {
                this.showFinalizePayment = true
                this.actionLoading.openPayment = false
            })
        },
        openCustomerDetails() {
            if (!this.metaForm.customer_id || !this.session?.id) return
            this.showCustomerDetails = true
        },
        closeCustomerDetails() {
            this.showCustomerDetails = false
        },
        handleCustomerDrawerToast(payload) {
            if (payload?.type === 'success') {
                this.toastSuccess(payload.message || 'Action completed successfully.')
                return
            }

            if (payload?.type === 'error') {
                this.toastError(payload.message || 'Unable to complete customer action.')
            }
        },
        refreshSessionSnapshot() {
            router.reload({
                only: ['session'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.reloadProducts()
                },
            })
        },
        toggleLoyaltyMenu() {
            if (!this.canUseLoyaltyActions) {
                this.showLoyaltyMenu = false
                return
            }

            this.showLoyaltyMenu = !this.showLoyaltyMenu
        },
        openQuickCustomerModal() {
            this.quickCustomerErrors = {}
            this.quickCustomerForm = {
                name: '',
                phone: '',
            }
            this.showQuickCustomerModal = true
        },
        closeQuickCustomerModal() {
            if (this.quickCustomerSaving) return
            this.showQuickCustomerModal = false
            this.quickCustomerErrors = {}
        },
        async submitQuickCustomer() {
            if (this.quickCustomerSaving) return

            this.quickCustomerErrors = {}
            this.quickCustomerSaving = true
            const phone = this.normalizedQuickCustomerPhone()

            try {
                const { data } = await axios.post(route('vendor.customers.store'), {
                    quick_create: true,
                    name: this.quickCustomerForm.name,
                    phone,
                    customer_type: 'regular',
                    is_active: true,
                })

                const customer = data?.customer

                if (!customer?.id) {
                    throw new Error('Customer was created, but no customer data was returned.')
                }

                const exists = this.localCustomers.some((item) => Number(item.id) === Number(customer.id))
                if (!exists) {
                    this.localCustomers = [...this.localCustomers, customer]
                }

                this.metaForm.customer_id = customer.id
                this.showQuickCustomerModal = false

                this.$nextTick(() => {
                    this.saveMeta()
                })
            } catch (error) {
                const errors = error?.response?.data?.errors || {}

                this.quickCustomerErrors = {
                    name: errors.name?.[0] || '',
                    phone: errors.phone?.[0] || '',
                    general: errors.general?.[0] || error?.response?.data?.message || 'Unable to create customer.',
                }
            } finally {
                this.quickCustomerSaving = false
            }
        },
        normalizedQuickCustomerPhone() {
            const phone = String(this.quickCustomerForm.phone || '').trim()

            if (!phone || /^\+\d{1,4}$/.test(phone)) return null

            return phone
        },
        async openLoyaltySummary() {
            if (!this.canUseLoyaltyActions) return
            this.showLoyaltyMenu = false
            this.showLoyaltySummary = true
            await this.loadLoyaltySummary()
        },
        async openLoyaltyGifts() {
            if (!this.canUseLoyaltyActions) return
            this.showLoyaltyMenu = false
            this.showLoyaltyGifts = true
            await this.loadLoyaltyGifts()
        },
        async loadLoyaltySummary() {
            if (!this.session?.id) return
            this.loyaltyLoading = true
            try {
                const { data } = await axios.get(route('vendor.pos.loyalty.summary', this.session.id))
                this.loyaltySummary = data || { customer: null, account: null, rewards: [] }
            } catch (error) {
                this.toastError('Unable to load loyalty rewards.')
            } finally {
                this.loyaltyLoading = false
            }
        },
        async loadLoyaltyGifts() {
            if (!this.session?.id) return
            this.loyaltyLoading = true
            try {
                const { data } = await axios.get(route('vendor.pos.loyalty.gifts', this.session.id))
                this.loyaltyGifts = Array.isArray(data.gifts) ? data.gifts : []
            } catch (error) {
                this.toastError('Unable to load loyalty gifts.')
            } finally {
                this.loyaltyLoading = false
            }
        },
        redeemReward(rewardId) {
            if (!this.session?.id || !rewardId) return

            const reward = this.loyaltySummary.rewards?.find(r => r.id === rewardId)
            const isGift = reward && ['free_item', 'voucher_code'].includes(reward.type)

            router.post(route('vendor.pos.loyalty.redeem', this.session.id), { reward_id: rewardId }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: async () => {
                    if (isGift) {
                        this.showLoyaltySummary = false
                        this.showLoyaltyGifts = true
                        await this.loadLoyaltyGifts()
                    } else {
                        await this.loadLoyaltySummary()
                    }
                },
                onError: () => {
                    this.toastError('Unable to redeem this reward.')
                },
            })
        },
        applyLoyaltyGift(giftId) {
            if (!this.session?.id || !giftId) return
            router.post(route('vendor.pos.loyalty.gifts.apply', { session: this.session.id, gift: giftId }), {}, {
                preserveScroll: true,
                preserveState: false,
                onSuccess: async () => {
                    this.showLoyaltyGifts = false
                    router.reload({ only: ['session'], preserveScroll: true, preserveState: true })
                },
                onError: (errors) => {
                    const errorMsg = Object.values(errors)[0] || 'Unable to claim this gift.'
                    this.toastError(errorMsg)
                },
            })
        },
        checkChannelArrow() {
            this.$nextTick(() => {
                const el = this.$refs.channelStrip;

                if (!el) {
                    this.showChannelRightArrow = false;
                    this.showChannelLeftArrow = false;
                    return;
                }

                const maxScrollLeft = el.scrollWidth - el.clientWidth;

                const hasScroll = maxScrollLeft > 0;

                this.showChannelLeftArrow = hasScroll && el.scrollLeft > 0;

                this.showChannelRightArrow = hasScroll && el.scrollLeft < maxScrollLeft;
            });
        },

        scrollChannelRight() {
            const el = this.$refs.channelStrip
            if (!el) return

            el.scrollBy({
                left: 140,
                behavior: 'smooth',
            })
        },

        scrollChannelLeft() {
            const el = this.$refs.channelStrip;
            if (!el) return;

            el.scrollBy({
                left: -140,
                behavior: "smooth",
            });
        },

        handleChannelStripScroll() {
            const el = this.$refs.channelStrip;
            if (!el) return;

            const maxScrollLeft = el.scrollWidth - el.clientWidth;

            // RIGHT arrow: not at end
            this.showChannelRightArrow = el.scrollLeft < maxScrollLeft - 4;

            // LEFT arrow: not at start
            this.showChannelLeftArrow = el.scrollLeft > 4;
        },
        formatDateTimeLocal(value) {
            if (!value) return ''
            const date = new Date(value)
            if (Number.isNaN(date.getTime())) return ''
            const pad = (n) => String(n).padStart(2, '0')
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
        },
        async openTableViewer() {
            this.showTableViewer = true
            await this.loadTableViewer()
        },
        closeTableViewer() {
            this.showTableViewer = false
            this.selectedTable = null
            this.showMergeModal = false
        },
       async loadTableViewer() {
    if (!this.session?.id) return

    this.tableViewerLoading = true
    const startedAt = Date.now()

    try {
        const { data } = await axios.get(route('vendor.pos.tables.list', this.session.id), {
            params: {
                branch_id: this.metaForm.branch_id || undefined,
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })

        this.tableViewerTables = Array.isArray(data.tables) ? data.tables : []

        if (this.selectedTable?.id) {
            this.selectedTable =
                this.tableViewerTables.find(
                    (table) => Number(table.id) === Number(this.selectedTable.id)
                ) || this.selectedTable
        }
    } catch (error) {
        this.toastError(error?.response?.data?.message || 'Unable to load tables.')
    } finally {
        const elapsed = Date.now() - startedAt
        const minSkeletonTime = 350

        if (elapsed < minSkeletonTime) {
            await new Promise((resolve) => setTimeout(resolve, minSkeletonTime - elapsed))
        }

        this.tableViewerLoading = false
    }
},
        toggleTableFilters() {
            this.showTableFilters = !this.showTableFilters
        },
        resetTableFilters() {
            this.tableFilterStatus = 'all'
            this.tableFilterZone = 'all'
            this.tableFilterFloor = 'all'
        },
        openTableDetails(table) {
            this.selectedTable = table
        },
      selectTableForOrder(table) {
    if (!table?.id) return

    this.metaForm.channel = 'dine_in'
    this.metaForm.dining_table_id = table.id

    const exists = this.tableViewerTables.some(
        (row) => Number(row.id) === Number(table.id)
    )

    if (!exists) {
        this.tableViewerTables = [table, ...this.tableViewerTables]
    }

    this.saveMeta()
    this.closeTableViewer()
},
        openMergeModal() {
            this.mergeForm.type = ''
            this.mergeForm.member_table_ids = []
            this.showMergeModal = true
        },
        async submitTableMerge() {
            if (!this.session?.id || !this.selectedTable?.id || this.mergeSubmitting) return
            this.mergeSubmitting = true
            try {
                const { data } = await axios.post(route('vendor.pos.tables.merge', this.session.id), {
                    primary_table_id: this.selectedTable.id,
                    member_table_ids: this.mergeForm.member_table_ids,
                    type: this.mergeForm.type,
                })
                this.toastSuccess(data?.message || 'Tables merged successfully.')
                this.showMergeModal = false
                await this.loadTableViewer()
            } catch (error) {
                this.toastError(error?.response?.data?.message || 'Unable to merge tables.')
            } finally {
                this.mergeSubmitting = false
            }
        },
        formatTableStatus(status) {
            const labels = { available: 'Available', occupied: 'Occupied', merged: 'Merged' }
            return labels[String(status || '').toLowerCase()] || this.prettyTableLabel(status)
        },
        prettyTableLabel(value) {
            return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
        },
        formatTime(value) {
            if (!value) return '-'
            try {
                return new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            } catch (error) {
                return value
            }
        },
        itemSummary(items) {
            return (items || []).slice(0, 3).map((item) => item.product_name).join(', ')
        },
        productQueryParams(page = 1) {
            return {
                page,
                per_page: 18,
                search: String(this.searchQuery || '').trim() || undefined,
                category_id: this.selectedCategoryId || undefined,
                menu_id: this.metaForm.menu_id || undefined,
            }
        },
        focusBarcodeInput() {
            this.$refs.barcodeInput?.focus()
        },
        async handleBarcodeScan() {
            const barcode = String(this.searchQuery || '').trim()
            if (!barcode || !this.session?.id || this.scannerLoading || this.addItemForm.processing) return

            this.scannerLoading = true
            let shouldClearSearch = false

            try {
                const product = this.loadedProducts.find((row) => String(row.sku || '').trim() === barcode)
                    || await this.fetchProductByBarcode(barcode)

                if (!product) {
                    this.toastError(`No product found for barcode ${barcode}.`)
                    return
                }

                this.addScannedProduct(product)
                shouldClearSearch = true
            } catch (error) {
                this.toastError('Unable to scan product.')
            } finally {
                if (shouldClearSearch) {
                    this.searchQuery = ''
                }
                this.scannerLoading = false
                this.$nextTick(() => this.focusBarcodeInput())
            }
        },
        async fetchProductByBarcode(barcode) {
            const { data } = await axios.get(route('vendor.pos.products', this.session.id), {
                params: {
                    page: 1,
                    per_page: 1,
                    barcode,
                    menu_id: this.metaForm.menu_id || undefined,
                },
            })

            return Array.isArray(data.products) ? (data.products[0] || null) : null
        },
        addScannedProduct(product) {
            if (this.isProductUnavailable(product)) {
                this.toastError(`${product.name} is out of stock.`)
                return
            }

            this.openProduct(product)
        },
        async loadProducts({ page = 1, append = false } = {}) {
            if (!this.session?.id) return
            if (append && (!this.productsHasMore || this.productsLoadingMore)) return

            const requestKey = ++this.productRequestKey
            this.productsLoading = !append
            this.productsLoadingMore = append

            try {
                const { data } = await axios.get(route('vendor.pos.products', this.session.id), {
                    params: this.productQueryParams(page),
                })

                if (requestKey !== this.productRequestKey) return

                const products = Array.isArray(data.products) ? data.products : []
                this.loadedProducts = append ? [...this.loadedProducts, ...products] : products
                this.productsPage = Number(data.pagination?.current_page || page)
                this.productsLastPage = Number(data.pagination?.last_page || this.productsPage)
                this.productsHasMore = Boolean(data.pagination?.has_more)
            } catch (error) {
                if (!append) {
                    this.loadedProducts = []
                    this.productsPage = 1
                    this.productsLastPage = 1
                    this.productsHasMore = false
                }
            } finally {
                if (requestKey === this.productRequestKey) {
                    this.productsLoading = false
                    this.productsLoadingMore = false
                }
            }
        },
        reloadProducts() {
            if (!this.session?.id) return
            this.productsPage = 1
            this.productsLastPage = 1
            this.productsHasMore = false
            this.loadProducts({ page: 1 })
        },
        loadMoreProducts() {
            if (!this.productsHasMore || this.productsLoading || this.productsLoadingMore) return
            this.loadProducts({ page: this.productsPage + 1, append: true })
        },
        handleCatalogScroll(event) {
            const el = event?.target
            if (!el || this.productsLoading || this.productsLoadingMore || !this.productsHasMore) return

            const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight
            if (distanceFromBottom < 260) {
                this.loadMoreProducts()
            }
        },
        syncFormsFromSession() {
            this.metaForm.defaults({
                branch_id: this.session.branch_id ?? '',
                menu_id: this.session.menu_id ?? '',
                customer_id: this.session.customer_id ?? '',
                dining_table_id: this.session.dining_table_id ?? '',
                channel: this.session.channel ?? 'takeaway',
                currency_mode: this.session.currency_mode ?? 'base',
                waiter_name: this.session.waiter_name ?? '',
                customer_name: this.session.customer_name ?? '',
                car_plate: this.session.car_plate ?? '',
                car_description: this.session.car_description ?? '',
                scheduled_at: this.formatDateTimeLocal(this.session.scheduled_at),
                notes: this.session.notes ?? '',
                guest_count: this.session.guest_count && this.session.guest_count > 1 ? this.session.guest_count : '',
            })
            this.metaForm.reset()

            this.discountForm.defaults({
                discount_mode: this.session.discount_mode ?? 'discount',
                discount_type: this.session.discount_type ?? null,
                discount_value: this.session.discount_value ?? null,
            })
            this.discountForm.reset()
            this.selectedPmsGuest = this.normalizePmsGuest(this.session.pms_guest_snapshot) || null

            const promotionId = this.session.discount_mode === 'voucher'
                ? this.session.promotion_voucher_id
                : this.session.promotion_discount_id
            const selectedIndex = this.activePromotionPresets.findIndex((preset) => Number(preset.id) === Number(promotionId))
            this.discountPresetIndex = selectedIndex >= 0 ? String(selectedIndex) : ''
        },
        sendToKitchen() {
            if (!this.session?.id || !this.hasOrderItems || this.actionLoading.sendToKitchen) return
            if (this.selectedPmsGuest && this.pmsCurrencyMismatch(this.selectedPmsGuest)) {
                this.toastError(this.pmsCurrencyMismatchMessage(this.selectedPmsGuest))
                return
            }
            if (this.isDriveThru && (!this.metaForm.car_plate?.trim() || !this.metaForm.car_description?.trim())) {
                this.toastError('Car plate and car description are required for ' + this.metaForm.channel + ' orders.')
                return
            }
            if (this.isScheduledChannel && !this.metaForm.scheduled_at) {
                const channelLabel = this.metaForm.channel === 'pre_order' ? 'Pre-Order' : 'Catering'
                this.toastError(`Scheduled date and time are required for ${channelLabel} orders.`)
                return
            }
            if (this.isScheduledChannel && this.metaForm.scheduled_at) {
                const selectedDate = new Date(this.metaForm.scheduled_at)
                const today = new Date()
                today.setHours(0, 0, 0, 0)

                if (!isNaN(selectedDate.getTime()) && selectedDate < today) {
                    this.toastError('Scheduled date must be today or future.')
                    return
                }
            }
            this.actionLoading.sendToKitchen = true

            router.patch(
                route('vendor.pos.send-to-kitchen', this.session.id),
                {
                    customer_id: this.metaForm.customer_id || null,
                    dining_table_id: this.metaForm.dining_table_id || null,
                    channel: this.metaForm.channel,
                    waiter_name: this.metaForm.waiter_name || null,
                    customer_name: this.metaForm.customer_name || null,
                    car_plate: this.metaForm.car_plate || null,
                    car_description: this.metaForm.car_description || null,
                    scheduled_at: this.metaForm.scheduled_at || null,
                    notes: this.metaForm.notes || null,
                    guest_count: this.metaForm.guest_count || 1,
                    pms_guest: this.pmsGuestPayload(),
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.toastSuccess('Order sent to kitchen successfully.')
                        this.resetPosDetailsOnly()
                        this.reloadProducts()
                    },
                    onError: (errors) => {
                        this.toastError(this.firstError(errors) || 'Unable to send order to kitchen.')
                    },
                    onFinish: () => {
                        this.actionLoading.sendToKitchen = false
                    },
                }
            )
        },
        resetPosDetailsOnly() {
            this.metaForm.customer_id = ''
            this.metaForm.dining_table_id = ''
            this.metaForm.channel = 'takeaway'
            this.metaForm.waiter_name = ''
            this.metaForm.customer_name = ''
            this.metaForm.car_plate = ''
            this.metaForm.car_description = ''
            this.metaForm.scheduled_at = ''
            this.metaForm.notes = ''
            this.metaForm.guest_count = ''
            this.selectedPmsGuest = null

            this.discountPresetIndex = ''
            this.discountForm.discount_mode = 'discount'
            this.discountForm.discount_type = null
            this.discountForm.discount_value = null

            this.selectedProduct = null
            this.modalOpen = false
        },
        submitOpenSession() {
            if (!this.openSessionForm.pos_register_id) {
                this.toastError('No register selected.')
                return
            }

            const mode =
                this.selectedCurrencyMode === 'secondary' && this.secondaryCurrencyCode
                    ? 'secondary'
                    : 'base'

            this.openSessionForm.currency_mode = mode
            this.openSessionForm.currency_code =
                mode === 'secondary' ? this.secondaryCurrencyCode : this.baseCurrencyCode

            this.openSessionForm.post(route('vendor.pos.sessions.store'), {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    this.showOpenSession = false
                },
            })
        },
        money(value) {
            const numeric = Number(value || 0)
            return numeric.toFixed(2)
        },
        toNumber(value) {
            return Number(value || 0)
        },
        trimQty(value) {
            const numeric = Number(value || 0)
            return Number.isInteger(numeric) ? numeric : numeric.toFixed(3)
        },
        isSpecialPriceActive(product) {
            if (!product) return false;
            const hasSpecial = this.metaForm.currency_mode === 'secondary' && this.secondaryCurrencyCode
                ? (product.secondary_special_price !== null && product.secondary_special_price !== undefined && product.secondary_special_price !== '') || (product.special_price_type === 'percentage' && product.base_special_price !== null && product.base_special_price !== undefined && product.base_special_price !== '')
                : product.base_special_price !== null && product.base_special_price !== undefined && product.base_special_price !== '';

            if (!hasSpecial) return false;

            const now = new Date();

            if (product.special_price_start) {
                const start = new Date(product.special_price_start);
                if (now < start) return false;
            }

            if (product.special_price_end) {
                const end = new Date(product.special_price_end);
                if (now > end) return false;
            }

            return true;
        },
        normalPrice(product) {
            return this.metaForm.currency_mode === 'secondary' && this.secondaryCurrencyCode
                ? (product.secondary_price ?? product.base_price ?? 0)
                : (product.base_price ?? 0)
        },
        productPrice(product) {
            const normalPrice = this.normalPrice(product);

            if (this.isSpecialPriceActive(product)) {
                if (product.special_price_type === 'percentage') {
                    const percentage = this.metaForm.currency_mode === 'secondary' && this.secondaryCurrencyCode
                        ? Number(product.secondary_special_price ?? product.base_special_price ?? 0)
                        : Number(product.base_special_price ?? 0);
                    return normalPrice - (normalPrice * percentage / 100);
                } else {
                    return this.metaForm.currency_mode === 'secondary' && this.secondaryCurrencyCode
                        ? (product.secondary_special_price ?? product.base_special_price ?? normalPrice)
                        : (product.base_special_price ?? normalPrice);
                }
            }
            return normalPrice;
        },
        isProductUnavailable(product) {
            return Number(product?.current_stock ?? 0) <= 0
        },
        isShopStockLow(product) {
            const stock = Number(product?.current_stock ?? 0)
            const reorderLevel = Number(product?.reorder_level ?? 0)

            return reorderLevel > 0 && stock <= reorderLevel
        },
        canIncreaseCartItem(item) {
            if (!item?.product) {
                return true
            }

            const stock = Number(item.product.current_stock ?? 0)

            if (!Number.isFinite(stock)) {
                return true
            }

            const nextQty = Number(item?.qty ?? 0) + this.cartQtyStep(item)

            return stock > 0 && nextQty <= stock + 0.0001
        },
        isLooseItem(product) {
            return !!product?.is_loose_item
        },
        minCartQty(item) {
            return this.isLooseItem(item?.product) ? 0.001 : 1
        },
        cartQtyStep(item) {
            if (!this.isLooseItem(item?.product)) {
                return 1
            }

            const unit = item?.product?.unit_type || ''

            if (unit === 'kg' || unit === 'l') return 0.05
            if (unit === 'g' || unit === 'ml') return 50

            return 0.001
        },
        normalizeCartQty(item, qty) {
            const min = this.minCartQty(item)
            const normalized = Math.max(min, Number(qty || min))

            return this.isLooseItem(item?.product)
                ? Number(normalized.toFixed(3))
                : Math.round(normalized)
        },
        productRecipeStockLabel(product) {
            const stock = product?.recipe_stock

            if (stock.status === 'unavailable' || stock.status === 'low') {
                return stock.message || '';
            }
            // if (stock.can_make !== null && stock.can_make !== undefined) {
            //     return `${stock.can_make} available`;
            // }
            return '';

        },
        recipeStockClass(product) {
            const status = product?.recipe_stock?.status

            if (status === 'unavailable') return 'product-card--unavailable'
            if (status === 'low') return 'product-card--low-stock'

            return ''
        },
        unitPriceWithOptions(item) {
            return this.toNumber(item.unit_price) + this.toNumber(item.option_total)
        },
        channelIcon(channel) {
            const icons = {
                takeaway: 'bi bi-bag',
                dine_in: 'bi bi-cup-hot',
                pick_up: 'bi bi-box-seam',
                drive_thru: 'bi bi-car-front',
                pre_order: 'bi bi-calendar-event',
                catering: 'bi bi-people',
                pms: 'bi bi-building-check',
            }
            return icons[channel] || 'bi bi-circle'
        },

        formatChannelLabel(channel) {
            const labels = {
                takeaway: 'Takeaway',
                dine_in: 'Dine In',
                pick_up: 'Pick Up',
                drive_thru: 'Drive Thru',
                pre_order: 'Pre Order',
                catering: 'Catering',
                pms: 'PMS',
            }
            return labels[channel] || channel
        },

        channelChipClass(channel) {
            const map = {
                takeaway: 'channel-chip--takeaway',
                dine_in: 'channel-chip--dine-in',
                pick_up: 'channel-chip--pick-up',
                drive_thru: 'channel-chip--drive-thru',
                pre_order: 'channel-chip--pre-order',
                catering: 'channel-chip--catering',
                pms: 'channel-chip--pms',
            }

            return map[channel] || ''
        },
       setChannel(channel) {
    this.metaForm.channel = channel

    if (channel !== 'pms' && this.selectedPmsGuest) {
        this.selectedPmsGuest = null
    }

    if (channel === 'dine_in') {
        this.openTableViewer()
    }

    this.saveMeta()

    this.$nextTick(() => {
        const active = this.$refs.channelStrip?.querySelector('.channel-chip--active')
        active?.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest',
        })

        this.checkChannelArrow()
    })
},
      saveMeta() {
    if (!this.session?.id) return

    router.patch(
        route('vendor.pos.meta', this.session.id),
        {
            ...this.metaForm.data(),
            pms_guest: this.pmsGuestPayload(),
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['session'],
        }
    )
},
        can(permission) {
            return (this.$page?.props?.auth?.permissions || []).includes(permission)
        },
        setDiscountMode(mode) {
            if (this.discountForm.discount_mode === mode) return
            this.discountForm.discount_mode = mode
            this.discountPresetIndex = ''
            this.discountForm.discount_type = null
            this.discountForm.discount_value = null

            if (this.session?.id) {
                this.actionLoading.discount = true
                this.discountForm.transform((data) => ({
                    discount_mode: mode,
                    discount_type: null,
                    discount_value: null,
                    promotion_id: null,
                    promotion_code: null,
                })).patch(route('vendor.pos.discount', this.session.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.loadPromotionPresets()
                    },
                    onFinish: () => {
                        this.actionLoading.discount = false
                    }
                })
            } else {
                this.loadPromotionPresets()
            }
        },
        async loadPromotionPresets() {
            if (!this.session?.id) return

            try {
                const { data } = await axios.get(route('vendor.pos.promotions', this.session.id))
                this.localDiscountPresets = Array.isArray(data.discounts) ? data.discounts : []
                this.localVoucherPresets = Array.isArray(data.vouchers) ? data.vouchers : []
            } catch (error) {
                this.toastError('Unable to load promotions.')
            }
        },
        openProduct(product) {
            if (this.isProductUnavailable(product)) {
                this.toastError(`${product.name} is out of stock.`)
                return
            }

            const stock = Number(product?.current_stock ?? 0)
            this.selectedProductInitialQty = 1

            this.selectedProduct = product
            this.modalOpen = true
        },
        closeModal() {
            this.modalOpen = false
            this.selectedProduct = null
            this.selectedProductInitialQty = 1
        },
        addToCart(payload) {
            if (!this.session?.id || this.addItemForm.processing) return
            const productName = payload.product_name || this.selectedProduct?.name || this.productNameById(payload.product_id)
            const product = this.selectedProduct || this.loadedProducts.find((row) => Number(row.id) === Number(payload.product_id))
            const stock = Number(product?.current_stock ?? 0)

            if (Number.isFinite(stock) && stock > 0 && Number(payload.qty || 0) > stock) {
                this.toastError(`Only ${this.trimQty(stock)} ${product?.unit_type || 'pcs'} of ${productName || 'this item'} available.`)
                return
            }

            this.addingProductId = payload.product_id
            this.addItemForm.product_id = payload.product_id
            this.addItemForm.qty = payload.qty
            this.addItemForm.notes = payload.notes || ''
            this.addItemForm.selected_options = payload.selected_options || []
            this.addItemForm.post(route('vendor.pos.add-item', this.session.id), {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal()
                    this.toastSuccess(`${productName || 'Item'} added successfully.`)
                    this.loadPromotionPresets()
                    this.reloadProducts()
                },
                onError: (errors) => {
                    this.toastError(this.firstError(errors) || 'Unable to add item.')
                },
                onFinish: () => {
                    this.addingProductId = null
                },
            })
        },
        productNameById(productId) {
            return this.loadedProducts.find((product) => Number(product.id) === Number(productId))?.name || ''
        },
        toastSuccess(message) {
            alertSuccess(message)
        },
        toastError(message) {
            alertError(message)
        },
        removeItem(itemId) {
            if (!this.session?.id || this.actionLoading.removingItemId) return

            const item = this.session.items?.find((item) => Number(item.id) === Number(itemId))
            const isGiftItem = item && item.loyalty_gift_id !== null
            const itemName = item?.product_name || 'Item'

            this.actionLoading.removingItemId = itemId
            router.delete(route('vendor.pos.remove-item', { session: this.session.id, itemId }), {
                preserveScroll: true,
                onSuccess: () => {
                    if (isGiftItem) {
                        this.toastSuccess('Loyalty reward removed successfully.')
                    } else {
                        this.toastSuccess(`${itemName} removed successfully.`)
                    }
                    this.loadPromotionPresets()
                    this.reloadProducts()
                },
                onFinish: () => {
                    this.actionLoading.removingItemId = null
                },
            })
        },
        updateItemQty(item, newQty) {
            if (!this.session?.id || this.actionLoading.updatingItemId) return
            const qty = this.normalizeCartQty(item, newQty)
            if (qty < this.minCartQty(item)) return

            this.actionLoading.updatingItemId = item.id
            router.patch(route('vendor.pos.update-item-qty', { session: this.session.id, itemId: item.id }), { qty }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.loadPromotionPresets()
                    this.reloadProducts()
                },
                onError: (errors) => {
                    this.toastError(this.firstError(errors) || 'Unable to update quantity.')
                },
                onFinish: () => {
                    this.actionLoading.updatingItemId = null
                },
            })
        },
        applyDiscountPreset() {
            if (!this.session?.id || this.actionLoading.discount || this.discountForm.processing) return

            // If the selection is cleared (empty string), clear the discount on the backend
            if (this.discountPresetIndex === '') {
                this.discountForm.discount_type = null
                this.discountForm.discount_value = null
                this.actionLoading.discount = true
                this.discountForm
                    .transform((data) => ({
                        ...data,
                        discount_type: null,
                        discount_value: null,
                        promotion_id: null,
                        promotion_code: null,
                    }))
                    .patch(route('vendor.pos.discount', this.session.id), {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.toastSuccess('Promotion cleared.')
                            this.loadPromotionPresets()
                        },
                        onError: (errors) => {
                            this.toastError(this.firstError(errors) || 'Unable to clear promotion.')
                        },
                        onFinish: () => {
                            this.actionLoading.discount = false
                        },
                    })
                return
            }

            const selected = this.activePromotionPresets?.[Number(this.discountPresetIndex)]
            this.discountForm.discount_type = selected?.type ?? null
            this.discountForm.discount_value = selected?.value ?? null
            this.actionLoading.discount = true
            this.discountForm
                .transform((data) => ({
                    ...data,
                    promotion_id: selected?.id ?? null,
                    promotion_code: selected?.code ?? null,
                }))
                .patch(route('vendor.pos.discount', this.session.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.toastSuccess(selected?.id ? `${selected.label} redeemed successfully.` : 'Promotion cleared.')
                        this.loadPromotionPresets()
                    },
                    onError: (errors) => {
                        this.toastError(this.firstError(errors) || 'Unable to apply promotion.')
                    },
                    onFinish: () => {
                        this.actionLoading.discount = false
                    },
                })
        },
        onDiscountDropdownChange(val) {
            if (val === '') {
                this.applyDiscountPreset()
            }
        },
        clearPromotionDiscount() {
            this.discountPresetIndex = ''
            this.applyDiscountPreset()
        },
        removeLoyaltyDiscount() {
            if (!this.session?.id || this.actionLoading.discount) return
            this.actionLoading.discount = true
            router.delete(route('vendor.pos.loyalty.redeem.cancel', this.session.id), {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    // Let the global flash watcher handle the success toast
                },
                onError: () => {
                    this.toastError('Unable to remove loyalty reward.')
                },
                onFinish: () => {
                    this.actionLoading.discount = false
                }
            })
        },
        firstError(errors) {
            if (!errors || typeof errors !== 'object') return ''
            const value = Object.values(errors)[0]
            return Array.isArray(value) ? value[0] : value
        },
        holdOrder() {
            if (!this.session?.id || this.actionLoading.hold) return
            this.actionLoading.hold = true
            router.patch(route('vendor.pos.hold', this.session.id), {}, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.resetPosDetailsOnly()
                    this.reloadProducts()
                },
                onError: (errors) => {
                    this.toastError(this.firstError(errors) || 'Unable to hold order.')
                },
                onFinish: () => {
                    this.actionLoading.hold = false
                },
            })
        },
        async cancelOrder() {
            if (!this.session?.id || !this.hasOrderItems || this.actionLoading.cancel) return

            const result = await confirmPrompt({
                title: 'Cancel Order',
                text: 'Cancel this order and reset the POS cart?',
                confirmText: 'Cancel Order',
                cancelText: 'Keep Order',
            })

            if (!result.isConfirmed) return

            this.actionLoading.cancel = true

            router.patch(
                route('vendor.pos.cancel', this.session.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.resetPosDetailsOnly()
                        this.selectedCategoryId = null
                        this.toastSuccess('Order cancelled and POS cart reset.')
                        this.reloadProducts()
                    },
                    onError: (errors) => {
                        this.toastError(this.firstError(errors) || 'Unable to cancel order.')
                    },
                    onFinish: () => {
                        this.actionLoading.cancel = false
                    },
                }
            )
        },
        payFire() {
            if (!this.session?.id) return
            router.patch(route('vendor.pos.pay-fire', this.session.id), {}, { preserveScroll: true })
        },
    },

    watch: {
        flash: {
            handler(flash) {
                if (flash?.message) {
                    if (flash.message.toLowerCase().includes('session opened')) {
                        this.alertSuccess(flash.message);
                    } else {
                        this.toastSuccess(flash.message);
                    }
                }

                if (flash?.error) {
                    this.alertError(flash.error);
                }
            },
            immediate: true,
            deep: true,
        },
    }

}
</script>
<style scoped>
.pos-viewer {
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.pos-grid {
    display: grid;
    grid-template-columns: minmax(160px, 1.02fr) minmax(400px, 1.2fr) minmax(280px, 0.86fr);
    gap: 14px;
    align-items: stretch;
    height: 100%;
    min-height: 0;
}

.pos-card {
    background: #ffffff;
    border: 1px solid #e7eaef;
    border-radius: 14px;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.catalog-panel,
.order-panel,
.summary-panel {
    min-height: 0;
    height: 100%;
}

.catalog-panel {
    padding: 12px;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.catalog-toolbar {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 12px;
}

.search-box,
.barcode-box {
    position: relative;
}

.search-box i,
.barcode-box i {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #9aa4b2;
    font-size: 14px;
}

.search-box__input,
.barcode-box__input,
.field-control {
    width: 100%;
    min-height: 42px;
    border: 1px solid #d9e0e8;
    border-radius: 8px;
    background: #ffffff;
    color: #1f2937;
    font-size: 13px;
    font-weight: 500;
    padding: 0 14px;
    outline: none;
    transition: all 0.18s ease;
}

.search-box__input {
    padding-left: 38px;
}

.barcode-box__input {
    border-color: #3b82f6;
    padding-left: 42px;
    padding-right: 88px;
    font-weight: 800;
}

.search-box__input:focus,
.barcode-box__input:focus,
.field-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.barcode-box__status {
    position: absolute;
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    color: #3b82f6;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}

.barcode-box--loading .barcode-box__input {
    background: #eff6ff;
}

.field-control--textarea {
    min-height: 58px;
    resize: none;
    padding: 12px 14px;
}

.category-strip {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 8px;
    scrollbar-width: thin;
    scrollbar-color: #3b82f6 #eff6ff;
}

.category-strip::-webkit-scrollbar {
    height: 8px;
}

.category-strip::-webkit-scrollbar-track {
    background: #eff6ff;
    border-radius: 999px;
}

.category-strip::-webkit-scrollbar-thumb {
    background: #3b82f6;
    border-radius: 999px;
}

.category-chip {
    flex: 0 0 auto;
    min-height: 34px;
    padding: 0 16px;
    border: none;
    border-radius: 999px;
    background: #e5e7eb;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s ease;
}

.category-chip--active {
    background: #3b82f6;
    color: #ffffff;
}

.catalog-scroll {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding-right: 2px;
    scrollbar-width: thin;
    scrollbar-color: #3b82f6 #eff6ff;
}

.catalog-scroll::-webkit-scrollbar,
.order-list::-webkit-scrollbar,
.summary-panel::-webkit-scrollbar {
    width: 8px;
}

.catalog-scroll::-webkit-scrollbar-track,
.order-list::-webkit-scrollbar-track,
.summary-panel::-webkit-scrollbar-track {
    background: #eff6ff;
    border-radius: 999px;
}

.catalog-scroll::-webkit-scrollbar-thumb,
.order-list::-webkit-scrollbar-thumb,
.summary-panel::-webkit-scrollbar-thumb {
    background: #3b82f6;
    border-radius: 999px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    align-content: start;
    align-items: stretch;
}

.product-card {
    position: relative;
    /* height: 210px;  */
    display: flex;
    flex-direction: column;
    border: 1px dashed #d9dee6;
    border-radius: 14px;
    background: #ffffff;
    overflow: hidden;
    text-align: left;
    cursor: pointer;
    transition: all 0.18s ease;
}

.product-card--low-stock {
    border-color: #93c5fd;
    box-shadow: 0 12px 24px rgba(59, 130, 246, 0.12);
}

.product-card--unavailable {
    cursor: not-allowed;
    opacity: 0.62;
    filter: grayscale(0.18);
}

.product-card__media {
    position: relative;
    height: 100px;
    width: 100%;
    /* background: #f3f4f6; */
    border-radius: 14px 14px 0 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-card__image {
    width: 100%;
    height: 100%;
    object-fit: contain; /* full image, no crop */
    display: block;
    border-radius: 0;
}

.product-card__stock-alert {
    position: absolute;
    left: 8px;
    right: 8px;
    bottom: 8px;
    min-height: 26px;
    border-radius: 6px;
    padding: 5px 7px;
    background: rgba(15, 23, 42, 0.82);
    color: #ffffff;
    font-size: 11px;
    font-weight: 900;
    line-height: 1.25;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.product-card--low-stock .product-card__stock-alert {
    background: rgba(217, 119, 6, 0.92);
}

.product-card--unavailable .product-card__stock-alert {
    background: rgba(185, 28, 28, 0.94);
}

.product-card__body {
    flex: 1;
    padding: 10px 10px 12px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.product-card__name {
    margin: 0 0 8px;
    font-size: 13px;
    line-height: 1.35;
    font-weight: 600;
    color: #475569;

    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.product-card__price-row {
    display: flex;
    align-items: center;
}

.product-card__price {
    font-size: 15px;
    font-weight: 500;
    color: #64748b;
}

.product-card__price--special {
    font-weight: 700;
    color: #ef4444;
}

.product-card__price-original {
    font-size: 10px;
    text-decoration: line-through;
    color: #94a3b8;
    margin-left: 14px;
}

.product-card__stock-note {
    margin: 6px 0 0;
    color: #2563eb;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.25;
}

.product-card__stock-note--low {
    color: #dc2626;
}

.product-card__badge {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.74);
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
}

.order-panel {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
}

.order-toolbar {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}

.order-meta-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.field-wrap {
    position: relative;
}

.field-wrap--table {
    grid-column: span 2;
}

.order-toolbar {
    gap: 10px;
}

.summary-utility-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.order-stage {
    flex: 1;
    min-height: 0;
    border: 1px solid #e3e7ed;
    border-radius: 12px;
    background: #f3f4f6;
    overflow: hidden;
}

.empty-order,
.empty-state {
    width: 100%;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #5f6b7a;
    padding: 32px 20px;
}

.empty-order__icon {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    color: #5f6b7a;
    font-size: 48px;
}

.empty-order h3,
.empty-state h3 {
    margin: 0 0 6px;
    font-size: 16px;
    font-weight: 700;
    color: #475569;
}

.empty-order p,
.empty-state p {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

.order-item-card__meta {
    margin: 0;
    font-size: 13px;
    color: var(--pos-text-muted);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.order-item-qty-control {
    display: inline-flex;
    align-items: center;
    background: var(--pos-bg-body);
    border: 1px solid var(--pos-border);
    border-radius: 6px;
    overflow: hidden;
}

.order-item-qty-control button {
    background: transparent;
    border: none;
    padding: 2px 6px;
    color: var(--pos-text);
    cursor: pointer;
    display: grid;
    place-items: center;
}

.order-item-qty-control button:hover:not(:disabled) {
    background: var(--pos-bg-card-hover);
    color: var(--pos-primary);
}

.order-item-qty-control button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.order-item-qty-control span {
    font-weight: 600;
    font-size: 13px;
    min-width: 24px;
    text-align: center;
    color: var(--pos-text);
}

.order-item-card__meta .meta-dot {
    color: #64748b;
}

.order-list {
    height: 100%;
    overflow: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scrollbar-width: thin;
    scrollbar-color: #3b82f6 #eff6ff;
}

.order-item-card {
    display: grid;
    grid-template-columns: 88px 1fr auto;
    gap: 12px;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #e0e6ed;
    background: #ffffff;
}

.order-item-card__media {
    width: 88px;
    height: 88px;
    border-radius: 10px;
    overflow: hidden;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
}
.product-card__image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
    background: transparent;
    border-radius: 0;
}

.order-item-card__image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    display: block;
    background: transparent;
    border-radius: 10px;
}
.order-item-card__content {
    min-width: 0;
}

.order-item-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}

.order-item-card__name {
    margin: 0;
    font-size: 14px;
    line-height: 1.4;
    font-weight: 700;
    color: #334155;
}

.loyalty-item-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #16a34a;
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    margin-left: 6px;
    vertical-align: middle;
}
.loyalty-item-tag i {
    font-size: 10px;
}

.order-item-card__meta,
.order-item-card__sub,
.order-item-card__notes {
    margin: 5px 0 0;
    font-size: 12px;
    color: #64748b;
}

.order-item-card__meta span {
    margin: 0 6px;
}

.order-item-card__total {
    white-space: nowrap;
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
}

.option-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}

.option-pill {
    padding: 5px 9px;
    border-radius: 999px;
    background: #eef2f7;
    color: #5f6b7a;
    font-size: 11px;
    font-weight: 700;
}

.remove-btn {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 10px;
    background: #eff6ff;
    color: #3b82f6;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.16s ease;
}

.remove-btn:hover {
    background: #dbeafe;
    color: #1d4ed8;
}

.remove-btn:disabled,
.apply-btn:disabled,
.summary-action:disabled {
    cursor: wait;
}

.remove-btn:disabled i {
    animation: pos-spin 0.75s linear infinite;
}

@keyframes pos-spin {
    to {
        transform: rotate(360deg);
    }
}

.summary-panel {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #3b82f6 #eff6ff;
}

.summary-utility-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.summary-utility-card {
    min-height: 74px;
    border: 1px dashed #d9dee6;
    border-radius: 12px;
    background: #ffffff;
    color: #5f6b7a;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.18s ease;
}

.summary-utility-card i {
    font-size: 22px;
}

.summary-utility-card:hover {
    border-color: #3b82f6;
    color: #2563eb;
}

.summary-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.discount-switcher {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.discount-switcher__btn {
    min-height: 48px;
    border: 1px dashed #d9dee6;
    border-radius: 12px;
    background: #ffffff;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.18s ease;
}

.discount-switcher__btn--active {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #2563eb;
}

.discount-row {
    display: grid;
    grid-template-columns: 1fr 96px;
    gap: 8px;
}

.discount-row__select {
    min-width: 0;
}

.apply-btn {
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.totals-card {
    border-radius: 10px;
    background: #f5f5f5;
    padding: 14px 16px;
}

.totals-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 0;
    font-size: 14px;
    color: #475569;
}

.totals-row strong {
    font-size: 14px;
    font-weight: 700;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.remove-deduction-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 8px;
    background: #eff6ff;
    color: #3b82f6;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.16s ease;
}

.remove-deduction-btn:hover {
    background: #dbeafe;
    color: #1d4ed8;
}

.totals-row--grand {
    margin-top: 8px;
    padding-top: 12px;
    border-top: 1px solid #d9dde4;
}

.totals-row--grand span,
.totals-row--grand strong {
    color: #3b82f6;
    font-weight: 800;
}

.totals-row--discount {
    color: #16a34a;
}

.totals-row--discount strong {
    color: #16a34a;
    font-weight: 700;
}

.tax-breakdown {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 2px 0 6px;
}

.totals-row--tax-detail {
    padding-left: 12px;
    font-size: 12px;
}

.totals-row--tax-detail span,
.totals-row--tax-detail strong {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
}

.summary-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.summary-action {
    min-height: 52px;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
}

.summary-action i {
    font-size: 18px;
}

.summary-action--cash {
    background: #16a34a;
    color: #ffffff;
}

.summary-action--card {
    background: #2563eb;
    color: #ffffff;
}

.summary-action--credit {
    background: #7c3aed;
    color: #ffffff;
}

.summary-action--cancel {
    background: #dc2626;
    color: #ffffff;
}

.summary-action--hold {
    background: #eab308;
    color: #1f2937;
}

@media (max-width: 1480px) {
    .channel-strip {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 1200px) {
    .pos-viewer {
        height: auto;
        min-height: calc(100vh - 110px);
        overflow: visible;
    }

    .pos-grid {
        grid-template-columns: 1fr;
        height: auto;
    }

    .catalog-panel,
    .order-panel,
    .summary-panel {
        height: auto;
        min-height: auto;
        overflow: visible;
    }

    .catalog-scroll,
    .order-stage {
        max-height: min(680px, calc(100vh - 180px));
    }

    .summary-panel {
        max-height: none;
    }
}

@media (max-width: 760px) {

    .channel-strip,
    .order-meta-row,
    .summary-utility-grid,
    .discount-switcher,
    .summary-actions {
        grid-template-columns: 1fr;
    }

    .field-wrap--table {
        grid-column: auto;
    }

    .discount-row {
        grid-template-columns: 1fr;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .order-item-card {
        grid-template-columns: 1fr;
    }

    .remove-btn {
        width: 100%;
    }
}

.field-wrap :deep(.form-group),
.discount-row :deep(.form-group) {
    margin-bottom: 0;
    width: 100%;
}

.field-wrap :deep(.form-label),
.discount-row :deep(.form-label) {
    margin-bottom: 6px;
    font-size: 12px;
    line-height: 1.2;
}

.field-wrap :deep(.form-input),
.discount-row :deep(.form-input),
.field-wrap :deep(.select-trigger),
.discount-row :deep(.select-trigger) {
    min-height: 42px;
    border-radius: 8px;
}

.field-wrap :deep(.error-text),
.discount-row :deep(.error-text) {
    margin-top: 4px;
}

.order-meta-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 10px;
    align-items: end;
}

.pos-add-customer-btn {
    width: 42px;
    height: 42px;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    background: #eff6ff;
    color: #3b82f6;
    display: inline-grid;
    place-items: center;
    align-self: end;
    cursor: pointer;
    font-size: 18px;
    transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease, box-shadow 0.12s ease;
}

.pos-customer-view-btn {
    width: 42px;
    height: 42px;
    border: 1px solid #dbe4f0;
    border-radius: 12px;
    background: #ffffff;
    color: #64748b;
    display: inline-grid;
    place-items: center;
    align-self: end;
    cursor: pointer;
    font-size: 18px;
    transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease, box-shadow 0.12s ease;
}

.pos-customer-view-btn:hover:not(:disabled) {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.08);
}

.pos-customer-view-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.pos-add-customer-btn:hover {
    background: #dbeafe;
    border-color: #60a5fa;
    color: #1d4ed8;
    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.12);
}

.pos-actions-wrap {
    position: relative;
    align-self: end;
}

.pos-actions-btn {
    position: relative;
    isolation: isolate;
    min-height: 42px;
    border: none;
    border-radius: 8px;
    padding: 0 15px;
    background: #3b82f6;
    color: #ffffff;
    font-weight: 800;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    cursor: pointer;
    overflow: hidden;
    box-shadow: 0 10px 18px rgba(59, 130, 246, 0.22);
    transition: background 0.14s ease, box-shadow 0.14s ease, opacity 0.14s ease;
}

.pos-actions-btn::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(110deg, transparent 0%, rgba(255, 255, 255, 0.28) 45%, transparent 70%);
    transform: translateX(-120%);
    transition: transform 0.55s ease;
}

.pos-actions-btn:hover:not(:disabled),
.pos-actions-btn--open {
    background: #3b82f6;
    box-shadow: 0 10px 18px rgba(59, 130, 246, 0.22);
}

.pos-actions-btn:hover:not(:disabled)::before {
    transform: translateX(120%);
}

.pos-actions-btn:active:not(:disabled) {
    background: #1d4ed8;
}

.pos-actions-btn:disabled {
    background: #f6b26b;
    color: #ffffff;
    cursor: not-allowed;
    box-shadow: none;
    opacity: 0.72;
}

.pos-actions-btn__chevron {
    font-size: 12px;
    transition: transform 0.18s ease;
}

.pos-actions-btn--open .pos-actions-btn__chevron {
    transform: rotate(180deg);
}

.pos-actions-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    z-index: 30;
    width: 286px;
    padding: 9px;
    border-radius: 14px;
    border: 1px solid #fee2e2;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 20px 44px rgba(15, 23, 42, 0.16);
}

.pos-actions-menu button {
    width: 100%;
    border: none;
    background: transparent;
    color: #334155;
    padding: 12px;
    border-radius: 11px;
    text-align: left;
    display: grid;
    grid-template-columns: 36px 1fr;
    align-items: center;
    gap: 11px;
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease;
}

.pos-actions-menu button i {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #eff6ff;
    color: #3b82f6;
    display: grid;
    place-items: center;
    font-size: 16px;
}

.pos-actions-menu button strong,
.pos-actions-menu button small {
    display: block;
}

.pos-actions-menu button strong {
    color: #1e293b;
    font-size: 13px;
    font-weight: 900;
}

.pos-actions-menu button small {
    margin-top: 3px;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.35;
}

.pos-actions-menu button:hover {
    background: #eff6ff;
}

@media (max-width: 760px) {
    .order-meta-row {
        grid-template-columns: 1fr;
    }

    .pos-actions-wrap,
    .pos-actions-btn,
    .pos-customer-view-btn,
    .pos-add-customer-btn {
        width: 100%;
    }

    .pos-actions-btn,
    .pos-customer-view-btn,
    .pos-add-customer-btn {
        justify-content: center;
    }

    .pos-actions-menu {
        left: 0;
        right: auto;
        width: min(100%, 320px);
    }
}

.loyalty-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 4200;
    background: rgba(15, 23, 42, 0.42);
    display: grid;
    place-items: center;
    padding: 42px 38px;
}

.loyalty-modal {
    width: min(1430px, 100%);
    min-height: min(764px, calc(100dvh - 84px));
    background: #ffffff;
    border: 0;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.16);
}

.loyalty-modal--wide {
    width: min(1430px, 100%);
}

.loyalty-modal__header {
    min-height: 58px;
    padding: 0 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
}

.loyalty-modal__header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 900;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.loyalty-modal__header button {
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: transparent;
    color: #334155;
    font-size: 16px;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease;
}

.loyalty-modal__header button:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.loyalty-hero {
    margin: 0 24px 22px;
    min-height: 124px;
    border: 0;
    border-radius: 10px;
    padding: 18px 18px;
    background: linear-gradient(90deg, #eff6ff 0%, #e8e1dc 58%, #cfcfcf 100%);
    display: grid;
    grid-template-columns: 1.2fr 1fr 1fr;
    align-items: center;
    gap: 16px;
    box-shadow: none;
}

.loyalty-hero__customer {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.loyalty-tier-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #ffffff;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    overflow: hidden;
}

.loyalty-tier-avatar img {
    width: 42px;
    height: 42px;
    object-fit: contain;
}

.loyalty-tier-avatar i {
    color: #2563eb;
    font-size: 28px;
}

.loyalty-hero__level {
    display: grid;
    justify-items: center;
    gap: 5px;
    text-align: center;
}

.loyalty-hero__level img {
    width: 34px;
    height: 34px;
    object-fit: contain;
}

.loyalty-hero__level i {
    color: #2563eb;
    font-size: 28px;
}

.loyalty-hero__points {
    display: grid;
    justify-items: end;
    gap: 5px;
    text-align: right;
}

.loyalty-hero span {
    color: #64748b;
    font-size: 13px;
}

.loyalty-hero strong {
    color: #334155;
    font-weight: 900;
}

.loyalty-hero__points strong {
    color: #3b82f6;
    font-size: 18px;
}

.loyalty-hero__points small {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

.loyalty-rewards {
    padding: 0 24px 24px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 12px;
    overflow-y: auto;
}

.loyalty-reward-card {
    border: 1px solid #e5e7eb;
    background: #ffffff;
    border-radius: 14px;
    padding: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    text-align: left;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
}

.loyalty-reward-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 14px 26px rgba(59, 130, 246, 0.1);
}

.loyalty-reward-card strong,
.loyalty-reward-card span {
    display: block;
}

.loyalty-reward-card strong {
    color: #334155;
    font-weight: 900;
}

.loyalty-reward-card span {
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
}

.loyalty-reward-card em {
    color: #3b82f6;
    font-style: normal;
    font-weight: 900;
    white-space: nowrap;
}

.loyalty-empty {
    flex: 1;
    display: grid;
    place-items: center;
    color: #64748b;
    font-weight: 800;
    padding: 60px 20px;
}

@media (max-width: 640px) {
    .loyalty-modal-backdrop {
        padding: 12px;
    }

    .loyalty-modal {
        min-height: calc(100dvh - 24px);
        border-radius: 14px;
    }

    .loyalty-modal__header {
        align-items: flex-start;
        padding: 14px;
    }

    .loyalty-modal__header h3 {
        font-size: 15px;
    }

    .loyalty-hero {
        margin: 14px;
        grid-template-columns: 1fr;
        padding: 16px;
    }

    .loyalty-hero__level,
    .loyalty-hero__points {
        justify-items: start;
        text-align: left;
    }

    .loyalty-rewards {
        grid-template-columns: 1fr;
        padding: 0 14px 18px;
    }
}

.loyalty-empty--large {
    align-content: center;
    gap: 12px;
}

.loyalty-empty--large i {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: #f1ede8;
    color: #3b82f6;
    font-size: 70px;
    display: grid;
    place-items: center;
}

.quick-customer-backdrop {
    position: fixed;
    inset: 0;
    z-index: 4300;
    background: rgba(15, 23, 42, 0.42);
    display: grid;
    place-items: center;
    padding: 24px;
}

.quick-customer-modal {
    width: min(762px, 100%);
    border: none;
    border-radius: 10px;
    background: #ffffff;
    box-shadow: 0 22px 52px rgba(15, 23, 42, 0.18);
    padding: 0;
    overflow: visible;
}

.quick-customer-modal__header {
    min-height: 62px;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.quick-customer-modal__header h3 {
    margin: 0;
    color: #334155;
    font-size: 18px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 9px;
}

.quick-customer-modal__header button {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #475569;
    display: grid;
    place-items: center;
}

.quick-customer-modal__header button:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.quick-customer-modal__body {
    padding: 6px 24px 22px;
    display: grid;
    gap: 24px;
}

.quick-customer-field input {
    width: 100%;
    min-height: 38px;
    border: 1px solid #d6dbe1;
    border-radius: 6px;
    padding: 0 14px;
    color: #334155;
    font-size: 14px;
    outline: none;
}

.quick-customer-field input:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.quick-customer-phone {
    max-width: 100%;
}

.quick-customer-phone :deep(.formLabel) {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

.quick-customer-phone :deep(.phone-row) {
    gap: 24px;
}

.quick-customer-phone :deep(.country-display),
.quick-customer-phone :deep(.phone-input) {
    min-height: 38px;
    border-radius: 6px;
}

.quick-customer-phone :deep(.country-display) {
    min-width: 282px;
}

.quick-customer-modal__footer {
    padding: 6px 24px 24px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 24px;
}

.quick-customer-cancel,
.quick-customer-create {
    border: none;
    background: transparent;
    color: #3b82f6;
    font-size: 14px;
    font-weight: 700;
    padding: 7px 4px;
    cursor: pointer;
}

.quick-customer-create:disabled {
    color: #94a3b8;
    cursor: not-allowed;
}

.quick-customer-error {
    display: block;
    margin-top: 6px;
    color: #dc2626;
    font-size: 12px;
    font-weight: 700;
}

.quick-customer-error--general {
    margin: 0 24px;
}

@media (max-width: 640px) {
    .quick-customer-modal__body {
        gap: 16px;
    }

    .quick-customer-phone :deep(.phone-row) {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .quick-customer-phone :deep(.country-display) {
        min-width: 100%;
    }
}

.field-wrap {
    position: relative;
    min-width: 0;
}

.field-wrap--meta {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.field-wrap--table {
    grid-column: span 2;
}

.discount-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 108px;
    gap: 8px;
    align-items: end;
}

.discount-row__field {
    min-width: 0;
}

.apply-btn {
    height: 42px;
    border: none;
    border-radius: 8px;
    background: #f6b26b;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    align-self: end;
}

.apply-btn:disabled {
    cursor: wait;
    opacity: 0.72;
}

.apply-btn--ready {
    background: #3b82f6;
    box-shadow: 0 10px 18px rgba(59, 130, 246, 0.22);
}

.product-card--skeleton {
    pointer-events: none;
    border-style: solid;
}

.skeleton-box,
.skeleton-line {
    position: relative;
    overflow: hidden;
    background: #e5e7eb;
}

.skeleton-box::after,
.skeleton-line::after {
    content: '';
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg,
            transparent,
            rgba(255, 255, 255, 0.7),
            transparent);
    animation: skeleton-loading 1.2s infinite;
}

.skeleton-line {
    height: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
}

.skeleton-line--title {
    width: 78%;
    height: 14px;
}

.skeleton-line--price {
    width: 52%;
    height: 12px;
    margin-bottom: 0;
}

@keyframes skeleton-loading {
    100% {
        transform: translateX(100%);
    }
}

.category-strip {
    display: grid;
    grid-auto-flow: column;
    grid-template-rows: repeat(2, auto);
    grid-auto-columns: max-content;
    gap: 8px 10px;

    overflow-x: auto;
    overflow-y: hidden;

    padding: 2px 2px 8px;
    max-width: 100%;

    scrollbar-width: thin;
    scrollbar-color: #3b82f6 #eff6ff;
}

.category-strip::-webkit-scrollbar {
    height: 8px;
}

.category-strip::-webkit-scrollbar-track {
    background: #eff6ff;
    border-radius: 999px;
}

.category-strip::-webkit-scrollbar-thumb {
    background: linear-gradient(90deg, #3b82f6, #3b82f6);
    border-radius: 999px;
}

.category-strip::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(90deg, #1d4ed8, #3b82f6);
}

.viewer-empty-state {
    min-height: calc(100vh - 120px);
    display: grid;
    place-items: center;
    padding: 32px;
}

.viewer-empty-state__card {
    width: min(860px, 100%);
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 28px;
    background: linear-gradient(135deg, #ffffff, #eff6ff);
    border: 1px solid #fde7cf;
    border-radius: 28px;
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.08);
    padding: 34px;
}

.viewer-empty-state__icon {
    width: 160px;
    height: 160px;
    border-radius: 28px;
    display: grid;
    place-items: center;
    background: radial-gradient(circle at 30% 30%, #bfdbfe, #60a5fa);
    color: #ffffff;
    font-size: 62px;
    box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.2);
}

.viewer-empty-state__content {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.viewer-empty-state__badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    padding: 8px 12px;
    border-radius: 999px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #2563eb;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 14px;
}

.viewer-empty-state__content h2 {
    margin: 0 0 10px;
    font-size: 36px;
    line-height: 1.1;
    font-weight: 900;
    color: #0f172a;
}

.viewer-empty-state__content p {
    margin: 0;
    max-width: 620px;
    font-size: 16px;
    line-height: 1.7;
    color: #64748b;
}

.viewer-empty-state__meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 24px;
}

.viewer-empty-state__meta-item {
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    padding: 14px 16px;
}

.viewer-empty-state__meta-item .label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    margin-bottom: 4px;
}

.viewer-empty-state__meta-item strong {
    font-size: 15px;
    color: #0f172a;
}

.viewer-empty-state__actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 26px;
}

.viewer-primary-btn,
.viewer-secondary-btn {
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.16s ease;
}

.viewer-primary-btn {
    border: none;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #fff;
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.22);
    cursor: pointer;
}

.viewer-secondary-btn {
    border: 1px solid #dbe2ea;
    background: #fff;
    color: #334155;
}

@media (max-width: 840px) {
    .viewer-empty-state__card {
        grid-template-columns: 1fr;
        padding: 22px;
    }

    .viewer-empty-state__icon {
        width: 100px;
        height: 100px;
        font-size: 40px;
    }

    .viewer-empty-state__content h2 {
        font-size: 28px;
    }

    .viewer-empty-state__meta {
        grid-template-columns: 1fr;
    }
}

.open-session-backdrop {
    position: fixed;
    inset: 0;
    z-index: 3000;
    background: rgba(15, 23, 42, 0.48);
    backdrop-filter: blur(4px);
    display: grid;
    place-items: center;
    padding: 20px;
}

.open-session-modal {
    width: 100%;
    max-width: 520px;
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.22);
    padding: 24px;
    display: grid;
    gap: 16px;
    animation: openSessionPop 0.18s ease;
}

.open-session-modal h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
}

.open-session-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 6px;
}

@keyframes openSessionPop {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.open-session-backdrop {
    position: fixed;
    inset: 0;
    z-index: 3000;
    background: rgba(15, 23, 42, 0.52);
    backdrop-filter: blur(5px);
    display: grid;
    place-items: center;
    padding: 20px;
}

.open-session-modal {
    width: 100%;
    max-width: 560px;
    background: #ffffff;
    border-radius: 26px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.2);
    overflow: hidden;
    animation: openSessionFadeIn 0.2s ease;
}

.open-session-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 24px 24px 18px;
    border-bottom: 1px solid #eef2f7;
}

.open-session-modal__header-content {
    min-width: 0;
}

.open-session-modal__badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #2563eb;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 12px;
}

.open-session-modal__title {
    margin: 0;
    font-size: 28px;
    font-weight: 900;
    line-height: 1.1;
    color: #0f172a;
}

.open-session-modal__subtitle {
    margin: 10px 0 0;
    font-size: 14px;
    line-height: 1.6;
    color: #64748b;
    max-width: 420px;
}

.open-session-modal__close {
    width: 42px;
    height: 42px;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    color: #475569;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.16s ease;
}

.open-session-modal__close:hover {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.open-session-modal__body {
    padding: 22px 24px;
    display: grid;
    gap: 18px;
}

.open-session-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.open-session-summary__item {
    padding: 14px 16px;
    border: 1px solid #edf2f7;
    background: #f8fafc;
    border-radius: 16px;
}

.open-session-summary__label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    margin-bottom: 4px;
}

.open-session-summary__value {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}

.open-session-field {
    display: grid;
    gap: 8px;
}

.open-session-field__label {
    font-size: 13px;
    font-weight: 800;
    color: #334155;
}

.open-session-field__input-wrap {
    position: relative;
}

.open-session-field__prefix {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
}

.open-session-field__input,
.open-session-field__textarea {
    width: 100%;
    border: 1px solid #dbe2ea;
    background: #fff;
    border-radius: 14px;
    font-size: 14px;
    color: #0f172a;
    outline: none;
    transition: all 0.16s ease;
}

.open-session-field__input {
    height: 48px;
    padding: 0 14px 0 66px;
}

.open-session-field__textarea {
    min-height: 120px;
    resize: vertical;
    padding: 14px;
}

.open-session-field__input:focus,
.open-session-field__textarea:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.open-session-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 18px 24px 24px;
    border-top: 1px solid #eef2f7;
}

.open-session-btn {
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    border: none;
    font-size: 14px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.16s ease;
}

.open-session-btn--secondary {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #dbe2ea;
}

.open-session-btn--primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.22);
}

.open-session-btn--primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

@keyframes openSessionFadeIn {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 640px) {
    .open-session-modal {
        max-width: 100%;
        border-radius: 20px;
    }

    .open-session-modal__header,
    .open-session-modal__body,
    .open-session-modal__footer {
        padding-left: 16px;
        padding-right: 16px;
    }

    .open-session-summary {
        grid-template-columns: 1fr;
    }

    .open-session-modal__footer {
        flex-direction: column;
    }

    .open-session-btn {
        width: 100%;
        justify-content: center;
    }

    .open-session-modal__title {
        font-size: 24px;
    }
}

.summary-action--disabled {
    opacity: 0.45;
    cursor: not-allowed;
    pointer-events: none;
    filter: grayscale(0.15);
}

:deep(.pos-swal-toast) {
    width: min(360px, calc(100vw - 28px));
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 13px 16px;
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
}

:deep(.pos-swal-toast--success) {
    background: #f0fdf4;
    color: #14532d;
    border-color: #bbf7d0;
}

:deep(.pos-swal-toast--error) {
    background: #fef2f2;
    color: #7f1d1d;
    border-color: #fecaca;
}

:deep(.pos-swal-toast__title) {
    color: #14532d;
    font-size: 14px;
    font-weight: 800;
    line-height: 1.35;
    text-align: left;
}

:deep(.pos-swal-toast--error .pos-swal-toast__title) {
    color: #7f1d1d;
}

:deep(.pos-swal-toast__progress) {
    background: rgba(22, 163, 74, 0.35);
}

:deep(.pos-swal-toast--error .pos-swal-toast__progress) {
    background: rgba(220, 38, 38, 0.35);
}

.order-toolbar {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-width: 0;
}

.channel-strip {
    display: flex;
    flex-wrap: nowrap;
    gap: 10px;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 4px;
    scroll-behavior: smooth;
    -ms-overflow-style: none;
    scrollbar-width: none;
    min-width: 0;
}

.channel-strip::-webkit-scrollbar {
    display: none;
}

.channel-chip {
    --channel-accent: #64748b;
    --channel-soft: #f8fafc;

    flex: 0 0 auto;
    min-width: 110px;
    min-height: 56px;
    padding: 0 16px;
    border: 1px dashed #d9dee6;
    border-radius: 12px;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s ease;
    white-space: nowrap;
}

.channel-chip i {
    font-size: 18px;
    color: var(--channel-accent);
    transition: all 0.18s ease;
}

.channel-chip span {
    color: #475569;
    line-height: 1.1;
}

.channel-chip:hover {
    border-color: var(--channel-accent);
    background: var(--channel-soft);
    transform: translateY(-1px);
}

.channel-chip--active {
    border-color: var(--channel-accent);
    background: var(--channel-soft);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
}

.channel-chip--active span {
    color: var(--channel-accent);
}

.channel-chip--takeaway {
    --channel-accent: #3b82f6;
    --channel-soft: #eff6ff;
}

.channel-chip--dine-in {
    --channel-accent: #2563eb;
    --channel-soft: #eff6ff;
}

.channel-chip--pick-up {
    --channel-accent: #22c55e;
    --channel-soft: #f0fdf4;
}

.channel-chip--drive-thru {
    --channel-accent: #ef4444;
    --channel-soft: #fef2f2;
}

.channel-chip--pre-order {
    --channel-accent: #a855f7;
    --channel-soft: #faf5ff;
}

.channel-chip--catering {
    --channel-accent: #06b6d4;
    --channel-soft: #ecfeff;
}

.channel-chip--pms {
    --channel-accent: #3b82f6;
    --channel-soft: #fff4ed;
}

.pms-offcanvas-backdrop {
    position: fixed;
    inset: 0;
    z-index: 4300;
    background: rgba(15, 23, 42, 0.38);
    display: flex;
    justify-content: flex-end;
}

.pms-offcanvas {
    width: min(460px, 100%);
    height: 100%;
    background:  #ffffff ;
    border-left: 1px solid rgba(59, 130, 246, 0.24);
    box-shadow: -24px 0 60px rgba(15, 23, 42, 0.22);
    display: flex;
    flex-direction: column;
}

.pms-offcanvas__header {
    min-height: 92px;
    padding: 20px 20px 18px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    position: relative;
}


.pms-offcanvas__eyebrow {
    margin: 0 0 3px;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0;
}

.pms-offcanvas__header h3 {
    margin: 0;
    color: #0f172a;
    font-size: 22px;
    font-weight: 900;
}

.pms-offcanvas__count {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    margin-top: 9px;
    padding: 0 10px;
    border: 1px solid rgba(59, 130, 246, 0.28);
    border-radius: 999px;
    background: #ffffff;
    color: #2563eb;
    font-size: 12px;
    font-weight: 900;
}

.pms-offcanvas__close {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 12px;
    background: #eff6ff;
    color: #2563eb;
    display: grid;
    place-items: center;
    transition: all 0.18s ease;
}

.pms-offcanvas__close:hover {
    background: #dbeafe;
    color: #1d4ed8;
}

.pms-offcanvas__tools {
    padding: 16px 20px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px;
    border-bottom: 1px solid rgba(59, 130, 246, 0.14);
}

.pms-search {
    min-height: 46px;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 12px;
    background: #ffffff;
    box-shadow: 0 10px 22px rgba(59, 130, 246, 0.08);
    transition: all 0.18s ease;
}

.pms-search:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.13);
}

.pms-search i {
    color: #3b82f6;
}

.pms-search input {
    min-width: 0;
    width: 100%;
    border: none;
    outline: none;
    color: #334155;
    font-size: 13px;
    background: transparent;
}

.pms-refresh {
    min-height: 46px;
    border: none;
    border-radius: 12px;
    background: #3b82f6;
    color: #ffffff;
    font-size: 13px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 14px;
    box-shadow: 0 14px 24px rgba(59, 130, 246, 0.22);
    transition: all 0.18s ease;
}

.pms-refresh:hover:not(:disabled) {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.pms-refresh:disabled {
    opacity: 0.65;
}

.pms-guest-list {
    padding: 16px 20px 28px;
    overflow-y: auto;
    display: grid;
    gap: 10px;
}

.pms-guest-list::-webkit-scrollbar {
    width: 8px;
}

.pms-guest-list::-webkit-scrollbar-thumb {
    background: #fdba74;
    border-radius: 999px;
}

.pms-guest-list::-webkit-scrollbar-track {
    background: #eff6ff;
}

.pms-guest-card {
    border: 1px solid #f1e2d2;
    border-radius: 14px;
    padding: 13px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.pms-guest-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 16px 30px rgba(59, 130, 246, 0.12);
    transform: translateY(-1px);
}

.pms-guest-card--selected {
    border-color: #3b82f6;
    background: linear-gradient(90deg, #eff6ff 0%, #ffffff 100%);
    box-shadow: 0 16px 34px rgba(59, 130, 246, 0.16);
}

.pms-guest-card__icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #eff6ff;
    color: #3b82f6;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    font-size: 17px;
}

.pms-guest-card--selected .pms-guest-card__icon {
    background: #dbeafe;
    color: #2563eb;
}

.pms-guest-card__content {
    min-width: 0;
    flex: 1;
}

.pms-guest-card strong {
    display: block;
    overflow: visible;
    text-overflow: clip;
    white-space: normal;
    line-height: 1.25;
    word-break: break-word;
}

.pms-guest-card strong {
    color: #0f172a;
    font-weight: 900;
}

.pms-guest-card__details {
    margin-top: 4px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 4px 10px;
}

.pms-guest-card__details span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #64748b;
    font-size: 12px;
}

.pms-guest-card__details .pms-guest-card__room-type {
    overflow: visible;
    text-overflow: clip;
    white-space: normal;
    line-height: 1.35;
    word-break: break-word;
}

.pms-guest-card__details b {
    color: #334155;
    font-weight: 900;
}

.pms-guest-card--skeleton {
    pointer-events: none;
}

.pms-guest-card--skeleton:hover {
    border-color: #f1e2d2;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
    transform: none;
}

.pms-skeleton-block,
.pms-skeleton-line,
.pms-skeleton-button {
    display: block;
    border-radius: 999px;
    background: linear-gradient(90deg, #eff6ff 0%, #bfdbfe 46%, #eff6ff 100%);
    background-size: 220% 100%;
    animation: pms-skeleton-shimmer 1.1s ease-in-out infinite;
}

.pms-skeleton-block {
    border-radius: 12px;
}

.pms-skeleton-line {
    width: 82%;
    height: 12px;
}

.pms-skeleton-line--name {
    width: 46%;
    height: 16px;
    margin-bottom: 9px;
}

.pms-skeleton-line--wide {
    width: 96%;
}

.pms-skeleton-button {
    width: 58px;
    height: 36px;
    border-radius: 10px;
    flex: 0 0 auto;
}

@keyframes pms-skeleton-shimmer {
    0% {
        background-position: 120% 0;
    }

    100% {
        background-position: -120% 0;
    }
}

.pms-select-btn {
    border: none;
    border-radius: 10px;
    background: #3b82f6;
    color: #ffffff;
    font-size: 12px;
    font-weight: 900;
    padding: 9px 12px;
    white-space: nowrap;
    box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
    transition: all 0.18s ease;
    cursor: pointer;
}

.pms-select-btn:hover:not(:disabled) {
    background: #1d4ed8;
}

.pms-select-btn:disabled {
    background: #cbd5e1;
    color: #64748b;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 0.6;
}

.pms-guest-card--selected .pms-select-btn {
    background: #2563eb;
}

.pms-guest-card em {
    color: #1d4ed8;
    font-style: normal;
    font-weight: 900;
}

.pms-currency-warning {
    display: block;
    margin-top: 7px;
    color: #b91c1c !important;
    font-size: 12px;
    line-height: 1.35;
}

.pms-selected-bar {
    margin-top: 10px;
    min-height: 52px;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    background: #eff6ff;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 11px;
}

.pms-selected-bar__icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #dbeafe;
    color: #3b82f6;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
}

.pms-selected-bar__body {
    min-width: 0;
    flex: 1;
}

.pms-selected-bar__body strong,
.pms-selected-bar__body span {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pms-selected-bar__body strong {
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
}

.pms-selected-bar__body span {
    margin-top: 2px;
    color: #2563eb;
    font-size: 12px;
    font-weight: 800;
}

.pms-selected-bar__clear {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 9px;
    background: #ffffff;
    color: #9a3412;
    flex: 0 0 auto;
}

.pms-empty {
    flex: 1;
    display: grid;
    place-items: center;
    padding: 28px;
    color: #2563eb;
    font-weight: 800;
    text-align: center;
}

.pms-empty--error {
    color: #b91c1c;
    gap: 8px;
}

.channel-strip-wrap {
    position: relative;
    min-width: 0;
}

.channel-strip {
    display: flex;
    flex-wrap: nowrap;
    gap: 10px;
    overflow-x: auto;
    overflow-y: hidden;
    padding-right: 34px;
    padding-bottom: 4px;
    scroll-behavior: smooth;
    -ms-overflow-style: none;
    scrollbar-width: none;
    min-width: 0;
}

.channel-strip::-webkit-scrollbar {
    display: none;
}

.channel-strip-arrow-right,
.channel-strip-arrow-left {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 25px;
    height: 25px;
    border: none;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.96);
    color: #2f3236;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
    z-index: 2;
    transition: all 0.16s ease;
}

.channel-strip-arrow-left {
    left: 24px;
}

.channel-strip-arrow-right {
    right: 24px;
}

.channel-strip-arrow i {
    font-size: 10px;
    line-height: 1;
}

.channel-strip-arrow:hover {
    color: #3b82f6;
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
}

.channel-strip-wrap::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 42px;
    height: 100%;
    pointer-events: none;
    background: linear-gradient(to right, rgba(255, 255, 255, 0), #ffffff 72%);
    border-radius: 0 12px 12px 0;
}
.totals-row--loyalty span,
.totals-row--loyalty strong {
    color: #7c3aed;
    font-weight: 800;
}

.totals-row--loyalty small {
    display: inline-block;
    margin-left: 4px;
    color: #8b5cf6;
    font-weight: 700;
}

.table-viewer-backdrop,
.table-detail-backdrop {
    position: fixed;
    inset: 0;
    z-index: 3300;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    justify-content: flex-end;
}

.table-detail-backdrop {
    justify-content: center;
    align-items: center;
}

.table-viewer-panel {
    width: min(620px, 100%);
    height: 100%;
    overflow: auto;
    background: #fff;
    padding: 28px 24px;
}

.table-viewer-header,
.table-detail-header,
.table-viewer-tools,
.table-card__top,
.table-detail-actions,
.merge-modal-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.table-viewer-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #1f2937;
}

.table-viewer-title h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
}

.table-viewer-title i {
    color: #7c3aed;
    font-size: 20px;
}

.table-viewer-close {
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 999px;
    background: #f3f4f6;
    display: grid;
    place-items: center;
}

.table-viewer-tools {
    margin: 22px 0 14px;
}

.table-viewer-search {
    position: relative;
    flex: 1;
}

.table-viewer-search i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #475569;
}

.table-viewer-search input,
.merge-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 14px 0 40px;
}

.merge-input {
    padding-left: 14px;
}

.table-viewer-filter,
.table-action,
.merge-modal-actions button {
    min-height: 40px;
    border: 0;
    border-radius: 6px;
    padding: 0 18px;
    font-weight: 800;
}

.table-viewer-filter {
    background: #dbeafe;
    color: #3b82f6;
    cursor: pointer;
    transition: all 0.2s ease;
}

.table-viewer-filter:hover,
.table-viewer-filter--active {
    background: #bfdbfe;
    color: #1d4ed8;
}

.table-viewer-filter-panel {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 16px;
    background: #fffdfa;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.table-viewer-filter-actions {
    grid-column: span 2;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 4px;
}

.btn-clear-filters {
    height: 34px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    padding: 0 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.15s ease;
}

.btn-clear-filters:hover {
    background: #f1f5f9;
    color: #475569;
}

.table-viewer-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.table-card {
    border: 0;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
    padding: 16px;
    text-align: left;
}

.table-card strong {
    color: #1f2937;
}

.table-card p {
    margin: 8px 0 0;
    color: #94a3b8;
    font-size: 13px;
}

.table-status {
    border-radius: 5px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 800;
}

.table-status--available {
    background: #ecfdf3;
    color: #22c55e;
}

.table-status--occupied {
    background: #fee2e2;
    color: #ef4444;
}

.table-status--merged {
    background: #dbeafe;
    color: #3b82f6;
}

.table-detail-modal,
.merge-modal {
    width: min(760px, calc(100% - 32px));
    background: #fff;
    border-radius: 10px;
    padding: 24px;
}

.merge-modal {
    width: min(520px, calc(100% - 32px));
    display: grid;
    gap: 18px;
}

.table-detail-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
    margin: 22px 0 14px;
}

.table-detail-meta span,
.table-detail-label {
    display: block;
    color: #94a3b8;
    font-size: 12px;
}

.table-detail-meta strong {
    color: #64748b;
    font-size: 14px;
}

.table-detail-section {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px dashed #e2e8f0;
}

.table-detail-section h4 {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 800;
    color: #334155;
}

.merge-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.merge-chip {
    border-radius: 6px;
    background: #e5e7eb;
    color: #64748b;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 800;
}

.merge-chip--primary {
    background: #dbeafe;
    color: #3b82f6;
}

.table-order-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 16px;
}

.table-order-card div {
    display: grid;
    gap: 4px;
}

.table-order-card span,
.table-order-card small {
    color: #94a3b8;
    font-size: 12px;
}

.table-order-card strong {
    color: #64748b;
}

.table-detail-actions {
    justify-content: flex-end;
    margin-top: 20px;
}

.table-action--merge {
    background: #e5e7eb;
    color: #64748b;
}

.table-action--order {
    background: #dcfce7;
    color: #22c55e;
}

.merge-table-list {
    max-height: 220px;
    overflow: auto;
    display: grid;
    gap: 8px;
}

.merge-table-option {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #475569;
}

.merge-modal-actions {
    justify-content: flex-end;
}

.merge-modal-actions button:last-child {
    background: #bfdbfe;
    color: #3b82f6;
}

@media (max-width: 760px) {
    .table-viewer-grid,
    .table-detail-meta {
        grid-template-columns: 1fr;
    }
}
.table-order-card--rich {
    display: block;
    padding: 16px;
    border-radius: 12px;
    background: #fff;
    border-bottom: 1px dashed #e5e7eb;
}

.table-order-card__top {
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.table-order-card__time {
    display: block;
    color: #a0a8b3;
    font-size: 12px;
    margin-bottom: 10px;
}

.table-order-card__title {
    display: block;
    color: #64748b;
    font-size: 14px;
    margin-bottom: 4px;
}

.table-order-card__items {
    display: block;
    color: #a0a8b3;
    font-size: 12px;
}

.table-order-card__total {
    color: #64748b;
    font-size: 14px;
    white-space: nowrap;
}

.table-order-card__badges {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 10px;
}

.table-order-status,
.table-payment-status {
    min-height: 26px;
    padding: 0 10px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 700;
}

.table-order-status--pending {
    background: #eff6ff;
    color: #2563eb;
}

.table-order-status--preparing {
    background: #efe5ff;
    color: #8b5cf6;
}

.table-order-status--ready {
    background: #dcfce7;
    color: #16a34a;
}

.table-order-status--served {
    background: #d7f6f1;
    color: #0f766e;
}

.table-payment-status--unpaid {
    background: #ffe4e0;
    color: #ef4444;
}

.table-payment-status--paid {
    background: #dcfce7;
    color: #16a34a;
}

.table-payment-status--partial {
    background: #eff6ff;
    color: #2563eb;
}

.table-order-card__actions {
    margin-top: 14px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.table-order-card__actions-left,
.table-order-card__actions-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.table-order-icon-btn,
.table-order-action {
    min-height: 38px;
    border: 0;
    border-radius: 8px;
    padding: 0 13px;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
}

.table-order-icon-btn {
    background: #eef0f3;
    color: #6b7280;
    min-width: 40px;
}

.table-order-icon-btn--print {
    min-width: 98px;
}

.table-order-action--pay,
.table-order-action--ready {
    background: #d8f5e5;
    color: #22b36f;
}

.table-order-action--prepare {
    background: #efe5ff;
    color: #8b5cf6;
}

.table-cancel-btn--ghost {
    background: #f1f5f9;
    color: #64748b;
}

.table-cancel-btn--danger {
    background: #ef4444;
    color: #fff;
}

@media (max-width: 720px) {
    .table-detail-meta--pro,
    .table-cancel-grid {
        grid-template-columns: 1fr;
    }

    .table-order-card__top,
    .table-order-card__actions {
        flex-direction: column;
        align-items: stretch;
    }

    .table-order-card__badges {
        justify-content: flex-start;
    }
}

.table-viewer-grid--skeleton {
    pointer-events: none;
}

.table-card--skeleton {
    cursor: default;
    border: 1px solid #edf2f7;
    background: #ffffff;
    overflow: hidden;
    position: relative;
}

.table-card--skeleton::after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.75),
        transparent
    );
    animation: tableSkeletonMove 1.15s infinite;
}

.table-skeleton-line,
.table-skeleton-pill {
    background: #edf2f7;
    border-radius: 999px;
}

.table-skeleton-line--name {
    width: 72px;
    height: 15px;
}

.table-skeleton-line--meta {
    width: 120px;
    height: 13px;
    margin-top: 14px;
}

.table-skeleton-pill {
    width: 78px;
    height: 24px;
    border-radius: 7px;
}

.table-viewer-empty {
    min-height: 260px;
    display: grid;
    place-items: center;
    align-content: center;
    gap: 8px;
    text-align: center;
    color: #94a3b8;
}

.table-viewer-empty i {
    font-size: 34px;
    color: #cbd5e1;
}

.table-viewer-empty strong {
    color: #475569;
    font-size: 15px;
    font-weight: 900;
}

.table-viewer-empty span {
    font-size: 13px;
    color: #94a3b8;
}

@keyframes tableSkeletonMove {
    100% {
        transform: translateX(100%);
    }
}
.summary-actions--dine-in {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.summary-actions--dine-in .summary-action--cancel {
    background: #ef4438;
    color: #ffffff;
}

.summary-actions--dine-in .summary-action--hold {
    background: #f5d313;
    color: #ffffff;
}

.summary-actions--dine-in .summary-action {
    min-height: 54px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 800;
}

.summary-actions--dine-in .summary-action i {
    font-size: 18px;
}

.summary-actions--dine-in .summary-action--disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.selected-table-strip {
    width: 100%;
    min-height: 64px;
    border: 1px solid #edf2f7;
    border-radius: 12px;
    background: #ffffff;
    display: grid;
    grid-template-columns: 1fr 42px;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    margin-bottom: 10px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.035);
}

.selected-table-strip__body {
    border: none;
    background: transparent;
    padding: 0;
    text-align: left;
    cursor: pointer;
}

.selected-table-strip strong {
    display: block;
    color: #334155;
    font-size: 14px;
    font-weight: 900;
    line-height: 1.2;
}

.selected-table-strip span {
    display: block;
    margin-top: 5px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 700;
}

.selected-table-strip__remove {
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 10px;
    background: #eff6ff;
    color: #3b82f6;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: all 0.16s ease;
}

.selected-table-strip__remove:hover {
    background: #dbeafe;
    color: #1d4ed8;
}

.selected-table-strip--empty {
    grid-template-columns: 1fr 36px;
    border-style: dashed;
    color: #64748b;
    cursor: pointer;
}

.selected-table-strip--empty i {
    color: #22c55e;
    font-size: 18px;
}
</style>
