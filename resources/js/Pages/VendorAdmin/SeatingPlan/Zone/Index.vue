<template>

  <Head title="Zones" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Zones</h1>
            <p class="header-subtitle">Manage seating zones.</p>
          </div>
          <button v-if="can('zones.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Zone</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[5, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th class="text-center">
                <input type="checkbox" class="form-check-input">
              </th>
              <th>Name</th>
              <th>Branch</th>
              <th>Floor</th>
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
    :loading="deleting" title="Delete this zone?" cancel-label="Keep Zone" confirm-label="Delete Zone"
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

const tableId = 'zoneTable'
const dtRef = ref(null)
const datatableUrl = computed(() => route('vendor.zones.getdata'))

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
    render: (d) => `<div class="text-center"><input type="checkbox" class="form-check-input js-select-row" value="${d}"></div>`,
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
            ZN - ${row.id}
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
      : `<span class="text-muted small">â€”</span>`,
  },
  {
    data: 'floor_name',
    name: 'floor_id',
    orderable: false,
    searchable: false,
    render: (data) => data
      ? `<span class="floor-chip"> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">â€”</span>`,
  },
  {
    data: 'activation_badge',
    name: 'is_active',
    orderable: false,
    searchable: false,
    render: (d) => d ?? `<span class="text-muted small">â€”</span>`,
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
        ${can('zones.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit">
               <i class="bi bi-pencil-fill"></i>
             </button>`
          : ''
        }
        ${can('zones.delete')
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

const goCreate = () => router.visit(route('vendor.zones.create'))
const goEdit = (id) => router.visit(route('vendor.zones.edit', id))

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.zones.destroy', id), {
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

/* Checkbox */
:deep(.form-check-input) {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

/* Name chip */
:deep(.name-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #c2410c;
  font-size: 0.78rem;
  font-weight: 600;
}

/* Branch chip */
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

/* Floor chip */
:deep(.floor-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #1d4ed8;
  font-size: 0.78rem;
  font-weight: 500;
}

/* Circle action buttons */
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
