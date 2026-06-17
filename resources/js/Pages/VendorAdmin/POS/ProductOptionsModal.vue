<template>
  <transition name="pos-modal-fade">
    <div v-if="show" class="pos-modal-backdrop" @click.self="close">
      <div class="pos-modal">
        <div class="pos-modal__body">
          <div class="pos-modal__header">
            <div class="pos-product">
              <div class="pos-product__image-wrap">
                <img
                  v-if="product?.image_url"
                  :src="product.image_url"
                  :alt="product?.name || 'Product'"
                  class="pos-product__image"
                />
                <div v-else class="pos-product__placeholder">
                  <i class="bi bi-image"></i>
                </div>
              </div>

              <div class="pos-product__content">
                <h3 class="pos-product__title">{{ product?.name || 'Product' }}</h3>
                <div class="pos-product__price">
                  <template v-if="isSpecialPriceActive()">
                    <span class="pos-product__price--special">
                      {{ currencySymbol(currencyCode) }} {{ money(activeProductPrice()) }}
                    </span>
                    <span class="pos-product__price-original">
                      {{ currencySymbol(currencyCode) }} {{ money(normalProductPrice()) }}
                    </span>
                  </template>
                  <template v-else>
                    {{ currencySymbol(currencyCode) }} {{ money(activeProductPrice()) }}
                  </template>
                </div>
                <p class="pos-product__stock">
                  Stock: {{ trimQty(maxStock) }} {{ product?.unit_type || 'pcs' }}
                </p>
              </div>
            </div>
          </div>

          <div class="pos-modal__scroll">
            <section
              v-for="option in normalizedOptions"
              :key="option.key"
              class="option-group"
            >
              <h4 class="option-group__title">
                {{ option.name }}
                <span v-if="option.is_required" class="option-group__required">*</span>
              </h4>

              <div v-if="option.type === 'select'" class="option-list option-list--stack">
                <label
                  v-for="row in option.rows"
                  :key="row.label"
                  class="option-choice option-choice--radio"
                >
                  <input
                    type="radio"
                    :name="option.key"
                    :value="row.label"
                    v-model="form.selects[option.key]"
                  />
                  <span>{{ row.label }}{{ rowPriceLabel(row) }}</span>
                </label>
              </div>

              <div
                v-else-if="option.type === 'multiple_select' || option.type === 'checkbox'"
                class="option-list option-list--stack"
              >
                <label
                  v-for="row in option.rows"
                  :key="row.label"
                  class="option-choice option-choice--checkbox"
                >
                  <input
                    type="checkbox"
                    :value="row.label"
                    v-model="form.multi[option.key]"
                  />
                  <span>{{ row.label }}{{ rowPriceLabel(row) }}</span>
                </label>
              </div>

              <div v-else-if="option.type === 'radio_button'" class="option-list option-list--stack">
                <label
                  v-for="row in option.rows"
                  :key="row.label"
                  class="option-choice option-choice--radio"
                >
                  <input
                    type="radio"
                    :name="option.key"
                    :value="row.label"
                    v-model="form.radios[option.key]"
                  />
                  <span>{{ row.label }}{{ rowPriceLabel(row) }}</span>
                </label>
              </div>

              <div v-else-if="option.type === 'textarea'" class="option-field-wrap">
                <textarea
                  v-model="form.inputs[option.key]"
                  class="option-field option-field--textarea"
                  :placeholder="option.name"
                ></textarea>
              </div>

              <div v-else-if="option.type === 'date'" class="option-field-wrap">
                <input v-model="form.inputs[option.key]" type="date" class="option-field" />
              </div>

              <div v-else-if="option.type === 'time'" class="option-field-wrap">
                <input v-model="form.inputs[option.key]" type="time" class="option-field" />
              </div>

              <div v-else class="option-field-wrap">
                <input
                  v-model="form.inputs[option.key]"
                  type="text"
                  class="option-field"
                  :placeholder="option.name"
                />
              </div>
            </section>
          </div>
        </div>

        <div class="pos-modal__footer">
          <div class="pos-modal__qty-wrap">
            <button type="button" class="qty-btn" :disabled="!qtyEditable || form.qty <= minQty" @click="decreaseQty">
              <i class="bi bi-dash-lg"></i>
            </button>
            <input
              ref="qtyInput"
              type="number"
              v-model.number="form.qty"
              :min="minQty"
              :max="maxStock || undefined"
              :step="inputStep"
              class="qty-input"
              @blur="normalizeQty"
              @focus="selectQtyInput"
              @keydown.enter.prevent="confirmAdd"
              :readonly="!qtyEditable"
              :disabled="!qtyEditable"
            />
            <button type="button" class="qty-btn" :disabled="!qtyEditable || form.qty >= maxStock" @click="increaseQty">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>
          <div v-if="isLooseItem && qtyEditable" class="loose-qty-presets">
            <button
              v-for="preset in looseQtyPresets"
              :key="preset.label"
              type="button"
              :disabled="maxStock > 0 && preset.value > maxStock"
              @click="setPresetQty(preset.value)"
            >
              {{ preset.label }}
            </button>
          </div>
          <div v-if="qtyError" class="pos-modal__qty-error">{{ qtyError }}</div>
          <div class="pos-modal__actions">
            <button type="button" class="footer-btn footer-btn--ghost" :disabled="submitting" @click="close">
              Cancel
            </button>
            <button type="button" class="footer-btn footer-btn--primary" :disabled="submitting || !!qtyError" @click="confirmAdd">
              <i class="bi bi-cart-plus"></i>
              {{ submitting ? 'Adding...' : 'Add To Cart' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
import { useForm } from '@inertiajs/vue3'
import { currencySymbol } from '@/Utils/currency'

export default {
  name: 'ProductOptionsModal',
  emits: ['close', 'confirm'],
  props: {
    show: { type: Boolean, default: false },
    product: { type: Object, default: null },
    currencyCode: { type: String, default: 'LKR' },
    currencyMode: { type: String, default: 'base' },
    submitting: { type: Boolean, default: false },
    initialQty: { type: Number, default: 1 },
    qtyEditable: { type: Boolean, default: true },
  },
  data() {
    return {
      form: useForm({
        qty: 1,
        notes: '',
        selects: {},
        multi: {},
        radios: {},
        inputs: {},
      }),
    }
  },
  computed: {
    normalizedOptions() {
      const direct = Array.isArray(this.product?.productOptions)
        ? this.product.productOptions
        : Array.isArray(this.product?.options)
          ? this.product.options
          : []

      return direct.map((option, index) => ({
        key: `opt_${index}`,
        id: option.id ?? index,
        name: option.name || option.label || `Option ${index + 1}`,
        type: option.type || 'text',
        is_required: !!option.is_required,
        base_price: this.toNumber(option.base_price),
        secondary_price: this.toNumber(option.secondary_price),
        price_type: option.price_type || 'fixed',
        rows: Array.isArray(option.rows) ? option.rows : [],
      }))
    },
    maxStock() {
      return Math.max(0, this.toNumber(this.product?.current_stock))
    },
    isLooseItem() {
      return !!this.product?.is_loose_item
    },
    minQty() {
      return this.isLooseItem ? 0.001 : 1
    },
    inputStep() {
      return this.isLooseItem ? 0.001 : 1
    },
    buttonStep() {
      if (!this.isLooseItem) return 1

      const unit = this.product?.unit_type || ''

      if (unit === 'kg' || unit === 'l') return 0.05
      if (unit === 'g' || unit === 'ml') return 50

      return 0.001
    },
    looseQtyPresets() {
      const unit = this.product?.unit_type || 'kg'

      if (unit === 'kg') {
        return [
          { label: '100g', value: 0.1 },
          { label: '250g', value: 0.25 },
          { label: '500g', value: 0.5 },
          { label: '1kg', value: 1 },
        ]
      }

      if (unit === 'g') {
        return [
          { label: '100g', value: 100 },
          { label: '250g', value: 250 },
          { label: '500g', value: 500 },
          { label: '1kg', value: 1000 },
        ]
      }

      if (unit === 'l') {
        return [
          { label: '100ml', value: 0.1 },
          { label: '250ml', value: 0.25 },
          { label: '500ml', value: 0.5 },
          { label: '1l', value: 1 },
        ]
      }

      if (unit === 'ml') {
        return [
          { label: '100ml', value: 100 },
          { label: '250ml', value: 250 },
          { label: '500ml', value: 500 },
          { label: '1l', value: 1000 },
        ]
      }

      return [
        { label: '0.100', value: 0.1 },
        { label: '0.250', value: 0.25 },
        { label: '0.500', value: 0.5 },
        { label: '1.000', value: 1 },
      ]
    },
    qtyError() {
      const qty = this.toNumber(this.form.qty)

      if (qty <= 0) {
        return `Quantity must be at least ${this.trimQty(this.minQty)}.`
      }

      if (this.maxStock > 0 && qty > this.maxStock) {
        return `Only ${this.trimQty(this.maxStock)} ${this.product?.unit_type || 'pcs'} available.`
      }

      return ''
    },
  },
  watch: {
    show(value) {
      if (value) this.resetForm()
    },
    product() {
      if (this.show) this.resetForm()
    },
  },
  mounted() {
    window.addEventListener('keydown', this.handleModalKeydown)
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleModalKeydown)
  },
  methods: {
    handleModalKeydown(event) {
      if (!this.show || event.defaultPrevented) return

      if (event.key === 'Escape') {
        event.preventDefault()
        this.close()
        return
      }

      if (event.key !== 'Enter') return

      const target = event.target
      const tagName = String(target?.tagName || '').toLowerCase()
      const isTyping = ['input', 'textarea', 'select'].includes(tagName)

      if (isTyping) return

      event.preventDefault()
      this.confirmAdd()
    },
    resetForm() {
      this.form.qty = this.clampQty(this.initialQty)
      this.form.notes = ''
      this.form.selects = {}
      this.form.multi = {}
      this.form.radios = {}
      this.form.inputs = {}

      this.normalizedOptions.forEach((option) => {
        this.form.selects[option.key] = ''
        this.form.multi[option.key] = []
        this.form.radios[option.key] = ''
        this.form.inputs[option.key] = ''
      })

      this.$nextTick(() => {
        this.focusQtyInput()
      })
    },
    close() {
      if (this.submitting) return
      this.$emit('close')
    },
    clampQty(value) {
      const qty = Math.max(this.minQty, this.toNumber(value) || this.minQty)
      const normalized = this.isLooseItem ? Number(qty.toFixed(3)) : Math.round(qty)

      if (this.maxStock > 0) {
        return Math.min(normalized, this.maxStock)
      }

      return normalized
    },
    normalizeQty() {
      this.form.qty = this.clampQty(this.form.qty)
    },
    focusQtyInput() {
      if (!this.qtyEditable) return
      this.$refs.qtyInput?.focus()
      this.selectQtyInput()
    },
    selectQtyInput() {
      if (!this.qtyEditable) return
      this.$refs.qtyInput?.select?.()
    },
    decreaseQty() {
      this.form.qty = this.clampQty(this.toNumber(this.form.qty) - this.buttonStep)
    },
    increaseQty() {
      this.form.qty = this.clampQty(this.toNumber(this.form.qty) + this.buttonStep)
    },
    setPresetQty(value) {
      this.form.qty = this.clampQty(value)
    },
    isSpecialPriceActive() {
      if (!this.product) return false
      const hasSpecial = this.currencyMode === 'secondary'
        ? (this.product.secondary_special_price !== null && this.product.secondary_special_price !== undefined && this.product.secondary_special_price !== '') || (this.product.special_price_type === 'percentage' && this.product.base_special_price !== null && this.product.base_special_price !== undefined && this.product.base_special_price !== '')
        : this.product.base_special_price !== null && this.product.base_special_price !== undefined && this.product.base_special_price !== '';

      if (!hasSpecial) return false;

      const now = new Date();

      if (this.product.special_price_start) {
        const start = new Date(this.product.special_price_start);
        if (now < start) return false;
      }

      if (this.product.special_price_end) {
        const end = new Date(this.product.special_price_end);
        if (now > end) return false;
      }

      return true;
    },
    normalProductPrice() {
      if (this.currencyMode === 'secondary') {
        return this.toNumber(this.product?.secondary_price ?? this.product?.base_price ?? this.product?.price)
      }
      return this.toNumber(this.product?.base_price ?? this.product?.price)
    },
    activeProductPrice() {
      const normalPrice = this.normalProductPrice();

      if (this.isSpecialPriceActive()) {
        if (this.product.special_price_type === 'percentage') {
          const percentage = this.currencyMode === 'secondary'
            ? this.toNumber(this.product.secondary_special_price ?? this.product.base_special_price ?? 0)
            : this.toNumber(this.product.base_special_price ?? 0);
          return normalPrice - (normalPrice * percentage / 100);
        } else {
          return this.currencyMode === 'secondary'
            ? this.toNumber(this.product.secondary_special_price ?? this.product.base_special_price ?? normalPrice)
            : this.toNumber(this.product.base_special_price ?? normalPrice);
        }
      }
      return normalPrice;
    },
    activeRowPrice(row) {
      if (this.currencyMode === 'secondary') {
        return this.toNumber(row?.secondary_price ?? row?.base_price)
      }
      return this.toNumber(row?.base_price)
    },
    rowPriceLabel(row) {
      const price = this.activeRowPrice(row)
      return price > 0 ? ` (${this.currencySymbol(this.currencyCode)} ${this.money(price)})` : ''
    },
    currencySymbol,
    findRow(option, label) {
      return (option.rows || []).find((row) => row.label === label)
    },
    validateOptions() {
      for (const option of this.normalizedOptions) {
        if (!option.is_required) continue

        if (option.type === 'select' && !this.form.selects[option.key]) return false
        if ((option.type === 'multiple_select' || option.type === 'checkbox') && !(this.form.multi[option.key] || []).length) return false
        if (option.type === 'radio_button' && !this.form.radios[option.key]) return false
        if (!['select', 'multiple_select', 'checkbox', 'radio_button'].includes(option.type) && !this.form.inputs[option.key]) return false
      }

      return true
    },
    confirmAdd() {
      if (this.submitting) return
      if (!this.validateOptions()) return
      if (this.qtyError) return
      this.normalizeQty()

      const selectedOptions = []

      for (const option of this.normalizedOptions) {
        if (option.type === 'select') {
          const value = this.form.selects[option.key]
          if (value) {
            const row = this.findRow(option, value)
            selectedOptions.push({
              option_name: option.name,
              option_type: option.type,
              value_label: value,
              value_input: null,
              price: this.activeRowPrice(row),
              price_type: row?.price_type || 'fixed',
            })
          }
          continue
        }

        if (option.type === 'multiple_select' || option.type === 'checkbox') {
          const values = this.form.multi[option.key] || []
          values.forEach((value) => {
            const row = this.findRow(option, value)
            selectedOptions.push({
              option_name: option.name,
              option_type: option.type,
              value_label: value,
              value_input: null,
              price: this.activeRowPrice(row),
              price_type: row?.price_type || 'fixed',
            })
          })
          continue
        }

        if (option.type === 'radio_button') {
          const value = this.form.radios[option.key]
          if (value) {
            const row = this.findRow(option, value)
            selectedOptions.push({
              option_name: option.name,
              option_type: option.type,
              value_label: value,
              value_input: null,
              price: this.activeRowPrice(row),
              price_type: row?.price_type || 'fixed',
            })
          }
          continue
        }

        const inputValue = this.form.inputs[option.key]
        if (inputValue) {
          selectedOptions.push({
            option_name: option.name,
            option_type: option.type,
            value_label: null,
            value_input: inputValue,
            price: this.currencyMode === 'secondary'
              ? this.toNumber(option.secondary_price ?? option.base_price)
              : this.toNumber(option.base_price),
            price_type: option.price_type || 'fixed',
          })
        }
      }

      this.$emit('confirm', {
        product_id: this.product.id,
        qty: this.clampQty(this.form.qty),
        notes: this.form.notes || '',
        selected_options: selectedOptions,
      })
    },
    toNumber(value) {
      const parsed = parseFloat(value || 0)
      return Number.isFinite(parsed) ? parsed : 0
    },
    money(value) {
      return this.toNumber(value).toFixed(3)
    },
    trimQty(value) {
      const numeric = this.toNumber(value)
      return Number.isInteger(numeric) ? numeric : numeric.toFixed(3).replace(/\.?0+$/, '')
    },
  },
}
</script>

<style scoped>
.pos-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 4000;
  background: rgba(15, 23, 42, 0.42);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.pos-modal {
  width: 100%;
  max-width: 550px;
  max-height: calc(100vh - 48px);
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.pos-modal__body {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.pos-modal__header {
  padding: 24px 24px 10px;
}

.pos-product {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 22px;
  align-items: start;
}

.pos-product__image-wrap {
  width: 120px;
  height: 100px;
  overflow: hidden;
  border-radius: 6px;
  background: #f3f4f6;
}

.pos-product__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.pos-product__placeholder {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  color: #94a3b8;
  font-size: 24px;
}

.pos-product__title {
  margin: 2px 0 8px;
  font-size: 16px;
  line-height: 1.4;
  font-weight: 700;
  color: #5b6472;
}

.pos-product__price {
  font-size: 15px;
  color: #6b7280;
  font-weight: 500;
}

.pos-product__price--special {
  font-weight: 700;
  color: #ef4444;
}

.pos-product__price-original {
  font-size: 13px;
  text-decoration: line-through;
  color: #94a3b8;
  margin-left: 8px;
}

.pos-product__stock {
  margin: 8px 0 0;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
}

.pos-modal__scroll {
  padding: 4px 24px 20px;
  overflow: auto;
}

.option-group {
  margin-bottom: 26px;
}

.option-group:last-child {
  margin-bottom: 0;
}

.option-group__title {
  margin: 0 0 10px;
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
}

.option-group__required {
  color: #ef4444;
}

.option-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.option-choice {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #5b6472;
  cursor: pointer;
}

.option-choice input {
  width: 19px;
  height: 19px;
  margin: 0;
  accent-color: #3b82f6;
}

.option-field-wrap {
  margin-top: 8px;
}

.option-field {
  width: 100%;
  min-height: 42px;
  border: 1px solid #d8dee6;
  border-radius: 8px;
  padding: 0 12px;
  font-size: 13px;
  color: #475569;
  outline: none;
}

.option-field--textarea {
  min-height: 90px;
  resize: vertical;
  padding: 12px;
}

.option-field:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.pos-modal__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
  padding: 16px 24px 22px;
}

.pos-modal__actions {
  display: flex;
  gap: 18px;
}

.pos-modal__qty-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 4px;
}

.qty-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: #ffffff;
  border-radius: 6px;
  display: grid;
  place-items: center;
  cursor: pointer;
  color: #475569;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.qty-btn:hover {
  color: #3b82f6;
}

.qty-btn:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.qty-input {
  width: 64px;
  text-align: center;
  border: none;
  background: transparent;
  font-size: 15px;
  font-weight: 600;
  color: #334155;
  outline: none;
  -moz-appearance: textfield;
}

.pos-modal__qty-error {
  flex: 1;
  min-width: 120px;
  color: #dc2626;
  font-size: 12px;
  font-weight: 700;
}

.loose-qty-presets {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
  width: min(100%, 300px);
}

.loose-qty-presets button {
  min-height: 38px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  color: #334155;
  font-size: 13px;
  font-weight: 800;
}

.loose-qty-presets button:hover:not(:disabled) {
  border-color: #3b82f6;
  color: #3b82f6;
}

.loose-qty-presets button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.footer-btn {
  min-height: 40px;
  border: none;
  background: transparent;
  font-size: 15px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.footer-btn:disabled {
  cursor: wait;
  opacity: 0.65;
}

.footer-btn--ghost {
  color: #6b7280;
}

.footer-btn--primary {
  color: #3b82f6;
}

.pos-modal-fade-enter-active,
.pos-modal-fade-leave-active {
  transition: all 0.18s ease;
}

.pos-modal-fade-enter-from,
.pos-modal-fade-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

@media (max-width: 640px) {
  .pos-modal-backdrop {
    padding: 12px;
  }

  .pos-modal {
    max-width: 100%;
    max-height: calc(100vh - 24px);
  }

  .pos-product {
    grid-template-columns: 92px 1fr;
    gap: 14px;
  }

  .pos-product__image-wrap {
    width: 92px;
    height: 82px;
  }

  .pos-modal__header,
  .pos-modal__scroll,
  .pos-modal__footer {
    padding-left: 16px;
    padding-right: 16px;
  }

  .pos-modal__footer {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
