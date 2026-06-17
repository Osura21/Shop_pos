<template>

  <MiniSettingsNav :activeItem="'pms'" size="auto" :hideOnDesktop="true" />

  <Head title="PMS Integration" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-building-check me-2 text-warning"></i>PMS Integration
            </h1>
            <p class="header-subtitle">
              Connect this vendor to one hotel property for room lookup and room-charge posting.
            </p>
          </div>

          <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
            <span v-if="form.processing" class="spinner-icon"></span>
            {{ form.processing ? 'Saving...' : 'Update Settings' }}
          </button>
        </div>
      </div>


      <form class="form-content" @submit.prevent="submit">
        <div class="form-grid">
          <MiniSettingsNav :activeItem="'pms'" size="auto" :hideOnMobile="true" />
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="card-header">
              <h2 class="card-title cardTitle">Connection Details</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="row g-4">
                <div class="col-12 col-lg-6">
                  <label class="form-label formLabel">Property ID</label>
                  <input v-model="form.property_id" class="form-control-modern" type="text"
                    placeholder="Hotel property ID" />
                  <div v-if="form.errors.property_id" class="error-text">{{ form.errors.property_id }}</div>
                </div>

                <div class="col-12 col-lg-6">
                  <label class="form-label formLabel">PMS Base URL <span class="text-danger">*</span></label>
                  <input v-model="form.pms_base_url" class="form-control-modern" type="url"
                    placeholder="https://hotel.example.com" />
                  <div v-if="form.errors.pms_base_url" class="error-text">{{ form.errors.pms_base_url }}</div>
                </div>

                <div class="col-12 col-lg-8">
                  <label class="form-label formLabel">
                    API Key <span v-if="!setting?.has_api_key" class="text-danger">*</span>
                  </label>
                  <input v-model="form.pms_api_key" class="form-control-modern" type="text"
                    placeholder="Enter PMS API key" />
                  <div v-if="form.errors.pms_api_key" class="error-text">{{ form.errors.pms_api_key }}</div>
                </div>

                <div class="col-12 col-lg-4">
                  <label class="form-label formLabel">Status</label>
                  <label class="switch-row">
                    <input v-model="form.active" type="checkbox" />
                    <span>{{ form.active ? 'Active' : 'Inactive' }}</span>
                  </label>
                </div>

                <div v-if="form.errors.general" class="alert alert-danger mt-4">
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
import { Head, useForm } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import MiniSettingsNav from "@/Components/MiniSettingsNav.vue"
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

export default {
  name: 'PmsIntegrationSettings',
  layout: VendorAdminLayout,
  components: { Head, MiniSettingsNav },

  props: {
    setting: { type: Object, default: () => ({}) },
  },

  data() {
    return {
      form: useForm({
        property_id: this.setting?.property_id ?? '',
        pms_base_url: this.setting?.pms_base_url ?? '',
        pms_api_key: this.setting?.pms_api_key ?? '',
        active: Boolean(this.setting?.active),
      }),
    }
  },
  computed: {
    flash() {
      return this.$page.props.flash;
    },
  },

  methods: {
    submit() {
      this.form
        .transform((data) => ({
          ...data,
          property_id: data.property_id || null,
          pms_api_key: data.pms_api_key || null,
          _method: 'PUT',
        }))
        .post(route('vendor.settings.pms.update'), {
          preserveScroll: true,
          // onSuccess: () => {
          //   this.showSuccessToast(this.$page?.props?.flash?.success || 'PMS integration settings updated successfully.')
          // },
        })
    },
    // showSuccessToast(message) {
    //   alertSuccess(message, { duration: 3000 })
    // },
  },
  watch: {
    flash: {
      handler(flash) {
        if (flash?.message) {
          alertSuccess(flash.message);
        }

        if (flash?.error) {
          alertError(flash.error);
        }
      },
      immediate: true,
      deep: true
    }
  },
}
</script>

<style scoped>
.form-grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 40px;
}

.form-control-modern {
  width: 100%;
  min-height: 46px;
  border: 1px solid #d9e0e8;
  border-radius: 12px;
  padding: 0 14px;
  color: #334155;
  outline: none;
}

.form-control-modern:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.13);
}

.switch-row {
  min-height: 46px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 14px;
  border: 1px solid #d9e0e8;
  border-radius: 12px;
  font-weight: 800;
  color: #334155;
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

:deep(.pms-swal-toast) {
  border-radius: 12px;
  padding: 12px 14px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
}

:deep(.pms-swal-toast__title) {
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
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
