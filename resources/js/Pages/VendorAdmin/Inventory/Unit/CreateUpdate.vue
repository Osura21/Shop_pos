<template>
  <Head :title="isEdit ? 'Update Unit' : 'Create Unit'" />

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
              <i class="bi bi-rulers me-2 text-warning"></i>
              {{ isEdit ? 'Update Unit' : 'Create Unit' }}
            </h1>
            <p class="header-subtitle">
              Define measurement units for ingredients and stock management.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.units.index')" class="btn btn-ghost">
              Cancel
            </Link>
            <button
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Unit' : 'Create Unit') }}
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
                <h2 class="card-title cardTitle">Unit Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-lg-4">
                    <Input
                      id="name"
                      label="Name (English)"
                      v-model="form.name"
                      placeholder="Unit name"
                      :error="form.errors.name"
                    />
                  </div>

                  <div class="col-12 col-lg-4">
                    <Input
                      id="symbol"
                      label="Symbol (English)"
                      v-model="form.symbol"
                      placeholder="Symbol (e.g. kg, ml)"
                      :error="form.errors.symbol"
                    />
                  </div>

                  <div class="col-12 col-lg-4">
                    <label class="form-label formLabel">Type</label>
                    <SelectInput id="branch_id" v-model="form.type" :options="Object.entries(typeOptions).map(([value, label]) => ({
                      value,
                      label
                    }))" valueKey="value" labelKey="label" placeholder="Select Type" />
                    <div v-if="form.errors.type" class="error-text">
                      {{ form.errors.type }}
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input
                        id="is_active"
                        class="form-check-input"
                        type="checkbox"
                        v-model="form.is_active"
                      />
                      <label class="form-check-label" for="is_active"
                        >Active Unit</label
                      >
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
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import VendorAdminLayout from "@/Layouts/VendorAdminLayout.vue";
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import Input from "@/Components/InputField.vue";
import SelectInput from '@/Components/SelectInput.vue';

export default {
    name: "UnitCreateUpdate",
    layout: VendorAdminLayout,
    components: { Head, Link, Input, SelectInput },
    props: {
        unit: { type: Object, default: null },
        typeOptions: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            form: useForm({
                name: this.unit?.name ?? "",
                symbol: this.unit?.symbol ?? "",
                type: this.unit?.type ?? "",
                is_active: !!(this.unit?.is_active ?? true),
            }),
        };
    },
    computed: {
        isEdit() {
            return !!this.unit?.id;
        },
    },
    methods: {
        submit() {
            const options = {
                preserveScroll: true,
                onSuccess: () => router.visit(route("vendor.units.index")),
                onError: (errors) => {
                const message =
                  errors?.general ||
                  Object.values(errors)?.flat()?.[0] ||
                  'Something went wrong.'

                alertError(message)
              }
            };

            if (this.isEdit) {
                this.form
                    .transform((data) => ({
                        ...data,
                        is_active: data.is_active ? 1 : 0,
                        _method: "PUT",
                    }))
                    .post(route("vendor.units.update", this.unit.id), options);
                return;
            }

            this.form
                .transform((data) => ({
                    ...data,
                    is_active: data.is_active ? 1 : 0,
                }))
                .post(route("vendor.units.store"), options);
        },
    },
};
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
