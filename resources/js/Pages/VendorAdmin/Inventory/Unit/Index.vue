<template>

  <Head title="Units" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Units</h1>
            <p class="header-subtitle">Manage measurement units, symbols, and types for your products.</p>
          </div>
          <button v-if="can('units.create')" type="button" class="btn btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Unit</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[4, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th>Name</th>
              <th>Symbol</th>
              <th>Type</th>
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
    :loading="deleting" title="Delete this unit?" cancel-label="Keep Unit" confirm-label="Delete Unit"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { usePermission } from "@/composables/usePermission";
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'

defineOptions({ layout: VendorAdminLayout })

const page = usePage()
const { can } = usePermission()

const tableId = 'unitTable'
const dtRef = ref(null)
const datatableUrl = computed(() => route('vendor.units.getdata'))

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

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
            UN - ${row.id}
          </div>
        </div>
      </div>
    `
  },
  {
    data: 'symbol',
    name: 'symbol',
    render: (data) => `<span class="symbol-chip">${data}</span>`
  },
  {
    data: 'type_label',
    name: 'type',
    render: (data, type, row) => {
      const colorMap = {
        custom: { bg: 'rgba(59, 130, 246, 0.10)', color: '#2563eb' },
        count: { bg: 'rgba(59,130,246,0.10)', color: '#3b82f6' },
      }
      const style = colorMap[row.type] ?? { bg: 'rgba(22,163,74,0.10)', color: '#16a34a' }
      return `<span>${data}</span>`
    },
  },
  { data: 'created_at', name: 'created_at', render: d => `<span class="text-secondary small">${d}</span>` },
  { data: 'updated_at', name: 'updated_at', render: d => `<span class="text-secondary small">${d}</span>` },
  {
    data: 'id',
    orderable: false,
    render: (data, type, row) => {
      const name = String(row?.name ?? '').replace(/"/g, '&quot;')

      return `
      <div class="d-flex gap-2 justify-content-end">
        ${can('units.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
               <i class="bi bi-pencil-fill"></i>
             </button>`
          : ''
        }
        ${can('units.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${data}" data-name="${name}">
               <i class="bi bi-trash3-fill"></i>
             </button>`
          : ''
        }
      </div>`
    }
  }
]))

const columnDefs = [
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

function goCreate() {
  router.visit(route('vendor.units.create'))
}

function goEdit(id) {
  router.visit(route('vendor.units.edit', id))
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
  router.delete(route('vendor.units.destroy', id), {
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

:deep(.symbol-chip) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  padding: 0.2rem;
  border-radius: 8px;
  background: var(--slate-50);
  border: 1px solid var(--slate-200);
  color: var(--slate-600);
  font-size: 0.85rem;
  font-weight: 700;
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
