<template>
  <Head title="Brands" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-tags me-2 text-warning"></i>
              Brands
            </h1>
            <p class="header-subtitle">
              Create and manage product brands for the shop POS.
            </p>
          </div>
        </div>
      </div>

      <div v-if="form.errors.general" class="alert alert-danger">
        {{ form.errors.general }}
      </div>

      <div class="brand-layout">
        <section class="form-card brand-list-card">
          <div class="brand-list-card__top">
            <div class="brand-list-card__search">
              <i class="bi bi-search"></i>
              <input v-model="searchQuery" type="text" class="form-control formControl" placeholder="Search brands...">
            </div>
            <button v-if="can('products.create')" type="button" class="btn btn-root" @click="startCreate">
              New Brand
            </button>
          </div>

          <div class="brand-list-card__body">
            <div v-if="!filteredBrands.length" class="tree-empty">
              <i class="bi bi-search"></i>
              <div>{{ searchQuery ? 'No matching brands found.' : 'No brands yet.' }}</div>
            </div>

            <button
              v-for="brand in filteredBrands"
              :key="brand.id"
              type="button"
              class="brand-row"
              :class="{ 'brand-row--active': selectedBrandId === brand.id }"
              @click="selectBrand(brand.id)"
            >
              <div>
                <strong>{{ brand.name }}</strong>
                <small>#{{ brand.sort_order }} - {{ brand.is_active ? 'Active' : 'Inactive' }}</small>
              </div>
              <span class="brand-badge" :class="brand.is_active ? 'brand-badge--active' : 'brand-badge--inactive'">
                {{ brand.is_active ? 'Active' : 'Inactive' }}
              </span>
            </button>
          </div>
        </section>

        <section class="form-card brand-editor-card">
          <div class="brand-editor-card__header">
            <div>
              <h4 class="brand-editor-card__title">{{ isEditMode ? 'Edit Brand' : 'Create Brand' }}</h4>
              <p class="brand-editor-card__subtitle">
                {{ isEditMode ? 'Update brand information for product selection.' : 'Create a brand for the product dropdown.' }}
              </p>
            </div>

            <div class="category-editor-card__actions">
              <button v-if="isEditMode && can('products.delete')" type="button" class="btn btn-delete" @click="openDeleteModal">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>

              <button
                v-if="isEditMode ? can('products.edit') : can('products.create')"
                type="button"
                class="btn btn-primary-modern"
                :disabled="form.processing"
                @click="submit"
              >
                <i class="bi bi-check2 me-2"></i>
                {{ isEditMode ? 'Update' : 'Create' }}
              </button>
            </div>
          </div>

          <div class="brand-editor-card__body">
            <div class="row g-4">
              <div class="col-12 col-lg-8 d-flex flex-column gap-3">
                <div>
                  <label class="form-label formLabel">Brand Name</label>
                  <input v-model="form.name" type="text" class="form-control formControl" placeholder="Brand name">
                  <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                </div>

                <div>
                  <label class="checkbox-wrap">
                    <input v-model="form.is_active" type="checkbox">
                    <span class="checkbox-wrap__box">
                      <i class="bi bi-check"></i>
                    </span>
                    <span class="checkbox-wrap__label">Active</span>
                  </label>
                </div>
              </div>

              <div class="col-12 col-lg-4 d-flex flex-column gap-3">
                <div class="info-panel">
                  <div class="info-panel__row">
                    <span class="info-panel__label">Sort Order</span>
                    <input v-model="form.sort_order" type="number" min="0" class="form-control mt-2 formControl" placeholder="0">
                  </div>

                  <div v-if="selectedBrand" class="info-panel__row">
                    <span class="info-panel__label">Created</span>
                    <span class="info-panel__value">{{ formatDate(selectedBrand.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <DeleteModal
    v-model:show="showDeleteModal"
    :target-id="deleteTarget.id"
    :target-name="deleteTarget.name"
    :loading="deleting"
    title="Delete this brand?"
    cancel-label="Keep Brand"
    confirm-label="Delete Brand"
    @confirm="confirmDelete"
    @closed="onModalClosed"
  />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from '@/composables/usePermission'

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const props = defineProps({
  brands: { type: Array, default: () => [] },
})

const searchQuery = ref('')
const selectedBrandId = ref(props.brands[0]?.id ?? null)
const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

const form = useForm({
  name: '',
  sort_order: 0,
  is_active: true,
})

const filteredBrands = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return props.brands

  return props.brands.filter((brand) =>
    String(brand.name || '').toLowerCase().includes(query),
  )
})

const selectedBrand = computed(() =>
  props.brands.find((brand) => Number(brand.id) === Number(selectedBrandId.value)) || null,
)

const isEditMode = computed(() => !!selectedBrand.value)

function syncForm(brand) {
  form.name = brand?.name ?? ''
  form.sort_order = brand?.sort_order ?? 0
  form.is_active = !!(brand?.is_active ?? true)
}

function selectBrand(id) {
  selectedBrandId.value = Number(id)
}

function startCreate() {
  selectedBrandId.value = null
  syncForm(null)
  form.clearErrors()
}

function openDeleteModal() {
  if (!selectedBrand.value) return
  deleteTarget.value = { id: selectedBrand.value.id, name: selectedBrand.value.name }
  showDeleteModal.value = true
}

function onModalClosed() {}

function confirmDelete() {
  if (!deleteTarget.value.id) return

  deleting.value = true
  router.delete(route('vendor.brands.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onFinish: () => {
      deleting.value = false
    },
  })
}

function submit() {
  const payload = {
    ...form.data(),
    is_active: form.is_active ? 1 : 0,
  }

  const options = {
    preserveScroll: true,
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong.'

      alertError(message)
    },
  }

  if (selectedBrand.value) {
    form.transform(() => ({ ...payload, _method: 'PUT' }))
      .post(route('vendor.brands.update', selectedBrand.value.id), options)
    return
  }

  form.transform(() => payload)
    .post(route('vendor.brands.store'), options)
}

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}

watch(
  selectedBrand,
  (brand) => {
    syncForm(brand)
  },
  { immediate: true },
)

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) alertSuccess(flash.success)
    if (flash?.message) alertSuccess(flash.message)
    if (flash?.error) alertError(flash.error)
  },
  { immediate: true },
)
</script>

<style scoped>
.brand-layout {
  display: grid;
  grid-template-columns: 360px minmax(0, 1fr);
  gap: 24px;
}

.brand-list-card,
.brand-editor-card {
  min-height: 640px;
}

.brand-list-card__top {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-bottom: 1px solid #eef2f7;
}

.brand-list-card__search {
  position: relative;
  flex: 1;
}

.brand-list-card__search i {
  position: absolute;
  top: 50%;
  left: 14px;
  transform: translateY(-50%);
  color: #94a3b8;
}

.brand-list-card__search input {
  padding-left: 40px;
}

.brand-list-card__body {
  display: grid;
  gap: 10px;
  padding: 18px;
}

.brand-row {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #ffffff;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  text-align: left;
}

.brand-row--active {
  border-color: #fdba74;
  background: #fff7ed;
}

.brand-row strong {
  display: block;
  color: #111827;
  font-weight: 900;
}

.brand-row small {
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.brand-badge {
  min-height: 28px;
  border-radius: 999px;
  padding: 0 10px;
  display: inline-flex;
  align-items: center;
  font-size: 12px;
  font-weight: 800;
}

.brand-badge--active {
  background: #dcfce7;
  color: #166534;
}

.brand-badge--inactive {
  background: #fee2e2;
  color: #991b1b;
}

.brand-editor-card__header {
  padding: 22px 24px 0;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.brand-editor-card__title {
  margin: 0;
  color: #111827;
  font-size: 22px;
  font-weight: 900;
}

.brand-editor-card__subtitle {
  margin: 6px 0 0;
  color: #64748b;
}

.brand-editor-card__body {
  padding: 24px;
}

@media (max-width: 992px) {
  .brand-layout {
    grid-template-columns: 1fr;
  }

  .brand-list-card,
  .brand-editor-card {
    min-height: auto;
  }

  .brand-editor-card__header {
    flex-direction: column;
  }
}
</style>
