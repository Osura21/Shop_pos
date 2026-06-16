<template>
  <teleport to="body">
    <transition name="vendor-drawer-fade">
      <div v-if="open" class="vendor-drawer-backdrop" aria-hidden="true" @click="emitClose"></div>
    </transition>

    <transition name="vendor-drawer-slide">
      <aside
        v-if="open"
        ref="drawer"
        class="vendor-drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="vendorDrawerTitle"
        tabindex="-1"
      >
        <div class="vendor-drawer__shell">
          <section class="vendor-drawer__side">
            <div class="vendor-drawer__top">
              <button type="button" class="vendor-drawer__close" aria-label="Close" @click="emitClose">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="vendor-profile">
              <div class="vendor-profile__avatar">
                <img v-if="vendor?.logo_url" :src="vendor.logo_url" :alt="vendor?.name || 'Vendor logo'" />
                <span v-else>{{ avatarLetter }}</span>
              </div>

              <div class="vendor-profile__name">{{ vendor?.name || 'Vendor' }}</div>
              <div class="vendor-profile__email">{{ vendor?.admin?.email || vendor?.contact || 'No email' }}</div>
              <div class="vendor-profile__status" :class="statusClass">{{ statusLabel(vendor?.status) }}</div>

              <button type="button" class="vendor-status-button" :disabled="savingStatus || loading" @click="toggleVendorStatus">
                <span v-if="savingStatus" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                <i v-else :class="isActive ? 'bi bi-pause-circle' : 'bi bi-play-circle'"></i>
                {{ isActive ? 'Make inactive' : 'Make active' }}
              </button>
            </div>

            <div class="vendor-meta">
              <div class="vendor-meta__item">
                <i class="bi bi-envelope"></i>
                <span>{{ vendor?.admin?.email || 'No admin email' }}</span>
              </div>
              <div class="vendor-meta__item">
                <i class="bi bi-telephone"></i>
                <span>{{ vendor?.admin?.phone || vendor?.contact || 'No phone' }}</span>
              </div>
              <div class="vendor-meta__item">
                <i class="bi bi-globe2"></i>
                <span>{{ vendor?.primary_domain || 'No primary domain' }}</span>
              </div>
              <div class="vendor-meta__item">
                <i class="bi bi-palette"></i>
                <span>{{ vendor?.theme || 'No theme' }}</span>
              </div>
              <div class="vendor-meta__item">
                <i class="bi bi-clock-history"></i>
                <span>Created {{ vendor?.created_at || '-' }}</span>
              </div>
            </div>

            <div class="vendor-drawer__divider"></div>

            <nav class="vendor-tabs" aria-label="Vendor detail sections">
              <button
                type="button"
                class="vendor-tabs__item"
                :class="{ 'vendor-tabs__item--active': activeTab === 'details' }"
                @click="activeTab = 'details'"
              >
                <i class="bi bi-shop-window"></i>
                Vendor details
              </button>
              <button
                type="button"
                class="vendor-tabs__item"
                :class="{ 'vendor-tabs__item--active': activeTab === 'subscription' }"
                @click="activeTab = 'subscription'"
              >
                <i class="bi bi-gem"></i>
                Subscribed plan
              </button>
              <button
                type="button"
                class="vendor-tabs__item"
                :class="{ 'vendor-tabs__item--active': activeTab === 'edit' }"
                @click="activeTab = 'edit'"
              >
                <i class="bi bi-pencil-square"></i>
                Edit details
              </button>
            </nav>
          </section>

          <section class="vendor-drawer__content">
            <div class="vendor-drawer__header">
              <div>
                <div class="vendor-drawer__eyebrow">Vendor workspace</div>
                <h4 id="vendorDrawerTitle" class="vendor-drawer__title">{{ sectionTitle }}</h4>
              </div>
              <span class="vendor-drawer__plan-pill">
                {{ currentPlan?.plan_name || 'No plan' }}
              </span>
            </div>

            <div ref="drawerBody" class="vendor-drawer__body">
              <div v-if="loading" class="vendor-loading">
                <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                Loading vendor details...
              </div>

              <transition name="vendor-section" mode="out-in">
                <div v-if="!loading" :key="activeTab">
                  <div v-if="activeTab === 'details'" class="vendor-details">
                    <div class="vendor-summary-row">
                      <div v-for="item in countCards" :key="item.label" class="vendor-count-card">
                        <i :class="item.icon"></i>
                        <div>
                          <span>{{ item.label }}</span>
                          <strong>{{ item.value }}</strong>
                        </div>
                      </div>
                    </div>

                    <div class="vendor-detail-grid">
                      <div v-for="field in detailFields" :key="field.label" class="vendor-info-card" :class="{ 'vendor-info-card--wide': field.wide }">
                        <span>{{ field.label }}</span>
                        <strong>{{ field.value || '-' }}</strong>
                      </div>
                    </div>

                    <div v-if="vendor?.food_types?.length || vendor?.website_order_types?.length" class="vendor-profile-section">
                      <div class="vendor-profile-section__title">Food & service profile</div>
                      <div v-if="vendor?.food_types?.length" class="vendor-chip-row">
                        <span v-for="type in vendor.food_types" :key="type" class="vendor-chip">{{ type }}</span>
                      </div>
                      <div v-if="vendor?.website_order_types?.length" class="vendor-chip-row">
                        <span v-for="type in vendor.website_order_types" :key="type" class="vendor-chip vendor-chip--service">
                          {{ statusLabel(type) }}
                        </span>
                      </div>
                    </div>

                    <div v-if="vendor?.business_photo_urls?.length || vendor?.business_license_url || restaurantImageEntries.length" class="vendor-profile-section">
                      <div class="vendor-profile-section__title">Business media & documents</div>
                      <div v-if="vendor?.business_photo_urls?.length" class="vendor-media-grid">
                        <a v-for="(url, index) in vendor.business_photo_urls" :key="url" :href="url" target="_blank" class="vendor-media-thumb">
                          <img :src="url" :alt="`Business photo ${index + 1}`">
                        </a>
                      </div>
                      <a v-if="vendor?.business_license_url" :href="vendor.business_license_url" target="_blank" class="vendor-doc-link">
                        <i class="bi bi-file-earmark-check"></i>
                        View business / food license
                      </a>
                      <div v-if="restaurantImageEntries.length" class="vendor-restaurant-images">
                        <a v-for="[key, url] in restaurantImageEntries" :key="key" :href="url" target="_blank" class="vendor-restaurant-image">
                          <img :src="url" :alt="formatImageSlot(key)">
                          <span>{{ formatImageSlot(key) }}</span>
                        </a>
                      </div>
                    </div>
                  </div>

                  <div v-else-if="activeTab === 'subscription'" class="vendor-subscription">
                    <div class="subscription-hero">
                      <div>
                        <span>Subscription pricing</span>
                        <h5>{{ currentPlan?.plan_name || 'No plan selected' }}</h5>
                        <p>{{ currentPlan?.short_description || 'Choose the POS subscription plan used by this vendor.' }}</p>
                      </div>
                      <div class="subscription-price">
                        <strong>{{ formatMoney(currentPlan?.monthly_price, currentPlan?.currency_code) }}</strong>
                        <span>/ month</span>
                      </div>
                    </div>

                    <div class="subscription-card-grid">
                      <div class="subscription-card">
                        <span>Current plan</span>
                        <strong>{{ currentPlan?.plan_name || '-' }}</strong>
                        <small>{{ currentPlan?.plan_code || 'No plan code' }}</small>
                      </div>
                      <div class="subscription-card">
                        <span>Subscription status</span>
                        <strong>{{ statusLabel(vendor?.subscription?.status) }}</strong>
                        <small>Panel {{ vendor?.subscription?.panel_enabled ? 'enabled' : 'disabled' }}</small>
                      </div>
                      <div class="subscription-card">
                        <span>Started</span>
                        <strong>{{ vendor?.subscription?.started_at || '-' }}</strong>
                        <small>Trial ends {{ vendor?.subscription?.trial_ends_at || '-' }}</small>
                      </div>
                    </div>

                    <div v-if="currentPlan?.features?.length" class="subscription-features">
                      <div class="subscription-features__title">Included POS features</div>
                      <div class="subscription-feature-list">
                        <span
                          v-for="feature in currentPlan.features"
                          :key="feature.key || feature.name"
                          class="subscription-feature"
                          :class="{ 'subscription-feature--off': !feature.enabled }"
                        >
                          <i :class="feature.enabled ? 'bi bi-check-circle-fill' : 'bi bi-x-circle'"></i>
                          {{ feature.name }}
                          <em v-if="feature.limit">{{ feature.limit }}</em>
                        </span>
                      </div>
                    </div>

                    <div class="plan-editor">
                      <div class="plan-editor__head">
                        <div>
                          <span>Change vendor subscription plan</span>
                          <h5>Plan assignment</h5>
                        </div>
                        <span class="vendor-override-pill">Vendor override</span>
                      </div>

                      <div class="plan-editor__form">
                        <SelectInput
                          id="vendorDrawerPlan"
                          v-model="selectedPlanId"
                          label="Plan"
                          :options="planOptions"
                          value-key="id"
                          label-key="label"
                          placeholder="Use default plan"
                          :disabled="savingPlan"
                        />

                        <SelectInput
                          id="vendorDrawerStatus"
                          v-model="selectedSubscriptionStatus"
                          label="Subscription Status"
                          :options="subscriptionStatusOptions"
                          value-key="value"
                          label-key="label"
                          placeholder="Select status"
                          :clearable="false"
                          :disabled="savingPlan"
                        />
                      </div>

                      <div class="selected-plan-row">
                        <div>
                          <span>Selected price</span>
                          <strong>{{ formatMoney(selectedPlan?.monthly_price, selectedPlan?.currency_code) }} / month</strong>
                        </div>
                        <div>
                          <span>Yearly</span>
                          <strong>{{ formatMoney(selectedPlan?.yearly_price, selectedPlan?.currency_code) }}</strong>
                        </div>
                        <div>
                          <span>Renewal</span>
                          <strong>{{ selectedPlan?.auto_renew ? 'Auto' : 'Manual' }}</strong>
                        </div>
                      </div>

                      <div class="plan-editor__actions">
                        <button type="button" class="vendor-btn vendor-btn--light" :disabled="savingPlan" @click="useDefaultPlan">
                          Use default
                        </button>
                        <button type="button" class="vendor-btn vendor-btn--primary" :disabled="savingPlan" @click="saveSubscriptionPlan">
                          <span v-if="savingPlan" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                          {{ savingPlan ? 'Saving...' : 'Save plan' }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <div v-else class="vendor-profile-editor">
                    <div class="plan-editor">
                      <div class="plan-editor__head">
                        <div>
                          <span>Editable vendor profile</span>
                          <h5>Business details</h5>
                        </div>
                        <span class="vendor-override-pill">Super Admin</span>
                      </div>

                      <div class="profile-editor-grid">
                        <label>
                          <span>Vendor Name</span>
                          <input v-model="editProfileForm.name" type="text">
                          <em v-if="profileErrors.name">{{ profileErrors.name }}</em>
                        </label>
                        <label>
                          <span>Legal Business Name</span>
                          <input v-model="editProfileForm.legal_business_name" type="text">
                        </label>
                        <label>
                          <span>Store Display Name</span>
                          <input v-model="editProfileForm.store_display_name" type="text">
                        </label>
                        <label>
                          <span>Owner / Manager</span>
                          <input v-model="editProfileForm.owner_name" type="text">
                        </label>
                        <label>
                          <span>Business Email</span>
                          <input v-model="editProfileForm.business_email" type="email">
                          <em v-if="profileErrors.business_email">{{ profileErrors.business_email }}</em>
                        </label>
                        <label>
                          <span>Store Contact</span>
                          <input v-model="editProfileForm.contact" type="text">
                        </label>
                        <label>
                          <span>Admin Name</span>
                          <input v-model="editProfileForm.admin_name" type="text">
                        </label>
                        <label>
                          <span>Admin Email</span>
                          <input v-model="editProfileForm.admin_email" type="email">
                          <em v-if="profileErrors.admin_email">{{ profileErrors.admin_email }}</em>
                        </label>
                        <label>
                          <span>Admin Phone</span>
                          <input v-model="editProfileForm.admin_phone" type="text">
                          <em v-if="profileErrors.admin_phone">{{ profileErrors.admin_phone }}</em>
                        </label>
                        <label>
                          <span>Business Registration No.</span>
                          <input v-model="editProfileForm.business_registration_number" type="text">
                        </label>
                        <label class="profile-editor-grid__wide">
                          <span>Business Address</span>
                          <textarea v-model="editProfileForm.address" rows="3"></textarea>
                        </label>
                        <label>
                          <span>Address Line 1</span>
                          <input v-model="editProfileForm.address_line_1" type="text">
                        </label>
                        <label>
                          <span>Address Line 2</span>
                          <input v-model="editProfileForm.address_line_2" type="text">
                        </label>
                        <label>
                          <span>City</span>
                          <input v-model="editProfileForm.city" type="text">
                        </label>
                        <label>
                          <span>State / Province</span>
                          <input v-model="editProfileForm.state_province" type="text">
                        </label>
                        <label>
                          <span>Postal Code</span>
                          <input v-model="editProfileForm.postal_code" type="text">
                        </label>
                        <label>
                          <span>Country</span>
                          <input v-model="editProfileForm.country" type="text">
                        </label>
                        <label>
                          <span>Country Code</span>
                          <input v-model="editProfileForm.country_code" type="text" maxlength="2">
                        </label>
                        <label class="profile-editor-grid__wide">
                          <span>Google Search Location</span>
                          <input v-model="editProfileForm.search_location" type="text">
                        </label>
                        <label>
                          <span>Google Place ID</span>
                          <input v-model="editProfileForm.google_place_id" type="text">
                        </label>
                        <label>
                          <span>Opening From</span>
                          <input v-model="editProfileForm.opening_from" type="time">
                        </label>
                        <label>
                          <span>Opening To</span>
                          <input v-model="editProfileForm.opening_to" type="time">
                        </label>
                        <label class="profile-editor-grid__wide">
                          <span>Food Types</span>
                          <input v-model="editProfileForm.food_types_text" type="text" placeholder="Pizza, Burgers">
                        </label>
                        <label class="profile-editor-grid__wide">
                          <span>Service Options</span>
                          <input v-model="editProfileForm.website_order_types_text" type="text" placeholder="Delivery, Pickup, Table Booking">
                        </label>
                      </div>

                      <div class="plan-editor__actions">
                        <button type="button" class="vendor-btn vendor-btn--primary" :disabled="savingProfile" @click="saveProfileDetails">
                          <span v-if="savingProfile" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                          {{ savingProfile ? 'Saving...' : 'Save details' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </transition>
            </div>
          </section>
        </div>
      </aside>
    </transition>
  </teleport>
</template>

<script>
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'

export default {
  name: 'VendorViewOffcanvas',

  components: {
    SelectInput,
  },

  props: {
    open: {
      type: Boolean,
      default: false,
    },
    vendor: {
      type: Object,
      default: () => null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['close', 'vendor-updated'],

  data() {
    return {
      activeTab: 'details',
      savingPlan: false,
      savingStatus: false,
      savingProfile: false,
      selectedPlanId: '',
      selectedSubscriptionStatus: 'active',
      profileErrors: {},
      editProfileForm: {},
      subscriptionStatusOptions: [
        { value: 'active', label: 'Active' },
        { value: 'trialing', label: 'Trialing' },
        { value: 'past_due', label: 'Past Due' },
        { value: 'suspended', label: 'Suspended' },
        { value: 'cancelled', label: 'Cancelled' },
        { value: 'inactive', label: 'Inactive' },
      ],
    }
  },

  computed: {
    avatarLetter() {
      return String(this.vendor?.name || 'V').trim().charAt(0).toUpperCase()
    },

    isActive() {
      return String(this.vendor?.status || 'active').toLowerCase() === 'active'
    },

    statusClass() {
      return this.isActive ? 'vendor-profile__status--active' : 'vendor-profile__status--inactive'
    },

    sectionTitle() {
      if (this.activeTab === 'subscription') return 'Subscribed plan'
      if (this.activeTab === 'edit') return 'Edit vendor details'
      return 'Vendor details'
    },

    currentPlan() {
      return this.vendor?.subscription?.plan || null
    },

    planOptions() {
      return this.vendor?.available_subscription_plans || []
    },

    selectedPlan() {
      return this.planOptions.find((plan) => String(plan.id) === String(this.selectedPlanId)) || this.currentPlan
    },

    countCards() {
      const counts = this.vendor?.counts || {}

      return [
        { label: 'Domains', value: counts.domains ?? 0, icon: 'bi bi-globe2' },
        { label: 'Branches', value: counts.branches ?? 0, icon: 'bi bi-diagram-3' },
        { label: 'Products', value: counts.products ?? 0, icon: 'bi bi-box-seam' },
        { label: 'Users', value: counts.users ?? 0, icon: 'bi bi-people' },
      ]
    },

    detailFields() {
      return [
        { label: 'Vendor name', value: this.vendor?.name },
        { label: 'Legal business name', value: this.vendor?.legal_business_name },
        { label: 'Store display name', value: this.vendor?.store_display_name },
        { label: 'Slug', value: this.vendor?.slug },
        { label: 'Primary domain', value: this.vendor?.primary_domain },
        { label: 'Theme', value: this.vendor?.theme },
        { label: 'Admin name', value: this.vendor?.admin?.name },
        { label: 'Admin email', value: this.vendor?.admin?.email },
        { label: 'Admin phone', value: this.vendor?.admin?.phone || this.vendor?.contact },
        { label: 'Business email', value: this.vendor?.business_email },
        { label: 'Owner / manager', value: this.vendor?.owner_name },
        { label: 'Business registration no.', value: this.vendor?.business_registration_number },
        { label: 'Status', value: this.statusLabel(this.vendor?.status) },
        { label: 'Address', value: this.vendor?.address, wide: true },
        { label: 'Address line 1', value: this.vendor?.address_line_1 },
        { label: 'Address line 2', value: this.vendor?.address_line_2 },
        { label: 'City', value: this.vendor?.city },
        { label: 'State / Province', value: this.vendor?.state_province },
        { label: 'Postal code', value: this.vendor?.postal_code },
        { label: 'Country', value: this.vendor?.country },
        { label: 'Country code', value: this.vendor?.country_code },
        { label: 'Google place ID', value: this.vendor?.google_place_id, wide: true },
        { label: 'Opening hours', value: [this.vendor?.opening_from, this.vendor?.opening_to].filter(Boolean).join(' - ') },
        { label: 'Source request ID', value: this.vendor?.source_requested_vendor_id },
        { label: 'Created', value: this.vendor?.created_at },
        { label: 'Updated', value: this.vendor?.updated_at },
      ]
    },

    restaurantImageEntries() {
      return Object.entries(this.vendor?.restaurant_page_image_urls || {}).filter(([, url]) => Boolean(url))
    },
  },

  watch: {
    open: {
      immediate: true,
      handler(value) {
        document.body.style.overflow = value ? 'hidden' : ''

        if (value) {
          this.activeTab = 'details'
          window.addEventListener('keydown', this.handleKeydown)

          this.$nextTick(() => {
            this.$refs.drawer?.focus?.()
            this.scrollDrawerBodyToTop()
          })
        } else {
          window.removeEventListener('keydown', this.handleKeydown)
        }
      },
    },

    activeTab() {
      this.scrollDrawerBodyToTop()
    },
    vendor: {
      immediate: true,
      deep: true,
      handler(value) {
        this.selectedPlanId = value?.subscription?.plan_id ? Number(value.subscription.plan_id) : ''
        this.selectedSubscriptionStatus = value?.subscription?.status || 'active'
        this.fillProfileForm(value)
      },
    },
  },

  beforeUnmount() {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', this.handleKeydown)
  },

  methods: {
    handleKeydown(event) {
      if (event.key === 'Escape') {
        this.emitClose()
      }
    },
    emitClose() {
      this.$emit('close')
    },

    scrollDrawerBodyToTop() {
      this.$nextTick(() => {
        if (this.$refs.drawerBody) {
          this.$refs.drawerBody.scrollTop = 0
        }
      })
    },
    statusLabel(value) {
      const status = String(value || 'active').replaceAll('_', ' ')
      return status.charAt(0).toUpperCase() + status.slice(1)
    },

    formatMoney(value, currency = 'LKR') {
      const amount = Number(value ?? 0)
      return `${currency || 'LKR'} ${Number.isFinite(amount) ? amount.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '0'}`
    },

    formatImageSlot(value = '') {
      return String(value)
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
    },

    fillProfileForm(vendor = this.vendor) {
      this.profileErrors = {}
      this.editProfileForm = {
        name: vendor?.name || '',
        legal_business_name: vendor?.legal_business_name || '',
        store_display_name: vendor?.store_display_name || '',
        address: vendor?.address || '',
        contact: vendor?.contact || '',
        business_email: vendor?.business_email || '',
        owner_name: vendor?.owner_name || '',
        business_registration_number: vendor?.business_registration_number || '',
        country: vendor?.country || '',
        country_code: vendor?.country_code || '',
        search_location: vendor?.search_location || '',
        google_place_id: vendor?.google_place_id || '',
        postal_code: vendor?.postal_code || '',
        address_line_1: vendor?.address_line_1 || '',
        address_line_2: vendor?.address_line_2 || '',
        state_province: vendor?.state_province || '',
        city: vendor?.city || '',
        food_types_text: (vendor?.food_types || []).join(', '),
        website_order_types_text: (vendor?.website_order_types || []).join(', '),
        opening_from: vendor?.opening_from || '',
        opening_to: vendor?.opening_to || '',
        admin_name: vendor?.admin?.name || '',
        admin_email: vendor?.admin?.email || '',
        admin_phone: vendor?.admin?.phone || '',
      }
    },

    splitCsv(value = '') {
      return String(value)
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)
    },

    validationErrors(error) {
      const errors = error?.response?.data?.errors || {}
      return Object.fromEntries(Object.entries(errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]))
    },

    async toggleVendorStatus() {
      if (!this.vendor?.id || this.savingStatus) return

      this.savingStatus = true

      try {
        const response = await fetch(route('vendors.toggleStatus', this.vendor.id), {
          method: 'PATCH',
          headers: this.jsonHeaders(),
        })
        const data = await response.json()

        if (!response.ok || !data?.vendor) {
          throw new Error(data?.message || 'Unable to update vendor status.')
        }

        alertSuccess(data.message || 'Vendor status updated.')
        this.$emit('vendor-updated', data.vendor)
      } catch (err) {
        alertError(err.message || 'Unable to update vendor status.')
      } finally {
        this.savingStatus = false
      }
    },

    async saveProfileDetails() {
      if (!this.vendor?.id || this.savingProfile) return

      this.savingProfile = true
      this.profileErrors = {}

      try {
        const payload = {
          ...this.editProfileForm,
          food_types: this.splitCsv(this.editProfileForm.food_types_text),
          website_order_types: this.splitCsv(this.editProfileForm.website_order_types_text),
        }

        delete payload.food_types_text
        delete payload.website_order_types_text

        const response = await fetch(route('vendors.updateProfileDetails', this.vendor.id), {
          method: 'PATCH',
          headers: this.jsonHeaders(),
          body: JSON.stringify(payload),
        })
        const data = await response.json()

        if (!response.ok || !data?.vendor) {
          const error = new Error(data?.message || 'Unable to update vendor details.')
          error.response = { data }
          throw error
        }

        alertSuccess(data.message || 'Vendor details updated.')
        this.$emit('vendor-updated', data.vendor)
        this.fillProfileForm(data.vendor)
      } catch (err) {
        this.profileErrors = this.validationErrors(err)
        alertError(err.message || 'Unable to update vendor details.')
      } finally {
        this.savingProfile = false
      }
    },

    async saveSubscriptionPlan() {
      if (!this.vendor?.id || this.savingPlan) return

      this.savingPlan = true

      try {
        const response = await fetch(route('vendors.updateSubscription', this.vendor.id), {
          method: 'PATCH',
          headers: this.jsonHeaders(),
          body: JSON.stringify({
            vendor_subscription_plan_id: this.selectedPlanId || null,
            vendor_subscription_status: this.selectedSubscriptionStatus || 'active',
          }),
        })
        const data = await response.json()

        if (!response.ok || !data?.vendor) {
          throw new Error(data?.message || 'Unable to update vendor subscription.')
        }

        alertSuccess(data.message || 'Vendor subscription updated.')
        this.$emit('vendor-updated', data.vendor)
      } catch (err) {
        alertError(err.message || 'Unable to update vendor subscription.')
      } finally {
        this.savingPlan = false
      }
    },

    useDefaultPlan() {
      this.selectedPlanId = ''
      this.saveSubscriptionPlan()
    },

    jsonHeaders() {
      return {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    },
  },
}
</script>

<style scoped>
.vendor-drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.42);
  z-index: 1040;
}

.vendor-drawer {
  position: fixed;
  top: 0;
  right: 0;
  width: min(88vw, 1320px);
  max-width: 100vw;
  height: 100vh;
  background: #fff;
  z-index: 1050;
  box-shadow: -18px 0 44px rgba(15, 23, 42, 0.18);
  overflow: hidden;
}

.vendor-drawer__shell {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  height: 100%;
}

.vendor-drawer__side {
  background: #fff;
  border-right: 1px solid rgba(15, 23, 42, 0.08);
  padding: 18px 22px 24px;
  overflow-y: auto;
}

.vendor-drawer__top {
  display: flex;
  margin-bottom: 12px;
}

.vendor-drawer__close {
  width: 42px;
  height: 42px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: #fff;
  color: #334155;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: 0.18s ease;
}

.vendor-drawer__close:hover {
  border-color: #fb923c;
  color: #f97316;
  background: #fff7ed;
}

.vendor-profile {
  text-align: center;
  padding: 8px 0 10px;
}

.vendor-profile__avatar {
  width: 88px;
  height: 88px;
  border-radius: 999px;
  background: linear-gradient(135deg, #fff7ed, #fed7aa);
  color: #c2410c;
  font-size: 38px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  overflow: hidden;
}

.vendor-profile__avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.vendor-profile__name {
  color: #0f172a;
  font-size: 28px;
  font-weight: 800;
  line-height: 1.2;
  word-break: break-word;
}

.vendor-profile__email {
  color: #64748b;
  font-size: 14px;
  margin: 7px 0 12px;
  word-break: break-word;
}

.vendor-profile__status,
.vendor-drawer__plan-pill,
.vendor-override-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.vendor-profile__status {
  min-width: 92px;
  padding: 8px 14px;
}

.vendor-profile__status--active {
  background: #dcfce7;
  color: #15803d;
}

.vendor-profile__status--inactive {
  background: #fee2e2;
  color: #dc2626;
}

.vendor-status-button {
  width: 100%;
  height: 42px;
  margin-top: 14px;
  border-radius: 999px;
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #ea580c;
  font-size: 13px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: 0.18s ease;
}

.vendor-status-button:hover:not(:disabled) {
  background: #ffedd5;
  border-color: #fb923c;
}

.vendor-meta {
  display: grid;
  gap: 12px;
  margin-top: 22px;
}

.vendor-meta__item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: #475569;
  font-size: 14px;
  line-height: 1.5;
}

.vendor-meta__item i {
  color: #f97316;
  font-size: 16px;
  margin-top: 2px;
}

.vendor-drawer__divider {
  height: 1px;
  background: rgba(15, 23, 42, 0.08);
  margin: 24px 0;
}

.vendor-tabs {
  display: grid;
  gap: 8px;
}

.vendor-tabs__item {
  width: 100%;
  min-height: 48px;
  border: 0;
  border-radius: 12px;
  background: transparent;
  color: #475569;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 0 14px;
  text-align: left;
  font-weight: 800;
  transition: 0.18s ease;
}

.vendor-tabs__item:hover,
.vendor-tabs__item--active {
  background: #fff7ed;
  color: #ea580c;
}

.vendor-drawer__content {
  min-width: 0;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
}

.vendor-drawer__header {
  min-height: 86px;
  padding: 22px 28px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.vendor-drawer__eyebrow {
  color: #f97316;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
}

.vendor-drawer__title {
  margin: 4px 0 0;
  color: #0f172a;
  font-size: 30px;
  font-weight: 800;
}

.vendor-drawer__plan-pill,
.vendor-override-pill {
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #ea580c;
  padding: 9px 13px;
  white-space: nowrap;
}

.vendor-drawer__body {
  flex: 1;
  overflow-y: auto;
  padding: 26px 28px 30px;
}

.vendor-loading {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 16px;
  padding: 22px 24px;
  color: #475569;
  font-weight: 700;
}

.vendor-summary-row {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.vendor-count-card,
.vendor-info-card,
.subscription-hero,
.subscription-card,
.plan-editor {
  background: #fff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 16px;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
}

.vendor-count-card {
  min-height: 84px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 13px;
}

.vendor-count-card i {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: #fff7ed;
  color: #f97316;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.vendor-count-card span,
.vendor-info-card span,
.subscription-card span,
.subscription-hero span,
.plan-editor__head span,
.selected-plan-row span,
.subscription-features__title {
  color: #64748b;
  display: block;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.vendor-count-card strong {
  color: #0f172a;
  display: block;
  font-size: 21px;
  line-height: 1.2;
}

.vendor-detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.vendor-info-card {
  min-height: 88px;
  padding: 18px;
}

.vendor-info-card--wide {
  grid-column: 1 / -1;
}

.vendor-info-card strong {
  color: #0f172a;
  display: block;
  font-size: 16px;
  margin-top: 8px;
  word-break: break-word;
}

.vendor-profile-section {
  margin-top: 18px;
  padding: 18px;
  border-radius: 16px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: #ffffff;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
}

.vendor-profile-section__title {
  color: #64748b;
  display: block;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  margin-bottom: 12px;
}

.vendor-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 9px;
  margin-bottom: 10px;
}

.vendor-chip {
  min-height: 34px;
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #ea580c;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 800;
}

.vendor-chip--service {
  border-color: #bbf7d0;
  background: #f0fdf4;
  color: #15803d;
}

.vendor-media-grid,
.vendor-restaurant-images {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.vendor-media-thumb,
.vendor-restaurant-image {
  overflow: hidden;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: #f8fafc;
  color: #0f172a;
  text-decoration: none;
}

.vendor-media-thumb img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
}

.vendor-doc-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 12px 0;
  color: #ea580c;
  font-weight: 900;
  text-decoration: none;
}

.vendor-restaurant-images {
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-top: 12px;
}

.vendor-restaurant-image img {
  width: 100%;
  aspect-ratio: 1.45;
  object-fit: cover;
}

.vendor-restaurant-image span {
  display: block;
  padding: 8px 10px;
  color: #0f172a;
  font-size: 12px;
  font-weight: 900;
}

.vendor-subscription {
  display: grid;
  gap: 18px;
}

.subscription-hero {
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  border-color: #fed7aa;
  background: linear-gradient(135deg, #fff, #fff7ed);
}

.subscription-hero h5,
.plan-editor__head h5 {
  margin: 6px 0;
  color: #0f172a;
  font-size: 22px;
  font-weight: 800;
}

.subscription-hero p {
  margin: 0;
  color: #64748b;
  line-height: 1.5;
}

.subscription-price {
  min-width: 190px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid #fed7aa;
  padding: 16px;
  text-align: right;
}

.subscription-price strong {
  color: #ea580c;
  display: block;
  font-size: 26px;
  line-height: 1.1;
}

.subscription-price span {
  color: #64748b;
  font-weight: 700;
}

.subscription-card-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}

.subscription-card {
  padding: 18px;
}

.subscription-card strong {
  display: block;
  color: #0f172a;
  font-size: 18px;
  margin-top: 8px;
}

.subscription-card small {
  color: #64748b;
  display: block;
  margin-top: 6px;
}

.subscription-features {
  background: #fff;
  border-radius: 16px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  padding: 18px;
}

.subscription-feature-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.subscription-feature {
  min-height: 34px;
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #14532d;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 11px;
  font-size: 13px;
  font-weight: 800;
}

.subscription-feature i {
  color: #16a34a;
}

.subscription-feature em {
  color: #64748b;
  font-style: normal;
}

.subscription-feature--off {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #64748b;
}

.subscription-feature--off i {
  color: #94a3b8;
}

.plan-editor {
  padding: 18px;
}

.plan-editor__head,
.plan-editor__actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.plan-editor__form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 260px;
  gap: 14px;
  margin-top: 14px;
}

.profile-editor-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-top: 16px;
}

.profile-editor-grid__wide {
  grid-column: 1 / -1;
}

.profile-editor-grid label {
  display: grid;
  gap: 7px;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.profile-editor-grid input,
.profile-editor-grid textarea {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  color: #0f172a;
  padding: 11px 12px;
  font-size: 14px;
  font-weight: 700;
  text-transform: none;
  outline: none;
}

.profile-editor-grid input:focus,
.profile-editor-grid textarea:focus {
  border-color: #fb923c;
  box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.14);
}

.profile-editor-grid em {
  color: #dc2626;
  font-style: normal;
  text-transform: none;
}

.selected-plan-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.selected-plan-row > div {
  min-height: 74px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 14px;
  background: #f8fafc;
  padding: 14px;
}

.selected-plan-row strong {
  color: #0f172a;
  display: block;
  margin-top: 7px;
  font-size: 15px;
}

.plan-editor__actions {
  justify-content: flex-start;
  margin-top: 18px;
}

.vendor-btn {
  min-height: 40px;
  border-radius: 12px;
  border: 1px solid transparent;
  padding: 0 16px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.vendor-btn--light {
  background: #fff7ed;
  border-color: #fed7aa;
  color: #ea580c;
}

.vendor-btn--primary {
  background: linear-gradient(135deg, #f97316, #fb923c);
  color: #fff;
  box-shadow: 0 14px 24px rgba(249, 115, 22, 0.22);
}

.vendor-drawer-fade-enter-active,
.vendor-drawer-fade-leave-active {
  transition: opacity 0.22s ease;
}

.vendor-drawer-fade-enter-from,
.vendor-drawer-fade-leave-to {
  opacity: 0;
}

.vendor-drawer-slide-enter-active,
.vendor-drawer-slide-leave-active {
  transition: transform 0.28s ease;
}

.vendor-drawer-slide-enter-from,
.vendor-drawer-slide-leave-to {
  transform: translateX(100%);
}

.vendor-section-enter-active,
.vendor-section-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.vendor-section-enter-from,
.vendor-section-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@media (max-width: 1199.98px) {
  .vendor-drawer {
    width: 96vw;
  }

  .vendor-summary-row,
  .subscription-card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 991.98px) {
  .vendor-drawer {
    width: 100vw;
  }

  .vendor-drawer__shell {
    grid-template-columns: 280px minmax(0, 1fr);
  }

  .vendor-detail-grid,
  .plan-editor__form,
  .selected-plan-row,
  .profile-editor-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .vendor-drawer__shell {
    grid-template-columns: 1fr;
    grid-template-rows: auto minmax(0, 1fr);
  }

  .vendor-drawer__side {
    border-right: 0;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    padding: 16px;
  }

  .vendor-tabs {
    display: flex;
    overflow-x: auto;
  }

  .vendor-tabs__item {
    width: auto;
    flex: 0 0 auto;
    white-space: nowrap;
  }

  .vendor-drawer__header,
  .subscription-hero,
  .plan-editor__head {
    align-items: flex-start;
    flex-direction: column;
  }

  .vendor-drawer__body {
    padding: 18px;
  }

  .vendor-summary-row,
  .subscription-card-grid,
  .vendor-media-grid,
  .vendor-restaurant-images {
    grid-template-columns: 1fr;
  }

  .vendor-profile__name,
  .vendor-drawer__title {
    font-size: 24px;
  }

  .subscription-price {
    min-width: 0;
    width: 100%;
    text-align: left;
  }
}

/* Extra completed responsive behavior */
.vendor-drawer,
.vendor-drawer * {
  box-sizing: border-box;
}

.vendor-drawer__side,
.vendor-drawer__body {
  scrollbar-width: thin;
  scrollbar-color: rgba(249, 115, 22, 0.45) transparent;
}

.vendor-drawer__side::-webkit-scrollbar,
.vendor-drawer__body::-webkit-scrollbar,
.vendor-tabs::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}

.vendor-drawer__side::-webkit-scrollbar-thumb,
.vendor-drawer__body::-webkit-scrollbar-thumb,
.vendor-tabs::-webkit-scrollbar-thumb {
  background: rgba(249, 115, 22, 0.45);
  border-radius: 999px;
}

.vendor-drawer :where(button, [role="button"], input, select, textarea, .select-input):focus-visible {
  outline: 3px solid rgba(249, 115, 22, 0.32);
  outline-offset: 2px;
}

.vendor-btn:disabled,
.vendor-status-button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

@media (max-width: 575.98px) {
  .vendor-drawer {
    width: 100vw;
    height: 100dvh;
    border-radius: 0;
  }

  .vendor-drawer__shell {
    height: 100dvh;
  }

  .vendor-drawer__side {
    max-height: 43dvh;
    overflow-y: auto;
    padding: 12px 14px;
  }

  .vendor-drawer__top {
    justify-content: flex-end;
    margin-bottom: 4px;
  }

  .vendor-drawer__close {
    width: 38px;
    height: 38px;
  }

  .vendor-profile {
    display: grid;
    grid-template-columns: 58px minmax(0, 1fr);
    column-gap: 12px;
    text-align: left;
    align-items: center;
    padding: 0;
  }

  .vendor-profile__avatar {
    grid-row: span 4;
    width: 58px;
    height: 58px;
    margin: 0;
    font-size: 24px;
  }

  .vendor-profile__name {
    font-size: 18px;
  }

  .vendor-profile__email {
    margin: 3px 0 7px;
    font-size: 12px;
  }

  .vendor-profile__status {
    min-width: 0;
    width: fit-content;
    padding: 6px 10px;
  }

  .vendor-status-button {
    grid-column: 1 / -1;
    height: 38px;
    margin-top: 12px;
  }

  .vendor-meta {
    grid-template-columns: 1fr;
    gap: 8px;
    margin-top: 12px;
  }

  .vendor-meta__item {
    font-size: 12px;
  }

  .vendor-drawer__divider {
    margin: 14px 0;
  }

  .vendor-tabs {
    margin: 0 -14px;
    padding: 0 14px 4px;
  }

  .vendor-tabs__item {
    min-height: 40px;
    border-radius: 999px;
    padding: 0 12px;
    font-size: 12px;
  }

  .vendor-drawer__content {
    min-height: 0;
  }

  .vendor-drawer__header {
    min-height: auto;
    padding: 14px 16px;
    gap: 10px;
  }

  .vendor-drawer__title {
    font-size: 20px;
  }

  .vendor-drawer__plan-pill,
  .vendor-override-pill {
    max-width: 100%;
    white-space: normal;
    text-align: left;
  }

  .vendor-drawer__body {
    padding: 14px;
  }

  .vendor-loading {
    width: 100%;
    justify-content: center;
  }

  .vendor-count-card,
  .vendor-info-card,
  .subscription-card,
  .plan-editor,
  .subscription-features,
  .subscription-hero {
    border-radius: 14px;
  }

  .vendor-count-card {
    min-height: 72px;
    padding: 13px;
  }

  .vendor-count-card i {
    width: 38px;
    height: 38px;
  }

  .vendor-info-card {
    min-height: auto;
    padding: 14px;
  }

  .subscription-hero,
  .plan-editor {
    padding: 14px;
  }

  .subscription-hero h5,
  .plan-editor__head h5 {
    font-size: 18px;
  }

  .subscription-price strong {
    font-size: 22px;
  }

  .subscription-feature-list {
    gap: 8px;
  }

  .subscription-feature {
    width: 100%;
    justify-content: flex-start;
    border-radius: 12px;
  }

  .plan-editor__actions {
    flex-direction: column-reverse;
    align-items: stretch;
  }

  .vendor-btn {
    width: 100%;
    min-height: 44px;
  }
}

@media (max-width: 374.98px) {
  .vendor-profile {
    grid-template-columns: 48px minmax(0, 1fr);
  }

  .vendor-profile__avatar {
    width: 48px;
    height: 48px;
    font-size: 20px;
  }

  .vendor-profile__name,
  .vendor-drawer__title {
    font-size: 18px;
  }

  .vendor-drawer__side,
  .vendor-drawer__body {
    padding-left: 12px;
    padding-right: 12px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .vendor-drawer-fade-enter-active,
  .vendor-drawer-fade-leave-active,
  .vendor-drawer-slide-enter-active,
  .vendor-drawer-slide-leave-active,
  .vendor-section-enter-active,
  .vendor-section-leave-active,
  .vendor-drawer__close,
  .vendor-tabs__item,
  .vendor-status-button {
    transition: none;
  }
}


/* FINAL SCROLL FIX: keeps header visible and makes the inside content scroll correctly */
.vendor-drawer {
  height: 100vh;
  height: 100dvh;
  display: block;
}

.vendor-drawer__shell {
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.vendor-drawer__side {
  height: 100%;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.vendor-drawer__content {
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.vendor-drawer__header {
  flex: 0 0 auto;
}

.vendor-drawer__body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
  padding-bottom: max(30px, env(safe-area-inset-bottom));
}

.vendor-details,
.vendor-subscription {
  min-height: 0;
}

@media (max-width: 767.98px) {
  .vendor-drawer {
    inset: 0;
    width: 100vw;
    max-width: 100vw;
  }

  .vendor-drawer__shell {
    height: 100vh;
    height: 100dvh;
    grid-template-columns: 1fr;
    grid-template-rows: auto minmax(0, 1fr);
  }

  .vendor-drawer__side {
    height: auto;
    max-height: 48vh;
    max-height: 48dvh;
    overflow-y: auto;
  }

  .vendor-drawer__content {
    height: 100%;
    min-height: 0;
  }

  .vendor-drawer__body {
    min-height: 0;
    padding-bottom: max(26px, env(safe-area-inset-bottom));
  }
}

@media (max-width: 575.98px) {
  .vendor-drawer__side {
    max-height: 44vh;
    max-height: 44dvh;
  }

  .vendor-drawer__header {
    position: sticky;
    top: 0;
    z-index: 2;
  }

  .vendor-drawer__body {
    padding-bottom: max(34px, env(safe-area-inset-bottom));
  }
}
</style>
