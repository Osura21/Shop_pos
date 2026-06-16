<template>
  <MultiVendorLayout>
    <!-- Banner Section -->
    <Banner />

    <!-- Filter Section -->
    <div class="filter-section">
      <StockFilter @filter-change="handleFilterChange" />
    </div>

    <!-- Browse Cars -->
    <div class="browse-content">
      <div class="results-header">
        <div class="results-info">
          <h2 class="results-title">Available Vehicles</h2>
          <p class="results-count">{{ filteredCars.length }} cars found</p>
        </div>
        
        <!-- Sort Dropdown -->
        <div class="sort-dropdown">
          <label for="sort">Sort by:</label>
          <select 
            id="sort" 
            v-model="sortBy"
            class="sort-select"
            @change="sortCars"
          >
            <option value="default">Featured</option>
            <option value="price-asc">Price: Low to High</option>
            <option value="price-desc">Price: High to Low</option>
            <option value="year-desc">Newest First</option>
            <option value="year-asc">Oldest First</option>
          </select>
        </div>
      </div>

      <!-- Active Filters -->
      <div v-if="hasActiveFilters" class="active-filters-wrapper">
        <div class="active-filters">
          <span class="active-filters-label">Active Filters:</span>
          <div class="filter-tags">
            <div v-if="currentFilters.search" class="filter-tag">
              <span>Search: "{{ currentFilters.search }}"</span>
              <button @click="clearFilter('search')">
                <XIcon size="14" />
              </button>
            </div>
            <div v-if="currentFilters.make" class="filter-tag">
              <span>{{ currentFilters.make }}</span>
              <button @click="clearFilter('make')">
                <XIcon size="14" />
              </button>
            </div>
            <div v-if="currentFilters.model" class="filter-tag">
              <span>{{ currentFilters.model }}</span>
              <button @click="clearFilter('model')">
                <XIcon size="14" />
              </button>
            </div>
            <div v-if="currentFilters.year" class="filter-tag">
              <span>{{ currentFilters.year }}</span>
              <button @click="clearFilter('year')">
                <XIcon size="14" />
              </button>
            </div>
            <button v-if="hasActiveFilters" class="clear-all" @click="clearAllFilters">
              Clear All
            </button>
          </div>
        </div>
      </div>

      <!-- Car Grid -->
      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Finding the perfect vehicle for you...</p>
      </div>

      <div v-else-if="filteredCars.length === 0" class="no-results-state">
        <div class="no-results-icon">
          <CarIcon size="48" />
        </div>
        <h3>No vehicles found</h3>
        <p>Try adjusting your filters or browse our full inventory</p>
        <button @click="clearAllFilters" class="reset-filters-btn">
          Reset All Filters
        </button>
      </div>

      <div v-else class="cars-grid">
        <CarCard
          v-for="car in paginatedCars"
          :key="car.id"
          :car="car"
        />
      </div>

      <!-- Pagination -->
      <div v-if="filteredCars.length > 0" class="pagination">
        <button 
          class="pagination-btn"
          :disabled="currentPage === 1"
          @click="currentPage--"
        >
          <ChevronLeftIcon size="18" />
        </button>
        
        <div class="pagination-pages">
          <button
            v-for="page in displayedPages"
            :key="page"
            class="pagination-page"
            :class="{ 'active': currentPage === page }"
            @click="currentPage = page"
          >
            {{ page }}
          </button>
        </div>
        
        <button 
          class="pagination-btn"
          :disabled="currentPage === totalPages"
          @click="currentPage++"
        >
          <ChevronRightIcon size="18" />
        </button>
      </div>
    </div>
  </MultiVendorLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import Banner from "./Partials/Banner.vue"
import StockFilter from "./Partials/StockFilter.vue"
import CarCard from "./Partials/CarCard.vue"
import { XIcon, ChevronLeftIcon, ChevronRightIcon, CarIcon } from 'lucide-vue-next'

// ============ STATE ============
const loading = ref(false)
const currentFilters = ref({
  search: '',
  make: '',
  model: '',
  year: ''
})
const sortBy = ref('default')
const currentPage = ref(1)
const carsPerPage = 9

// ============ MOCK CAR DATA ============
const allCars = ref([
  {
    id: 1,
    make: 'Tesla',
    model: 'Model S',
    year: 2023,
    price: 129990,
    mileage: 8500,
    fuelType: 'Electric',
    image: '/assets/images/card3.webp',
    featured: true,
    transmission: 'Auto',
    color: 'White',
    condition: 'Pre-Owned'
  },
  {
    id: 2,
    make: 'BMW',
    model: 'X5 M',
    year: 2024,
    price: 105000,
    mileage: 3200,
    fuelType: 'Gasoline',
    image: '/assets/images/card5.png',
    featured: true,
    transmission: 'Auto',
    color: 'Black',
    condition: 'Pre-Owned'
  },
  {
    id: 3,
    make: 'Mercedes-Benz',
    model: 'E-Class',
    year: 2023,
    price: 85000,
    mileage: 12500,
    fuelType: 'Hybrid',
    image: '/assets/images/card4.webp',
    featured: true,
    transmission: 'Auto',
    color: 'Silver',
    condition: 'Brand-New'
  },
  {
    id: 4,
    make: 'Audi',
    model: 'RS7',
    year: 2024,
    price: 145000,
    mileage: 5600,
    fuelType: 'Gasoline',
    image: '/assets/images/card5.png',
    featured: false,
    transmission: 'Auto',
    color: 'Blue',
    condition: 'Pre-Owned'
  },
  {
    id: 5,
    make: 'Porsche',
    model: '911',
    year: 2024,
    price: 185000,
    mileage: 4200,
    fuelType: 'Gasoline',
    image: '/assets/images/card1.webp',
    featured: true,
    transmission: 'Auto',
    color: 'Red',
    condition: 'Brand-New'
  },
  {
    id: 6,
    make: 'Range Rover',
    model: 'Sport',
    year: 2022,
    price: 95000,
    mileage: 2800,
    fuelType: 'Diesel',
    image: '/assets/images/card2.webp',
    featured: false,
    transmission: 'Auto',
    color: 'Green',
    condition: 'Brand-New'
  }
])

// ============ COMPUTED ============
const hasActiveFilters = computed(() => {
  return currentFilters.value.search || 
         currentFilters.value.make || 
         currentFilters.value.model || 
         currentFilters.value.year
})

const filteredCars = computed(() => {
  let cars = [...allCars.value]

  if (currentFilters.value.search) {
    const searchTerm = currentFilters.value.search.toLowerCase()
    cars = cars.filter(car => 
      car.make.toLowerCase().includes(searchTerm) ||
      car.model.toLowerCase().includes(searchTerm)
    )
  }


  if (currentFilters.value.make) {
    cars = cars.filter(car => 
      car.make === currentFilters.value.make
    )
  }


  if (currentFilters.value.model) {
    cars = cars.filter(car => 
      car.model === currentFilters.value.model
    )
  }


  if (currentFilters.value.year) {
    if (currentFilters.value.year.includes('-')) {
      const [from, to] = currentFilters.value.year.split(' - ').map(Number)
      cars = cars.filter(car => car.year >= from && car.year <= to)
    } else if (currentFilters.value.year.includes('From')) {
      const from = parseInt(currentFilters.value.year.replace('From ', ''))
      cars = cars.filter(car => car.year >= from)
    } else if (currentFilters.value.year.includes('Until')) {
      const to = parseInt(currentFilters.value.year.replace('Until ', ''))
      cars = cars.filter(car => car.year <= to)
    }
  }

  // Apply sorting
  switch (sortBy.value) {
    case 'price-asc':
      cars.sort((a, b) => a.price - b.price)
      break
    case 'price-desc':
      cars.sort((a, b) => b.price - a.price)
      break
    case 'year-desc':
      cars.sort((a, b) => b.year - a.year)
      break
    case 'year-asc':
      cars.sort((a, b) => a.year - b.year)
      break
    default:
      
      cars.sort((a, b) => {
        if (a.featured && !b.featured) return -1
        if (!a.featured && b.featured) return 1
        return b.year - a.year
      })
  }

  return cars
})

const totalPages = computed(() => 
  Math.ceil(filteredCars.value.length / carsPerPage)
)

const paginatedCars = computed(() => {
  const start = (currentPage.value - 1) * carsPerPage
  const end = start + carsPerPage
  return filteredCars.value.slice(start, end)
})

const displayedPages = computed(() => {
  const pages = []
  const maxDisplayed = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxDisplayed / 2))
  let end = Math.min(totalPages.value, start + maxDisplayed - 1)
  
  if (end - start + 1 < maxDisplayed) {
    start = Math.max(1, end - maxDisplayed + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// ============ METHODS ============
const handleFilterChange = (filters) => {
  currentFilters.value = { ...filters }
  currentPage.value = 1 
  
  // Simulate loading
  loading.value = true
  setTimeout(() => {
    loading.value = false
  }, 500)
}

const clearFilter = (filterName) => {
  currentFilters.value[filterName] = ''
  currentPage.value = 1
}

const clearAllFilters = () => {
  currentFilters.value = {
    search: '',
    make: '',
    model: '',
    year: ''
  }
  currentPage.value = 1
}

const sortCars = () => {
  currentPage.value = 1
}

onMounted(() => {

})
</script>

<style scoped>
.browse-content {
  max-width: 1440px;
  margin: 0 auto;
  padding: 2rem;
}

/* Results Header */
.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.results-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.results-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #183B4E;
  margin: 0;
}

.results-count {
  font-size: 1rem;
  color: #27548A;
  font-weight: 500;
}

/* Sort Dropdown */
.sort-dropdown {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.sort-dropdown label {
  font-size: 0.9rem;
  color: #183B4E;
  font-weight: 500;
}

.sort-select {
  padding: 0.625rem 2rem 0.625rem 1rem;
  border: 1px solid rgba(221, 168, 83, 0.3);
  border-radius: 50px;
  font-size: 0.9rem;
  color: #183B4E;
  background: #FFFFFF;
  cursor: pointer;
  outline: none;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2327548A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  transition: all 0.3s ease;
}

.sort-select:hover {
  border-color: #DDA853;
}

.sort-select:focus {
  border-color: #DDA853;
  box-shadow: 0 0 0 2px rgba(221, 168, 83, 0.2);
}

/* Active Filters */
.active-filters-wrapper {
  margin-bottom: 2rem;
}

.active-filters {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.25rem;
  background: rgba(245, 238, 220, 0.5);
  border: 1px solid rgba(221, 168, 83, 0.2);
  border-radius: 50px;
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
  background: #FFFFFF;
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
  border-radius: 50%;
  transition: all 0.2s ease;
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

/* Cars Grid */
.cars-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

@media (min-width: 640px) {
  .cars-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 768px) {
  .cars-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1024px) {
  .cars-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  text-align: center;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 3px solid rgba(221, 168, 83, 0.2);
  border-top-color: #DDA853;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: #183B4E;
  font-size: 1.1rem;
  font-weight: 500;
}

/* No Results State */
.no-results-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  text-align: center;
  background: rgba(245, 238, 220, 0.3);
  border-radius: 20px;
}

.no-results-icon {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(221, 168, 83, 0.1);
  border-radius: 50%;
  margin-bottom: 1.5rem;
  color: #DDA853;
}

.no-results-state h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #183B4E;
  margin-bottom: 0.5rem;
}

.no-results-state p {
  color: #27548A;
  margin-bottom: 2rem;
}

.reset-filters-btn {
  padding: 0.75rem 2rem;
  background: linear-gradient(135deg, #183B4E, #27548A);
  border: none;
  border-radius: 50px;
  color: #FFFFFF;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.reset-filters-btn:hover {
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(221, 168, 83, 0.3);
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 3rem;
}

.pagination-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: 1px solid rgba(221, 168, 83, 0.3);
  border-radius: 50%;
  background: #FFFFFF;
  color: #183B4E;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #DDA853;
  background: rgba(221, 168, 83, 0.1);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-pages {
  display: flex;
  gap: 0.5rem;
}

.pagination-page {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  border-radius: 50%;
  background: transparent;
  color: #183B4E;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pagination-page:hover {
  border-color: #DDA853;
  background: rgba(221, 168, 83, 0.1);
}

.pagination-page.active {
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  color: #183B4E;
  font-weight: 700;
}

/* Filter Section */
.filter-section {
  position: relative;
  z-index: 40;
  margin-top: -2rem;
  margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
  .browse-content {
    padding: 1.5rem 1rem;
  }

  .results-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .sort-dropdown {
    width: 100%;
  }

  .sort-select {
    flex: 1;
  }

  .active-filters {
    flex-direction: column;
    align-items: flex-start;
    border-radius: 20px;
  }

  .filter-tags {
    width: 100%;
  }

  .pagination {
    flex-wrap: wrap;
  }
}
</style>