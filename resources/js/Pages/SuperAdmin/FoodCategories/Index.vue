<template>
  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Food Types</h1>
            <p class="header-subtitle">Manage marketplace food type filters with image, slug, and display order.</p>
          </div>
          <button v-if="can('food-types.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span>Create Food Type</span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[3, 'asc']]" searchPlaceholder="Search name or slug">
          <template #header>
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Slug</th>
              <th>Sort Order</th>
              <th>Status</th>
              <th>Created</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this food type?" cancel-label="Keep" confirm-label="Delete"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { onMounted, onUnmounted, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import { usePermission } from '@/composables/usePermission'

const page = usePage()

defineOptions({ layout: SuperAdminLayout })

const { can } = usePermission()
const tableId = 'foodCategoriesTable'
const datatableUrl = computed(() => route('food-categories.getdata'))
const dtRef = ref(null)

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

function goCreate() {
  router.visit(route('food-categories.create'))
}

function goEdit(id) {
  router.visit(route('food-categories.edit', id))
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

  router.delete(route('food-categories.destroy', id), {
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
    data: 'image_url',
    name: 'image_url',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const src = data || '/multivendor/product.webp'
      return `<img src="${escapeHtml(src)}" alt="${escapeHtml(row?.name || 'Food type')}" class="food-type-thumb" />`
    },
  },
  { data: 'name', name: 'name' },
  { data: 'slug', name: 'slug' },
  { data: 'sort_order', name: 'sort_order' },
  {
    data: 'is_active',
    name: 'is_active',
    render: (data) => data
      ? `<span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1"><i class="bi bi-check-circle-fill"></i>Active</span>`
      : `<span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1"><i class="bi bi-x-circle-fill text-danger"></i>Inactive</span>`,
  },
  { data: 'created_at', name: 'created_at' },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const id = row?.id ?? data
      const name = escapeHtml(row?.name ?? '')
      const editButton = `
    <button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit">
        <i class="bi bi-pencil-fill"></i>
    </button>`

      const deleteButton = `
    <button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" data-name="${name}" title="Delete">
        <i class="bi bi-trash3-fill"></i>
    </button>`

      return `
      <div class="d-flex gap-2 justify-content-end">
        ${can('food-types.edit') ? editButton : ''}
        ${can('food-types.delete') ? deleteButton : ''}
      </div>
    `
    },
  },
]))

const columnDefs = [
  { targets: 0, width: '82px', className: 'align-middle' },
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

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

:deep(.food-type-thumb) {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid var(--slate-200);
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