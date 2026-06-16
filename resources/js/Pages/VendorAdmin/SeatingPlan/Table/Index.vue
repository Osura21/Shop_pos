<template>

  <Head title="Tables" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Tables</h1>
            <p class="header-subtitle">Manage seating tables.</p>
          </div>
          <button v-if="can('tables.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Table</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[8, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th class="text-center">
                <input type="checkbox" class="form-check-input">
              </th>
              <th>Name</th>
              <th>Branch</th>
              <th>Floor</th>
              <th>Zone</th>
              <th>Capacity</th>
              <th>Activation</th>
              <th>Created At</th>
              <th>Updated At</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this table?" cancel-label="Keep Table" confirm-label="Delete Table"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const tableId = 'diningTableTable'
const dtRef = ref(null)
const datatableUrl = computed(() => route('vendor.tables.getdata'))

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
    data: 'id',
    name: 'checkbox',
    orderable: false,
    searchable: false,
    render: () => `<div class="text-center"><input type="checkbox" class="form-check-input js-select-row"></div>`,
  },
  {
    data: 'name',
    name: 'name',
    render: (data, type, row) => `
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold text-dark mb-0">
            ${data}
          </div>
          <div class="text-muted x-small">
            TB - ${row.id}
          </div>
        </div>
      </div>
    `
  },
  {
    data: 'branch_name',
    name: 'branch_id',
    orderable: false,
    searchable: false,
    render: (data) => data
      ? `<span class="branch-chip"><i class="bi bi-building"></i> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'floor_name',
    name: 'floor_id',
    orderable: false,
    searchable: false,
    render: (data) => data
      ? `<span class="floor-chip"> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'zone_name',
    name: 'zone_id',
    orderable: false,
    searchable: false,
    render: (data) => data
      ? `<span class="zone-chip"> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'capacity',
    name: 'capacity',
    render: (data) => data !== null && data !== undefined
      ? `<span class="quantity-chip"><i class="bi bi-people"></i> ${escapeHtml(String(data))}</span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'activation_badge',
    name: 'is_active',
    orderable: false,
    searchable: false,
    render: (d) => d ?? `<span class="text-muted small">—</span>`,
  },
  { data: 'created_at', name: 'created_at', render: d => `<span class="text-secondary small">${d}</span>` },
  { data: 'updated_at', name: 'updated_at', render: d => `<span class="text-secondary small">${d}</span>` },
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
        ${can('tables.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit">
               <i class="bi bi-pencil-fill"></i>
             </button>`
          : ''
        }
        ${can('tables.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" data-name="${name}" title="Delete">
               <i class="bi bi-trash3-fill"></i>
             </button>`
          : ''
        }
      </div>
    `
    },
  }
]))

const columnDefs = [
  { targets: 0, className: 'text-center align-middle', width: '48px' },
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

const goCreate = () => router.visit(route('vendor.tables.create'))
const goEdit = (id) => router.visit(route('vendor.tables.edit', id))

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.tables.destroy', id), {
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
  const s = `#${tableId}`
  $(document).on('click', `${s} .js-edit`, (e) => goEdit(e.currentTarget.dataset.id))
  $(document).on('click', `${s} .js-delete`, (e) => {
    const id = e.currentTarget?.dataset?.id
    const name = e.currentTarget?.dataset?.name || ''
    if (id) openDeleteModal(id, name)
  })
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const s = `#${tableId}`
  $(document).off('click', `${s} .js-edit`)
  $(document).off('click', `${s} .js-delete`)
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

:deep(.form-check-input) {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

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

:deep(.floor-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #1d4ed8;
  text-wrap: nowrap;
  font-size: 0.78rem;
  font-weight: 500;
}

:deep(.zone-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  text-wrap: nowrap;
  color: #6d28d9;
  font-size: 0.78rem;
  font-weight: 500;
}

:deep(.quantity-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  color: #15803d;
  font-size: 0.78rem;
  font-weight: 700;
}

:deep(.status-badge) {
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
}

:deep(.status-badge--active) {
  background: #dcfce7;
  color: #22c55e;
}

:deep(.status-badge--inactive) {
  background: #fee2e2;
  color: #ef4444;
}

:deep(.status-badge--available) {
  background: #e8f5e9;
  color: #4caf50;
}

:deep(.status-badge--occupied) {
  background: #fde8e8;
  color: #ef4444;
}

:deep(.qr-pill) {
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  padding: 6px 12px;
  background: #e5e7eb;
  color: #4b5563;
  font-weight: 600;
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