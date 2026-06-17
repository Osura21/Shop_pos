<template>
  <div :class="className" ref="root">

    <label v-if="label" :for="id" class="form-label formLabel mb-2">
      {{ label }}
      <span v-if="isRequired" class="text-danger ms-1">*</span>
    </label>

    <!-- Display Input -->
    <div class="floating-input-wrapper position-relative" :class="disabled ? 'opacity-50 pe-none' : ''">

      <span v-if="secondaryLabel" class="floating-chip">
        {{ secondaryLabel }}
      </span>

      <input :id="id" type="text" class="form-control select-display" :class="[
        error ? 'is-invalid' : '',
        canClear ? 'select-display--clearable' : ''
      ]"
        :value="selectedText" :placeholder="placeholder" readonly :disabled="disabled" role="combobox"
        :aria-expanded="open ? 'true' : 'false'" @click="openDropdown" @keydown.delete.prevent="clearSelection"
        @keydown.backspace.prevent="clearSelection" />

      <div class="select-actions">
        <button v-if="canClear" type="button" class="select-clear-btn" aria-label="Clear selected option"
          @mousedown.prevent @click.stop="clearSelection">
          <i class="bi bi-x"></i>
        </button>
        <i class="bi bi-chevron-down select-chevron"></i>
      </div>

      <!-- Dropdown -->
      <transition name="fade" appear>
        <div v-if="open" class="bs-select-menu shadow-sm" :class="[
          placement === 'down' ? 'menu-down' : 'menu-up',
          hPlacement === 'right' ? 'menu-right' : 'menu-left'
        ]" role="listbox">
          <!-- Search -->
          <div class="dropdown-search">
            <input ref="search" type="text" v-model="query" class="form-control form-control-sm" placeholder="Search..."
              autocomplete="off" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
              @keydown.enter.prevent="selectActive" @keydown.esc.prevent="close" />
          </div>

          <!-- Options -->
          <div v-if="filtered.length" class="options-list">
            <button v-for="(opt, idx) in filtered" :key="getVal(opt)" type="button"
              class="dropdown-item w-100 text-start" role="option" :aria-selected="isSelected(getVal(opt))" :class="[
                idx === activeIndex ? 'active' : '',
                isSelected(getVal(opt)) && idx !== activeIndex ? 'fw-semibold' : ''
              ]" @mousedown.prevent @mouseenter="activeIndex = idx" @click="choose(opt)">
              {{ getLbl(opt) }}
            </button>
          </div>

          <div v-else class="no-results">
            No results
          </div>
        </div>
      </transition>
    </div>

    <!-- Error -->
    <div v-if="error" class="invalid-feedback d-block mt-1">
      {{ error }}
    </div>

    <select v-if="useHiddenSelect" :name="name" class="d-none" :required="isRequired" :disabled="disabled"
      :value="modelValue">
      <option v-for="opt in normalizedOptions" :key="'hid-' + getVal(opt)" :value="getVal(opt)">
        {{ getLbl(opt) }}
      </option>
    </select>
  </div>
</template>

<script>
export default {
  name: 'SelectInputBootstrap',
  emits: ['update:modelValue', 'change'],
  props: {
    id: { type: String, required: true },
    className: { type: String, default: 'w-100' },
    label: { type: String, required: true },
    error: { type: String, default: '' },
    isRequired: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    options: { type: Array, default: () => [] },
    valueKey: { type: String, default: 'id' },
    labelKey: { type: String, default: 'name' },
    modelValue: { default: '' },
    placeholder: { type: String, default: 'Select option' },
    name: { type: String, default: '' },
    useHiddenSelect: { type: Boolean, default: false },
    secondaryLabel: { type: String, default: '' },
    clearable: { type: Boolean, default: true },
  },

  data() {
    return {
      open: false,
      query: '',
      activeIndex: 0,
      placement: 'down',
      hPlacement: 'right',
      dropdownMaxHeight: 280,
    }
  },

  computed: {
    getVal() {
      return o => o?.[this.valueKey] ?? o?.value ?? o
    },
    getLbl() {
      return o => o?.[this.labelKey] ?? o?.label ?? String(o)
    },
    normalizedOptions() {
      return Array.isArray(this.options) ? this.options : []
    },
    filtered() {
      const q = this.query.trim().toLowerCase()
      if (!q) return this.normalizedOptions
      return this.normalizedOptions.filter(o =>
        this.getLbl(o).toLowerCase().includes(q)
      )
    },
    selectedText() {
      const found = this.normalizedOptions.find(
        o => this.getVal(o) === this.modelValue
      )
      return found ? this.getLbl(found) : this.placeholder
    },
    hasValue() {
      return this.modelValue !== '' && this.modelValue !== null && this.modelValue !== undefined
    },
    canClear() {
      return this.clearable && !this.disabled && this.hasValue
    },
  },

  watch: {
    open(val) {
      if (val) {
        this.$nextTick(() => {
          this.recomputePlacement()
          this.$refs.search?.focus()
          const idx = this.filtered.findIndex(o => this.getVal(o) === this.modelValue)
          this.activeIndex = idx >= 0 ? idx : 0
        })
        document.addEventListener('pointerdown', this.onOutside, { capture: true })
      } else {
        document.removeEventListener('pointerdown', this.onOutside, { capture: true })
      }
    }
  },

  methods: {
    openDropdown() {
      if (!this.disabled) this.open = true
    },

    close() {
      this.open = false
      this.query = ''
      this.activeIndex = 0
    },

    onOutside(e) {
      if (!this.$refs.root.contains(e.target)) this.close()
    },

    isSelected(val) {
      return this.modelValue === val
    },

    move(dir) {
      if (!this.filtered.length) return
      this.activeIndex = (this.activeIndex + dir + this.filtered.length) % this.filtered.length
    },

    selectActive() {
      const opt = this.filtered[this.activeIndex]
      if (opt) this.choose(opt)
    },

    choose(opt) {
      this.emitValue(this.getVal(opt))
      this.close()
    },

    clearSelection() {
      if (!this.canClear) return
      this.emitValue('')
      this.close()
    },

    emitValue(val) {
      this.$emit('update:modelValue', val)
      this.$emit('change', val)
    },

    recomputePlacement() {
      const rect = this.$refs.root.getBoundingClientRect()

      const vh = window.innerHeight
      const vw = window.innerWidth

      this.placement =
        (vh - rect.bottom < this.dropdownMaxHeight && rect.top > 100)
          ? 'up'
          : 'down'

      const dropdownWidth = 260

      this.hPlacement =
        (vw - rect.left < dropdownWidth && rect.left > dropdownWidth)
          ? 'left'
          : 'right'
    },
  }
}
</script>

<style scoped>
/* Main Input */
.select-display {
  height: 42px;
  border-radius: 12px;   
  /* margin-top: 34px; */
  border: 1px solid #d1d5db;
  padding: 0 42px 0 14px;
  font-size: 14px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s;
}

.select-display--clearable {
  padding-right: 68px;
}

.select-display:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  outline: none;
}

.select-display.is-invalid {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
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

.menu-right {
  left: 0;
}

.menu-left {
  right: 0;
}

/* Search */
.dropdown-search {
  padding: 6px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.dropdown-search .form-control {
  height: 36px;
  font-size: 0.85rem;
  border-radius: 8px;
  border: 1px solid #d1d5db;
}


.options-list {
  max-height: 190px;
  margin-top: 6px;
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
  background: #93c5fd;
  border-radius: 20px;
  min-height: 30px;
}

.options-list::-webkit-scrollbar-thumb:hover {
  background: #3b82f6;
}

input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  outline: none;
}

.dropdown-item {
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 14px;
  min-height: 36px;
  display: flex;
  align-items: center;
}

.dropdown-item.active {
  background: linear-gradient(135deg, #2563eb, #60a5fa);
  color: #fff;
  border-radius: 10px;
  position: relative;
}

.dropdown-item:not(.active):hover {
  background: #f8fafc;
}


.floating-input-wrapper {
  position: relative;
  margin-top: -3px;
}

.select-actions {
  position: absolute;
  top: 50%;
  right: 10px;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  gap: 4px;
  pointer-events: none;
}

.select-chevron {
  color: #334155;
  font-size: 13px;
  line-height: 1;
}

.select-clear-btn {
  width: 22px;
  height: 22px;
  border: 1px solid #94a3b8;
  border-radius: 999px;
  background: #ffffff;
  color: #475569;
  display: inline-grid;
  place-items: center;
  padding: 0;
  cursor: pointer;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.12s ease, border-color 0.12s ease, color 0.12s ease, background 0.12s ease;
}

.select-clear-btn i {
  font-size: 14px;
  line-height: 1;
}

.floating-input-wrapper:hover .select-clear-btn,
.floating-input-wrapper:focus-within .select-clear-btn {
  opacity: 1;
  pointer-events: auto;
}

.select-clear-btn:hover,
.select-clear-btn:focus {
  border-color: #ef4444;
  background: #fff7ed;
  color: #ef4444;
  outline: none;
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

/* Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (min-width: 1377px) {
  .floating-chip {
    display: none;
  }
}
</style>
