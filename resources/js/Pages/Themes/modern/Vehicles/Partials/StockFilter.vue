<template>
  <div class="filter-wrapper" :class="{ 'filter-sticky': isSticky }" ref="filterRef">
    <div class="filter-container">
      <!-- Main Filter Bar -->
      <div class="filter-bar">
        <!-- Make Dropdown -->
        <div class="filter-group" :class="{ 'active': activeDropdown === 'make' }">
          <button 
            class="filter-trigger"
            @click="toggleDropdown('make')"
          >
            <span class="trigger-label">Make</span>
            <span class="trigger-value">{{ selectedMake || 'All Brands' }}</span>
            <ChevronDownIcon 
              size="18" 
              class="trigger-icon"
              :class="{ 'rotate': activeDropdown === 'make' }"
            />
          </button>
          
          <transition name="fade-dropdown">
            <div v-if="activeDropdown === 'make'" class="dropdown-menu">
              <div class="dropdown-header">
                <span class="dropdown-title">Select Make</span>
                <button @click="activeDropdown = null" class="close-dropdown">
                  <XIcon size="16" />
                </button>
              </div>
              <div class="dropdown-search">
                <SearchIcon size="16" />
                <input 
                  type="text" 
                  v-model="makeSearch" 
                  placeholder="Search brand..."
                  class="dropdown-search-input"
                />
              </div>
              <div class="dropdown-options">
                <button
                  v-for="make in filteredMakes"
                  :key="make"
                  class="dropdown-option"
                  :class="{ 'selected': selectedMake === make }"
                  @click="selectMake(make)"
                >
                  <span>{{ make }}</span>
                  <CheckIcon v-if="selectedMake === make" size="16" />
                </button>
                <div v-if="filteredMakes.length === 0" class="no-results">
                  No brands found
                </div>
              </div>
            </div>
          </transition>
        </div>

        <!-- Model Dropdown -->
        <div class="filter-group" :class="{ 'active': activeDropdown === 'model'}">
          <button 
            class="filter-trigger"
            @click="selectedMake ? toggleDropdown('model') : null"
            :disabled="!selectedMake"
          >
            <span class="trigger-label">Model</span>
            <span class="trigger-value">{{ selectedModel || 'All Models' }}</span>
            <ChevronDownIcon 
              size="18" 
              class="trigger-icon"
              :class="{ 'rotate': activeDropdown === 'model' }"
            />
          </button>
          
          <transition name="fade-dropdown">
            <div v-if="activeDropdown === 'model'" class="dropdown-menu">
              <div class="dropdown-header">
                <span class="dropdown-title">Select Model</span>
                <button @click="activeDropdown = null" class="close-dropdown">
                  <XIcon size="16" />
                </button>
              </div>
              <div class="dropdown-search">
                <SearchIcon size="16" />
                <input 
                  type="text" 
                  v-model="modelSearch" 
                  placeholder="Search model..."
                  class="dropdown-search-input"
                />
              </div>
              <div class="dropdown-options">
                <button
                  v-for="model in filteredModels"
                  :key="model"
                  class="dropdown-option"
                  :class="{ 'selected': selectedModel === model }"
                  @click="selectModel(model)"
                >
                  <span>{{ model }}</span>
                  <CheckIcon v-if="selectedModel === model" size="16" />
                </button>
                <div v-if="filteredModels.length === 0" class="no-results">
                  No models found
                </div>
              </div>
            </div>
          </transition>
        </div>

        <!-- Year Dropdown -->
        <div class="filter-group" :class="{ 'active': activeDropdown === 'year' }">
          <button 
            class="filter-trigger"
            @click="toggleDropdown('year')"
          >
            <span class="trigger-label">Year</span>
            <span class="trigger-value">{{ selectedYear || 'Any Year' }}</span>
            <ChevronDownIcon 
              size="18" 
              class="trigger-icon"
              :class="{ 'rotate': activeDropdown === 'year' }"
            />
          </button>
          
          <transition name="fade-dropdown">
            <div v-if="activeDropdown === 'year'" class="dropdown-menu year-menu">
              <div class="dropdown-header">
                <span class="dropdown-title">Select Year</span>
                <button @click="activeDropdown = null" class="close-dropdown">
                  <XIcon size="16" />
                </button>
              </div>
              <div class="year-range">
                <div class="year-input-group">
                  <label>From</label>
                  <select v-model="yearFrom" class="year-select">
                    <option value="">Any</option>
                    <option v-for="year in years" :key="`from-${year}`" :value="year">
                      {{ year }}
                    </option>
                  </select>
                </div>
                <div class="year-input-group">
                  <label>To</label>
                  <select v-model="yearTo" class="year-select">
                    <option value="">Any</option>
                    <option v-for="year in years" :key="`to-${year}`" :value="year">
                      {{ year }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="dropdown-actions">
                <button class="btn-clear" @click="clearYear">Clear</button>
                <button class="btn-apply" @click="applyYear">Apply</button>
              </div>
            </div>
          </transition>
        </div>

        <!-- Search Button -->
        <button class="filter-submit" @click="applyFilters">
          <SearchIcon size="18" />
          <span>Search</span>
        </button>
      </div>

      <!-- Active Filters -->
      <transition name="slide-fade">
        <div v-if="hasActiveFilters" class="active-filters">
          <span class="active-filters-label">Active Filters:</span>
          <div class="filter-tags">
            <div v-if="selectedMake" class="filter-tag">
              <span>{{ selectedMake }}</span>
              <button @click="clearMake">
                <XIcon size="14" />
              </button>
            </div>
            <div v-if="selectedModel" class="filter-tag">
              <span>{{ selectedModel }}</span>
              <button @click="clearModel">
                <XIcon size="14" />
              </button>
            </div>
            <div v-if="selectedYear" class="filter-tag">
              <span>{{ selectedYear }}</span>
              <button @click="clearYear">
                <XIcon size="14" />
              </button>
            </div>
            <button v-if="hasActiveFilters" class="clear-all" @click="clearAllFilters">
              Clear All
            </button>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { SearchIcon, ChevronDownIcon, XIcon, CheckIcon } from 'lucide-vue-next'

// Props
const props = defineProps({
  initialFilters: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['filter-change'])

// ============ STATE ============
const isSticky = ref(false)
const filterRef = ref(null)
const activeDropdown = ref(null)
const makeSearch = ref('')
const modelSearch = ref('')
const yearFrom = ref('')
const yearTo = ref('')

// Filter values
const filters = ref({
  search: '',
  make: '',
  model: '',
  year: ''
})

const selectedMake = computed(() => filters.value.make)
const selectedModel = computed(() => filters.value.model)
const selectedYear = computed(() => filters.value.year)

// ============ MOCK DATA ============
const makes = [
  'Tesla', 'BMW', 'Mercedes-Benz', 'Audi', 'Porsche', 
  'Lexus', 'Range Rover', 'Ferrari', 'Lamborghini', 'Bentley'
]

const modelsByMake = {
  'Tesla': ['Model S', 'Model 3', 'Model X', 'Model Y', 'Cybertruck'],
  'BMW': ['X5 M', 'X7', 'M3', 'M4', 'M5', 'i8', '7 Series'],
  'Mercedes-Benz': ['E-Class', 'S-Class', 'GLE', 'GLS', 'AMG GT'],
  'Audi': ['RS7', 'R8', 'Q7', 'Q8', 'e-tron'],
  'Porsche': ['911', 'Cayenne', 'Panamera', 'Taycan', 'Macan'],
  'Lexus': ['RX', 'NX', 'ES', 'LS', 'LC'],
  'Range Rover': ['Sport', 'Velar', 'Evoque', 'Autobiography'],
  'Ferrari': ['F8 Tributo', 'SF90', 'Roma', 'Portofino'],
  'Lamborghini': ['Huracán', 'Urus', 'Aventador'],
  'Bentley': ['Continental GT', 'Bentayga', 'Flying Spur']
}

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 30 }, (_, i) => currentYear - i)

// ============ COMPUTED ============
const filteredMakes = computed(() => {
  if (!makeSearch.value) return makes
  return makes.filter(make => 
    make.toLowerCase().includes(makeSearch.value.toLowerCase())
  )
})

const filteredModels = computed(() => {
  if (!selectedMake.value) return []
  const models = modelsByMake[selectedMake.value] || []
  if (!modelSearch.value) return models
  return models.filter(model =>
    model.toLowerCase().includes(modelSearch.value.toLowerCase())
  )
})

const hasActiveFilters = computed(() => {
  return filters.value.search || 
         filters.value.make || 
         filters.value.model || 
         filters.value.year
})

// ============ METHODS ============
const toggleDropdown = (dropdown) => {
  activeDropdown.value = activeDropdown.value === dropdown ? null : dropdown
}

const selectMake = (make) => {
  filters.value.make = make
  filters.value.model = '' 
  activeDropdown.value = null
  makeSearch.value = ''
  emitFilters()
}

const selectModel = (model) => {
  filters.value.model = model
  activeDropdown.value = null
  modelSearch.value = ''
  emitFilters()
}

const applyYear = () => {
  if (yearFrom.value && yearTo.value) {
    filters.value.year = `${yearFrom.value} - ${yearTo.value}`
  } else if (yearFrom.value) {
    filters.value.year = `From ${yearFrom.value}`
  } else if (yearTo.value) {
    filters.value.year = `Until ${yearTo.value}`
  }
  activeDropdown.value = null
  emitFilters()
}

const clearMake = () => {
  filters.value.make = ''
  filters.value.model = '' 
  emitFilters()
}

const clearModel = () => {
  filters.value.model = ''
  emitFilters()
}

const clearYear = () => {
  filters.value.year = ''
  yearFrom.value = ''
  yearTo.value = ''
  emitFilters()
}

const clearAllFilters = () => {
  filters.value = {
    search: '',
    make: '',
    model: '',
    year: ''
  }
  yearFrom.value = ''
  yearTo.value = ''
  makeSearch.value = ''
  modelSearch.value = ''
  emitFilters()
}

const applyFilters = () => {
  activeDropdown.value = null
  emitFilters()
}

const emitFilters = () => {
  emit('filter-change', { ...filters.value })
}

// ============ STICKY FILTER ============
const handleScroll = () => {
  if (filterRef.value) {
    const rect = filterRef.value.getBoundingClientRect()
    isSticky.value = rect.top <= 20
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  handleScroll()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})


onMounted(() => {
  if (props.initialFilters) {
    filters.value = { ...filters.value, ...props.initialFilters }
  }
})
</script>

<style scoped>
/* Filter Wrapper */
.filter-wrapper {
  position: relative;
  z-index: 50;
  width: 100%;
  transition: all 0.3s ease;
}

.filter-wrapper.filter-sticky {
  position: sticky;
  top: 80px;
  z-index: 100;
}

.filter-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Filter Bar */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(221, 168, 83, 0.2);
  border-radius: 100px;
  padding: 0.5rem;
  box-shadow: 0 10px 30px rgba(24, 59, 78, 0.15);
  transform: translateY(-50%);
  margin-bottom: -2rem;
  margin-top: 2rem;
  transition: all 0.3s ease;
}

.filter-sticky .filter-bar {
  transform: translateY(0);
  box-shadow: 0 15px 40px rgba(24, 59, 78, 0.2);
  border-color: rgba(221, 168, 83, 0.4);
}

/* Filter Group */
.filter-group {
  position: relative;
  flex: 1;
}

.search-group {
  flex: 1.5;
  display: flex;
  align-items: center;
  background: rgba(245, 238, 220, 0.3);
  border-radius: 100px;
  padding: 0 0.5rem;
  transition: all 0.3s ease;
}

.search-group:focus-within {
  background: #FFFFFF;
  box-shadow: 0 0 0 2px rgba(221, 168, 83, 0.3);
}

.filter-icon {
  display: flex;
  align-items: center;
  padding-left: 0.75rem;
  color: #183B4E;
}

.filter-input {
  width: 100%;
  padding: 0.875rem 0.5rem;
  border: none;
  background: transparent;
  font-size: 0.95rem;
  color: #183B4E;
  outline: none;
}

.filter-input::placeholder {
  color: #8AA4BE;
}

.clear-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  margin-right: 0.5rem;
  border: none;
  background: rgba(24, 59, 78, 0.1);
  border-radius: 50%;
  color: #183B4E;
  cursor: pointer;
  transition: all 0.3s ease;
}

.clear-btn:hover {
  background: rgba(24, 59, 78, 0.2);
}

.filter-trigger {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  width: 100%;
  padding: 0.625rem 1rem;
  background: transparent;
  border: none;
  border-radius: 100px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.filter-trigger:hover {
  background: rgba(221, 168, 83, 0.08);
}

.filter-group.active .filter-trigger {
  background: rgba(221, 168, 83, 0.12);
  box-shadow: inset 0 0 0 2px rgba(221, 168, 83, 0.3);
}

.trigger-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #27548A;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.125rem;
}

.trigger-value {
  font-size: 0.95rem;
  font-weight: 600;
  color: #183B4E;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.trigger-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #27548A;
  transition: transform 0.3s ease;
}

.trigger-icon.rotate {
  transform: translateY(-50%) rotate(180deg);
}

.filter-group.disabled {
  opacity: 0.5;
  pointer-events: none;
}

.filter-submit {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 2rem;
  background: linear-gradient(135deg, #183B4E, #27548A);
  border: none;
  border-radius: 100px;
  color: #FFFFFF;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.filter-submit:hover {
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  transform: scale(1.02);
  box-shadow: 0 10px 25px rgba(221, 168, 83, 0.4);
}

.filter-submit svg {
  transition: transform 0.3s ease;
}

.filter-submit:hover svg {
  transform: scale(1.1);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 0;
  width: 320px;
  background: #FFFFFF;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(24, 59, 78, 0.2);
  border: 1px solid rgba(221, 168, 83, 0.2);
  overflow: hidden;
  z-index: 1000;
}

.year-menu {
  width: 280px;
}

.dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(221, 168, 83, 0.2);
}

.dropdown-title {
  font-weight: 700;
  color: #183B4E;
  font-size: 0.95rem;
}

.close-dropdown {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border: none;
  background: rgba(24, 59, 78, 0.1);
  border-radius: 50%;
  color: #183B4E;
  cursor: pointer;
  transition: all 0.3s ease;
}

.close-dropdown:hover {
  background: rgba(24, 59, 78, 0.2);
}

.dropdown-search {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: rgba(245, 238, 220, 0.3);
  margin: 1rem;
  border-radius: 12px;
}

.dropdown-search svg {
  color: #27548A;
}

.dropdown-search-input {
  width: 100%;
  border: none;
  background: transparent;
  font-size: 0.9rem;
  color: #183B4E;
  outline: none;
}

.dropdown-search-input::placeholder {
  color: #8AA4BE;
}

.dropdown-options {
  max-height: 280px;
  overflow-y: auto;
  padding: 0.5rem 1rem 1rem;
}

.dropdown-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.625rem 1rem;
  border: none;
  background: transparent;
  border-radius: 10px;
  color: #183B4E;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dropdown-option:hover {
  background: rgba(221, 168, 83, 0.1);
  color: #DDA853;
}

.dropdown-option.selected {
  background: rgba(221, 168, 83, 0.15);
  color: #DDA853;
  font-weight: 600;
}

.dropdown-option.selected svg {
  color: #DDA853;
}

.no-results {
  padding: 1.5rem;
  text-align: center;
  color: #8AA4BE;
  font-size: 0.9rem;
}

.year-range {
  padding: 1.25rem;
  display: flex;
  gap: 1rem;
}

.year-input-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.year-input-group label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #27548A;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.year-select {
  padding: 0.625rem;
  border: 1px solid rgba(221, 168, 83, 0.2);
  border-radius: 10px;
  font-size: 0.9rem;
  color: #183B4E;
  background: #FFFFFF;
  outline: none;
  transition: all 0.3s ease;
}

.year-select:focus {
  border-color: #DDA853;
  box-shadow: 0 0 0 2px rgba(221, 168, 83, 0.2);
}

.dropdown-actions {
  display: flex;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(221, 168, 83, 0.2);
}

.btn-clear {
  flex: 1;
  padding: 0.625rem;
  border: 1px solid rgba(24, 59, 78, 0.2);
  border-radius: 10px;
  background: transparent;
  color: #183B4E;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-clear:hover {
  background: rgba(24, 59, 78, 0.05);
}

.btn-apply {
  flex: 1;
  padding: 0.625rem;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #183B4E, #27548A);
  color: #FFFFFF;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-apply:hover {
  background: linear-gradient(135deg, #DDA853, #C19A4F);
}

.active-filters {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
  padding: 0.75rem 1.25rem;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(221, 168, 83, 0.2);
  border-radius: 50px;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.active-filters-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #27548A;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.filter-tag {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: rgba(221, 168, 83, 0.1);
  border: 1px solid rgba(221, 168, 83, 0.3);
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #183B4E;
}

.filter-tag button {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.125rem;
  border: none;
  background: transparent;
  color: #27548A;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 50%;
}

.filter-tag button:hover {
  background: rgba(221, 168, 83, 0.3);
  color: #DDA853;
}

.clear-all {
  padding: 0.375rem 0.75rem;
  border: none;
  background: transparent;
  color: #27548A;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
  transition: color 0.3s ease;
}

.clear-all:hover {
  color: #DDA853;
}

/* Animations */
.fade-dropdown-enter-active,
.fade-dropdown-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-dropdown-enter-from,
.fade-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Scrollbar Styling */
.dropdown-options::-webkit-scrollbar {
  width: 6px;
}

.dropdown-options::-webkit-scrollbar-track {
  background: rgba(245, 238, 220, 0.3);
  border-radius: 10px;
}

.dropdown-options::-webkit-scrollbar-thumb {
  background: rgba(221, 168, 83, 0.4);
  border-radius: 10px;
}

.dropdown-options::-webkit-scrollbar-thumb:hover {
  background: rgba(221, 168, 83, 0.6);
}

/* Responsive */
@media (max-width: 1024px) {
  .filter-bar {
    flex-direction: column;
    border-radius: 24px;
    padding: 1rem;
    transform: translateY(-10%);
  }

  .filter-group {
    width: 100%;
  }

  .search-group {
    width: 100%;
  }

  .filter-submit {
    width: 100%;
    justify-content: center;
  }

  .dropdown-menu {
    position: fixed;
    top: auto;
    left: 1rem;
    right: 1rem;
    width: auto;
    max-width: 400px;
    margin: 0 auto;
  }
}

@media (max-width: 768px) {
  .filter-container {
    padding: 0 1rem;
  }

  .active-filters {
    flex-direction: column;
    align-items: flex-start;
    border-radius: 20px;
  }

  .filter-tags {
    width: 100%;
  }

  .clear-all {
    width: 100%;
    text-align: center;
    padding: 0.5rem;
    border-top: 1px solid rgba(221, 168, 83, 0.2);
  }
}
</style>