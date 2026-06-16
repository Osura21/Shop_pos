<template>

  <Head title="Products" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Products</h1>
            <p class="header-subtitle">Manage all products for this tenant.</p>
          </div>
          <button v-if="can('products.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Product</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[6, 'desc']]" searchPlaceholder="Search here...">
          <template #header>
            <tr>
              <th class="text-center">
                <input type="checkbox" class="form-check-input">
              </th>
              <th>Thumbnail</th>
              <th>Name</th>
              <th>Price</th>
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

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this product?" cancel-label="Keep Product" confirm-label="Delete Product"
    @confirm="confirmDelete" @closed="onModalClosed" />

  <transition name="product-drawer-fade">
    <div v-if="drawerOpen" class="product-drawer-backdrop" @click="closeProductDrawer"></div>
  </transition>

  <transition name="product-drawer-slide">
    <aside v-if="drawerOpen" class="product-drawer" aria-label="Product details">
      <div class="product-drawer__header">
        <div class="product-drawer__media">
          <img v-if="selectedProduct?.image_url" :src="selectedProduct.image_url" :alt="selectedProduct.name">
          <i v-else class="bi bi-image"></i>
        </div>

        <div class="product-drawer__title">
          <span class="product-drawer__eyebrow">PR - {{ selectedProduct?.id || productLoadingId }}</span>
          <h2>{{ selectedProduct?.name || 'Loading product' }}</h2>
          <div class="product-drawer__chips">
            <span class="drawer-chip" :class="selectedProduct?.is_active ? 'drawer-chip--active' : 'drawer-chip--inactive'">
              <i :class="selectedProduct?.is_active ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
              {{ selectedProduct?.is_active ? 'Active' : 'Inactive' }}
            </span>
            <span v-if="selectedProduct?.sku" class="drawer-chip drawer-chip--neutral">SKU {{ selectedProduct.sku }}</span>
          </div>
        </div>

        <button type="button" class="product-drawer__close" aria-label="Close product details" @click="closeProductDrawer">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="product-drawer__tabs">
        <button type="button" :class="{ active: drawerTab === 'details' }" @click="drawerTab = 'details'">
          <i class="bi bi-card-list"></i>
          Details
        </button>
        <button type="button" :class="{ active: drawerTab === 'ingredients' }" @click="drawerTab = 'ingredients'">
          <i class="bi bi-clipboard2-pulse"></i>
          Ingredient Analysis
        </button>
      </div>

      <div class="product-drawer__body">
        <div v-if="drawerLoading" class="drawer-loading">
          <span class="spinner-border spinner-border-sm"></span>
          Loading product details...
        </div>

        <div v-else-if="drawerError" class="drawer-error">
          <i class="bi bi-exclamation-triangle-fill"></i>
          {{ drawerError }}
        </div>

        <template v-else-if="selectedProduct">
          <section v-if="drawerTab === 'details'" class="drawer-section">
            <div class="drawer-stats">
              <div class="drawer-stat">
                <span>Base Price</span>
                <strong>{{ money(selectedProduct.base_price) }}</strong>
              </div>
              <div class="drawer-stat">
                <span>Secondary Price</span>
                <strong>{{ selectedProduct.secondary_price ? money(selectedProduct.secondary_price) : '-' }}</strong>
              </div>
              <div class="drawer-stat">
                <span>Can Make Now</span>
                <strong>{{ availabilityLabel(selectedProduct.analysis?.can_make) }}</strong>
              </div>
            </div>

            <div class="detail-grid">
              <div class="detail-item">
                <span>Description</span>
                <strong>{{ selectedProduct.description || '-' }}</strong>
              </div>
              <div class="detail-item">
                <span>Categories</span>
                <strong>{{ listNames(selectedProduct.categories) }}</strong>
              </div>
              <div class="detail-item">
                <span>Taxes</span>
                <strong>{{ listNames(selectedProduct.taxes) }}</strong>
              </div>
              <div class="detail-item">
                <span>Special Price</span>
                <strong>{{ specialPriceLabel(selectedProduct) }}</strong>
              </div>
              <div class="detail-item">
                <span>Special Period</span>
                <strong>{{ dateRange(selectedProduct.special_price_start, selectedProduct.special_price_end) }}</strong>
              </div>
              <div class="detail-item">
                <span>New Product Period</span>
                <strong>{{ dateRange(selectedProduct.new_from, selectedProduct.new_to) }}</strong>
              </div>
              <div class="detail-item">
                <span>Created</span>
                <strong>{{ selectedProduct.created_at || '-' }}</strong>
              </div>
              <div class="detail-item">
                <span>Updated</span>
                <strong>{{ selectedProduct.updated_at || '-' }}</strong>
              </div>
            </div>

            <div class="drawer-subsection">
              <div class="drawer-subsection__title">
                <h3>Options</h3>
                <span>{{ selectedProduct.options?.length || 0 }}</span>
              </div>

              <div v-if="selectedProduct.options?.length" class="option-list">
                <div v-for="option in selectedProduct.options" :key="option.id" class="option-card">
                  <div>
                    <strong>{{ option.name }}</strong>
                    <span>{{ prettyLabel(option.type) }} · {{ option.is_required ? 'Required' : 'Optional' }}</span>
                  </div>
                  <b>{{ money(option.base_price) }}</b>
                </div>
              </div>
              <div v-else class="drawer-empty">No options configured.</div>
            </div>
          </section>

          <section v-else class="drawer-section">
            <div class="analysis-hero">
              <div>
                <span>Ingredient Analysis</span>
                <h3>{{ availabilityLabel(selectedProduct.analysis?.can_make) }}</h3>
                <p v-if="selectedProduct.analysis?.limiting_ingredient">
                  Limited by {{ selectedProduct.analysis.limiting_ingredient }}
                </p>
                <p v-else>No limiting ingredient found.</p>
              </div>
              <div class="analysis-meter">
                <strong>{{ selectedProduct.analysis?.ingredient_count || 0 }}</strong>
                <span>ingredients</span>
              </div>
            </div>

            <div class="analysis-cards">
              <div class="analysis-card">
                <span>Low Stock</span>
                <strong>{{ selectedProduct.analysis?.low_ingredient_count || 0 }}</strong>
              </div>
              <div class="analysis-card">
                <span>Unavailable</span>
                <strong>{{ selectedProduct.analysis?.unavailable_ingredient_count || 0 }}</strong>
              </div>
              <div class="analysis-card">
                <span>Base Cost</span>
                <strong>{{ money(selectedProduct.analysis?.estimated_base_cost) }}</strong>
              </div>
            </div>

            <div v-if="selectedProduct.analysis?.ingredients?.length" class="ingredient-analysis-list">
              <article v-for="ingredient in selectedProduct.analysis.ingredients" :key="ingredient.id" class="ingredient-analysis-card"
                :class="{ 'ingredient-analysis-card--danger': ingredient.is_unavailable, 'ingredient-analysis-card--warn': ingredient.is_low && !ingredient.is_unavailable }">
                <div class="ingredient-analysis-card__head">
                  <div>
                    <strong>{{ ingredient.name }}</strong>
                    <span>{{ ingredient.note || 'Recipe ingredient' }}</span>
                  </div>
                  <span class="drawer-chip" :class="ingredientStatusClass(ingredient)">
                    {{ ingredientStatusLabel(ingredient) }}
                  </span>
                </div>

                <div class="ingredient-metrics">
                  <div>
                    <span>Needed / Product</span>
                    <strong>{{ qty(ingredient.required_per_product, ingredient.unit) }}</strong>
                  </div>
                  <div>
                    <span>Current Stock</span>
                    <strong>{{ qty(ingredient.current_stock, ingredient.unit) }}</strong>
                  </div>
                  <div>
                    <span>Alert Level</span>
                    <strong>{{ qty(ingredient.alert_quantity, ingredient.unit) }}</strong>
                  </div>
                  <div>
                    <span>Can Make</span>
                    <strong>{{ ingredient.can_make ?? '-' }}</strong>
                  </div>
                </div>

                <div class="ingredient-bar">
                  <span :style="{ width: stockPercent(ingredient) + '%' }"></span>
                </div>

                <div class="ingredient-cost-line">
                  <span>Recipe qty {{ qty(ingredient.quantity, ingredient.unit) }} + {{ ingredient.loss_pct }}% loss</span>
                  <b>Cost {{ money(ingredient.base_cost_total) }}</b>
                </div>
              </article>
            </div>
            <div v-else class="drawer-empty">No ingredients configured for this product.</div>
          </section>
        </template>
      </div>
    </aside>
  </transition>

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

const tableId = 'productTable'
const dtRef = ref(null)
const datatableUrl = computed(() => route('vendor.products.getdata'))

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)
const drawerOpen = ref(false)
const drawerLoading = ref(false)
const drawerError = ref('')
const drawerTab = ref('details')
const selectedProduct = ref(null)
const productLoadingId = ref(null)

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function renderThumbnail(data, row) {
  const image = data || row?.image_url || ''
  const name = escapeHtml(row?.name || 'Product')

  if (!image) {
    return `
      <div class="product-thumb product-thumb--placeholder">
        <i class="bi bi-image"></i>
      </div>
    `
  }

  return `
    <div class="product-thumb">
      <img src="${image}" alt="${name}">
    </div>
  `
}

const columns = computed(() => ([
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
    data: 'image_url',
    name: 'image_path',
    orderable: false,
    searchable: false,
    render: (data, type, row) => renderThumbnail(data, row),
  },
  {
    data: 'name',
    name: 'name',
    render: (data, type, row) => `
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold text-dark mb-0">
            ${data}
          </div>
          <div class="text-muted x-small">
            PR - ${row.id}
          </div>
        </div>
      </div>
    `
  },
  {
    data: 'price_display',
    name: 'base_price',
    render: (data, type, row) => {
      const value = data || row?.price || null
      return value
        ? `<span class="quantity-chip">${escapeHtml(String(value))}</span>`
        : `<span class="text-muted small">—</span>`
    },
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
  { data: 'created_at', name: 'created_at', render: d => `<span class="text-secondary small">${d}</span>` },
  { data: 'updated_at', name: 'updated_at', render: d => `<span class="text-secondary small">${d}</span>` },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const name = escapeHtml(row?.name ?? '')
      const id = escapeHtml(data)

      return `
      <div class="d-flex gap-2 justify-content-end">
        ${can('products.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${id}" title="Edit">
               <i class="bi bi-pencil-fill"></i>
             </button>`
          : ''
        }
        <button type="button" class="btn-circle js-view" data-id="${id}" title="View">
          <i class="bi bi-eye-fill"></i>
        </button>
        ${can('products.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" data-name="${name}" title="Delete">
               <i class="bi bi-trash3-fill"></i>
             </button>`
          : ''
        }
      </div>`
    },
  }
]))

const columnDefs = [
  { targets: 0, className: 'text-center align-middle', width: '44px' },
  { targets: 1, className: 'align-middle', width: '92px' },
  { targets: -1, className: 'text-end align-middle', width: '150px' },
  { targets: '_all', className: 'align-middle' },
]

function goCreate() {
  router.visit(route('vendor.products.create'))
}

function goEdit(id) {
  router.visit(route('vendor.products.edit', id))
}

function toggleStatus(id, active) {
  const isActive = Number(active) === 1
  if (!window.confirm(isActive ? 'Deactivate this product?' : 'Activate this product?')) return
  router.patch(route('vendor.products.toggle-status', id), {}, { preserveScroll: true })
}

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

async function openProductDrawer(id) {
  if (!id) return
  productLoadingId.value = id
  drawerOpen.value = true
  drawerLoading.value = true
  drawerError.value = ''
  drawerTab.value = 'details'
  selectedProduct.value = null

  try {
    const { data } = await window.axios.get(route('vendor.products.show', id))
    selectedProduct.value = data
  } catch (error) {
    drawerError.value = error?.response?.data?.message || 'Unable to load product details.'
  } finally {
    drawerLoading.value = false
  }
}

function closeProductDrawer() {
  drawerOpen.value = false
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.products.destroy', id), {
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
    if (id) goEdit(id)
  })
  $(document).on('click', `${selector} .js-delete`, (e) => {
    const id = e.currentTarget?.dataset?.id
    const name = e.currentTarget?.dataset?.name || ''
    if (id) openDeleteModal(id, name)
  })
  $(document).on('click', `${selector} .js-view`, (e) => {
    const id = e.currentTarget?.dataset?.id
    if (id) openProductDrawer(id)
  })
  $(document).on('click', `${selector} .js-toggle-status`, (e) => {
    const id = e.currentTarget?.dataset?.id
    const active = e.currentTarget?.dataset?.active
    if (id) toggleStatus(id, active)
  })
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`
  $(document).off('click', `${selector} .js-edit`)
  $(document).off('click', `${selector} .js-delete`)
  $(document).off('click', `${selector} .js-view`)
  $(document).off('click', `${selector} .js-toggle-status`)
}

function listNames(items = []) {
  if (!items?.length) return '-'
  return items.map(item => item.name).join(', ')
}

function money(value) {
  const number = Number(value || 0)
  return number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function qty(value, unit = '') {
  const number = Number(value || 0)
  return `${number.toLocaleString(undefined, { maximumFractionDigits: 4 })}${unit ? ` ${unit}` : ''}`
}

function availabilityLabel(value) {
  if (value === null || value === undefined) return 'No recipe'
  return `${value} can be made`
}

function dateRange(start, end) {
  if (!start && !end) return '-'
  return `${start || '-'} to ${end || '-'}`
}

function specialPriceLabel(product) {
  if (!product?.base_special_price && !product?.secondary_special_price) return '-'
  const type = product.special_price_type ? prettyLabel(product.special_price_type) : 'Fixed'
  return `${type}: ${product.base_special_price || '-'}${product.secondary_special_price ? ` / ${product.secondary_special_price}` : ''}`
}

function prettyLabel(value) {
  return String(value || '-').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function ingredientStatusLabel(ingredient) {
  if (ingredient.is_unavailable) return 'Unavailable'
  if (ingredient.is_low) return 'Low Stock'
  return 'Ready'
}

function ingredientStatusClass(ingredient) {
  if (ingredient.is_unavailable) return 'drawer-chip--inactive'
  if (ingredient.is_low) return 'drawer-chip--warn'
  return 'drawer-chip--active'
}

function stockPercent(ingredient) {
  const alert = Number(ingredient.alert_quantity || 0)
  const current = Number(ingredient.current_stock || 0)
  const target = alert > 0 ? alert * 2 : Math.max(current, 1)
  return Math.max(4, Math.min(100, Math.round((current / target) * 100)))
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

:deep(.product-thumb) {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  overflow: hidden;
  background: var(--slate-50);
  border: 1px solid var(--slate-200);
  display: grid;
  place-items: center;
}

:deep(.product-thumb img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

:deep(.product-thumb--placeholder) {
  color: #cbd5e1;
  font-size: 22px;
}

:deep(.name-chip) {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #c2410c;
  font-size: 0.78rem;
  font-weight: 600;
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

:deep(.form-check-input) {
  width: 18px;
  height: 18px;
  cursor: pointer;
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

.product-drawer-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1090;
  background: rgba(15, 23, 42, 0.42);
  backdrop-filter: blur(3px);
}

.product-drawer {
  position: fixed;
  top: 0;
  right: 0;
  z-index: 1100;
  width: min(760px, 100vw);
  height: 100vh;
  background: #ffffff;
  box-shadow: -24px 0 70px rgba(15, 23, 42, 0.22);
  display: flex;
  flex-direction: column;
}

.product-drawer__header {
  position: relative;
  min-height: 188px;
  padding: 24px 62px 22px 24px;
  display: grid;
  grid-template-columns: 128px minmax(0, 1fr);
  gap: 18px;
  align-items: end;
  background:
    linear-gradient(135deg, rgba(249, 115, 22, 0.92), rgba(245, 158, 11, 0.88)),
    #f97316;
  color: #ffffff;
}

.product-drawer__media {
  width: 128px;
  height: 128px;
  border-radius: 22px;
  overflow: hidden;
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.38);
  color: rgba(255, 255, 255, 0.85);
  font-size: 34px;
}

.product-drawer__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-drawer__title {
  min-width: 0;
}

.product-drawer__eyebrow {
  display: block;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.9;
}

.product-drawer__title h2 {
  margin: 8px 0 12px;
  font-size: 28px;
  line-height: 1.15;
  font-weight: 900;
}

.product-drawer__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.drawer-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 28px;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  border: 1px solid transparent;
}

.drawer-chip--active {
  background: #dcfce7;
  color: #166534;
  border-color: #bbf7d0;
}

.drawer-chip--inactive {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fecaca;
}

.drawer-chip--warn {
  background: #ffedd5;
  color: #9a3412;
  border-color: #fed7aa;
}

.drawer-chip--neutral {
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.34);
}

.product-drawer__close {
  position: absolute;
  top: 18px;
  right: 18px;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.38);
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
  display: grid;
  place-items: center;
}

.product-drawer__tabs {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  padding: 12px 18px 0;
  gap: 10px;
  border-bottom: 1px solid #eef2f7;
}

.product-drawer__tabs button {
  border: 0;
  background: transparent;
  color: #64748b;
  min-height: 46px;
  padding: 0 12px;
  border-radius: 14px 14px 0 0;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.product-drawer__tabs button.active {
  color: #c2410c;
  background: #fff7ed;
  box-shadow: inset 0 -2px 0 #f97316;
}

.product-drawer__body {
  flex: 1;
  overflow: auto;
  padding: 22px;
  background: #fbfdff;
}

.drawer-loading,
.drawer-error,
.drawer-empty {
  min-height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px dashed #cbd5e1;
  border-radius: 16px;
  color: #64748b;
  font-weight: 700;
  background: #ffffff;
}

.drawer-error {
  color: #b91c1c;
  background: #fef2f2;
  border-color: #fecaca;
}

.drawer-section {
  display: grid;
  gap: 18px;
}

.drawer-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.drawer-stat,
.detail-item,
.option-card,
.analysis-card,
.ingredient-analysis-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}

.drawer-stat {
  padding: 16px;
}

.drawer-stat span,
.detail-item span,
.analysis-card span,
.ingredient-metrics span,
.ingredient-cost-line span {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.drawer-stat strong {
  display: block;
  margin-top: 7px;
  font-size: 20px;
  color: #0f172a;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.detail-item {
  padding: 15px;
}

.detail-item strong {
  display: block;
  margin-top: 7px;
  color: #1e293b;
  font-size: 14px;
  line-height: 1.55;
}

.drawer-subsection__title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.drawer-subsection__title h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 900;
  color: #111827;
}

.drawer-subsection__title span {
  min-width: 30px;
  height: 30px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: #fff7ed;
  color: #c2410c;
  font-weight: 900;
}

.option-list {
  display: grid;
  gap: 10px;
}

.option-card {
  padding: 14px 16px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.option-card strong,
.ingredient-analysis-card strong {
  color: #111827;
}

.option-card span {
  display: block;
  color: #64748b;
  font-size: 12px;
  margin-top: 3px;
}

.analysis-hero {
  padding: 22px;
  border-radius: 18px;
  background: linear-gradient(135deg, #fff7ed, #ffffff);
  border: 1px solid #fed7aa;
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: center;
}

.analysis-hero span {
  color: #c2410c;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.analysis-hero h3 {
  margin: 7px 0 4px;
  font-size: 26px;
  font-weight: 900;
  color: #111827;
}

.analysis-hero p {
  margin: 0;
  color: #64748b;
  font-weight: 700;
}

.analysis-meter {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: #ffffff;
  border: 8px solid #fed7aa;
  display: grid;
  place-items: center;
  text-align: center;
  flex-shrink: 0;
}

.analysis-meter strong {
  display: block;
  font-size: 24px;
  line-height: 1;
  color: #c2410c;
}

.analysis-meter span {
  display: block;
  margin-top: 3px;
  color: #64748b;
  font-size: 10px;
  letter-spacing: 0;
}

.analysis-cards {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.analysis-card {
  padding: 15px;
}

.analysis-card strong {
  display: block;
  margin-top: 7px;
  font-size: 22px;
  color: #0f172a;
}

.ingredient-analysis-list {
  display: grid;
  gap: 14px;
}

.ingredient-analysis-card {
  padding: 16px;
}

.ingredient-analysis-card--danger {
  border-color: #fecaca;
  background: #fffafa;
}

.ingredient-analysis-card--warn {
  border-color: #fed7aa;
  background: #fffaf4;
}

.ingredient-analysis-card__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.ingredient-analysis-card__head span:not(.drawer-chip) {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
}

.ingredient-metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.ingredient-metrics div {
  padding: 10px;
  border-radius: 12px;
  background: #f8fafc;
}

.ingredient-metrics strong {
  display: block;
  margin-top: 5px;
  font-size: 13px;
}

.ingredient-bar {
  height: 8px;
  margin: 14px 0 12px;
  border-radius: 999px;
  background: #e2e8f0;
  overflow: hidden;
}

.ingredient-bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #f97316, #facc15);
}

.ingredient-cost-line {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 13px;
  color: #334155;
}

.ingredient-cost-line b {
  color: #111827;
}

.product-drawer-fade-enter-active,
.product-drawer-fade-leave-active {
  transition: opacity 0.22s ease;
}

.product-drawer-fade-enter-from,
.product-drawer-fade-leave-to {
  opacity: 0;
}

.product-drawer-slide-enter-active,
.product-drawer-slide-leave-active {
  transition: transform 0.28s ease, opacity 0.28s ease;
}

.product-drawer-slide-enter-from,
.product-drawer-slide-leave-to {
  transform: translateX(100%);
  opacity: 0.6;
}

@media (max-width: 720px) {
  .product-drawer__header {
    grid-template-columns: 92px minmax(0, 1fr);
    min-height: 150px;
  }

  .product-drawer__media {
    width: 92px;
    height: 92px;
    border-radius: 16px;
  }

  .product-drawer__title h2 {
    font-size: 22px;
  }

  .drawer-stats,
  .detail-grid,
  .analysis-cards,
  .ingredient-metrics {
    grid-template-columns: 1fr;
  }

  .analysis-hero {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
