<template>
  <Head title="My Account" />

  <div class="profile-page">
    <section class="profile-hero">
      <div class="profile-hero__identity">
        <div class="profile-avatar">
          <img v-if="vendor.logo_url" :src="vendor.logo_url" :alt="vendorName" />
          <span v-else>{{ initials(vendorName || account.name) }}</span>
        </div>
        <div>
          <span class="profile-eyebrow">Vendor Account</span>
          <h1>{{ vendorName }}</h1>
          <p>{{ account.email || 'No email configured' }}</p>
          <div class="hero-badges">
            <span class="status-pill" :class="account.status ? 'status-pill--active' : 'status-pill--inactive'">
              {{ account.status ? 'Active Account' : 'Inactive Account' }}
            </span>
            <span class="status-pill status-pill--soft">
              {{ account.role || 'Vendor User' }}
            </span>
          </div>
        </div>
      </div>

      <div class="profile-hero__membership">
        <span>Membership</span>
        <strong>{{ membership.plan?.name || 'No Plan Assigned' }}</strong>
        <p>{{ prettyStatus(membership.status) }}</p>
      </div>
    </section>

    <div class="profile-tabs">
      <button type="button" :class="{ active: activeTab === 'profile' }" @click="activeTab = 'profile'">
        <i class="bi bi-person-badge"></i>
        Profile Details
      </button>
      <button type="button" :class="{ active: activeTab === 'membership' }" @click="activeTab = 'membership'">
        <i class="bi bi-stars"></i>
        My Membership
      </button>
    </div>

    <section v-if="activeTab === 'profile'" class="profile-content">
      <div class="profile-card profile-card--wide">
        <div class="section-title">
          <div>
            <span>Account</span>
            <h2>Profile Details</h2>
          </div>
          <i class="bi bi-person-lines-fill"></i>
        </div>

        <div class="detail-grid">
          <InfoItem label="Full Name" :value="account.name" />
          <InfoItem label="Username" :value="account.username" />
          <InfoItem label="Email" :value="account.email" />
          <InfoItem label="Phone" :value="account.phone" />
          <InfoItem label="Gender" :value="prettyStatus(account.gender)" />
          <InfoItem label="Assigned Branch" :value="account.branch" />
          <InfoItem label="Created At" :value="account.created_at" />
          <InfoItem label="Last Updated" :value="account.updated_at" />
        </div>
      </div>

      <div class="profile-card">
        <div class="section-title">
          <div>
            <span>Restaurant</span>
            <h2>Vendor Details</h2>
          </div>
          <i class="bi bi-shop"></i>
        </div>

        <div class="detail-stack">
          <InfoItem label="Vendor Name" :value="vendor.name" />
          <InfoItem label="Brand Name" :value="vendor.brand_name" />
          <InfoItem label="Slug" :value="vendor.slug" />
          <InfoItem label="Primary Domain" :value="vendor.primary_domain" />
          <InfoItem label="Theme" :value="vendor.theme" />
          <InfoItem label="Contact" :value="vendor.contact" />
          <InfoItem label="Address" :value="vendor.address" />
          <InfoItem label="Vendor Status" :value="prettyStatus(vendor.status)" />
          <InfoItem label="Panel Access" :value="vendor.panel_enabled ? 'Enabled' : 'Disabled'" />
        </div>
      </div>
    </section>

    <section v-else class="profile-content">
      <div class="membership-card">
        <div class="membership-card__top">
          <div>
            <span class="profile-eyebrow">Current Plan</span>
            <h2>{{ membership.plan?.name || 'No Plan Assigned' }}</h2>
            <p>{{ membership.plan?.description || 'Your membership details will appear here once a plan is assigned.' }}</p>
          </div>
          <button type="button" class="upgrade-btn" @click="upgradePlan">
            <i class="bi bi-arrow-up-circle"></i>
            Upgrade Plan
          </button>
        </div>

        <div class="membership-pricing">
          <div>
            <span>Monthly</span>
            <strong>{{ priceLabel(membership.plan?.monthly_price) }}</strong>
          </div>
          <div>
            <span>Yearly</span>
            <strong>{{ priceLabel(membership.plan?.yearly_price) }}</strong>
          </div>
          <div>
            <span>Status</span>
            <strong>{{ prettyStatus(membership.status) }}</strong>
          </div>
        </div>
      </div>

      <div class="profile-card profile-card--wide">
        <div class="section-title">
          <div>
            <span>Plan Timeline</span>
            <h2>Membership Details</h2>
          </div>
          <i class="bi bi-calendar2-check"></i>
        </div>

        <div class="detail-grid">
          <InfoItem label="Plan Code" :value="membership.plan?.code" />
          <InfoItem label="Currency" :value="membership.plan?.currency_code" />
          <InfoItem label="Started At" :value="membership.started_at" />
          <InfoItem label="Ends At" :value="membership.ends_at" />
          <InfoItem label="Trial Ends At" :value="membership.trial_ends_at" />
          <InfoItem label="Auto Renew" :value="membership.plan?.auto_renew ? 'Enabled' : 'Disabled'" />
          <InfoItem label="Trial Days" :value="membership.plan?.trial_days" />
          <InfoItem label="Badge" :value="membership.plan?.badge" />
        </div>
      </div>

      <div class="profile-card profile-card--wide">
        <div class="section-title">
          <div>
            <span>Included</span>
            <h2>Plan Features</h2>
          </div>
          <i class="bi bi-ui-checks-grid"></i>
        </div>

        <div v-if="membership.plan?.features?.length" class="feature-grid">
          <article v-for="feature in membership.plan.features" :key="feature.name" class="feature-card"
            :class="{ 'feature-card--disabled': !feature.enabled }">
            <i :class="feature.enabled ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
            <div>
              <strong>{{ feature.name }}</strong>
              <span>{{ featureLimit(feature) }}</span>
            </div>
          </article>
        </div>
        <div v-else class="empty-state">No features are attached to this membership plan yet.</div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, h, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError } from '@/Utils/modernAlert'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  account: { type: Object, required: true },
  vendor: { type: Object, required: true },
  membership: { type: Object, required: true },
})

const activeTab = ref('profile')
const vendorName = computed(() => props.vendor.brand_name || props.vendor.name || props.account.name || 'Vendor Account')

const InfoItem = {
  props: {
    label: { type: String, required: true },
    value: { default: null },
  },
  setup(componentProps) {
    return () => h('div', { class: 'info-item' }, [
      h('span', componentProps.label),
      h('strong', componentProps.value === null || componentProps.value === undefined || componentProps.value === '' ? '-' : componentProps.value),
    ])
  },
}

function initials(value) {
  return String(value || 'VA')
    .split(' ')
    .map(part => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

function prettyStatus(value) {
  if (!value) return '-'
  return String(value).replace(/[_-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function priceLabel(value) {
  if (value === null || value === undefined || value === '') return '-'
  return value
}

function featureLimit(feature) {
  if (!feature.enabled) return 'Not included'
  if (feature.is_unlimited) return 'Unlimited'
  if (feature.limit_value) return `${feature.limit_value} ${feature.unit || ''}`.trim()
  return feature.notes || 'Included'
}

function upgradePlan() {
  alertError('Please contact us to upgrade the plan.', { duration: 3200 })
}
</script>

<style scoped>
.profile-page {
  padding: 24px;
  display: grid;
  gap: 18px;
}

.profile-hero,
.profile-card,
.membership-card {
  border: 1px solid #fde8cc;
  background: #ffffff;
  border-radius: 18px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}

.profile-hero {
  padding: 26px;
  display: flex;
  justify-content: space-between;
  gap: 24px;
  background: linear-gradient(135deg, #fff7ed 0%, #ffffff 52%, #f8fafc 100%);
}

.profile-hero__identity {
  display: flex;
  align-items: center;
  gap: 18px;
  min-width: 0;
}

.profile-avatar {
  width: 92px;
  height: 92px;
  border-radius: 24px;
  display: grid;
  place-items: center;
  overflow: hidden;
  background: linear-gradient(135deg, #f9741623, #f59e0b);
  color: #ffffff;
  font-size: 30px;
  font-weight: 900;
  box-shadow: 0 14px 28px rgba(249, 115, 22, 0.22);
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-eyebrow,
.section-title span,
.profile-hero__membership span,
.membership-pricing span {
  color: #c2410c;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.profile-hero h1 {
  margin: 7px 0 4px;
  color: #111827;
  font-size: 30px;
  font-weight: 900;
}

.profile-hero p,
.membership-card__top p {
  margin: 0;
  color: #64748b;
  line-height: 1.6;
}

.hero-badges {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.status-pill {
  display: inline-flex;
  min-height: 28px;
  align-items: center;
  border-radius: 999px;
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 900;
}

.status-pill--active {
  background: #dcfce7;
  color: #166534;
}

.status-pill--inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-pill--soft {
  background: #fff7ed;
  color: #9a3412;
}

.profile-hero__membership {
  min-width: 220px;
  padding: 18px;
  border-radius: 16px;
  background: #ffffff;
  border: 1px solid #fed7aa;
}

.profile-hero__membership strong {
  display: block;
  margin-top: 7px;
  color: #111827;
  font-size: 19px;
}

.profile-tabs {
  display: inline-flex;
  width: fit-content;
  gap: 8px;
  padding: 8px;
  border: 1px solid #fde8cc;
  border-radius: 16px;
  background: #ffffff;
}

.profile-tabs button {
  border: 0;
  min-height: 42px;
  border-radius: 12px;
  padding: 0 16px;
  background: transparent;
  color: #64748b;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.profile-tabs button.active {
  background: #fff7ed;
  color: #c2410c;
}

.profile-content {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.75fr);
  gap: 18px;
}

.profile-card,
.membership-card {
  padding: 22px;
}

.profile-card--wide,
.membership-card {
  grid-column: span 1;
}

.section-title,
.membership-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.section-title h2,
.membership-card h2 {
  margin: 5px 0 0;
  color: #111827;
  font-size: 22px;
  font-weight: 900;
}

.section-title i {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: #fff7ed;
  color: #f97316;
  font-size: 20px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.detail-stack {
  display: grid;
  gap: 12px;
}

:deep(.info-item) {
  min-height: 78px;
  padding: 15px;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  background: #fbfdff;
}

:deep(.info-item span) {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

:deep(.info-item strong) {
  display: block;
  margin-top: 7px;
  color: #0f172a;
  font-size: 14px;
  line-height: 1.45;
  overflow-wrap: anywhere;
}

.membership-card {
  grid-column: 1 / -1;
  background: linear-gradient(135deg, #111827, #3b2414 54%, #f97316);
  color: #ffffff;
  border: 0;
}

.membership-card .profile-eyebrow,
.membership-pricing span {
  color: #fed7aa;
}

.membership-card h2,
.membership-pricing strong {
  color: #ffffff;
}

.upgrade-btn {
  border: 0;
  min-height: 44px;
  border-radius: 13px;
  padding: 0 16px;
  background: #ffffff;
  color: #c2410c;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.membership-pricing {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 20px;
}

.membership-pricing div {
  padding: 16px;
  border-radius: 15px;
  background: rgba(255, 255, 255, 0.13);
  border: 1px solid rgba(255, 255, 255, 0.18);
}

.membership-pricing strong {
  display: block;
  margin-top: 7px;
  font-size: 20px;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.feature-card {
  padding: 14px;
  border: 1px solid #bbf7d0;
  border-radius: 14px;
  background: #f0fdf4;
  display: flex;
  gap: 12px;
}

.feature-card i {
  color: #16a34a;
}

.feature-card strong {
  display: block;
  color: #111827;
}

.feature-card span {
  display: block;
  color: #64748b;
  font-size: 12px;
  margin-top: 4px;
}

.feature-card--disabled {
  background: #f8fafc;
  border-color: #e5e7eb;
}

.feature-card--disabled i {
  color: #94a3b8;
}

.empty-state {
  padding: 22px;
  border-radius: 14px;
  border: 1px dashed #cbd5e1;
  color: #64748b;
  background: #f8fafc;
  font-weight: 800;
}

@media (max-width: 980px) {
  .profile-hero,
  .membership-card__top {
    flex-direction: column;
  }

  .profile-content,
  .detail-grid,
  .membership-pricing,
  .feature-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .profile-page {
    padding: 16px;
  }

  .profile-hero__identity {
    align-items: flex-start;
    flex-direction: column;
  }

  .profile-tabs {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr;
  }
}
</style>
