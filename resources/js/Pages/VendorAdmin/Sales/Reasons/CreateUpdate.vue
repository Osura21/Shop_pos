<template>

  <Head :title="isEdit ? 'Edit Reason' : 'Create Reason'" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <!-- Header -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Edit Reason' : 'Create Reason' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Update the selected sales reason.' : 'Create a reusable sales reason for POS actions.' }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.sales.reasons.index')" class="btn btn-ghost">Cancel</Link>
            <button type="submit" class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Reason' : 'Create Reason') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form class="form-content" @submit.prevent="submit">
        <div v-if="form.errors.general" class="alert alert-danger">
          {{ form.errors.general }}
        </div>

        <div class="form-grid">
          <!-- Left Column -->
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Reason Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label formLabel" for="name">Name</label>
                    <input id="name" v-model="form.name" type="text" class="form-control formControl"
                      placeholder="Enter reason name" />
                    <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label formLabel" for="type">Type</label>
                    <SelectInput id="type.id" v-model="form.type" :options="normalizedTypeOptions" valueKey="value"
                      labelKey="label" placeholder="Select Type" />
                    <div v-if="form.errors.type" class="error-text">{{ form.errors.type }}</div>
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input id="is_active" class="form-check-input" type="checkbox" v-model="form.is_active" />
                      <label class="form-check-label" for="is_active">Active Reason</label>
                    </div>
                    <p class="hint-text">
                      Active reasons can be selected in POS refund, cancellation, payment or other related flows.
                    </p>
                    <div v-if="form.errors.is_active" class="error-text">{{ form.errors.is_active }}</div>
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
import { Head, Link, useForm } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

export default {
  name: 'SalesReasonForm',
  layout: VendorAdminLayout,
  components: { Head, Link, SelectInput },

  props: {
    reason: { type: Object, default: null },
    typeOptions: {
      type: [Object, Array],
      default: () => ({
        refund: 'Refund',
        cancel: 'Cancel',
        payment: 'Payment',
        other: 'Other',
      }),
    },
  },

  data() {
    return {
      form: useForm({
        name: this.reason?.name ?? '',
        type: this.reason?.type ?? '',
        is_active: !!(this.reason?.is_active ?? true),
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.reason?.id
    },

    normalizedTypeOptions() {
      if (Array.isArray(this.typeOptions)) {
        return this.typeOptions.map((item) => {
          if (typeof item === 'string') return { value: item, label: this.pretty(item) }
          return {
            value: item.value ?? item.id ?? '',
            label: item.label ?? item.name ?? item.title ?? item.value ?? '',
          }
        }).filter((item) => item.value !== '')
      }

      return Object.entries(this.typeOptions || {}).map(([value, label]) => ({ value, label }))
    },
  },

  methods: {
    submit() {
      const payload = {
        ...this.form.data(),
        is_active: this.form.is_active ? 1 : 0,
      }

      if (this.isEdit) {
        this.form.transform(() => payload).put(
          route('vendor.sales.reasons.update', this.reason.id),
          { preserveScroll: true,
            onError: (errors) => {
              const message =
                errors?.general ||
                Object.values(errors)?.flat()?.[0] ||
                'Something went wrong.'

              alertError(message)
            }
           },
        )
        return
      }

      this.form.transform(() => payload).post(
        route('vendor.sales.reasons.store'),
        { preserveScroll: true,
          onError: (errors) => {
            const message =
              errors?.general ||
              Object.values(errors)?.flat()?.[0] ||
              'Something went wrong.'

            alertError(message)
          }
         },
      )
    },

    pretty(value) {
      return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase())
    },
  },
}
</script>

<style scoped>
.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.formControl {
  height: 40px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
  width: 100%;
  padding: 0 12px;
}

.formControl:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
  outline: none;
}

.hint-text {
  margin: 6px 0 0;
  font-size: 13px;
  color: #98a2b3;
  line-height: 1.5;
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

@media (max-width: 640px) {
  .form-grid {
    max-width: 100%;
  }

  .form-card {
    border-radius: 12px;
  }

  .card-header {
    padding: 20px 16px;
  }
}
</style>
