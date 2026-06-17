<template>

  <Head title="Purchases" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Purchases</h1>
            <p class="header-subtitle">Manage vendor purchase orders with base and secondary totals.</p>
          </div>
          <button v-if="can('purchases.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              <span class="create-text">Create</span> Purchases
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="defaultOrder" searchPlaceholder="Search purchases...">
          <template #header>
            <tr>
              <th>Reference No</th>
              <th>Branch</th>
              <th>Supplier</th>
              <th>Total ({{ baseCurrencyCode }})</th>
              <th v-if="hasSecondaryCurrency">Total ({{ secondaryCurrencyCode }})</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this purchase?" cancel-label="Keep Record" confirm-label="Delete Record"
    @confirm="confirmDelete" @closed="onModalClosed" />

  <ConfirmActionModal v-model:show="showReceiveModal" :target-id="receiveTarget.id" :target-name="receiveTarget.name"
    :loading="receiving" title="Mark purchase as received?" cancel-label="Cancel" confirm-label="Receive Stock"
    icon-class="bi-box-arrow-in-down" tone="orange" @confirm="confirmReceive" @closed="onReceiveModalClosed">
    <template #description>
      This will receive all remaining purchase quantities and add them to ingredient stock.
    </template>
  </ConfirmActionModal>

</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import ConfirmActionModal from '@/Components/ConfirmActionModal.vue'
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()
const tableId = 'purchaseTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.purchases.getdata'))

const baseCurrency = computed(() => page.props.currencySettings?.base_currency ?? null)
const secondaryCurrency = computed(() => page.props.currencySettings?.secondary_currency ?? null)
const hasSecondaryCurrency = computed(() => !!secondaryCurrency.value)
const baseCurrencyCode = computed(() => baseCurrency.value?.code || 'Base')
const secondaryCurrencyCode = computed(() => secondaryCurrency.value?.code || 'Secondary')

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)
const showReceiveModal = ref(false)
const receiveTarget = ref({ id: null, name: '' })
const receiving = ref(false)

const columns = computed(() => {
  const cols = [
    {
      data: 'reference_no',
      name: 'reference_no',
      render: (data, type, row) => `
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold text-dark mb-0">
            ${data}
          </div>
          <div class="text-muted x-small">
            PUR - ${row.id}
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
        ? `<span class="branch-chip"><i class="bi bi-building"></i> ${data}</span>`
        : `<span class="text-muted small">â€”</span>`
    },
    {
      data: 'supplier_name',
      name: 'supplier_id',
      orderable: false,
      searchable: false,
      render: (data) => data
        ? `<span class="supplier-chip"><i class="bi bi-truck"></i> ${data}</span>`
        : `<span class="text-muted small">â€”</span>`
    },
    {
      data: 'total',
      name: 'total',
      render: (data) => data !== null && data !== undefined
        ? `<span class="quantity-chip">${data}</span>`
        : `<span class="text-muted small">â€”</span>`
    },
  ]

  if (hasSecondaryCurrency.value) {
    cols.push({
      data: 'secondary_total',
      name: 'secondary_total',
      orderable: false,
      searchable: false,
      render: (data) => data !== null && data !== undefined
        ? `<span class="quantity-chip secondary">${data}</span>`
        : `<span class="text-muted small">â€”</span>`
    })
  }

  cols.push(
    {
      data: 'status_badge',
      name: 'status',
      orderable: false,
      searchable: false,
      render: (data) => data ?? `<span class="text-muted small">â€”</span>`,
    },
    {
      data: 'id',
      name: 'actions',
      orderable: false,
      searchable: false,
      render: (data, type, row) => {
        const name = String(row?.reference_no ?? '').replace(/"/g, '&quot;')
        return `
  <div class="d-flex gap-2 justify-content-end">
    ${can('purchases.view')
            ? `<button type="button" class="btn-circle js-show" data-id="${data}" title="View purchase">
            <i class="bi bi-eye-fill"></i>
          </button>`
            : ''
          }

    ${row?.status !== 'received' && can('purchases.edit')
            ? `<button type="button" class="btn-circle js-receive" data-id="${data}" data-name="${name}" title="Mark as received">
            <i class="bi bi-check2-circle"></i>
          </button>`
            : ''
          }

    ${row?.status !== 'received' && can('purchases.edit')
            ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
            : ''
          }

    ${row?.status === 'pending' && can('purchases.delete')
            ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${data}" data-name="${name}">
            <i class="bi bi-trash3-fill"></i>
          </button>`
            : ''
          }
  </div>
`
      }
    }
  )

  return cols
})

const defaultOrder = computed(() => [[hasSecondaryCurrency.value ? 7 : 6, 'desc']])

const columnDefs = computed(() => [
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
])

function goCreate() {
  router.visit(route('vendor.purchases.create'))
}

function goShow(id) {
  router.visit(route('vendor.purchases.show', id))
}

function goEdit(id) {
  router.visit(route('vendor.purchases.edit', id))
}

function markReceived(id, name = '') {
  receiveTarget.value = { id, name }
  showReceiveModal.value = true
}

function onReceiveModalClosed() {
  if (!receiving.value) {
    receiveTarget.value = { id: null, name: '' }
  }
}

function confirmReceive() {
  const id = receiveTarget.value?.id
  if (!id) return

  receiving.value = true
  router.post(route('vendor.purchases.mark-received', id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showReceiveModal.value = false
      setTimeout(() => {
        dtRef.value?.reloadDatatable?.()
      }, 300)
    },
    onError: (errors) => {
      const message = errors?.general || 'Unable to mark purchase as received.'
      alertError(message)
    },
    onFinish: () => {
      receiving.value = false
      if (!showReceiveModal.value) {
        receiveTarget.value = { id: null, name: '' }
      }
    },
  })
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
  router.delete(route('vendor.purchases.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      setTimeout(() => {
        dtRef.value?.reloadDatatable?.()
      }, 300)
    },
    onError: (errors) => {
      deleting.value = false
      const message = errors?.general || 'Something went wrong.'
      alertError(message)
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
  $(document).on('click', `${selector} .js-show`, (e) => {
    const id = e.currentTarget?.dataset?.id
    if (id) goShow(id)
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
  $(document).on('click', `${selector} .js-receive`, (e) => {
    const id = e.currentTarget?.dataset?.id
    const name = e.currentTarget?.dataset?.name || ''
    if (id) markReceived(id, name)
  })
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`
  $(document).off('click', `${selector} .js-show`)
  $(document).off('click', `${selector} .js-edit`)
  $(document).off('click', `${selector} .js-delete`)
  $(document).off('click', `${selector} .js-receive`)
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

:deep(.ref-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  color: #6d28d9;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: monospace;
}

:deep(.branch-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  text-wrap: nowrap;
  color: var(--slate-600);
  font-size: 0.8rem;
  font-weight: 500;
}

:deep(.supplier-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  text-wrap: nowrap;
  color: #c2410c;
  font-size: 0.8rem;
  font-weight: 500;
}

:deep(.quantity-chip) {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  text-wrap: nowrap;
  color: #2563eb;
  font-size: 0.8rem;
  font-weight: 700;
}

:deep(.quantity-chip.secondary) {
  background: #f0fdf4;
  color: #15803d;
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
