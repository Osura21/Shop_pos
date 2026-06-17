<template>
    <Head :title="isEdit ? 'Edit Loyalty Tier' : 'Create Loyalty Tier'" />
    <div class="form-container">
        <div class="form-wrapper formWrapper">
            <div class="form-header formHeader">
                <div
                    class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3"
                >
                    <h1 class="header-title">
                        <i class="bi bi- me-2 text-warning"></i
                        >{{
                            isEdit ? "Edit Loyalty Tier" : "Create Loyalty Tier"
                        }}
                    </h1>
                    <div class="d-flex gap-2">
                        <button
                            class="btn btn-ghost"
                            type="button"
                            @click="goBack"
                        >
                            Cancel</button
                        ><button
                            class="btn btn-primary-modern"
                            type="button"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            {{
                                form.processing
                                    ? "Saving..."
                                    : isEdit
                                      ? "Update"
                                      : "Create"
                            }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="tier-layout">
                <div class="form-card">
                    <div class="card-accent-line"></div>
                    <div class="d-flex align-items-center gap-2 card-header">
                        <i class="bi bi-info-circle"></i>
                        <h2 class="card-title cardTitle">
                            Loyalty Tier Information
                        </h2>
                    </div>
                    <div class="card-body formCardBody form-grid">
                        <InputField
                            id="name"
                            v-model="form.name"
                            label="Name"
                            placeholder="Tier name"
                            :error="form.errors.name"
                            is-required
                        />
                        <div class="full">
                            <label class="field-label">Benefits</label
                            ><textarea
                                v-model="form.benefits"
                                class="form-control fancy-input formControl"
                                rows="7"
                                placeholder="Benefits"
                            ></textarea>
                            <div v-if="form.errors.benefits" class="error-text">
                                {{ form.errors.benefits }}
                            </div>
                        </div>
                        <label class="checkbox-line"
                            ><input
                                v-model="form.is_active"
                                type="checkbox"
                            /><span>Active</span></label
                        >
                    </div>
                </div>
                <div class="side-stack">
                    <div class="form-card">
                        <div class="card-accent-line"></div>
                        <div
                            class="d-flex align-items-center gap-2 card-header"
                        >
                            <i class="bi bi-sliders"></i>
                            <h2 class="card-title cardTitle">
                                Program & Rules
                            </h2>
                        </div>
                        <div class="card-body formCardBody side-grid">
                            <SelectInput
                                id="program"
                                v-model="form.loyalty_program_id"
                                label="Program"
                                :options="programs"
                                value-key="id"
                                label-key="name"
                                placeholder="Program"
                                :error="form.errors.loyalty_program_id"
                            />
                            <InputField
                                id="minimum_spend"
                                v-model="form.minimum_spend"
                                type="number"
                                :label="`Minimum Spend (${baseCurrencyCode})`"
                                placeholder="0.000"
                                :error="form.errors.minimum_spend"
                            />
                            <InputField
                                v-if="secondaryCurrencyCode"
                                id="secondary_minimum_spend"
                                v-model="form.secondary_minimum_spend"
                                type="number"
                                :label="`Minimum Spend (${secondaryCurrencyCode})`"
                                placeholder="0.000"
                                :error="form.errors.secondary_minimum_spend"
                            />
                            <InputField
                                id="multiplier"
                                v-model="form.multiplier"
                                type="number"
                                label="Multiplier"
                                placeholder="1"
                                :error="form.errors.multiplier"
                            />
                            <InputField
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                label="Order"
                                placeholder="0"
                                :error="form.errors.sort_order"
                            />
                        </div>
                    </div>
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
            <img :src="iconPreview" alt="Tier Image" class="avatar-img" />
          </div>

          <div class="fw-semibold mt-2">Upload Tier Image</div>
          <div class="small text-muted mt-1">JPG, PNG or WEBP (max 5MB)</div>
        </div>
      </template>

      <template v-else>
        <div class="avatar-placeholder">
          <i class="bi bi-gem avatar-icon"></i>
          <div class="fw-semibold mt-2">Upload Tier Image</div>
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
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import InputField from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  tier: { type: Object, default: null },
  programs: { type: Array, default: () => [] },
})

const page = usePage()

const isEdit = computed(() => !!props.tier?.id)
const baseCurrencyCode = computed(() => page.props.currencySettings?.base_currency?.code || 'LKR')
const secondaryCurrencyCode = computed(() => page.props.currencySettings?.secondary_currency?.code || '')

const iconInput = ref(null)
const iconPreview = ref(props.tier?.icon_url || '')

const form = useForm({
  loyalty_program_id: props.tier?.loyalty_program_id ?? '',
  name: props.tier?.name ?? '',
  benefits: props.tier?.benefits ?? '',
  minimum_spend: props.tier?.minimum_spend ?? 0,
  secondary_minimum_spend: props.tier?.secondary_minimum_spend ?? '',
  multiplier: props.tier?.multiplier ?? 1,
  sort_order: props.tier?.sort_order ?? 0,
  is_active: props.tier?.is_active ?? true,

  icon: null,
  remove_icon: 0,
})

function nullable(v) {
  return v === '' || v === undefined ? null : v
}

function goBack() {
  router.visit(route('vendor.loyalty.tiers.index'))
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

function submit() {
  form.transform((data) => ({
    ...data,
    _method: isEdit.value ? 'PUT' : undefined,
    secondary_minimum_spend: secondaryCurrencyCode.value
      ? nullable(data.secondary_minimum_spend)
      : null,
    icon: data.icon,
    remove_icon: data.remove_icon ? 1 : 0,
    is_active: Boolean(data.is_active),
  }))

  form.post(
    isEdit.value
      ? route('vendor.loyalty.tiers.update', props.tier.id)
      : route('vendor.loyalty.tiers.store'),
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
.tier-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
    gap: 1.5rem;
}
.side-stack {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.form-grid,
.side-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}
.full {
    grid-column: 1 / -1;
}
.field-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #334155;
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
    font-weight: 800;
    color: #334155;
}
.checkbox-line input {
    width: 18px;
    height: 18px;
    accent-color: #3b82f6;
}
@media (max-width: 1100px) {
    .tier-layout {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 760px) {
    .form-grid,
    .side-grid {
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
