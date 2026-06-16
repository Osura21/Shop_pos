<template>
  <Head :title="isEdit ? 'Update Branch' : 'Create Branch'" />

  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay gradientOverlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Branch' : 'Create Branch' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Manage branch information and POS settings.' : 'Set up a new branch location with precision.' }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.branches.index')" class="btn btn-ghost">
              Cancel
            </Link>

            <button
              type="button"
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Branch' : 'Create Branch') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <!-- Left Column -->
          <div class="form-column">
            <!-- Branch Information Card -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Branch Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="form-group-wrap">
                  <div class="form-group">
                    <Input
                      id="name"
                      label="Branch Name"
                      v-model="form.name"
                      placeholder="Enter branch name"
                      :error="form.errors.name"
                    />
                  </div>

                  <div class="form-group">
                    <Input
                      id="legal_name"
                      label="Legal Name"
                      v-model="form.legal_name"
                      placeholder="Legal entity name"
                      is_required
                      :error="form.errors.legal_name"
                    />
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <PhoneInput v-model="form.phone" />
                    </div>

                    <div class="form-group">
                      <Input
                        id="email"
                        label="Email"
                        type="email"
                        v-model="form.email"
                        placeholder="branch@restaurant.com"
                        is_required
                        :error="form.errors.email"
                      />
                    </div>
                  </div>

                  <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                      <input
                        id="is_active"
                        type="checkbox"
                        v-model="form.is_active"
                        class="checkbox-input"
                      />
                      <span>Active Branch</span>
                    </label>
                    <div v-if="form.errors.is_active" class="error-text">
                      {{ form.errors.is_active }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Address Information Card -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Location Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="form-group-wrap">
                  <div class="form-group">
                    <label class="form-label formLabel">Search Location</label>
                    <input
                      ref="locationSearchInput"
                      v-model="locationSearch"
                      type="text"
                      class="form-input location-search"
                      placeholder="Search address or place..."
                      autocomplete="off"
                    />
                    <div class="form-hint">
                      <span v-if="googleMapsLoading">Loading Google location search…</span>
                      <span v-else-if="googleMapsError" class="error-text">{{ googleMapsError }}</span>
                      <span v-else>Search a Google suggestion to auto-fill address and coordinates.</span>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <Input
                        id="country"
                        label="Country"
                        v-model="form.country"
                        placeholder="Country"
                        :error="form.errors.country"
                      />
                    </div>

                    <div class="form-group">
                      <Input
                        id="postal_code"
                        label="Postal Code"
                        v-model="form.postal_code"
                        placeholder="Postal Code"
                        :error="form.errors.postal_code"
                      />
                    </div>
                  </div>

                  <div class="form-group">
                    <Input
                      id="address_line_1"
                      label="Address Line 1"
                      v-model="form.address_line_1"
                      placeholder="Street address"
                      :error="form.errors.address_line_1"
                    />
                  </div>

                  <div class="form-group">
                    <Input
                      id="address_line_2"
                      label="Address Line 2"
                      v-model="form.address_line_2"
                      placeholder="Apartment, floor, etc."
                      :error="form.errors.address_line_2"
                    />
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <Input
                        id="city"
                        label="City"
                        v-model="form.city"
                        placeholder="City"
                        :error="form.errors.city"
                      />
                    </div>

                    <div class="form-group">
                      <Input
                        id="state"
                        label="State/Province"
                        v-model="form.state"
                        placeholder="State"
                        :error="form.errors.state"
                      />
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <Input
                        id="latitude"
                        label="Latitude"
                        v-model="form.latitude"
                        placeholder="0.000000"
                        :error="form.errors.latitude"
                      />
                    </div>

                    <div class="form-group">
                      <Input
                        id="longitude"
                        label="Longitude"
                        v-model="form.longitude"
                        placeholder="0.000000"
                        :error="form.errors.longitude"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="form-column">
            <!-- Business Registration Card -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Business Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="form-group-wrap">
                  <div class="form-group">
                    <Input
                      id="registration_number"
                      label="Registration Number"
                      v-model="form.registration_number"
                      placeholder="Business registration"
                      :error="form.errors.registration_number"
                    />
                  </div>

                  <div class="form-group">
                    <Input
                      id="vat_tin"
                      label="VAT / TIN"
                      v-model="form.vat_tin"
                      placeholder="Tax identification"
                      :error="form.errors.vat_tin"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Regional Settings Card -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Regional Settings</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="form-group-wrap">
                  <div class="form-row">
                    <div class="form-group">

                      <SelectInput label="Currency" v-model="form.currency" :options="currencies" labelKey="name"
                        valueKey="code" placeholder="Select a Currency" :error="form.errors.timezone" />
                      <div v-if="form.errors.currency" class="error-text">
                        {{ form.errors.currency }}
                      </div>
                    </div>

                    <div class="form-group">

                      <SelectInput label="TimeZone" v-model="form.timezone" :options="timezones" labelKey="name"
                        valueKey="id" placeholder="Select a TimeZone" :error="form.errors.timezone" />
                      <div v-if="form.errors.timezone" class="error-text">
                        {{ form.errors.timezone }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- POS Settings Card -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">POS Configuration</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="form-group-wrap">
                  <!-- Order Types -->
                  <div class="form-group" ref="orderTypesDropdownRef">
                    <label class="form-label formLabel">Order Types</label>

                    <button
                      type="button"
                      class="multi-select-btn"
                      @click="toggleDropdown('orderTypes')"
                    >
                      <span v-if="selectedOrderTypeLabels.length" class="select-value">
                        {{ selectedOrderTypeLabels.join(', ') }}
                      </span>
                      <span v-else class="select-placeholder">Select types</span>

                      <svg class="select-icon" :class="{ open: showOrderTypesDropdown }" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>

                    <div v-if="showOrderTypesDropdown" class="multi-select-menu">
                      <label
                        v-for="option in orderTypeOptionList"
                        :key="option.value"
                        class="multi-select-item"
                      >
                        <input
                          type="checkbox"
                          :checked="isSelected(form.order_types, option.value)"
                          @change="toggleMultiValue('order_types', option.value)"
                        />
                        <span>{{ option.label }}</span>
                      </label>
                    </div>

                    <div v-if="selectedOrderTypeLabels.length" class="tag-list">
                      <span
                        v-for="option in selectedOrderTypeObjects"
                        :key="option.value"
                        class="tag-badge"
                      >
                        {{ option.label }}
                        <button type="button" @click="removeMultiValue('order_types', option.value)" class="tag-remove">×</button>
                      </span>
                    </div>

                    <div v-if="groupError('order_types')" class="error-text">
                      {{ groupError('order_types') }}
                    </div>
                  </div>

                  <!-- Payment Methods -->
                  <div class="form-group" ref="paymentMethodsDropdownRef">
                    <label class=" formLabel formLabel">Payment Methods</label>

                    <button
                      type="button"
                      class="multi-select-btn"
                      @click="toggleDropdown('paymentMethods')"
                    >
                      <span v-if="selectedPaymentMethodLabels.length" class="select-value">
                        {{ selectedPaymentMethodLabels.join(', ') }}
                      </span>
                      <span v-else class="select-placeholder">Select methods</span>

                      <svg class="select-icon" :class="{ open: showPaymentMethodsDropdown }" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>

                    <div v-if="showPaymentMethodsDropdown" class="multi-select-menu">
                      <label
                        v-for="option in paymentMethodOptionList"
                        :key="option.value"
                        class="multi-select-item"
                      >
                        <input
                          type="checkbox"
                          :checked="isSelected(form.payment_methods, option.value)"
                          @change="toggleMultiValue('payment_methods', option.value)"
                        />
                        <span>{{ option.label }}</span>
                      </label>
                    </div>

                    <div v-if="selectedPaymentMethodLabels.length" class="tag-list">
                      <span
                        v-for="option in selectedPaymentMethodObjects"
                        :key="option.value"
                        class="tag-badge"
                      >
                        {{ option.label }}
                        <button type="button" @click="removeMultiValue('payment_methods', option.value)" class="tag-remove">×</button>
                      </span>
                    </div>

                    <div v-if="groupError('payment_methods')" class="error-text">
                      {{ groupError('payment_methods') }}
                    </div>
                  </div>

                  <!-- Cash Difference Threshold -->
                  <div class="form-group">
                    <Input
                      id="cash_difference_threshold"
                      label="Cash Difference Threshold"
                      v-model="form.cash_difference_threshold"
                      placeholder="0.00"
                      :error="form.errors.cash_difference_threshold"
                    />
                  </div>

                  <!-- Quick Pay Amounts -->
                  <div class="form-group">
                    <label class="form-label formLabel">Quick Pay Amounts</label>
                    <div class="quick-pay-grid">
                      <Input
                        id="quick_pay_amount_1"
                        label="Amount 1"
                        v-model="form.quick_pay_amount_1"
                        placeholder="0.00"
                        :error="form.errors.quick_pay_amount_1"
                      />

                      <Input
                        id="quick_pay_amount_2"
                        label="Amount 2"
                        v-model="form.quick_pay_amount_2"
                        placeholder="0.00"
                        :error="form.errors.quick_pay_amount_2"
                      />

                      <Input
                        id="quick_pay_amount_3"
                        label="Amount 3"
                        v-model="form.quick_pay_amount_3"
                        placeholder="0.00"
                        :error="form.errors.quick_pay_amount_3"
                      />

                      <Input
                        id="quick_pay_amount_4"
                        label="Amount 4"
                        v-model="form.quick_pay_amount_4"
                        placeholder="0.00"
                        :error="form.errors.quick_pay_amount_4"
                      />

                      <Input
                        id="quick_pay_amount_5"
                        label="Amount 5"
                        v-model="form.quick_pay_amount_5"
                        placeholder="0.00"
                        :error="form.errors.quick_pay_amount_5"
                      />

                      <Input
                        id="quick_pay_amount_6"
                        label="Amount 6"
                        v-model="form.quick_pay_amount_6"
                        placeholder="0.00"
                        :error="form.errors.quick_pay_amount_6"
                      />
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
import ctd from 'country-telephone-data'
import 'flag-icons/css/flag-icons.min.css'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'
import PhoneInput from '@/Components/PhoneInput.vue'

export default {
  name: 'BranchCreateUpdate',
  layout: VendorAdminLayout,

  components: {
    Head,
    Link,
    Input,
    SelectInput,
    PhoneInput,
  },

  props: {
    branch: { type: Object, default: null },
    currencies: { type: Array, default: () => [] },
    timezones: { type: Array, default: () => [] },
    orderTypeOptions: { type: [Object, Array], default: () => ({}) },
    paymentMethodOptions: { type: [Object, Array], default: () => ({}) },
  },

  data() {
    const initialLocationSearch = [
      this.branch?.address_line_1,
      this.branch?.address_line_2,
      this.branch?.city,
      this.branch?.state,
      this.branch?.postal_code,
      this.branch?.country,
    ].filter(Boolean).join(', ')

    return {
      locationSearch: initialLocationSearch,
      googleMapsLoading: false,
      googleMapsError: '',
      autocompleteInstance: null,

      showOrderTypesDropdown: false,
      showTimezone: false,
      showCurrency: false,
      showPaymentMethodsDropdown: false,

      form: useForm({
        name: this.branch?.name ?? '',
        legal_name: this.branch?.legal_name ?? '',
        phone: this.branch?.phone || '',
        email: this.branch?.email ?? '',
        is_active: !!(this.branch?.is_active ?? true),

        registration_number: this.branch?.registration_number ?? '',
        vat_tin: this.branch?.vat_tin ?? '',

        currency: this.branch?.currency ?? '',
        timezone: this.branch?.timezone ?? 'UTC',

        country: this.branch?.country ?? '',
        postal_code: this.branch?.postal_code ?? '',
        address_line_1: this.branch?.address_line_1 ?? '',
        address_line_2: this.branch?.address_line_2 ?? '',
        city: this.branch?.city ?? '',
        state: this.branch?.state ?? '',
        latitude: this.branch?.latitude ?? '',
        longitude: this.branch?.longitude ?? '',

        order_types: Array.isArray(this.branch?.order_types) ? [...this.branch.order_types] : [],
        payment_methods: Array.isArray(this.branch?.payment_methods) ? [...this.branch.payment_methods] : [],

        cash_difference_threshold: this.branch?.cash_difference_threshold ?? '',
        quick_pay_amount_1: this.branch?.quick_pay_amount_1 ?? '',
        quick_pay_amount_2: this.branch?.quick_pay_amount_2 ?? '',
        quick_pay_amount_3: this.branch?.quick_pay_amount_3 ?? '',
        quick_pay_amount_4: this.branch?.quick_pay_amount_4 ?? '',
        quick_pay_amount_5: this.branch?.quick_pay_amount_5 ?? '',
        quick_pay_amount_6: this.branch?.quick_pay_amount_6 ?? '',
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.branch?.id
    },

    selectedCurrencyLabel() {
      const found = this.currencies.find(
        c => c.code === this.form.currency
      )
      return found
        ? `${found.code} - ${found.name}`
        : 'Select Currency'
    },

    orderTypeOptionList() {
      return this.normalizeOptions(this.orderTypeOptions)
    },

    paymentMethodOptionList() {
      return this.normalizeOptions(this.paymentMethodOptions)
    },

    selectedTimezoneLabel() {
    return this.form.timezone || 'Select Timezone'
  },

    selectedOrderTypeObjects() {
      return this.orderTypeOptionList.filter((item) => this.form.order_types.includes(item.value))
    },

    selectedPaymentMethodObjects() {
      return this.paymentMethodOptionList.filter((item) => this.form.payment_methods.includes(item.value))
    },

    selectedOrderTypeLabels() {
      return this.selectedOrderTypeObjects.map((item) => item.label)
    },

    selectedPaymentMethodLabels() {
      return this.selectedPaymentMethodObjects.map((item) => item.label)
    },
  },

  methods: {
    normalizeOptions(source) {
      if (Array.isArray(source)) {
        return source.map((item) => {
          if (typeof item === 'string') {
            return { value: item, label: item }
          }

          return {
            value: item.value ?? item.id ?? item.code ?? '',
            label: item.label ?? item.name ?? item.title ?? item.value ?? '',
          }
        }).filter((item) => item.value !== '')
      }

      return Object.entries(source || {}).map(([value, label]) => ({
        value,
        label,
      }))
    },

    nullable(value) {
      return value === '' || value === undefined ? null : value
    },

     toggleTimezone() {
    this.showTimezone = !this.showTimezone
  },

  selectTimezone(tz) {
    this.form.timezone = tz
    this.showTimezone = false
  },
    toggleCurrency() {
      this.showCurrency = !this.showCurrency
    },


    groupError(prefix) {
      if (this.form.errors[prefix]) return this.form.errors[prefix]

      const key = Object.keys(this.form.errors).find((item) => item.startsWith(`${prefix}.`))
      return key ? this.form.errors[key] : ''
    },

    isSelected(list, value) {
      return Array.isArray(list) && list.includes(value)
    },

    toggleDropdown(type) {
      if (type === 'orderTypes') {
        this.showOrderTypesDropdown = !this.showOrderTypesDropdown
        this.showPaymentMethodsDropdown = false
      }

      if (type === 'paymentMethods') {
        this.showPaymentMethodsDropdown = !this.showPaymentMethodsDropdown
        this.showOrderTypesDropdown = false
      }
    },

    closeDropdowns() {
      this.showOrderTypesDropdown = false
      this.showPaymentMethodsDropdown = false
    },

    toggleMultiValue(field, value) {
      const current = Array.isArray(this.form[field]) ? [...this.form[field]] : []
      const index = current.indexOf(value)

      if (index === -1) {
        current.push(value)
      } else {
        current.splice(index, 1)
      }

      this.form[field] = current
    },

    removeMultiValue(field, value) {
      const current = Array.isArray(this.form[field]) ? [...this.form[field]] : []
      this.form[field] = current.filter((item) => item !== value)
    },

    handleDocumentClick(event) {
      const orderWrap = this.$refs.orderTypesDropdownRef
      const paymentWrap = this.$refs.paymentMethodsDropdownRef

      if (orderWrap && !orderWrap.contains(event.target)) {
        this.showOrderTypesDropdown = false
      }

      if (paymentWrap && !paymentWrap.contains(event.target)) {
        this.showPaymentMethodsDropdown = false
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
        if (!window.__branchGoogleMapsPromise) {
          window.__branchGoogleMapsPromise = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-google-maps="branch-form"]')
            if (existingScript) {
              existingScript.addEventListener('load', () => resolve(window.google))
              existingScript.addEventListener('error', () => reject(new Error('Failed to load Google Maps script')))
              return
            }

            const script = document.createElement('script')
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`
            script.async = true
            script.defer = true
            script.dataset.googleMaps = 'branch-form'

            script.onload = () => resolve(window.google)
            script.onerror = () => reject(new Error('Failed to load Google Maps script'))

            document.head.appendChild(script)
          })
        }

        await window.__branchGoogleMapsPromise
        this.googleMapsLoading = false
        return window.google
      } catch (error) {
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
      })

      this.autocompleteInstance.addListener('place_changed', this.onPlaceChanged)
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
      const postalCode = getComponent('postal_code')
      const sublocality =
        getComponent('sublocality') ||
        getComponent('sublocality_level_1') ||
        getComponent('neighborhood')

      const addressLine1 = [streetNumber, routeName].filter(Boolean).join(' ').trim()
      const fallbackFirstPart = (place.formatted_address || '').split(',')[0]?.trim() || place.name || ''

      this.form.address_line_1 = addressLine1 || fallbackFirstPart
      this.form.address_line_2 = this.form.address_line_2 || sublocality || ''
      this.form.city = locality || this.form.city
      this.form.state = state || this.form.state
      this.form.country = country || this.form.country
      this.form.postal_code = postalCode || this.form.postal_code

      if (place.geometry?.location) {
        this.form.latitude = place.geometry.location.lat().toFixed(7)
        this.form.longitude = place.geometry.location.lng().toFixed(7)
      }

      this.locationSearch = place.formatted_address || place.name || this.locationSearch
    },

    normalizedPayload(data) {
      return {
        ...data,
        is_active: data.is_active ? 1 : 0,
        latitude: this.nullable(data.latitude),
        longitude: this.nullable(data.longitude),
        cash_difference_threshold: this.nullable(data.cash_difference_threshold),
        quick_pay_amount_1: this.nullable(data.quick_pay_amount_1),
        quick_pay_amount_2: this.nullable(data.quick_pay_amount_2),
        quick_pay_amount_3: this.nullable(data.quick_pay_amount_3),
        quick_pay_amount_4: this.nullable(data.quick_pay_amount_4),
        quick_pay_amount_5: this.nullable(data.quick_pay_amount_5),
        quick_pay_amount_6: this.nullable(data.quick_pay_amount_6),
        order_types: Array.isArray(data.order_types) ? data.order_types : [],
        payment_methods: Array.isArray(data.payment_methods) ? data.payment_methods : [],
      }
    },

    submit() {
      this.closeDropdowns()

      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.normalizedPayload(data),
            _method: 'PUT',
          }))
          .post(route('vendor.branches.update', this.branch.id), {
            preserveScroll: true,
            onSuccess: () => router.visit(route('vendor.branches.index')),
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
        .transform((data) => this.normalizedPayload(data))
        .post(route('vendor.branches.store'), {
          preserveScroll: true,
          onSuccess: () => router.visit(route('vendor.branches.index')),
          onError: (errors) => {
            const message =
              errors?.general ||
              Object.values(errors)?.flat()?.[0] ||
              'Something went wrong.'

            alertError(message)
          }
        })
    },
  },

  mounted() {
    document.addEventListener('click', this.handleDocumentClick)
    this.initPlacesAutocomplete()
  },

  beforeUnmount() {
    document.removeEventListener('click', this.handleDocumentClick)
    
  },
}
</script>

<style scoped>

/* Form Grid */
.form-content {
  width: 100%;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-bottom: 40px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}



/* Form Groups */
.form-group-wrap {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  width: 100%;
  position: relative;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.location-search {
  width: 100%;
  height: 44px;
  min-height: 44px;
  padding: 10px 16px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
  color: #1a202c;
  background: rgba(255, 255, 255, 0.5);
  transition: all 0.3s ease;
  font-family: inherit;
}

.location-search:focus {
  outline: none;
  border-color: #f28c00;
  background: rgba(255, 255, 255, 0.8);
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
}

.location-search::placeholder {
  color: rgba(0, 0, 0, 0.4);
}

.form-hint {
  font-size: 12px;
  color: #718096;
  margin-top: 8px;
  font-style: italic;
}

/* Checkboxes */
.checkbox-group {
  padding-top: 4px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #4a5568;
  font-weight: 500;
  transition: color 0.2s ease;
}

.checkbox-label:hover {
  color: #1a202c;
}

.phone-row {
    display: flex;
    gap: 10px;
    margin-top: 3px;
    align-items: center;
}

/* COUNTRY DROPDOWN */
.country-dropdown {
    position: relative;
    min-width: 80px !important;
    height: 40px;
    margin-bottom: -26px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    background: #f3f3f3;
    font-size: 14px;
}

.admin-modal__label.fixed {
    position: absolute;
    transform: translateY(-50%);
    font-size: 14px;
    color: #373d44;
    transition: 0.2s ease;
    font-weight: 500;
    pointer-events: none;
    top: -15px;
    left: 0;
}

.country-dropdown i {
    font-size: 12px;
}

/* dropdown list */
.country-list {
    position: absolute;
    top: 110%;
    left: 0;
    font-size: 16px !important;
    width: 100px;
    background: #f0f0f0;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);

    max-height: 200px;      
    overflow-y: auto;       
    z-index: 99999;          
}

.country-item span, .country-dropdown span {
  width: 22px;
  height: 16px;
  display: inline-block;
  margin-right: 5px;
}

.country-item,
.country-dropdown {
     font-family: 'DM Sans', sans-serif;
    font-weight: 500; 
    letter-spacing: 0.3px; 
}

.country-item:hover {
    font-weight: 600;
    color: #0f172a;
}

/* items */
.country-item {
    padding: 10px;
    cursor: pointer;
    font-size: 13px;
}

.country-item:hover {
    background: #f1f5f9;
}


/* Multi-Select */
.multi-select-btn {
  width: 100%;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 16px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.5);
  font-size: 14px;
  font-weight: 500;
  color: #1a202c;
  cursor: pointer;
  transition: all 0.3s ease;
}

.multi-select-btn:hover {
  background: rgba(255, 255, 255, 0.7);
  border-color: rgba(242, 140, 0, 0.25);
}

.multi-select-btn:focus {
  outline: none;
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
}

.select-value {
  text-align: left;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}

.select-placeholder {
  color: rgba(0, 0, 0, 0.4);
  flex: 1;
}

.select-icon {
  flex-shrink: 0;
  color: #718096;
  transition: transform 0.3s ease;
}

.select-icon.open {
  transform: rotate(180deg);
}

.single-select-dropdown {
  position: relative;
  width: 100%;
}

.single-select-trigger {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  color: #2d3748;
}

.single-select-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 50;
   scrollbar-width: none;       
  -ms-overflow-style: none;     
  margin-top: 8px;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  backdrop-filter: blur(10px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
  max-height: 280px;
  overflow-y: auto;
  padding: 6px;
}

.single-select-menu::-webkit-scrollbar {
  display: none; 
}
.single-select-item {
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #4a5568;
  transition: all 0.2s ease;
}

.single-select-item:hover {
  background: rgba(242, 140, 0, 0.1);
  color: #1a202c;
}

.multi-select-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 50;
  margin-top: 8px;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  backdrop-filter: blur(10px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
  max-height: 280px;
  overflow-y: auto;
  padding: 8px;
}

.multi-select-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #4a5568;
  transition: all 0.2s ease;
}

.multi-select-item:hover {
  background: rgba(242, 140, 0, 0.1);
  color: #1a202c;
}

.multi-select-item input {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #f28c00;
}

/* Tags */
.tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.tag-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: rgba(242, 140, 0, 0.12);
  color: #b85c00;
  border: 1px solid rgba(242, 140, 0, 0.3);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.tag-badge:hover {
  background: rgba(242, 140, 0, 0.18);
  border-color: rgba(242, 140, 0, 0.5);
}

.tag-remove {
  border: none;
  background: transparent;
  color: inherit;
  font-size: 18px;
  line-height: 1;
  padding: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.tag-remove:hover {
  opacity: 1;
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
  to { transform: rotate(360deg); }
}

/* Error Text */
.error-text {
  display: block;
  font-size: 12px;
  color: #dc2626;
  margin-top: 6px;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  margin-top: 40px;
}

/* Responsive */
@media (max-width: 1024px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .form-actions {
    flex-direction: row;
  }
}

@media (max-width: 640px) {
  .form-container {
    padding: 24px 16px;
  }

  .formHeader {
    margin-bottom: 32px;
  }

  .header-title {
    font-size: 24px;
  }

  .form-actions {
    width: 100%;
    gap: 12px;
    flex-direction: column;
  }

  .btn {
    flex: 1;
  }

  .form-grid {
    gap: 24px;
  }

  .form-card {
    border-radius: 12px;
  }

  .card-header {
    padding: 16px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .quick-pay-grid {
    grid-template-columns: 1fr;
  }
}
</style>
