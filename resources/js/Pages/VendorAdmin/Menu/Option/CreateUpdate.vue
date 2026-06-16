<template>

  <Head :title="isEdit ? 'Edit Option' : 'Create Option'" />

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
              <i class="bi bi-sliders2-vertical me-2 text-warning"></i>
              {{ isEdit ? 'Edit Option' : 'Create Option' }}
            </h1>
            <p class="header-subtitle">
              Create customizable options for menu items (size, add-ons, extras, etc.).
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.options.index')" class="btn btn-ghost">
              Cancel
            </Link>
            <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Option' : 'Create Option') }}
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
          <!-- Left Column - Option Information -->
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Option Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label formLabel">Name (English)</label>
                    <input v-model="form.name" type="text" class="form-control formControl"
                      placeholder="Option name (e.g. Size, Spice Level)">
                    <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Branch</label>
                    <!-- <SelectInput id="branch.id" v-model="form.branch_id" :options="branches" 
                      valueKey="id" labelKey="name" placeholder="All Branches (Global)"
                      :error="form.errors.branch_id" /> -->
                      <MultiSelectInput v-model="form.branch_ids" :options="branches" labelKey="name" valueKey="id"
                      placeholder="Select Branch" />
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Type</label>
                    <SelectInput id="type.id" v-model="form.type" :options="Object.entries(typeOptions).map(([value, label]) => ({
                      value,
                      label
                    }))"  valueKey="id" labelKey="name"
                      placeholder="Select Type" :error="form.errors.type" />
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch">
                      <input v-model="form.is_required" type="checkbox" class="form-check-input">
                      <label class="form-check-label">Required Option</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Values -->
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Option Values</h2>
              </div>
              <div class="card-body formCardBody">
                <div v-if="!form.type" class="alert alert-orange">
                  Please select an option type above to configure values.
                </div>

                <template v-else-if="usesRows(form.type)">
                  <div class="table-responsive">
                    <table class="table option-values-table align-middle">
                      <thead>
                        <tr>
                          <th width="52"></th>
                          <th>Label</th>
                          <th>Price ({{ baseCurrencyCode }})</th>
                          <th v-if="hasSecondaryCurrency">Price ({{ secondaryCurrencyCode }})</th>
                          <th>Price Type</th>
                          <th width="60"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, index) in form.values" :key="index">
                          <td class="text-center drag-cell">
                            <i class="bi bi-grip-vertical"></i>
                          </td>
                          <td>
                            <input v-model="row.label" type="text" class="form-control formControl" placeholder="Label">
                          </td>
                          <td>
                            <input v-model="row.base_price" type="number" min="0" step="0.001" class="form-control formControl">
                          </td>
                          <td v-if="hasSecondaryCurrency">
                            <input v-model="row.secondary_price" type="number" min="0" step="0.001"
                              class="form-control formControl">
                          </td>
                          <td>
                            <SelectInput id="type.id" v-model="row.price_type" :options="Object.entries(priceTypes).map(([value, label]) => ({
                              value,
                              label
                            }))"  valueKey="value" labelKey="label"
                              placeholder="Select Price Type" />
                          </td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeValueRow(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <button type="button" class="btn btn-primary-modern btn-sm mt-3" @click="addValueRow">
                    <i class="bi bi-plus-lg me-2"></i>Add Row
                  </button>

                  <div v-if="form.errors.values" class="error-text mt-2">{{ form.errors.values }}</div>
                </template>

                <template v-else>
                  <div class="row g-3">
                    <div class="col-12 col-lg-6">
                      <label class="form-label formLabel">Price ({{ baseCurrencyCode }})</label>
                      <input v-model="form.base_price" type="number" min="0" step="0.001" class="form-control formControl">
                    </div>
                    <div v-if="hasSecondaryCurrency" class="col-12 col-lg-6">
                      <label class="form-label formLabel">Price ({{ secondaryCurrencyCode }})</label>
                      <input v-model="form.secondary_price" type="number" min="0" step="0.001" class="form-control formControl">
                    </div>
                    <div class="col-12 col-lg-6">
                      <label class="form-label formLabel">Price Type</label>
                      <SelectInput id="type.id" v-model="form.price_type" :options="Object.entries(priceTypes).map(([value, label]) => ({
                              value,
                              label
                            }))"  valueKey="value" labelKey="label"
                              placeholder="Select Price Type" />
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MultiSelectInput from '@/Components/MultiSelectInput.vue'
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const props = defineProps({
  option: { type: Object, default: null },
  branches: { type: Array, default: () => [] },
  typeOptions: { type: Object, default: () => ({}) },
  priceTypes: { type: Object, default: () => ({}) },
})

const baseCurrency = computed(() => page.props.currencySettings?.base_currency ?? null)
const secondaryCurrency = computed(() => page.props.currencySettings?.secondary_currency ?? null)
const hasSecondaryCurrency = computed(() => !!secondaryCurrency.value)
const baseCurrencyCode = computed(() => baseCurrency.value?.code || 'Base')
const secondaryCurrencyCode = computed(() => secondaryCurrency.value?.code || 'Secondary')

const form = useForm({
  name: props.option?.name ?? '',
  branch_ids: props.option?.branch_ids ?? [], 
  type: props.option?.type ?? '',
  is_required: !!(props.option?.is_required ?? false),

  base_price: props.option?.base_price ?? 0,
  secondary_price: props.option?.secondary_price ?? '',
  price_type: props.option?.price_type ?? 'fixed',

  values: props.option?.values?.length
    ? props.option.values.map((row) => ({
      label: row.label,
      base_price: row.base_price ?? 0,
      secondary_price: row.secondary_price ?? '',
      price_type: row.price_type ?? 'fixed',
    }))
    : [],
})

const isEdit = computed(() => !!props.option?.id)

function usesRows(type) {
  return ['select', 'multiple_select', 'checkbox', 'radio_button'].includes(type)
}

function newValueRow() {
  return {
    label: '',
    base_price: 0,
    secondary_price: '',
    price_type: 'fixed',
  }
}

watch(
  () => form.type,
  (value) => {
    if (!value) return
    if (usesRows(value)) {
      form.base_price = 0
      form.secondary_price = ''
      form.price_type = 'fixed'
      if (!form.values.length) form.values = [newValueRow()]
    } else {
      form.values = []
    }
  }
)

function addValueRow() {
  form.values.push(newValueRow())
}

function removeValueRow(index) {
  form.values.splice(index, 1)
}

function normalizedPayload(data) {
  return {
    ...data,
    branch_ids: Array.isArray(data.branch_ids)
  ? data.branch_ids.map(b => typeof b === 'object' ? b.id : b)
  : [],
    is_required: data.is_required ? 1 : 0,
    base_price: usesRows(data.type) ? 0 : (data.base_price || 0),
    secondary_price: usesRows(data.type)
      ? null
      : (hasSecondaryCurrency.value ? (data.secondary_price || null) : null),
    price_type: usesRows(data.type) ? 'fixed' : (data.price_type || 'fixed'),
    values: usesRows(data.type)
      ? (data.values || []).map((row) => ({
        label: row.label || '',
        base_price: row.base_price || 0,
        secondary_price: hasSecondaryCurrency.value ? (row.secondary_price || null) : null,
        price_type: row.price_type || 'fixed',
      }))
      : [],
  }
}

function submit() {
  if (isEdit.value) {
    form
      .transform((data) => ({
        ...normalizedPayload(data),
        _method: 'PUT',
      }))
      .post(route('vendor.options.update', props.option.id), {
        preserveScroll: true,
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong.'

          alertError(message)
        } 
      })
    return
  }

  form
    .transform((data) => normalizedPayload(data))
    .post(route('vendor.options.store'), {
      preserveScroll: true,
      onError: (errors) => {
        const message =
          errors?.general ||
          Object.values(errors)?.flat()?.[0] ||
          'Something went wrong.'

        alertError(message)
      } 
    })
}
</script>

<style scoped>

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1.4fr;
  gap: 40px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.alert-orange {
  --bs-alert-color: #7c2d12;          
  --bs-alert-bg: #ffedd5;              
  --bs-alert-border-color: #ffdcb6;  
  --bs-alert-link-color: #9a3412;
}


.formControl {
  height: 44px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
}

.formControl:focus {
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
}

/* Table Styling */
.option-values-table th,
.option-values-table td {
  vertical-align: middle;
}

.table-responsive {
  overflow: visible !important;
}

.table>:not(caption)>*>* {
  padding: .3rem .3rem;
  background-color: transparent;
}

.drag-cell {
  color: #6b7280;
  font-size: 18px;
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
@media (max-width: 1199px) {
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

  .card-header{
    padding: 20px 16px;
  }
}
</style>