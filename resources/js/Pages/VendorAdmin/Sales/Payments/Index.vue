<template>
  <Head title="Payments" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Payments</h1>
            <p class="header-subtitle">
              Track POS payment records by order, branch, cashier, method and amount.
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
          :order="[[5, 'desc']]"
          searchPlaceholder="Search by order no, branch, cashier or payment method..."
        >
          <template #header>
            <tr>
              <th>Order No</th>
              <th>Branch</th>
              <th>Cashier</th>
              <th>Method</th>
              <th>Amount</th>
              <th>Created At</th>
            </tr>
          </template>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'

defineOptions({ layout: VendorAdminLayout })

const tableId = 'salesPaymentsTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.sales.payments.getdata'))

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

const columns = computed(() => ([
  {
    data: 'order_no',
    name: 'transaction.order_no',
    orderable: false,
    render: (data, type, row) => `
      <div>
        <div class="fw-bold text-dark">${escapeHtml(data || '-')}</div>
        <div class="text-muted x-small">PAY #${escapeHtml(row?.id)}</div>
      </div>
    `,
  },
  {
    data: 'branch_name',
    name: 'transaction.session.register.branch.name',
    orderable: false,
    render: (data) => `
      <span class="branch-pill">
        <i class="bi bi-shop"></i>
        ${escapeHtml(data || '-')}
      </span>
    `,
  },
  {
    data: 'cashier_name',
    name: 'transaction.cashier_name',
    orderable: false,
    render: (data) => `
      <span class="fw-semibold text-dark">${escapeHtml(data || 'System')}</span>
    `,
  },
  {
    data: 'method_badge',
    name: 'payment_method',
    render: (data) => data || '-',
  },
  {
    data: 'amount_display',
    name: 'amount',
    render: (data) => `
      <span class="fw-bold text-dark">${escapeHtml(data)}</span>
    `,
  },
  {
    data: 'created_at',
    name: 'created_at',
    render: (data) => `<span class="text-secondary small">${escapeHtml(data)}</span>`,
  },
]))

const columnDefs = [
  { targets: '_all', className: 'align-middle' },
]
</script>

<style scoped>
.table-container-modern {
  padding: 0.5rem 1.5rem 1.5rem;
}

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

:deep(.branch-pill) {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-height: 30px;
  padding: 0 0.75rem;
  border-radius: 999px;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 700;
}

:deep(.x-small) {
  font-size: 0.72rem;
}

@media (max-width: 760px) {
  .table-container-modern {
    padding: 0.5rem 0.75rem 1rem;
  }
}
</style>