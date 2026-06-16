<template>
  <MultiVendorLayout>
    <div v-if="!car" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading vehicle details...</p>
    </div>

    <div v-else>
      <Banner :title="`${car.make} ${car.model}`" />

      <div class="content-container">
        <div class="product-content">
          <div class="left-column">
            <GalleryGrid :images="carImages" :mainImage="car.image" />
            
            <!-- Dealer Section -->
            <div class="dealer-section">
              <h3 class="section-subtitle">Dealer Information</h3>
              <div class="dealer-details">
                <div class="dealer-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  <div>
                    <span class="dealer-label">Dealer Name</span>
                    <span class="dealer-value">{{ car.dealerName || 'Premium Auto Sales' }}</span>
                  </div>
                </div>
                <div class="dealer-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                    <line x1="7" y1="2" x2="7" y2="22"></line>
                    <line x1="17" y1="2" x2="17" y2="22"></line>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <line x1="2" y1="7" x2="7" y2="7"></line>
                    <line x1="2" y1="17" x2="7" y2="17"></line>
                    <line x1="17" y1="17" x2="22" y2="17"></line>
                    <line x1="17" y1="7" x2="22" y2="7"></line>
                  </svg>
                  <div>
                    <span class="dealer-label">License</span>
                    <span class="dealer-value">{{ car.dealerLicense || 'DL-12345-6789' }}</span>
                  </div>
                </div>
                <div class="dealer-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <div>
                    <span class="dealer-label">Location</span>
                    <span class="dealer-value">{{ car.dealerLocation || 'Miami, FL' }}</span>
                  </div>
                </div>
                <div class="dealer-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8 10a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.574 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                  <div>
                    <span class="dealer-label">Phone</span>
                    <span class="dealer-value">{{ car.dealerPhone || '(555) 123-4567' }}</span>
                  </div>
                </div>
              </div>
              
              <!--Dealer Button -->
              <button class="btn-contact" @click="handleContactDealer">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8 10a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.574 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                Contact Dealer
              </button>
            </div>
          </div>

          <div class="right-column">
            <Product 
              :car="car"
              :hide-contact-button="true"
            />
          </div>
        </div>
      </div>
    </div>
  </MultiVendorLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import Banner from "./Partials/Banner.vue"
import GalleryGrid from "./Partials/GalleryGrid.vue"
import Product from "./Partials/product.vue"

const car = ref(null)
const additionalImages = [
  '/assets/images/card1.webp',
  '/assets/images/card2.webp',
  '/assets/images/card3.webp',
  '/assets/images/card4.webp'
]

onMounted(() => {
  const stored = sessionStorage.getItem('selectedCar')
  if (stored) {
    try {
      car.value = JSON.parse(stored)
      console.log('Car loaded:', car.value)
    } catch (e) {
      console.error('Error parsing car data:', e)
    }
  }
})

const carImages = computed(() => {
  if (!car.value) return []
  return [car.value.image, ...additionalImages].filter(Boolean)
})

const handleContactDealer = () => {
  alert(`Contact dealer about ${car.value.make} ${car.value.model}`)
}
</script>

<style scoped>
.content-container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 2rem;
}

.product-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-top: 2rem;
}

/* Left Column */
.left-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.dealer-section {
  background: linear-gradient(135deg, rgba(24, 59, 78, 0.03), rgba(39, 84, 138, 0.03));
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid rgba(221, 168, 83, 0.2);
}

.section-subtitle {
  font-size: 1.1rem;
  font-weight: 600;
  color: #183B4E;
  margin-bottom: 1rem;
  position: relative;
  padding-bottom: 0.5rem;
}

.section-subtitle::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 40px;
  height: 2px;
  background: linear-gradient(90deg, #DDA853, #C19A4F);
  border-radius: 2px;
}

.dealer-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.dealer-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.dealer-item svg {
  color: #DDA853;
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.dealer-item div {
  display: flex;
  flex-direction: column;
}

.dealer-label {
  font-size: 0.75rem;
  color: #27548A;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.dealer-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: #183B4E;
}

.btn-contact {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 1rem;
  border: none;
  border-radius: 50px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  color: #183B4E;
  margin-top: 0.5rem;
}

.btn-contact:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(221, 168, 83, 0.3);
}

/* Right Column */
.right-column {
  width: 100%;
}

.loading-state {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 3px solid #f3f3f3;
  border-top-color: #DDA853;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1024px) {
  .product-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}

@media (max-width: 768px) {
  .content-container {
    padding: 1rem;
  }
  
  .dealer-details {
    grid-template-columns: 1fr;
  }
}
</style>