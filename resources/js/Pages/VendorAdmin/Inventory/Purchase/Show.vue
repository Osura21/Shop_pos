<template>
  <Head :title="`Purchase ${purchase.reference_no}`" />

  <div class="page-wrap">
    <div class="head-row">
      <div>
        <h3 class="page-title">
          <i class="bi bi-bag me-2 text-warning"></i>Show Purchase
        </h3>
        <p class="page-subtitle mb-0">Detailed purchase summary, totals, and receipts.</p>
      </div>

      <div class="d-flex gap-2">
        <Link :href="route('vendor.purchases.index')" class="btn btn-cancel">Back</Link>
        <button
          v-if="purchase.status !== 'received'"
          class="btn btn-create"
          @click="openReceiveModal"
        >
          Mark as Received
        </button>
      </div>
    </div>

    <div class="show-layout">
      <section>
        <div class="info-card">
          <h5 class="section-title">Purchase Information</h5>

          <div class="info-grid">
            <div><strong>Reference No</strong><span>{{ purchase.reference_no }}</span></div>
            <div><strong>Branch</strong><span>{{ purchase.branch?.name || '-' }}</span></div>
            <div><strong>Supplier</strong><span>{{ purchase.supplier?.name || '-' }}</span></div>
            <div><strong>Status</strong><span class="status-pill">{{ statusLabel }}</span></div>
            <div><strong>Expected At</strong><span>{{ purchase.expected_at || '-' }}</span></div>
            <div><strong>Updated At</strong><span>{{ purchase.updated_at || '-' }}</span></div>
            <div><strong>Created At</strong><span>{{ purchase.created_at || '-' }}</span></div>
            <div class="wide"><strong>Notes</strong><span>{{ purchase.notes || '-' }}</span></div>
          </div>

          <div class="summary-panels">
            <div class="summary-card">
              <h6>Base Summary ({{ primaryCurrencyCode }})</h6>

              <div class="summary-row">
                <span>Sub Total</span>
                <strong>{{ primaryCurrencyCode }} {{ formatAmount(purchase.subtotal) }}</strong>
              </div>

              <div class="summary-row">
                <span>Tax</span>
                <strong>{{ primaryCurrencyCode }} {{ formatAmount(purchase.tax) }}</strong>
              </div>

              <div class="summary-row">
                <span>Discount</span>
                <strong>{{ primaryCurrencyCode }} {{ formatAmount(purchase.discount) }}</strong>
              </div>

              <div class="summary-row summary-row--total">
                <span>Total</span>
                <strong>{{ primaryCurrencyCode }} {{ formatAmount(purchase.total) }}</strong>
              </div>
            </div>

            <div v-if="hasSecondaryCurrency" class="summary-card summary-card--secondary">
              <h6>Secondary Summary ({{ secondaryCurrencyCode }})</h6>

              <div class="summary-row">
                <span>Sub Total</span>
                <strong>{{ secondaryCurrencyCode }} {{ formatAmount(purchase.secondary_subtotal) }}</strong>
              </div>

              <div class="summary-row">
                <span>Tax</span>
                <strong>{{ secondaryCurrencyCode }} {{ formatAmount(purchase.secondary_tax) }}</strong>
              </div>

              <div class="summary-row">
                <span>Discount</span>
                <strong>{{ secondaryCurrencyCode }} {{ formatAmount(purchase.secondary_discount) }}</strong>
              </div>

              <div class="summary-row summary-row--total">
                <span>Total</span>
                <strong>{{ secondaryCurrencyCode }} {{ formatAmount(purchase.secondary_total) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="items-card mt-4">
          <h5 class="section-title">Purchase Items</h5>

          <div class="table-responsive mt-3">
            <table class="table align-middle purchase-items-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Ingredient</th>
                  <th>Quantity</th>
                  <th>Received Quantity</th>
                  <th>Unit Cost ({{ primaryCurrencyCode }})</th>
                  <th v-if="hasSecondaryCurrency">Unit Cost ({{ secondaryCurrencyCode }})</th>
                  <th>Line Total ({{ primaryCurrencyCode }})</th>
                  <th v-if="hasSecondaryCurrency">Line Total ({{ secondaryCurrencyCode }})</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(item, index) in purchase.items" :key="item.id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ item.ingredient?.name || '-' }}</td>
                  <td>{{ formatAmount(item.quantity) }} {{ item.ingredient?.unit?.symbol || '' }}</td>
                  <td>{{ formatAmount(item.received_quantity) }} {{ item.ingredient?.unit?.symbol || '' }}</td>
                  <td>{{ primaryCurrencyCode }} {{ formatAmount(item.unit_cost) }}</td>
                  <td v-if="hasSecondaryCurrency">{{ secondaryCurrencyCode }} {{ formatAmount(item.secondary_unit_cost) }}</td>
                  <td>{{ primaryCurrencyCode }} {{ formatAmount(item.line_total) }}</td>
                  <td v-if="hasSecondaryCurrency">{{ secondaryCurrencyCode }} {{ formatAmount(item.secondary_line_total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <aside class="receipt-column">
        <div v-for="receipt in purchase.receipts" :key="receipt.id" class="receipt-card">
          <div class="receipt-card__head">
            <div>
              <h6>Receipt {{ receipt.receipt_no }}</h6>
              <span>{{ formatDateTime(receipt.received_at) }} Â· {{ receipt.received_by_name || '-' }}</span>
            </div>
          </div>

          <div class="receipt-ref">Ref: {{ receipt.reference_no || '-' }}</div>

          <div v-for="item in receipt.items" :key="item.id" class="receipt-item">
            <div class="receipt-badge">{{ item.unit_symbol || '-' }}</div>
            <div>
              <strong>{{ item.ingredient?.name || '-' }}</strong>
              <div class="text-muted">
                Quantity: {{ formatAmount(item.quantity) }} {{ item.unit_name || item.unit_symbol || '' }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="!purchase.receipts.length" class="receipt-empty">
          No receipts yet.
        </div>
      </aside>
    </div>

    <ConfirmActionModal v-model:show="showReceiveModal" :target-id="purchase.id" :target-name="purchase.reference_no"
      :loading="receiving" title="Mark purchase as received?" cancel-label="Cancel" confirm-label="Receive Stock"
      icon-class="bi-box-arrow-in-down" tone="orange" @confirm="confirmReceive">
      <template #description>
        This will receive all remaining purchase quantities and add them to ingredient stock.
      </template>
    </ConfirmActionModal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import ConfirmActionModal from '@/Components/ConfirmActionModal.vue'
import { error as alertError } from '@/Utils/modernAlert'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  purchase: { type: Object, required: true },
  statusOptions: { type: Object, default: () => ({}) },
})

const statusLabel = computed(() => props.statusOptions[props.purchase.status] || props.purchase.status)
const primaryCurrencyCode = computed(() => props.purchase.base_currency_code || props.purchase.currency || 'LKR')
const secondaryCurrencyCode = computed(() => props.purchase.secondary_currency_code || '')
const hasSecondaryCurrency = computed(() => !!secondaryCurrencyCode.value)
const showReceiveModal = ref(false)
const receiving = ref(false)

function formatAmount(value) {
  const num = parseFloat(value || 0)
  return Number.isFinite(num) ? num.toFixed(3) : '0.000'
}

function formatDateTime(value) {
  if (!value) return '-'
  return String(value).replace('T', ' ').slice(0, 16)
}

function openReceiveModal() {
  showReceiveModal.value = true
}

function confirmReceive() {
  receiving.value = true
  router.post(route('vendor.purchases.mark-received', props.purchase.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showReceiveModal.value = false
    },
    onError: (errors) => {
      alertError(errors?.general || 'Unable to mark purchase as received.')
    },
    onFinish: () => {
      receiving.value = false
    },
  })
}
</script>

<style scoped>
.page-wrap{
  background:#f6f7fb;
  min-height:100%;
  padding:4px;
}
.head-row{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  flex-wrap:wrap;
  margin-bottom:16px;
}
.page-title{
  font-weight:800;
  color:#334155;
  margin:0 0 4px 0;
}
.page-subtitle{
  color:#64748b;
  font-size:14px;
}
.show-layout{
  display:grid;
  grid-template-columns:1fr 420px;
  gap:22px;
}
.info-card,
.items-card,
.receipt-card,
.receipt-empty{
  background:#fff;
  border-radius:18px;
  padding:18px;
  box-shadow:0 8px 24px rgba(15,23,42,.06);
}
.section-title{
  font-weight:800;
  color:#475569;
  margin:0;
}
.info-grid{
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:18px;
  margin-top:16px;
}
.info-grid div{
  display:flex;
  flex-direction:column;
  gap:6px;
}
.info-grid .wide{
  grid-column:span 4;
}
.info-grid strong{
  font-size:14px;
  color:#475569;
}
.info-grid span{
  color:#6b7280;
}
.summary-panels{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:16px;
  margin-top:20px;
}
.summary-card{
  border:1px solid #e2e8f0;
  border-radius:16px;
  padding:16px;
}
.summary-card--secondary{
  background:#f8fbff;
}
.summary-card h6{
  margin:0 0 10px 0;
  font-weight:800;
  color:#334155;
}
.summary-row{
  display:flex;
  justify-content:space-between;
  gap:12px;
  padding:8px 0;
  color:#475569;
}
.summary-row--total{
  border-top:1px solid #e2e8f0;
  margin-top:8px;
  padding-top:12px;
  font-size:18px;
}
.status-pill{
  display:inline-block;
  background:#dff4fb;
  color:#10b7f4;
  border-radius:8px;
  padding:4px 10px;
  font-weight:700;
  width:max-content;
}
.receipt-column{
  display:flex;
  flex-direction:column;
  gap:16px;
}
.receipt-card__head{
  display:flex;
  justify-content:space-between;
  gap:12px;
  align-items:flex-start;
}
.receipt-card__head h6{
  margin:0;
  font-weight:800;
}
.receipt-card__head span{
  font-size:13px;
  color:#9ca3af;
}
.receipt-ref{
  margin:14px 0;
  padding:4px 10px;
  border-radius:8px;
  background:#dff4fb;
  color:#10b7f4;
  font-weight:700;
  display:inline-block;
}
.receipt-item{
  display:flex;
  gap:12px;
  align-items:flex-start;
  margin-top:14px;
}
.receipt-badge{
  width:38px;
  height:38px;
  border-radius:50%;
  background:#fff1e4;
  color:#2563eb;
  display:grid;
  place-items:center;
  font-weight:800;
  font-size:12px;
}
.btn-cancel,
.btn-create{
  min-width:110px;
  border-radius:12px;
  padding:10px 16px;
}
.btn-cancel{
  background:#fff;
  border:1px solid #e2e8f0;
  color:#475569;
  text-decoration:none;
}
.btn-create{
  background:#2563eb;
  border:none;
  color:#fff;
  font-weight:700;
}
@media (max-width: 1399.98px){
  .show-layout{
    grid-template-columns:1fr;
  }
}
@media (max-width: 1199.98px){
  .info-grid{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
  .info-grid .wide{
    grid-column:span 2;
  }
  .summary-panels{
    grid-template-columns:1fr;
  }
}
@media (max-width: 767.98px){
  .info-grid{
    grid-template-columns:1fr;
  }
  .info-grid .wide{
    grid-column:span 1;
  }
}
</style>
