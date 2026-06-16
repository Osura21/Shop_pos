<template>
  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Vendor' : 'Create New Vendor' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Edit vendor information, domain and admin user.' : 'Create vendor with domain and admin user.' }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendors.index')" class="btn btn-ghost">
            Cancel
            </Link>

            <button type="button" class="btn btn-primary-modern" @click="submit" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Vendor' : 'Create Vendor') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <!-- Left Column -->
          <div class="form-column">

            <!-- Store Details -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Store Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorName" label="Store Name" v-model="form.name" placeholder="Enter Store Name"
                      :error="form.errors.name" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <div class="phone-wrap">
                      <PhoneInput v-model="form.contact" />
                    </div>

                    <div v-if="form.errors.contact" class="error-text">
                      {{ form.errors.contact }}
                    </div>
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorSlug" label="Slug" v-model="form.slug" placeholder="abcdstore"
                      :error="form.errors.slug" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorDomain" label="Domain" v-model="form.domain" placeholder="abcdstore.com"
                      :error="form.errors.domain" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput label="Theme" v-model="form.theme_id" :options="themes" labelKey="name" valueKey="id"
                      placeholder="Select a theme" :error="form.errors.theme_id" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput label="Status" v-model="form.status" :options="[
                      { id: 'active', name: 'Active' },
                      { id: 'inactive', name: 'Inactive' },
                    ]" labelKey="name" valueKey="id" placeholder="Select status" :error="form.errors.status" />
                  </div>

                  <div class="col-12 mb-2">
                    <div class="field-label-with-help">
                      <span>Order Types</span>
                      <span
                        class="field-help-icon"
                        title="For website search. Select which order types this vendor accepts online."
                      >?</span>
                    </div>
                    <MultiSelectInput
                      v-model="form.website_order_types"
                      :options="websiteOrderTypeOptions"
                      valueKey="value"
                      labelKey="label"
                      placeholder="Select order types"
                      :error="form.errors.website_order_types"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Business Details -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Business Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorLegalBusinessName" label="Legal Business Name"
                      v-model="form.legal_business_name" placeholder="Registered business name"
                      :error="form.errors.legal_business_name" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorStoreDisplayName" label="Store Display Name"
                      v-model="form.store_display_name" placeholder="Customer-facing restaurant name"
                      :error="form.errors.store_display_name" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorBusinessEmail" label="Business Email" v-model="form.business_email"
                      type="email" placeholder="restaurant@email.com" :error="form.errors.business_email" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorOwnerName" label="Owner / Manager Name" v-model="form.owner_name"
                      placeholder="Owner or manager name" :error="form.errors.owner_name" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorBusinessRegistration" label="Business Registration Number"
                      v-model="form.business_registration_number" placeholder="BR / PV number"
                      :error="form.errors.business_registration_number" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorFoodTypes" label="Food Types" v-model="form.food_types_text"
                      placeholder="Pizza, Burgers, Desserts" :error="form.errors.food_types" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorOpeningFrom" label="Opening From" v-model="form.opening_from"
                      type="time" :error="form.errors.opening_from" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorOpeningTo" label="Opening To" v-model="form.opening_to"
                      type="time" :error="form.errors.opening_to" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Location Details -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Location Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput
                      id="vendorCountry"
                      label="Country"
                      v-model="selectedCountryCode"
                      :options="countryOptions"
                      labelKey="name"
                      valueKey="code"
                      placeholder="Select country"
                      :error="form.errors.country"
                      :clearable="false"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <div class="location-search-field">
                      <label class="form-label formLabel mb-1" for="vendorSearchLocation">Search Location</label>
                      <input
                        id="vendorSearchLocation"
                        ref="locationSearchInput"
                        v-model="form.search_location"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.search_location }"
                        placeholder="Search address or place..."
                        autocomplete="off"
                      />
                      <div v-if="form.errors.search_location" class="invalid-feedback d-block">
                        {{ form.errors.search_location }}
                      </div>
                      <div class="form-hint">
                        <span v-if="googleMapsLoading">Loading Google location search...</span>
                        <span v-else-if="googleMapsError" class="error-text">{{ googleMapsError }}</span>
                        <span v-else>Search a Google suggestion to auto-fill address fields.</span>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorPostalCode" label="Postal Code" v-model="form.postal_code"
                      placeholder="00700" :error="form.errors.postal_code" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorCity" label="City" v-model="form.city"
                      placeholder="Colombo" :error="form.errors.city" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorStateProvince" label="State/Province" v-model="form.state_province"
                      placeholder="Western Province" :error="form.errors.state_province" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="vendorAddressLine1" label="Address Line 1" v-model="form.address_line_1"
                      placeholder="Wijerama Mawatha" :error="form.errors.address_line_1" />
                  </div>

                  <div class="col-12 mb-2">
                    <InputField id="vendorAddressLine2" label="Address Line 2" v-model="form.address_line_2"
                      placeholder="Colombo 07" :error="form.errors.address_line_2" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Subscription Details -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">POS Subscription</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput id="vendorSubscriptionPlan" label="Subscription Plan"
                      v-model="form.vendor_subscription_plan_id" :options="subscriptionPlans" labelKey="name"
                      valueKey="id" placeholder="Select POS plan" :error="form.errors.vendor_subscription_plan_id"
                      :isRequired="true" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput id="vendorSubscriptionStatus" label="Subscription Status"
                      v-model="form.vendor_subscription_status" :options="subscriptionStatusOptions" labelKey="name"
                      valueKey="id" placeholder="Select subscription status"
                      :error="form.errors.vendor_subscription_status" :clearable="false" />
                  </div>

                  <div class="col-12 mb-2">
                    <label class="checkbox-wrap">
                      <input v-model="form.vendor_panel_enabled" type="checkbox">
                      <span class="checkbox-wrap__box">
                        <i class="bi bi-check"></i>
                      </span>
                      <span class="checkbox-wrap__label">Vendor panel access enabled</span>
                    </label>
                    <p class="subscription-help">
                      Disable this when the vendor should not be able to sign in to the POS admin panel.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Admin User Details -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Admin User Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="adminName" label="Admin Name" v-model="form.admin_name"
                      placeholder="Enter admin name" :error="form.errors.admin_name" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="adminEmail" label="Admin Email" v-model="form.admin_email" type="email"
                      placeholder="admin@example.com" :error="form.errors.admin_email" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <div class="phone-wrap">
                      <PhoneInput v-model="form.admin_phone" />
                    </div>

                    <div v-if="form.errors.admin_phone" class="error-text">
                      {{ form.errors.admin_phone }}
                    </div>
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="adminPassword" label="Admin Password" v-model="form.admin_password" type="password"
                      :placeholder="isEdit ? 'Leave blank to keep same' : 'Enter password'"
                      :error="form.errors.admin_password" />
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Right Column -->
          <div class="form-column">

            <!-- Store Logo -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Store Logo</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="avatar-box" @click="triggerLogoSelect">
                  <template v-if="logoPreview">
                    <div class="avatar-placeholder">
                      <div class="avatar-image-wrapper">
                        <img :src="logoPreview" alt="Store Logo" class="avatar-img" />
                      </div>
                      <div class="fw-semibold mt-2">Upload Logo</div>
                      <div class="small text-muted mt-1">JPG or PNG (max 5MB)</div>
                    </div>
                  </template>
                  <template v-else>
                    <div class="avatar-placeholder">
                      <i class="bi bi-shop avatar-icon"></i>
                      <div class="fw-semibold mt-2">Upload Logo</div>
                      <div class="small text-muted mt-1">JPG or PNG (max 5MB)</div>
                    </div>
                  </template>
                </div>

                <input ref="logoInput" id="storeLogo" type="file" class="d-none" accept="image/*"
                  @change="onLogoChange" />

                <div class="d-flex gap-2 mt-3">
                  <button type="button" class="btn btn-outline-secondary btn-sm" @click="triggerLogoSelect">
                    Upload Image
                  </button>
                  <button v-if="logoPreview" type="button" class="btn btn-outline-danger btn-sm" @click="clearLogo">
                    Remove
                  </button>
                </div>

                <div v-if="form.errors.logo" class="error-text mt-2">
                  {{ form.errors.logo }}
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
import { useForm, Link } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import InputField from '@/Components/InputField.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'
import PhoneInput from '@/Components/PhoneInput.vue'
import MultiSelectInput from '@/Components/MultiSelectInput.vue'
import ctd from 'country-telephone-data'

export default {
  name: 'CreateUpdateVendor',
  layout: SuperAdminLayout,

  components: {
    Link,
    InputField,
    SelectInput,
    PhoneInput,
    MultiSelectInput,
  },

  props: {
    themes: {
      type: Array,
      default: () => [],
    },
    vendor: {
      type: Object,
      default: null,
    },
    subscriptionPlans: {
      type: Array,
      default: () => [],
    },
    websiteOrderTypeOptions: {
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

    const initialCountryCode = this.vendor?.country_code
      || countries.find((country) => country.name === cleanCountryName(this.vendor?.country))?.code
      || 'LK'

    const selectedCountry = countries.find((country) => country.code === initialCountryCode) || countries[0]

    return {

      logoPreview: this.vendor?.logo_url || null,
      countryOptions: countries,
      selectedCountryCode: selectedCountry?.code || 'LK',
      googleMapsLoading: false,
      googleMapsError: '',
      autocompleteInstance: null,

      inlineFields: [
        'name',
        'slug',
        'domain',
        'theme_id',
        'status',
        'legal_business_name',
        'store_display_name',
        'business_email',
        'owner_name',
        'business_registration_number',
        'food_types',
        'opening_from',
        'opening_to',
        'vendor_subscription_plan_id',
        'vendor_subscription_status',
        'vendor_panel_enabled',
        'address',
        'country',
        'country_code',
        'search_location',
        'google_place_id',
        'postal_code',
        'address_line_1',
        'address_line_2',
        'state_province',
        'city',
        'contact',
        'website_order_types',
        'logo',
        'admin_name',
        'admin_email',
        'admin_phone',
        'admin_password',
      ],

      form: useForm({
        name: this.vendor?.name ?? '',
        legal_business_name: this.vendor?.legal_business_name ?? '',
        store_display_name: this.vendor?.store_display_name ?? '',
        slug: this.vendor?.slug ?? '',
        domain: this.vendor?.domain ?? '',
        theme_id: this.vendor?.theme_id ?? '',
        status: this.vendor?.status ?? 'active',
        vendor_subscription_plan_id: this.vendor?.vendor_subscription_plan_id ?? '',
        vendor_subscription_status: this.vendor?.vendor_subscription_status ?? 'active',
        vendor_panel_enabled: this.vendor?.vendor_panel_enabled ?? true,
        address: this.vendor?.address ?? '',
        country: cleanCountryName(this.vendor?.country) || selectedCountry?.name || 'Sri Lanka',
        country_code: this.vendor?.country_code ?? selectedCountry?.code ?? 'LK',
        search_location: this.vendor?.search_location ?? this.vendor?.address ?? '',
        google_place_id: this.vendor?.google_place_id ?? '',
        postal_code: this.vendor?.postal_code ?? '',
        address_line_1: this.vendor?.address_line_1 ?? '',
        address_line_2: this.vendor?.address_line_2 ?? '',
        state_province: this.vendor?.state_province ?? '',
        city: this.vendor?.city ?? '',
        contact: this.vendor?.contact ?? '',
        business_email: this.vendor?.business_email ?? '',
        owner_name: this.vendor?.owner_name ?? '',
        business_registration_number: this.vendor?.business_registration_number ?? '',
        food_types: this.vendor?.food_types ?? [],
        food_types_text: (this.vendor?.food_types ?? []).join(', '),
        opening_from: this.vendor?.opening_from ?? '',
        opening_to: this.vendor?.opening_to ?? '',
        website_order_types: this.vendor?.website_order_types ?? ['delivery', 'pickup', 'scheduled'],
        logo: null,

        admin_name: this.vendor?.admin_name ?? '',
        admin_email: this.vendor?.admin_email ?? '',
        admin_phone: this.vendor?.admin_phone ?? '',
        admin_password: '',
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.vendor?.id
    },
    subscriptionStatusOptions() {
      return [
        { id: 'active', name: 'Active' },
        { id: 'trialing', name: 'Trialing' },
        { id: 'past_due', name: 'Past Due' },
        { id: 'suspended', name: 'Suspended' },
        { id: 'cancelled', name: 'Cancelled' },
        { id: 'inactive', name: 'Inactive' },
      ]
    },
    selectedCountry() {
      return this.countryOptions.find((country) => country.code === this.selectedCountryCode)
    },
  },

  watch: {
    selectedCountryCode(value) {
      const country = this.countryOptions.find((item) => item.code === value)
      this.form.country = country?.name || ''
      this.form.country_code = country?.code || ''
      this.applyAutocompleteCountryRestriction()
    },
  },

  beforeUnmount() {
    if (this.logoPreview && String(this.logoPreview).startsWith('blob:')) {
      URL.revokeObjectURL(this.logoPreview)
    }
  },

  mounted() {
    this.initPlacesAutocomplete()
  },

  methods: {

    triggerLogoSelect() {
      this.$refs.logoInput?.click()
    },

    onLogoChange(e) {
      const file = e.target.files?.[0] || null
      this.form.logo = file

      if (this.logoPreview && String(this.logoPreview).startsWith('blob:')) {
        URL.revokeObjectURL(this.logoPreview)
      }

      this.logoPreview = file ? URL.createObjectURL(file) : null
    },

    clearLogo() {
      if (this.logoPreview && String(this.logoPreview).startsWith('blob:')) {
        URL.revokeObjectURL(this.logoPreview)
      }

      this.logoPreview = null
      this.form.logo = null

      if (this.$refs.logoInput) {
        this.$refs.logoInput.value = ''
      }
    },

    async loadGoogleMapsApi() {
      if (window.google?.maps?.places) {
        this.googleMapsLoading = false
        this.googleMapsError = ''
        return window.google
      }

      const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

      if (!apiKey) {
        this.googleMapsError = 'Missing VITE_GOOGLE_MAPS_API_KEY in .env'
        return null
      }

      this.googleMapsLoading = true
      this.googleMapsError = ''

      try {
        if (!window.__vendorGoogleMapsPromise) {
          window.__vendorGoogleMapsPromise = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-google-maps="vendor-form"]')
            if (existingScript) {
              existingScript.addEventListener('load', () => resolve(window.google))
              existingScript.addEventListener('error', () => reject(new Error('Failed to load Google Maps script')))
              return
            }

            const script = document.createElement('script')
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`
            script.async = true
            script.defer = true
            script.dataset.googleMaps = 'vendor-form'

            script.onload = () => resolve(window.google)
            script.onerror = () => reject(new Error('Failed to load Google Maps script'))

            document.head.appendChild(script)
          })
        }

        await window.__vendorGoogleMapsPromise
        this.googleMapsLoading = false
        return window.google
      } catch {
        this.googleMapsLoading = false
        this.googleMapsError = 'Google Maps could not be loaded. Check API key, billing, and Places API access.'
        return null
      }
    },

    async initPlacesAutocomplete() {
      const googleObject = await this.loadGoogleMapsApi()
      if (!googleObject || !this.$refs.locationSearchInput) return

      this.autocompleteInstance = new google.maps.places.Autocomplete(this.$refs.locationSearchInput, {
        types: ['geocode'],
        fields: ['place_id', 'name', 'formatted_address', 'address_components'],
      })

      this.applyAutocompleteCountryRestriction()
      this.autocompleteInstance.addListener('place_changed', this.onPlaceChanged)
    },

    applyAutocompleteCountryRestriction() {
      if (!this.autocompleteInstance) return

      const countryCode = String(this.form.country_code || '').toLowerCase()
      this.autocompleteInstance.setComponentRestrictions(countryCode ? { country: countryCode } : {})
    },

    onPlaceChanged() {
      if (!this.autocompleteInstance) return

      const place = this.autocompleteInstance.getPlace()
      if (!place) return

      const components = place.address_components || []
      const getComponent = (type, format = 'long_name') => {
        const found = components.find((component) => component.types?.includes(type))
        return found ? found[format] : ''
      }

      const streetNumber = getComponent('street_number')
      const routeName = getComponent('route')
      const locality =
        getComponent('locality') ||
        getComponent('postal_town') ||
        getComponent('administrative_area_level_2')
      const state = getComponent('administrative_area_level_1')
      const country = getComponent('country')
      const countryCode = getComponent('country', 'short_name')
      const postalCode = getComponent('postal_code')
      const sublocality =
        getComponent('sublocality') ||
        getComponent('sublocality_level_1') ||
        getComponent('neighborhood')

      const addressLine1 = [streetNumber, routeName].filter(Boolean).join(' ').trim()
      const fallbackFirstPart = (place.formatted_address || '').split(',')[0]?.trim() || place.name || ''

      this.form.google_place_id = place.place_id || ''
      this.form.search_location = place.formatted_address || place.name || this.form.search_location
      this.form.address = place.formatted_address || this.form.search_location
      this.form.address_line_1 = addressLine1 || fallbackFirstPart
      this.form.address_line_2 = this.form.address_line_2 || sublocality || ''
      this.form.city = locality || this.form.city
      this.form.state_province = state || this.form.state_province
      this.form.country = this.cleanCountryName(country || this.form.country)
      this.form.country_code = countryCode ? countryCode.toUpperCase() : this.form.country_code
      this.form.postal_code = postalCode || this.form.postal_code

      if (countryCode) {
        this.selectedCountryCode = countryCode.toUpperCase()
      }
    },

    cleanCountryName(name) {
      return String(name || '').replace(/\s*\([^)]*\)\s*/g, '').trim()
    },

    resolvedAddress(data) {
      return data.address
        || data.search_location
        || [data.address_line_1, data.address_line_2, data.city, data.state_province, data.postal_code, data.country]
          .filter(Boolean)
          .join(', ')
    },

    splitCsv(value = '') {
      return String(value)
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)
    },

    submit() {
      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...data,
            address: this.resolvedAddress(data),
            country: this.cleanCountryName(this.selectedCountry?.name || data.country),
            country_code: this.selectedCountry?.code || data.country_code,
            food_types: this.splitCsv(data.food_types_text),
            vendor_panel_enabled: data.vendor_panel_enabled ? 1 : 0,
            website_order_types: Array.isArray(data.website_order_types) ? data.website_order_types : [],
            _method: 'put',
          }))
          .post(route('vendors.update', this.vendor.id), {
            preserveScroll: true,
            forceFormData: true,
            onError: (errors) => {
              const message =
                errors?.general ||
                Object.values(errors)?.flat()?.[0] ||
                'Something went wrong.'

              alertError(message)
            }
          })

        return
      }

      this.form
        .transform((data) => ({
          ...data,
          address: this.resolvedAddress(data),
          country: this.cleanCountryName(this.selectedCountry?.name || data.country),
          country_code: this.selectedCountry?.code || data.country_code,
          food_types: this.splitCsv(data.food_types_text),
          vendor_panel_enabled: data.vendor_panel_enabled ? 1 : 0,
          website_order_types: Array.isArray(data.website_order_types) ? data.website_order_types : [],
        }))
        .post(route('vendors.store'), {
        preserveScroll: true,
        forceFormData: true,
          onError: (errors) => {
            const message =
              errors?.general ||
              Object.values(errors)?.flat()?.[0] ||
              'Something went wrong.'

            alertError(message)
          }
      })
    }
  },
}
</script>

<style scoped>
/* ── Form layout ── */
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

/* ── Avatar / Logo upload ── */
.avatar-box {
  min-height: 240px;
  border: 2px dashed rgba(242, 140, 0, 0.3);
  border-radius: 16px;
  background: #fcfaf7;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-align: center;
  padding: 20px;
  transition: all 0.2s ease;
}

.avatar-box:hover {
  border-color: rgba(242, 140, 0, 0.6);
  background: #fdf6ec;
}

.avatar-placeholder {
  color: #6b7280;
}

.subscription-help {
  color: #64748b;
  font-size: 0.82rem;
  margin: 0.55rem 0 0 2rem;
}

.field-label-with-help {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #4a5568;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.3px;
  margin-bottom: 4px;
  text-transform: uppercase;
}

.field-help-icon {
  align-items: center;
  background: rgba(242, 140, 0, 0.12);
  border: 1px solid rgba(242, 140, 0, 0.3);
  border-radius: 999px;
  color: #c85a00;
  cursor: help;
  display: inline-flex;
  font-size: 11px;
  font-weight: 800;
  height: 18px;
  justify-content: center;
  line-height: 1;
  width: 18px;
}

.location-field {
  position: relative;
}

.location-search-field {
  position: relative;
}

.location-search-field .form-control {
  height: 42px;
  border-radius: 10px;
  border: 1px solid #d1d5db;
  padding: 0.55rem 0.75rem;
  font-size: 14px;
  background: #fff;
}

.location-search-field .form-control:focus {
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
  outline: none;
}

.form-hint {
  color: #64748b;
  font-size: 12px;
  margin-top: 5px;
}

:global(.pac-container) {
  z-index: 100000 !important;
  border-radius: 0 0 10px 10px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.15);
}

.suggestion-menu {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 12px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
  left: 12px;
  max-height: 240px;
  overflow-y: auto;
  padding: 6px;
  position: absolute;
  right: 12px;
  top: calc(100% - 4px);
  z-index: 30;
}

.suggestion-menu button {
  align-items: flex-start;
  background: transparent;
  border: 0;
  border-radius: 10px;
  color: #334155;
  display: flex;
  gap: 10px;
  min-height: 44px;
  padding: 9px 10px;
  text-align: left;
  width: 100%;
}

.suggestion-menu button:hover {
  background: rgba(242, 140, 0, 0.08);
  color: #c85a00;
}

.suggestion-menu i {
  color: #a3a3a3;
  font-size: 15px;
  margin-top: 2px;
}

.suggestion-menu span {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.suggestion-menu strong {
  color: #0f172a;
  font-size: 13px;
  font-weight: 800;
  line-height: 1.25;
}

.suggestion-menu small {
  color: #64748b;
  font-size: 12px;
  line-height: 1.3;
}

.avatar-icon {
  font-size: 68px;
  color: #f28c00;
}

.avatar-image-wrapper {
  width: 80px;
  height: 80px;
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

/* ── Error text ── */
.error-text {
  font-size: 12px;
  color: #dc2626;
  margin-top: 4px;
}

/* ── Spinner ── */
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

/* ── Responsive ── */
@media (max-width: 1024px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
}

@media (max-width: 768px) {
  .form-container {
    padding: 24px 16px;
  }
}

@media (max-width: 640px) {
  .header-title {
    font-size: 24px;
  }

  .form-card {
    border-radius: 12px;
  }

  .card-header {
    padding: 20px 16px 0;
  }
}
</style>
