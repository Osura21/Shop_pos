<template>

  <Head :title="isEdit ? 'Edit Loyalty Reward' : 'Create Loyalty Reward'" />
  <div class="form-container">
    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <h1 class="header-title"><i class="bi  me-2 text-warning"></i>{{ isEdit ? 'Edit Loyalty Reward' :
            'Create Loyalty Reward' }}</h1>
          <div class="d-flex gap-2"><button class="btn btn-ghost" type="button" @click="goBack">Cancel</button><button
              class="btn btn-primary-modern" type="button" :disabled="form.processing" @click="submit">{{
                form.processing ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}</button></div>
        </div>
      </div>

      <div class="reward-layout">
        <div class="stack">
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-info-circle"></i>
              <h2 class="card-title cardTitle">Loyalty Reward Information</h2>
            </div>
            <div class="card-body formCardBody form-grid">
              <InputField id="name" v-model="form.name" label="Name" placeholder="Reward name"
                :error="form.errors.name" />
              <SelectInput id="type" v-model="form.type" label="Type" :options="rewardTypes" value-key="value"
                label-key="label" placeholder="Type" :error="form.errors.type" />
              <div class="full"><label class="field-label">Description</label><textarea v-model="form.description"
                  class="form-control fancy-input formControl" rows="5" placeholder="Description"></textarea></div>
              <SelectInput id="program" v-model="form.loyalty_program_id" label="Program" :options="programs"
                value-key="id" label-key="name" placeholder="Program" :error="form.errors.loyalty_program_id" />
              <SelectInput id="tier" v-model="form.loyalty_tier_id" label="Tier" :options="tierOptions" value-key="id"
                label-key="name" placeholder="All Tiers" />
              <label class="checkbox-line"><input v-model="form.is_active" type="checkbox"><span>Active</span></label>
            </div>
          </div>

          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-gear"></i>
              <h2 class="card-title cardTitle">Configuration</h2>
            </div>
            <div class="card-body formCardBody form-grid">
              <template v-if="['discount', 'voucher_code'].includes(form.type)">
                <SelectInput id="value_type" v-model="form.value_type" label="Value Type" :options="valueTypes"
                  value-key="value" label-key="label" placeholder="Value Type" />
                <InputField id="value" v-model="form.value" type="number"
                  :label="form.value_type === 'percentage' ? 'Value (%)' : `Value (${baseCurrencyCode})`"
                  placeholder="Value" />
                <InputField v-if="secondaryCurrencyCode && form.value_type === 'fixed'" id="secondary_value"
                  v-model="form.secondary_value" type="number" :label="`Value (${secondaryCurrencyCode})`"
                  placeholder="Secondary value" />
                <InputField v-if="form.type === 'voucher_code'" id="code_prefix" v-model="form.code_prefix"
                  label="Code Prefix" placeholder="LOY" />
                <InputField id="expires_in_days" v-model="form.expires_in_days" type="number" label="Expires In Days"
                  placeholder="Days" />
              </template>
              <template v-else-if="form.type === 'free_item'">
                <SelectInput id="product" v-model="form.product_id" label="Product" :options="products" value-key="id"
                  label-key="name" placeholder="Product" />
                <InputField id="quantity" v-model="form.quantity" type="number" label="Quantity" placeholder="1" />
              </template>
              <template v-else>
                <SelectInput id="target_tier" v-model="form.target_tier_id" label="Target Tier" :options="tiers"
                  value-key="id" label-key="name" placeholder="Target Tier" />
              </template>
            </div>
          </div>

          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-funnel"></i>
              <h2 class="card-title cardTitle">Conditions</h2>
            </div>
            <div class="card-body formCardBody form-grid">
              <InputField id="minimum_spend" v-model="form.minimum_spend" type="number"
                :label="`Minimum Spend (${baseCurrencyCode})`" placeholder="0.000" />
              <MultiSelectInput label="Branches" v-model="form.branch_ids" :options="branches" value-key="id"
                label-key="name" placeholder="All Branches" />
              <MultiSelectInput label="Available Days" v-model="form.available_days" :options="availableDays"
                value-key="value" label-key="label" placeholder="Any Day" />
            </div>
          </div>
        </div>

        <div class="stack">
          <div class="form-card">
  <div class="card-accent-line"></div>

  <div class="d-flex align-items-center gap-2 card-header">
    <i class="bi bi-image"></i>
    <h2 class="card-title cardTitle">Media</h2>
  </div>

  <div class="card-body formCardBody">
    <div class="avatar-box" @click="triggerIconSelect">
      <template v-if="iconPreview">
        <div class="avatar-placeholder">
          <div class="avatar-image-wrapper">
            <img :src="iconPreview" alt="Reward Image" class="avatar-img" />
          </div>

          <div class="fw-semibold mt-2">Upload Reward Image</div>
          <div class="small text-muted mt-1">JPG, PNG or WEBP (max 5MB)</div>
        </div>
      </template>

      <template v-else>
        <div class="avatar-placeholder">
          <i class="bi  avatar-icon"></i>
          <div class="fw-semibold mt-2">Upload Reward Image</div>
          <div class="small text-muted mt-1">JPG, PNG or WEBP (max 5MB)</div>
        </div>
      </template>
    </div>

    <input
      ref="iconInput"
      type="file"
      class="d-none"
      accept=".jpg,.jpeg,.png,.webp"
      @change="onIconChange"
    >

    <div class="d-flex gap-2 mt-3">
      <button type="button" class="btn btn-outline-secondary btn-sm" @click="triggerIconSelect">
        Upload Image
      </button>

      <button
        v-if="iconPreview"
        type="button"
        class="btn btn-outline-danger btn-sm"
        @click="clearIcon"
      >
        Remove
      </button>
    </div>

    <div v-if="form.errors.icon" class="error-text mt-2">
      {{ form.errors.icon }}
    </div>
  </div>
</div>
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-coin"></i>
              <h2 class="card-title cardTitle">Reward Value & Points</h2>
            </div>
            <div class="card-body formCardBody side-grid">
              <InputField id="points_cost" v-model="form.points_cost" type="number" label="Points Cost"
                placeholder="100" :error="form.errors.points_cost" />
              <InputField id="minimum_order_total" v-model="form.minimum_order_total" type="number"
                :label="`Minimum Order Total (${baseCurrencyCode})`" placeholder="0.000" />
              <InputField id="maximum_order_total" v-model="form.maximum_order_total" type="number"
                :label="`Maximum Order Total (${baseCurrencyCode})`" placeholder="0.000" />
            </div>
          </div>
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-arrow-clockwise"></i>
              <h2 class="card-title cardTitle">Usage & Redemption Rules</h2>
            </div>
            <div class="card-body formCardBody side-grid">
              <InputField id="max_redemptions_per_order" v-model="form.max_redemptions_per_order" type="number"
                label="Maximum Redemptions Per Order" />
              <InputField id="usage_limit" v-model="form.usage_limit" type="number" label="Usage Limit" />
              <InputField id="per_customer_limit" v-model="form.per_customer_limit" type="number"
                label="Per Customer Limit" />
            </div>
          </div>
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header"><i class="bi bi-calendar2-check"></i>
              <h2 class="card-title cardTitle">Availability & Schedule</h2>
            </div>
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
import { computed, ref, watch, onBeforeUnmount } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import InputField from '@/Components/InputField.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'
import MultiSelectInput from '@/Components/MultiSelectInput.vue'
import DatePicker from '@/Components/DatePicker.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  reward: { type: Object, default: null },
  programs: Array,
  tiers: Array,
  products: Array,
  branches: Array,
  availableDays: Array,
  rewardTypes: Array,
  valueTypes: Array,
})

const page = usePage()

const isEdit = computed(() => !!props.reward?.id)
const baseCurrencyCode = computed(() => page.props.currencySettings?.base_currency?.code || 'LKR')
const secondaryCurrencyCode = computed(() => page.props.currencySettings?.secondary_currency?.code || '')

const iconInput = ref(null)
const iconPreview = ref(props.reward?.icon_url || '')

function arr(v) {
  return Array.isArray(v) ? v : []
}

function date(v) {
  return v ? String(v).slice(0, 10) : ''
}

const form = useForm({
  loyalty_program_id: props.reward?.loyalty_program_id ?? '',
  loyalty_tier_id: props.reward?.loyalty_tier_id ?? '',
  name: props.reward?.name ?? '',
  description: props.reward?.description ?? '',
  type: props.reward?.type ?? 'discount',
  points_cost: props.reward?.points_cost ?? '',

  value_type: props.reward?.value_type ?? 'fixed',
  value: props.reward?.value ?? '',
  secondary_value: props.reward?.secondary_value ?? '',

  product_id: props.reward?.product_id ?? '',
  quantity: props.reward?.quantity ?? 1,
  target_tier_id: props.reward?.target_tier_id ?? '',
  code_prefix: props.reward?.code_prefix ?? 'LOY',
  expires_in_days: props.reward?.expires_in_days ?? '',

  minimum_order_total: props.reward?.minimum_order_total ?? '',
  maximum_order_total: props.reward?.maximum_order_total ?? '',
  minimum_spend: props.reward?.minimum_spend ?? '',

  branch_ids: arr(props.reward?.branch_ids),
  available_days: arr(props.reward?.available_days),

  starts_at: date(props.reward?.starts_at),
  ends_at: date(props.reward?.ends_at),

  max_redemptions_per_order: props.reward?.max_redemptions_per_order ?? '',
  usage_limit: props.reward?.usage_limit ?? '',
  per_customer_limit: props.reward?.per_customer_limit ?? '',

  is_active: props.reward?.is_active ?? true,

  icon: null,
  remove_icon: 0,
})

const tierOptions = computed(() => {
  const allTiers = props.tiers || []
  const programId = form.loyalty_program_id

  const filtered = programId
    ? allTiers.filter(t => String(t.loyalty_program_id) === String(programId))
    : allTiers

  return [{ id: '', name: 'All Tiers' }, ...filtered]
})

watch(() => form.loyalty_program_id, (newProgramId) => {
  if (form.loyalty_tier_id) {
    const tierExistsInProgram = (props.tiers || []).some(
      tier => String(tier.id) === String(form.loyalty_tier_id) &&
              String(tier.loyalty_program_id) === String(newProgramId)
    )
    if (!tierExistsInProgram) {
      form.loyalty_tier_id = ''
    }
  }
})

function nullable(v) {
  return v === '' || v === undefined ? null : v
}

function triggerIconSelect() {
  iconInput.value?.click()
}

function onIconChange(event) {
  const file = event.target.files?.[0]
  if (!file) return

  form.icon = file
  form.remove_icon = 0

  if (iconPreview.value && iconPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(iconPreview.value)
  }

  iconPreview.value = URL.createObjectURL(file)
}

function clearIcon() {
  if (iconPreview.value && iconPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(iconPreview.value)
  }

  iconPreview.value = ''
  form.icon = null
  form.remove_icon = 1

  if (iconInput.value) {
    iconInput.value.value = ''
  }
}

function payload(data) {
  return {
    ...data,
    loyalty_tier_id: nullable(data.loyalty_tier_id),
    value: nullable(data.value),
    secondary_value: secondaryCurrencyCode.value ? nullable(data.secondary_value) : null,
    product_id: nullable(data.product_id),
    target_tier_id: nullable(data.target_tier_id),
    expires_in_days: nullable(data.expires_in_days),
    minimum_order_total: nullable(data.minimum_order_total),
    maximum_order_total: nullable(data.maximum_order_total),
    minimum_spend: nullable(data.minimum_spend),
    starts_at: nullable(data.starts_at),
    ends_at: nullable(data.ends_at),
    max_redemptions_per_order: nullable(data.max_redemptions_per_order),
    usage_limit: nullable(data.usage_limit),
    per_customer_limit: nullable(data.per_customer_limit),
    icon: data.icon,
    remove_icon: data.remove_icon ? 1 : 0,
    is_active: Boolean(data.is_active),
  }
}

function goBack() {
  router.visit(route('vendor.loyalty.rewards.index'))
}

function submit() {
  form.transform((data) => ({
    ...payload(data),
    _method: isEdit.value ? 'PUT' : undefined,
  }))

  form.post(
    isEdit.value
      ? route('vendor.loyalty.rewards.update', props.reward.id)
      : route('vendor.loyalty.rewards.store'),
    {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: goBack,
      onError: (errors) => {
        const message =
          errors?.general ||
          Object.values(errors)?.flat()?.[0] ||
          'Something went wrong.'

        alertError(message)
      }
    }
  )
}

onBeforeUnmount(() => {
  if (iconPreview.value && iconPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(iconPreview.value)
  }
})
</script>

<style scoped>
.reward-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr);
  gap: 1.5rem;
}

.stack {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.side-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.full {
  grid-column: 1 / -1;
}

.field-label {
  display: block;
  margin-bottom: .45rem;
  color: #334155;
  font-size: .82rem;
  font-weight: 800;
}

.checkbox-line {
  display: inline-flex;
  align-items: center;
  gap: .6rem;
  font-weight: 800;
  color: #334155;
}

.checkbox-line input {
  width: 18px;
  height: 18px;
  accent-color: #3b82f6;
}

@media (max-width:1100px) {
  .reward-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width:760px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
.avatar-box {
  min-height: 220px;
  border: 2px dashed rgba(59, 130, 246, 0.3);
  border-radius: 16px;
  background: #fcfaf7;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-align: center;
  padding: 20px;
}

.avatar-placeholder {
  color: #6b7280;
}

.avatar-icon {
  font-size: 64px;
  color: #2563eb;
}

.avatar-image-wrapper {
  width: 86px;
  height: 86px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  margin: 0 auto;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.error-text {
  margin-top: .35rem;
  color: #dc2626;
  font-size: .78rem;
  font-weight: 600;
}
</style>
