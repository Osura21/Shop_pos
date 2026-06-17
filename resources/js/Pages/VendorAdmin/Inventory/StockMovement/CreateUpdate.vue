<template>
  <Head :title="isEdit ? 'Update Stock Movement' : 'Create Stock Movement'" />

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
              <i class="bi bi-graph-up-arrow me-2 text-warning"></i>
              {{ isEdit ? 'Update Stock Movement' : 'Create Stock Movement' }}
            </h1>
            <p class="header-subtitle">
              Record ingredient stock adjustment or transfer between branches.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.stock-movements.index')" class="btn btn-ghost">
              Cancel
            </Link>
            <button
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Movement' : 'Create Movement') }}
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
                <h2 class="card-title cardTitle">Stock Movement Information</h2>
              </div>
              <div class="card-body  formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Branch</label>
                    <SelectInput id="branch_id" v-model="form.branch_id" :options="branches"
                      valueKey="id" labelKey="name" placeholder="Select Branch" />
                    <div v-if="form.errors.branch_id" class="error-text">{{ form.errors.branch_id }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Movement Type</label>
                    <SelectInput id="type.id" v-model="form.type" :options="Object.entries(typeOptions).map(([value, label]) => ({
                  value,
                  label
                }))"  valueKey="value" labelKey="label"
                  placeholder="Select Type" />
                    <div v-if="form.errors.type" class="error-text">{{ form.errors.type }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Ingredient</label>
                    <SelectInput id="ingredient_id" v-model="form.ingredient_id" :options="filteredIngredients.map(i => ({
                      ...i,
                      label: `${i.name}${i.unit_symbol ? ` (${i.unit_symbol})` : ''}`
                    }))"  valueKey="id" labelKey="label" placeholder="Select Ingredient" />
                    <div v-if="form.errors.ingredient_id" class="error-text">{{ form.errors.ingredient_id }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <Input
                      id="quantity"
                      label="Quantity"
                      v-model="form.quantity"
                      placeholder="Enter quantity"
                      :error="form.errors.quantity"
                    />
                  </div>

                  <div class="col-12">
                    <label class="form-label formLabel">Note</label>
                    <textarea
                      class="form-control formControl"
                      rows="4"
                      v-model="form.note"
                      placeholder="Add a note (optional)"
                    ></textarea>
                    <div v-if="form.errors.note" class="error-text">{{ form.errors.note }}</div>
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
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import Input from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'

export default {
  name: 'StockMovementCreateUpdate',
  layout: VendorAdminLayout,
  components: { Head, Link, Input, SelectInput },
  props: {
    movement: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] },
    typeOptions: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: useForm({
        branch_id: this.movement?.branch_id ?? '',
        ingredient_id: this.movement?.ingredient_id ?? '',
        type: this.movement?.type ?? '',
        quantity: this.movement?.quantity ?? '',
        note: this.movement?.note ?? '',
      }),
    }
  },
  computed: {
    isEdit() {
      return !!this.movement?.id
    },
    filteredIngredients() {
      if (!this.form.branch_id) return this.ingredients
      return this.ingredients.filter((item) => this.matchesBranch(item.branch_ids, this.form.branch_id))
    },
  },
  methods: {
    nullable(value) {
      return value === '' || value === undefined ? null : value
    },
    matchesBranch(branchIds, branchId) {
      if (!Array.isArray(branchIds) || branchIds.length === 0) return true
      return branchIds.some((id) => Number(id) === Number(branchId))
    },
    submit() {
      const options = { preserveScroll: true, onSuccess: () => {router.visit(route('vendor.stock-movements.index'))},
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong.'

          alertError(message)
        } }
      const payload = (data) => ({
        ...data,
        branch_id: this.nullable(data.branch_id),
        ingredient_id: this.nullable(data.ingredient_id),
        quantity: data.quantity || 0,
        note: this.nullable(data.note),
      })

      if (this.isEdit) {
        this.form.transform((data) => ({ ...payload(data), _method: 'PUT' }))
          .post(route('vendor.stock-movements.update', this.movement.id), options)
        return
      }

      this.form.transform(payload).post(route('vendor.stock-movements.store'), options)
    },
  },
}
</script>

<style scoped>

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
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
