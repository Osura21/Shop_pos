<template>
  <div class="plan-form-page">
    <div class="form-shell">

      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Vendor Subscription Plan' : 'Create Vendor Subscription Plan' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Update Vendor Subscription Plan' : 'Create Vendor Subscription Plan' }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor-subscriptions.index')" class="btn btn-ghost">
            Cancel
            </Link>

            <button type="button" class="btn btn-primary-modern" @click="submit" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Plan' : 'Create Plan') }}
            </button>
          </div>
        </div>
      </div>

      <form class="form-stack" @submit.prevent="submit">
        <section class="form-card">

          <div class="card-header">
            <h2 class="card-title cardTitle">Basic Information</h2>
            <p>Name, code, status, and vendor-facing summary.</p>
          </div>

           <div class="card-body formCardBody">
 
          <div class="field-grid">
            <label class="field">
              <span>Subscription Name</span>
              <input v-model="form.plan_name" type="text" placeholder="Growth POS" />
              <small v-if="form.errors.plan_name">{{ form.errors.plan_name }}</small>
            </label>

            <label class="field code-field">
              <span>Subscription Plan Code</span>
              <div class="code-row">
                <input v-model="form.plan_code" type="text" placeholder="VSP-GROWTH" />
                  <button type="button" class="btn-outline-orange" @click="generateCode">
                    Generate
                  </button>
 
              </div>
              <small v-if="form.errors.plan_code">{{ form.errors.plan_code }}</small>
            </label>

            <label class="field">
              <span>Status</span>
              <SelectInput
                id="subscription-status"
                v-model="form.status"
                :options="statusOptions"
                valueKey="id"
                labelKey="name"
                label=""
                :clearable="false"
              />
            </label>

            <label class="toggle-card toggle-card--compact">
              <input v-model="form.is_default" type="checkbox" />
              <span></span>
              <strong>Default Plan</strong>
            </label>
          </div>

          <label class="field">
            <span>Short Description</span>
            <textarea v-model="form.short_description" maxlength="500" rows="3" placeholder="Best for growing restaurants using POS, kitchen flow, and customer engagement tools."></textarea>
            <em>{{ (form.short_description || '').length }}/500</em>
          </label>
          </div>
        </section>

        <section class="form-card">
           <div class="card-header">
            <h2 class="card-title cardTitle">Pricing & Billing</h2>
            <p>Monthly and yearly billing amounts for the vendor plan.</p>
          </div>

          <div class="card-body formCardBody">



          <div class="field-grid field-grid--four">
            <label class="field">
              <span>Currency</span>
              <input v-model="form.currency_code" type="text" maxlength="10" placeholder="LKR" />
            </label>

            <label class="field">
              <span>Monthly Price</span>
              <input v-model="form.monthly_price" type="number" min="0" step="0.01" placeholder="5000.00" />
              <small v-if="form.errors.monthly_price">{{ form.errors.monthly_price }}</small>
            </label>

            <label class="field">
              <span>Yearly Price</span>
              <input v-model="form.yearly_price" type="number" min="0" step="0.01" placeholder="51000.00" />
            </label>

            <label class="field">
              <span>Trial Days</span>
              <input v-model="form.trial_days" type="number" min="0" max="365" placeholder="14" />
            </label>
          </div>

          <div class="field-grid field-grid--four mt">
            <label class="field">
              <span>Yearly Discount Type</span>
              <SelectInput
                id="yearly-discount-type"
                v-model="form.yearly_discount_type"
                :options="yearlyDiscountTypeOptions"
                valueKey="id"
                labelKey="name"
                label=""
                :clearable="false"
              />
            </label>

            <label class="field">
              <span>Yearly Discount Value</span>
              <input v-model="form.yearly_discount_value" type="number" min="0" step="0.01" placeholder="15" />
            </label>

            <label class="toggle-card toggle-card--inline">
              <input v-model="form.auto_renew" type="checkbox" />
              <span></span>
              <strong>Auto Renew</strong>
            </label>
          </div>
          </div>
        </section>

        <section class="form-card">
          <div class="section-title card-header section-title--row">
            <div>
              <h2 class="cardTitle">Included POS Features</h2>
              <p>Select the operational modules this plan unlocks for vendors.</p>
            </div>
            <div class="feature-toolbar">
              <span>{{ includedVisibleFeatureCount }} of {{ visibleFeatureCount }} included</span>
              <button type="button" class="outline-btn" @click="enableCorePlan">
                <i class="bi bi-lightning-charge"></i>
                Core POS
              </button>
              <button type="button" class="outline-btn" @click="setAllFeatures(true)">
                <i class="bi bi-check2-all"></i>
                Include All
              </button>
              <button type="button" class="outline-btn" @click="clearOptionalFeatures">
                <i class="bi bi-x-lg"></i>
                Clear Optional
              </button>
            </div>
          </div>

          <div class="card-body formCardBody">



          <div class="feature-editor">
            <article v-for="group in featureGroups" :key="group.name" class="feature-group-panel">
              <div class="feature-group-heading">
                <h3>{{ group.name }}</h3>
                <span>{{ group.features.filter((feature) => feature.enabled).length }}/{{ group.features.length }}</span>
              </div>

              <div class="feature-list">
                <label
                  v-for="feature in group.features"
                  :key="feature.feature_key"
                  class="feature-option"
                  :class="{ 'feature-option--active': feature.enabled }"
                >
                  <input v-model="feature.enabled" type="checkbox" />
                  <span class="feature-check">
                    <i :class="feature.enabled ? 'bi bi-check-lg' : 'bi bi-plus-lg'"></i>
                  </span>
                  <span class="feature-copy">
                    <strong>{{ feature.feature_name }}</strong>
                    <small>{{ catalogMap[feature.feature_key]?.description }}</small>
                  </span>
                  <span class="feature-state">{{ feature.enabled ? 'Included' : 'Not included' }}</span>
                </label>
              </div>
            </article>
          </div>

          <div v-if="productLimitFeature" class="limit-panel">
            <div>
              <span class="feature-group">Catalog Limit</span>
              <div class="limit-panel-title">
                <h3>Products Count</h3>
                <label class="toggle-card toggle-card--small">
                  <input v-model="productLimitFeature.enabled" type="checkbox" />
                  <span></span>
                  <strong>{{ productLimitFeature.enabled ? 'Included' : 'Not Included' }}</strong>
                </label>
              </div>
              <p>Set how many menu or product records this plan allows.</p>
            </div>
            <div class="limit-row">
              <label class="field">
                <span>Product Limit</span>
                <input
                  v-model="productLimitFeature.limit_value"
                  :disabled="productLimitFeature.is_unlimited"
                  type="number"
                  min="0"
                  step="1"
                />
              </label>
              <label class="toggle-card toggle-card--small">
                <input v-model="productLimitFeature.is_unlimited" type="checkbox" />
                <span></span>
                <strong>Unlimited</strong>
              </label>
            </div>
          </div>
          </div>
        </section>

        <section class="form-card">

          <div class="card-header">
            <h2 class="card-title cardTitle">Policies & Display</h2>
            <p>Rules, placement, and card appearance for the superadmin plan catalog.</p>
          </div>

          <div class="card-body formCardBody">



          <div class="field-grid">
            <label class="field">
              <span>Cancellation Policy</span>
              <textarea v-model="form.cancellation_policy" rows="4" placeholder="Enter cancellation policy"></textarea>
            </label>
            <label class="field">
              <span>Refund Policy</span>
              <textarea v-model="form.refund_policy" rows="4" placeholder="Enter refund policy"></textarea>
            </label>
          </div>

          <div class="field-grid field-grid--four mt">
            <label class="field">
              <span>Display Order</span>
              <input v-model="form.display_order" type="number" min="0" placeholder="1" />
            </label>

            <label class="field">
              <span>Badge</span>
              <input v-model="form.badge" type="text" placeholder="Best Value" />
            </label>

            <label class="field">
              <span>Plan Color</span>
              <input v-model="form.plan_card_color" type="color" />
            </label>

            <label class="field">
              <span>Plan Icon</span>
              <SelectInput
                id="plan-icon"
                v-model="form.icon_key"
                :options="iconOptions"
                valueKey="id"
                labelKey="name"
                label=""
                :clearable="false"
              />
            </label>
          </div>

          <div class="display-toggles">
            <label class="toggle-card toggle-card--inline">
              <input v-model="form.highlight_plan" type="checkbox" />
              <span></span>
              <strong>Highlight Plan</strong>
            </label>
            <label class="toggle-card toggle-card--inline">
              <input v-model="form.most_popular" type="checkbox" />
              <span></span>
              <strong>Most Popular</strong>
            </label>
          </div>
          </div>
        </section>
      </form>
    </div>
  </div>
</template>

<script>
import { Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

export default {
  name: 'VendorSubscriptionCreateUpdate',
  layout: SuperAdminLayout,
  components: {
    Link,
    SelectInput,
  },
  props: {
    plan: { type: Object, default: null },
    featureCatalog: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    iconOptions: { type: Array, default: () => [] },
  },
  data() {
    return {
      requiredFeatureKeys: ['products_count', 'vendor_panel'],
      yearlyDiscountTypeOptions: [
        { id: '', name: 'None' },
        { id: 'percentage', name: 'Percentage' },
        { id: 'fixed', name: 'Fixed Amount' },
      ],
      form: useForm({
        plan_name: this.plan?.plan_name ?? '',
        plan_code: this.plan?.plan_code ?? `VSP-${this.randomCode()}`,
        short_description: this.plan?.short_description ?? '',
        status: this.plan?.status ?? 'draft',
        is_default: this.plan?.is_default ?? false,
        monthly_price: this.plan?.monthly_price ?? 0,
        yearly_price: this.plan?.yearly_price ?? '',
        yearly_discount_type: this.plan?.yearly_discount_type ?? '',
        yearly_discount_value: this.plan?.yearly_discount_value ?? '',
        currency_code: this.plan?.currency_code ?? 'LKR',
        trial_days: this.plan?.trial_days ?? 0,
        auto_renew: this.plan?.auto_renew ?? true,
        cancellation_policy: this.plan?.cancellation_policy ?? '',
        refund_policy: this.plan?.refund_policy ?? '',
        display_order: this.plan?.display_order ?? 0,
        badge: this.plan?.badge ?? '',
        highlight_plan: this.plan?.highlight_plan ?? false,
        most_popular: this.plan?.most_popular ?? false,
        plan_card_color: this.plan?.plan_card_color ?? '#f57c00',
        icon_key: this.plan?.icon_key ?? 'bi-gem',
        features: this.initialFeatures(),
      }),
    }
  },
  computed: {
    isEdit() {
      return Boolean(this.plan?.id)
    },
    catalogMap() {
      return Object.fromEntries(this.featureCatalog.map((feature) => [feature.feature_key, feature]))
    },
    productLimitFeature() {
      return this.form.features.find((feature) => feature.feature_key === 'products_count')
    },
    visibleFeatures() {
      return this.form.features.filter((feature) => feature.feature_key !== 'vendor_panel')
    },
    visibleFeatureCount() {
      return this.visibleFeatures.length
    },
    includedVisibleFeatureCount() {
      return this.visibleFeatures.filter((feature) => feature.enabled).length
    },
    featureGroups() {
      const groups = new Map()

      this.form.features
        .filter((feature) => feature.value_type !== 'limit' && feature.feature_key !== 'vendor_panel')
        .forEach((feature) => {
          const groupName = feature.feature_group || 'General'

          if (!groups.has(groupName)) {
            groups.set(groupName, [])
          }

          groups.get(groupName).push(feature)
        })

      return Array.from(groups, ([name, features]) => ({ name, features }))
    },
  },
  methods: {
    initialFeatures() {
      const existing = Object.fromEntries((this.plan?.features || []).map((feature) => [feature.feature_key, feature]))

      return this.featureCatalog.map((feature) => ({
        feature_key: feature.feature_key,
        feature_name: existing[feature.feature_key]?.feature_name ?? feature.feature_name,
        feature_group: existing[feature.feature_key]?.feature_group ?? feature.feature_group,
        value_type: existing[feature.feature_key]?.value_type ?? feature.value_type,
        enabled: existing[feature.feature_key]?.enabled ?? feature.feature_key === 'vendor_panel',
        is_unlimited: existing[feature.feature_key]?.is_unlimited ?? false,
        limit_value: existing[feature.feature_key]?.limit_value ?? (feature.feature_key === 'products_count' ? 100 : null),
        unit: existing[feature.feature_key]?.unit ?? feature.unit,
        notes: existing[feature.feature_key]?.notes ?? '',
        sort_order: existing[feature.feature_key]?.sort_order ?? feature.sort_order,
      }))
    },
    randomCode() {
      const part = Math.random().toString(36).slice(2, 8).toUpperCase()
      return `PLAN-${part}`
    },
    generateCode() {
      const base = String(this.form.plan_name || 'POS')
        .replace(/[^a-z0-9]+/gi, '-')
        .replace(/^-|-$/g, '')
        .toUpperCase()

      this.form.plan_code = `VSP-${base || this.randomCode()}-${Math.random().toString(36).slice(2, 5).toUpperCase()}`
    },
    enableCorePlan() {
      const core = ['products_count', 'vendor_panel', 'reports', 'seating_plan', 'kitchen']

      this.form.features.forEach((feature) => {
        if (core.includes(feature.feature_key)) {
          feature.enabled = true
        }

        if (feature.feature_key === 'products_count' && !feature.limit_value) {
          feature.limit_value = 500
        }
      })
    },
    setAllFeatures(enabled) {
      this.form.features.forEach((feature) => {
        feature.enabled = feature.feature_key === 'vendor_panel' ? true : enabled
      })
    },
    clearOptionalFeatures() {
      this.form.features.forEach((feature) => {
        feature.enabled = this.requiredFeatureKeys.includes(feature.feature_key)
      })
    },
    normalizedPayload(data) {
      return {
        ...data,
        plan_code: String(data.plan_code || '').toUpperCase(),
        is_default: data.is_default ? 1 : 0,
        auto_renew: data.auto_renew ? 1 : 0,
        highlight_plan: data.highlight_plan ? 1 : 0,
        most_popular: data.most_popular ? 1 : 0,
        yearly_price: data.yearly_price === '' ? null : data.yearly_price,
        yearly_discount_type: data.yearly_discount_type || null,
        yearly_discount_value: data.yearly_discount_value === '' ? null : data.yearly_discount_value,
        features: data.features.map((feature) => ({
          ...feature,
          enabled: feature.feature_key === 'vendor_panel' || feature.enabled ? 1 : 0,
          is_unlimited: feature.is_unlimited ? 1 : 0,
          limit_value: feature.value_type === 'limit' && !feature.is_unlimited ? feature.limit_value : null,
        })),
      }
    },
    submit() {
      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.normalizedPayload(data),
            _method: 'put',
          }))
          .post(route('vendor-subscriptions.update', this.plan.id), {
            preserveScroll: true,
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
        .post(route('vendor-subscriptions.store'), {
          preserveScroll: true,
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
}
</script>

<style scoped>
.plan-form-page {
  padding: 24px;
  background: #f6f7fb;
}

.form-shell {
  background: transparent;
  border: 0;
  border-radius: 0;
  padding: 8px 4px 28px;
  box-shadow: none;
}

.form-hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
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
  text-transform: uppercase;
  margin-bottom: 7px;
}

.form-hero h1 {
  margin: 0;
  color: #0f172a;
  font-size: 1.7rem;
  font-weight: 850;
}

.form-hero p{
  margin: 6px 0 0;
  color: #475569;
}
.card-header p {
  font-size: 14px;
  font-weight: 300;
  margin: 2px 0 -10px 0;
  color: #747980;
}

.btn-outline-orange {
  border: 1px solid #f4a261 !important;
  color: #f4a261;
  background: transparent;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  transition: 0.2s ease;
}

.btn-outline-orange:hover {
  background: #f97316;
  color: white;
}

.hero-actions,
.display-toggles {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.primary-btn,
.ghost-btn,
.outline-btn {
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 999px;
  padding: 0 18px;
  font-weight: 800;
  text-decoration: none;
}

.primary-btn {
  border: 0;
  color: #fff;
  background: linear-gradient(135deg, #f97316, #fb8500);
  box-shadow: 0 14px 24px rgba(245, 124, 0, 0.24);
}

.ghost-btn,
.outline-btn {
  color: #334155;
  background: #fff;
  border: 1px solid #cbd5e1;
}

.form-stack {
  display: grid;
  grid-template-columns: 1fr;
  align-items: start;
  gap: 14px;
  margin-top: 18px;
}

/* .form-card {
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 18px;
  padding: 20px;
  background: #fff;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.045);
} */

.section-title {
  margin-bottom: 14px;
}

.section-title--row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.section-title h2 {
  margin: 0;
  color: #334155;
  font-size: 1rem;
  font-weight: 850;
}

.section-title p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 0.88rem;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.field-grid--four {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.mt {
  margin-top: 14px;
  margin-bottom: 14px;
}

.field {
  display: grid;
  gap: 7px;
  align-content: start;
}

.field span {
  color: #0f172a;
  font-size: 0.86rem;
  font-weight: 750;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  background: #fff;
  color: #0f172a;
  min-height: 44px;
  padding: 10px 12px;
  outline: none;
  transition: 0.16s ease;
}

.field :deep(.form-label) {
  display: none;
}

.field :deep(.select-display) {
  min-height: 44px;
  height: 44px;
  border-color: #cbd5e1;
  border-radius: 12px;
  color: #0f172a;
}

.field :deep(.floating-input-wrapper) {
  margin-top: 0;
}

.field textarea {
  resize: vertical;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  border-color: #f57c00;
  box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.12);
}

.field small {
  color: #dc2626;
  font-weight: 700;
}

.field em {
  color: #64748b;
  font-size: 0.78rem;
  font-style: normal;
}

.code-row {
  display: grid;
  grid-template-columns: 1fr auto;
}

.code-row input {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.code-row button {
  border: 1px solid #334155;
  border-left: 0;
  border-radius: 0 12px 12px 0;
  background: #fff;
  color: #334155;
  font-weight: 800;
  padding: 0 14px;
}

.toggle-card {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  align-self: end;
  min-height: 44px;
  border: 1px solid #e5e7eb;
  border-radius: 999px;
  padding: 0 14px 0 10px;
  cursor: pointer;
}

.toggle-card--compact {
  width: max-content;
  min-width: 0;
  justify-self: start;
  align-self: end;
  padding-right: 12px;
}

.toggle-card input,
.feature-option input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.toggle-card > span {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #cbd5e1;
  box-shadow: inset 0 0 0 6px #cbd5e1;
}

.toggle-card input:checked + span {
  background: #f57c00;
  box-shadow: inset 0 0 0 6px #f57c00;
}

.toggle-card strong {
  color: #0f172a;
  font-size: 0.88rem;
}

.toggle-card--compact > span {
  width: 22px;
  height: 22px;
}

.toggle-card--inline {
  justify-self: start;
}

.toggle-card--small {
  align-self: end;
}

.feature-toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.feature-toolbar > span {
  color: #334155;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 999px;
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  padding: 0 14px;
  font-size: 0.84rem;
  font-weight: 850;
}

.feature-editor {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  align-items: start;
  gap: 12px;
}

.feature-group-panel,
.limit-panel {
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fbfcfe;
  padding: 14px;
}

.feature-group-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.feature-group-heading h3,
.limit-panel h3 {
  margin: 0;
  color: #0f172a;
  font-size: 0.98rem;
  font-weight: 850;
}

.feature-group-heading span {
  color: #c2410c;
  background: #ffedd5;
  border-radius: 999px;
  padding: 5px 10px;
  font-size: 0.76rem;
  font-weight: 850;
}

.feature-group {
  color: #f57c00;
  font-size: 0.72rem;
  font-weight: 850;
  text-transform: uppercase;
}

.feature-list {
  display: grid;
  gap: 8px;
}

.feature-option {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: center;
  gap: 10px;
  min-height: 58px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  padding: 10px;
  cursor: pointer;
  transition: 0.16s ease;
}

.feature-option:hover {
  border-color: #fdba74;
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
}

.feature-option--active {
  border-color: #f57c00;
  background: #fff7ed;
  box-shadow: inset 4px 0 0 #f57c00;
}

.feature-check {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
}

.feature-option--active .feature-check {
  color: #fff;
  background: #f57c00;
  border-color: #f57c00;
}

.feature-copy {
  display: grid;
  gap: 2px;
  min-width: 0;
}

.feature-copy strong {
  color: #0f172a;
  font-size: 0.92rem;
  font-weight: 850;
}

.feature-copy small,
.limit-panel p {
  margin: 0;
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.45;
}

.feature-state {
  display: none;
  color: #64748b;
  background: #f1f5f9;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.76rem;
  font-weight: 850;
  white-space: nowrap;
}

.feature-option--active .feature-state {
  color: #166534;
  background: #dcfce7;
}

.limit-panel {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(320px, 0.55fr);
  gap: 16px;
  align-items: end;
  margin-top: 16px;
}

.limit-panel-title {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: space-between;
  margin: 4px 0;
}

.limit-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
}

@media (max-width: 1200px) {
  .feature-editor,
  .field-grid--four {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .limit-panel {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 780px) {
  .plan-form-page {
    padding: 14px;
  }

  .form-shell {
    padding: 18px;
  }

  .form-hero,
  .section-title--row,
  .feature-toolbar,
  .limit-panel-title {
    flex-direction: column;
    align-items: stretch;
  }

  .field-grid,
  .field-grid--four,
  .feature-editor,
  .feature-option,
  .limit-row {
    grid-template-columns: 1fr;
  }

  .feature-state {
    justify-self: start;
  }
}
</style>
