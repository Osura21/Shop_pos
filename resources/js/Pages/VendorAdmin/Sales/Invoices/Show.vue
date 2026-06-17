<template>
  <Head :title="`Receipt - ${invoice.invoice_no}`" />

  <div class="page-shell">
    <div class="page-header">
      <div>
        <h1>Receipt</h1>
        <p>{{ invoice.invoice_no }}</p>
      </div>

      <div class="header-actions">
        <button type="button" class="secondary-btn" @click="goBack">
          <i class="bi bi-arrow-left"></i>
          Back
        </button>

        <button type="button" class="primary-btn" @click="printInvoice">
          <i class="bi bi-printer"></i>
          Print
        </button>

        <button type="button" class="secondary-btn" @click="downloadInvoice">
          <i class="bi bi-download"></i>
          Download
        </button>
      </div>
    </div>

    <div class="invoice-card">
      <div class="invoice-top">
        <div>
          <h2>{{ invoice.seller_name || branchName }}</h2>
          <p>{{ invoice.branch?.email || 'info@company.com' }}</p>
          <p>{{ invoice.branch?.phone || '' }}</p>
        </div>

        <div class="invoice-title">RECEIPT</div>
      </div>

      <div class="divider"></div>

      <div class="info-grid info-grid--four">
        <div class="info-item">
          <label>Invoice Number</label>
          <strong>{{ invoice.invoice_no }}</strong>
        </div>

        <div class="info-item">
          <label>Receipt Date</label>
          <strong>{{ formatDate(invoice.issued_at) }}</strong>
        </div>

        <div class="info-item">
          <label>Status</label>
          <strong>
            <span class="badge" :class="statusClass(invoice.status)">
              {{ pretty(invoice.status) }}
            </span>
          </strong>
        </div>

        <div class="info-item">
          <label>Customer</label>
          <strong>{{ buyerName }}</strong>
        </div>

        <div class="info-item">
          <label>Currency</label>
          <strong>{{ currency }}</strong>
        </div>

        <div class="info-item">
          <label>Branch</label>
          <strong>{{ branchName }}</strong>
        </div>

        <div class="info-item">
          <label>Shop</label>
          <strong>
            {{ invoice.seller_name || branchName }}
          </strong>
        </div>

        <div class="info-item">
          <label>UUID</label>
          <strong class="text-break">{{ invoice.uuid || '-' }}</strong>
        </div>
      </div>

      <div class="parties-grid">
        <div class="party-box">
          <div class="party-label">Shop</div>
          <div class="party-name">{{ invoice.seller_name || branchName }}</div>
          <div class="party-line">{{ branchName }}</div>
          <div class="party-line">{{ invoice.branch?.email || '-' }}</div>
          <div class="party-line">{{ invoice.branch?.phone || '-' }}</div>
        </div>

        <div class="party-box">
          <div class="party-label">Customer</div>
          <div class="party-name">{{ buyerName }}</div>
          <div class="party-line">{{ invoice.customer?.email || '-' }}</div>
          <div class="party-line">{{ invoice.customer?.phone || '-' }}</div>
        </div>

        <div class="qr-box">
          POS
        </div>
      </div>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Description</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th>Sub Total</th>
              <th>Total Tax</th>
              <th>Total</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(item, index) in invoice.items" :key="item.id || index">
              <td>{{ index + 1 }}</td>

              <td>
                <strong>{{ item.product_name || item.name || item.description || '-' }}</strong>

               <div
  v-for="(option, optionIndex) in normalizedOptions(item.options)"
  :key="optionIndex"
  class="option-line"
>
  <strong v-if="option.label">{{ option.label }}:</strong>
  {{ option.value }}
</div>
              </td>

              <td>{{ currency }} {{ money(item.unit_price) }}</td>
              <td>{{ trimQty(item.qty || item.quantity) }}</td>
              <td>{{ currency }} {{ money(item.subtotal || item.line_subtotal) }}</td>
              <td>{{ currency }} {{ money(item.tax_total) }}</td>
              <td>{{ currency }} {{ money(item.line_total || item.total) }}</td>
            </tr>

            <tr v-if="!invoice.items || !invoice.items.length">
              <td colspan="7" class="empty-cell">No invoice items available.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bottom-grid">
        <div class="mini-card">
          <h3>Payment Summary</h3>

          <div class="summary-row">
            <span>Paid Amount</span>
            <strong>{{ currency }} {{ money(invoice.paid_amount) }}</strong>
          </div>

          <div class="summary-row">
            <span>Refunded Amount</span>
            <strong>{{ currency }} {{ money(invoice.refunded_amount) }}</strong>
          </div>

          <div class="summary-row">
            <span>Net Paid</span>
            <strong class="success-text">{{ currency }} {{ money(invoice.net_paid) }}</strong>
          </div>
        </div>

        <div class="mini-card">
          <h3>Total Summary</h3>

          <div class="summary-list">
            <div class="summary-row">
              <span>Sub Total</span>
              <strong>{{ currency }} {{ money(invoice.subtotal) }}</strong>
            </div>

            <div class="summary-row">
              <span>Tax Total</span>
              <strong>{{ currency }} {{ money(invoice.tax_total) }}</strong>
            </div>

            <div
              v-if="Number(invoice.discount_total || 0) > 0"
              class="summary-row"
            >
              <span>Discount</span>
              <strong>- {{ currency }} {{ money(invoice.discount_total) }}</strong>
            </div>

            <div class="summary-row summary-row--total">
              <span>Total</span>
              <strong>{{ currency }} {{ money(invoice.total) }}</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="footer-note">
        <strong>Receipt UUID:</strong>
        <span>{{ invoice.uuid || '-' }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { currencySymbol } from '@/Utils/currency'

export default {
  name: 'SalesInvoicesShow',

  layout: VendorAdminLayout,

  components: {
    Head,
  },

  props: {
    invoice: {
      type: Object,
      required: true,
    },
  },

  computed: {
    currency() {
      return currencySymbol(this.invoice.currency_code || 'LKR')
    },

    branchName() {
      return this.invoice.branch?.name || '-'
    },

    customerName() {
  return this.invoice.customer?.name || this.invoice.buyer_name || '-'
},
buyerName() {
  return this.invoice.customer?.name
    || this.invoice.buyer_name
    || 'Walk-In Customer'
},
  },

  methods: {
    currencySymbol,
    normalizedOptions(options) {
  if (!options) return []

  let list = options

  if (typeof list === 'string') {
    try {
      list = JSON.parse(list)
    } catch (e) {
      list = []
    }
  }

  if (!Array.isArray(list)) return []

  return list
    .map((option) => {
      const label = String(
        option?.label
        || option?.option_name
        || option?.name
        || option?.title
        || option?.group_name
        || option?.group
        || ''
      ).trim()

      const value = String(
        option?.value
        || option?.value_label
        || option?.value_input
        || option?.option_value
        || option?.selected_value
        || option?.text
        || option?.display_value
        || ''
      ).trim()

      return { label, value }
    })
    .filter((option) => option.label || option.value)
},
    goBack() {
      router.visit(route('vendor.sales.invoices.index'))
    },

    printInvoice() {
      window.open(route('vendor.sales.invoices.print', this.invoice.id), '_blank')
    },

    downloadInvoice() {
      window.open(route('vendor.sales.invoices.download', this.invoice.id), '_blank')
    },

    pretty(value) {
      return String(value || '-')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase())
    },

    money(value) {
      return Number(value || 0).toFixed(3)
    },

    trimQty(value) {
      const numeric = Number(value || 0)
      return Number.isInteger(numeric) ? numeric : numeric.toFixed(3)
    },

    formatDate(value) {
      return value ? new Date(value).toLocaleString() : '-'
    },

    statusClass(value) {
      const map = {
        paid: 'badge--success',
        completed: 'badge--success',
        active: 'badge--success',
        issued: 'badge--success',
        draft: 'badge--warning',
        pending: 'badge--warning',
        cancelled: 'badge--danger',
        void: 'badge--danger',
        refunded: 'badge--danger',
      }

      return map[value] || 'badge--success'
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

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.primary-btn,
.secondary-btn {
  min-height: 40px;
  border: none;
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
}

.primary-btn {
  background: #3b82f6;
  color: #fff;
}

.secondary-btn {
  background: #fff;
  border: 1px solid #d8dee6;
  color: #475467;
}

.invoice-card {
  max-width: 1140px;
  margin: 0 auto;
  background: #fff;
  border: 1px solid #eceff4;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 10px 18px rgba(15, 23, 42, 0.03);
}

.invoice-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  flex-wrap: wrap;
}

.invoice-top h2 {
  margin: 0 0 6px;
  font-size: 28px;
  font-weight: 800;
  color: #667085;
}

.invoice-top p {
  margin: 0;
  color: #98a2b3;
  font-size: 14px;
}

.invoice-title {
  font-size: 40px;
  font-weight: 900;
  color: #667085;
}

.divider {
  height: 1px;
  background: #eceff4;
  margin: 18px 0;
}

.info-grid {
  display: grid;
  gap: 18px 16px;
}

.info-grid--four {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.info-item label {
  font-size: 13px;
  font-weight: 700;
  color: #667085;
}

.info-item strong,
.info-item span {
  font-size: 14px;
  color: #475467;
}

.text-break {
  word-break: break-word;
}

.parties-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 220px;
  gap: 24px;
  margin-top: 22px;
  align-items: stretch;
}

.party-box {
  background: #f8fafc;
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 18px;
}

.party-label {
  font-size: 14px;
  font-weight: 700;
  color: #98a2b3;
  margin-bottom: 8px;
  text-transform: uppercase;
}

.party-name {
  font-size: 16px;
  font-weight: 800;
  color: #667085;
}

.party-line {
  margin-top: 8px;
  color: #98a2b3;
  line-height: 1.4;
  font-size: 14px;
}

.qr-box {
  border: 1px dashed #d8dee6;
  border-radius: 12px;
  min-height: 160px;
  display: grid;
  place-items: center;
  color: #98a2b3;
  font-weight: 800;
}

.table-wrap {
  margin-top: 22px;
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
  background: #fff;
  white-space: nowrap;
}

.option-line {
  font-size: 13px;
  color: #98a2b3;
  margin-top: 4px;
}

.empty-cell {
  text-align: center !important;
  color: #98a2b3 !important;
  padding: 24px 16px !important;
}

.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-top: 22px;
}

.mini-card {
  background: #fff;
  border: 1px solid #eceff4;
  border-radius: 16px;
  padding: 18px;
}

.mini-card h3 {
  margin: 0 0 14px;
  font-size: 16px;
  font-weight: 700;
  color: #344054;
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
  text-align: right;
  color: #344054;
}

.summary-row--total {
  color: #3b82f6;
  font-weight: 800;
}

.summary-row--total strong {
  color: #3b82f6;
}

.success-text {
  color: #22c55e !important;
}

.footer-note {
  font-size: 14px;
  color: #667085;
  word-break: break-word;
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

.badge--orange {
  background: #fde7c9;
  color: #3b82f6;
}

@media (max-width: 1200px) {
  .info-grid--four {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .parties-grid {
    grid-template-columns: 1fr 1fr;
  }

  .qr-box {
    grid-column: 1 / -1;
  }
}

@media (max-width: 760px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    align-items: stretch;
  }

  .primary-btn,
  .secondary-btn {
    width: 100%;
  }

  .invoice-card {
    padding: 16px;
  }

  .invoice-title {
    font-size: 30px;
  }

  .info-grid--four,
  .parties-grid,
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}
</style>
