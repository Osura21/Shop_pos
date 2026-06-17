<template>
  <Head :title="isEdit ? 'Update Gift Card Batch' : 'Create Gift Card Batch'" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-layers me-2 text-warning"></i>
              {{ isEdit ? 'Update Gift Card Batch' : 'Create Gift Card Batch' }}
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

            <button
              class="btn btn-primary-modern"
              type="button"
              @click="submit"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Batch' : 'Create Batch') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="form.errors.general" class="alert alert-danger mb-3">
        {{ form.errors.general }}
      </div>

      <div class="batch-layout">
        <div class="batch-layout__left">
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-layers"></i>
              <h2 class="card-title cardTitle">Gift Card Batch</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="form-grid">
                <div class="form-grid__half">
                  <label class="field-label">Batch Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control fancy-input formControl"
                    placeholder="Batch Name"
                  >
                  <div v-if="form.errors.name" class="error-text">
                    {{ form.errors.name }}
                  </div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Prefix</label>
                  <input
                    v-model="form.prefix"
                    type="text"
                    class="form-control fancy-input formControl"
                    placeholder="Prefix"
                    :disabled="isEdit"
                  >
                  <div v-if="form.errors.prefix" class="error-text">
                    {{ form.errors.prefix }}
                  </div>
                </div>

                <div class="form-grid__full">
                  <label class="field-label">Branch</label>
                  <SelectInput
                    id="branch_id"
                    v-model="form.branch_id"
                    :options="branchOptions"
                    valueKey="id"
                    labelKey="name"
                    placeholder="Branch"
                  />
                  <div v-if="form.errors.branch_id" class="error-text">
                    {{ form.errors.branch_id }}
                  </div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Quantity</label>
                  <input
                    v-model="form.quantity"
                    type="number"
                    min="1"
                    step="1"
                    class="form-control fancy-input formControl"
                    placeholder="Quantity"
                    :disabled="isEdit"
                  >
                  <div v-if="form.errors.quantity" class="error-text">
                    {{ form.errors.quantity }}
                  </div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Value ({{ baseCurrencyCode }})</label>
                  <input
                    v-model="form.value"
                    type="number"
                    min="0"
                    step="0.001"
                    class="form-control fancy-input formControl"
                    placeholder="Value"
                    :disabled="isEdit"
                  >
                  <div v-if="form.errors.value" class="error-text">
                    {{ form.errors.value }}
                  </div>
                </div>

                <div v-if="hasSecondaryCurrency" class="form-grid__half">
                  <label class="field-label">Value ({{ secondaryCurrencyCode }})</label>
                  <input
                    v-model="form.secondary_value"
                    type="number"
                    min="0"
                    step="0.001"
                    class="form-control fancy-input formControl"
                    placeholder="Secondary Value"
                    :disabled="isEdit"
                  >
                  <div v-if="form.errors.secondary_value" class="error-text">
                    {{ form.errors.secondary_value }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="batch-layout__right">
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-info-circle"></i>
              <h2 class="card-title cardTitle">Batch Info</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="info-box">
                <div class="info-box__icon">
                  <i class="bi bi-gift"></i>
                </div>

                <h3>{{ isEdit ? 'Batch Update' : 'Batch Generation' }}</h3>

                <p>
                  Gift card batches create multiple gift cards with the same value and branch.
                  Quantity and value are locked after creation to protect generated card balances.
                </p>
              </div>

              <div v-if="isEdit" class="summary-list">
                <div class="summary-row">
                  <span>Batch</span>
                  <strong>{{ batch?.name || '-' }}</strong>
                </div>

                <div class="summary-row">
                  <span>Prefix</span>
                  <strong>{{ batch?.prefix || '-' }}</strong>
                </div>

                <div class="summary-row">
                  <span>Quantity</span>
                  <strong>{{ batch?.quantity ?? '-' }}</strong>
                </div>

                <div class="summary-row">
                  <span>Value</span>
                  <strong>{{ baseCurrencyCode }} {{ batch?.value ?? '0.000' }}</strong>
                </div>

                <div v-if="hasSecondaryCurrency" class="summary-row">
                  <span>Secondary Value</span>
                  <strong>{{ secondaryCurrencyCode }} {{ batch?.secondary_value ?? '0.000' }}</strong>
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
import { computed } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  batch: { type: Object, default: null },
  branches: { type: Array, default: () => [] },
})

const page = usePage()

const isEdit = computed(() => !!props.batch?.id)

const baseCurrencyCode = computed(() => {
  return page.props.currencySettings?.base_currency?.code || 'LKR'
})

const secondaryCurrencyCode = computed(() => {
  return page.props.currencySettings?.secondary_currency?.code || ''
})

const hasSecondaryCurrency = computed(() => !!secondaryCurrencyCode.value)

const branchOptions = computed(() => [
  { id: '', name: 'No Branch' },
  ...props.branches,
])

const form = useForm({
  name: props.batch?.name ?? '',
  prefix: props.batch?.prefix ?? '',
  branch_id: props.batch?.branch_id ?? '',
  quantity: props.batch?.quantity ?? 1,
  value: props.batch?.value ?? '',
  secondary_value: props.batch?.secondary_value ?? '',
})

function nullable(value) {
  return value === '' || value === undefined ? null : value
}

function buildPayload(data) {
  return {
    ...data,
    branch_id: nullable(data.branch_id),
    quantity: data.quantity || 1,
    value: data.value || 0,
    secondary_value: hasSecondaryCurrency.value
      ? (data.secondary_value || 0)
      : null,
  }
}

function goBack() {
  router.visit(route('vendor.gift-cards.batches.index'))
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('vendor.gift-cards.batches.index'))
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
      .put(route('vendor.gift-cards.batches.update', props.batch.id), options)

    return
  }

  form
    .transform((data) => buildPayload(data))
    .post(route('vendor.gift-cards.batches.store'), options)
}
</script>

<style scoped>
.batch-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
  gap: 1.5rem;
}

.batch-layout__left,
.batch-layout__right {
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

.info-box {
  padding: 18px;
  border: 1px solid #edf0f5;
  border-radius: 14px;
  background: #f8fafc;
  text-align: center;
}

.info-box__icon {
  width: 54px;
  height: 54px;
  border-radius: 18px;
  background: #eff6ff;
  color: #3b82f6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  margin-bottom: 12px;
}

.info-box h3 {
  margin: 0 0 8px;
  color: #0f172a;
  font-size: 1rem;
  font-weight: 900;
}

.info-box p {
  margin: 0;
  color: #64748b;
  font-size: 0.88rem;
  line-height: 1.55;
}

.summary-list {
  margin-top: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid #edf0f5;
  color: #64748b;
  font-size: 0.9rem;
}

.summary-row:last-child {
  border-bottom: 0;
}

.summary-row strong {
  color: #0f172a;
  font-weight: 800;
  text-align: right;
}

:deep(.form-control:disabled),
:deep(.formControl:disabled) {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

@media (max-width: 1100px) {
  .batch-layout {
    grid-template-columns: 1fr;
  }

  .batch-layout__right {
    display: grid;
    grid-template-columns: 1fr;
  }
}

@media (max-width: 760px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-grid__full {
    grid-column: auto;
  }
}
</style>
