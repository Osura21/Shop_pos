<template>

  <MiniSettingsNav :activeItem="'currency'" size="auto" :hideOnDesktop="true" />

  <Head title="Currency Settings" />

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
              <i class="bi bi-gear me-2 text-warning"></i>Currency Settings
            </h1>
            <p class="header-subtitle">
              Configure tenant-level base currency and optional secondary currency for POS and checkout.
            </p>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : 'Update Settings' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <MiniSettingsNav :activeItem="'currency'" size="auto" :hideOnMobile="true" />
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Currency Configuration</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-4">
                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Base Currency <span class="text-danger">*</span></label>
                    <SelectInput id="base_currency_code.id" v-model="form.base_currency_code" :options="currencies.map(c => ({
                      ...c,
                      label: `${c.code} - ${c.name} (${c.symbol})`
                    }))" valueKey="code" labelKey="label" placeholder="Select Base Currency" />
                    <div v-if="form.errors.base_currency_code" class="error-text">
                      {{ form.errors.base_currency_code }}
                    </div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Secondary Currency (Optional)</label>
                    <SelectInput id="base_currency_code.id" v-model="form.secondary_currency_code" :options="secondaryOptions.map(c => ({
                      ...c,
                      label: `${c.code} - ${c.name} (${c.symbol})`
                    }))" valueKey="code" labelKey="label" placeholder="Select Secondary Currency" />
                    <div v-if="form.errors.secondary_currency_code" class="error-text">
                      {{ form.errors.secondary_currency_code }}
                    </div>
                  </div>

                  <!-- Preview -->
                  <div class="col-12">
                    <div class="currency-preview">
                      <div class="currency-preview__item">
                        <div class="currency-preview__label">Base Currency</div>
                        <div class="currency-preview__value">
                          {{ selectedBase ? `${selectedBase.code} - ${selectedBase.name} (${selectedBase.symbol})` :
                          'Not selected' }}
                        </div>
                      </div>

                      <div class="currency-preview__item">
                        <div class="currency-preview__label">Secondary Currency</div>
                        <div class="currency-preview__value">
                          {{ selectedSecondary ? `${selectedSecondary.code} - ${selectedSecondary.name}
                          (${selectedSecondary.symbol})` : 'Not selected' }}
                        </div>
                      </div>
                    </div>
                  </div>
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
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MiniSettingsNav from "@/Components/MiniSettingsNav.vue"

export default {
  name: 'CurrencySettings',
  layout: VendorAdminLayout,
  components: { Head, SelectInput, MiniSettingsNav },

  props: {
    setting: { type: Object, default: () => ({}) },
    currencies: { type: Array, default: () => [] },
  },

  data() {
    return {
      form: useForm({
        base_currency_code: this.setting?.base_currency_code ?? 'LKR',
        secondary_currency_code: this.setting?.secondary_currency_code ?? '',
      }),
    }
  },

  computed: {
    selectedBase() {
      return this.currencies.find((item) => item.code === this.form.base_currency_code) || null
    },

    flash() {
      return this.$page.props.flash;
    },

    selectedSecondary() {
      return this.currencies.find((item) => item.code === this.form.secondary_currency_code) || null
    },
    secondaryOptions() {
      return this.currencies.filter((item) => item.code !== this.form.base_currency_code)
    },
  },

  methods: {
    submit() {
      this.form
        .transform((data) => ({
          ...data,
          secondary_currency_code: data.secondary_currency_code || null,
          _method: 'PUT',
        }))
        .post(route('vendor.settings.currency.update'), {
          preserveScroll: true,
        })
    },
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

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

/* Currency Preview */
.currency-preview {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-top: 12px;
}

.currency-preview__item {
  background: #f8fafc;
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 14px;
  padding: 18px;
}

.currency-preview__label {
  font-size: 12px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.currency-preview__value {
  margin-top: 8px;
  font-weight: 700;
  color: #334155;
  font-size: 15px;
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

.error-text {
  font-size: 12px;
  color: #dc2626;
  margin-top: 4px;
}

/* Responsive */
@media (max-width: 1024px) {
  .currency-preview {
    grid-template-columns: 1fr;
  }
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
