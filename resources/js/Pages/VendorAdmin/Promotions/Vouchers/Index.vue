<template>

  <Head title="Vouchers" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Vouchers</h1>
            <p class="header-subtitle">Manage all promotion vouchers for this tenant.</p>
          </div>

          <button v-if="can('promotion-vouchers.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Voucher</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[updatedAtColumnIndex, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th class="text-center" style="width: 44px;">
                <input type="checkbox" class="form-check-input">
              </th>
              <th>Name</th>
              <th>Branch</th>
              <th>Code</th>
              <th>Type</th>
              <th>Value ({{ baseCurrencyCode }})</th>
              <th v-if="hasSecondaryCurrency">
                Value ({{ secondaryCurrencyCode }})
              </th>
              <th>Used</th>
              <th>Activation</th>
              <th>Created At</th>
              <th>Updated At</th>
              <th class="text-end" style="width: 110px;">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this voucher?" cancel-label="Keep Voucher" confirm-label="Delete Voucher"
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

const tableId = 'promotionVouchersTable'
const dtRef = ref(null)

const datatableUrl = computed(() => route('vendor.promotions.vouchers.getdata'))

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

const baseCurrencyCode = computed(() => {
  return page.props.currencySettings?.base_currency?.code || 'LKR'
})

const secondaryCurrencyCode = computed(() => {
  return page.props.currencySettings?.secondary_currency?.code || ''
})

const hasSecondaryCurrency = computed(() => !!secondaryCurrencyCode.value)

const updatedAtColumnIndex = computed(() => {
  return hasSecondaryCurrency.value ? 10 : 9
})

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function renderValue(data, row) {
  if (row?.type === 'percentage') {
    return `<span class="quantity-chip">${escapeHtml(String(data))}</span>`
  }

  return data
    ? `<span class="quantity-chip">${baseCurrencyCode.value} ${escapeHtml(String(data))}</span>`
    : `<span class="text-muted small">—</span>`
}

function renderSecondaryValue(data, row) {
  if (row?.type === 'percentage') {
    return `<span class="text-muted small">—</span>`
  }

  return data
    ? `<span class="quantity-chip">${secondaryCurrencyCode.value} ${escapeHtml(String(data))}</span>`
    : `<span class="text-muted small">—</span>`
}

const columns = computed(() => {
  const cols = [
    {
      data: 'id',
      name: 'checkbox',
      orderable: false,
      searchable: false,
      render: (data) => `
        <div class="text-center">
          <input type="checkbox" class="form-check-input js-select-row" value="${data}">
        </div>
      `,
    },
    {
      data: 'name',
      name: 'name',
      render: (data, type, row) => `
        <div class="d-flex align-items-center">
          <div>
            <div class="fw-bold text-dark mb-0">
              ${escapeHtml(data || '')}
            </div>
            <div class="text-muted x-small">
              VC - ${row.id}
            </div>
          </div>
        </div>
      `,
    },
    {
      data: 'branch_name',
      name: 'branch_id',
      orderable: false,
      searchable: false,
      render: (data) => `
        <span class="text-secondary small">${escapeHtml(data || '-')}</span>
      `,
    },
    {
      data: 'code',
      name: 'code',
      render: (data) => `
        <span class="code-chip">${escapeHtml(data || '-')}</span>
      `,
    },
    {
      data: 'type_badge',
      name: 'type',
      render: (data) => data,
    },
    {
      data: 'value',
      name: 'value',
      render: (data, type, row) => renderValue(data, row),
    },
  ]

  if (hasSecondaryCurrency.value) {
    cols.push({
      data: 'secondary_value',
      name: 'secondary_value',
      render: (data, type, row) => renderSecondaryValue(data, row),
    })
  }

  cols.push(
    {
      data: 'used_count',
      name: 'used_count',
      render: (data) => `
        <span class="used-chip">${escapeHtml(String(data ?? 0))}</span>
      `,
    },
    {
      data: 'activation_badge',
      name: 'is_active',
      orderable: false,
      searchable: false,
      render: (data, type, row) => {
        if (data) return data

        const active = Number(row?.is_active) === 1

        return active
          ? `<span class="status-chip status-chip--active"><i class="bi bi-check-lg"></i> Active</span>`
          : `<span class="status-chip status-chip--inactive"><i class="bi bi-x-lg"></i> Inactive</span>`
      },
    },
    {
      data: 'created_at',
      name: 'created_at',
      render: (data) => `<span class="text-secondary small">${escapeHtml(data || '-')}</span>`,
    },
    {
      data: 'updated_at',
      name: 'updated_at',
      render: (data) => `<span class="text-secondary small">${escapeHtml(data || '-')}</span>`,
    },
    {
      data: 'id',
      name: 'actions',
      orderable: false,
      searchable: false,
      render: (data, type, row) => {
        const name = escapeHtml(row?.name ?? '')

        return `
  <div class="d-flex gap-2 justify-content-end">
    ${can('promotion-vouchers.edit')
            ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
            : ''
          }

    ${can('promotion-vouchers.delete')
            ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${data}" data-name="${name}">
            <i class="bi bi-trash3-fill"></i>
          </button>`
            : ''
          }
  </div>
`
      }
    },
  )

  return cols
})

const columnDefs = [
  { targets: 0, className: 'text-center align-middle', width: '44px' },
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

function goCreate() {
  router.visit(route('vendor.promotions.vouchers.create'))
}

function goEdit(id) {
  router.visit(route('vendor.promotions.vouchers.edit', id))
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

  router.delete(route('vendor.promotions.vouchers.destroy', id), {
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

  $(document).on('click', `${selector} .js-edit`, (event) => {
    const id = event.currentTarget?.dataset?.id
    if (id) goEdit(id)
  })

  $(document).on('click', `${selector} .js-delete`, (event) => {
    const id = event.currentTarget?.dataset?.id
    const name = event.currentTarget?.dataset?.name || ''
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

:deep(.form-check-input) {
  width: 18px;
  height: 18px;
  cursor: pointer;
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

:deep(.used-chip) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  padding: 0.35rem 0.65rem;
  border-radius: 8px;
  color: #0f766e;
  background: #f0fdfa;
  font-size: 0.78rem;
  font-weight: 800;
}

:deep(.code-chip) {
  display: inline-flex;
  align-items: center;
  border-radius: 8px;
  padding: 0.35rem 0.7rem;
  background: #fff7ed;
  color: #c2410c;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
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

:deep(.badge) {
  font-size: 0.76rem;
  font-weight: 800;
  padding: 0.42rem 0.7rem;
}
</style>