<template>

  <Head title="Customers" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Customers</h1>
            <p class="header-subtitle">Manage customer profiles, credentials, and business details.</p>
          </div>
          <button v-if="can('customers.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Customer</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[1, 'desc']]" searchPlaceholder="Search customer name, phone, email, username">
          <template #header>
            <tr>
              <th>Avatar</th>
              <th>Name</th>
              <th>Type</th>
              <th>Phone</th>
              <th>E-mail</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this customer?" cancel-label="Keep Customer" confirm-label="Delete Customer"
    @confirm="confirmDelete" @closed="onModalClosed" />

  <CustomerDetailsOffcanvas
    :show="showCustomerDrawer"
    :customer-id="selectedCustomerId"
    mode="customer-page"
    currency-code="LKR"
    @close="closeCustomerDrawer"
  />

</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import CustomerDetailsOffcanvas from '@/Pages/VendorAdmin/POS/CustomerDetailsOffcanvas.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()
const tableId = 'customerTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.customers.getdata'))

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function initials(name = '') {
  return String(name)
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('') || 'C'
}

const columns = computed(() => ([
  {
    data: 'avatar_url',
    name: 'avatar',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      if (row?.avatar_url) {
        return `
          <div class="customer-avatar">
            <img src="${row.avatar_url}" alt="${escapeHtml(row?.name || 'Customer')}" />
          </div>
        `
      }
      return `
        <div class="customer-avatar customer-avatar--placeholder">
          ${escapeHtml(initials(row?.name))}
        </div>
      `
    },
  },
  {
    data: 'name',
    name: 'name',
    render: (data, type, row) => {
      const username = row?.username ? `@${escapeHtml(row.username)}` : '-'
      return `
        <div>
          <div class="fw-bold text-dark mb-0">${escapeHtml(row?.name || '-')}</div>
          <div class="text-muted x-small">${username}</div>
        </div>
      `
    },
  },
  { data: 'customer_type_label', name: 'customer_type' },
  { data: 'phone', name: 'phone' },
  { data: 'email', name: 'email' },
  {
    data: 'status',
    name: 'is_active',
    orderable: false,
    searchable: false,
    render: (data) => {
      const active = String(data).toLowerCase().includes('active')
      return `<span class="modern-badge ${active ? 'active' : 'inactive'}">${data}</span>`
    }
  },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const name = escapeHtml(row?.name || '')
      return `
  <div class="d-flex gap-2 justify-content-end">
    <button type="button" class="btn-circle js-view" data-id="${data}">
      <i class="bi bi-eye"></i>
    </button>
    ${can('customers.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
          : ''
        }

    ${can('customers.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${data}" data-name="${name}">
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
  { targets: 0, width: '70px', className: 'text-center align-middle' },
  { targets: 1, width: '220px', className: 'align-middle' },
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

function goCreate() {
  router.visit(route('vendor.customers.create'))
}

function goEdit(id) {
  router.visit(route('vendor.customers.edit', id))
}

const showCustomerDrawer = ref(false)
const selectedCustomerId = ref(null)

function openCustomerDrawer(id) {
  selectedCustomerId.value = id
  showCustomerDrawer.value = true
}

function closeCustomerDrawer() {
  showCustomerDrawer.value = false
  selectedCustomerId.value = null
}

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.customers.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      deleteTarget.value = { id: null, name: '' }
      setTimeout(() => {
        dtRef.value?.reloadDatatable?.()
      }, 300)
    },
    onError: () => {
      deleting.value = false
    },
    onFinish: () => { deleting.value = false },
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
  $(document).on('click', `${selector} .js-view`, (e) => {
    const id = e.currentTarget?.dataset?.id
    if (id) openCustomerDrawer(id)
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
  $(document).off('click', `${selector} .js-view`)
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
    if (flash?.message) {
      alertSuccess(flash.message)
    }
    if (flash?.error) {
      alertError(flash.error)
    }
  },
  { immediate: true }
)
</script>

<style scoped>
/* Table */
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

/* Avatar */
:deep(.customer-avatar) {
  width: 40px;
  height: 40px;
  min-width: 40px;
  border-radius: 10px;
  overflow: hidden;
  margin: 0 auto;
  background: var(--primary-soft);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
  color: var(--primary);
}

:deep(.customer-avatar img) {
  width: 40px;
  height: 40px;
  object-fit: cover;
  display: block;
}

:deep(.customer-avatar--placeholder) {
  border: 1px solid rgba(59, 130, 246, 0.2);
}

/* Badges */
.modern-badge {
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
}

.modern-badge.active {
  background: #ecfdf5;
  color: #059669;
}

.modern-badge.inactive {
  background: #f1f5f9;
  color: #64748b;
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
