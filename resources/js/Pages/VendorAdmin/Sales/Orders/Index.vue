<template>
  <Head title="Orders" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Orders</h1>
            <p class="header-subtitle">
              Shop POS order history with customer, register, payment and sales details.
            </p>
          </div>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable
          ref="dtRef"
          :id="tableId"
          :url="datatableUrl"
          :columns="columns"
          :columnDefs="columnDefs"
          :order="[[5, 'desc']]"
          searchPlaceholder="Search by customer, reference, branch or status..."
        >
          <template #header>
            <tr>
              <th>Customer</th>
              <th>Reference No</th>
              <th>Branch</th>
              <th>Payment</th>
              <th>Total</th>
              <th>Created</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

      <!-- <Transition name="fade">
        <div
          v-if="actionMenu.visible"
          class="order-action-menu"
          :style="actionMenuStyle"
        >
          <button type="button" class="menu-item" @click="showSelectedOrder">
            <i class="bi bi-eye"></i>
            <span>Show Order</span>
          </button>

         

          <button
            type="button"
            class="menu-item danger"
            :disabled="!selectedRow?.can_cancel"
            @click="openCancelModalFromMenu"
          >
            <i class="bi bi-x-lg"></i>
            <span>Cancel Order</span>
          </button>
        </div>
      </Transition> -->
    </div>

    <Transition name="modal-fade">
      <div
        v-if="cancelModal.visible"
        class="modal-backdrop-custom"
        @click.self="closeCancelModal"
      >
        <div class="cancel-modal-card">
          <div class="cancel-modal-header">
            <h3>Cancel Order</h3>
            <p>
              You are about to cancel this order. Please provide a reason for cancellation.
              No payment will be refunded unless the order was already paid.
            </p>
          </div>

          <div
            v-if="cancelErrors.general"
            class="form-error-box"
          >
            {{ cancelErrors.general }}
          </div>

          <div class="cancel-grid">
            <div class="field-group">
              <label>POS Register</label>

              <SelectInput :class="{ invalid: cancelErrors.register_id }" id="pos_register_id" v-model="cancelForm.register_id" :options="registers.map(r => ({
                      ...r,
                      label: `${r.name} (${r.branch_name})`
                    }))" valueKey="id" labelKey="label" placeholder="Pos Register" />

              <div v-if="cancelErrors.register_id" class="error-text">
                {{ cancelErrors.register_id }}
              </div>
            </div>

            <div class="field-group">
              <label>Reason</label>

              <SelectInput :class="{ invalid: cancelErrors.reason_id }" id="pos_reason_id" v-model="cancelForm.reason_id" :options="cancelReasons.map(r => ({
                      ...r,
                      label: `${r.name}`
                    }))" valueKey="id" labelKey="label" placeholder="Reason" />

              <div v-if="cancelErrors.reason_id" class="error-text">
                {{ cancelErrors.reason_id }}
              </div>
            </div>
          </div>

          <div class="field-group mt-3">
            <textarea
              v-model="cancelForm.note"
              class="note-textarea"
              :class="{ invalid: cancelErrors.note }"
              rows="5"
              placeholder="Note"
            ></textarea>

            <div v-if="cancelErrors.note" class="error-text">
              {{ cancelErrors.note }}
            </div>
          </div>

          <div class="cancel-modal-footer">
            <button
              type="button"
              class="btn-link-soft"
              :disabled="cancelSubmitting"
              @click="closeCancelModal"
            >
              Cancel
            </button>

            <button
              type="button"
              class="btn-submit-soft"
              :disabled="cancelSubmitting"
              @click="submitCancelOrder"
            >
              {{ cancelSubmitting ? 'Submitting...' : 'Submit' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import SelectInput from '@/Components/SelectInput.vue';

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  registers: {
    type: Array,
    default: () => [],
  },
  cancelReasons: {
    type: Array,
    default: () => [],
  },
})

const tableId = 'salesOrdersTable'
const dtRef = ref(null)
const rowCache = new Map()

const datatableUrl = computed(() => route('vendor.sales.orders.getdata'))

const actionMenu = ref({
  visible: false,
  x: 0,
  y: 0,
  rowId: null,
})

const actionMenuStyle = computed(() => ({
  left: `${actionMenu.value.x}px`,
  top: `${actionMenu.value.y}px`,
}))

const selectedRow = computed(() => {
  if (!actionMenu.value.rowId) return null
  return rowCache.get(String(actionMenu.value.rowId)) || null
})

const cancelModal = ref({
  visible: false,
  row: null,
})

const cancelForm = ref({
  register_id: '',
  reason_id: '',
  note: '',
})

const cancelErrors = ref({})
const cancelSubmitting = ref(false)

const registers = computed(() => props.registers || [])
const cancelReasons = computed(() => props.cancelReasons || [])

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function cacheRow(row, data) {
  const id = String(row?.id ?? data)
  rowCache.set(id, { ...row })
  return id
}

function goShow(id) {
  if (!id) return
  router.visit(route('vendor.sales.orders.show', id))
}

function showSelectedOrder() {
  const id = selectedRow.value?.id
  closeActionMenu()
  goShow(id)
}

function printSelectedOrder() {
  const id = selectedRow.value?.id
  closeActionMenu()

  if (!id) return

  window.open(route('vendor.sales.orders.show', id), '_blank')
}

function openCancelModalFromMenu() {
  const row = selectedRow.value
  closeActionMenu()

  if (!row || !row.can_cancel) return

  openCancelModal(row)
}

function openCancelModal(row) {
  cancelErrors.value = {}

  cancelModal.value = {
    visible: true,
    row,
  }

  cancelForm.value = {
    register_id: row.register_id || '',
    reason_id: '',
    note: '',
  }
}

function closeCancelModal(force = false) {
  if (cancelSubmitting.value && !force) return

  cancelModal.value = {
    visible: false,
    row: null,
  }

  cancelForm.value = {
    register_id: '',
    reason_id: '',
    note: '',
  }

  cancelErrors.value = {}
}
function submitCancelOrder() {
  const row = cancelModal.value.row

  if (!row?.id) return

  cancelSubmitting.value = true
  cancelErrors.value = {}

  router.patch(route('vendor.sales.orders.cancel', row.id), {
    register_id: cancelForm.value.register_id,
    reason_id: cancelForm.value.reason_id,
    note: cancelForm.value.note,
  }, {
    preserveScroll: true,

    onSuccess: () => {
      cancelSubmitting.value = false
      closeCancelModal(true)
      dtRef.value?.reloadDatatable?.()
    },

    onError: (errors) => {
      cancelErrors.value = errors || {}
    },

    onFinish: () => {
      cancelSubmitting.value = false
    },
  })
}

function openActionMenu(row, element) {
  const rect = element.getBoundingClientRect()
  const width = 190

  actionMenu.value = {
    visible: true,
    x: Math.max(12, rect.right - width),
    y: rect.bottom + 8,
    rowId: row.id,
  }
}

function closeActionMenu() {
  actionMenu.value.visible = false
}

const columns = computed(() => ([
  {
    data: 'customer_display',
    name: 'customer_name',
    render: (data, type, row) => `
      <div class="d-flex align-items-center">
        <div class="order-avatar">
          <i class="bi bi-person"></i>
        </div>
        <div class="ms-2">
          <div class="fw-bold text-dark text-nowrap mb-0">${escapeHtml(data || 'Walk-In Customer')}</div>
          <div class="text-muted x-small">Order #${escapeHtml(row?.id)}</div>
        </div>
      </div>
    `,
  },
  {
    data: 'reference_no',
    name: 'uuid',
    render: (data) => `
      <span class="reference-pill">
        ${escapeHtml(data)}
      </span>
    `,
  },
  {
    data: 'branch_name',
    name: 'register.branch.name',
    orderable: false,
    render: (data, type, row) => `
      <div>
        <div class="fw-semibold text-dark">${escapeHtml(data || '-')}</div>
        <div class="text-muted x-small">${escapeHtml(row?.register_name || 'No register')}</div>
      </div>
    `,
  },
  {
    data: 'payment_badge',
    name: 'payment_status',
    render: (data) => data || '-',
  },
  {
    data: 'total_display',
    name: 'grand_total',
    render: (data) => `
      <span class="fw-bold text-dark">${escapeHtml(data)}</span>
    `,
  },
  {
    data: 'created_at',
    name: 'created_at',
    render: (data) => `<span class="text-secondary small">${escapeHtml(data)}</span>`,
  },
  {
    data: 'id',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const id = cacheRow(row, data)

      return `
        <div class="d-flex gap-2 justify-content-end">

    <button 
      type="button" 
      class="btn-circle js-edit" 
      data-id="${escapeHtml(data)}" 
      title="Edit"
    >
      <i class="bi bi-eye-fill"></i>
    </button>

    <button 
      type="button" 
      class="btn-circle btn-circle-danger js-delete" 
      data-id="${escapeHtml(data)}" 
      data-name="${escapeHtml(name ?? '')}" 
      title="Delete"
    >
      <i class="bi bi-x-lg"></i>
    </button>

  </div>
      `
    },
  },
]))

const columnDefs = [
  { targets: -1, width: '90px' },
  { targets: '_all', className: 'align-middle' },
]

function handleDocumentClick(event) {

  const editBtn = event.target.closest('.js-edit')
  if (editBtn) {
    const id = editBtn.dataset.id
    goShow(id)
    return
  }

  const deleteBtn = event.target.closest('.js-delete')
  if (deleteBtn) {
    const id = deleteBtn.dataset.id

    const row = rowCache.get(String(id))

    if (!row) return

    if (!row.can_cancel) return

    openCancelModal(row)
    return
  }

  if (
    !event.target.closest('.order-action-menu') &&
    !event.target.closest('.cancel-modal-card')
  ) {
    closeActionMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick)
})
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

:deep(tbody tr) {
  transition: background 0.18s ease, transform 0.18s ease;
}

:deep(tbody tr:hover) {
  background: #fffaf3;
}

:deep(.order-avatar) {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fff3e0 0%, #fff8ef 100%);
  border: 1px solid #fed7aa;
  color: #f97316;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
}

.pms-sync-status {
  margin-top: 0.65rem;
  width: fit-content;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.7rem;
  border-radius: 999px;
  background: #e6f7ff;
  color: #0f8fcf;
  font-size: 0.76rem;
  font-weight: 800;
  box-shadow: 0 10px 24px rgba(15, 143, 207, 0.12);
}

.pms-sync-status i {
  animation: sales-pms-sync-spin 0.8s linear infinite;
}

@keyframes sales-pms-sync-spin {
  to {
    transform: rotate(360deg);
  }
}

:deep(.reference-pill) {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 0.75rem;
  border-radius: 10px;
  background: #fff8ef;
  border: 1px solid #fde3bd;
  text-wrap: nowrap;
  color: #334155;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

:deep(.btn-circle) {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #dbe5f0;
  background: #ffffff;
  color: #64748b;
  transition: all 0.18s ease;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
}

:deep(.btn-circle:hover) {
  border-color: #fdba74;
  color: #ea580c;
  background: #fff7ed;
  transform: translateY(-1px);
}

:deep(.btn-more) {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 0;
  background: #e5e7eb;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

:deep(.btn-more:hover) {
  background: #d1d5db;
  color: #111827;
}

:deep(.x-small) {
  font-size: 0.72rem;
}

.order-action-menu {
  position: fixed;
  z-index: 1100;
  width: 190px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 2px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
  padding: 8px 0;
}

.menu-item {
  width: 100%;
  min-height: 42px;
  border: 0;
  background: transparent;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 16px;
  color: #475569;
  font-size: 14px;
  text-align: left;
  cursor: pointer;
}

.menu-item i {
  width: 18px;
  color: #0ea5e9;
}

.menu-item:hover {
  background: #f8fafc;
}

.menu-item.danger i,
.menu-item.danger {
  color: #ef4444;
}

.menu-item:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(15, 23, 42, 0.48);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.cancel-modal-card {
  width: min(100%, 600px);
  background: #ffffff;
  border-radius: 6px;
  padding: 24px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25);
}

.cancel-modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #334155;
}

.cancel-modal-header p {
  margin: 6px 0 20px;
  color: #6b7280;
  font-size: 14px;
  line-height: 1.55;
}

.cancel-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.field-group {
  position: relative;
}

.field-group label {
  position: absolute;
  top: -8px;
  left: 12px;
  background: #ffffff;
  padding: 0 6px;
  font-size: 10px;
  color: #ff7a00;
  z-index: 1;
}

.form-select-custom,
.note-textarea {
  width: 100%;
  border: 1px solid #d8dee6;
  border-radius: 6px;
  color: #475467;
  font-size: 14px;
  outline: none;
  background: #ffffff;
}

.form-select-custom {
  height: 38px;
  padding: 0 14px;
}

.note-textarea {
  min-height: 110px;
  resize: vertical;
  padding: 12px 14px;
}

.form-select-custom:focus,
.note-textarea:focus {
  border-color: #ff7a00;
  box-shadow: 0 0 0 1px #ff7a00;
}

.form-select-custom.invalid,
.note-textarea.invalid {
  border-color: #ef4444;
}

.error-text {
  margin-top: 5px;
  font-size: 12px;
  color: #ef4444;
}

.form-error-box {
  margin-bottom: 14px;
  padding: 10px 12px;
  background: #fff1f2;
  border: 1px solid #fecdd3;
  color: #be123c;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 700;
}

.mt-3 {
  margin-top: 24px;
}

.cancel-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 24px;
  margin-top: 28px;
}

.btn-link-soft,
.btn-submit-soft {
  border: 0;
  background: transparent;
  font-size: 14px;
  cursor: pointer;
}

.btn-link-soft {
  color: #6b7280;
}

.btn-submit-soft {
  color: #f59e0b;
  font-weight: 700;
}

.btn-link-soft:disabled,
.btn-submit-soft:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.fade-enter-active,
.fade-leave-active,
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to,
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@media (max-width: 760px) {
  .table-container-modern {
    padding: 0.5rem 0.75rem 1rem;
  }

  .cancel-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .cancel-modal-footer {
    gap: 16px;
  }
}
</style>
