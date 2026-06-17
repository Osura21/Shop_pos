<template>

  <Head :title="isEdit ? 'Edit Discount' : 'Create Discount'" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-bag-heart me-2 text-warning"></i>
              {{ isEdit ? 'Edit Discount' : 'Create Discount' }}
            </h1>

            <p class="header-subtitle">
              Base currency: <strong>{{ baseCurrencyCode }}</strong>
              <template v-if="hasSecondaryCurrency">
                Â· Secondary currency: <strong>{{ secondaryCurrencyCode }}</strong>
              </template>
            </p>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-ghost" type="button" @click="goBack">
              Cancel
            </button>

            <button class="btn btn-primary-modern" type="button" @click="submit" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Discount' : 'Create Discount') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="form.errors.general" class="alert alert-danger mb-3">
        {{ form.errors.general }}
      </div>

      <div class="discount-layout">
        <div class="discount-layout__left">
          <!-- General Information -->
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-info-circle"></i>
              <h2 class="card-title cardTitle">General Information</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="form-grid">
                <div class="form-grid__full">
                  <label class="field-label">Name</label>
                  <input v-model="form.name" type="text" class="form-control fancy-input formControl"
                    placeholder="Discount name">
                  <div v-if="form.errors.name" class="error-text">
                    {{ form.errors.name }}
                  </div>
                </div>

                <div class="form-grid__full">
                  <label class="field-label">Branch</label>
                  <SelectInput id="branch_id" v-model="form.branch_id" :options="branchOptions" valueKey="id"
                    labelKey="name" placeholder="All Branches" />
                  <div v-if="form.errors.branch_id" class="error-text">
                    {{ form.errors.branch_id }}
                  </div>
                </div>

                <div class="form-grid__full">
                  <label class="field-label">Description</label>
                  <textarea v-model="form.description" rows="6"
                    class="form-control fancy-input formControl fancy-textarea" placeholder="Description"></textarea>
                  <div v-if="form.errors.description" class="error-text">
                    {{ form.errors.description }}
                  </div>
                </div>

                <div class="form-grid__full">
                  <label class="checkbox-line">
                    <input v-model="form.is_active" type="checkbox">
                    <span>Active</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Conditions -->
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-toggles"></i>
              <h2 class="card-title cardTitle">Conditions</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="form-grid">
                <div class="form-grid__half">
                  <MultiSelectInput label="Order Types" v-model="form.order_types" :options="orderTypeOptions"
                    valueKey="value" labelKey="label" placeholder="Select order types"
                    :error="form.errors.order_types" />
                </div>

                <div class="form-grid__half">
                  <MultiSelectInput label="Available Days" v-model="form.available_days" :options="availableDayOptions"
                    valueKey="value" labelKey="label" placeholder="Select available days"
                    :error="form.errors.available_days" />
                </div>

                <div class="form-grid__half">
                  <MultiSelectInput label="Categories" v-model="form.category_ids" :options="categories" valueKey="id"
                    labelKey="name" placeholder="Select categories" :error="form.errors.category_ids" />
                </div>

                <div class="form-grid__half">
                  <MultiSelectInput label="Products" v-model="form.product_ids" :options="productOptions" valueKey="id"
                    labelKey="name" placeholder="Select products" :error="form.errors.product_ids" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="discount-layout__right">
          <!-- Discount Details -->
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-ui-checks"></i>
              <h2 class="card-title cardTitle">Discount Details</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="pricing-grid">
                <div>
                  <label class="field-label">Type</label>
                  <SelectInput id="type" v-model="form.type" :options="typeOptions" valueKey="value" labelKey="label"
                    placeholder="Select Type" />
                  <div v-if="form.errors.type" class="error-text">
                    {{ form.errors.type }}
                  </div>
                </div>

                <div>
                  <label class="field-label">{{ valueLabel }}</label>
                  <input v-model="form.value" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Value">
                  <div v-if="form.errors.value" class="error-text">
                    {{ form.errors.value }}
                  </div>
                </div>

                <div v-if="hasSecondaryCurrency && form.type === 'fixed'">
                  <label class="field-label">Value ({{ secondaryCurrencyCode }})</label>
                  <input v-model="form.secondary_value" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Secondary value">
                  <div v-if="form.errors.secondary_value" class="error-text">
                    {{ form.errors.secondary_value }}
                  </div>
                </div>

                <div>
                  <label class="field-label">Maximum Discount ({{ baseCurrencyCode }})</label>
                  <input v-model="form.max_discount" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Maximum discount">
                  <div v-if="form.errors.max_discount" class="error-text">
                    {{ form.errors.max_discount }}
                  </div>
                </div>

                <div v-if="hasSecondaryCurrency">
                  <label class="field-label">Maximum Discount ({{ secondaryCurrencyCode }})</label>
                  <input v-model="form.secondary_max_discount" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Secondary maximum discount">
                  <div v-if="form.errors.secondary_max_discount" class="error-text">
                    {{ form.errors.secondary_max_discount }}
                  </div>
                </div>

                <div>
                  <label class="field-label">Minimum Spend ({{ baseCurrencyCode }})</label>
                  <input v-model="form.min_spend" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Minimum spend">
                  <div v-if="form.errors.min_spend" class="error-text">
                    {{ form.errors.min_spend }}
                  </div>
                </div>

                <div v-if="hasSecondaryCurrency">
                  <label class="field-label">Minimum Spend ({{ secondaryCurrencyCode }})</label>
                  <input v-model="form.secondary_min_spend" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Secondary minimum spend">
                  <div v-if="form.errors.secondary_min_spend" class="error-text">
                    {{ form.errors.secondary_min_spend }}
                  </div>
                </div>

                <div>
                  <label class="field-label">Maximum Spend ({{ baseCurrencyCode }})</label>
                  <input v-model="form.max_spend" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Maximum spend">
                  <div v-if="form.errors.max_spend" class="error-text">
                    {{ form.errors.max_spend }}
                  </div>
                </div>

                <div v-if="hasSecondaryCurrency">
                  <label class="field-label">Maximum Spend ({{ secondaryCurrencyCode }})</label>
                  <input v-model="form.secondary_max_spend" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Secondary maximum spend">
                  <div v-if="form.errors.secondary_max_spend" class="error-text">
                    {{ form.errors.secondary_max_spend }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Availability -->
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-calendar2-check"></i>
              <h2 class="card-title cardTitle">Availability</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="pricing-grid">
                <div>
                  <label class="field-label">Start Date</label>
                  <DatePicker v-model="form.starts_at" />
                  <div v-if="form.errors.starts_at" class="error-text">
                    {{ form.errors.starts_at }}
                  </div>
                </div>

                <div>
                  <label class="field-label">End Date</label>
                  <DatePicker v-model="form.ends_at" />
                  <div v-if="form.errors.ends_at" class="error-text">
                    {{ form.errors.ends_at }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Limits -->
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-sliders"></i>
              <h2 class="card-title cardTitle">Limits</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="pricing-grid">
                <div>
                  <label class="field-label">Usage Limit</label>
                  <input v-model="form.usage_limit" type="number" min="1" step="1"
                    class="form-control fancy-input formControl" placeholder="Usage limit">
                  <div v-if="form.errors.usage_limit" class="error-text">
                    {{ form.errors.usage_limit }}
                  </div>
                </div>

                <div>
                  <label class="field-label">Per Customer Limit</label>
                  <input v-model="form.per_customer_limit" type="number" min="1" step="1"
                    class="form-control fancy-input formControl" placeholder="Per customer limit">
                  <div v-if="form.errors.per_customer_limit" class="error-text">
                    {{ form.errors.per_customer_limit }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MultiSelectInput from '@/Components/MultiSelectInput.vue'
import DatePicker from '@/Components/DatePicker.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  discount: { type: Object, default: null },
  branches: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
  orderTypes: { type: Array, default: () => [] },
  availableDays: { type: Array, default: () => [] },
})

const page = usePage()

const categoriesOpen = ref(false)

const isEdit = computed(() => !!props.discount?.id)

const baseCurrencyCode = computed(() => {
  return page.props.currencySettings?.base_currency?.code || 'LKR'
})

const secondaryCurrencyCode = computed(() => {
  return page.props.currencySettings?.secondary_currency?.code || ''
})

const hasSecondaryCurrency = computed(() => !!secondaryCurrencyCode.value)

const typeOptions = [
  { value: 'fixed', label: 'Fixed' },
  { value: 'percentage', label: 'Percentage' },
]

const branchOptions = computed(() => [
  { id: '', name: 'All Branches' },
  ...props.branches,
])

const orderTypeOptions = computed(() => props.orderTypes || [])
const availableDayOptions = computed(() => props.availableDays || [])
const productOptions = computed(() => props.products || [])

const valueLabel = computed(() => {
  return form.type === 'percentage'
    ? 'Value (%)'
    : `Value (${baseCurrencyCode.value})`
})

function normalizeArray(value) {
  if (Array.isArray(value)) return value
  if (!value) return []

  try {
    const parsed = JSON.parse(value)
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

function normalizeIds(value) {
  return normalizeArray(value)
    .map((item) => Number(item))
    .filter((item) => Number.isFinite(item))
}

function dateValue(value) {
  if (!value) return ''
  return String(value).slice(0, 10)
}

const form = useForm({
  name: props.discount?.name ?? '',
  description: props.discount?.description ?? '',
  branch_id: props.discount?.branch_id ?? '',
  type: props.discount?.type ?? 'fixed',
  value: props.discount?.value ?? '',
  secondary_value: props.discount?.secondary_value ?? '',
  max_discount: props.discount?.max_discount ?? '',
  secondary_max_discount: props.discount?.secondary_max_discount ?? '',
  min_spend: props.discount?.min_spend ?? '',
  secondary_min_spend: props.discount?.secondary_min_spend ?? '',
  max_spend: props.discount?.max_spend ?? '',
  secondary_max_spend: props.discount?.secondary_max_spend ?? '',
  starts_at: dateValue(props.discount?.starts_at),
  ends_at: dateValue(props.discount?.ends_at),
  usage_limit: props.discount?.usage_limit ?? '',
  per_customer_limit: props.discount?.per_customer_limit ?? '',
  order_types: normalizeArray(props.discount?.order_types),
  available_days: normalizeArray(props.discount?.available_days),
  category_ids: normalizeIds(props.discount?.category_ids),
  product_ids: normalizeIds(props.discount?.product_ids),
  is_active: props.discount?.is_active ?? true,
})

const selectedCategories = computed(() => {
  return props.categories.filter((item) => isSelected(form.category_ids, item.id))
})

function isSelected(values, value) {
  return (values || []).map(String).includes(String(value))
}

function toggleArrayValue(field, value) {
  const current = [...(form[field] || [])]
  const index = current.map(String).indexOf(String(value))

  if (index >= 0) {
    current.splice(index, 1)
  } else {
    current.push(Number.isFinite(Number(value)) ? Number(value) : value)
  }

  form[field] = current
}

function nullable(value) {
  return value === '' || value === undefined ? null : value
}

function buildPayload(data) {
  return {
    ...data,
    branch_id: nullable(data.branch_id),

    secondary_value:
      hasSecondaryCurrency.value && data.type === 'fixed'
        ? nullable(data.secondary_value)
        : null,

    max_discount: nullable(data.max_discount),
    secondary_max_discount: hasSecondaryCurrency.value
      ? nullable(data.secondary_max_discount)
      : null,

    min_spend: nullable(data.min_spend),
    secondary_min_spend: hasSecondaryCurrency.value
      ? nullable(data.secondary_min_spend)
      : null,

    max_spend: nullable(data.max_spend),
    secondary_max_spend: hasSecondaryCurrency.value
      ? nullable(data.secondary_max_spend)
      : null,

    starts_at: nullable(data.starts_at),
    ends_at: nullable(data.ends_at),
    usage_limit: nullable(data.usage_limit),
    per_customer_limit: nullable(data.per_customer_limit),

    order_types: normalizeArray(data.order_types),
    available_days: normalizeArray(data.available_days),
    category_ids: normalizeIds(data.category_ids),
    product_ids: normalizeIds(data.product_ids),

    is_active: Boolean(data.is_active),
  }
}

function goBack() {
  router.visit(route('vendor.promotions.discounts.index'))
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('vendor.promotions.discounts.index'))
    },
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong.'

      alertError(message)
    }
  }

  if (isEdit.value) {
    form
      .transform((data) => buildPayload(data))
      .put(route('vendor.promotions.discounts.update', props.discount.id), options)

    return
  }

  form
    .transform((data) => buildPayload(data))
    .post(route('vendor.promotions.discounts.store'), options)
}
</script>

<style scoped>
.discount-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
  gap: 1.5rem;
}

.discount-layout__left,
.discount-layout__right {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.form-grid__full {
  grid-column: 1 / -1;
}

.form-grid__half {
  min-width: 0;
}

.pricing-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.field-label {
  display: block;
  margin-bottom: 0.45rem;
  color: var(--slate-700, #334155);
  font-size: 0.82rem;
  font-weight: 800;
}

.error-text {
  margin-top: 0.35rem;
  color: #dc2626;
  font-size: 0.78rem;
  font-weight: 600;
}

.checkbox-line {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  color: var(--slate-700, #334155);
  font-weight: 800;
  cursor: pointer;
}

.checkbox-line input {
  width: 18px;
  height: 18px;
  accent-color: var(--primary, #3b82f6);
}

.fancy-textarea {
  resize: vertical;
  min-height: 130px;
}

.picker-box {
  position: relative;
}

.picker-box__control {
  width: 100%;
  min-height: 42px;
  border-radius: 8px;
  border: 1px solid #d7dee7;
  background: #fff;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  text-align: left;
}

.picker-box__control:focus {
  border-color: var(--primary, #3b82f6);
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.14);
  outline: none;
}

.picker-box__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.picker-chip {
  background: #eef2f7;
  color: #475569;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 700;
}

.picker-box__placeholder {
  color: #94a3b8;
}

.picker-box__dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 8px);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
  max-height: 320px;
  overflow: auto;
  z-index: 40;
  padding: 8px 0;
}

.picker-box__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  color: #334155;
  cursor: pointer;
}

.picker-box__item:hover {
  background: #f8fafc;
}

.picker-box__item input {
  width: 18px;
  height: 18px;
  accent-color: var(--primary, #3b82f6);
}

:deep(.multiselect-input) {
  min-height: 42px;
}

:deep(.bs-select-menu) {
  z-index: 50;
}

@media (max-width: 1100px) {
  .discount-layout {
    grid-template-columns: 1fr;
  }

  .discount-layout__right {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .discount-layout__right .form-card:first-child {
    grid-column: 1 / -1;
  }
}

@media (max-width: 760px) {

  .form-grid,
  .discount-layout__right {
    grid-template-columns: 1fr;
  }

  .form-grid__full {
    grid-column: auto;
  }
}
</style>
