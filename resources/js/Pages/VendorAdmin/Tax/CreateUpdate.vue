<template>

  <Head :title="isEdit ? 'Edit Tax' : 'Create Tax'" />

  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay gradientOverlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-percent me-2 text-warning"></i>
              {{ isEdit ? 'Edit Tax' : 'Create Tax' }}
            </h1>
            <p class="header-subtitle">
              Configure tax rate, type and applicable order types.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.taxes.index')" class="btn btn-ghost">
              Cancel
            </Link>
            <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              <i class="bi me-2" :class="isEdit ? 'bi-pencil-square' : 'bi-plus-lg'"></i>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Tax' : 'Create Tax') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div v-if="form.errors.general" class="alert alert-danger">
          {{ form.errors.general }}
        </div>

        <div class="form-grid">
          <!-- Left Column - Tax Information -->
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Tax Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Name (English)</label>
                    <input v-model="form.name" type="text" class="form-control formControl"
                      placeholder="Name (English)">
                    <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Branch</label>
                    <SelectInput id="branch.id" v-model="form.branch_id" :options="branches" "
                      valueKey="id" labelKey="name" placeholder="Select Branch" :error="form.errors.branch_id" />
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Code</label>
                    <input v-model="form.code" type="text" class="form-control formControl" placeholder="Tax Code">
                    <div v-if="form.errors.code" class="error-text">{{ form.errors.code }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Type</label>
                    <SelectInput id="type.id" v-model="form.type" :options="Object.entries(typeOptions).map(([value, label]) => ({
                      value,
                      label
                    }))"  valueKey="value" labelKey="label"
                      placeholder="Select Price Type" :error="form.errors.type" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Tax Settings -->
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Tax Settings</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="mb-4">
                  <label class="form-label formLabel">Rate (%)</label>
                  <input v-model="form.rate" type="number" min="0" step="0.001" class="form-control formControl"
                    placeholder="Tax Rate">
                  <div v-if="form.errors.rate" class="error-text">{{ form.errors.rate }}</div>
                </div>

                <div class="mb-4">
                  <label class="form-label formLabel">Order Types</label>
                  <div class="order-picker" ref="orderPickerRef">
                    <button type="button" class="order-picker__control" @click="orderTypesOpen = !orderTypesOpen">
                      <div class="order-picker__chips" v-if="selectedOrderTypeLabels.length">
                        <span v-for="label in selectedOrderTypeLabels" :key="label" class="order-chip">
                          {{ label }}
                        </span>
                      </div>
                      <span v-else class="order-picker__placeholder">Select Order Types</span>
                      <i class="bi" :class="orderTypesOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>

                    <div v-if="orderTypesOpen" class="order-picker__dropdown">
                      <label v-for="(label, value) in orderTypeOptions" :key="value" class="order-picker__item">
                        <input type="checkbox" :value="value" :checked="form.order_types.includes(value)"
                          @change="toggleOrderType(value)">
                        <span>{{ label }}</span>
                      </label>
                    </div>
                  </div>
                  <div v-if="form.errors.order_types" class="error-text">{{ form.errors.order_types }}</div>
                </div>

                <div class="settings-checks">
                  <label class="checkbox-line">
                    <input v-model="form.is_global" type="checkbox">
                    <span>Global Tax</span>
                  </label>

                  <label class="checkbox-line">
                    <input v-model="form.is_compound" type="checkbox">
                    <span>Compound Tax</span>
                  </label>

                  <label class="checkbox-line">
                    <input v-model="form.is_active" type="checkbox">
                    <span>Active</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  tax: { type: Object, default: null },
  branches: { type: Array, default: () => [] },
  typeOptions: { type: Object, default: () => ({}) },
  orderTypeOptions: { type: Object, default: () => ({}) },
})

const orderTypesOpen = ref(false)
const orderPickerRef = ref(null)

const form = useForm({
  name: props.tax?.name ?? '',
  branch_id: props.tax?.branch_id ?? '',
  code: props.tax?.code ?? '',
  rate: props.tax?.rate ?? '',
  type: props.tax?.type ?? 'exclusive',
  order_types: props.tax?.order_types ?? [],
  is_global: !!(props.tax?.is_global ?? false),
  is_compound: !!(props.tax?.is_compound ?? false),
  is_active: !!(props.tax?.is_active ?? true),
})

const isEdit = computed(() => !!props.tax?.id)

const selectedOrderTypeLabels = computed(() =>
  form.order_types
    .map((value) => props.orderTypeOptions[value])
    .filter(Boolean)
)

watch(
  () => form.is_global,
  (value) => {
    if (value) {
      form.branch_id = ''
    }
  }
)

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

function handleClickOutside(event) {
  if (orderPickerRef.value && !orderPickerRef.value.contains(event.target)) {
    orderTypesOpen.value = false
  }
}

function toggleOrderType(value) {
  const index = form.order_types.indexOf(value)

  if (index >= 0) {
    form.order_types.splice(index, 1)
  } else {
    form.order_types.push(value)
  }
}

function normalizedPayload(data) {
  return {
    ...data,
    branch_id: data.is_global ? null : (data.branch_id || null),
    rate: data.rate || 0,
    is_global: data.is_global ? 1 : 0,
    is_compound: data.is_compound ? 1 : 0,
    is_active: data.is_active ? 1 : 0,
    order_types: data.order_types || [],
  }
}

function submit() {
  const options = {
    preserveScroll: true,
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
      .transform((data) => ({
        ...normalizedPayload(data),
        _method: 'PUT',
      }))
      .post(route('vendor.taxes.update', props.tax.id), options)

    return
  }

  form
    .transform((data) => normalizedPayload(data))
    .post(route('vendor.taxes.store'), options)
}
</script>
<style scoped>
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.formControl {
  height: 44px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
}

.formControl:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
}

/* Order Picker */
.order-picker {
  position: relative;
}

.order-picker__control {
  width: 100%;
  min-height: 46px;
  border-radius: 10px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  background: rgba(255, 255, 255, 0.8);
  padding: 8px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.order-picker__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.order-chip {
  background: rgba(59, 130, 246, 0.12);
  color: #b85c00;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 13px;
  font-weight: 600;
}

.order-picker__placeholder {
  color: #94a3b8;
}

.order-picker__dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
  max-height: 320px;
  overflow: auto;
  z-index: 30;
  padding: 8px 0;
}

.order-picker__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  cursor: pointer;
}

.order-picker__item:hover {
  background: rgba(59, 130, 246, 0.10);
}

/* Checkboxes */
.settings-checks {
  display: flex;
  gap: 28px;
  flex-wrap: wrap;
  margin-top: 24px;
}

.checkbox-line {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}

.spinner-icon {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-text {
  font-size: 12px;
  color: #dc2626;
  margin-top: 4px;
}

/* Responsive */
@media (max-width: 1024px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
}

@media (max-width: 640px) {
  .form-container {
    padding: 24px 16px;
  }

  .header-title {
    font-size: 24px;
  }

  .form-card {
    border-radius: 12px;
  }

  .card-header {
    padding: 20px 16px;
  }
}
</style>
