<template>

  <Head title="Taxes" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Taxes</h1>
            <p class="header-subtitle">Manage all taxes for this tenant.</p>
          </div>
          <button v-if="can('taxes.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Taxes</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[6, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th class="text-center">
                <input type="checkbox" class="form-check-input">
              </th>
              <th>Name</th>
              <th>Branch</th>
              <th>Code</th>
              <th>Rate</th>
              <th>Compound</th>
              <th>Type</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this tax?" cancel-label="Keep Tax" confirm-label="Delete Tax"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const tableId = 'taxTable'
const dtRef = ref(null)
const datatableUrl = computed(() => route('vendor.taxes.getdata'))

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
    render: (data) => `
      <div class="text-center">
        <input type="checkbox" class="form-check-input js-select-row" value="${data}">
      </div>
    `,
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
            TX - ${row.id}
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
    data: 'code',
    name: 'code',
    render: (data) => data
      ? `<span class="code-chip">${escapeHtml(data)}</span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'rate',
    name: 'rate',
    render: (data) => data !== null && data !== undefined
      ? `<span class="quantity-chip">${escapeHtml(String(data))}</span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'compound_badge',
    name: 'is_compound',
    orderable: false,
    searchable: false,
    render: (data) => data ?? `<span class="text-muted small">—</span>`,
  },
  {
    data: 'type_badge',
    name: 'type',
    orderable: false,
    searchable: false,
    render: (data) => data ?? `<span class="text-muted small">—</span>`,
  },
  {
    data: 'status_badge',
    name: 'is_active',
    orderable: false,
    searchable: false,
    render: (data) => data ?? `<span class="text-muted small">—</span>`,
  },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const name = escapeHtml(row?.name ?? '')
      return `
  <div class="d-flex gap-2 justify-content-end">
    ${can('taxes.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
          : ''
        }

    ${can('taxes.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${data}" data-name="${name}">
            <i class="bi bi-trash3-fill"></i>
          </button>`
          : ''
        }
  </div>
`
    }
  },
]))

const columnDefs = [
  { targets: 0, className: 'text-center align-middle', width: '48px' },
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

const goCreate = () => router.visit(route('vendor.taxes.create'))
const goEdit = (id) => router.visit(route('vendor.taxes.edit', id))

function toggleStatus(id, active) {
  const isActive = Number(active) === 1
  if (!window.confirm(isActive ? 'Deactivate this tax?' : 'Activate this tax?')) return
  router.patch(route('vendor.taxes.toggle-status', id), {}, { preserveScroll: true })
}

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.taxes.destroy', id), {
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
  $(document).on('click', `${s} .js-edit`, (e) => {
    const id = e.currentTarget?.dataset?.id
    if (id) goEdit(id)
  })
  $(document).on('click', `${s} .js-delete`, (e) => {
    const id = e.currentTarget?.dataset?.id
    const name = e.currentTarget?.dataset?.name || ''
    if (id) openDeleteModal(id, name)
  })
  $(document).on('click', `${s} .js-toggle-status`, (e) => {
    const id = e.currentTarget?.dataset?.id
    const active = e.currentTarget?.dataset?.active
    if (id) toggleStatus(id, active)
  })
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const s = `#${tableId}`
  $(document).off('click', `${s} .js-edit`)
  $(document).off('click', `${s} .js-delete`)
  $(document).off('click', `${s} .js-toggle-status`)
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
  border-radius: 8px;
  color: var(--slate-600);
  font-size: 0.78rem;
  font-weight: 500;
}

:deep(.code-chip) {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #6d28d9;
  font-size: 0.78rem;
  font-weight: 600;
  font-family: monospace;
  letter-spacing: 0.05em;
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

:deep(.tax-badge) {
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

:deep(.tax-badge--compound) {
  background: #ffedd5;
  color: #f97316;
}

:deep(.tax-badge--plain) {
  background: #dbeafe;
  color: #2563eb;
}

:deep(.tax-badge--type) {
  background: #e5e7eb;
  color: #4b5563;
}

:deep(.tax-badge--active) {
  background: #dcfce7;
  color: #22c55e;
}

:deep(.tax-badge--inactive) {
  background: #fee2e2;
  color: #ef4444;
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