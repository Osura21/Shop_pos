<template>
  <teleport to="body">
    <transition name="customer-offcanvas-fade">
      <div
        v-if="open"
        class="customer-offcanvas-backdrop"
        @click="emitClose"
      ></div>
    </transition>

    <transition name="customer-offcanvas-slide">
      <aside
        v-if="open"
        class="customer-offcanvas"
        role="dialog"
        aria-modal="true"
        aria-label="Customer details"
      >
        <div class="customer-shell">
          <!-- LEFT PANEL -->
          <section class="customer-side">
            <div class="customer-side__top">
              <button
                type="button"
                class="customer-offcanvas__close"
                @click="emitClose"
              >
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="customer-profile">
              <div class="customer-profile__avatar">
                {{ avatarLetter }}
              </div>

              <div class="customer-profile__name">
                {{ customer?.name || 'Customer' }}
              </div>

              <div class="customer-profile__email">
                {{ customer?.email || 'No email' }}
              </div>

              <div class="customer-profile__status" :class="statusClass">
                {{ customer?.status || 'Active' }}
              </div>
            </div>

            <div class="customer-meta">
              <div class="customer-meta__item">
                <i class="bi bi-telephone"></i>
                <span>{{ customer?.phone || 'No phone' }}</span>
              </div>

              <div class="customer-meta__item">
                <i class="bi bi-geo-alt"></i>
                <span>{{ customer?.location || 'No location' }}</span>
              </div>

              <div class="customer-meta__item">
                <i class="bi bi-clock-history"></i>
                <span>Created {{ customer?.created_at || '-' }}</span>
              </div>
            </div>

            <div class="customer-side__divider"></div>

            <nav class="customer-tabs">
              <button
                type="button"
                class="customer-tabs__item"
                :class="{ 'customer-tabs__item--active': activeTab === 'appointments' }"
                @click="activeTab = 'appointments'"
              >
               Customer Ads
              </button>

              <button
                type="button"
                class="customer-tabs__item"
                :class="{ 'customer-tabs__item--active': activeTab === 'details' }"
                @click="activeTab = 'details'"
              >
                Client details
              </button>

              <button
                type="button"
                class="customer-tabs__item"
                :class="{ 'customer-tabs__item--active': activeTab === 'loyalty' }"
                @click="activeTab = 'loyalty'"
              >
                Subscribed plan
              </button>
            </nav>
          </section>

          <!-- RIGHT PANEL -->
          <section class="customer-content">
            <div class="customer-content__header">
              <h4 class="customer-content__title">
                {{ sectionTitle }}
              </h4>
            </div>

            <div class="customer-content__body">
              <div v-if="loading" class="customer-state">
                <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                Loading customer details...
              </div>

              <transition name="customer-section" mode="out-in">
                <div v-if="!loading" :key="activeTab">
                  <!-- APPOINTMENTS / ADS -->
                  <div v-if="activeTab === 'appointments'">
                    <div v-if="customer?.vehicles?.length" class="ads-grid">
                      <div
                        v-for="ad in customer.vehicles"
                        :key="ad.id"
                        class="ad-card"
                      >
                        <div class="ad-card__top">
                          <div>
                            <div class="ad-card__title">
                              {{ ad.title || 'Untitled vehicle' }}
                            </div>
                            <div class="ad-card__meta">
                              {{ ad.year || '-' }} • {{ ad.created_at || '-' }}
                            </div>
                          </div>

                          <span class="ad-card__status">
                            {{ ad.status || 'N/A' }}
                          </span>
                        </div>

                        <div class="ad-card__price">
                          {{ formatPrice(ad.price) }}
                        </div>
                      </div>
                    </div>

                    <div v-else class="empty-panel">
                      <div class="empty-panel__title">No customer ads found.</div>
                      <div class="empty-panel__text">
                        This customer has not posted any ads yet.
                      </div>
                    </div>
                  </div>

                  <!-- CLIENT DETAILS -->
                  <div v-else-if="activeTab === 'details'" class="details-grid">
                    <div class="detail-card">
                      <div class="detail-card__label">Full name</div>
                      <div class="detail-card__value">{{ customer?.name || '-' }}</div>
                    </div>

                    <div class="detail-card">
                      <div class="detail-card__label">Email</div>
                      <div class="detail-card__value">{{ customer?.email || '-' }}</div>
                    </div>

                    <div class="detail-card">
                      <div class="detail-card__label">Phone</div>
                      <div class="detail-card__value">{{ customer?.phone || '-' }}</div>
                    </div>

                    <div class="detail-card">
                      <div class="detail-card__label">Status</div>
                      <div class="detail-card__value">{{ customer?.status || '-' }}</div>
                    </div>

                    <div class="detail-card">
                      <div class="detail-card__label">Date of birth</div>
                      <div class="detail-card__value">{{ customer?.dob || '-' }}</div>
                    </div>

                    <div class="detail-card">
                      <div class="detail-card__label">Gender</div>
                      <div class="detail-card__value">{{ customer?.gender || '-' }}</div>
                    </div>

                    <div class="detail-card">
                      <div class="detail-card__label">Location</div>
                      <div class="detail-card__value">{{ customer?.location || '-' }}</div>
                    </div>

                    <div class="detail-card">
                      <div class="detail-card__label">Job</div>
                      <div class="detail-card__value">{{ customer?.c_job || '-' }}</div>
                    </div>

                    <div class="detail-card detail-card--wide">
                      <div class="detail-card__label">Created</div>
                      <div class="detail-card__value">{{ customer?.created_at || '-' }}</div>
                    </div>
                  </div>

                  <!-- LOYALTY -->
                  <!-- SUBSCRIBED PLAN -->
<div v-else class="loyalty-wrap">
  <div class="details-grid">
    <div class="detail-card">
      <div class="detail-card__label">Default plan</div>
      <div class="detail-card__value">
        {{ customer?.default_subscription_plan?.subscription_name || '-' }}
      </div>
      <div class="detail-card__subtext" v-if="customer?.default_subscription_plan">
        {{ customer.default_subscription_plan.subscription_plan_code }} •
        {{ formatPlanPrice(customer.default_subscription_plan.price) }} /
        {{ customer.default_subscription_plan.billing_interval }}
      </div>
    </div>

    <div class="detail-card">
      <div class="detail-card__label">Customer changed plan</div>
      <div class="detail-card__value">
        {{ customer?.subscribed_subscription_plan?.subscription_name || 'Using default plan' }}
      </div>
      <div class="detail-card__subtext" v-if="customer?.subscribed_subscription_plan">
        {{ customer.subscribed_subscription_plan.subscription_plan_code }} •
        {{ formatPlanPrice(customer.subscribed_subscription_plan.price) }} /
        {{ customer.subscribed_subscription_plan.billing_interval }}
      </div>
    </div>

    <div class="detail-card detail-card--wide">
      <div class="detail-card__label">Currently active for this customer</div>
      <div class="detail-card__value">
        {{ customer?.effective_subscription_plan?.subscription_name || '-' }}
      </div>
      <div class="detail-card__subtext" v-if="customer?.effective_subscription_plan">
        {{ customer.effective_subscription_plan.subscription_plan_code }} •
        {{ formatPlanPrice(customer.effective_subscription_plan.price) }} /
        {{ customer.effective_subscription_plan.billing_interval }}
      </div>
    </div>
  </div>

  <div class="detail-card plan-editor-card mt-3">
    <div class="detail-card__label">Change customer subscription plan</div>
    <div class="detail-card__subtext mb-3">
      Leave empty to use the default subscription plan.
    </div>

    <select v-model="selectedPlanId" class="form-select plan-select">
      <option value="">Use default plan</option>
      <option
        v-for="plan in customer?.available_subscription_plans || []"
        :key="plan.id"
        :value="String(plan.id)"
      >
        {{ plan.subscription_name }} - {{ formatPlanPrice(plan.price) }} / {{ plan.billing_interval }}
        {{ Number(plan.is_default) === 1 ? '(Default)' : '' }}
      </option>
    </select>

    <div class="plan-editor-actions">
      <button
        type="button"
        class="btn btn-light"
        @click="useDefaultPlan"
        :disabled="savingPlan"
      >
        Use default
      </button>

      <button
        type="button"
        class="btn btn-primary"
        @click="saveSubscriptionPlan"
        :disabled="savingPlan"
      >
        <span v-if="!savingPlan">Save plan</span>
        <span v-else class="d-inline-flex align-items-center gap-2">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          Saving...
        </span>
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
import { router } from '@inertiajs/vue3'

export default {
  name: 'ViewOffcanvas',

  props: {
    open: {
      type: Boolean,
      default: false,
    },
    customer: {
      type: Object,
      default: () => null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['close', 'subscription-updated'],

  data() {
    return {
      activeTab: 'appointments',
      savingPlan: false,
      selectedPlanId: '',
    }
  },

  computed: {
    avatarLetter() {
      return String(this.customer?.name || 'C').trim().charAt(0).toUpperCase()
    },

    sectionTitle() {
      if (this.activeTab === 'appointments') return 'Customer Ads'
      if (this.activeTab === 'details') return 'Client details'
      return 'Subscribed plan'
    },

    statusClass() {
      const status = String(this.customer?.status || 'active').toLowerCase()
      return status === 'active'
        ? 'customer-profile__status--active'
        : 'customer-profile__status--inactive'
    },
  },

  watch: {
    open: {
      immediate: true,
      handler(value) {
        document.body.style.overflow = value ? 'hidden' : ''

        if (value) {
          this.activeTab = 'appointments'
        }
      },
    },

    customer: {
      immediate: true,
      deep: true,
      handler(value) {
        this.selectedPlanId = value?.subscribed_plan_id
          ? String(value.subscribed_plan_id)
          : ''
      },
    },
  },

  methods: {
    emitClose() {
      this.$emit('close')
    },

    formatPrice(value) {
      const number = Number(value ?? 0)
      return Number.isFinite(number)
        ? `Rs ${number.toLocaleString()}`
        : 'Rs 0'
    },

    formatPlanPrice(value) {
      const number = Number(value ?? 0)
      return Number.isFinite(number)
        ? `Rs ${number.toLocaleString()}`
        : 'Rs 0'
    },

    async saveSubscriptionPlan() {
      if (!this.customer?.id || this.savingPlan) return

      this.savingPlan = true

      try {
        const response = await fetch(route('customers.updateSubscription', this.customer.id), {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN':
              document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify({
            subscribed_plan: this.selectedPlanId || null,
          }),
        })

        const data = await response.json()

        if (response.ok && data?.customer) {
          this.$emit('subscription-updated', data.customer)
        } else {
          console.error('Failed to update subscription plan', data)
        }
      } catch (error) {
        console.error('Failed to update subscription plan', error)
      } finally {
        this.savingPlan = false
      }
    },

    useDefaultPlan() {
      this.selectedPlanId = ''
      this.saveSubscriptionPlan()
    },
  },

  beforeUnmount() {
    document.body.style.overflow = ''
  },
}
</script>

<style scoped>
.customer-offcanvas-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.35);
  z-index: 1040;
}

.customer-offcanvas {
  position: fixed;
  top: 0;
  right: 0;
  width: min(88vw, 1320px);
  max-width: 100vw;
  height: 100vh;
  background: #ffffff;
  z-index: 1050;
  box-shadow: -12px 0 40px rgba(15, 23, 42, 0.16);
  border-left: 1px solid rgba(15, 23, 42, 0.08);
  overflow: hidden;
}

.customer-shell {
  display: grid;
  grid-template-columns: 320px 1fr;
  height: 100%;
}

.customer-side {
  background: #ffffff;
  border-right: 1px solid rgba(15, 23, 42, 0.08);
  padding: 18px 22px 24px;
  overflow-y: auto;
}

.customer-side__top {
  display: flex;
  justify-content: flex-start;
  margin-bottom: 12px;
}

.customer-offcanvas__close {
  width: 42px;
  height: 42px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #334155;
  transition: all 0.18s ease;
}

.customer-offcanvas__close:hover {
  background: rgba(51, 46, 120, 0.05);
  color: #332E78;
}

.customer-profile {
  text-align: center;
  padding: 8px 0 10px;
}

.customer-profile__avatar {
  width: 86px;
  height: 86px;
  border-radius: 999px;
  background: rgba(51, 46, 120, 0.10);
  color: #332E78;
  font-size: 38px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.customer-profile__name {
  font-size: 30px;
  line-height: 1.2;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
  word-break: break-word;
}

.customer-profile__email {
  color: #64748b;
  font-size: 15px;
  margin-bottom: 12px;
  word-break: break-word;
}

.customer-profile__status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 92px;
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

.customer-profile__status--active {
  background: rgba(34, 197, 94, 0.12);
  color: #15803d;
}

.customer-profile__status--inactive {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.customer-meta {
  display: grid;
  gap: 12px;
  margin-top: 20px;
}

.customer-meta__item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: #475569;
  font-size: 14px;
  line-height: 1.5;
}

.customer-meta__item i {
  color: #332E78;
  font-size: 16px;
  margin-top: 2px;
}

.customer-side__divider {
  height: 1px;
  background: rgba(15, 23, 42, 0.08);
  margin: 22px 0;
}

.customer-tabs {
  display: grid;
  gap: 8px;
}

.customer-tabs__item {
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 15px;
  font-weight: 700;
  color: #475569;
  transition: all 0.2s ease;
}

.customer-tabs__item:hover {
  background: rgba(51, 46, 120, 0.05);
  color: #332E78;
}

.customer-tabs__item--active {
  background: rgba(51, 46, 120, 0.10);
  color: #332E78;
}

.customer-content {
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.customer-content__header {
  padding: 24px 28px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  background: #ffffff;
}

.customer-content__title {
  margin: 0;
  font-size: 30px;
  font-weight: 800;
  color: #0f172a;
}

.customer-content__body {
  flex: 1;
  overflow-y: auto;
  padding: 26px 28px 30px;
}

.customer-state,
.empty-panel {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 18px;
  padding: 28px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
}

.customer-state {
  display: inline-flex;
  align-items: center;
  color: #475569;
  font-weight: 600;
}

.empty-panel__title {
  font-size: 20px;
  font-weight: 800;
  color: #332E78;
  margin-bottom: 10px;
}

.empty-panel__text {
  color: #64748b;
  line-height: 1.6;
}

.ads-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.ad-card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 18px;
  padding: 18px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.ad-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
}

.ad-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.ad-card__title {
  font-size: 17px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
}

.ad-card__meta {
  color: #64748b;
  font-size: 13px;
}

.ad-card__status {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
  border-radius: 999px;
  padding: 6px 10px;
  background: rgba(51, 46, 120, 0.08);
  color: #332E78;
  font-size: 12px;
  font-weight: 700;
}

.ad-card__price {
  margin-top: 18px;
  font-size: 20px;
  font-weight: 800;
  color: #332E78;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.detail-card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 18px;
  padding: 18px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
}

.detail-card--wide {
  grid-column: 1 / -1;
}

.detail-card__label {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 8px;
}

.detail-card__value {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  word-break: break-word;
}

.loyalty-wrap {
  display: block;
}

.customer-offcanvas-fade-enter-active,
.customer-offcanvas-fade-leave-active {
  transition: opacity 0.22s ease;
}

.customer-offcanvas-fade-enter-from,
.customer-offcanvas-fade-leave-to {
  opacity: 0;
}

.customer-offcanvas-slide-enter-active,
.customer-offcanvas-slide-leave-active {
  transition: transform 0.28s ease;
}

.customer-offcanvas-slide-enter-from,
.customer-offcanvas-slide-leave-to {
  transform: translateX(100%);
}

.customer-section-enter-active,
.customer-section-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.customer-section-enter-from,
.customer-section-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

@media (max-width: 1399.98px) {
  .customer-offcanvas {
    width: min(92vw, 1280px);
  }
}

@media (max-width: 991.98px) {
  .customer-offcanvas {
    width: 100vw;
  }

  .customer-shell {
    grid-template-columns: 280px 1fr;
  }

  .ads-grid,
  .details-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .customer-shell {
    grid-template-columns: 1fr;
    grid-template-rows: auto 1fr;
  }

  .customer-side {
    border-right: 0;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    padding: 16px;
  }

  .customer-content__header {
    padding: 18px 18px;
  }

  .customer-content__title {
    font-size: 24px;
  }

  .customer-content__body {
    padding: 18px;
  }

  .customer-tabs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
  }

  .customer-tabs__item {
    width: auto;
    white-space: nowrap;
    flex: 0 0 auto;
  }

  .customer-profile__name {
    font-size: 24px;
  }
}

.detail-card__subtext {
  margin-top: 8px;
  color: #64748b;
  font-size: 14px;
  line-height: 1.5;
}

.plan-editor-card {
  display: block;
}

.plan-select {
  min-height: 46px;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.12);
}

.plan-editor-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  flex-wrap: wrap;
}
</style>