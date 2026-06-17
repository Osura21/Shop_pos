<template>
  <Head :title="isEdit ? 'Update Ingredient' : 'Create Ingredient'" />

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
              <i class="bi bi-cup-hot me-2 text-warning"></i>
              {{ isEdit ? 'Update Ingredient' : 'Create Ingredient' }}
            </h1>
            <p class="header-subtitle">
              Manage ingredient details, stock levels and cost pricing.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.ingredients.index')" class="btn btn-ghost">
              Cancel
            </Link>
            <button
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Ingredient' : 'Create Ingredient') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Ingredient Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-lg-6">
                    <Input
                      id="name"
                      label="Name (English)"
                      v-model="form.name"
                      placeholder="Ingredient name"
                      :error="form.errors.name"
                    />
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label  formLabel">Branch</label>
                      <MultiSelectInput v-model="form.branch_ids" :options="branches" labelKey="name" valueKey="id"
                      placeholder="Select Branch" />
                    <div v-if="form.errors.branch_ids" class="error-text">{{ form.errors.branch_ids }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label  formLabel">Unit</label>
                    <SelectInput id="unit_id" v-model="form.unit_id" :options="units.map(u => ({
                      ...u,
                      label: `${u.name} (${u.symbol})`
                    }))" valueKey="id" labelKey="label" placeholder="Select Unit" />
                    <div v-if="form.errors.unit_id" class="error-text">{{ form.errors.unit_id }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <Input id="cost_per_unit" :label="`Cost Per Unit (${baseCurrency?.code || 'Base Currency'})`"
                      v-model="form.cost_per_unit"
                      :placeholder="`Cost Per Unit (${baseCurrency?.code || 'Base Currency'})`"
                      :error="form.errors.cost_per_unit"
                    />
                  </div>

                  <div v-if="hasSecondaryCurrency" class="col-12 col-lg-6">
                    <Input
                      id="secondary_cost_per_unit"
                      :label="`Cost Per Unit (${secondaryCurrency?.code || 'Secondary Currency'})`"
                      v-model="form.secondary_cost_per_unit"
                      :placeholder="`Cost Per Unit (${secondaryCurrency?.code || 'Secondary Currency'})`"
                      :error="form.errors.secondary_cost_per_unit"
                    />
                  </div>

                  <div v-if="!isEdit" class="col-12 col-lg-6">
                    <Input
                      id="current_stock"
                      label="Current Stock"
                      v-model="form.current_stock"
                      placeholder="Current stock quantity"
                      :error="form.errors.current_stock"
                    />
                  </div>

                  <div class="col-12 col-lg-6">
                    <Input
                      id="alert_quantity"
                      label="Alert Quantity"
                      v-model="form.alert_quantity"
                      placeholder="Low stock alert level"
                      :error="form.errors.alert_quantity"
                    />
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input
                        id="is_active"
                        class="form-check-input"
                        type="checkbox"
                        v-model="form.is_active"
                      >
                      <label class="form-check-label" for="is_active">Active Ingredient</label>
                    </div>
                  </div>
                </div>

                <div v-if="form.errors.general" class="alert alert-danger mt-3">
                  {{ form.errors.general }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue';
import Input from '@/Components/InputField.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MultiSelectInput from '@/Components/MultiSelectInput.vue'

export default {
  name: 'IngredientCreateUpdate',
  layout: VendorAdminLayout,
  components: { Head, Link, Input, SelectInput, MultiSelectInput },
  props: {
    ingredient: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: useForm({
        name: this.ingredient?.name ?? '',
        branch_ids: this.ingredient?.branch_ids ?? [],
        unit_id: this.ingredient?.unit_id ?? '',
        current_stock: this.ingredient?.current_stock ?? '0',
        alert_quantity: this.ingredient?.alert_quantity ?? '0',
        cost_per_unit: this.ingredient?.cost_per_unit ?? '0',
        secondary_cost_per_unit: this.ingredient?.secondary_cost_per_unit ?? '',
        is_active: !!(this.ingredient?.is_active ?? true),
      }),
    }
  },
 computed: {
  isEdit() {
    return !!this.ingredient?.id
  },
  page() {
    return usePage()
  },
  baseCurrency() {
    return this.page.props.currencySettings?.base_currency ?? null
  },
  secondaryCurrency() {
    return this.page.props.currencySettings?.secondary_currency ?? null
  },
  hasSecondaryCurrency() {
    return !!this.secondaryCurrency
  },
},
  methods: {
    nullable(value) {
      return value === '' || value === undefined ? null : value
    },
    submit() {
      const options = { preserveScroll: true, onSuccess: () => {router.visit(route('vendor.ingredients.index'))},
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong.'

          alertError(message)
        }
     }
      const payload = (data) => ({
        ...data,
        branch_ids: data.branch_ids ?? [],
        unit_id: this.nullable(data.unit_id),
        current_stock: data.current_stock || 0,
        alert_quantity: data.alert_quantity || 0,
        cost_per_unit: data.cost_per_unit || 0,
        secondary_cost_per_unit: this.hasSecondaryCurrency
          ? this.nullable(data.secondary_cost_per_unit)
          : null,
        is_active: data.is_active ? 1 : 0,
      })

      if (this.isEdit) {
        this.form.transform((data) => ({ ...payload(data), _method: 'PUT' }))
          .post(route('vendor.ingredients.update', this.ingredient.id), options)
        return
      }

      this.form.transform(payload).post(route('vendor.ingredients.store'), options)
    },
  },
}
</script>

<style scoped>

.error-text {
  font-size: 12px;
  color: #dc2626;
  margin-top: 4px;
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

@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 640px) {
  .form-container { padding: 24px 16px; }
  .header-title { font-size: 24px; }
  .form-card { border-radius: 12px; }
  .card-header{ padding: 20px 16px; }
}
</style>
