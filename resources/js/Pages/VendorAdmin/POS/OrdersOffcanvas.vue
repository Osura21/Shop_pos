<template>
  <transition name="orders-offcanvas">
    <div v-if="show" ref="ordersBackdrop" class="orders-offcanvas-backdrop" @click.self="close">
      <div ref="ordersPanel" class="orders-offcanvas">
        <div class="orders-offcanvas__header">
          <div class="orders-offcanvas__title-wrap">
            <div class="orders-offcanvas__title-icon">
              <i class="bi bi-basket"></i>
            </div>
            <h2>Order Management</h2>
          </div>

          <button type="button" class="orders-offcanvas__close" @click="close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="orders-tabs">
          <button type="button" class="orders-tab" :class="{ 'orders-tab--active': activeTab === 'active' }"
            @click="activeTab = 'active'">
            Active Orders
          </button>

          <button type="button" class="orders-tab" :class="{ 'orders-tab--active': activeTab === 'held' }"
            @click="activeTab = 'held'">
            Hold Orders
          </button>
        </div>

        <div class="orders-toolbar">
          <div class="orders-search">
            <i class="bi bi-search"></i>
            <input v-model="search" type="text" placeholder="Search by order number.." @input="debouncedLoad" />
          </div>

          <button type="button" class="orders-filter-btn" @click="showFilters = !showFilters">
            <i class="bi bi-funnel"></i>
            <span>Filters</span>
          </button>
        </div>

        <transition name="fade-fast">
          <div v-if="showFilters" class="orders-filters">
            <div class="orders-filters__grid">
              <select v-model="filters.channel" class="orders-filter-control" @change="loadOrders">
                <option value="">All Channels</option>
                <option value="takeaway">Takeaway</option>
                <option value="pick_up">Pick-up</option>
              </select>

              <select v-model="filters.status" class="orders-filter-control" @change="loadOrders">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="held">Held</option>
                <option value="confirmed">Confirmed</option>
                <option value="preparing">Preparing</option>
                <option value="ready">Ready</option>
                <option value="served">Served</option>
                <option value="cancelled">Cancelled</option>
              </select>

              <select v-model="filters.payment_status" class="orders-filter-control" @change="loadOrders">
                <option value="">All Payments</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
              </select>
            </div>
          </div>
        </transition>

        <div class="orders-content">
          <div v-if="loading" class="orders-skeleton-list">
            <div v-for="n in 5" :key="`sk-${n}`" class="order-skeleton-card">
              <div class="order-skeleton-card__top"></div>
              <div class="order-skeleton-card__mid"></div>
              <div class="order-skeleton-card__actions"></div>
            </div>
          </div>

          <div v-else-if="currentOrders.length" class="orders-list">
            <article v-for="order in currentOrders" :key="order.id" class="order-row">
              <div class="order-row__main">
                <div class="order-row__icon-block">
                  <div class="order-row__icon" :class="channelIconClass(order.channel)">
                    <i :class="channelIcon(order.channel)"></i>
                  </div>
                  <div class="order-row__channel">{{ prettyLabel(order.channel) }}</div>
                </div>

                <div class="order-row__content">
                  <div class="order-row__top">
                    <span class="order-row__time">
                      {{ formatTime(order.sent_to_kitchen_at || order.created_at) }}
                    </span>

                    <strong class="order-row__total">
                      {{ currencySymbol(order.currency_code || 'LKR') }} {{ money(order.grand_total) }}
                    </strong>
                  </div>

                  <h3 class="order-row__title">
                    #{{ orderNumber(order) }} - {{ order.customer_name || order.customer?.name || 'Walk-In Customer' }}
                  </h3>

                  <p class="order-row__items">
                    {{ order.items?.length || 0 }} Items: {{ itemSummary(order.items) }}
                  </p>

                  <div class="order-row__badges">
                    <span class="status-chip" :class="statusChipClass(order.status)">
                      <i v-if="statusIcon(order.status)" :class="statusIcon(order.status)"></i>
                      {{ statusLabel(order.status) }}
                    </span>

                    <button v-if="needsPmsPaymentSync(order)" type="button" class="status-sync-btn"
                      :class="{ 'status-sync-btn--loading': pmsSyncingId === order.id }"
                      :disabled="pmsSyncingId === order.id"
                      :aria-label="pmsSyncingId === order.id ? 'Syncing PMS status' : 'Sync PMS status'"
                      :data-tooltip="pmsSyncingId === order.id ? 'Syncing PMS status...' : 'Sync PMS status'"
                      @click="syncPmsPaymentStatus(order)">
                      <i class="bi bi-arrow-repeat"></i>
                    </button>

                    <span class="status-chip status-chip--payment" :class="paymentChipClass(order.payment_status)">
                      {{ prettyLabel(order.payment_status || 'unpaid') }}
                    </span>

                    <span v-if="order.pms_posting_status" class="status-chip status-chip--pms"
                      :class="pmsChipClass(order.pms_posting_status)">
                      PMS {{ prettyLabel(order.pms_posting_status) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="order-row__actions">
                <div class="order-row__actions-left">
                  <button type="button" class="icon-btn" @click="openViewModal(order)" title="View order">
                    <i class="bi bi-eye"></i>
                  </button>

                  <button type="button" class="icon-btn" @click="editOrder(order)"
                    :disabled="order.status === 'cancelled'"
                    :title="order.status === 'held' ? 'Restore order' : 'Edit order'">
                    <i :class="order.status === 'held' ? 'bi bi-arrow-counterclockwise' : 'bi bi-pencil-square'"></i>
                  </button>

                  <button v-if="order.payment_status !== 'paid' && order.status !== 'cancelled' && order.status === 'pending'" type="button"
                    class="icon-btn icon-btn--danger" title="Cancel order" @click="openCancelModal(order)">
                    <i class="bi bi-x-lg"></i>
                  </button>

                  <button type="button" class="icon-btn icon-btn--print" @click="openPrintModal(order)" title="Print">
                    <i class="bi bi-printer"></i>
                    <span class="icon-btn__label">Print</span>
                  </button>
                </div>

                <div class="order-row__actions-right">
                  <button v-if="canPayOrder(order)" type="button" class="action-btn action-btn--pay"
                    :disabled="isPayBusy(order)" @click="requestPay(order)">
                    <i :class="isPayBusy(order) ? 'bi bi-arrow-repeat' : 'bi bi-cash-stack'"></i>
                    <span>{{ payButtonLabel(order) }}</span>
                  </button>

                  <button v-if="nextStatusAction(order)" type="button" class="action-btn"
                    :class="nextStatusAction(order).class" :disabled="statusSubmittingId === order.id"
                    @click="changeStatus(order)">
                    <i :class="nextStatusAction(order).icon"></i>
                    <span>
                      {{ statusSubmittingId === order.id ? 'Please wait...' : nextStatusAction(order).label }}
                    </span>
                  </button>

                  <button v-if="canRetryPms(order)" type="button" class="action-btn action-btn--pms"
                    :disabled="pmsSyncingId === order.id" @click="retryPmsOrder(order)">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>{{ pmsSyncingId === order.id ? 'Syncing...' : 'Retry PMS' }}</span>
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="orders-empty">
            <i class="bi bi-basket"></i>
            <h3>No orders found</h3>
            <p>There are no orders for the current filter.</p>
          </div>
        </div>
      </div>

      <!-- cancel modal -->
      <div v-if="showCancelModal" class="modal-backdrop" @click.self="closeCancelModal">
        <div class="cancel-modal">
          <h3>Cancel Order</h3>
          <p>
            You are about to cancel this order. Please provide a reason for cancellation.
            No payment will be refunded unless the order was already paid.
          </p>

          <div class="cancel-grid">
            <div class="field-wrap">
              <label>Pos Register</label>
              <input type="text" class="modal-input" :value="cancelTarget?.register?.name || '-'" readonly />
            </div>

            <div class="field-wrap">
              <label>Reason</label>
              <select v-model="cancelForm.cancel_reason" class="modal-input">
                <option value="">Reason</option>
                <option v-for="reason in cancelReasons" :key="reason" :value="reason">
                  {{ reason }}
                </option>
              </select>
            </div>
          </div>

          <div class="field-wrap">
            <label>Note</label>
            <textarea v-model="cancelForm.cancel_note" class="modal-input modal-input--textarea"
              placeholder="Note"></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn--ghost" @click="closeCancelModal">
              Cancel
            </button>
            <button type="button" class="modal-btn modal-btn--primary"
              :disabled="cancelSubmitting || !cancelForm.cancel_reason" @click="submitCancel">
              {{ cancelSubmitting ? 'Submitting...' : 'Submit' }}
            </button>
          </div>
        </div>
      </div>

      <!-- view modal -->
      <div v-if="showViewModal" class="modal-backdrop modal-backdrop--full" @click.self="closeViewModal">
        <div class="details-modal">
          <div class="details-modal__header">
            <h3>Order Details</h3>
            <button type="button" class="orders-offcanvas__close" @click="closeViewModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="details-modal__body">
            <div v-if="detailsLoading" class="details-skeleton">
              <div class="details-skeleton__line" v-for="n in 8" :key="n"></div>
            </div>

            <template v-else-if="viewOrderData">
              <div class="details-layout">
                <div class="details-main">
                  <div class="details-card">
                    <h4>Order Information</h4>

                    <div class="details-grid">
                      <div><strong>Reference No</strong><span>{{ viewOrderData.reference_no }}</span></div>
                      <div><strong>Order Number</strong><span>{{ viewOrderData.order_number }}</span></div>
                      <div><strong>Branch</strong><span>{{ viewOrderData.branch }}</span></div>
                      <div><strong>Status</strong><span>{{ statusLabel(viewOrderData.status) }}</span></div>
                      <div><strong>Type</strong><span>{{ prettyLabel(viewOrderData.type) }}</span></div>
                      <div><strong>Payment Status</strong><span>{{ prettyLabel(viewOrderData.payment_status) }}</span>
                      </div>
                      <div><strong>Guest count</strong><span>{{ viewOrderData.guest_count }}</span></div>
                      <div><strong>Created By</strong><span>{{ viewOrderData.created_by }}</span></div>
                      <div><strong>Waiter</strong><span>{{ viewOrderData.waiter }}</span></div>
                      <div><strong>Cashier</strong><span>{{ viewOrderData.cashier }}</span></div>
                      <div><strong>Pos Register</strong><span>{{ viewOrderData.pos_register }}</span></div>
                      <div><strong>Pos Session Id</strong><span>{{ viewOrderData.pos_session_id }}</span></div>
                      <div><strong>Created At</strong><span>{{ formatDateTime(viewOrderData.created_at) }}</span></div>
                      <div><strong>Updated At</strong><span>{{ formatDateTime(viewOrderData.updated_at) }}</span></div>
                    </div>
                  </div>

                  <div class="details-card">
                    <h4>Customer Information</h4>
                    <div class="details-grid details-grid--two">
                      <div><strong>Name</strong><span>{{ viewOrderData.customer_name || viewOrderData.customer?.name
                          }}</span></div>
                      <div><strong>Phone</strong><span>{{ viewOrderData.customer_phone }}</span></div>
                    </div>
                  </div>

                  <div class="details-card">
                    <h4>Products</h4>

                    <!-- Desktop table -->
                    <div class="products-table products-table--desktop">
                      <div class="products-table__head">
                        <span>Product</span>
                        <span>Status</span>
                        <span>Unit Price</span>
                        <span>Qty</span>
                        <span>Subtotal</span>
                        <span>Tax</span>
                        <span>Total</span>
                        <span>Cost</span>
                        <span>Revenue</span>
                      </div>

                      <div v-for="item in viewOrderData.items" :key="item.id" class="products-table__row">
                        <div class="product-col">
                          <strong>{{ item.product_name }}</strong>
                          <div v-for="option in item.options" :key="option.label + option.value" class="product-option">
                            {{ option.label }} : {{ option.value }}
                            <template v-if="Number(option.price) > 0">
                              ({{ currencySymbol(viewOrderData.currency) }} {{ money(option.price) }})
                            </template>
                          </div>
                        </div>

                        <span>{{ prettyLabel(item.status) }}</span>
                        <span>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.unit_price) }}</span>
                        <span>{{ trimQty(item.qty) }}</span>
                        <span>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.subtotal) }}</span>
                        <span>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.tax_total) }}</span>
                        <span>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.line_total) }}</span>
                        <span>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.cost_price) }}</span>
                        <span>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.revenue) }}</span>
                      </div>
                    </div>

                    <!-- Mobile cards -->
                    <div class="products-cards products-table--mobile">
                      <div v-for="item in viewOrderData.items" :key="'m-' + item.id" class="product-card">
                        <div class="product-card__header">
                          <strong class="product-card__name">{{ item.product_name }}</strong>
                          <span class="status-chip" style="font-size:11px;padding:0 8px;min-height:24px;">{{
                            prettyLabel(item.status)
                            }}</span>
                        </div>
                        <div v-for="option in item.options" :key="option.label + option.value" class="product-option">
                          {{ option.label }} : {{ option.value }}
                          <template v-if="Number(option.price) > 0">
                            ({{ currencySymbol(viewOrderData.currency) }} {{ money(option.price) }})
                          </template>
                        </div>
                        <div class="product-card__grid">
                          <div class="product-card__field">
                            <span>Unit Price</span>
                            <strong>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.unit_price) }}</strong>
                          </div>
                          <div class="product-card__field">
                            <span>Qty</span>
                            <strong>{{ trimQty(item.qty) }}</strong>
                          </div>
                          <div class="product-card__field">
                            <span>Subtotal</span>
                            <strong>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.subtotal) }}</strong>
                          </div>
                          <div class="product-card__field">
                            <span>Tax</span>
                            <strong>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.tax_total) }}</strong>
                          </div>
                          <div class="product-card__field">
                            <span>Total</span>
                            <strong>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.line_total) }}</strong>
                          </div>
                          <div class="product-card__field">
                            <span>Revenue</span>
                            <strong>{{ currencySymbol(viewOrderData.currency) }} {{ money(item.revenue) }}</strong>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="details-side">
                  <div class="details-card">
                    <h4>Order Summary</h4>
                    <div class="summary-list">
                      <div><span>Currency</span><strong>{{ currencySymbol(viewOrderData.currency) }}</strong></div>
                      <div><span>Currency Rate</span><strong>{{ viewOrderData.currency_rate }}</strong></div>
                      <div><span>Cost Price</span><strong>{{ currencySymbol(viewOrderData.currency) }} {{
                        money(viewOrderData.cost_price) }}</strong></div>
                      <div><span>Revenue</span><strong>{{ currencySymbol(viewOrderData.currency) }} {{
                        money(viewOrderData.revenue)
                          }}</strong></div>
                      <div><span>Subtotal</span><strong>{{ currencySymbol(viewOrderData.currency) }} {{
                        money(viewOrderData.subtotal)
                          }}</strong></div>
                      <div><span>VAT 15%</span><strong>{{ currencySymbol(viewOrderData.currency) }} {{
                        money(viewOrderData.tax_total)
                          }}</strong></div>
                      <div class="summary-list__total"><span>Total</span><strong>{{
                        currencySymbol(viewOrderData.currency) }} {{
                            money(viewOrderData.grand_total) }}</strong></div>
                    </div>
                  </div>

                  <div class="details-card">
                    <h4>Notes</h4>
                    <p class="side-text">{{ viewOrderData.notes || 'No notes available' }}</p>
                  </div>

                  <div class="details-card">
                    <h4>Payments</h4>
                    <p class="side-text">
                      {{ viewOrderData.payments?.length ? 'Payment data loaded' : 'No data available' }}
                    </p>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- print modal -->
      <div v-if="showPrintModal" class="modal-backdrop" @click.self="closePrintModal">
        <div class="print-docs-modal">
          <div class="print-docs-modal__header">
            <h3>Print Order Documents</h3>
          </div>

          <p class="print-docs-modal__desc">
            Select the required document types to print for this order, including invoice,
            kitchen ticket, waiter copy, and delivery slip.
          </p>

          <div class="print-docs-list">
            <div class="print-docs-row">
              <div class="print-docs-row__text">
                <h4>Print Bill</h4>
                <span>Bill</span>
              </div>

              <div class="print-docs-row__actions">
                <button type="button" class="print-docs-btn print-docs-btn--preview" title="Preview"
                  @click="previewPrint('bill')">
                  <i class="bi bi-eye"></i>
                </button>

                <button type="button" class="print-docs-btn print-docs-btn--print" title="Print"
                  @click="doPrint('bill')">
                  <i class="bi bi-printer"></i>
                </button>

                <button type="button" class="print-docs-btn print-docs-btn--download" title="Download"
                  @click="downloadPrint('bill')">
                  <i class="bi bi-download"></i>
                </button>
              </div>
            </div>

            <div class="print-docs-row">
              <div class="print-docs-row__text">
                <h4>Print For Waiter</h4>
                <span>Waiter</span>
              </div>

              <div class="print-docs-row__actions">
                <button type="button" class="print-docs-btn print-docs-btn--preview" title="Preview"
                  @click="previewPrint('waiter')">
                  <i class="bi bi-eye"></i>
                </button>

                <button type="button" class="print-docs-btn print-docs-btn--print" title="Print"
                  @click="doPrint('waiter')">
                  <i class="bi bi-printer"></i>
                </button>

                <button type="button" class="print-docs-btn print-docs-btn--download" title="Download"
                  @click="downloadPrint('waiter')">
                  <i class="bi bi-download"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="print-docs-modal__footer">
            <button type="button" class="modal-btn modal-btn--ghost" @click="closePrintModal">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

  </transition>
</template>

<script>
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import { currencySymbol } from "@/Utils/currency";

export default {
  name: "OrdersOffcanvas",
  props: {
    show: { type: Boolean, default: false },
    registerId: { type: [Number, String], default: null },
    branchId: { type: [Number, String], default: null },
    sessionId: { type: [Number, String], default: null },
  },
  emits: ["close", "pay-order"],
  data() {
    return {
      pollTimer: null,
      showPrintModal: false,
      printTarget: null,
      loading: false,
      search: "",
      activeTab: "active",
      activeOrders: [],
      upcomingOrders: [],
      heldOrders: [],
      debounceTimer: null,
      showFilters: false,
      statusSubmittingId: null,
      pmsSyncingId: null,
      payingOrderId: null,
      autoSyncedPaymentIds: {},
      filters: {
        channel: "",
        status: "",
        payment_status: "",
      },

      showCancelModal: false,
      cancelSubmitting: false,
      cancelTarget: null,
      cancelReasons: [
        "Customer Request",
        "Item Not Available",
        "Duplicate Order",
        "Kitchen Delay",
        "Wrong Order",
      ],
      cancelForm: {
        cancel_reason: "",
        cancel_note: "",
      },

      showViewModal: false,
      detailsLoading: false,
      viewOrderData: null,
    };
  },
  computed: {
    currentOrders() {
      if (this.activeTab === "held") return this.heldOrders;
      return this.activeTab === "upcoming" ? this.upcomingOrders : this.activeOrders;
    },
  },
  watch: {
    show: {
      immediate: true,
      handler(value) {
        if (value) {
          this.loadOrders();
          this.startPolling();
        } else {
          this.stopPolling();
        }
      },
    },
    registerId() {
      if (this.show) this.loadOrders();
    },
    branchId() {
      if (this.show) this.loadOrders();
    },
  },
  beforeUnmount() {
    this.stopPolling();
    clearTimeout(this.debounceTimer);
  },
  methods: {
    currencySymbol,

    openPrintModal(order) {
      this.printTarget = order;
      this.showPrintModal = true;
    },

    closePrintModal() {
      this.showPrintModal = false;
      this.printTarget = null;
    },

    previewPrint(type) {
      if (!this.printTarget?.id) return;
      const url = route("vendor.pos.orders.print", this.printTarget.id) + `?copy=${type}&preview=1`;
      window.open(url, "_blank");
    },

    doPrint(type) {
      if (!this.printTarget?.id) return;
      const url = route("vendor.pos.orders.print", this.printTarget.id) + `?copy=${type}&print=1`;
      const win = window.open(url, "_blank");
      if (!win) return;
      const timer = setInterval(() => {
        if (win.closed) { clearInterval(timer); return; }
        try { win.focus(); win.print(); clearInterval(timer); } catch (e) { }
      }, 500);
    },

    downloadPrint(type) {
      if (!this.printTarget?.id) return;
      const url = route("vendor.pos.orders.print", this.printTarget.id) + `?copy=${type}&download=1`;
      window.open(url, "_blank");
    },

    startPolling() {
      this.stopPolling();
      this.pollTimer = setInterval(() => {
        if (!this.show) return;
        if (this.showViewModal || this.showCancelModal) return;
        this.loadOrders(false);
      }, 5000);
    },

    stopPolling() {
      if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; }
    },

    close() {
      this.$emit("close");
    },

    requestPay(order) {
      if (!order?.id || this.payingOrderId || this.pmsSyncingId === order.id) return;
      this.payingOrderId = order.id;
      if (this.needsPmsPaymentSync(order) && !this.pmsSyncingId) { this.pmsSyncingId = order.id; }
      this.$emit("pay-order", order, () => {
        if (this.pmsSyncingId === order.id) { this.pmsSyncingId = null; }
        if (this.payingOrderId === order.id) { this.payingOrderId = null; }
      });
    },

    async editOrder(order) {
      if (!this.sessionId) { this.toastError("No live POS session found for editing."); return; }
      try {
        await axios.patch(route("vendor.pos.orders.edit-ticket", order.id), { session_id: this.sessionId });
        this.$emit("close");
        router.reload({
          only: ["session", "selectedRegister", "selectedBranchId", "selectedMenuId", "currencyCode", "hasActiveSession"],
          preserveScroll: true,
          preserveState: true,
        });
        this.toastSuccess(order.status === "held" ? "Hold order restored." : "Order loaded for editing.");
      } catch (error) {
        this.toastError(error?.response?.data?.message || "Unable to load order for editing.");
      }
    },

    debouncedLoad() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => { this.loadOrders(); }, 350);
    },

    async loadOrders(showLoader = true, options = {}) {
      if (showLoader) this.loading = true;
      try {
        const { data } = await axios.get(route("vendor.pos.orders.list"), {
          params: {
            register_id: this.registerId || undefined,
            branch_id: this.branchId || undefined,
            search: this.search || undefined,
            channel: this.filters.channel || undefined,
            status: this.filters.status || undefined,
            payment_status: this.filters.payment_status || undefined,
          },
          headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });

        this.activeOrders = Array.isArray(data.active_orders) ? data.active_orders : [];
        this.upcomingOrders = Array.isArray(data.upcoming_orders) ? data.upcoming_orders : [];
        this.heldOrders = Array.isArray(data.held_orders) ? data.held_orders : [];

        if (!options.skipAutoSync) { this.autoSyncPmsPaymentStatuses(); }

        if (this.showViewModal && this.viewOrderData?.id) {
          const fresh = [...this.activeOrders, ...this.upcomingOrders, ...this.heldOrders].find(
            (row) => Number(row.id) === Number(this.viewOrderData.id)
          ) || null;
          if (fresh) { await this.openViewModal(fresh); }
        }
      } catch (error) {
        if (showLoader) {
          this.activeOrders = [];
          this.upcomingOrders = [];
          this.heldOrders = [];
          this.toastError("Unable to load orders.");
        }
      } finally {
        if (showLoader) this.loading = false;
      }
    },

    openCancelModal(order) {
      this.cancelTarget = order;
      this.cancelForm.cancel_reason = "";
      this.cancelForm.cancel_note = "";
      this.showCancelModal = true;
    },

    closeCancelModal() {
      this.showCancelModal = false;
      this.cancelSubmitting = false;
      this.cancelTarget = null;
      this.cancelForm.cancel_reason = "";
      this.cancelForm.cancel_note = "";
    },

    async submitCancel() {
      if (!this.cancelTarget?.id || !this.cancelForm.cancel_reason) return;
      this.cancelSubmitting = true;
      try {
        const { data } = await axios.patch(
          route("vendor.pos.orders.cancel-ticket", this.cancelTarget.id),
          { cancel_reason: this.cancelForm.cancel_reason, cancel_note: this.cancelForm.cancel_note || null },
          { headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" } }
        );
        this.closeCancelModal();
        await this.loadOrders(false);
        router.reload({ preserveScroll: true, preserveState: true, only: ["session"] });
        this.toastSuccess(data?.message || "Order cancelled successfully.");
      } catch (error) {
        this.toastError(error?.response?.data?.message || "Unable to cancel order.");
      } finally {
        this.cancelSubmitting = false;
      }
    },

    async openViewModal(order) {
      this.showViewModal = true;
      this.detailsLoading = true;
      this.viewOrderData = null;
      try {
        const { data } = await axios.get(route("vendor.pos.orders.details", order.id));
        this.viewOrderData = data.order || null;
      } catch (error) {
        this.toastError("Unable to load order details.");
      } finally {
        this.detailsLoading = false;
      }
    },

    closeViewModal() {
      this.showViewModal = false;
      this.detailsLoading = false;
      this.viewOrderData = null;
    },

    nextStatusAction(order) {
      const status = String(order.status || "").toLowerCase();
      if (status === "pending") return { label: "Prepare", icon: "bi bi-bag", class: "action-btn--prepare", routeName: "vendor.pos.kitchen.start-preparing" };
      if (status === "preparing") return { label: "Ready", icon: "bi bi-check2-circle", class: "action-btn--ready", routeName: "vendor.pos.kitchen.mark-ready" };
      if (status === "ready") return { label: "Complete", icon: "bi bi-check-circle", class: "action-btn--complete", routeName: "vendor.pos.kitchen.mark-served" };
      return null;
    },

    async changeStatus(order) {
      const action = this.nextStatusAction(order);
      if (!action) return;
      this.statusSubmittingId = order.id;
      router.patch(route(action.routeName, order.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: async () => {
          await this.loadOrders(false);
          const flashSuccess = this.$page?.props?.flash?.success;
          this.toastSuccess(flashSuccess || `Order ${action.label.toLowerCase()} successfully.`);
        },
        onError: (errors) => { this.toastError(this.errorMessage(errors, "Unable to update order status.")); },
        onFinish: () => { this.statusSubmittingId = null; },
      });
    },

    canRetryPms(order) {
      return ["pending", "failed"].includes(String(order?.pms_posting_status || "").toLowerCase());
    },

    needsPmsPaymentSync(order) {
      return order?.channel === "pms"
        && order?.status !== "cancelled"
        && order?.payment_status !== "paid"
        && String(order?.pms_posting_status || "").toLowerCase() === "posted";
    },

    canPayOrder(order) {
      return order?.payment_status !== "paid" && order?.status !== "cancelled";
    },

    isPayBusy(order) {
      return Boolean(this.payingOrderId) || this.pmsSyncingId === order?.id;
    },

    payButtonLabel(order) {
      if (this.pmsSyncingId === order?.id) return "Syncing...";
      if (this.payingOrderId === order?.id) return "Opening...";
      return "Pay";
    },

    async syncPmsPaymentStatus(order) {
      if (!order?.id || this.pmsSyncingId) return;
      await this.runPmsPaymentStatusSync(order, { reload: true, silent: false });
    },

    async runPmsPaymentStatusSync(order, { reload = true, silent = false } = {}) {
      if (!order?.id) return null;
      this.pmsSyncingId = order.id;
      try {
        const { data } = await axios.get(route("vendor.pos.orders.pms-payment-status", order.id), {
          params: { force: 1 },
          headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        });
        if (reload) { await this.loadOrders(false, { skipAutoSync: true }); }
        if (data?.status === false) {
          if (!silent) this.toastError(data?.message || "Unable to sync PMS payment status.");
          return data;
        }
        if (!silent) { this.toastSuccess(`PMS status synced: ${this.prettyLabel(data?.payment_status || order.payment_status || "unpaid")}.`); }
        return data;
      } catch (error) {
        if (!silent) this.toastError(error?.response?.data?.message || "Unable to sync PMS payment status.");
        return null;
      } finally {
        this.pmsSyncingId = null;
      }
    },

    async autoSyncPmsPaymentStatuses() {
      if (this.pmsSyncingId) return;
      const orders = [...this.activeOrders, ...this.upcomingOrders, ...this.heldOrders]
        .filter((order) => this.needsPmsPaymentSync(order) && !this.autoSyncedPaymentIds[order.id]);
      if (!orders.length) return;
      for (const order of orders) {
        this.autoSyncedPaymentIds = { ...this.autoSyncedPaymentIds, [order.id]: true };
        await this.runPmsPaymentStatusSync(order, { reload: false, silent: true });
      }
      await this.loadOrders(false, { skipAutoSync: true });
    },

    async retryPmsOrder(order) {
      if (!order?.id || this.pmsSyncingId) return;
      this.pmsSyncingId = order.id;
      try {
        const { data } = await axios.post(route("vendor.pms.order.retry", order.id));
        await this.loadOrders(false);
        this.toastSuccess(data?.message || "PMS order synced.");
      } catch (error) {
        this.toastError(error?.response?.data?.message || "Unable to sync PMS order.");
      } finally {
        this.pmsSyncingId = null;
      }
    },

    errorMessage(errors, fallback) {
      if (errors?.general) return errors.general;
      const first = Object.values(errors || {})[0];
      if (Array.isArray(first)) return first[0] || fallback;
      if (typeof first === "string") return first;
      return this.$page?.props?.errors?.general || this.$page?.props?.flash?.error || fallback;
    },

    toastSuccess(message) { alertSuccess(message); },
    toastError(message) { alertError(this.toastHtml(message)); },

    toastHtml(message) {
      if (String(message || "").startsWith("STOCK_SHORTAGE|")) {
        const [, ingredient = "-", available = "0.000", needed = "0.000"] = String(message).split("|");
        const escape = (v) => String(v).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        return `<div class="stock-shortage-toast"><strong>Not Enough Stock</strong><div><span>Ingredient</span><em>-></em><b>${escape(ingredient)}</b></div><div><span>Available</span><em>-></em><b>${escape(available)}</b></div><div><span>Needed</span><em>-></em><b>${escape(needed)}</b></div></div>`;
      }
      const lines = String(message || "").split(/\r?\n/).filter(Boolean);
      const escape = (v) => String(v).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
      if (lines.length <= 1) return `<div class="orders-toast-message"><strong>${escape(lines[0] || message)}</strong></div>`;
      const [title, ...details] = lines;
      return `<div class="orders-toast-message"><strong>${escape(title)}</strong>${details.map((l) => `<span>${escape(l)}</span>`).join("")}</div>`;
    },

    orderNumber(order) { return order.uuid ? String(order.uuid).slice(0, 2).toUpperCase() : order.id; },
    itemSummary(items) { return (items || []).slice(0, 3).map((item) => item.product_name).join(", "); },

    formatTime(value) {
      if (!value) return "-";
      try { return new Date(value).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }); } catch (e) { return value; }
    },

    formatDateTime(value) {
      if (!value) return "-";
      try { return new Date(value).toLocaleString(); } catch (e) { return value; }
    },

    trimQty(value) {
      const numeric = Number(value || 0);
      return Number.isInteger(numeric) ? numeric : numeric.toFixed(3);
    },

    money(value) { return Number(value || 0).toFixed(3); },

    prettyLabel(value) {
      return String(value || "").replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
    },

    statusLabel(status) {
      const map = { pending: "Pending", confirmed: "Confirmed", preparing: "Preparing", ready: "Ready", served: "Served", cancelled: "Cancelled", held: "Held" };
      return map[status] || this.prettyLabel(status);
    },

    statusIcon(status) {
      const map = { pending: "bi bi-hourglass-split", confirmed: "bi bi-check-lg", preparing: "bi bi-bag", ready: "bi bi-check2-circle", served: "bi bi-box-seam", held: "bi bi-pause-circle" };
      return map[status] || "";
    },

    statusChipClass(status) {
      const map = { pending: "status-chip--pending", confirmed: "status-chip--confirmed", preparing: "status-chip--preparing", ready: "status-chip--ready", served: "status-chip--served", cancelled: "status-chip--cancelled", held: "status-chip--held" };
      return map[status] || "status-chip--confirmed";
    },

    paymentChipClass(status) {
      const map = { paid: "status-chip--paid", partial: "status-chip--partial", unpaid: "status-chip--unpaid" };
      return map[status] || "status-chip--unpaid";
    },

    pmsChipClass(status) {
      const map = { posted: "status-chip--paid", pending: "status-chip--partial", failed: "status-chip--unpaid" };
      return map[String(status || "").toLowerCase()] || "status-chip--partial";
    },

    channelIcon(channel) {
      const map = { takeaway: "bi bi-bag", dine_in: "bi bi-cup-hot", pick_up: "bi bi-box-seam", drive_thru: "bi bi-car-front", pre_order: "bi bi-calendar-event", catering: "bi bi-people", pms: "bi bi-building-check" };
      return map[channel] || "bi bi-basket";
    },

    channelIconClass(channel) {
      const map = { takeaway: "order-row__icon--takeaway", dine_in: "order-row__icon--dine-in", pick_up: "order-row__icon--pick-up", drive_thru: "order-row__icon--drive-thru", pre_order: "order-row__icon--pre-order", catering: "order-row__icon--catering", pms: "order-row__icon--pms" };
      return map[channel] || "order-row__icon--takeaway";
    },
  },
};
</script>

<style scoped>
/* â”€â”€â”€ Transitions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-offcanvas-enter-active,
.orders-offcanvas-leave-active {
  transition: all 0.22s ease;
}

.orders-offcanvas-enter-from,
.orders-offcanvas-leave-to {
  opacity: 0;
}

.fade-fast-enter-active,
.fade-fast-leave-active {
  transition: opacity 0.18s ease;
}

.fade-fast-enter-from,
.fade-fast-leave-to {
  opacity: 0;
}

/* â”€â”€â”€ Backdrop & Panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-offcanvas-backdrop {
  position: fixed;
  inset: 0;
  z-index: 3200;
  background: rgba(15, 23, 42, 0.34);
  backdrop-filter: blur(3px);
  display: flex;
  justify-content: flex-end;
}

.orders-offcanvas {
  width: min(610px, 100%);
  height: 100%;
  background: #ffffff;
  box-shadow: -20px 0 40px rgba(15, 23, 42, 0.12);
  padding: 24px 24px 20px;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: #a3a3a3 transparent;
  display: flex;
  flex-direction: column;
}

.orders-offcanvas::-webkit-scrollbar {
  width: 7px;
}

.orders-offcanvas::-webkit-scrollbar-thumb {
  background: #8f8f8f;
  border-radius: 999px;
}

.orders-offcanvas::-webkit-scrollbar-track {
  background: transparent;
}

/* â”€â”€â”€ Header â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-offcanvas__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
  flex-shrink: 0;
}

.orders-offcanvas__title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.orders-offcanvas__title-icon {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  color: #1980e6;
  font-size: 20px;
}

.orders-offcanvas__header h2 {
  margin: 0;
  font-size: 17px;
  line-height: 1.2;
  font-weight: 800;
  color: #243447;
}

.orders-offcanvas__close {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border: none;
  border-radius: 999px;
  background: #f3f4f6;
  color: #4b5563;
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: all 0.16s ease;
}

.orders-offcanvas__close:hover {
  background: #e5e7eb;
  color: #111827;
}

/* â”€â”€â”€ Tabs â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-tabs {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  border-bottom: 1px solid #e8edf3;
  margin-bottom: 14px;
  flex-shrink: 0;
}

.orders-tab {
  height: 48px;
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  position: relative;
  transition: color 0.16s ease;
  white-space: nowrap;
  padding: 0 8px;
}

.orders-tab--active {
  color: #3b82f6;
}

.orders-tab--active::after {
  content: "";
  position: absolute;
  left: 8px;
  right: 8px;
  bottom: -1px;
  height: 2px;
  background: #3b82f6;
  border-radius: 999px;
}

/* â”€â”€â”€ Toolbar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-toolbar {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 10px;
  margin-bottom: 12px;
  flex-shrink: 0;
}

.orders-search {
  position: relative;
  min-width: 0;
}

.orders-search i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #7b8794;
  font-size: 17px;
  pointer-events: none;
}

/* â”€â”€â”€ Inputs â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-search input,
.orders-filter-control,
.modal-input {
  width: 100%;
  min-height: 42px;
  border: 1px solid #d8dee6;
  border-radius: 8px;
  background: #fff;
  color: #334155;
  font-size: 14px;
  outline: none;
  transition: all 0.16s ease;
  box-sizing: border-box;
}

.orders-search input {
  padding: 0 14px 0 40px;
}

.orders-filter-control {
  padding: 0 14px;
}

.modal-input {
  padding: 0 14px;
}

.modal-input--textarea {
  min-height: 118px;
  resize: vertical;
  padding: 14px;
}

.orders-search input:focus,
.orders-filter-control:focus,
.modal-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
}

/* â”€â”€â”€ Filter Button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-filter-btn {
  height: 42px;
  padding: 0 16px;
  border: none;
  border-radius: 8px;
  background: #fde7c9;
  color: #3b82f6;
  font-size: 14px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.16s ease;
  white-space: nowrap;
}

.orders-filter-btn:hover {
  background: #fbd9ad;
}

/* â”€â”€â”€ Filters â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-filters {
  margin-bottom: 14px;
  flex-shrink: 0;
}

.orders-filters__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

/* â”€â”€â”€ Content â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-content {
  flex: 1;
  min-height: 220px;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* â”€â”€â”€ Order Row â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.order-row {
  background: #ffffff;
  border: 1px solid #edf1f6;
  border-radius: 18px;
  padding: 16px 14px 14px;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.03);
}

.order-row__main {
  display: grid;
  grid-template-columns: 58px 1fr;
  gap: 12px;
  align-items: start;
}

.order-row__icon-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.order-row__icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-size: 24px;
  flex: 0 0 auto;
}

.order-row__channel {
  font-size: 11px;
  font-weight: 700;
  color: #4b5563;
  text-align: center;
  line-height: 1.2;
}

.order-row__icon--takeaway {
  background: #fff6df;
  color: #f2b632;
}

.order-row__icon--dine-in {
  background: #edf4ff;
  color: #4f83ff;
}

.order-row__icon--pick-up {
  background: #eafaf0;
  color: #31c26a;
}

.order-row__icon--drive-thru {
  background: #fef0f0;
  color: #ff6b6b;
}

.order-row__icon--pre-order {
  background: #f5efff;
  color: #a855f7;
}

.order-row__icon--catering {
  background: #e8fbfd;
  color: #06b6d4;
}

.order-row__icon--pms {
  background: #ecfdf5;
  color: #0f766e;
}

.order-row__content {
  min-width: 0;
}

.order-row__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}

.order-row__time {
  font-size: 12px;
  font-weight: 700;
  color: #a0a8b3;
}

.order-row__total {
  font-size: 14px;
  font-weight: 900;
  color: #4b5d73;
  white-space: nowrap;
}

.order-row__title {
  margin: 0 0 4px;
  font-size: 15px;
  line-height: 1.3;
  font-weight: 800;
  color: #334155;
  word-break: break-word;
}

.order-row__items {
  margin: 0;
  font-size: 12px;
  line-height: 1.5;
  color: #a0a8b3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.order-row__badges {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

/* â”€â”€â”€ Status Chips â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.status-chip {
  min-height: 28px;
  padding: 0 10px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
  gap: 6px;
}

.status-chip--pending {
  background: #fff5dc;
  color: #edae24;
}

.status-chip--confirmed {
  background: #dce8ff;
  color: #4b83ff;
}

.status-chip--preparing {
  background: #efe5ff;
  color: #9a62ff;
}

.status-chip--ready {
  background: #d7f4e7;
  color: #1fad66;
}

.status-chip--served {
  background: #d7f6f1;
  color: #17b9a4;
}

.status-chip--cancelled {
  background: #ffe4e0;
  color: #ff6b63;
}

.status-chip--held {
  background: #f1f5f9;
  color: #475569;
}

.status-chip--paid {
  background: #dcfce7;
  color: #22c55e;
}

.status-chip--partial {
  background: #fff5dc;
  color: #d97706;
}

.status-chip--unpaid {
  background: #ffe4e0;
  color: #ff6b63;
}

.status-chip--pms {
  border: 1px solid currentColor;
}

/* â”€â”€â”€ Sync Button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.status-sync-btn {
  position: relative;
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 8px;
  background: #e6f7ff;
  color: #0f8fcf;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.16s ease;
  flex-shrink: 0;
}

.status-sync-btn:hover {
  background: #d7f0ff;
}

.status-sync-btn:disabled {
  cursor: wait;
  opacity: 0.8;
}

.status-sync-btn--loading i {
  animation: order-sync-spin 0.8s linear infinite;
}

.status-sync-btn::before,
.status-sync-btn::after {
  position: absolute;
  left: 50%;
  opacity: 0;
  pointer-events: none;
  transform: translate(-50%, 6px);
  transition: opacity 0.16s ease, transform 0.16s ease;
  z-index: 20;
}

.status-sync-btn::before {
  content: attr(data-tooltip);
  bottom: calc(100% + 10px);
  min-width: 132px;
  padding: 8px 10px;
  border-radius: 8px;
  background: #0f172a;
  color: #ffffff;
  font-size: 12px;
  font-weight: 800;
  line-height: 1.2;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.2);
  white-space: nowrap;
}

.status-sync-btn::after {
  content: "";
  bottom: calc(100% + 4px);
  width: 10px;
  height: 10px;
  background: #0f172a;
  transform: translate(-50%, 6px) rotate(45deg);
}

.status-sync-btn:hover::before,
.status-sync-btn:hover::after {
  opacity: 1;
  transform: translate(-50%, 0);
}

.status-sync-btn:hover::after {
  transform: translate(-50%, 0) rotate(45deg);
}

@keyframes order-sync-spin {
  to {
    transform: rotate(360deg);
  }
}

/* â”€â”€â”€ Order Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.order-row__actions {
  margin-top: 14px;
  padding-top: 10px;
  border-top: 1px dashed #eceff4;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
}

.order-row__actions-left,
.order-row__actions-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* â”€â”€â”€ Buttons â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.icon-btn,
.action-btn,
.modal-btn {
  min-height: 38px;
  border: none;
  border-radius: 8px;
  padding: 0 12px;
  font-size: 13px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  transition: all 0.16s ease;
  white-space: nowrap;
}

.icon-btn {
  background: #eef0f3;
  color: #6b7280;
  min-width: 40px;
  padding: 0 10px;
}

.icon-btn:hover {
  background: #e3e7ec;
}

.icon-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.icon-btn--danger {
  background: #eef0f3;
  color: #6b7280;
}

.icon-btn--danger:hover {
  background: #ffe4e0;
  color: #ef4444;
}

.icon-btn--print {
  gap: 6px;
}

.icon-btn__label {
  font-size: 13px;
}

.action-btn--pay {
  background: #d8f5e5;
  color: #22b36f;
  min-width: 80px;
}

.action-btn--pay:hover {
  background: #c8efd9;
}

.action-btn--confirm {
  background: #dce8ff;
  color: #4b83ff;
  min-width: 110px;
}

.action-btn--confirm:hover {
  background: #cfdefe;
}

.action-btn--prepare {
  background: #efe5ff;
  color: #9a62ff;
  min-width: 110px;
}

.action-btn--prepare:hover {
  background: #e7d9ff;
}

.action-btn--ready {
  background: #d7f4e7;
  color: #1fad66;
  min-width: 110px;
}

.action-btn--ready:hover {
  background: #caefdf;
}

.action-btn--complete {
  background: #d7f4e7;
  color: #16a34a;
  min-width: 110px;
}

.action-btn--complete:hover {
  background: #caefdf;
}

.action-btn--pms {
  background: #ecfdf5;
  color: #0f766e;
  min-width: 110px;
}

.action-btn--pms:hover {
  background: #d1fae5;
}

.action-btn:disabled,
.modal-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

/* â”€â”€â”€ Empty / Skeleton â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.orders-empty {
  min-height: 360px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #64748b;
}

.orders-empty i {
  font-size: 40px;
  color: #cbd5e1;
  margin-bottom: 8px;
}

.orders-empty h3 {
  margin: 0 0 6px;
  font-size: 16px;
  font-weight: 800;
  color: #334155;
}

.orders-empty p {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
}

.orders-skeleton-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.order-skeleton-card {
  border: 1px solid #edf1f6;
  border-radius: 18px;
  padding: 16px;
  background: #fff;
}

.order-skeleton-card__top,
.order-skeleton-card__mid,
.order-skeleton-card__actions,
.details-skeleton__line {
  position: relative;
  overflow: hidden;
  background: #eef2f7;
  border-radius: 10px;
}

.order-skeleton-card__top::after,
.order-skeleton-card__mid::after,
.order-skeleton-card__actions::after,
.details-skeleton__line::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.75), transparent);
  transform: translateX(-100%);
  animation: skeletonMove 1.15s infinite;
}

.order-skeleton-card__top {
  height: 54px;
  margin-bottom: 10px;
}

.order-skeleton-card__mid {
  height: 46px;
  margin-bottom: 12px;
}

.order-skeleton-card__actions {
  height: 42px;
}

@keyframes skeletonMove {
  100% {
    transform: translateX(100%);
  }
}

/* â”€â”€â”€ Modal Backdrop â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 3300;
  background: rgba(15, 23, 42, 0.34);
  backdrop-filter: blur(3px);
  display: grid;
  place-items: center;
  padding: 16px;
  overflow-y: auto;
}

.modal-backdrop--full {
  align-items: flex-start;
  padding: 16px;
}

/* â”€â”€â”€ Cancel Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.cancel-modal {
  width: 100%;
  max-width: 560px;
  background: #ffffff;
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
  padding: 24px;
}

.cancel-modal h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #334155;
}

.cancel-modal p {
  margin: 8px 0 18px;
  font-size: 14px;
  line-height: 1.6;
  color: #6b7280;
}

.cancel-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.field-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-wrap label {
  font-size: 13px;
  font-weight: 700;
  color: #475569;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 16px;
}

.modal-btn--ghost {
  background: #f1f5f9;
  color: #64748b;
}

.modal-btn--ghost:hover {
  background: #e2e8f0;
}

.modal-btn--primary {
  background: #f6b26b;
  color: #ffffff;
}

.modal-btn--primary:hover {
  background: #f4a558;
}

/* â”€â”€â”€ Details Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.details-modal {
  width: 100%;
  max-width: 1400px;
  background: #ffffff;
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 32px);
}

.details-modal__header {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-shrink: 0;
}

.details-modal__header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #334155;
}

.details-modal__body {
  overflow-y: auto;
  flex: 1;
  scrollbar-width: thin;
  scrollbar-color: #a3a3a3 transparent;
}

.details-modal__body::-webkit-scrollbar {
  width: 6px;
}

.details-modal__body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}

.details-skeleton {
  padding: 22px;
}

.details-skeleton__line {
  height: 20px;
  margin-bottom: 12px;
}

.details-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 12px;
  padding: 12px;
}

.details-main,
.details-side {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.details-card {
  border: 1px solid #edf1f6;
  border-radius: 16px;
  padding: 16px;
  background: #ffffff;
}

.details-card h4 {
  margin: 0 0 14px;
  font-size: 15px;
  font-weight: 800;
  color: #334155;
}

/* â”€â”€â”€ Details Grid â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.details-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px 14px;
}

.details-grid--two {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.details-grid>div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.details-grid strong {
  font-size: 12px;
  color: #475569;
  font-weight: 800;
}

.details-grid span {
  font-size: 13px;
  color: #64748b;
  word-break: break-word;
}

/* â”€â”€â”€ Summary â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.summary-list {
  display: grid;
  gap: 10px;
}

.summary-list>div {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 8px;
  border-bottom: 1px dashed #e5e7eb;
  font-size: 14px;
  color: #475569;
}

.summary-list__total span,
.summary-list__total strong {
  color: #3b82f6;
  font-weight: 800;
}

.side-text {
  color: #64748b;
  text-align: center;
  padding: 16px 0;
  margin: 0;
  font-size: 14px;
}

/* â”€â”€â”€ Products Table (Desktop) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.products-table--desktop {
  display: block;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.products-table--desktop .products-table__head,
.products-table--desktop .products-table__row {
  display: grid;
  grid-template-columns: 2.2fr 0.8fr 0.8fr 0.6fr 0.8fr 0.8fr 0.8fr 0.8fr 0.8fr;
  gap: 10px;
  align-items: start;
  padding: 12px 0;
  border-bottom: 1px solid #e5e7eb;
  min-width: 680px;
}

.products-table--desktop .products-table__head {
  font-size: 12px;
  font-weight: 800;
  color: #475569;
}

.products-table--desktop .products-table__row {
  font-size: 13px;
  color: #64748b;
}

/* â”€â”€â”€ Products Cards (Mobile) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.products-table--mobile {
  display: none;
}

.products-cards {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.product-card {
  border: 1px solid #edf1f6;
  border-radius: 12px;
  padding: 14px;
  background: #f8fafc;
}

.product-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
}

.product-card__name {
  font-size: 14px;
  font-weight: 800;
  color: #334155;
  word-break: break-word;
}

.product-card__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-top: 12px;
}

.product-card__field {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.product-card__field span {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.product-card__field strong {
  font-size: 13px;
  color: #334155;
  font-weight: 700;
}

.product-col {
  display: grid;
  gap: 4px;
}

.product-option {
  font-size: 12px;
  color: #64748b;
  padding-left: 14px;
}

/* â”€â”€â”€ Print Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.print-docs-modal {
  width: 100%;
  max-width: 560px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
  padding: 24px;
}

.print-docs-modal__header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #334155;
}

.print-docs-modal__desc {
  margin: 12px 0 18px;
  font-size: 14px;
  line-height: 1.7;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 16px;
}

.print-docs-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.print-docs-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid #edf2f7;
}

.print-docs-row__text h4 {
  margin: 0 0 3px;
  font-size: 15px;
  font-weight: 800;
  color: #334155;
}

.print-docs-row__text span {
  font-size: 13px;
  color: #94a3b8;
}

.print-docs-row__actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.print-docs-btn {
  width: 42px;
  height: 42px;
  border: none;
  border-radius: 999px;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: all 0.16s ease;
  font-size: 18px;
}

.print-docs-btn--preview {
  background: #dce6ef;
  color: #1f5c97;
}

.print-docs-btn--preview:hover {
  background: #cddae8;
}

.print-docs-btn--print {
  background: #fde7c9;
  color: #3b82f6;
}

.print-docs-btn--print:hover {
  background: #fbd9ad;
}

.print-docs-btn--download {
  background: #dcfce7;
  color: #16a34a;
}

.print-docs-btn--download:hover {
  background: #c7f7d8;
}

.print-docs-modal__footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

/* â”€â”€â”€ Toast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
:deep(.orders-toast-message) {
  display: grid;
  gap: 4px;
  text-align: left;
}

:deep(.orders-toast-message strong) {
  font-size: 15px;
  font-weight: 900;
  color: #0f172a;
}

:deep(.orders-toast-message span) {
  font-size: 13px;
  font-weight: 700;
  color: #475569;
  line-height: 1.35;
}

:deep(.stock-shortage-toast) {
  display: grid;
  gap: 6px;
  min-width: 230px;
  text-align: left;
}

:deep(.stock-shortage-toast > strong) {
  font-size: 15px;
  font-weight: 900;
  color: #0f172a;
}

:deep(.stock-shortage-toast div) {
  display: grid;
  grid-template-columns: 82px 18px minmax(0, 1fr);
  gap: 4px;
  align-items: baseline;
}

:deep(.stock-shortage-toast span) {
  font-size: 13px;
  font-weight: 800;
  color: #64748b;
}

:deep(.stock-shortage-toast em) {
  font-style: normal;
  font-size: 13px;
  font-weight: 900;
  color: #94a3b8;
}

:deep(.stock-shortage-toast b) {
  font-size: 13px;
  font-weight: 900;
  color: #334155;
  word-break: break-word;
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   RESPONSIVE BREAKPOINTS
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

/* â”€â”€â”€ Tablet: â‰¤ 1100px (Details modal layout) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media (max-width: 1100px) {
  .details-layout {
    grid-template-columns: 1fr;
  }

  .details-side {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .details-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

/* â”€â”€â”€ Tablet: â‰¤ 900px â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media (max-width: 900px) {
  .orders-offcanvas {
    width: 100%;
    padding: 20px 18px 16px;
  }

  .details-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .details-side {
    grid-template-columns: 1fr;
  }
}

/* â”€â”€â”€ Mobile: â‰¤ 640px â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media (max-width: 640px) {
  .orders-offcanvas {
    padding: 16px 14px 14px;
  }

  .orders-offcanvas__header h2 {
    font-size: 15px;
  }

  /* Tabs scale down */
  .orders-tab {
    font-size: 13px;
    height: 42px;
  }

  /* Toolbar stacks */
  .orders-toolbar {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .orders-filter-btn {
    width: 100%;
  }

  /* Filters stack */
  .orders-filters__grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  /* Order row: tighter icon */
  .order-row__main {
    grid-template-columns: 48px 1fr;
    gap: 10px;
  }

  .order-row__icon {
    width: 44px;
    height: 44px;
    font-size: 20px;
    border-radius: 11px;
  }

  .order-row__channel {
    font-size: 10px;
  }

  .order-row__title {
    font-size: 14px;
  }

  /* Actions: stack vertically on very small screens */
  .order-row__actions {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .order-row__actions-left {
    flex-wrap: nowrap;
  }

  .order-row__actions-right {
    width: 100%;
  }

  .order-row__actions-right .action-btn {
    flex: 1;
    justify-content: center;
  }

  /* Hide print label on small screens */
  .icon-btn__label {
    display: none;
  }

  /* Cancel modal */
  .cancel-modal {
    padding: 18px 16px;
  }

  .cancel-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .modal-actions {
    flex-direction: column-reverse;
    gap: 8px;
  }

  .modal-btn {
    width: 100%;
    justify-content: center;
    min-height: 44px;
  }

  /* Details modal */
  .details-modal {
    max-height: calc(100vh - 24px);
    border-radius: 14px;
  }

  .details-modal__header {
    padding: 14px 16px;
  }

  .details-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px 10px;
  }

  /* Switch to card view for products */
  .products-table--desktop {
    display: none;
  }

  .products-table--mobile {
    display: block;
  }

  /* Product cards */
  .product-card__grid {
    grid-template-columns: repeat(2, 1fr);
  }

  /* Print modal */
  .print-docs-modal {
    padding: 18px 16px;
  }

  .print-docs-btn {
    width: 38px;
    height: 38px;
    font-size: 16px;
  }
}

/* â”€â”€â”€ Very small mobile: â‰¤ 380px â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media (max-width: 380px) {
  .orders-offcanvas {
    padding: 14px 12px 12px;
  }

  .orders-tab {
    font-size: 12px;
    padding: 0 4px;
  }

  .order-row {
    padding: 12px 10px 10px;
  }

  .order-row__main {
    grid-template-columns: 42px 1fr;
    gap: 8px;
  }

  .order-row__icon {
    width: 40px;
    height: 40px;
    font-size: 18px;
    border-radius: 10px;
  }

  .status-chip {
    font-size: 11px;
    min-height: 24px;
    padding: 0 8px;
  }

  .icon-btn {
    min-width: 36px;
    padding: 0 8px;
  }

  .action-btn {
    font-size: 12px;
    padding: 0 10px;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .product-card__grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }
}

/* â”€â”€â”€ Print: hide backdrop â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media print {
  .orders-offcanvas-backdrop {
    display: none !important;
  }
}

.products-table--mobile,
.products-table--mobile .products-cards {
  display: none !important;
}

.products-table--desktop {
  display: none !important;
}

.products-table--mobile,
.products-table--mobile .products-cards {
  display: flex !important;
}
</style>
