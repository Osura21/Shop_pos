<template>

  <Head title="Registers" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Registers</h1>
            <p class="header-subtitle">Create and manage POS registers per branch with printer mapping and status
              control.</p>
          </div>
          <button v-if="can('pos-registers.create')" type="button" class="btn-primary-modern" @click="goToCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Register</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[1, 'desc']]" searchPlaceholder="Search registers...">
          <template #header>
            <tr>
              <th>Name</th>
              <th>Branch</th>
              <th>Code</th>
              <th>Invoice Printer</th>
              <th>Bill Printer</th>
              <th>Status</th>
              <th>Created At</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this register?" cancel-label="Keep Register" confirm-label="Delete Register"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { computed, onMounted, onUnmounted, watch, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const tableId = 'registerTable'
const dtRef = ref(null)
const datatableUrl = computed(() => route('vendor.pos.registers.getdata'))

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

const columns = computed(() => ([
  {
    data: 'name',
    name: 'name',
    render: (data, type, row) => `
      <div>
        <div class="fw-bold text-dark">${escapeHtml(data || 'â€”')}</div>
        <div class="text-muted x-small">REG-${row.id}</div>
      </div>
    `
  },
  {
    data: 'branch',
    name: 'branch',
    render: (data) => data
      ? `<span class="branch-chip"><i class="bi bi-building"></i> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'code',
    name: 'code',
    render: (data) => data
      ? `<span class="code-chip">${escapeHtml(data)}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'invoice_printer',
    name: 'invoice_printer',
    render: (data) => data
      ? `<span class="printer-chip"><i class="bi bi-printer"></i> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'bill_printer',
    name: 'bill_printer',
    render: (data) => data
      ? `<span class="printer-chip"><i class="bi bi-receipt"></i> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'status',
    name: 'status',
    orderable: false,
    searchable: false,
    render: (data) => data || `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'created_at',
    name: 'created_at',
    render: (d) => `<span class="text-secondary small">${d || 'â€”'}</span>`
  },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const name = escapeHtml(row?.name ?? '')
      const id = escapeHtml(data)

      return `
      <div class="d-flex gap-2 justify-content-end">
        ${can('pos-registers.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit">
               <i class="bi bi-pencil-fill"></i>
             </button>`
          : ''
        }
        ${can('pos-registers.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" data-name="${name}" title="Delete">
               <i class="bi bi-trash3-fill"></i>
             </button>`
          : ''
        }
      </div>`
    },
  }
]))

const columnDefs = [
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

const goToCreate = () => router.get(route('vendor.pos.registers.create'))
const goEdit = (id) => router.visit(route('vendor.pos.registers.edit', id))

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.pos.registers.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      setTimeout(() => {
        dtRef.value?.reloadDatatable?.()
      }, 300)
    },
    onError: () => {
      deleting.value = false
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
    if (id) goEdit(id)
  })
  $(document).on('click', `${selector} .js-delete`, (e) => {
    const id = e.currentTarget?.dataset?.id
    const name = e.currentTarget?.dataset?.name || ''
    if (id) openDeleteModal(id, name)
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

:deep(.branch-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: var(--slate-600);
  font-size: 0.78rem;
  font-weight: 500;
  white-space: nowrap;
}

:deep(.code-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #6d28d9;
  font-size: 0.78rem;
  font-weight: 700;
  font-family: monospace;
  letter-spacing: 0.04em;
}

:deep(.printer-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #1d4ed8;
  font-size: 0.78rem;
  font-weight: 500;
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
