<template>

  <Head :title="isEdit ? 'Update Purchase' : 'Create Purchase'" />

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
              {{ isEdit ? 'Update Purchase' : 'Create Purchase' }}
            </h1>
            <p class="header-subtitle">
              Base currency is <strong>{{ baseCurrencyCode }}</strong>
              <span v-if="hasSecondaryCurrency"> and secondary currency is <strong>{{ secondaryCurrencyCode
              }}</strong></span>.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.purchases.index')" class="btn btn-ghost">
            Cancel
            </Link>
            <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Purchase' : 'Create Purchase') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <!-- Purchase Information -->
          <div class="form-column full-width">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Purchase Information</h2>
              </div>

              <div class="card-body  formCardBody">
                <div class="row g-3 align-items-end">

                  <!-- Branch -->
                  <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label formLabel">Branch</label>
                    <SelectInput id="branch_id" v-model="form.branch_id" :options="branches"
                      valueKey="id" labelKey="name" placeholder="Select Branch" />
                    <div v-if="form.errors.branch_id" class="error-text">{{ form.errors.branch_id }}</div>
                  </div>

                  <!-- Supplier -->
                  <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label formLabel">Supplier</label>
                    <SelectInput id="supplier_id" v-model="form.supplier_id" :options="filteredSuppliers"
                     valueKey="id" labelKey="name" placeholder="Select Supplier" />
                    <div v-if="form.errors.supplier_id" class="error-text">{{ form.errors.supplier_id }}</div>
                  </div>

                  <!-- Expected Date -->
                  <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label formLabel">Expected Date</label>
                    <DatePicker v-model="form.expected_at" :min-date="minDate" placeholder="Select Date Time" timeEnabled />
                    <div v-if="form.errors.expected_at" class="error-text">{{ form.errors.expected_at }}</div>
                  </div>

                  <!-- TAX + DISCOUNT (UNCHANGED) -->
                  <div class="col-12">
                    <div class="tax-discount-card">
                      <h6 class="block-title mb-3">Tax</h6>
                      <div class="row g-3">
                        <div class="col-12 col-lg-4">
                          <label class="form-label formLabel">Tax Type</label>
                          <SelectInput id="tax_type" v-model="form.tax_type" :options="Object.entries(calculationTypes).map(([value, label]) => ({
                            value,
                            label
                          }))"  valueKey="value" labelKey="label"
                            placeholder="Select Tax Type" />
                        </div>

                        <div class="col-12"
                          :class="hasSecondaryCurrency && form.tax_type === 'fixed' ? 'col-lg-4' : 'col-lg-8'">
                          <label class="form-label formLabel">
                            {{ form.tax_type === 'percentage' ? 'Tax Percentage (%)' : `Tax Amount
                            (${baseCurrencyCode})` }}
                          </label>
                          <input v-model="form.tax_value" class="form-control formControl" type="number" min="0" step="0.001" />
                        </div>

                        <div v-if="hasSecondaryCurrency && form.tax_type === 'fixed'" class="col-12 col-lg-4">
                          <label class="form-label formLabel">Tax Amount ({{ secondaryCurrencyCode }})</label>
                          <input v-model="form.secondary_tax_value" class="form-control formControl" type="number" min="0"
                            step="0.001" />
                        </div>

                        <div class="col-12">
                          <div class="preview-line">
                            <span>Tax Preview</span>
                            <div class="preview-values">
                              <strong>{{ moneyBase(taxAmount) }}</strong>
                              <strong v-if="hasSecondaryCurrency">{{ moneySecondary(secondaryTaxAmount) }}</strong>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="divider"></div>

                      <h6 class="block-title mb-3">Discount</h6>
                      <div class="row g-3">
                        <div class="col-12 col-lg-4">
                          <label class="form-label formLabel">Discount Type</label>
                          <SelectInput id="discount_type" v-model="form.discount_type" :options="Object.entries(calculationTypes).map(([value, label]) => ({
                            value,
                            label
                          }))"  valueKey="value" labelKey="label"
                            placeholder="Select iscount Type" />
                        </div>

                        <div class="col-12"
                          :class="hasSecondaryCurrency && form.discount_type === 'fixed' ? 'col-lg-4' : 'col-lg-8'">
                          <label class="form-label formLabel">
                            {{ form.discount_type === 'percentage' ? 'Discount Percentage (%)' : `Discount Amount
                            (${baseCurrencyCode})` }}
                          </label>
                          <input v-model="form.discount_value" class="form-control formControl" type="number" min="0"
                            step="0.001" />
                        </div>

                        <div v-if="hasSecondaryCurrency && form.discount_type === 'fixed'" class="col-12 col-lg-4">
                          <label class="form-label  formLabel">Discount Amount ({{ secondaryCurrencyCode }})</label>
                          <input v-model="form.secondary_discount_value" class="form-control formControl" type="number" min="0"
                            step="0.001" />
                        </div>

                        <div class="col-12">
                          <div class="preview-line">
                            <span>Discount Preview</span>
                            <div class="preview-values">
                              <strong>{{ moneyBase(discountAmount) }}</strong>
                              <strong v-if="hasSecondaryCurrency">{{ moneySecondary(secondaryDiscountAmount) }}</strong>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Notes -->
                  <div class="col-12">
                    <label class="form-label  formLabel">Notes</label>
                    <textarea v-model="form.notes" rows="4" class="form-control formControl"></textarea>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Purchase Items -->
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title cardTitle">Purchase Items</h2>
                <button class="btn btn-primary-modern btn-sm" type="button" @click="addItem">
                  <i class="bi bi-plus-lg me-1"></i>Add Item
                </button>
              </div>
              <div class="card-body  formCardBody">
                <p class="small text-muted mb-3">Enter quantities and costs in the configured tenant currencies.</p>

                <div class="table-responsive">
                  <table class="table purchase-table align-middle">
                    <thead>
                      <tr>
                        <th >Ingredient</th>
                        <th >Quantity</th>
                        <th >Unit Cost ({{ baseCurrencyCode }})</th>
                        <th v-if="hasSecondaryCurrency">Unit Cost ({{ secondaryCurrencyCode
                          }})</th>
                        <th >Line Total ({{ baseCurrencyCode }})</th>
                        <th v-if="hasSecondaryCurrency" >Line Total ({{ secondaryCurrencyCode
                          }})</th>
                        <th width="70" class="text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in form.items" :key="index">
                        <td>
                          <SelectInput id="ingredient_id" v-model="item.ingredient_id" :options="filteredIngredients.map(i => ({
                            ...i,
                            label: `${i.name}${i.unit_symbol ? ` (${i.unit_symbol})` : ''}`
                          }))" valueKey="id" labelKey="label" placeholder="Select Ingredient" />
                        </td>
                        <td>
                          <input v-model="item.quantity" class="form-control formControl" type="number" min="0" step="0.001" />
                        </td>
                        <td>
                          <input v-model="item.unit_cost" class="form-control formControl" type="number" min="0" step="0.001" />
                        </td>
                        <td v-if="hasSecondaryCurrency">
                          <input v-model="item.secondary_unit_cost" class="form-control formControl" type="number" min="0"
                            step="0.001" />
                        </td>
                        <td class="fw-bold text-nowrap">{{ moneyBase(lineTotal(item)) }}</td>
                        <td v-if="hasSecondaryCurrency" class="fw-bold text-nowrap">{{
                          moneySecondary(secondaryLineTotal(item)) }}</td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-remove" type="button" @click="removeItem(index)">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-if="form.errors.items" class="error-text mt-2">{{ form.errors.items }}</div>

                <!-- Summary -->
                <div class="summary-grid mt-4">
                  <div class="summary-card">
                    <div class="summary-title">Base Summary ({{ baseCurrencyCode }})</div>
                    <div class="summary-row"><span>Sub Total</span><strong>{{ moneyBase(subtotal) }}</strong></div>
                    <div class="summary-row">
                      <span>Discount <small v-if="form.discount_type === 'percentage'">({{
                        numberValue(form.discount_value).toFixed(3) }}%)</small></span>
                      <strong>{{ moneyBase(discountAmount) }}</strong>
                    </div>
                    <div class="summary-row">
                      <span>Tax <small v-if="form.tax_type === 'percentage'">({{ numberValue(form.tax_value).toFixed(3)
                      }}%)</small></span>
                      <strong>{{ moneyBase(taxAmount) }}</strong>
                    </div>
                    <div class="summary-row summary-row--total">
                      <span>Total</span>
                      <strong>{{ moneyBase(grandTotal) }}</strong>
                    </div>
                  </div>

                  <div v-if="hasSecondaryCurrency" class="summary-card summary-card--secondary">
                    <div class="summary-title">Secondary Summary ({{ secondaryCurrencyCode }})</div>
                    <div class="summary-row"><span>Sub Total</span><strong>{{ moneySecondary(secondarySubtotal)
                    }}</strong></div>
                    <div class="summary-row">
                      <span>Discount <small v-if="form.discount_type === 'percentage'">({{
                        numberValue(form.discount_value).toFixed(3) }}%)</small></span>
                      <strong>{{ moneySecondary(secondaryDiscountAmount) }}</strong>
                    </div>
                    <div class="summary-row">
                      <span>Tax <small v-if="form.tax_type === 'percentage'">({{ numberValue(form.tax_value).toFixed(3)
                      }}%)</small></span>
                      <strong>{{ moneySecondary(secondaryTaxAmount) }}</strong>
                    </div>
                    <div class="summary-row summary-row--total">
                      <span>Total</span>
                      <strong>{{ moneySecondary(secondaryGrandTotal) }}</strong>
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
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import DatePicker from '@/Components/DatePicker.vue'

export default {
  name: 'PurchaseCreateUpdate',
  layout: VendorAdminLayout,
  components: { Head, Link, SelectInput, DatePicker },
  props: {
    purchase: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] },
    calculationTypes: { type: Object, default: () => ({}) },
  },

  data() {
    return {
      form: useForm({
        branch_id: this.purchase?.branch_id ?? '',
        supplier_id: this.purchase?.supplier_id ?? '',
        expected_at: this.purchase?.expected_at ?? '',
        tax_type: this.purchase?.tax_type ?? 'fixed',
        tax_value: this.purchase?.tax_value ?? '0',
        secondary_tax_value: this.purchase?.secondary_tax_value ?? '',
        discount_type: this.purchase?.discount_type ?? 'fixed',
        discount_value: this.purchase?.discount_value ?? '0',
        secondary_discount_value: this.purchase?.secondary_discount_value ?? '',
        notes: this.purchase?.notes ?? '',
        items: this.purchase?.items?.length
          ? this.purchase.items.map((item) => ({
            ingredient_id: item.ingredient_id,
            quantity: item.quantity,
            unit_cost: item.unit_cost,
            secondary_unit_cost: item.secondary_unit_cost ?? '',
          }))
          : [this.newItem()],
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.purchase?.id
    },

    minDate() {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      return today;
    },

    baseCurrency() {
      return this.$page.props.currencySettings?.base_currency ?? null
    },

    secondaryCurrency() {
      return this.$page.props.currencySettings?.secondary_currency ?? null
    },

    hasSecondaryCurrency() {
      return !!this.secondaryCurrency
    },

    baseCurrencyCode() {
      return this.baseCurrency?.code || 'Base Currency'
    },

    secondaryCurrencyCode() {
      return this.secondaryCurrency?.code || 'Secondary Currency'
    },

    filteredIngredients() {
      if (!this.form.branch_id) return this.ingredients

      return this.ingredients.filter(
        (item) => this.matchesBranch(item.branch_ids, this.form.branch_id)
      )
    },

    filteredSuppliers() {
      if (!this.form.branch_id) return this.suppliers

      return this.suppliers.filter(
        (item) => !item.branch_id || Number(item.branch_id) === Number(this.form.branch_id)
      )
    },

    subtotal() {
      return this.form.items.reduce((sum, item) => sum + this.lineTotal(item), 0)
    },

    secondarySubtotal() {
      return this.form.items.reduce((sum, item) => sum + this.secondaryLineTotal(item), 0)
    },

    taxAmount() {
      const value = this.numberValue(this.form.tax_value)

      if (this.form.tax_type === 'percentage') {
        return (this.subtotal * value) / 100
      }

      return value
    },

    secondaryTaxAmount() {
      if (!this.hasSecondaryCurrency) return 0

      if (this.form.tax_type === 'percentage') {
        return (this.secondarySubtotal * this.numberValue(this.form.tax_value)) / 100
      }

      return this.numberValue(this.form.secondary_tax_value)
    },

    discountAmount() {
      const value = this.numberValue(this.form.discount_value)

      if (this.form.discount_type === 'percentage') {
        return (this.subtotal * value) / 100
      }

      return value
    },

    secondaryDiscountAmount() {
      if (!this.hasSecondaryCurrency) return 0

      if (this.form.discount_type === 'percentage') {
        return (this.secondarySubtotal * this.numberValue(this.form.discount_value)) / 100
      }

      return this.numberValue(this.form.secondary_discount_value)
    },

    grandTotal() {
      return Math.max(0, this.subtotal + this.taxAmount - this.discountAmount)
    },

    secondaryGrandTotal() {
      if (!this.hasSecondaryCurrency) return 0
      return Math.max(0, this.secondarySubtotal + this.secondaryTaxAmount - this.secondaryDiscountAmount)
    },
  },

  methods: {
    newItem() {
      return {
        ingredient_id: '',
        quantity: '',
        unit_cost: '',
        secondary_unit_cost: '',
      }
    },

    numberValue(value) {
      const num = parseFloat(value)
      return Number.isFinite(num) ? num : 0
    },

    lineTotal(item) {
      return this.numberValue(item.quantity) * this.numberValue(item.unit_cost)
    },

    secondaryLineTotal(item) {
      return this.numberValue(item.quantity) * this.numberValue(item.secondary_unit_cost)
    },

    moneyBase(value) {
      return `${this.baseCurrencyCode} ${this.numberValue(value).toFixed(3)}`
    },

    moneySecondary(value) {
      return `${this.secondaryCurrencyCode} ${this.numberValue(value).toFixed(3)}`
    },

    matchesBranch(branchIds, branchId) {
      if (!Array.isArray(branchIds) || branchIds.length === 0) return true
      return branchIds.some((id) => Number(id) === Number(branchId))
    },

    onIngredientChange(item) {
      const ingredient = this.ingredients.find((row) => Number(row.id) === Number(item.ingredient_id))
      if (!ingredient) return

      if (item.unit_cost === '' || item.unit_cost === null) {
        item.unit_cost = ingredient.cost_per_unit ?? ''
      }

      if (this.hasSecondaryCurrency && (item.secondary_unit_cost === '' || item.secondary_unit_cost === null)) {
        item.secondary_unit_cost = ingredient.secondary_cost_per_unit ?? ''
      }
    },

    addItem() {
      this.form.items.push(this.newItem())
    },

    removeItem(index) {
      if (this.form.items.length === 1) return
      this.form.items.splice(index, 1)
    },

    submit() {
      const cleanItems = this.form.items
        .filter((item) => item.ingredient_id && this.numberValue(item.quantity) > 0)
        .map((item) => ({
          ingredient_id: item.ingredient_id,
          quantity: this.numberValue(item.quantity),

          // base
          unit_cost: this.numberValue(item.unit_cost),

          // secondary
          secondary_unit_cost: this.hasSecondaryCurrency
            ? this.numberValue(item.secondary_unit_cost)
            : null,
        }))

      const payload = {
        branch_id: this.form.branch_id || null,
        supplier_id: this.form.supplier_id || null,
        expected_at: this.form.expected_at || null,
        notes: this.form.notes || null,

        tax_type: this.form.tax_type || 'fixed',
        tax_value: this.numberValue(this.form.tax_value),
        tax_amount: this.taxAmount,
        secondary_tax_value: this.hasSecondaryCurrency && this.form.tax_type === 'fixed'
          ? this.numberValue(this.form.secondary_tax_value)
          : null,
        secondary_tax_amount: this.hasSecondaryCurrency ? this.secondaryTaxAmount : null,

        discount_type: this.form.discount_type || 'fixed',
        discount_value: this.numberValue(this.form.discount_value),
        discount_amount: this.discountAmount,
        secondary_discount_value: this.hasSecondaryCurrency && this.form.discount_type === 'fixed'
          ? this.numberValue(this.form.secondary_discount_value)
          : null,
        secondary_discount_amount: this.hasSecondaryCurrency ? this.secondaryDiscountAmount : null,

        // base
        subtotal: this.subtotal,
        total: this.grandTotal,

        // secondary
        secondary_subtotal: this.hasSecondaryCurrency ? this.secondarySubtotal : null,
        secondary_total: this.hasSecondaryCurrency ? this.secondaryGrandTotal : null,

        items: cleanItems,
      }

      const options = {
        preserveScroll: true,
        onSuccess: () => router.visit(route('vendor.purchases.index')),
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong.'

          alertError(message)
        }
      }

      if (this.isEdit) {
        this.form
          .transform(() => ({ ...payload, _method: 'PUT' }))
          .post(route('vendor.purchases.update', this.purchase.id), options)

        return
      }

      this.form
        .transform(() => payload)
        .post(route('vendor.purchases.store'), options)
    },
  },
}
</script>
<style scoped>

.form-grid {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.form-column.full-width {
  width: 100%;
}

/* Fix alignment consistency */
.formCardBody .row {
  margin-left: -10px;
  margin-right: -10px;
}

.formCardBody .row>div {
  padding-left: 10px;
  padding-right: 10px;
}


.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}


.formControl {
  width: 100%;
  height: 44px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
}

.table-responsive {
  overflow: visible;
}

.formControl:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
}

/* Tax & Discount Card */
.tax-discount-card {
  background: #fbfcfe;
  border: 1px solid #eef2f7;
  border-radius: 16px;
  padding: 20px;
}

.block-title {
  font-weight: 700;
  color: #334155;
  margin-bottom: 12px;
}

.preview-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 14px;
}

.preview-values {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.divider {
  height: 1px;
  background: #e2e8f0;
  margin: 20px 0;
}

/* Table */
.purchase-table th,
.purchase-table td {
  vertical-align: middle;
  padding: 12px 10px;
}

.btn-remove {
  color: #ef4444;
  border: 1px solid #fee2e2;
  background: #fff;
  border-radius: 8px;
  padding: 6px 10px;
}

/* Summary */
.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.summary-card {
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 18px;
  background: #fff;
}

.summary-card--secondary {
  background: #f8fbff;
}

.summary-title {
  font-weight: 700;
  color: #334155;
  margin-bottom: 12px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  color: #475569;
}

.summary-row--total {
  border-top: 1px solid #e2e8f0;
  margin-top: 8px;
  padding-top: 14px;
  font-size: 18px;
  font-weight: 700;
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
@media (max-width: 1199px) {.form-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
}

@media (max-width: 762px) {
  .table-responsive {
    overflow: auto;
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

  .card-header{
    padding: 20px 16px;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
