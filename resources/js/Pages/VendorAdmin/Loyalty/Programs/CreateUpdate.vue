<template>
  <Head :title="isEdit ? 'Edit Loyalty Program' : 'Create Loyalty Program'" />

  <div class="form-container">
    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title"><i class="bi bi- me-2 text-warning"></i>{{ isEdit ? 'Edit Loyalty Program' : 'Create Loyalty Program' }}</h1>
            <p class="header-subtitle">Base currency: <strong>{{ baseCurrencyCode }}</strong><template v-if="secondaryCurrencyCode"> · Secondary currency: <strong>{{ secondaryCurrencyCode }}</strong></template></p>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-ghost" type="button" @click="goBack">Cancel</button>
            <button class="btn btn-primary-modern" type="button" :disabled="form.processing" @click="submit">{{ form.processing ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}</button>
          </div>
        </div>
      </div>

      <div class="form-card">
        <div class="card-accent-line"></div>
        <div class="d-flex align-items-center gap-2 card-header">
          <i class="bi bi-info-circle"></i>
          <h2 class="card-title cardTitle">Loyalty Program Information</h2>
        </div>
        <div class="card-body formCardBody">
          <div class="form-grid">
            <InputField id="name" v-model="form.name" label="Name" placeholder="Program name" :error="form.errors.name" is-required />
            <InputField id="earning_rate" v-model="form.earning_rate" type="number" label="Earning Rate" :placeholder="`Points per ${baseCurrencyCode} 1.000`" :error="form.errors.earning_rate" is-required />
            <InputField id="points_expire_after_days" v-model="form.points_expire_after_days" type="number" label="Points Expire After" placeholder="Days" :error="form.errors.points_expire_after_days" />
            <label class="checkbox-line"><input v-model="form.is_active" type="checkbox"><span>Active</span></label>
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
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({ program: { type: Object, default: null } })
const page = usePage()
const isEdit = computed(() => !!props.program?.id)
const baseCurrencyCode = computed(() => page.props.currencySettings?.base_currency?.code || 'LKR')
const secondaryCurrencyCode = computed(() => page.props.currencySettings?.secondary_currency?.code || '')

const form = useForm({
  name: props.program?.name ?? '',
  earning_rate: props.program?.earning_rate ?? '',
  points_expire_after_days: props.program?.points_expire_after_days ?? '',
  is_active: props.program?.is_active ?? true,
})

function nullable(value) { return value === '' || value === undefined ? null : value }
function goBack() { router.visit(route('vendor.loyalty.programs.index')) }
function submit() {
  form.transform((data) => ({ ...data, points_expire_after_days: nullable(data.points_expire_after_days), is_active: Boolean(data.is_active) }))
  if (isEdit.value) form.put(route('vendor.loyalty.programs.update', props.program.id), {
    onSuccess: goBack,
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong.'

      alertError(message)
    }
  })
  else form.post(route('vendor.loyalty.programs.store'), {
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
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.checkbox-line {
  display: inline-flex;
  align-items: center;
  gap: .6rem;
  font-weight: 800;
  color: #334155;
  margin-top: 1.7rem;
}

.checkbox-line input {
  width: 18px;
  height: 18px;
  accent-color: #f97316;
}

@media (max-width: 760px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
