<template>

  <Head title="Users" />

  <div class="page-container">
    <div class="card-modern">

      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Users</h1>
            <p class="header-subtitle">Manage tenant users and access control.</p>
          </div>
          <button v-if="can('users.create')" type="button" class="btn-primary-modern" @click="goToCreateRole">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">User</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[0, 'asc']]" searchPlaceholder="Search users...">
          <template #header>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Branch</th>
              <th>Role</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this user?" cancel-label="Keep User" confirm-label="Delete User"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { computed, onMounted, onUnmounted, watch, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from '@/composables/usePermission'

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const tableId = 'usersTable'
const dtRef = ref(null)

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

const datatableUrl = computed(() => route('vendor.users.getdata'))

function escapeHtml(value = '') {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

const columns = computed(() => [
  {
    data: 'name',
    name: 'name',
    render: (data) => `<span class="fw-bold text-dark">${escapeHtml(data || 'â€”')}</span>`
  },
  {
    data: 'username',
    name: 'username',
    render: (data) => `<span class="text-muted">${escapeHtml(data || 'â€”')}</span>`
  },
  {
    data: 'email',
    name: 'email',
    render: (data) => `<span class="text-muted">${escapeHtml(data || 'â€”')}</span>`
  },
  {
    data: 'branch',
    name: 'branch',
    render: (data) => data
      ? `<span class="branch-chip"><i class="bi bi-building"></i> ${escapeHtml(data)}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'role',
    name: 'role',
    render: (data) => data
      ? `<span class="role-chip">${escapeHtml(data)}</span>`
      : `<span class="text-muted small">â€”</span>`
  },
  {
    data: 'status',
    name: 'status',
    orderable: false,
    searchable: false,
    render: (data) => {
      const isActive = !!data
      const cls = isActive ? 'modern-badge active' : 'modern-badge inactive'
      const icon = isActive ? 'bi-check-lg' : 'bi-x-lg'
      const label = isActive ? 'Active' : 'Inactive'
      return `<span class="${cls}"><i class="bi ${icon}"></i> ${label}</span>`
    }
  },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const name = escapeHtml(row?.name || '')
      const isRestrictedRole = row?.role === 'Vendor Admin'
      const disabledAttr = isRestrictedRole ? 'disabled' : ''

      const editBtn = can('users.edit')
        ? `<button type="button"
              class="btn btn-circle js-edit"
              data-id="${data}"
              title="Edit"
              ${disabledAttr}>
              <i class="bi bi-pencil-fill"></i>
            </button>`
        : ''

      const deleteBtn = can('users.delete')
        ? `<button type="button"
              class="btn btn-circle btn-circle-danger js-delete"
              data-id="${data}"
              data-name="${name}"
              title="Delete"
              ${disabledAttr}>
              <i class="bi bi-trash3-fill"></i>
            </button>`
        : ''

      return `<div class="d-flex gap-2 justify-content-end">${editBtn}${deleteBtn}</div>`
    }
  },
])

const columnDefs = [
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

function goToCreateRole() {
  router.get(route('vendor.users.create'))
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
  router.delete(route('vendor.users.destroy', id), {
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
    if (id) router.visit(route('vendor.users.edit', id))
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
  color: var(--slate-600);
  font-size: 0.78rem;
  font-weight: 500;
}

:deep(.role-chip) {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  color: #6d28d9;
  font-size: 0.78rem;
  font-weight: 500;
}

:deep(.modern-badge) {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
}

:deep(.modern-badge.active) {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

:deep(.modern-badge.inactive) {
  background: rgba(239, 68, 68, 0.1);
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
