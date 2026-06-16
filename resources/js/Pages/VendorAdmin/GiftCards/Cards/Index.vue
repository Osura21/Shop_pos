<template>

  <Head title="Gift Cards" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Gift Cards</h1>
            <p class="header-subtitle">Manage issued cards, balances, status and expiry dates.</p>
          </div>

          <div class="d-flex gap-2">
            <button v-if="can('gift-cards.create')" type="button" class="btn-primary-modern" @click="goCreate">
              <i class="bi bi-plus" />
              <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                <span class="create-text">Create</span> Gift Card
              </span>
            </button>

            <button v-if="can('gift-card-batches.create')" type="button"
              class="btn-primary-modern btn-primary-modern--soft" @click="goBatch">
              <i class="bi bi-plus" />
              <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                <span class="create-text">Create</span> Gift Batch
              </span>
            </button>
          </div>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[createdAtColumnIndex, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th class="text-center">
                <input type="checkbox" class="form-check-input">
              </th>
              <th>Card Code</th>
              <th>Branch</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Initial Balance ({{ baseCurrencyCode }})</th>
              <th>Current Balance ({{ baseCurrencyCode }})</th>
              <th v-if="hasSecondaryCurrency">
                Initial Balance ({{ secondaryCurrencyCode }})
              </th>
              <th v-if="hasSecondaryCurrency">
                Current Balance ({{ secondaryCurrencyCode }})
              </th>
              <th>Expiry Date</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.code"
    :loading="deleting" title="Delete this gift card?" cancel-label="Keep Gift Card" confirm-label="Delete Gift Card"
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

const tableId = 'giftCardsTable'
const dtRef = ref(null)

const page = usePage()

const datatableUrl = computed(() => route('vendor.gift-cards.getdata'))

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, code: '' })
const deleting = ref(false)

const baseCurrencyCode = computed(() => {
  return page.props.currencySettings?.base_currency?.code || 'LKR'
})

const secondaryCurrencyCode = computed(() => {
  return page.props.currencySettings?.secondary_currency?.code || ''
})

const hasSecondaryCurrency = computed(() => !!secondaryCurrencyCode.value)

const createdAtColumnIndex = computed(() => {
  return hasSecondaryCurrency.value ? 10 : 8
})

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function moneyCell(currency, value) {
  if (value === null || value === undefined || value === '') {
    return `<span class="text-muted small">—</span>`
  }

  return `<span class="quantity-chip">${escapeHtml(currency)} ${escapeHtml(String(value))}</span>`
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
      data: 'code',
      name: 'code',
      render: (data, type, row) => `
        <div class="d-flex align-items-center">
          <div>
            <div class="fw-bold text-dark mb-0">
              ${escapeHtml(data || '')}
            </div>
            <div class="text-muted x-small">
              GC - ${row.id}
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
      render: (data) => `<span class="text-secondary small">${escapeHtml(data || '-')}</span>`,
    },
    {
      data: 'customer_name',
      name: 'customer_id',
      orderable: false,
      searchable: false,
      render: (data) => `<span class="text-secondary small">${escapeHtml(data || '-')}</span>`,
    },
    {
      data: 'status_badge',
      name: 'status',
      render: (data, type, row) => {
        if (data) return data

        return `<span class="status-chip">${escapeHtml(row?.status || '-')}</span>`
      },
    },
    {
      data: 'initial_balance',
      name: 'initial_balance',
      render: (data) => moneyCell(baseCurrencyCode.value, data),
    },
    {
      data: 'current_balance',
      name: 'current_balance',
      render: (data) => moneyCell(baseCurrencyCode.value, data),
    },
  ]

  if (hasSecondaryCurrency.value) {
    cols.push(
      {
        data: 'secondary_initial_balance',
        name: 'secondary_initial_balance',
        render: (data) => moneyCell(secondaryCurrencyCode.value, data),
      },
      {
        data: 'secondary_current_balance',
        name: 'secondary_current_balance',
        render: (data) => moneyCell(secondaryCurrencyCode.value, data),
      },
    )
  }

  cols.push(
    {
      data: 'expires_at',
      name: 'expires_at',
      render: (data) => `<span class="text-secondary small">${escapeHtml(data || '-')}</span>`,
    },
    {
      data: 'id',
      name: 'actions',
      orderable: false,
      searchable: false,
      render: (data, type, row) => {
        const code = escapeHtml(row?.code ?? '')

        return `
  <div class="d-flex gap-2 justify-content-end">
    ${can('gift-cards.edit')
            ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
            : ''
          }

    ${can('gift-cards.delete')
            ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${data}" data-code="${code}">
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
  router.visit(route('vendor.gift-cards.create'))
}

function goBatch() {
  router.visit(route('vendor.gift-cards.batches.create'))
}

function goEdit(id) {
  router.visit(route('vendor.gift-cards.edit', id))
}

function openDeleteModal(id, code = '') {
  deleteTarget.value = { id, code }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return

  deleting.value = true

  router.delete(route('vendor.gift-cards.destroy', id), {
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
    const code = event.currentTarget?.dataset?.code || ''
    if (id) openDeleteModal(id, code)
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
.btn-primary-modern--soft {
  background: #fff7ed;
  color: #f97316;
  border: 1px solid #fed7aa;
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

:deep(.form-check-input) {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.table-container-modern {
  padding: 0.5rem 1rem 1rem;
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

:deep(.badge) {
  font-size: 0.76rem;
  font-weight: 800;
  padding: 0.42rem 0.7rem;
}
</style>