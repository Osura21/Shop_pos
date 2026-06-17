<template>
  <Head :title="isEdit ? 'Edit Loyalty Promotion' : 'Create Loyalty Promotion'" />
  <div class="form-container">
    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <h1 class="header-title"><i class="bi bi- me-2 promotion-header-icon"></i>{{ isEdit ? 'Edit Loyalty Promotion' : 'Create Loyalty Promotion' }}</h1>
          <div class="d-flex gap-2"><button class="btn btn-ghost" type="button" @click="goBack">Cancel</button><button class="btn btn-primary-modern" type="button" :disabled="form.processing" @click="submit">{{ form.processing ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}</button></div>
        </div>
      </div>

      <div class="promotion-layout">
        <div class="stack">
          <div class="form-card">
            <div class="card-accent-line promotion-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-info-circle"></i><h2 class="card-title cardTitle">Loyalty Promotion Information</h2></div>
            <div class="card-body formCardBody form-grid">
              <InputField id="name" v-model="form.name" label="Name" placeholder="Promotion name" :error="form.errors.name" />
              <SelectInput id="type" v-model="form.type" label="Type" :options="promotionTypes" value-key="value" label-key="label" placeholder="Type" :error="form.errors.type" />
              <div class="full"><label class="field-label">Description</label><textarea v-model="form.description" class="form-control fancy-input formControl" rows="6" placeholder="Description"></textarea></div>
              <SelectInput id="program" v-model="form.loyalty_program_id" label="Program" :options="programs" value-key="id" label-key="name" placeholder="Program" :error="form.errors.loyalty_program_id" />
              <label class="checkbox-line"><input v-model="form.is_active" type="checkbox"><span>Active</span></label>
            </div>
          </div>

          <div class="form-card">
            <div class="card-accent-line promotion-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-funnel"></i><h2 class="card-title cardTitle">Conditions</h2></div>
            <div class="card-body formCardBody form-grid">
              <InputField id="minimum_spend" v-model="form.minimum_spend" type="number" :label="`Minimum Spend (${baseCurrencyCode})`" />
              <MultiSelectInput label="Branches" v-model="form.branch_ids" :options="branches" value-key="id" label-key="name" placeholder="All Branches" />
              <MultiSelectInput label="Available Days" v-model="form.available_days" :options="availableDays" value-key="value" label-key="label" placeholder="Any Day" />
            </div>
          </div>
        </div>

        <div class="stack">
          <div class="form-card">
            <div class="card-accent-line promotion-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-coin"></i><h2 class="card-title cardTitle">Multiplier & Bonus Points</h2></div>
            <div class="card-body formCardBody side-grid">
              <InputField v-if="form.type === 'multiplier'" id="multiplier" v-model="form.multiplier" type="number" label="Multiplier" placeholder="2" />
              <InputField v-else id="bonus_points" v-model="form.bonus_points" type="number" label="Bonus Points" placeholder="100" />
            </div>
          </div>
          <div class="form-card">
            <div class="card-accent-line promotion-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-arrow-clockwise"></i><h2 class="card-title cardTitle">Usage Rules</h2></div>
            <div class="card-body formCardBody side-grid">
              <InputField id="usage_limit" v-model="form.usage_limit" type="number" label="Usage Limit" />
              <InputField id="per_customer_limit" v-model="form.per_customer_limit" type="number" label="Per Customer Limit" />
            </div>
          </div>
          <div class="form-card">
            <div class="card-accent-line promotion-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-calendar2-check"></i><h2 class="card-title cardTitle">Availability & Schedule</h2></div>
            <div class="card-body formCardBody side-grid">
              <div>
                <label class="field-label">Start At</label>
                <DatePicker v-model="form.starts_at" placeholder="Select Start Date" />
                <div v-if="form.errors.starts_at" class="error-text">{{ form.errors.starts_at }}</div>
              </div>
              <div>
                <label class="field-label">End At</label>
                <DatePicker v-model="form.ends_at" placeholder="Select End Date" />
                <div v-if="form.errors.ends_at" class="error-text">{{ form.errors.ends_at }}</div>
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
import InputField from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MultiSelectInput from '@/Components/MultiSelectInput.vue'
import DatePicker from '@/Components/DatePicker.vue'

defineOptions({ layout: VendorAdminLayout })
const props = defineProps({ promotion: { type: Object, default: null }, programs: Array, branches: Array, availableDays: Array, promotionTypes: Array })
const page = usePage()
const isEdit = computed(() => !!props.promotion?.id)
const baseCurrencyCode = computed(() => page.props.currencySettings?.base_currency?.code || 'LKR')
function arr(v) { return Array.isArray(v) ? v : [] }
function date(v) { return v ? String(v).slice(0, 10) : '' }
const form = useForm({ loyalty_program_id: props.promotion?.loyalty_program_id ?? '', name: props.promotion?.name ?? '', description: props.promotion?.description ?? '', type: props.promotion?.type ?? 'multiplier', multiplier: props.promotion?.multiplier ?? 2, bonus_points: props.promotion?.bonus_points ?? '', minimum_spend: props.promotion?.minimum_spend ?? '', branch_ids: arr(props.promotion?.branch_ids), available_days: arr(props.promotion?.available_days), starts_at: date(props.promotion?.starts_at), ends_at: date(props.promotion?.ends_at), usage_limit: props.promotion?.usage_limit ?? '', per_customer_limit: props.promotion?.per_customer_limit ?? '', is_active: props.promotion?.is_active ?? true })
function nullable(v) { return v === '' || v === undefined ? null : v }
function payload(data) { return { ...data, multiplier: data.type === 'multiplier' ? nullable(data.multiplier) : null, bonus_points: data.type !== 'multiplier' ? nullable(data.bonus_points) : null, minimum_spend: nullable(data.minimum_spend), starts_at: nullable(data.starts_at), ends_at: nullable(data.ends_at), usage_limit: nullable(data.usage_limit), per_customer_limit: nullable(data.per_customer_limit), is_active: Boolean(data.is_active) } }
function goBack() { router.visit(route('vendor.loyalty.promotions.index')) }
function submit() {
  form.transform(payload); if (isEdit.value) form.put(route('vendor.loyalty.promotions.update', props.promotion.id), {
    onSuccess: goBack,
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong.'

      alertError(message)
    }
  }); else form.post(route('vendor.loyalty.promotions.store'), {
    onSuccess: goBack,
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
.promotion-layout { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(360px,.9fr); gap:1.5rem; }
.stack { display:flex; flex-direction:column; gap:1.5rem; }
.form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
.side-grid { display:grid; grid-template-columns:1fr; gap:1rem; }
.full { grid-column:1 / -1; }
.field-label { display:block; margin-bottom:.45rem; color:#334155; font-size:.82rem; font-weight:800; }
.checkbox-line { display:inline-flex; align-items:center; gap:.6rem; font-weight:800; color:#334155; }
.checkbox-line input { width:18px; height:18px; accent-color:#3b82f6; }
@media (max-width:1100px) { .promotion-layout { grid-template-columns:1fr; } }
@media (max-width:760px) { .form-grid { grid-template-columns:1fr; } }
.error-text {
  margin-top: .35rem;
  color: #dc2626;
  font-size: .78rem;
  font-weight: 600;
}

.promotion-header-icon {
  color: #3b82f6;
}

.promotion-accent-line {
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
  box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.08);
}
</style>
