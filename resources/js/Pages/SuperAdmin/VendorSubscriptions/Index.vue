<template>
  <div class="subscription-page">
    <section class="subscription-shell">

      <div class="formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">Vendor Subscription Plans</h1>
            <p class="header-subtitle">
              Manage POS plan pricing, product limits, feature gates, and vendor panel access rules.
            </p>
          </div>

            <button v-if="this.can('vendor-subscriptions.create')" type="button" class="btn-primary-modern"
              @click="goCreate">
              <i class="bi bi-plus" />
              <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                Add <span class="create-text">Vendor Subscription Plan</span>
              </span>
            </button>
        </div>
      </div>

      <div class="stats-grid">
        <article class="stat-card">
          <i class="bi bi-layers"></i>
          <div>
            <span>Total Plans</span>
            <strong>{{ stats.total || 0 }}</strong>
          </div>
        </article>
        <article class="stat-card">
          <i class="bi bi-check2-circle"></i>
          <div>
            <span>Active Plans</span>
            <strong>{{ stats.active || 0 }}</strong>
          </div>
        </article>
        <article class="stat-card">
          <i class="bi bi-star"></i>
          <div>
            <span>Default Plan</span>
            <strong>{{ stats.default || '-' }}</strong>
          </div>
        </article>
        <article class="stat-card">
          <i class="bi bi-shop"></i>
          <div>
            <span>Assigned Vendors</span>
            <strong>{{ stats.assigned_vendors || 0 }}</strong>
          </div>
        </article>
      </div>

      <div v-if="plans.length" class="plans-grid">
        <article
          v-for="plan in plans"
          :key="plan.id"
          class="plan-card"
          :class="{ 'plan-card--highlight': plan.highlight_plan || plan.most_popular }"
          :style="{ '--plan-color': plan.plan_card_color || '#f57c00' }"
        >
          <div class="plan-accent"></div>

          <div class="plan-top">
            <div class="plan-title-row">
              <div class="plan-icon">
                <i :class="['bi', plan.icon_key || 'bi-gem']"></i>
              </div>
              <div>
                <h2>{{ plan.plan_name }}</h2>
                <p>{{ plan.plan_code }}</p>
              </div>
            </div>
            <div class="plan-menu">
              <button v-if="this.can('vendor-subscriptions.edit')" type="button" title="Edit" @click="goEdit(plan.id)">
                <i class="bi bi-pencil-square"></i>
              </button>
            </div>
          </div>

          <div class="badges">
            <span class="status-pill" :class="`status-pill--${plan.status}`">{{ labelStatus(plan.status) }}</span>
            <span v-if="plan.is_default" class="soft-pill">Default</span>
            <span v-if="plan.most_popular" class="soft-pill soft-pill--hot">Most Popular</span>
          </div>

          <div class="pricing-strip">
            <div class="price-box">
              <div>
                <span>Monthly</span>
                <small>/ month</small>
              </div>
              <strong>{{ money(plan.monthly_price, plan.currency_code) }}</strong>
            </div>

            <div v-if="plan.yearly_price !== null && plan.yearly_price !== undefined" class="price-box price-box--yearly">
              <div>
                <span>Yearly</span>
                <small>/ year</small>
              </div>
              <strong>{{ money(plan.yearly_price, plan.currency_code) }}</strong>
            </div>
          </div>

          <p class="description">{{ plan.short_description || 'No description added for this POS subscription plan.' }}</p>

          <div class="plan-summary">
            <div>
              <span>Features</span>
              <strong>{{ enabledFeatures(plan).length }}</strong>
            </div>
            <div>
              <span>Vendors</span>
              <strong>{{ plan.vendors_count || 0 }}</strong>
            </div>
            <div>
              <span>Renewal</span>
              <strong>{{ plan.auto_renew ? 'Auto' : 'Manual' }}</strong>
            </div>
          </div>

          <div class="feature-section">
            <div class="feature-section-title">
              <h3>Included POS Features</h3>
              <span>{{ excludedFeatureCount(plan) }} excluded</span>
            </div>
            <div class="feature-list">
              <div v-for="feature in featuredRows(plan)" :key="feature.feature_key" class="feature-row">
                <span>
                  <i class="bi bi-check-circle-fill"></i>
                  {{ feature.feature_name }}
                </span>
                <strong v-if="feature.value_type === 'limit'">{{ featureValue(feature) }}</strong>
              </div>
              <div v-if="!featuredRows(plan).length" class="feature-row feature-row--empty">
                No included POS features
              </div>
            </div>
          </div>

          <div class="card-actions">
            <button v-if="this.can('vendor-subscriptions.edit')" type="button" class="ghost-action" @click="goEdit(plan.id)">
              <i class="bi bi-pencil"></i>
              Edit Plan
            </button>
            <button v-if="!plan.is_default" type="button" class="ghost-action" @click="setDefault(plan.id)">
              <i class="bi bi-star"></i>
              Set Default
            </button>
            <button
              type="button"
              class="status-switch"
              :class="{ 'status-switch--on': plan.status === 'active' }"
              :aria-label="plan.status === 'active' ? 'Deactivate plan' : 'Activate plan'"
              @click="toggleStatus(plan.id)"
            >
              <span class="status-switch__track">
                <span class="status-switch__thumb"></span>
              </span>
              <strong>{{ plan.status === 'active' ? 'Active' : 'Inactive' }}</strong>
            </button>
            <button v-if="!plan.vendors_count" type="button" class="danger-action" @click="confirmDelete(plan)">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </article>
      </div>

      <div v-else class="empty-state">
        <i class="bi bi-credit-card-2-front"></i>
        <h2>No vendor subscription plans yet</h2>
        <p>Create your first POS plan with product limits and restaurant feature gates.</p>
        <button type="button" class="primary-action" @click="goCreate">
          <i class="bi bi-plus-lg"></i>
          <span>Create Plan</span>
        </button>
      </div>
    </section>

    <DeleteModal
      v-model:show="deleteModal.show"
      :target-id="deleteModal.plan?.id"
      :target-name="deleteModal.plan?.plan_name"
      :loading="deleteModal.loading"
      title="Delete this vendor subscription plan?"
      cancel-label="Keep"
      confirm-label="Delete"
      @confirm="deletePlan"
    />
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'

export default {
  name: 'VendorSubscriptionIndex',
  layout: SuperAdminLayout,
  components: {
    DeleteModal,
  },
  props: {
    plans: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      deleteModal: {
        show: false,
        loading: false,
        plan: null,
      },
      importantFeatureKeys: [
        'products_count',
        'vendor_panel',
        'loyalty_points',
        'gift_cards',
        'promotions',
        'reports',
        'seating_plan',
        'kitchen',
      ],
    }
  },
  watch: {
    '$page.props.flash': {
      immediate: true,
      handler(flash) {
        if (flash?.message) alertSuccess(flash.message)
        if (flash?.error) alertError(flash.error)
      },
    },
  },
  methods: {
    goCreate() {
      router.visit(route('vendor-subscriptions.create'))
    },
    goEdit(id) {
      router.visit(route('vendor-subscriptions.edit', id))
    },
    toggleStatus(id) {
      router.patch(route('vendor-subscriptions.toggle-status', id), {}, { preserveScroll: true })
    },
    setDefault(id) {
      router.patch(route('vendor-subscriptions.set-default', id), {}, { preserveScroll: true })
    },
    confirmDelete(plan) {
      this.deleteModal.plan = plan
      this.deleteModal.show = true
    },
    can(permission) {
      return this.$page.props.auth.permissions.includes(permission)
    },
    deletePlan() {
      if (!this.deleteModal.plan?.id) return

      this.deleteModal.loading = true
      router.delete(route('vendor-subscriptions.destroy', this.deleteModal.plan.id), {
        preserveScroll: true,
        onSuccess: () => {
          this.deleteModal.show = false
          this.deleteModal.plan = null
        },
        onFinish: () => {
          this.deleteModal.loading = false
        },
      })
    },
    enabledFeatures(plan) {
      return (plan.features || []).filter((feature) => feature.enabled)
    },
    featuredRows(plan) {
      const features = plan.features || []

      return this.importantFeatureKeys
        .map((key) => features.find((feature) => feature.feature_key === key))
        .filter((feature) => feature?.enabled)
    },
    excludedFeatureCount(plan) {
      return (plan.features || []).filter((feature) => !feature.enabled).length
    },
    featureValue(feature) {
      if (!feature.enabled) return 'Inactive'

      if (feature.value_type === 'limit') {
        if (feature.is_unlimited) return 'Unlimited'

        const value = Number(feature.limit_value || 0)
        return `${value.toLocaleString()} ${feature.unit || ''}`.trim()
      }

      return 'Active'
    },
    labelStatus(status) {
      return String(status || 'draft')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
    },
    money(value, currency = 'LKR') {
      return `${currency || 'LKR'} ${Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
      })}`
    },
  },
}
</script>

<style scoped>
.subscription-page {
  padding: 24px;
  background: #f6f7fb;
}

.subscription-shell {
  background: transparent;
  border: 0;
  border-radius: 0;
  padding: 8px 4px 28px;
  box-shadow: none;
}

.subscription-header {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: flex-start;
  border: 1px solid rgba(245, 124, 0, 0.14);
  border-radius: 20px;
  padding: 24px 26px;
  background:
    linear-gradient(110deg, rgba(255, 149, 0, 0.13), rgba(255, 255, 255, 0.94) 48%, rgba(239, 246, 255, 0.88)),
    #ffffff;
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05);
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  color: #c2410c;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 999px;
  padding: 0 12px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
  margin-bottom: 7px;
}

.subscription-header h1 {
  margin: 0;
  font-size: 1.7rem;
  font-weight: 800;
  color: #0f172a;
}

.subscription-header p {
  margin: 6px 0 0;
  color: #475569;
}

.primary-action,
.solid-action,
.ghost-action,
.danger-action {
  border: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 38px;
  border-radius: 999px;
  font-weight: 800;
  padding: 0 18px;
  transition: 0.18s ease;
}

.primary-action,
.solid-action {
  color: #fff;
  background: linear-gradient(135deg, #f97316, #fb8500);
  box-shadow: 0 14px 24px rgba(245, 124, 0, 0.24);
}

.ghost-action {
  color: #334155;
  background: #fff;
  border: 1px solid #cbd5e1;
}

.danger-action {
  width: 38px;
  padding: 0;
  color: #dc2626;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.status-switch {
  border: 1px solid #cbd5e1;
  border-radius: 999px;
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px 0 8px;
  color: #64748b;
  background: #fff;
  font-weight: 850;
}

.status-switch__track {
  width: 42px;
  height: 24px;
  border-radius: 999px;
  background: #cbd5e1;
  padding: 3px;
  display: inline-flex;
  align-items: center;
  transition: 0.16s ease;
}

.status-switch__thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #ffffff;
  box-shadow: 0 2px 5px rgba(15, 23, 42, 0.18);
  transition: 0.16s ease;
}

.status-switch--on {
  color: #047857;
  border-color: #bbf7d0;
  background: #f0fdf4;
}

.status-switch--on .status-switch__track {
  background: #16a34a;
}

.status-switch--on .status-switch__thumb {
  transform: translateX(18px);
}

.status-switch strong {
  font-size: 0.84rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin: 18px 0;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 14px;
  background: #ffffff;
  padding: 14px;
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.035);
}

.stat-card i {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  color: #f57c00;
  background: #fff7ed;
  border: 1px solid #fed7aa;
}

.stat-card span {
  display: block;
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 700;
}

.stat-card strong {
  display: block;
  margin-top: 6px;
  color: #0f172a;
  font-size: 1.35rem;
}

.plans-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.plan-card {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 16px;
  background: #fff;
  padding: 16px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.045);
}


.plan-accent {
  position: absolute;
  inset: 0 0 auto;
  height: 5px;
  background: var(--plan-color);
}

.plan-top {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}

.plan-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.plan-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: color-mix(in srgb, var(--plan-color) 14%, white);
  border: 1px solid color-mix(in srgb, var(--plan-color) 22%, white);
  color: var(--plan-color);
  font-size: 1.12rem;
}

.plan-top h2 {
  margin: 0;
  font-size: 0.98rem;
  font-weight: 850;
}

.plan-top p,
.description {
  margin: 4px 0 0;
  color: #475569;
  font-size: 0.8rem;
}

.plan-menu button {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  color: #334155;
  background: #fff;
}

.badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 12px 0;
}

.status-pill,
.soft-pill {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  border-radius: 999px;
  padding: 0 10px;
  font-size: 0.74rem;
  font-weight: 800;
}

.status-pill--active {
  color: #047857;
  background: #dcfce7;
}

.status-pill--inactive,
.status-pill--archived {
  color: #64748b;
  background: #f1f5f9;
}

.status-pill--draft {
  color: #92400e;
  background: #fef3c7;
}

.soft-pill {
  color: #c2410c;
  background: #ffedd5;
}

.soft-pill--hot {
  color: #9f1239;
  background: #ffe4e6;
}

.pricing-strip {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.price-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff, #fffaf5);
  padding: 9px 10px;
}

.price-box span,
.price-box small {
  display: block;
  color: #334155;
  font-weight: 800;
}

.price-box small {
  color: #64748b;
  font-size: 0.76rem;
}

.price-box strong {
  color: #0f172a;
  font-size: 0.9rem;
}

.description {
  min-height: 34px;
  margin-top: 10px;
  line-height: 1.4;
}

.plan-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0;
  margin-top: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  background: #ffffff;
}

.plan-summary > div {
  padding: 9px 10px;
  border-right: 1px solid #e5e7eb;
}

.plan-summary > div:last-child {
  border-right: 0;
}

.plan-summary span {
  display: block;
  color: #64748b;
  font-size: 0.74rem;
  font-weight: 750;
}

.plan-summary strong {
  display: block;
  margin-top: 5px;
  color: #0f172a;
  font-size: 0.9rem;
}

.feature-section {
  margin-top: 12px;
}

.feature-section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.feature-section h3 {
  margin: 0 0 10px;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 850;
}

.feature-section-title h3 {
  margin: 0;
}

.feature-section-title span {
  color: #9a3412;
  background: #ffedd5;
  border-radius: 999px;
  padding: 5px 9px;
  font-size: 0.74rem;
  font-weight: 850;
}

.feature-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.feature-row {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 999px;
  background: #ffffff;
  padding: 6px 9px;
  color: #334155;
  font-size: 0.76rem;
}

.feature-row span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 750;
}

.feature-row .bi-check-circle-fill {
  color: #16a34a;
}

.feature-row .bi-x-circle {
  color: #94a3b8;
}

.feature-row strong {
  color: #64748b;
  font-size: 0.78rem;
}

.feature-row--empty {
  justify-content: center;
  color: #94a3b8;
  font-weight: 800;
}

.card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  border-top: 1px solid #f1f5f9;
  margin: 14px -16px 0;
  padding: 12px 16px 0;
}

.empty-state {
  min-height: 320px;
  display: grid;
  place-items: center;
  text-align: center;
  border: 1px dashed #cbd5e1;
  border-radius: 16px;
  padding: 32px;
}

.empty-state > i {
  color: #f57c00;
  font-size: 2.6rem;
}

.empty-state h2 {
  margin: 10px 0 4px;
  color: #0f172a;
  font-size: 1.25rem;
}

.empty-state p {
  color: #64748b;
}

@media (max-width: 1280px) {
  .plans-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 820px) {
  .subscription-page {
    padding: 14px;
  }

  .subscription-shell {
    padding: 18px;
  }

  .subscription-header {
    flex-direction: column;
  }

  .stats-grid,
  .plans-grid,
  .pricing-strip,
  .plan-summary {
    grid-template-columns: 1fr;
  }

  .plan-summary > div {
    border-right: 0;
    border-bottom: 1px solid #e5e7eb;
  }

  .plan-summary > div:last-child {
    border-bottom: 0;
  }
}
</style>
