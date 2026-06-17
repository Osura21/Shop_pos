<template>

  <Head :title="isEdit ? 'Update Menu' : 'Create Menu'" />

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
              {{ isEdit ? 'Update Menu' : 'Create Menu' }}
            </h1>
            <p class="header-subtitle">
              Create or update menu categories for your restaurant.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.menus.index')" class="btn btn-ghost">
            Cancel
            </Link>
            <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Menu' : 'Create Menu') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div v-if="form.errors.general" class="alert alert-danger">
          {{ form.errors.general }}
        </div>

        <div class="form-grid">
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Menu Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <Input id="name" label="Menu Name" v-model="form.name" placeholder="Menu name"
                      :error="form.errors.name" />
                  </div>

                  <div class="col-12 col-md-6">
                    <!-- <SelectInput id="branch.id" v-model="form.branch_id" :options="branches"
                      valueKey="id" labelKey="name" placeholder="Select Branch" :error="form.errors.branch_id" /> -->
                    <MultiSelectInput label="Branches" v-model="form.branch_ids" :options="branches" labelKey="name" valueKey="id"
                      placeholder="Select Branch" :error="form.errors.branch_ids" />
                  </div>

                  <div class="col-12">
                    <label class="form-label formLabel">Description</label>
                    <textarea class="form-control formControl text-field" :class="{ 'is-invalid': form.errors.description }" rows="5" v-model="form.description"
                      placeholder="Short description about this menu..."></textarea>
                    <div v-if="form.errors.description" class="error-text">
                      {{ form.errors.description }}
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input id="is_active" class="form-check-input" type="checkbox" v-model="form.is_active">
                      <label class="form-check-label" for="is_active">Active Menu</label>
                    </div>
                  </div>
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
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MultiSelectInput from '@/Components/MultiSelectInput.vue'

export default {
  name: 'MenuCreateUpdate',
  layout: VendorAdminLayout,
  components: { Head, Link, Input, SelectInput, MultiSelectInput },

  props: {
    menu: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
  },

  data() {
    return {
      form: useForm({
        name: this.menu?.name ?? '',
        branch_ids: this.menu?.branch_ids ?? [],
        description: this.menu?.description ?? '',
        is_active: !!(this.menu?.is_active ?? true),
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.menu?.id
    },
  },

  methods: {
    nullable(value) {
      return value === '' || value === undefined ? null : value
    },

    normalizedPayload(data) {
      return {
        ...data,
        branch_ids: data.branch_ids ?? [],
        description: this.nullable(data.description),
        is_active: data.is_active ? 1 : 0,
      }
    },

    submit() {
      const requestOptions = {
        preserveScroll: true,
        onSuccess: () => router.visit(route('vendor.menus.index')),
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong.'

          alertError(message)
        }
      }

      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.normalizedPayload(data),
            _method: 'PUT',
          }))
          .post(route('vendor.menus.update', this.menu.id), requestOptions)

        return
      }

      this.form
        .transform((data) => this.normalizedPayload(data))
        .post(route('vendor.menus.store'), requestOptions)
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

.formControl.is-invalid {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.text-field {
  height: 120px;
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .form-container {
    padding: 24px 16px;
  }

  .header-title {
    font-size: 24px;
  }

  .form-card {
    border-radius: 12px;
  }

  .card-header {
    padding: 20px 16px;
  }
}
</style>
