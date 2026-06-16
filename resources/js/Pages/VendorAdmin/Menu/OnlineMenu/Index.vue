<template>

  <Head title="Online Menus" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Online Menus</h1>
            <p class="header-subtitle">Manage all online menus for this tenant.</p>
          </div>
          <button v-if="can('online-menus.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Online Menu</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[1, 'desc']]" searchPlaceholder="Search online menu name, slug">
          <template #header>
            <tr>
              <th>Name</th>
              <th>Branch</th>
              <th>Menu</th>
              <th>Slug</th>
              <th>Activation</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this online menu?" cancel-label="Keep Menu" confirm-label="Delete Menu"
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

const tableId = 'onlineMenuTable'
const dtRef = ref(null)
const datatableUrl = computed(() => route('vendor.online-menus.getdata'))

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
      ? `<span class="branch-chip">
           <i class="bi bi-building"></i>
           <span class="branch-chip-text">${escapeHtml(data)}</span>
         </span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'menu_name',
    name: 'menu_id',
    orderable: false,
    searchable: false,
    render: (data) => data
      ? `<span class="branch-chip">
           <i class="bi bi-building"></i>
           <span class="branch-chip-text">${escapeHtml(data)}</span>
         </span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'slug',
    name: 'slug',
    render: (data) => data
      ? `<span class="slug-chip"><i class="bi bi-link-45deg"></i> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">—</span>`,
  },
  {
    data: 'status',
    name: 'is_active',
    orderable: false,
    searchable: false,
    render: (data) => {
      const isActive = parseInt(data) === 1
      if (isActive) {
        return `
          <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-check-circle-fill"></i>
            Active
          </span>`
      }
      return `
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
          <i class="bi bi-x-circle-fill text-danger"></i>
          Inactive
        </span>`
    }
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
        ${can('online-menus.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit">
               <i class="bi bi-pencil-fill"></i>
             </button>`
          : ''
        }
        ${can('online-menus.delete')
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

function goCreate() {
  router.visit(route('vendor.online-menus.create'))
}

function goEdit(id) {
  router.visit(route('vendor.online-menus.edit', id))
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
  router.delete(route('vendor.online-menus.destroy', id), {
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
  font-size: 0.78rem;
  font-weight: 500;
  max-width: 280px;
  vertical-align: middle;
}

:deep(.branch-chip-text) {
  display: inline-block;
  max-width: 220px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

:deep(.menu-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #15803d;
  font-size: 0.78rem;
  font-weight: 500;
}

:deep(.slug-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  background: #f5f3ff;
  color: #6d28d9;
  font-size: 0.78rem;
  font-weight: 500;
  font-family: monospace;
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