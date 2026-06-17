<template>
  <Head :title="`Order Details - ${referenceNo}`" />

  <div class="page-shell">
    <div class="page-header">
      <div>
        <h1>Order Details</h1>
        <p>{{ referenceNo }}</p>
      </div>

      <button type="button" class="secondary-btn" @click="goBack">
        <i class="bi bi-arrow-left"></i>
        Back to Orders
      </button>
    </div>

    <div class="show-hero">
      <article class="hero-stat">
        <span>Customer</span>
        <strong>{{ customerName }}</strong>
        <small>Order #{{ order.id }}</small>
      </article>

      <article class="hero-stat">
        <span>Payment</span>
        <strong>{{ pretty(order.payment_status || 'unpaid') }}</strong>
        <small>{{ payments.length }} payment record(s)</small>
      </article>

      <article class="hero-stat">
        <span>Total</span>
        <strong>{{ currency }} {{ money(order.grand_total) }}</strong>
        <small>{{ order.register?.name || 'No register' }}</small>
      </article>

      <article class="hero-stat">
        <span>Created</span>
        <strong>{{ formatDate(order.created_at) }}</strong>
        <small>{{ order.created_by_name || 'System' }}</small>
      </article>
    </div>

    <div class="show-layout">
      <div class="show-main">
        <div class="show-card">
          <h3>Order Information</h3>

          <div class="info-grid">
            <div class="info-item">
              <label>Reference No</label>
              <strong>{{ referenceNo }}</strong>
            </div>

            <div class="info-item">
              <label>Order Number</label>
              <strong>{{ order.id }}</strong>
            </div>

            <div class="info-item">
              <label>Branch</label>
              <span>{{ order.register?.branch?.name || '-' }}</span>
            </div>

            <div class="info-item">
              <label>Payment Status</label>
              <span>
                <span class="badge" :class="paymentClass(order.payment_status)">
                  {{ pretty(order.payment_status || 'unpaid') }}
                </span>
              </span>
            </div>

            <div class="info-item">
              <label>Created By</label>
              <span>{{ order.created_by_name || 'System' }}</span>
            </div>

            <div class="info-item">
              <label>Cashier</label>
              <span>{{ order.cashier_name || '-' }}</span>
            </div>

            <div class="info-item">
              <label>POS Register</label>
              <span>{{ order.register?.name || '-' }}</span>
            </div>

            <div class="info-item">
              <label>POS Session ID</label>
              <span>{{ order.pos_session_id || '-' }}</span>
            </div>

            <div class="info-item">
              <label>Created At</label>
              <span>{{ formatDate(order.created_at) }}</span>
            </div>

            <div class="info-item">
              <label>Updated At</label>
              <span>{{ formatDate(order.updated_at) }}</span>
            </div>
          </div>
        </div>

        <div class="show-card">
          <h3>Customer Information</h3>

          <div class="info-grid info-grid--two">
            <div class="info-item">
              <label>Name</label>
              <span>{{ customerName }}</span>
            </div>

            <div class="info-item">
              <label>Phone</label>
              <span>{{ order.customer?.phone || order.customer_phone || '-' }}</span>
            </div>

            <div class="info-item">
              <label>Email</label>
              <span>{{ order.customer?.email || order.customer_email || '-' }}</span>
            </div>

            <div class="info-item">
              <label>Customer ID</label>
              <span>{{ order.customer?.id || order.customer_id || '-' }}</span>
            </div>
          </div>
        </div>

        <div class="show-card">
          <div class="section-heading">
            <h3>Products</h3>
            <span>{{ order.items?.length || 0 }} item(s)</span>
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Unit Price</th>
                  <th>Quantity</th>
                  <th>Subtotal</th>
                  <th>Tax</th>
                  <th>Total</th>
                  <th>Cost Price</th>
                  <th>Revenue</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="item in order.items" :key="item.id">
                  <td>
                    <strong>{{ item.product_name || item.name || '-' }}</strong>

                    <div
                      v-for="option in item.options"
                      :key="option.id"
                      class="option-line"
                    >
                      {{ option.option_name }} :
                      {{ option.value_label || option.value_input || '-' }}
                    </div>
                  </td>

                  <td>{{ currency }} {{ money(item.unit_price) }}</td>
                  <td>{{ trimQty(item.qty) }}</td>
                  <td>{{ currency }} {{ money(item.line_subtotal) }}</td>
                  <td>{{ currency }} {{ money(item.tax_total) }}</td>
                  <td>{{ currency }} {{ money(item.line_total) }}</td>
                  <td>{{ currency }} {{ money(item.cost_price || 0) }}</td>
                  <td>{{ currency }} {{ money(item.revenue || item.line_total) }}</td>
                </tr>

                <tr v-if="!order.items || !order.items.length">
                  <td colspan="8" class="empty-cell">No products available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="show-card">
          <h3>Status Logs</h3>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Changed By</th>
                  <th>Reason</th>
                  <th>Note</th>
                  <th>Changed At</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(log, index) in statusLogs" :key="index">
                  <td>
                    <span class="badge badge--info">
                      {{ log.status }}
                    </span>
                  </td>
                  <td>{{ log.changed_by }}</td>
                  <td>{{ log.reason }}</td>
                  <td>{{ log.note }}</td>
                  <td>{{ log.changed_at }}</td>
                </tr>

                <tr v-if="!statusLogs.length">
                  <td colspan="5" class="empty-cell">No status logs available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="show-side">
        <div class="show-card">
          <h3>Order Summary</h3>

          <div class="summary-list">
            <div class="summary-row">
              <span>Currency</span>
              <strong>{{ currency }}</strong>
            </div>

            <div class="summary-row">
              <span>Currency Rate</span>
              <strong>{{ money(order.currency_rate || 1, 4) }}</strong>
            </div>

            <div class="summary-row">
              <span>Cost Price</span>
              <strong>{{ currency }} {{ money(order.cost_total || 0) }}</strong>
            </div>

            <div class="summary-row">
              <span>Revenue</span>
              <strong>{{ currency }} {{ money(order.revenue_total || order.grand_total) }}</strong>
            </div>

            <div class="summary-row">
              <span>Subtotal</span>
              <strong>{{ currency }} {{ money(order.subtotal) }}</strong>
            </div>

            <div class="summary-row">
              <span>Tax</span>
              <strong>{{ currency }} {{ money(order.tax_total) }}</strong>
            </div>

            <div class="summary-row" v-if="Number(order.discount_total || 0) > 0">
              <span>Discount</span>
              <strong>- {{ currency }} {{ money(order.discount_total) }}</strong>
            </div>

            <div class="summary-row summary-row--total">
              <span>Total</span>
              <strong>{{ currency }} {{ money(order.grand_total) }}</strong>
            </div>
          </div>
        </div>

        <div class="show-card">
          <h3>Notes</h3>
          <div class="info-item">
            <span>{{ order.notes || order.note || 'No notes available' }}</span>
          </div>
        </div>

        <div class="show-card">
          <h3>Payments</h3>

          <div v-if="payments.length" class="payments-list">
            <div
              v-for="(payment, index) in payments"
              :key="index"
              class="payment-row"
            >
              <div>
                <strong>{{ payment.method }}</strong>
                <span>{{ payment.paid_at || '-' }}</span>
              </div>

              <div class="payment-amount">
                <span class="badge" :class="paymentClass(payment.status?.toLowerCase())">
                  {{ payment.status }}
                </span>
                <strong>{{ currencySymbol(payment.currency || order.currency_code || 'LKR') }} {{ money(payment.amount) }}</strong>
              </div>
            </div>
          </div>

          <div v-else class="info-item">
            <span>No payment records available.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { currencySymbol } from '@/Utils/currency'

export default {
  name: 'SalesOrdersShow',

  layout: VendorAdminLayout,

  components: {
    Head,
  },

  props: {
    order: {
      type: Object,
      required: true,
    },
    statusLogs: {
      type: Array,
      default: () => [],
    },
    payments: {
      type: Array,
      default: () => [],
    },
  },

  computed: {
    currency() {
      return currencySymbol(this.order.currency_code || 'LKR')
    },

    referenceNo() {
      const uuid = this.order.uuid || ''

      if (uuid) {
        return `ORD-${uuid.slice(0, 10).toUpperCase()}`
      }

      return `ORD-${this.order.id}`
    },

    customerName() {
      return this.order.customer_name
        || this.order.customer?.name
        || 'Walk-In Customer'
    },
  },

  methods: {
    currencySymbol,
    goBack() {
      router.visit(route('vendor.sales.orders.index'))
    },

    pretty(value) {
      return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase())
    },

    money(value, decimals = 3) {
      return Number(value || 0).toFixed(decimals)
    },

    trimQty(value) {
      const numeric = Number(value || 0)
      return Number.isInteger(numeric) ? numeric : numeric.toFixed(3)
    },

    statusClass(value) {
      const map = {
        pending: 'badge--warning',
        preparing: 'badge--purple',
        ready: 'badge--success',
        served: 'badge--teal',
        completed: 'badge--success',
        cancelled: 'badge--danger',
      }

      return map[value] || 'badge--info'
    },

    paymentClass(value) {
      const map = {
        paid: 'badge--success',
        unpaid: 'badge--danger',
        partial: 'badge--warning',
      }

      return map[value] || 'badge--danger'
    },

    formatDate(value) {
      if (!value) return '-'
      return new Date(value).toLocaleString()
    },
  },
}
</script>

<style scoped>
.page-shell {
  min-height: calc(100vh - 110px);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.show-hero {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 16px;
}

.hero-stat {
  background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
  border: 1px solid #f7ddba;
  border-radius: 16px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
}

.hero-stat span {
  color: #9a6b34;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.hero-stat strong {
  color: #1f2937;
  font-size: 19px;
  line-height: 1.25;
}

.hero-stat small {
  color: #6b7280;
  font-size: 12px;
}

.page-header h1 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #344054;
}

.page-header p {
  margin: 6px 0 0;
  font-size: 13px;
  color: #98a2b3;
}

.secondary-btn {
  min-height: 40px;
  border-radius: 8px;
  padding: 0 16px;
  font-weight: 700;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  cursor: pointer;
  background: #fff;
  border: 1px solid #d8dee6;
  color: #475467;
}

.secondary-btn:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

.show-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  gap: 16px;
}

.show-main,
.show-side {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.show-card {
  background: #fff;
  border: 1px solid #eceff4;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 10px 18px rgba(15, 23, 42, 0.03);
}

.show-card h3 {
  margin: 0 0 16px;
  font-size: 16px;
  font-weight: 700;
  color: #344054;
}

.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.section-heading h3 {
  margin: 0;
}

.section-heading span {
  font-size: 13px;
  font-weight: 700;
  color: #98a2b3;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 18px 16px;
}

.info-grid--two {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.info-item label {
  font-size: 12px;
  font-weight: 700;
  color: #667085;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.info-item strong,
.info-item span {
  font-size: 14px;
  color: #475467;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #edf1f6;
  font-size: 14px;
  color: #667085;
  text-align: left;
  vertical-align: top;
}

.data-table th {
  font-weight: 800;
  color: #344054;
  background: #fffaf3;
  white-space: nowrap;
}

.option-line {
  font-size: 13px;
  color: #667085;
  margin-top: 4px;
}

.empty-cell {
  text-align: center !important;
  color: #98a2b3 !important;
  padding: 24px 16px !important;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.badge--warning {
  background: #fff5dc;
  color: #edae24;
}

.badge--success {
  background: #dcfce7;
  color: #22c55e;
}

.badge--danger {
  background: #ffe4e0;
  color: #ff6b63;
}

.badge--info {
  background: #dce8ff;
  color: #4b83ff;
}

.badge--purple {
  background: #efe5ff;
  color: #9a62ff;
}

.badge--teal {
  background: #d7f6f1;
  color: #14b8a6;
}

.badge--orange {
  background: #fde7c9;
  color: #3b82f6;
}

.summary-list {
  display: grid;
  gap: 12px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 10px;
  border-bottom: 1px dashed #e5e7eb;
  font-size: 14px;
  color: #475467;
}

.summary-row strong {
  color: #344054;
  text-align: right;
}

.summary-row--total {
  color: #3b82f6;
  font-weight: 800;
}

.summary-row--total strong {
  color: #3b82f6;
}

.payments-list {
  display: grid;
  gap: 12px;
}

.payment-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px dashed #e5e7eb;
}

.payment-row:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.payment-row div {
  display: grid;
  gap: 4px;
}

.payment-row strong {
  font-size: 14px;
  color: #344054;
}

.payment-row span {
  font-size: 12px;
  color: #98a2b3;
}

.payment-amount {
  text-align: right;
  justify-items: end;
}

@media (max-width: 1200px) {
  .show-hero {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .show-layout {
    grid-template-columns: 1fr;
  }

  .info-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .show-hero {
    grid-template-columns: 1fr;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .info-grid,
  .info-grid--two {
    grid-template-columns: 1fr;
  }

  .show-card {
    padding: 16px;
  }
}
</style>
