<template>

  <Head title="Stock Movements" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Stock Movements</h1>
            <p class="header-subtitle">Track and manage ingredient stock movements across your branches.</p>
          </div>
          <button v-if="can('stock-movements.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Stock Movement</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[5, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th>Branch</th>
              <th>Ingredient</th>
              <th>Type</th>
              <th>Quantity</th>
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
    :loading="deleting" title="Delete this stock movement?" cancel-label="Keep Record" confirm-label="Delete Record"
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

const tableId = 'stockMovementTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.stock-movements.getdata'))

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

const columns = computed(() => ([
  {
    data: 'branch_name',
    name: 'branch_id',
    orderable: false,
    searchable: false,
    render: (data, type, row) => `
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold text-dark mb-0">
            ${data}
          </div>
          <div class="text-muted x-small">
            BR - ${row.id}
          </div>
        </div>
      </div>
    `
  },
  {
    data: 'ingredient_name',
    name: 'ingredient_id',
    orderable: false,
    searchable: false,
    render: (data) => data
      ? `<span class="ingredient-chip"><i class="bi bi-cup-hot"></i> ${data}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'type_badge',
    name: 'type',
    orderable: false,
    searchable: false,
    render: (data) => data ?? `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'quantity_label',
    name: 'quantity',
    render: (data) => data !== null && data !== undefined
      ? `<span class="quantity-chip">${data}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  { data: 'created_at', name: 'created_at', render: d => `<span class="text-secondary small">${d}</span>` },
  { data: 'updated_at', name: 'updated_at', render: d => `<span class="text-secondary small">${d}</span>` },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const name = String(row?.ingredient_name ?? '').replace(/"/g, '&quot;')
      return `
  <div class="d-flex gap-2 justify-content-end">
    ${can('stock-movements.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
          : ''
        }

    ${can('stock-movements.delete')
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
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

function goCreate() {
  router.visit(route('vendor.stock-movements.create'))
}

function goEdit(id) {
  router.visit(route('vendor.stock-movements.edit', id))
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
  router.delete(route('vendor.stock-movements.destroy', id), {
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
  background: var(--slate-50);
  border: 1px solid var(--slate-200);
  color: var(--slate-600);
  font-size: 0.78rem;
  font-weight: 500;
}

:deep(.ingredient-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #c2410c;
  font-size: 0.78rem;
  font-weight: 500;
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

.x-small {
  font-size: 0.7rem;
}
</style>
