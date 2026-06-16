<template>
  <div class="form-container">
    <div class="gradient-overlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update SEO Footer Link' : 'Create SEO Footer Link' }}</h1>
            <p class="header-subtitle">These links appear in the public Browse By City section.</p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('seo-footer-links.index')" class="btn btn-ghost">Cancel</Link>

            <button type="button" class="btn btn-primary-modern" @click="submit" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Link' : 'Create Link') }}
            </button>
          </div>
        </div>
      </div>

      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">SEO Link Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput
                      id="seoCountry"
                      label="Country"
                      v-model="selectedCountryCode"
                      :options="countryOptions"
                      labelKey="name"
                      valueKey="code"
                      placeholder="Select country"
                      :error="form.errors.country"
                      :isRequired="true"
                      :clearable="false"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2 location-field" ref="locationRoot">
                    <InputField
                      id="seoLocation"
                      label="Location"
                      v-model="form.location"
                      placeholder="Copenhagen"
                      :error="form.errors.location"
                      @update:modelValue="queueLocationSearch"
                    />
                    <div v-if="locationSuggestions.length" class="suggestion-menu">
                      <button
                        v-for="item in locationSuggestions"
                        :key="item.description"
                        type="button"
                        @click="chooseLocation(item)"
                      >
                        {{ item.description }}
                      </button>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField
                      id="seoLinkText"
                      label="Browse By City Link Text"
                      v-model="form.link_text"
                      placeholder="Pizza Delivery in Copenhagen"
                      :error="form.errors.link_text"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput
                      id="seoFoodType"
                      label="Food Type"
                      v-model="form.food_type_slug"
                      :options="foodTypeOptions"
                      labelKey="name"
                      valueKey="slug"
                      placeholder="Select food type"
                      :error="form.errors.food_type"
                      :isRequired="true"
                      :clearable="false"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput
                      id="seoOrderType"
                      label="Order Type"
                      v-model="form.order_type"
                      :options="orderTypeOptions"
                      labelKey="name"
                      valueKey="id"
                      placeholder="Select order type"
                      :error="form.errors.order_type"
                      :isRequired="true"
                      :clearable="false"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField
                      id="seoSortOrder"
                      label="Sort Order"
                      v-model="form.sort_order"
                      type="number"
                      placeholder="0"
                      :error="form.errors.sort_order"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2 d-flex align-items-end">
                    <label class="checkbox-wrap">
                      <input v-model="form.is_active" type="checkbox">
                      <span class="checkbox-wrap__box">
                        <i class="bi bi-check"></i>
                      </span>
                      <span class="checkbox-wrap__label">Active</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-column">
            <div class="form-card preview-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Website Preview</h2>
              </div>
              <div class="card-body formCardBody">
                <p class="preview-label">Browse By City link text</p>
                <div class="preview-link">{{ previewText }}</div>
                <p class="preview-help">Clicking this link filters the marketplace by country, location, food type, and order type.</p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import InputField from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError } from '@/Utils/modernAlert'
import ctd from 'country-telephone-data'

export default {
  name: 'CreateUpdateSeoFooterLink',
  layout: SuperAdminLayout,

  components: {
    Link,
    InputField,
    SelectInput,
  },

  props: {
    seoFooterLink: {
      type: Object,
      default: null,
    },
    orderTypeOptions: {
      type: Array,
      default: () => [],
    },
    foodTypeOptions: {
      type: Array,
      default: () => [],
    },
  },

  data() {
    const cleanCountryName = (name) => String(name || '').replace(/\s*\([^)]*\)\s*/g, '').trim()
    const countries = ctd.allCountries.map((country) => ({
      code: String(country.iso2 || '').toUpperCase(),
      name: cleanCountryName(country.name),
    })).sort((a, b) => a.name.localeCompare(b.name))

    const initialCountryCode = this.seoFooterLink?.country_code
      || countries.find((country) => country.name === cleanCountryName(this.seoFooterLink?.country))?.code
      || 'LK'

    const selectedCountry = countries.find((country) => country.code === initialCountryCode) || countries[0]

    return {
      countryOptions: countries,
      selectedCountryCode: selectedCountry?.code || 'LK',
      locationSuggestions: [],
      locationTimer: null,

      form: useForm({
        country: cleanCountryName(this.seoFooterLink?.country) || selectedCountry?.name || 'Sri Lanka',
        country_code: this.seoFooterLink?.country_code ?? selectedCountry?.code ?? 'LK',
        location: this.seoFooterLink?.location ?? '',
        link_text: this.seoFooterLink?.link_text ?? '',
        food_type: this.seoFooterLink?.food_type ?? '',
        food_type_slug: this.seoFooterLink?.food_type_slug ?? '',
        order_type: this.seoFooterLink?.order_type ?? 'delivery',
        sort_order: this.seoFooterLink?.sort_order ?? 0,
        is_active: this.seoFooterLink?.is_active ?? true,
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.seoFooterLink?.id
    },
    selectedCountry() {
      return this.countryOptions.find((country) => country.code === this.selectedCountryCode)
    },
    previewText() {
      if (String(this.form.link_text || '').trim()) {
        return this.form.link_text
      }

      const orderLabel = this.orderTypeOptions.find((item) => item.id === this.form.order_type)?.name || 'Delivery'
      const foodType = this.form.food_type || 'Pizza'
      const location = this.form.location || 'Copenhagen'
      return `${foodType} ${orderLabel} in ${location}`
    },
  },

  watch: {
    selectedCountryCode(value) {
      const country = this.countryOptions.find((item) => item.code === value)
      this.form.country = country?.name || ''
      this.form.country_code = country?.code || ''
      this.locationSuggestions = []
    },
    'form.food_type_slug'(value) {
      const foodType = this.foodTypeOptions.find((item) => item.slug === value)
      this.form.food_type = foodType?.name || ''
    },
  },

  mounted() {
    document.addEventListener('click', this.handleOutsideClick)
  },

  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick)
    if (this.locationTimer) clearTimeout(this.locationTimer)
  },

  methods: {
    slugify(value) {
      return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
    },

    queueLocationSearch() {
      if (this.locationTimer) clearTimeout(this.locationTimer)

      this.locationTimer = setTimeout(() => {
        this.fetchLocationSuggestions()
      }, 250)
    },

    async fetchLocationSuggestions() {
      const query = String(this.form.location || '').trim()
      if (query.length < 2) {
        this.locationSuggestions = []
        return
      }

      try {
        const response = await axios.get(route('seo-footer-links.locations'), {
          params: {
            query,
            country_code: this.form.country_code,
          },
        })
        this.locationSuggestions = response.data?.predictions || []
      } catch {
        this.locationSuggestions = []
      }
    },

    chooseLocation(item) {
      this.form.location = item.main_text || item.description || this.form.location
      this.locationSuggestions = []
    },

    handleOutsideClick(event) {
      if (!this.$refs.locationRoot?.contains(event.target)) {
        this.locationSuggestions = []
      }
    },

    payload(data) {
      return {
        ...data,
        country: this.cleanCountryName(this.selectedCountry?.name || data.country),
        country_code: this.selectedCountry?.code || data.country_code,
        link_text: String(data.link_text || '').trim(),
        food_type_slug: this.slugify(data.food_type_slug || data.food_type),
        sort_order: Number(data.sort_order || 0),
        is_active: data.is_active ? 1 : 0,
      }
    },

    cleanCountryName(name) {
      return String(name || '').replace(/\s*\([^)]*\)\s*/g, '').trim()
    },

    submit() {
      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.payload(data),
            _method: 'put',
          }))
          .post(route('seo-footer-links.update', this.seoFooterLink.id), {
            preserveScroll: true,
            onError: this.showError,
          })
        return
      }

      this.form
        .transform((data) => this.payload(data))
        .post(route('seo-footer-links.store'), {
          preserveScroll: true,
          onError: this.showError,
        })
    },

    showError(errors) {
      const message = errors?.general || Object.values(errors)?.flat()?.[0] || 'Something went wrong.'
      alertError(message)
    },
  },
}
</script>

<style scoped>
.form-content {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.location-field {
  position: relative;
}

.suggestion-menu {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 14px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
  left: 12px;
  max-height: 220px;
  overflow-y: auto;
  padding: 6px;
  position: absolute;
  right: 12px;
  top: calc(100% - 4px);
  z-index: 20;
}

.suggestion-menu button {
  align-items: center;
  background: transparent;
  border: 0;
  border-radius: 10px;
  color: #334155;
  display: flex;
  font-size: 14px;
  font-weight: 700;
  min-height: 38px;
  padding: 8px 10px;
  text-align: left;
  width: 100%;
}

.suggestion-menu button:hover {
  background: rgba(242, 140, 0, 0.08);
  color: #c85a00;
}

.preview-card {
  min-height: 220px;
}

.preview-label {
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  margin-bottom: 10px;
  text-transform: uppercase;
}

.preview-link {
  border: 1px solid rgba(242, 140, 0, 0.18);
  border-radius: 14px;
  color: #0f172a;
  font-size: 18px;
  font-weight: 900;
  padding: 16px;
}

.preview-help {
  color: #64748b;
  font-size: 13px;
  line-height: 1.55;
  margin: 14px 0 0;
}

.checkbox-wrap {
  align-items: center;
  cursor: pointer;
  display: inline-flex;
  gap: 10px;
  user-select: none;
}

.checkbox-wrap input {
  display: none;
}

.checkbox-wrap__box {
  align-items: center;
  background: #fff;
  border: 1px solid #d7dee7;
  border-radius: 7px;
  color: transparent;
  display: inline-flex;
  height: 22px;
  justify-content: center;
  transition: all 0.15s ease;
  width: 22px;
}

.checkbox-wrap input:checked + .checkbox-wrap__box {
  background: #f59e0b;
  border-color: #f59e0b;
  color: #fff;
}

.checkbox-wrap__label {
  color: #475569;
  font-weight: 700;
}

.spinner-icon {
  animation: spin 0.6s linear infinite;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #ffffff;
  display: inline-block;
  height: 14px;
  width: 14px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1024px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
}
</style>
