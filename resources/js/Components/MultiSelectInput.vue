<template>
  <div :class="className" ref="root">

    <!-- Label -->
    <label v-if="label" class="form-label formLabel mb-1">
      {{ label }}
      <span v-if="isRequired" class="text-danger ms-1">*</span>
    </label>

    <!-- Input -->
    <div class="floating-input-wrapper position-relative" :class="disabled ? 'opacity-50 pe-none' : ''"
      @click="openDropdown">

      <span v-if="secondaryLabel" class="floating-chip">
        {{ secondaryLabel }}
      </span>

      <div class="multiselect-input form-control formControl" ref="input"
        :class="[error ? 'is-invalid' : '', open ? 'is-open' : '']">

        <!-- Visible pills -->
        <span v-for="opt in visibleSelected" :key="getVal(opt)" class="badge-pill">
          {{ getLbl(opt) }}
          <span class="remove-pill" @click.stop="remove(opt)">×</span>
        </span>

        <!-- +N more -->
        <span v-if="hiddenCount > 0" class="more-pill">
          +{{ hiddenCount }} more
        </span>

        <!-- Hidden measurer -->
        <div ref="measurer" class="measurer">
          <span v-for="opt in selectedOptions" :key="getVal(opt)" class="badge-pill">
            {{ getLbl(opt) }}
          </span>
        </div>

      </div>

      <!-- Dropdown -->
      <transition name="fade" appear>
        <div v-if="open" class="bs-select-menu shadow-sm" :style="dropdownStyle">

          <!-- Search -->
          <div class="dropdown-search">
            <input ref="search" type="text" v-model="query" class="form-control formControl form-control-sm"
              placeholder="Search..." />
          </div>

          <!-- Select All -->
          <div v-if="normalizedOptions.length" class="select-all-item">
            <button type="button" class="dropdown-item w-100 text-start select-all-btn" @mousedown.prevent
              @click="toggleAll">

              <span class="check-box" :class="{ checked: isAllSelected }">
                <svg v-if="isAllSelected" viewBox="0 0 10 8" fill="none" width="10" height="8">
                  <path d="M1 4L3.5 6.5L9 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
              </span>

              <span class="flex-grow-1">Select All</span>

              <small v-if="modelValue.length" class="text-muted ms-auto">
                {{ modelValue.length }} selected
              </small>
            </button>
          </div>

          <!-- Options -->
          <div v-if="filtered.length" class="options-list">
            <button v-for="(opt, idx) in filtered" :key="getVal(opt)" type="button"
              class="dropdown-item w-100 text-start" :class="{
                active: idx === activeIndex,
                'fw-semibold': isSelected(getVal(opt)) && idx !== activeIndex
              }" @mouseenter="activeIndex = idx" @mousedown.prevent @click="toggle(opt)">
              <span class="check-box" :class="{ checked: isSelected(getVal(opt)) }">
                <svg v-if="isSelected(getVal(opt))" viewBox="0 0 10 8" fill="none" width="10" height="8">
                  <path d="M1 4L3.5 6.5L9 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
              </span>
              {{ getLbl(opt) }}
            </button>
          </div>

          <div v-else class="no-results">
            No results found
          </div>

        </div>
      </transition>
    </div>

    <!-- Error -->
    <div v-if="error" class="invalid-feedback d-block mt-1">
      {{ error }}
    </div>

    <!-- Hidden select (optional) -->
    <select
      v-if="useHiddenSelect"
      multiple
      :name="name"
      class="d-none"
    >
      <option
        v-for="opt in normalizedOptions"
        :key="getVal(opt)"
        :value="getVal(opt)"
        :selected="isSelected(getVal(opt))"
      >
        {{ getLbl(opt) }}
      </option>
    </select>
  </div>
</template>

<script>
export default {
  name: 'MultiSelectInput',

  props: {
    label: String,
    error: String,
    options: { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
    valueKey: { type: String, default: 'value' },
    labelKey: { type: String, default: 'label' },
    placeholder: { type: String, default: 'Select options' },
    className: { type: String, default: 'w-100' },
    name: { type: String, default: '' },
    useHiddenSelect: { type: Boolean, default: false },
    disabled: Boolean,
    secondaryLabel: String,
    isRequired: Boolean
  },

  data() {
    return {
      open: false,
      query: '',
      activeIndex: 0,
      visibleCount: 2, 

      dropdownStyle: {
        top: '100%',
        bottom: 'auto',
        left: '0px',
        right: 'auto'
      }
    }
  },

  computed: {
    getVal() { return o => o?.[this.valueKey] ?? o },
    getLbl() { return o => o?.[this.labelKey] ?? String(o) },

    normalizedOptions() {
      return Array.isArray(this.options) ? this.options : []
    },

    selectedOptions() {
      return this.normalizedOptions.filter(o =>
        this.modelValue.includes(this.getVal(o))
      )
    },

    visibleSelected() {
      return this.selectedOptions.slice(0, this.visibleCount)
    },

    hiddenCount() {
      return this.selectedOptions.length - this.visibleCount
    },

    filtered() {
      const q = this.query.trim().toLowerCase()
      if (!q) return this.normalizedOptions
      return this.normalizedOptions.filter(o =>
        this.getLbl(o).toLowerCase().includes(q)
      )
    },

    isAllSelected() {
      return (
        this.normalizedOptions.length > 0 &&
        this.modelValue.length === this.normalizedOptions.length
      )
    }
  },

  mounted() {
    document.addEventListener('click', this.handleOutsideClick)
    window.addEventListener('resize', this.calculateVisible)

    this.$nextTick(this.calculateVisible)
  },

  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick)
    window.removeEventListener('resize', this.calculateVisible)
  },

  methods: {

    openDropdown() {
      if (this.disabled) return

      this.open = true

      this.$nextTick(() => {
        this.calculatePosition()
        this.calculateVisible()
      })
    },

    calculateVisible() {
      const container = this.$refs.input
      const measurer = this.$refs.measurer

      if (!container || !measurer) return

      const maxWidth = container.clientWidth - 60
      let used = 0
      let count = 0

      const pills = measurer.children

      for (let el of pills) {
        const w = el.offsetWidth + 6
        if (used + w > maxWidth) break
        used += w
        count++
      }

      this.visibleCount = count || 1
    },

    calculatePosition() {
      const el = this.$refs.root
      const rect = el.getBoundingClientRect()

      const spaceBelow = window.innerHeight - rect.bottom
      const spaceAbove = rect.top

      if (spaceBelow < 300 && spaceAbove > 300) {
        this.dropdownStyle = { bottom: '100%', top: 'auto', left: '0px' }
      } else {
        this.dropdownStyle = { top: '100%', bottom: 'auto', left: '0px' }
      }

      if (rect.left + 280 > window.innerWidth) {
        this.dropdownStyle.left = 'auto'
        this.dropdownStyle.right = '0px'
      }
    },

    handleOutsideClick(e) {
      if (!this.$refs.root.contains(e.target)) {
        this.close()
      }
    },

    close() {
      this.open = false
      this.query = ''
    },

    isSelected(val) {
      return this.modelValue.includes(val)
    },

    toggle(opt) {
      const val = this.getVal(opt)

      const next = this.isSelected(val)
        ? this.modelValue.filter(v => v !== val)
        : [...this.modelValue, val]

      this.$emit('update:modelValue', next)
      this.$emit('change', next)

      this.$nextTick(this.calculateVisible)
    },

    remove(opt) {
      this.toggle(opt)
    },

    toggleAll() {
      if (this.isAllSelected) {
        this.$emit('update:modelValue', [])
      } else {
        const all = this.normalizedOptions.map(o => this.getVal(o))
        this.$emit('update:modelValue', all)
      }

      this.$nextTick(this.calculateVisible)
    }
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.floating-input-wrapper {
  position: relative;
}

.floating-chip {
  position: absolute;
  top: -8px;
  left: 12px;
  background: #fff;
  padding: 0 6px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #6b7280;
  z-index: 2;
  line-height: 1;
}

.more-pill {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  background: #f3f4f6;
  border-radius: 999px;
  padding: 2px 8px;
}

/* hidden measurer */
.measurer {
  position: absolute;
  visibility: hidden;
  white-space: nowrap;
  height: 0;
  overflow: hidden;
}

@media (min-width: 1100px) {
  .floating-chip {
    display: none;
  }
}

.multiselect-input {
  min-height: 42px;
  max-height: 94px;
  border-radius: 12px;
  border: 1px solid #d1d5db;
  padding: 6px 10px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  cursor: pointer;
  background-color: #fff;
}

.multiselect-input:hover {
  border-color: #f28c00;
}

.multiselect-input.is-open,
.multiselect-input:focus-within {
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
  outline: none;
}

.multiselect-input.is-invalid {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
  padding-right: calc(1.5em + 0.75rem);
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.multiselect-placeholder {
  color: #9ca3af;
  font-size: 14px;
  user-select: none;
}

.badge-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: linear-gradient(135deg, rgba(255, 122, 54, 0.12), rgba(255, 159, 77, 0.12));
  color: #c85a00;
  border: 1px solid rgba(242, 140, 0, 0.25);
  border-radius: 20px;
  padding: 3px 10px 3px 10px;
  font-size: 0.78rem;
  font-weight: 600;
  line-height: 1.4;
  white-space: nowrap;
}

.select-all-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  margin-bottom: 4px;
  padding-bottom: 4px;
}

.select-all-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
}

.remove-pill {
  cursor: pointer;
  font-weight: 700;
  font-size: 0.95rem;
  color: #f28c00;
  line-height: 1;
  transition: color 0.15s;
}

.remove-pill:hover {
  color: #c85a00;
}

/* Dropdown Menu */
.bs-select-menu {
  position: absolute;

  min-width: 100%;
  width: max-content;

  max-width: calc(100vw - 16px);

  z-index: 9999;
  background: #fff;
  border-radius: 14px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);

  max-height: 300px;
  overflow-y: hidden;
  padding: 6px;
}

.menu-down {
  top: calc(100% + 8px);
}

.menu-up {
  bottom: calc(100% + 8px);
}

.dropdown-search {
  padding: 6px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  margin-bottom: 4px;
}

.dropdown-search .formControl {
  height: 36px;
  font-size: 0.85rem;
  border-radius: 8px;
  border: 1px solid #d1d5db;
}

.dropdown-search .formControl:focus {
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
  outline: none;
}

.options-list {
  max-height: 190px;
  overflow-y: auto;
  padding-right: 1px;
}

.options-list::-webkit-scrollbar {
  width: 3px;
}

.options-list::-webkit-scrollbar-track {
  background: transparent;
}

.options-list::-webkit-scrollbar-thumb {
  background: #ff9f4d;
  border-radius: 20px;
  min-height: 30px;
}

.options-list::-webkit-scrollbar-thumb:hover {
  background: #ff7a1f;
}

.dropdown-item {
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 14px;
  min-height: 36px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #374151;
  transition: background 0.12s;
}

.dropdown-item:not(.active):hover {
  background: #f8fafc;
}

.dropdown-item.active {
  background: linear-gradient(135deg, #ff7a36, #ff9f4d);
  color: #fff;
  border-radius: 10px;
}

.check-box {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1.5px solid #d1d5db;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s;
  color: #fff;
}

.check-box.checked {
  background: linear-gradient(135deg, #ff7a36, #ff9f4d);
  border-color: #ff7a36;
}

.dropdown-item.active .check-box {
  background: rgba(255, 255, 255, 0.507);
  border-color: rgba(255, 255, 255, 0.6);
}

.dropdown-item.active .check-box.checked {
  background: linear-gradient(135deg, #ff7a36, #ff9f4d);
  border-color: rgb(255, 255, 255);
}


.no-results {
  padding: 12px 16px;
  font-size: 0.85rem;
  color: #9ca3af;
  text-align: center;
}
</style>