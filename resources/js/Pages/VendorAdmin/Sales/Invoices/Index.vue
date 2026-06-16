<template>
  <Head title="Invoices" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Invoices</h1>
            <p class="header-subtitle">
              View and manage issued sales invoices with branch, buyer, payment and tax details.
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
          :order="[[10, 'desc']]"
          searchPlaceholder="Search by invoice no, seller, buyer, branch or status..."
        >
          <template #header>
            <tr>
              <th>Invoice No</th>
              <th>Branch</th>
              <th>Seller</th>
              <th>Buyer</th>
              <th>Type</th>
              <th>Status</th>
              <th>PMS</th>
              <th>Purpose</th>
              <th>Kind</th>
              <th>Total</th>
              <th>Issued At</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import { error as alertError } from '@/Utils/modernAlert'

defineOptions({ layout: VendorAdminLayout })

const tableId = 'salesInvoicesTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.sales.invoices.getdata'))

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function goShow(id) {
  if (!id) return
  router.visit(route('vendor.sales.invoices.show', id))
}

function openPrint(id) {
  if (!id) return
  window.open(route('vendor.sales.invoices.print', id), '_blank')
}

function openDownload(id) {
  if (!id) return
  window.open(route('vendor.sales.invoices.download', id), '_blank')
}

async function retryPmsPosting(id) {
  if (!id) return

  try {
    await window.axios.post(route('vendor.pms.room-charge.retry', id))
    window.location.reload()
  } catch (error) {
    alertError(error?.response?.data?.message || 'Unable to retry PMS posting.')
  }
}

const columns = computed(() => ([
  {
    data: 'invoice_no',
    name: 'invoice_no',
    render: (data, type, row) => `
      <div>
        <div class="fw-bold text-dark">${escapeHtml(data || '-')}</div>
        <div class="text-muted x-small">INV #${escapeHtml(row?.id)}</div>
      </div>
    `,
  },
  {
    data: 'branch_name',
    name: 'branch.name',
    orderable: false,
    render: (data, type, row) => `
      <span class="branch-pill">
        <i class="bi bi-shop"></i>
        ${escapeHtml(data || '-')}
      </span>
    `,
  },
  {
    data: 'seller_display',
    name: 'seller_name',
    render: (data) => `
      <span class="fw-semibold text-dark">${escapeHtml(data || '-')}</span>
    `,
  },
  {
    data: 'buyer_display',
    name: 'buyer_name',
    render: (data) => `
      <span class="fw-semibold text-dark">${escapeHtml(data || '-')}</span>
    `,
  },
  {
    data: 'type_badge',
    name: 'type',
    render: (data) => data || '-',
  },
  {
    data: 'status_badge',
    name: 'status',
    render: (data) => data || '-',
  },
  {
    data: 'pms_posting_badge',
    name: 'pms_posting_status',
    render: (data) => data || '-',
  },
  {
    data: 'purpose_badge',
    name: 'purpose',
    render: (data) => data || '-',
  },
  {
    data: 'kind_badge',
    name: 'kind',
    render: (data) => data || '-',
  },
  {
    data: 'total_display',
    name: 'total',
    render: (data) => `
      <span class="fw-bold text-dark">${escapeHtml(data)}</span>
    `,
  },
  {
    data: 'issued_at',
    name: 'issued_at',
    render: (data) => `<span class="text-secondary small">${escapeHtml(data)}</span>`,
  },
  {
    data: 'id',
    orderable: false,
    searchable: false,
    render: (data, type, row) => `
      <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn-circle js-show-invoice" data-id="${escapeHtml(data)}" title="Show invoice">
          <i class="bi bi-eye-fill"></i>
        </button>

        <button type="button" class="btn-circle js-print-invoice" data-id="${escapeHtml(data)}" title="Print invoice">
          <i class="bi bi-printer-fill"></i>
        </button>

        <button type="button" class="btn-circle js-download-invoice" data-id="${escapeHtml(data)}" title="Download invoice">
          <i class="bi bi-download"></i>
        </button>

        ${row?.pms_posting_status === 'failed' || row?.pms_posting_status === 'pending' ? `
          <button type="button" class="btn-circle js-retry-pms" data-id="${escapeHtml(data)}" title="Retry PMS posting">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
        ` : ''}
      </div>
    `,
  },
]))

const columnDefs = [
  { targets: -1, width: '140px' },
  { targets: '_all', className: 'align-middle' },
]

function handleTableClick(event) {
  const showButton = event.target.closest(`#${tableId} .js-show-invoice`)
  if (showButton) {
    goShow(showButton.dataset.id)
    return
  }

  const printButton = event.target.closest(`#${tableId} .js-print-invoice`)
  if (printButton) {
    openPrint(printButton.dataset.id)
    return
  }

  const downloadButton = event.target.closest(`#${tableId} .js-download-invoice`)
  if (downloadButton) {
    openDownload(downloadButton.dataset.id)
    return
  }

  const retryButton = event.target.closest(`#${tableId} .js-retry-pms`)
  if (retryButton) {
    retryPmsPosting(retryButton.dataset.id)
  }
}

onMounted(() => {
  document.addEventListener('click', handleTableClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleTableClick)
})
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
  color: #475569;
  font-size: 0.8rem;
  font-weight: 700;
}

:deep(.btn-circle:hover) {
  border-color: #f97316;
  color: #f97316;
  background: #fff7ed;
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
