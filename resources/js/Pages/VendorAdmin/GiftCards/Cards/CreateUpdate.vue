<template>
  <Head :title="isEdit ? 'Update Gift Card' : 'Create Gift Card'" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-gift me-2 text-warning"></i>
              {{ isEdit ? 'Update Gift Card' : 'Create Gift Card' }}
            </h1>

            <p class="header-subtitle">
              Base currency: <strong>{{ baseCurrencyCode }}</strong>
              <template v-if="hasSecondaryCurrency">
                · Secondary currency: <strong>{{ secondaryCurrencyCode }}</strong>
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
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Gift Card' : 'Create Gift Card') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="form.errors.general" class="alert alert-danger mb-3">
        {{ form.errors.general }}
      </div>

      <div class="gift-card-layout">
        <div class="gift-card-layout__left">
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-postcard"></i>
              <h2 class="card-title cardTitle">Gift Card</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="form-grid">
                <div class="form-grid__full">
                  <label class="field-label">Card Code</label>
                  <input
                    v-model="form.code"
                    type="text"
                    class="form-control fancy-input formControl"
                    placeholder="Auto generated if left empty"
                    :disabled="isEdit"
                  >
                  <div v-if="form.errors.code" class="error-text">
                    {{ form.errors.code }}
                  </div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Initial Balance ({{ baseCurrencyCode }})</label>
                  <input
                    v-model="form.initial_balance"
                    type="number"
                    min="0"
                    step="0.001"
                    class="form-control fancy-input formControl"
                    placeholder="Initial Balance"
                    :disabled="isEdit"
                  >
                  <div v-if="form.errors.initial_balance" class="error-text">
                    {{ form.errors.initial_balance }}
                  </div>
                </div>

                <div v-if="hasSecondaryCurrency" class="form-grid__half">
                  <label class="field-label">Initial Balance ({{ secondaryCurrencyCode }})</label>
                  <input
                    v-model="form.secondary_initial_balance"
                    type="number"
                    min="0"
                    step="0.001"
                    class="form-control fancy-input formControl"
                    placeholder="Secondary Initial Balance"
                    :disabled="isEdit"
                  >
                  <div v-if="form.errors.secondary_initial_balance" class="error-text">
                    {{ form.errors.secondary_initial_balance }}
                  </div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Expiry Date</label>
                  <DatePicker v-model="form.expires_at" />
                  <div v-if="form.errors.expires_at" class="error-text">
                    {{ form.errors.expires_at }}
                  </div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Status</label>
                  <SelectInput
                    id="status"
                    v-model="form.status"
                    :options="statusOptionRows"
                    valueKey="value"
                    labelKey="label"
                    placeholder="Status"
                  />
                  <div v-if="form.errors.status" class="error-text">
                    {{ form.errors.status }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-card-text"></i>
              <h2 class="card-title cardTitle">Notes</h2>
            </div>

            <div class="card-body formCardBody">
              <label class="field-label">Notes</label>
              <textarea
                v-model="form.notes"
                rows="8"
                class="form-control fancy-input formControl fancy-textarea"
                placeholder="Notes"
              ></textarea>
              <div v-if="form.errors.notes" class="error-text">
                {{ form.errors.notes }}
              </div>
            </div>
          </div>
        </div>

        <div class="gift-card-layout__right">
          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-sliders"></i>
              <h2 class="card-title cardTitle">Settings</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="settings-grid">
                <div>
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

                <div>
                  <label class="field-label">Customer</label>
                  <SelectInput
                    id="customer_id"
                    v-model="form.customer_id"
                    :options="customerOptions"
                    valueKey="id"
                    labelKey="name"
                    placeholder="Customer"
                  />
                  <div v-if="form.errors.customer_id" class="error-text">
                    {{ form.errors.customer_id }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="isEdit" class="form-card">
            <div class="card-accent-line"></div>

            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-cash-stack"></i>
              <h2 class="card-title cardTitle">Current Card Info</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="summary-row">
                <span>Code</span>
                <strong>{{ card?.code || '-' }}</strong>
              </div>

              <div class="summary-row">
                <span>Initial Balance</span>
                <strong>{{ baseCurrencyCode }} {{ card?.initial_balance ?? '0.000' }}</strong>
              </div>

              <div class="summary-row">
                <span>Current Balance</span>
                <strong>{{ baseCurrencyCode }} {{ card?.current_balance ?? '0.000' }}</strong>
              </div>

              <template v-if="hasSecondaryCurrency">
                <div class="summary-row">
                  <span>Secondary Initial</span>
                  <strong>{{ secondaryCurrencyCode }} {{ card?.secondary_initial_balance ?? '0.000' }}</strong>
                </div>

                <div class="summary-row">
                  <span>Secondary Current</span>
                  <strong>{{ secondaryCurrencyCode }} {{ card?.secondary_current_balance ?? '0.000' }}</strong>
                </div>
              </template>
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
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import DatePicker from '@/Components/DatePicker.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  card: { type: Object, default: null },
  branches: { type: Array, default: () => [] },
  customers: { type: Array, default: () => [] },
  statusOptions: { type: Object, default: () => ({}) },
})

const page = usePage()

const isEdit = computed(() => !!props.card?.id)

const baseCurrencyCode = computed(() => {
  return page.props.currencySettings?.base_currency?.code || 'LKR'
})

const secondaryCurrencyCode = computed(() => {
  return page.props.currencySettings?.secondary_currency?.code || ''
})

const hasSecondaryCurrency = computed(() => !!secondaryCurrencyCode.value)

const statusOptionRows = computed(() => {
  return Object.entries(props.statusOptions || {}).map(([value, label]) => ({
    value,
    label,
  }))
})

const branchOptions = computed(() => [
  { id: '', name: 'No Branch' },
  ...props.branches,
])

const customerOptions = computed(() => [
  { id: '', name: 'No Customer' },
  ...props.customers,
])

function dateValue(value) {
  if (!value) return ''
  return String(value).slice(0, 10)
}

const form = useForm({
  code: props.card?.code ?? '',
  initial_balance: props.card?.initial_balance ?? '',
  secondary_initial_balance: props.card?.secondary_initial_balance ?? '',
  expires_at: dateValue(props.card?.expires_at),
  branch_id: props.card?.branch_id ?? '',
  customer_id: props.card?.customer_id ?? '',
  status: props.card?.status ?? 'active',
  notes: props.card?.notes ?? '',
})

function nullable(value) {
  return value === '' || value === undefined ? null : value
}

function buildPayload(data) {
  return {
    ...data,
    branch_id: nullable(data.branch_id),
    customer_id: nullable(data.customer_id),
    expires_at: nullable(data.expires_at),
    initial_balance: data.initial_balance || 0,
    secondary_initial_balance: hasSecondaryCurrency.value
      ? (data.secondary_initial_balance || 0)
      : null,
  }
}

function goBack() {
  router.visit(route('vendor.gift-cards.index'))
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('vendor.gift-cards.index'))
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
      .put(route('vendor.gift-cards.update', props.card.id), options)

    return
  }

  form
    .transform((data) => buildPayload(data))
    .post(route('vendor.gift-cards.store'), options)
}
</script>

<style scoped>
.gift-card-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
  gap: 1.5rem;
}

.gift-card-layout__left,
.gift-card-layout__right {
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

.settings-grid {
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

.fancy-textarea {
  resize: vertical;
  min-height: 150px;
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
  .gift-card-layout {
    grid-template-columns: 1fr;
  }

  .gift-card-layout__right {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .form-grid,
  .gift-card-layout__right {
    grid-template-columns: 1fr;
  }

  .form-grid__full {
    grid-column: auto;
  }
}
</style>