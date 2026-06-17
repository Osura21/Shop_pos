<template>
  <Head title="Reasons" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Reasons</h1>
            <p class="header-subtitle">
              Manage refund, cancellation, payment and other POS reason records.
            </p>
          </div>

          <button
            v-if="can('sales-reasons.create')"
            type="button"
            class="btn-primary-modern"
            @click="goCreate"
          >
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Reason</span>
            </span>
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
          :order="[[3, 'desc']]"
          searchPlaceholder="Search by name or type..."
        >
          <template #header>
            <tr>
              <th>Name</th>
              <th>Type</th>
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

  <DeleteModal
    v-model:show="showDeleteModal"
    :target-id="deleteTarget.id"
    :target-name="deleteTarget.name"
    :loading="deleting"
    title="Delete this reason?"
    cancel-label="Keep Reason"
    confirm-label="Delete Reason"
    @confirm="confirmDelete"
    @closed="onModalClosed"
  />

</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

const page = usePage()
defineOptions({ layout: VendorAdminLayout })

const tableId = 'salesReasonsTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.sales.reasons.getdata'))

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function goCreate() {
  router.visit(route('vendor.sales.reasons.create'))
}

function goEdit(id) {
  if (!id) return
  router.visit(route('vendor.sales.reasons.edit', id))
}

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() {}

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return

  deleting.value = true

  router.delete(route('vendor.sales.reasons.destroy', id), {
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

const columns = computed(() => ([
  {
    data: 'name',
    name: 'name',
    render: (data, type, row) => `
      <div>
        <div class="fw-bold text-dark mb-0">${escapeHtml(data || '-')}</div>
        <div class="text-muted x-small">RSN - ${escapeHtml(row?.id)}</div>
      </div>
    `,
  },
  {
    data: 'type_badge',
    name: 'type',
    render: (data) => data || '-',
  },
  {
    data: 'activation_badge',
    name: 'is_active',
    render: (data) => data || '-',
  },
  {
    data: 'created_at',
    name: 'created_at',
    render: (data) => `<span class="text-secondary small">${escapeHtml(data)}</span>`,
  },
  {
    data: 'updated_at',
    name: 'updated_at',
    render: (data) => `<span class="text-secondary small">${escapeHtml(data)}</span>`,
  },
  {
    data: 'id',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const name = escapeHtml(row?.name || '')
      const id = escapeHtml(data)

      return `
      <div class="d-flex gap-2 justify-content-end">
        ${can('sales-reasons.edit')
          ? `<button type="button" class="btn-circle js-edit-reason" data-id="${id}" title="Edit reason">
               <i class="bi bi-pencil-fill"></i>
             </button>`
          : ''
        }
        ${can('sales-reasons.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete-reason" data-id="${id}" data-name="${name}" title="Delete reason">
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
  { targets: -1, width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

function handleTableClick(event) {
  const editButton = event.target.closest(`#${tableId} .js-edit-reason`)
  if (editButton) {
    goEdit(editButton.dataset.id)
    return
  }

  const deleteButton = event.target.closest(`#${tableId} .js-delete-reason`)
  if (deleteButton) {
    openDeleteModal(deleteButton.dataset.id, deleteButton.dataset.name)
  }
}

onMounted(() => {
  document.addEventListener('click', handleTableClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleTableClick)
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

:deep(.x-small) {
  font-size: 0.72rem;
}

@media (max-width: 760px) {
  .table-container-modern {
    padding: 0.5rem 0.75rem 1rem;
  }
}
</style>
