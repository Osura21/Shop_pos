<template>
  <Head title="Gift Card Transactions" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Gift Card Transactions</h1>
            <p class="header-subtitle">
              Every purchase, redemption, recharge and adjustment is recorded here.
            </p>
          </div>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable
          ref="dtRef"
          :id="tableId"
          :url="datatableUrl"
          :columns="columns"
          :columnDefs="columnDefs"
          :order="[[8, 'desc']]"
          searchPlaceholder="Search here..."
        >
          <template #header>
            <tr>
              <th class="text-center" style="width: 44px;">
                <input type="checkbox" class="form-check-input">
              </th>
              <th>Transaction ID</th>
              <th>Gift Card</th>
              <th>Type</th>
              <th>Currency</th>
              <th>Amount</th>
              <th>Balance Before</th>
              <th>Balance After</th>
              <th>Branch</th>
              <th>Transaction Date</th>
            </tr>
          </template>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const tableId = 'giftCardTransactionsTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.gift-cards.transactions.getdata'))

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function moneyCell(currency, value) {
  if (value === null || value === undefined || value === '') {
    return `<span class="text-muted small">—</span>`
  }

  return `
    <span class="quantity-chip">
      ${escapeHtml(currency || '')} ${escapeHtml(String(value))}
    </span>
  `
}

const columns = computed(() => [
  {
    data: 'id',
    name: 'checkbox',
    orderable: false,
    searchable: false,
    render: (data) => `
      <div class="text-center">
        <input type="checkbox" class="form-check-input js-select-row" value="${data}">
      </div>
    `,
  },
  {
    data: 'uuid',
    name: 'uuid',
    render: (data, type, row) => `
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold  text-nowrap text-dark mb-0">
            ${escapeHtml(data || '')}
          </div>
          <div class="text-muted x-small">
            TX - ${row.id || '-'}
          </div>
        </div>
      </div>
    `,
  },
  {
    data: 'gift_card_code',
    name: 'gift_card_id',
    orderable: false,
    searchable: false,
    render: (data) => `
      <span class="code-chip text-nowrap">${escapeHtml(data || '-')}</span>
    `,
  },
  {
    data: 'type_badge',
    name: 'type',
    render: (data, type, row) => {
      if (data) return data

      return `<span class="status-chip">${escapeHtml(row?.type || '-')}</span>`
    },
  },
  {
    data: 'currency_label',
    name: 'currency_code',
    orderable: false,
    searchable: false,
    render: (data) => `
      <span class="currency-chip">${escapeHtml(data || '-')}</span>
    `,
  },
  {
    data: 'amount',
    name: 'amount',
    render: (data, type, row) => moneyCell(row?.currency_label, data),
  },
  {
    data: 'balance_before',
    name: 'balance_before',
    render: (data, type, row) => moneyCell(row?.currency_label, data),
  },
  {
    data: 'balance_after',
    name: 'balance_after',
    render: (data, type, row) => moneyCell(row?.currency_label, data),
  },
  {
    data: 'branch_name',
    name: 'branch_id',
    orderable: false,
    searchable: false,
    render: (data) => `
      <span class="text-secondary small">${escapeHtml(data || '-')}</span>
    `,
  },
  {
    data: 'occurred_at',
    name: 'occurred_at',
    render: (data) => `
      <span class="text-secondary small">${escapeHtml(data || '-')}</span>
    `,
  },
])

const columnDefs = [
  { targets: 0, className: 'text-center align-middle', width: '44px' },
  { targets: '_all', className: 'align-middle' },
]


watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) {
      alertSuccess((flash.message))
    }

    if (flash?.error) {
      alertError((flash.error))
    }
  },
  { immediate: true }
)

</script>

<style scoped>

:deep(thead th) {
  background: transparent !important;
  color: var(--slate-400) !important;
  font-weight: 600 !important;
  font-size: 0.75rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.05em !important;
  border-bottom: 1px solid var(--slate-50) !important;
  padding: 1.25rem 1rem !important;
}

:deep(tbody td) {
  padding: 1.25rem 1rem !important;
  border-bottom: 1px solid var(--slate-50) !important;
}

:deep(.form-check-input) {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

:deep(.quantity-chip) {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  color: #2563eb;
  font-size: 0.78rem;
  font-weight: 700;
}

:deep(.code-chip) {
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  padding: 0.35rem 0.7rem;
  color: #c2410c;
  font-size: 0.78rem;
  text-wrap: nowarp;
  font-weight: 800;
  letter-spacing: 0.04em;
}

:deep(.currency-chip) {
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  padding: 0.35rem 0.7rem;
  background: #f8fafc;
  color: #475569;
  font-size: 0.78rem;
  font-weight: 800;
}

:deep(.badge) {
  font-size: 0.76rem;
  font-weight: 800;
  padding: 0.42rem 0.7rem;
}

:deep(.status-chip) {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.38rem 0.7rem;
  background: #f1f5f9;
  color: #475569;
  font-size: 0.76rem;
  font-weight: 800;
}

@media (max-width: 768px) {
  .table-container-modern {
    padding: 0.5rem 1rem 1rem;
  }
}
</style>