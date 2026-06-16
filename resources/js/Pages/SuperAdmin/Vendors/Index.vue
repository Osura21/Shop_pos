<template>
  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Vendors</h1>
            <p class="header-subtitle">Manage vendor accounts, domains, themes, and status.</p>
          </div>
          <button v-if="can('vendor.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Vendor</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[4, 'desc']]" searchPlaceholder="Search name, slug, or domain">
          <template #header>
            <tr>
              <th>Name</th>
              <th>Slug</th>
              <th>Domain</th>
              <th>Theme</th>
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
    :loading="deleting" title="Delete this vendor?" cancel-label="Keep Vendor" confirm-label="Delete Vendor"
    @confirm="confirmDelete" @closed="onModalClosed" />

  <ViewOffcanvas
    :open="viewOpen"
    :vendor="viewVendor"
    :loading="viewLoading"
    @close="closeViewOffcanvas"
    @vendor-updated="handleVendorUpdated"
  />

</template>

<script setup>
import { onMounted, onUnmounted, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import ViewOffcanvas from './ViewOffcanvas.vue'
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

const page = usePage()

defineOptions({ layout: SuperAdminLayout })

const tableId = 'vendorsTable'
const datatableUrl = computed(() => route('vendors.getdata'))
const dtRef = ref(null)

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)
const viewOpen = ref(false)
const viewVendor = ref(null)
const viewLoading = ref(false)

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return

  deleting.value = true

  router.delete(route('vendors.destroy', id), {
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

function goCreate() {
  router.visit(route('vendors.create'))
}

function goEdit(id) {
  router.visit(route('vendors.edit', id))
}

function closeViewOffcanvas() {
  viewOpen.value = false
  setTimeout(() => {
    if (!viewOpen.value) viewVendor.value = null
  }, 260)
}

async function openViewOffcanvas(id, row = {}) {
  if (!id) return

  viewOpen.value = true
  viewLoading.value = true
  viewVendor.value = {
    id,
    name: row?.name || 'Vendor',
    slug: row?.slug || '',
    status: row?.status || 'active',
    primary_domain: row?.domain || '',
    theme: row?.theme || '',
    created_at: row?.created_at || '',
  }

  try {
    const response = await fetch(route('vendors.show', id), {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    const data = await response.json()

    if (!response.ok || !data?.vendor) {
      throw new Error(data?.message || 'Unable to load vendor details.')
    }

    viewVendor.value = data.vendor
  } catch (err) {
    alertError(err.message || 'Unable to load vendor details.')
    closeViewOffcanvas()
  } finally {
    viewLoading.value = false
  }
}

function handleVendorUpdated(vendor) {
  viewVendor.value = vendor
  dtRef.value?.reloadDatatable?.()
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
  { data: 'name', name: 'name' },
  { data: 'slug', name: 'slug' },
  { data: 'domain', name: 'domain', orderable: false },
  { data: 'theme', name: 'theme', orderable: false },
  {
    data: 'status',
    name: 'status',
    orderable: false,
    render: (data) => {
      const s = String(data || 'active').toLowerCase()
      const isActive = s === 'active'

      if (isActive) {
        return `
          <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-check-circle-fill"></i>
            Active
          </span>
        `
      }

      return `
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
          <i class="bi bi-x-circle-fill text-danger"></i>
          Inactive
        </span>
      `
    },
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

      const isProtected = false
      const disabledAttr = isProtected ? 'disabled aria-disabled="true"' : ''

      return `
        <div class="d-flex gap-2 justify-content-end">
          <button type="button" class="btn-circle js-view" data-id="${id}" data-row="${escapeHtml(JSON.stringify(row || {}))}" title="View">
            <i class="bi bi-eye-fill"></i>
          </button>
          <button type="button" class="btn-circle btn-circle-impersonate js-impersonate" data-id="${id}" title="Impersonate vendor admin">
            <i class="bi bi-box-arrow-up-right"></i>
          </button>
          ${can('vendor.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit" ${disabledAttr}>
            <i class="bi bi-pencil-fill"></i>
          </button>`
          : ''
        }
          ${can('vendor.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" data-name="${name}" title="Delete" ${disabledAttr}>
            <i class="bi bi-trash3-fill"></i>
          </button>`
          : ''
        }
        </div>
      `
    },
  },
]))

const columnDefs = [
  { targets: -1, className: 'text-end align-middle', width: '190px' },
  { targets: '_all', className: 'align-middle' },
]

function bindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`

  $(document).on('click', `${selector} .js-view`, (e) => {
    const id = e.currentTarget?.dataset?.id
    let row = {}

    try {
      row = JSON.parse(e.currentTarget?.dataset?.row || '{}')
    } catch (_) {
      row = {}
    }

    if (id) openViewOffcanvas(id, row)
  })

  $(document).on('click', `${selector} .js-impersonate`, (e) => {
    const id = e.currentTarget?.dataset?.id
    if (id) window.open(route('vendors.impersonate', id), '_blank', 'noopener,noreferrer')
  })
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
  $(document).off('click', `${selector} .js-view`)
  $(document).off('click', `${selector} .js-impersonate`)
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

:deep(.btn-circle-impersonate:hover) {
  border-color: #f97316;
  color: #f97316;
  background: #fff7ed;
}

:deep(.btn-circle-danger:hover) {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

:deep(.btn-circle:disabled),
:deep(.btn-circle[disabled]) {
  opacity: 0.35;
  cursor: not-allowed;
  pointer-events: none;
}
</style>
