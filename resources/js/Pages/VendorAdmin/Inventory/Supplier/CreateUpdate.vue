<template>
  <Head :title="isEdit ? 'Update Supplier' : 'Create Supplier'" />

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
              <i class="bi bi-cart3 me-2 text-warning"></i>
              {{ isEdit ? 'Update Supplier' : 'Create Supplier' }}
            </h1>
            <p class="header-subtitle">
              Manage supplier details and contact information.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.suppliers.index')" class="btn btn-ghost">
              Cancel
            </Link>
            <button
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Supplier' : 'Create Supplier') }}
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
                <h2 class="card-title cardTitle">Supplier Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-lg-6">
                    <Input
                      id="name"
                      label="Supplier Name"
                      v-model="form.name"
                      placeholder="Supplier name"
                      :error="form.errors.name"
                    />
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Branch</label>
                    <SelectInput id="branch_id" v-model="form.branch_id" :options="branches"
                      valueKey="id" labelKey="name" placeholder="Select Branch" />
                    <div v-if="form.errors.branch_id" class="error-text">{{ form.errors.branch_id }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <Input id="email" label="Email Address" type="email" v-model="form.email"
                      placeholder="supplier@email.com" :error="form.errors.email" />
                  </div>

                  <div class="col-12 col-lg-6">
                    <Input
                      id="phone"
                      label="Phone Number"
                      v-model="form.phone"
                      placeholder="Phone number"
                      :error="form.errors.phone"
                    />
                  </div>

                  <div class="col-12">
                    <Input
                      id="address"
                      label="Address"
                      v-model="form.address"
                      placeholder="Full address"
                      :error="form.errors.address"
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
                      <label class="form-check-label" for="is_active">Active Supplier</label>
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
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import Input from '@/Components/InputField.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'

export default {
  name: 'SupplierCreateUpdate',
  layout: VendorAdminLayout,
  components: { Head, Link, Input, SelectInput },
  props: {
    supplier: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: useForm({
        name: this.supplier?.name ?? '',
        branch_id: this.supplier?.branch_id ?? '',
        email: this.supplier?.email ?? '',
        phone: this.supplier?.phone ?? '',
        address: this.supplier?.address ?? '',
        is_active: !!(this.supplier?.is_active ?? true),
      }),
    }
  },
  computed: {
    isEdit() {
      return !!this.supplier?.id
    },
  },
  methods: {
    nullable(value) {
      return value === '' || value === undefined ? null : value
    },
    submit() {
      const options = {
        preserveScroll: true, onSuccess: () => { router.visit(route('vendor.suppliers.index')) },
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
        branch_id: this.nullable(data.branch_id),
        email: this.nullable(data.email),
        phone: this.nullable(data.phone),
        address: this.nullable(data.address),
        is_active: data.is_active ? 1 : 0,
      })

      if (this.isEdit) {
        this.form.transform((data) => ({ ...payload(data), _method: 'PUT' }))
          .post(route('vendor.suppliers.update', this.supplier.id), options)
        return
      }

      this.form.transform(payload).post(route('vendor.suppliers.store'), options)
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
