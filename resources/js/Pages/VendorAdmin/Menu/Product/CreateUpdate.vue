<template>

  <Head :title="isEdit ? 'Edit Product' : 'Create Product'" />

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
              <i class="bi bi-box-seam me-2 text-warning"></i>
              {{ isEdit ? 'Edit Product' : 'Create Product' }}
            </h1>
            <p class="header-subtitle">
              Base currency: <strong>{{ baseCurrencyCode }}</strong>
              <template v-if="hasSecondaryCurrency">
                · Secondary currency: <strong>{{ secondaryCurrencyCode }}</strong>
              </template>
            </p>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-ghost" type="button" @click="goBack">Cancel</button>
            <button class="btn btn-primary-modern" type="button" @click="submit" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Product' : 'Create Product') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="form.errors.general" class="alert alert-danger mb-3">
        {{ form.errors.general }}
      </div>

      <div class="product-layout">
        <div class="product-layout__left">
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header">

              <i class="bi bi-info-circle"></i>
              <h2 class="card-title cardTitle">General Information</h2>
            </div>
            <div class="card-body formCardBody">

              <div class="form-grid">
                <div class="form-grid__full">
                  <label class="field-label">Name ( English )</label>
                  <input v-model="form.name" type="text" class="form-control fancy-input formControl"
                    placeholder="Name ( English )">
                  <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                </div>

                <div class="form-grid__full">
                  <label class="field-label">Barcode / SKU</label>
                  <input v-model="form.sku" type="text" class="form-control fancy-input formControl"
                    placeholder="Scan or enter barcode">
                  <div v-if="form.errors.sku" class="error-text">{{ form.errors.sku }}</div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Brand</label>
                  <SelectInput id="brand" v-model="form.brand" :options="props.brands" valueKey="id" labelKey="name"
                    placeholder="Select Brand" />
                  <div v-if="form.errors.brand" class="error-text">{{ form.errors.brand }}</div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Unit Type</label>
                  <SelectInput id="unit_type" v-model="form.unit_type" :options="unitTypeOptions"
                    valueKey="value" labelKey="label" placeholder="Select Unit" />
                  <div v-if="form.errors.unit_type" class="error-text">{{ form.errors.unit_type }}</div>
                </div>

                <div class="form-grid__full">
                  <label class="checkbox-line checkbox-line--stack">
                    <span class="checkbox-line__main">
                      <input v-model="form.is_loose_item" type="checkbox">
                      <span>Loose item</span>
                    </span>
                    <small>Allow decimal quantity in POS for weighed or measured sales.</small>
                  </label>
                  <div v-if="form.errors.is_loose_item" class="error-text">{{ form.errors.is_loose_item }}</div>
                </div>

                <div class="form-grid__full">
                  <label class="field-label">Description ( English )</label>
                  <textarea v-model="form.description" rows="7"
                    class="form-control fancy-input formControl fancy-textarea"
                    placeholder="Description ( English )"></textarea>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Categories</label>
                  <div class="picker-box" ref="categoriesBox">
                    <button class="picker-box__control" type="button" @click="categoriesOpen = !categoriesOpen">
                      <div class="picker-box__chips" v-if="selectedCategories.length">
                        <span v-for="item in selectedCategories" :key="item.id" class="picker-chip">
                          {{ item.name }}
                        </span>
                      </div>
                      <span v-else class="picker-box__placeholder">Categories</span>
                      <i class="bi" :class="categoriesOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>

                    <div v-if="categoriesOpen" class="picker-box__dropdown">
                      <label v-for="category in categories" :key="category.id" class="picker-box__item"
                        :style="{ paddingLeft: `${14 + (category.depth * 18)}px` }">
                        <input type="checkbox" :value="category.id" :checked="form.category_ids.includes(category.id)"
                          @change="toggleSelection(form.category_ids, category.id)" />
                        <span>{{ category.display_name }}</span>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="form-grid__half">
                  <label class="field-label">Taxes</label>
                  <div class="picker-box" ref="taxesBox">
                    <button class="picker-box__control" type="button" @click="taxesOpen = !taxesOpen">
                      <div class="picker-box__chips" v-if="selectedTaxes.length">
                        <span v-for="item in selectedTaxes" :key="item.id" class="picker-chip">
                          {{ item.name }}
                        </span>
                      </div>
                      <span v-else class="picker-box__placeholder">Taxes</span>
                      <i class="bi" :class="taxesOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>

                    <div v-if="taxesOpen" class="picker-box__dropdown">
                      <label v-for="tax in taxes" :key="tax.id" class="picker-box__item">
                        <input type="checkbox" :value="tax.id" :checked="form.tax_ids.includes(tax.id)"
                          @change="toggleSelection(form.tax_ids, tax.id)" />
                        <span>{{ tax.name }}</span>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="form-grid__full">
                  <label class="checkbox-line">
                    <input v-model="form.is_active" type="checkbox">
                    <span>Active</span>
                  </label>
                </div>
              </div>

            </div>
          </div>

          <div v-if="false" class="form-card">
            <!-- Shop POS: recipe ingredients are hidden for Food City products. -->
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header">

              <i class="bi bi-basket2"></i>
              <h2 class="card-title cardTitle">Ingredients</h2>
            </div>
            <div class="card-body formCardBody">

              <div v-if="!form.ingredients.length" class="empty-ingredients">
                <i class="bi bi-basket3"></i>
                <h5>No Ingredients Added</h5>
                <p>Start building your recipe by adding ingredients. Each ingredient includes quantity, unit, and cost
                  details.
                </p>
                <button class="btn btn-ingredient" type="button" @click="addIngredient">
                  <i class="bi bi-list-task fs-4 text-white" style="margin-bottom: -2px;"></i>Add Ingredient
                </button>
              </div>

              <div v-else class="ingredients-wrap">
                <div class="table-responsive">
                  <table class="table ingredient-table align-middle">
                    <thead>
                      <tr>
                        <th width="52"></th>
                        <th>Ingredient</th>
                        <th>Quantity</th>
                        <th>Loss Pct</th>
                        <th>Note</th>
                        <th width="64"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in form.ingredients" :key="index">
                        <td class="text-center drag-cell">
                          <i class="bi bi-grip-vertical"></i>
                        </td>

                        <td>
                          <SelectInput id="ingredient_id" v-model="item.ingredient_id" :options="ingredients.map(i => ({
                            ...i,
                            label: `${i.name}${i.unit_symbol ? ` (${i.unit_symbol})` : ''}`
                          }))" valueKey="id" labelKey="label" placeholder="Select Ingredient" />
                        </td>

                        <td>
                          <div class="qty-input-wrap">
                            <input v-model="item.quantity" type="number" min="0" step="0.0001"
                              class="form-control formControl" placeholder="Quantity">
                            <span class="qty-unit">{{ ingredientUnit(item.ingredient_id) }}</span>
                          </div>
                        </td>

                        <td>
                          <div class="qty-input-wrap">
                            <input v-model="item.loss_pct" type="number" min="0" step="0.01"
                              class="form-control formControl" placeholder="Loss Pct">
                            <span class="qty-unit">%</span>
                          </div>
                        </td>

                        <td>
                          <input v-model="item.note" type="text" class="form-control formControl" placeholder="Note">
                        </td>

                        <td class="text-center">
                          <button class="icon-btn icon-btn--danger" type="button" @click="removeIngredient(index)">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <button class="btn btn-primary-modern mt-3" type="button" @click="addIngredient">
                  <i class="bi bi-list-task "></i>Add Ingredient
                </button>
              </div>

            </div>
          </div>

          <div v-if="false" class="form-card">
            <!-- Shop POS: restaurant product options are hidden for Food City products. -->
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-boxes"></i>
              <h2 class="card-title cardTitle">Options</h2>
            </div>
            <div class="card-body formCardBody">

              <div v-show="!optionsSectionCollapsed">
                <div v-for="(option, optionIndex) in form.options" :key="optionIndex" class="option-card">
                  <div class="option-card__head">
                    <div class="option-card__head-left">
                      <i class="bi bi-grip-vertical"></i>
                      <span>{{ option.name || 'New Option' }}</span>
                    </div>

                    <div class="option-card__head-right">
                      <button class="icon-btn icon-btn--danger" type="button" @click="removeOption(optionIndex)">
                        <i class="bi bi-trash"></i>
                      </button>

                      <button class="icon-btn" type="button" @click="option.is_collapsed = !option.is_collapsed">
                        <i class="bi" :class="option.is_collapsed ? 'bi-chevron-down' : 'bi-chevron-up'"></i>
                      </button>
                    </div>
                  </div>

                  <div v-show="!option.is_collapsed" class="option-card__body">
                    <div class="option-form-grid">
                      <div>
                        <label class="field-label">Name ( English )</label>
                        <input v-model="option.name" type="text" class="form-control fancy-input formControl"
                          placeholder="Name ( English )">
                      </div>

                      <div>
                        <label class="field-label">Type</label>
                        <SelectInput id="type.id" v-model="option.type" :options="Object.entries(optionTypes).map(([value, label]) => ({
                          value,
                          label
                        }))"  valueKey="id" labelKey="name" placeholder="Select Type" />
                      </div>

                      <div class="required-box">
                        <label class="checkbox-line">
                          <input v-model="option.is_required" type="checkbox">
                          <span>Required</span>
                        </label>
                      </div>
                    </div>

                    <template v-if="usesRows(option.type)">
                      <div class="table-responsive mt-3">
                        <table class="table option-row-table align-middle">
                          <thead>
                            <tr>
                              <th width="52"></th>
                              <th>Label</th>
                              <th>Price ({{ baseCurrencyCode }})</th>
                              <th v-if="hasSecondaryCurrency">Price ({{ secondaryCurrencyCode }})</th>
                              <th>Price Type</th>
                              <th width="44"></th>
                              <th width="44"></th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(row, rowIndex) in option.rows" :key="rowIndex">
                              <td class="text-center drag-cell"><i class="bi bi-grip-vertical"></i></td>

                              <td>
                                <input v-model="row.label" type="text" class="form-control fancy-input formControl"
                                  placeholder="Label">
                              </td>

                              <td>
                                <input v-model="row.base_price" type="number" min="0" step="0.001"
                                  class="form-control fancy-input formControl"
                                  :placeholder="`${baseCurrencyCode} Price`">
                              </td>

                              <td v-if="hasSecondaryCurrency">
                                <input v-model="row.secondary_price" type="number" min="0" step="0.001"
                                  class="form-control fancy-input formControl"
                                  :placeholder="`${secondaryCurrencyCode} Price`">
                              </td>

                              <td>
                                <SelectInput id="price_type.id" v-model="row.price_type" :options="Object.entries(priceTypes).map(([value, label]) => ({
                                  value,
                                  label
                                }))"  valueKey="id" labelKey="name" placeholder="Select Type" />
                              </td>

                              <td class="text-center">
                                <button class="icon-btn icon-btn--danger" type="button"
                                  @click="removeOptionRow(option, rowIndex)">
                                  <i class="bi bi-trash"></i>
                                </button>
                              </td>

                              <td class="text-center">
                                <button class="icon-btn icon-btn--primary" type="button"
                                  @click="cloneOptionRow(option, row)">
                                  <i class="bi bi-basket"></i>
                                </button>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      <button class="btn btn-add-row mt-3" type="button" @click="addOptionRow(option)">
                        <i class="bi bi-sliders2-vertical me-2"></i>Add Row
                      </button>
                    </template>

                    <template v-else>
                      <div class="table-responsive mt-3">
                        <table class="table option-row-table align-middle">
                          <thead>
                            <tr>
                              <th>Price ({{ baseCurrencyCode }})</th>
                              <th v-if="hasSecondaryCurrency">Price ({{ secondaryCurrencyCode }})</th>
                              <th>Price Type</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td>
                                <input v-model="option.base_price" type="number" min="0" step="0.001"
                                  class="form-control fancy-input formControl" :placeholder="`${baseCurrencyCode} 0`">
                              </td>

                              <td v-if="hasSecondaryCurrency">
                                <input v-model="option.secondary_price" type="number" min="0" step="0.001"
                                  class="form-control fancy-input formControl"
                                  :placeholder="`${secondaryCurrencyCode} 0`">
                              </td>

                              <td>
                                <SelectInput id="price_type.id" v-model="option.price_type" :options="Object.entries(priceTypes).map(([value, label]) => ({
                                  value,
                                  label
                                }))"  valueKey="id" labelKey="name" placeholder="Select Type" />
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </template>
                  </div>
                </div>

                <div class="options-footer">
                  <div v-if="!form.options.length" class="empty-ingredients">
                    <i class="bi bi-sliders2-vertical"></i>
                    <h5>No Option Added</h5>
                    <p>
                    <p>
                      Start by adding options such as sauces, toppings, or extras. Each option can include quantity,
                      unit,
                      and cost details.
                    </p>
                    </p>
                    <button class="btn btn-ingredient" type="button" @click="addOption">
                      <i class="bi bi-sliders2 fs-4 text-white" style="margin-bottom: -2px;"></i>Add Options
                    </button>
                  </div>

                  <div class="template-insert" v-if="form.options.length">
                    <SelectInput id="type.id" v-model="selectedTemplateKey" :options="templates"
                      style="margin-top: 3.5px !important;" valueKey="key" labelKey="value"
                      placeholder="Select Template" />

                    <button class="btn custom-btn btn-insert" type="button" @click="insertTemplate"
                      :disabled="!selectedTemplateKey">
                      <i class="bi bi-plus-lg me-2"></i>Insert
                    </button>
                  </div>

                  <button v-if="form.options.length" class="btn btn-add-primary flex-grow-1" type="button"
                    @click="addOption">
                    <i class="bi bi-sliders2-vertical me-2"></i>Add Option
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="product-layout__right">
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-boxes"></i>
              <h2 class="card-title cardTitle">Media</h2>
            </div>
            <div class="card-body formCardBody">

              <div class="media-box" @click="triggerImage">
                <div class="d-flex flex-column text-center">
                  <div class="media-box__inner">

                    <template v-if="imagePreview" class="d-flex flex-column">
                      <img :src="imagePreview" alt="Product image" class="media-box__image">
                    </template>

                    <template v-else class="d-flex flex-column">
                      <i class="bi bi-images media-box__icon"></i>
                    </template>

                  </div>
                  <div class="fw-semibold mt-2">Upload Media</div>
                  <div class="small text-muted mt-1 text-center">JPG or PNG (max 5MB)</div>
                </div>
              </div>

              <input ref="imageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="d-none" @change="onImageChange">

              <div class="media-actions">
                <!-- <button class="btn btn-outline-secondary btn-sm" type="button" @click="triggerImage">Upload</button> -->
                <button v-if="imagePreview" class="btn btn-outline-danger btn-sm" type="button" @click="removeImage">
                  Remove
                </button>
              </div>

              <div v-if="form.errors.image" class="text-danger small mt-1">{{ form.errors.image }}</div>

            </div>
          </div>

          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header">

              <i class="bi bi-cash-stack"></i>
              <h2 class="card-title cardTitle">Pricing</h2>
            </div>
            <div class="card-body formCardBody">



              <div class="pricing-grid">
                <div>
                  <label class="field-label">Price ({{ baseCurrencyCode }})</label>
                  <input v-model="form.base_price" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Price">
                  <div v-if="form.errors.base_price" class="text-danger small mt-1">{{ form.errors.base_price }}</div>
                </div>

                <div v-if="hasSecondaryCurrency">
                  <label class="field-label">Price ({{ secondaryCurrencyCode }})</label>
                  <input v-model="form.secondary_price" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Secondary Price">
                </div>

                <div>
                  <label class="field-label">Cost Price ({{ baseCurrencyCode }})</label>
                  <input v-model="form.cost_price" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Cost Price">
                  <div v-if="form.errors.cost_price" class="text-danger small mt-1">{{ form.errors.cost_price }}</div>
                </div>

                <div>
                  <label class="field-label">
                    {{ isEdit ? 'Current Stock' : 'Opening Quantity' }} ({{ form.unit_type || 'pcs' }})
                  </label>
                  <input v-model="form.current_stock" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" :class="{ 'readonly-input': isEdit }"
                    :readonly="isEdit" :placeholder="isEdit ? 'Current Stock' : 'Opening Quantity'">
                  <div v-if="form.errors.current_stock" class="text-danger small mt-1">{{ form.errors.current_stock }}</div>
                </div>

                <div>
                  <label class="field-label">Reorder Level</label>
                  <input v-model="form.reorder_level" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Reorder Level">
                  <div v-if="form.errors.reorder_level" class="text-danger small mt-1">{{ form.errors.reorder_level }}</div>
                </div>

                <div>
                  <label class="field-label">Special Price Type</label>

                  <SelectInput id="type.id" v-model="form.special_price_type" :options="Object.entries(priceTypes).map(([value, label]) => ({
                    value,
                    label
                  }))"  valueKey="value" labelKey="label" placeholder="Select Price Type" />
                </div>

                <div>
                  <label class="field-label">
                    {{ form.special_price_type === 'percentage'
                      ? 'Special Price (%)'
                      : `Special Price (${baseCurrencyCode})` }}
                  </label>
                  <input v-model="form.base_special_price" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Special Price">
                  <div v-if="form.special_price_type === 'percentage' && form.base_special_price" class="mt-2 p-2 rounded-3 bg-light border border-dashed border-success text-success small d-flex flex-column gap-1">
                    <span class="d-flex align-items-center gap-1"><i class="bi bi-tag-fill"></i><strong>Estimated Final Price:</strong></span>
                    <span>{{ baseCurrencyCode }}: <strong class="fs-6">{{ finalBasePrice.toFixed(3) }}</strong></span>
                    <span v-if="hasSecondaryCurrency">{{ secondaryCurrencyCode }}: <strong class="fs-6">{{ finalSecondaryPrice.toFixed(3) }}</strong></span>
                  </div>
                </div>

                <div v-if="hasSecondaryCurrency && form.special_price_type === 'fixed'">
                  <label class="field-label">Special Price ({{ secondaryCurrencyCode }})</label>
                  <input v-model="form.secondary_special_price" type="number" min="0" step="0.001"
                    class="form-control fancy-input formControl" placeholder="Secondary Special Price">
                </div>

                <div>
                  <label class="field-label">Special Price Start</label>
                  <DatePicker v-model="form.special_price_start" placeholder="Select Date Time" timeEnabled />
                </div>

                <div>
                  <label class="field-label">Special Price End</label>
                  <DatePicker v-model="form.special_price_end" placeholder="Select Date Time" timeEnabled />
                </div>
              </div>

            </div>
          </div>

          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="d-flex align-items-center gap-2 card-header">
              <i class="bi bi-boxes"></i>
              <h2 class="card-title cardTitle">Additional</h2>
            </div>
            <div class="card-body formCardBody">

              <div class="pricing-grid">
                <div>
                  <label class="field-label">New from</label>
                  <DatePicker v-model="form.new_from" placeholder="Select Date Time" timeEnabled />
                </div>

                <div>
                  <label class="field-label">New to</label>
                  <DatePicker v-model="form.new_to" placeholder="Select Date Time" timeEnabled />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const props = defineProps({
  product: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
  brands: { type: Array, default: () => [] },
  taxes: { type: Array, default: () => [] },
  ingredients: { type: Array, default: () => [] },
  optionTypes: { type: Object, default: () => ({}) },
  priceTypes: { type: Object, default: () => ({}) },
  templates: { type: Array, default: () => [] },
})

const categoriesOpen = ref(false)
const taxesOpen = ref(false)
const categoriesBox = ref(null)
const taxesBox = ref(null)

const handleOutsideClick = (e) => {
  if (categoriesBox.value && !categoriesBox.value.contains(e.target)) {
    categoriesOpen.value = false
  }
  if (taxesBox.value && !taxesBox.value.contains(e.target)) {
    taxesOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
})

const optionsSectionCollapsed = ref(false)
const selectedTemplateKey = ref('')
const imageInput = ref(null)
const imagePreview = ref(props.product?.image_url || '')

const baseCurrency = computed(() => page.props.currencySettings?.base_currency ?? null)
const secondaryCurrency = computed(() => page.props.currencySettings?.secondary_currency ?? null)
const hasSecondaryCurrency = computed(() => !!secondaryCurrency.value)
const baseCurrencyCode = computed(() => baseCurrency.value?.code || 'Base Currency')
const secondaryCurrencyCode = computed(() => secondaryCurrency.value?.code || 'Secondary Currency')
const unitTypeOptions = [
  { value: 'pcs', label: 'Pieces' },
  { value: 'kg', label: 'Kilograms' },
  { value: 'g', label: 'Grams' },
  { value: 'l', label: 'Liters' },
  { value: 'ml', label: 'Milliliters' },
  { value: 'pack', label: 'Pack' },
  { value: 'box', label: 'Box' },
]

const form = useForm({
  name: props.product?.name ?? '',
  sku: props.product?.sku ?? '',
  brand: props.product?.brand ?? '',
  unit_type: props.product?.unit_type ?? 'pcs',
  is_loose_item: !!(props.product?.is_loose_item ?? false),
  description: props.product?.description ?? '',

  image: null,
  remove_image: 0,

  base_price: props.product?.base_price ?? '',
  secondary_price: props.product?.secondary_price ?? '',
  cost_price: props.product?.cost_price ?? '',
  current_stock: props.product?.current_stock ?? '',
  reorder_level: props.product?.reorder_level ?? '',

  special_price_type: props.product?.special_price_type ?? 'fixed',
  base_special_price: props.product?.base_special_price ?? '',
  secondary_special_price: props.product?.secondary_special_price ?? '',

  special_price_start: props.product?.special_price_start ?? '',
  special_price_end: props.product?.special_price_end ?? '',

  new_from: props.product?.new_from ?? '',
  new_to: props.product?.new_to ?? '',

  is_active: !!(props.product?.is_active ?? true),

  category_ids: props.product?.category_ids ?? [],
  tax_ids: props.product?.tax_ids ?? [],

  ingredients: props.product?.ingredients?.length
    ? props.product.ingredients.map((item) => ({
      ingredient_id: item.ingredient_id,
      quantity: item.quantity,
      loss_pct: item.loss_pct,
      note: item.note || '',
    }))
    : [],

  options: props.product?.options?.length
    ? props.product.options.map((option) => ({
      template_key: option.template_key || null,
      name: option.name,
      type: option.type,
      is_required: !!option.is_required,
      base_price: option.base_price ?? 0,
      secondary_price: option.secondary_price ?? '',
      price_type: option.price_type ?? 'fixed',
      is_collapsed: !!option.is_collapsed,
      rows: (option.rows || []).map((row) => ({
        label: row.label,
        base_price: row.base_price ?? 0,
        secondary_price: row.secondary_price ?? '',
        price_type: row.price_type ?? 'fixed',
      })),
    }))
    : [],
})

const isEdit = computed(() => !!props.product?.id)

const finalBasePrice = computed(() => {
  const basePrice = parseFloat(form.base_price || 0)
  const specialValue = parseFloat(form.base_special_price || 0)
  if (form.special_price_type === 'percentage') {
    return Math.max(0, basePrice - (basePrice * specialValue / 100))
  }
  return specialValue
})

const finalSecondaryPrice = computed(() => {
  const secPrice = parseFloat(form.secondary_price || form.base_price || 0)
  if (form.special_price_type === 'percentage') {
    const specialValue = parseFloat(form.base_special_price || 0)
    return Math.max(0, secPrice - (secPrice * specialValue / 100))
  }
  return parseFloat(form.secondary_special_price || 0)
})

const selectedCategories = computed(() =>
  props.categories.filter((item) => form.category_ids.includes(item.id))
)

const selectedTaxes = computed(() =>
  props.taxes.filter((item) => form.tax_ids.includes(item.id))
)

function goBack() {
  router.visit(route('vendor.products.index'))
}

function toggleSelection(arr, id) {
  const index = arr.indexOf(id)

  if (index >= 0) {
    arr.splice(index, 1)
  } else {
    arr.push(id)
  }
}

function triggerImage() {
  imageInput.value?.click()
}

function onImageChange(event) {
  const file = event.target.files?.[0]
  if (!file) return

  form.image = file
  form.remove_image = 0
  imagePreview.value = URL.createObjectURL(file)
}

function removeImage() {
  form.image = null
  form.remove_image = 1
  imagePreview.value = ''
  if (imageInput.value) imageInput.value.value = ''
}

function ingredientUnit(ingredientId) {
  const item = props.ingredients.find((row) => Number(row.id) === Number(ingredientId))
  return item?.unit_symbol || ''
}

function addIngredient() {
  form.ingredients.push({
    ingredient_id: '',
    quantity: '',
    loss_pct: '',
    note: '',
  })
}

function removeIngredient(index) {
  form.ingredients.splice(index, 1)
}

function newOption() {
  return {
    template_key: null,
    name: '',
    type: 'text',
    is_required: false,
    base_price: 0,
    secondary_price: '',
    price_type: 'fixed',
    is_collapsed: false,
    rows: [],
  }
}

function newOptionRow() {
  return {
    label: '',
    base_price: 0,
    secondary_price: '',
    price_type: 'fixed',
  }
}

function usesRows(type) {
  return ['select', 'multiple_select', 'checkbox', 'radio_button'].includes(type)
}

function onOptionTypeChange(option) {
  if (usesRows(option.type)) {
    if (!Array.isArray(option.rows) || !option.rows.length) {
      option.rows = [newOptionRow()]
    }
  } else {
    option.rows = []
  }
}

function addOption() {
  form.options.push(newOption())
}

function removeOption(index) {
  form.options.splice(index, 1)
}

function addOptionRow(option) {
  if (!Array.isArray(option.rows)) option.rows = []
  option.rows.push(newOptionRow())
}

function removeOptionRow(option, rowIndex) {
  option.rows.splice(rowIndex, 1)
}

function cloneOptionRow(option, row) {
  option.rows.push({
    label: row.label,
    base_price: row.base_price,
    secondary_price: row.secondary_price,
    price_type: row.price_type,
  })
}

function insertTemplate() {
  const template = props.templates.find((item) => item.key === selectedTemplateKey.value)
  if (!template) return

  form.options.push({
    template_key: template.key,
    name: template.name,
    type: template.type,
    is_required: !!template.is_required,
    base_price: template.base_price ?? 0,
    secondary_price: hasSecondaryCurrency.value ? (template.secondary_price ?? '') : '',
    price_type: template.price_type ?? 'fixed',
    is_collapsed: false,
    rows: (template.rows || []).map((row) => ({
      label: row.label,
      base_price: row.base_price ?? 0,
      secondary_price: hasSecondaryCurrency.value ? (row.secondary_price ?? '') : '',
      price_type: row.price_type ?? 'fixed',
    })),
  })

  selectedTemplateKey.value = ''
}

function normalizedPayload(data) {
  return {
    ...data,
    remove_image: data.remove_image ? 1 : 0,
    is_active: data.is_active ? 1 : 0,
    is_loose_item: data.is_loose_item ? 1 : 0,

    category_ids: (data.category_ids || []).map((id) => Number(id)),
    tax_ids: (data.tax_ids || []).map((id) => Number(id)),

    base_price: (data.base_price === '' || data.base_price === null) ? null : data.base_price,
    secondary_price: hasSecondaryCurrency.value ? (data.secondary_price || null) : null,
    cost_price: data.cost_price || 0,
    current_stock: data.current_stock || 0,
    reorder_level: data.reorder_level || 0,

    base_special_price: data.base_special_price || null,
    secondary_special_price: hasSecondaryCurrency.value && data.special_price_type === 'fixed'
      ? (data.secondary_special_price || null)
      : null,

    ingredients: (data.ingredients || []).map((item) => ({
      ingredient_id: item.ingredient_id || null,
      quantity: item.quantity || 0,
      loss_pct: item.loss_pct || 0,
      note: item.note || null,
    })),

    options: (data.options || []).map((option) => ({
      template_key: option.template_key || null,
      name: option.name || '',
      type: option.type || 'text',
      is_required: option.is_required ? 1 : 0,
      base_price: option.base_price || 0,
      secondary_price: hasSecondaryCurrency.value ? (option.secondary_price || null) : null,
      price_type: option.price_type || 'fixed',
      is_collapsed: option.is_collapsed ? 1 : 0,
      rows: usesRows(option.type)
        ? (option.rows || []).map((row) => ({
          label: row.label || '',
          base_price: row.base_price || 0,
          secondary_price: hasSecondaryCurrency.value ? (row.secondary_price || null) : null,
          price_type: row.price_type || 'fixed',
        }))
        : [],
    })),
  }
}

function submit() {
  const options = {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      router.visit(route('vendor.products.index'))
    },
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong.'

      alertError(message)
    },
  }

  if (isEdit.value) {
    form
      .transform((data) => ({
        ...normalizedPayload(data),
        _method: 'PUT',
      }))
      .post(route('vendor.products.update', props.product.id), options)

    return
  }

  form
    .transform((data) => normalizedPayload(data))
    .post(route('vendor.products.store'), options)
}
</script>

<style scoped>
.product-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 420px;
  gap: 32px;
}

.product-layout__left,
.product-layout__right {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.table-responsive {
  overflow: visible !important;
}

.ingredient-table,
.option-row-table {
  overflow: visible;
}

.product-page {
  min-height: 100%;
}

.product-page__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.product-page__title {
  font-weight: 800;
  color: #334155;
  margin: 0;
}

.product-page__subtitle {
  margin: 6px 0 0;
  color: #64748b;
}

.product-page__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.product-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 420px;
  gap: 22px;
}

.product-layout__left,
.product-layout__right {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.lang-select {
  margin-bottom: 16px;
}

.lang-select__btn {
  min-width: 128px;
  border: 1px solid #d7dee7;
  background: #fff;
  border-radius: 12px;
  min-height: 40px;
  padding: 0 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.form-grid__full {
  grid-column: 1 / -1;
}

.form-grid__half {
  grid-column: span 1;
}

.field-label {
  display: block;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 8px;
}

.fancy-input {
  border-radius: 12px;
  border: 1px solid #d7dee7;
  box-shadow: none;
}

.formControl {
  height: 40px !important;
  font-size: 14px;
}

.formControl:focus {
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
}

.readonly-input {
  background: #f8fafc;
  color: #64748b;
  cursor: not-allowed;
}

.fancy-textarea {
  min-height: 160px;
  resize: vertical;
}

.checkbox-line {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #475569;
}

.checkbox-line--stack {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
}

.checkbox-line__main {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.checkbox-line--stack small {
  color: #64748b;
  font-weight: 500;
}

.picker-box {
  position: relative;
}

.picker-box__control {
  width: 100%;
  min-height: 40px;
  border-radius: 12px;
  border: 1px solid #d7dee7;
  background: #fff;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  text-align: left;
}

.picker-box__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.picker-chip {
  background: #eef2f7;
  color: #475569;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

.picker-box__placeholder {
  color: #94a3b8;
}

.picker-box__dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 18px 36px rgba(15, 23, 42, .12);
  max-height: 320px;
  overflow: auto;
  z-index: 20;
  padding: 8px 0;
}

.picker-box__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  color: #475569;
  cursor: pointer;
}

.picker-box__item:hover {
  background: #fff7ed;
}

.media-box {
  width: 100%;
  height: 250px;
  border: 2px dashed #ffbe90;
  background: #fff8f3;
  border-radius: 16px;
  display: grid;
  place-items: center;
  overflow: hidden;
  cursor: pointer;
}

.media-box__placeholder {
  font-size: 52px;
  color: #d0d5dd;
}

.media-box__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-box__icon {
  font-size: 58px;
  color: #a0a0a0;
}

.media-box__inner {
  width: 100%;
  max-width: 120px;
  aspect-ratio: 1 / 1;
  margin: auto;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  border-radius: 12px;
  background: #fff8f3;
  overflow: hidden;
}

.media-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.pricing-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}

.empty-ingredients {
  border: 1px dashed #d7dee7;
  border-radius: 16px;
  padding: 36px 18px;
  text-align: center;
  color: #64748b;
}

.empty-ingredients i {
  font-size: 46px;
  color: #f28c00;
}

.empty-ingredients h5 {
  margin: 10px 0 8px;
  font-weight: 800;
  color: #334155;
}

.btn-ingredient {
  background: linear-gradient(135deg, #f28c00 0%, #f5a623 100%);
  color: #ffffff;
  box-shadow: 0 4px 16px rgba(242, 140, 0, 0.3);
}

.ingredients-wrap .table,
.option-row-table,
.ingredient-table {
  margin-bottom: 0;
}

.drag-cell {
  color: #6b7280;
  font-size: 18px;
}

.qty-input-wrap {
  position: relative;
}

.qty-unit {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  font-weight: 600;
  font-size: 12px;
}

.option-card {
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  /* overflow: hidden; */
  margin-bottom: 12px;
}

.option-card__head {
  min-height: 52px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 14px;
  border-bottom: 1px solid #e2e8f0;
}

.option-card__head-left {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  color: #475569;
}

.option-card__head-right {
  display: flex;
  gap: 8px;
}

.option-card__body {
  padding: 14px;
}

.option-form-grid {
  display: grid;
  grid-template-columns: 2fr 1.5fr auto;
  gap: 14px;
  align-items: end;
}

.required-box {
  min-width: 130px;
}

.options-footer {
  display: flex;
  justify-content: center;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
  margin-top: 14px;
}

.template-insert {
  display: flex;
  gap: 10px;
}

.btn-cancel,
.btn-save,
.btn-add-primary,
.btn-add-row,
.btn-insert,
.btn-ingredient {
  border: none;
  min-height: 44px;
  padding: 11px 24px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-cancel {
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.btn-save {
  background: linear-gradient(135deg, #ff9800, #f57c00);
  color: #fff;
}

.btn-add-primary,
.btn-add-row {
  background: linear-gradient(135deg, #ff9800, #f57c00);
  color: #fff;
}

.btn-insert {
  background: linear-gradient(135deg, #fb923c, #f97316, #ea580c);
  color: #fff;
  border: none;
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25);
  transition: all 0.2s ease;
}

.btn-insert:hover {
  background: linear-gradient(135deg, #f97316, #ea580c, #c2410c);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(249, 115, 22, 0.35);
}

.btn-insert:active {
  transform: translateY(0px);
  box-shadow: 0 3px 8px rgba(249, 115, 22, 0.25);
}

.btn-insert:focus {
  outline: none;
  box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.35);
}

.icon-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
}

.icon-btn--danger {
  color: #ef4444;
  border-color: #fecaca;
}

.icon-btn--primary {
  color: #0b4673;
}

.collapse-toggle {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: #475569;
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

/* Responsive - Preserved */
@media (max-width: 1399.98px) {
  .product-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 991.98px) {

  .form-grid,
  .option-form-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1399.98px) {
  .product-layout {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 991.98px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .option-form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
