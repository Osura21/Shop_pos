<template>
  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">SEO Footer Links</h1>
            <p class="header-subtitle">Manage Browse By City country, location, food type, and order type links.</p>
          </div>
          <button v-if="can('seo-footer-links.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span>Create Link</span>
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
          :order="[[6, 'asc']]"
          searchPlaceholder="Search country, location, food type"
        >
          <template #header>
            <tr>
              <th>Country</th>
              <th>Location</th>
              <th>Link Text</th>
              <th>Food Type</th>
              <th>Order Type</th>
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

  <DeleteModal
    v-model:show="showDeleteModal"
    :target-id="deleteTarget.id"
    :target-name="deleteTarget.name"
    :loading="deleting"
    title="Delete this SEO footer link?"
    cancel-label="Keep"
    confirm-label="Delete"
    @confirm="confirmDelete"
  />
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'
import { usePermission } from '@/composables/usePermission'

defineOptions({ layout: SuperAdminLayout })

const page = usePage()
const { can } = usePermission()
const tableId = 'seoFooterLinksTable'
const datatableUrl = computed(() => route('seo-footer-links.getdata'))
const dtRef = ref(null)

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

function goCreate() {
  router.visit(route('seo-footer-links.create'))
}

function goEdit(id) {
  router.visit(route('seo-footer-links.edit', id))
}

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return

  deleting.value = true
  router.delete(route('seo-footer-links.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      setTimeout(() => dtRef.value?.reloadDatatable?.(), 300)
    },
    onFinish: () => {
      deleting.value = false
    },
  })
}

const columns = computed(() => ([
  { data: 'country', name: 'country' },
  { data: 'location', name: 'location' },
  { data: 'link_text', name: 'link_text' },
  { data: 'food_type', name: 'food_type' },
  {
    data: 'order_type',
    name: 'order_type',
    render: (data) => `<span class="pill">${escapeHtml(data)}</span>`,
  },
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
      const name = escapeHtml(`${row?.food_type || ''} in ${row?.location || ''}`)
      const editButton = `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>`
      const deleteButton = `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" data-name="${name}" title="Delete"><i class="bi bi-trash3-fill"></i></button>`

      return `<div class="d-flex gap-2 justify-content-end">
        ${can('seo-footer-links.edit') ? editButton : ''}
        ${can('seo-footer-links.delete') ? deleteButton : ''}
      </div>`
    },
  },
]))

const columnDefs = [
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

onMounted(bindActions)
onUnmounted(unbindActions)

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

:deep(.pill) {
  background: rgba(242, 140, 0, 0.1);
  border: 1px solid rgba(242, 140, 0, 0.18);
  border-radius: 999px;
  color: #c85a00;
  display: inline-flex;
  font-size: 12px;
  font-weight: 800;
  padding: 4px 10px;
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
