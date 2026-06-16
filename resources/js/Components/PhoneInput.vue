<template>
  <div :class="className" ref="root">
    <label :for="id" class="form-label formLabel mb-1">
      {{ label }}
      <span v-if="isRequired" class="text-danger ms-1">*</span>
    </label>

    <div class="phone-row position-relative" :class="disabled ? 'opacity-50 pe-none' : ''">
      <!-- Country Dropdown -->
      <div class="country-dropdown position-relative" ref="countryRoot">
        <div class="select-display country-display" :class="error ? 'is-invalid' : ''" role="combobox"
          :aria-expanded="showCountry ? 'true' : 'false'" @click="toggleCountry">
          <span class="country-code">{{ selectedCountry.code || '+94' }}</span>
          <i class="bi bi-chevron-down ms-1"></i>
        </div>

        <!-- Dropdown -->
        <transition name="fade" appear>
          <div v-if="showCountry" class="bs-select-menu shadow-sm country-list"
            :class="countryPlacement === 'down' ? 'menu-down' : 'menu-up'">
            <!-- Search -->
            <div class="dropdown-search">
              <input ref="countrySearch" v-model="countryQuery" type="text" class="form-control form-control-sm"
                placeholder="Search country..." autocomplete="off" @focus="showCountry = true" @input="onSearchInput"
                @keydown.down.prevent="moveCountry(1)" @keydown.up.prevent="moveCountry(-1)"
                @keydown.enter.prevent="selectActiveCountry" @keydown.esc.prevent="closeDropdown" />
            </div>

            <!-- Options -->
            <div v-if="filteredCountries.length" class="options-list country-options">
              <button v-for="(c, idx) in filteredCountries" :key="c.code + '-' + c.name" type="button"
                class="dropdown-item country-item w-100 text-start" :class="{
                  active: idx === activeCountryIndex,
                  selected:
                    c.code === selectedCountry.code &&
                    c.name === selectedCountry.name
                }" :ref="el => setCountryRef(el, idx)" @mousedown.prevent @mouseenter="activeCountryIndex = idx"
                @click.stop="selectCountry(c)">
                <span class="country-name">{{ c.name.split('(')[0].trim() }}</span>
                <span class="country-code">{{ c.code }}</span>
              </button>
            </div>

            <div v-else class="no-results">No results</div>
          </div>
        </transition>
      </div>

      <!-- Phone Input -->
      <input :id="id" v-model="phoneNumber" type="text" class="phone-input form-control"
        :class="error ? 'is-invalid' : ''" :placeholder="placeholder" :required="isRequired" :disabled="disabled"
        @input="formatPhone" />
    </div>

    <div v-if="error" class="invalid-feedback d-block mt-1">
      {{ error }}
    </div>
  </div>
</template>

<script>
import ctd from 'country-telephone-data'
import 'flag-icons/css/flag-icons.min.css'

export default {
  name: 'PhoneInput',

  props: {
    id: { type: String, required: true },
    className: { type: String, default: 'w-100' },
    label: { type: String, default: 'Phone' },
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '771234567' },
    error: { type: String, default: '' },
    isRequired: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
  },

  data() {
    return {
      showCountry: false,
      countries: [],
      phoneCountryCode: '+94',
      countryRefs: [],
      phoneNumber: '',
      selectedCountry: {},
      countryQuery: '',
      activeCountryIndex: 0,
      countryPlacement: 'down',
      dropdownMaxHeight: 280,
    }
  },

  computed: {
    uniqueCountries() {
      return [...new Map(this.countries.map(c => [c.code + c.name, c])).values()]
    },

    filteredCountries() {
      const q = this.countryQuery.toLowerCase().trim()
      if (!q) return this.uniqueCountries

      return this.uniqueCountries.filter(c => {
        const name = c.name.split('(')[0].trim().toLowerCase()
        const code = c.code.toLowerCase()
        return name.includes(q) || code.includes(q)
      })
    }
  },

  watch: {
    modelValue: {
      immediate: true,
      handler(val) {
        if (!this.countries.length) return
        this.parsePhoneValue(val)
      }
    },
    showCountry(val) {
      if (val) {
        this.$nextTick(() => {
          this.recomputeCountryPlacement()
          this.$refs.countrySearch?.focus()

          this.scrollToSelectedCountry()
        })

        document.addEventListener('pointerdown', this.onOutsideClick, { capture: true })
      } else {
        document.removeEventListener('pointerdown', this.onOutsideClick, { capture: true })
        this.countryRefs = []
      }
    }
  },

  mounted() {
    this.initCountries()
  },

  methods: {
    initCountries() {
      this.countries = ctd.allCountries.map(c => ({
        name: c.name,
        code: `+${c.dialCode}`,
      }))

      const defaultCountry = this.countries.find(c => c.code === '+94') || this.countries[0]
      this.selectedCountry = defaultCountry
      this.phoneCountryCode = defaultCountry.code
      this.parsePhoneValue(this.modelValue)
    },

    onSearchInput() {
      this.showCountry = true
      this.activeCountryIndex = 0
    },

    setCountryRef(el, idx) {
      if (el) {
        this.countryRefs[idx] = el
      }
    },

    scrollToSelectedCountry() {
      const index = this.filteredCountries.findIndex(
        c =>
          c.code === this.selectedCountry.code &&
          c.name === this.selectedCountry.name
      )

      if (index !== -1) {
        this.activeCountryIndex = index

        this.$nextTick(() => {
          this.countryRefs[index]?.scrollIntoView({
            block: 'center',
            behavior: 'auto',
          })
        })
      }
    },

    parsePhoneValue(val) {
      if (!val) {
        this.phoneNumber = ''
        return
      }
      const found = this.uniqueCountries.find(c => val.startsWith(c.code))
      if (found) {
        this.selectedCountry = found
        this.phoneCountryCode = found.code
        this.phoneNumber = val.slice(found.code.length)
      } else {
        this.phoneNumber = val.replace(/^\+?\d{1,4}/, '')
      }
    },

    toggleCountry() {
      if (this.disabled) return
      this.showCountry = !this.showCountry
    },

    closeDropdown() {
      this.showCountry = false
      this.countryQuery = ''
    },

    onOutsideClick(e) {
      if (!this.$refs.countryRoot.contains(e.target)) this.closeDropdown()
    },

    selectCountry(c) {
      this.selectedCountry = c
      this.phoneCountryCode = c.code
      this.closeDropdown()
      this.updateValue()
    },

    formatPhone() {
      let value = this.phoneNumber.replace(/\D/g, '')
      if (value.startsWith('0')) value = value.slice(1)
      this.phoneNumber = value
      this.updateValue()
    },

    updateValue() {
      const full = (this.phoneCountryCode || '+94') + (this.phoneNumber || '')
      this.$emit('update:modelValue', full)
    },

    moveCountry(dir) {
      if (!this.filteredCountries.length) return
      this.activeCountryIndex =
        (this.activeCountryIndex + dir + this.filteredCountries.length) %
        this.filteredCountries.length
    },

    selectActiveCountry() {
      const c = this.filteredCountries[this.activeCountryIndex]
      if (c) this.selectCountry(c)
    },

    recomputeCountryPlacement() {
      const rect = this.$refs.countryRoot.getBoundingClientRect()
      const vh = window.innerHeight
      this.countryPlacement =
        vh - rect.bottom < this.dropdownMaxHeight && rect.top > 100 ? 'up' : 'down'
    }
  }
}
</script>

<style scoped>
.phone-row {
  display: flex;
  gap: 10px;
  width: 100%;
  align-items: center;
}

.country-display {
  height: 42px;
  min-width: 75px;
  border-radius: 12px;
  border: 1px solid #d1d5db;
  padding: 0 12px;
  font-size: 14px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
}

.country-display:hover {
  border-color: #9ca3af;
}

.country-display:focus {
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
  outline: none;
}

.bs-select-menu.country-list {
  position: absolute;
  left: 0;
  right: auto;
  width: 200px;
  z-index: 9999;
  background: #fff;
  border-radius: 14px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  padding: 6px;
  max-height: 300px;
  overflow-y: auto;
}

.menu-down {
  top: calc(100% + 8px);
}

.menu-up {
  bottom: calc(100% + 8px);
}

.options-list.country-options {
  max-height: 230px;
  overflow-y: auto;
  margin-top: 6px;
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
}

.options-list::-webkit-scrollbar-thumb:hover {
  background: #ff7a1f;
}

.country-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  font-size: 14px;
}

.country-name {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.country-code {
  color: #202020;
  font-size: 14px;
  flex-shrink: 0;
}

.phone-input {
  flex: 1;
  height: 42px;
  border-radius: 12px;
  border: 1px solid #d1d5db;
  padding: 0 14px;
  font-size: 14px;
  transition: all 0.2s;
}

input:focus {
  border-color: #b19268ad;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
  outline: none;
}

.dropdown-search {
  padding: 6px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.dropdown-search input {
  height: 36px;
  font-size: 0.85rem;
  border-radius: 8px;
  border: 1px solid #d1d5db;
}

.dropdown-item.active,
.country-item.active {
  background: linear-gradient(135deg, #ff7a36, #ff9f4d);
  color: #fff;
  border-radius: 10px;
  position: relative;
}

.country-item.active::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  pointer-events: none;
}

.country-item.selected {
  background: rgba(255, 122, 54, 0.12);
  border-radius: 10px;
  color: #ff7a36;
  font-weight: 600;
}

.country-item.selected .country-code {
  color: #ff7a36;
}

.country-item.active {
  background: linear-gradient(135deg, #ff7a36, #ff9f4d);
  color: #fff;
}

.country-item.active .country-code {
  color: #fff;
}

.no-results {
  padding: 12px 10px;
  text-align: center;
  font-size: 13px;
  color: #6b7280;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>