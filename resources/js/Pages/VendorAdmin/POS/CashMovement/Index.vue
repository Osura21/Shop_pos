<template>
  <Head title="Cash Movements" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Cash Movements</h1>
            <p class="header-subtitle">Review all cash in and cash out activity, including balance transitions and reasons.</p>
          </div>
          <button v-if="can('pos-cash-movements.create')" type="button" class="btn-primary-modern" @click="openCreateModal">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Cash Movement</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable
          ref="dtRef"
          :id="tableId"
          :url="datatableUrl"
          :columns="columns"
          :columnDefs="columnDefs"
          :order="[[7, 'desc']]"
          searchPlaceholder="Search by register, reason, or reference..."
        >
          <template #header>
            <tr>
              <th>Register</th>
              <th>Branch</th>
              <th>Direction</th>
              <th>Reason</th>
              <th>Balance Before</th>
              <th>Amount</th>
              <th>Balance After</th>
              <th>Occurred At</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <CashMovementOffcanvas
    :show="showCreateModal"
    :session-id="null"
    :movement="editingMovement"
    :open-sessions="openSessions"
    @close="closeCreateModal"
  />

  <DeleteModal
    v-model:show="showDeleteModal"
    :target-id="deleteTarget.id"
    :target-name="deleteTarget.name"
    :loading="deleting"
    title="Delete this cash movement?"
    cancel-label="Keep Cash Movement"
    confirm-label="Delete Cash Movement"
    @confirm="confirmDelete"
  />
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import CashMovementOffcanvas from './CashMovementOffcanvas.vue'
import { currencySymbol } from '@/Utils/currency'
import { usePermission } from "@/composables/usePermission"
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert"


defineOptions({ layout: VendorAdminLayout })


const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  openSessions: { type: Array, default: () => [] },
})

  const { can } = usePermission()
  const page = usePage()

  const tableId = 'cashMovementsTable'
  const dtRef = ref(null)
  const datatableUrl = computed(() => route('vendor.pos.cash-movements.getdata'))

  const showCreateModal = ref(false)
  const editingMovement = ref(null)
  const showDeleteModal = ref(false)
  const deleteTarget = ref({ id: null, name: '' })
  const deleting = ref(false)

  function escapeHtml(value = '') {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}
function money(value) {
  return Number(value || 0).toFixed(3)
}
function prettyLabel(value) {
  if (!value) return '—'
  return String(value)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}
const columns = computed(() => {
  const list = [
    {
      data: 'register_name',
      name: 'register_name',
      render: (data, type, row) => 
        `<div>
          <div class="fw-bold text-dark">${escapeHtml(data || '—')}</div>
          <div class="text-muted x-small">CM-${row.id}</div>
        </div>
        `

    },

    {
      data: 'branch_name',
      name: 'branch_name',
      render: (data) => data
        ? `<span class="branch-chip"><i class="bi bi-building"></i> ${escapeHtml(data)}</span>`
        : `<span class="text-muted small">—</span>`
    },
    {
      data: 'direction',
      name: 'direction',
      orderable: false,
      searchable: false,
      render: (data) => {
        const isIn = data === 'in'
        const cls = isIn ? 'direction-badge--in' : 'direction-badge--out'
        const icon = isIn ? 'bi-arrow-down-circle-fill' : 'bi-arrow-up-circle-fill'
        const label = isIn ? 'Cash In' : 'Cash Out'
        return `<span class="direction-badge ${cls}"><i class="bi ${icon}"></i> ${label}</span>`
      }
    },
    {
      data: 'reason',
      name: 'reason',
      render: (data) =>
        `<span class="reason-chip">${escapeHtml(prettyLabel(data))}</span>`
    },
    {
      data: 'balance_before',
      name: 'balance_before',
      render: (data, type, row) =>
        `<span class="amount-chip amount-chip--neutral">${escapeHtml(currencySymbol(row.currency_code) || '-')} ${money(data)}</span>`
    },
    {
      data: 'amount',
      name: 'amount',
      render: (data, type, row) => {
        const cls = row.direction === 'in' ? 'amount-chip--in' : 'amount-chip--out'
        return `<span class="amount-chip ${cls}">${escapeHtml(currencySymbol(row.currency_code) || '-')} ${money(data)}</span>`
      }
    },
    {
      data: 'balance_after',
      name: 'balance_after',
      render: (data, type, row) =>
        `<span class="amount-chip amount-chip--neutral">${escapeHtml(currencySymbol(row.currency_code) || '-')} ${money(data)}</span>`
    },
    {
      data: 'occurred_at',
      name: 'occurred_at',
      render: (data) =>
        `<span class="text-secondary small">${data ? new Date(data).toLocaleString() : '—'}</span>`
    },
    {
      data: 'id',
      name: 'actions',
      orderable: false,
      searchable: false,
      render: (data, type, row) => {
        const id = escapeHtml(data)
        let actionsHtml = `<div class="d-flex gap-2 justify-content-end">`
        if (can('pos-cash-movements.edit')) {
          actionsHtml += `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit">
            <i class="bi bi-pencil-fill"></i>
          </button>`
        }
        if (can('pos-cash-movements.delete')) {
          actionsHtml += `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" title="Delete">
            <i class="bi bi-trash3-fill"></i>
          </button>`
        }
        actionsHtml += `</div>`
        return actionsHtml
      }
    }
  ]
  return list
})

  const columnDefs = [
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]
function openCreateModal() {
  editingMovement.value = null
  showCreateModal.value = true
}
function openEditModal(movement) {
  editingMovement.value = movement
  showCreateModal.value = true
}
function closeCreateModal() {
  showCreateModal.value = false
  editingMovement.value = null
  setTimeout(() => {
    dtRef.value?.reloadDatatable?.()
  }, 300)
}
function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}
function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.pos.cash-movements.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      setTimeout(() => {
        dtRef.value?.reloadDatatable?.()
      }, 300)
    },
    onError: (errors) => {
      deleting.value = false
      if (errors.general) {
        alertError(errors.general)
      }
    },
    onFinish: () => {
      deleting.value = false
    },
  })
}
function bindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`
  $(document).on('click', `${selector} .js-edit`, (e) => {
    const id = e.currentTarget?.dataset?.id
    if (id) {
      const table = $(`#${tableId}`).DataTable()
      const rowData = table.row($(e.currentTarget).closest('tr')).data()
      if (rowData) {
        openEditModal(rowData)
      }
    }
  })
  $(document).on('click', `${selector} .js-delete`, (e) => {
    const id = e.currentTarget?.dataset?.id
    if (id) {
      const table = $(`#${tableId}`).DataTable()
      const rowData = table.row($(e.currentTarget).closest('tr')).data()
      const name = rowData ? `CM-${rowData.id} (${prettyLabel(rowData.reason)})` : `CM-${id}`
      openDeleteModal(id, name)
    }
  })
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`
  $(document).off('click', `${selector} .js-edit`)
  $(document).off('click', `${selector} .js-delete`)
}
onMounted(() => {
  bindActions()
})
onUnmounted(() => {
  unbindActions()
})
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) alertSuccess(flash.message)
    if (flash?.error) alertError(flash.error)
  },
  { immediate: true }
)
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

/* Branch chip */
:deep(.branch-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  text-wrap: nowrap;
  border-radius: 8px;
  color: var(--slate-600);
  font-size: 0.78rem;
  font-weight: 500;
}

/* Direction badge */
:deep(.direction-badge) {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
  gap: 0.4rem;
  padding: 0.35rem 0.8rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
}

:deep(.direction-badge--in) {
  background: #dcfce7;
  color: #15803d;
}

:deep(.direction-badge--out) {
  background: #fee2e2;
  color: #dc2626;
}

/* Reason chip */
:deep(.reason-chip) {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  text-wrap: nowrap;
  color: #6d28d9;
  font-size: 0.78rem;
  font-weight: 500;
}

/* Amount chips */
:deep(.amount-chip) {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  font-family: monospace;
  white-space: nowrap;
}

:deep(.amount-chip--neutral) {
  background: var(--slate-50);
  color: var(--slate-600);
  border: 1px solid var(--slate-200);
}

:deep(.amount-chip--in) {
  background: #dcfce7;
  color: #15803d;
}

:deep(.amount-chip--out) {
  background: #fee2e2;
  color: #dc2626;
}

:deep(.btn-circle) {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--slate-200);
  background: white;
  color: var(--slate-600);
  transition: 0.2s;
  cursor: pointer;
}
:deep(.btn-circle:hover) {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-soft);
}
:deep(.btn-circle-danger:hover) {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}
</style>
