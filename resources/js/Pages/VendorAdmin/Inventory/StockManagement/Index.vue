<template>
  <Head title="Stock Management" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Stock Management</h1>
            <p class="header-subtitle">Record product stock in, stock out, and wastage for shop inventory.</p>
          </div>
          <button v-if="can('products.edit')" type="button" class="btn-primary-modern" @click="openMovementModal">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Add <span class="create-text">Stock Record</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[8, 'desc']]" searchPlaceholder="Search product, barcode, note...">
          <template #header>
            <tr>
              <th>Branch</th>
              <th>Product</th>
              <th>Barcode</th>
              <th>Type</th>
              <th>Quantity</th>
              <th>Before</th>
              <th>After</th>
              <th>Note</th>
              <th>Created</th>
            </tr>
          </template>
        </DataTable>
      </div>
    </div>
  </div>

  <div v-if="movementModalOpen" class="stock-modal-backdrop" @click.self="closeMovementModal">
    <form class="stock-modal" @submit.prevent="submitMovement">
      <div class="stock-modal__header">
        <div>
          <h3>Add Stock Record</h3>
          <p>Use this for stock in, manual stock out, and wastage/spoil records.</p>
        </div>
        <button type="button" class="stock-modal__close" @click="closeMovementModal">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="stock-modal__grid">
        <SelectInput
          id="stock-branch"
          v-model="movementForm.branch_id"
          label="Branch"
          :options="branchOptions"
          placeholder="All branches"
        />

        <SelectInput
          id="stock-product"
          v-model="movementForm.product_id"
          label="Product"
          :options="productOptions"
          placeholder="Select product"
          :clearable="false"
        />

        <SelectInput
          id="stock-type"
          v-model="movementForm.type"
          label="Movement Type"
          :options="movementTypeOptions"
          value-key="value"
          label-key="label"
          placeholder="Select type"
          :clearable="false"
        />

        <label>
          <span>Quantity</span>
          <input v-model="movementForm.quantity" type="number" min="0.001" step="0.001" class="form-control formControl">
        </label>

        <label class="stock-modal__full">
          <span>Note</span>
          <textarea v-model="movementForm.note" rows="3" class="form-control formControl" placeholder="Reason or reference"></textarea>
        </label>
      </div>

      <div v-if="movementError" class="stock-modal__error">{{ movementError }}</div>

      <div class="stock-modal__actions">
        <button type="button" class="btn btn-light" @click="closeMovementModal">Cancel</button>
        <button type="submit" class="btn-primary-modern" :disabled="movementSaving">
          <span v-if="movementSaving" class="spinner-border spinner-border-sm"></span>
          Save Record
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'
import { usePermission } from '@/composables/usePermission'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  branches: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
  typeOptions: { type: Object, default: () => ({}) },
})

const { can } = usePermission()
const page = usePage()
const tableId = 'stockManagementTable'
const dtRef = ref(null)
const movementModalOpen = ref(false)
const movementSaving = ref(false)
const movementError = ref('')

const movementForm = reactive({
  branch_id: '',
  product_id: '',
  type: '',
  quantity: '',
  note: '',
})

const datatableUrl = computed(() => route('vendor.stock-management.getdata'))
const branchOptions = computed(() => props.branches.map(branch => ({
  id: branch.id,
  name: branch.name,
})))
const productOptions = computed(() => props.products.map(product => ({
  id: product.id,
  label: `${product.name}${product.sku ? ` - ${product.sku}` : ''} (Stock: ${trimQty(product.current_stock)} ${product.unit_type})`,
})))
const movementTypeOptions = computed(() => Object.entries(props.typeOptions).map(([value, label]) => ({
  value,
  label,
})))

const columns = computed(() => ([
  { data: 'branch_name', name: 'branch_id', orderable: false, searchable: false },
  {
    data: 'product_name',
    name: 'product_id',
    orderable: false,
    render: (data) => `<strong class="stock-product-name">${escapeHtml(data || '-')}</strong>`,
  },
  { data: 'product_sku', name: 'product_sku', orderable: false, render: (data) => `<span class="stock-code">${escapeHtml(data || '-')}</span>` },
  { data: 'type_badge', name: 'type', orderable: false, searchable: false },
  { data: 'quantity_display', name: 'quantity', render: (data) => `<strong>${escapeHtml(data || '0')}</strong>` },
  { data: 'stock_before_display', name: 'stock_before' },
  { data: 'stock_after_display', name: 'stock_after' },
  { data: 'note', name: 'note', render: (data) => escapeHtml(data || '-') },
  { data: 'created_at', name: 'created_at', render: d => `<span class="text-secondary small">${d}</span>` },
]))

const columnDefs = [
  { targets: '_all', className: 'align-middle' },
]

function openMovementModal() {
  movementForm.branch_id = ''
  movementForm.product_id = ''
  movementForm.type = ''
  movementForm.quantity = ''
  movementForm.note = ''
  movementError.value = ''
  movementModalOpen.value = true
}

function closeMovementModal() {
  if (movementSaving.value) return
  movementModalOpen.value = false
}

function submitMovement() {
  movementSaving.value = true
  movementError.value = ''

  router.post(route('vendor.stock-management.store'), {
    branch_id: movementForm.branch_id || null,
    product_id: movementForm.product_id,
    type: movementForm.type,
    quantity: movementForm.quantity,
    note: movementForm.note || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      movementModalOpen.value = false
      dtRef.value?.reloadDatatable?.()
    },
    onError: (errors) => {
      movementError.value = Object.values(errors || {})[0] || 'Unable to save stock record.'
    },
    onFinish: () => {
      movementSaving.value = false
    },
  })
}

function trimQty(value) {
  return Number(value || 0).toFixed(3).replace(/\.?0+$/, '')
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) alertSuccess(flash.message)
    if (flash?.success) alertSuccess(flash.success)
    if (flash?.error) alertError(flash.error)
  },
  { immediate: true }
)
</script>

<style scoped>
.table-container-modern {
  padding: 0.5rem 1.5rem 1.5rem;
}

:deep(.stock-product-name) {
  color: #0f172a;
  font-weight: 900;
}

:deep(.stock-code) {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 12px;
  font-weight: 800;
  color: #334155;
}

.stock-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9100;
  display: grid;
  place-items: center;
  padding: 18px;
  background: rgba(15, 23, 42, 0.42);
}

.stock-modal {
  width: min(560px, 100%);
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.24);
  padding: 20px;
}

.stock-modal__header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.stock-modal__header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 900;
  color: #0f172a;
}

.stock-modal__header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
}

.stock-modal__close {
  width: 36px;
  height: 36px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #ffffff;
  color: #475569;
}

.stock-modal__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.stock-modal__full {
  grid-column: 1 / -1;
}

.stock-modal__grid label span {
  display: block;
  margin-bottom: 6px;
  color: #475569;
  font-size: 12px;
  font-weight: 900;
}

.stock-modal__error {
  margin-top: 12px;
  color: #dc2626;
  font-size: 13px;
  font-weight: 800;
}

.stock-modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}

@media (max-width: 640px) {
  .stock-modal__grid {
    grid-template-columns: 1fr;
  }
}
</style>
