
<template>
  <div class="product-details">
    <!-- Header -->
    <div class="product-header">
      <h1 class="product-title">{{ car.make }} {{ car.model }}</h1>
      <div class="product-badges">
        <span class="badge year">{{ car.year }}</span>
        <span class="badge condition">{{car.condition}}</span>
      </div>
    </div>

    <!-- Price Section -->
      <div class="current-price">${{ formatPrice(car.price) }}</div>

    <!-- Key Specs -->
    <div class="key-specs">
      <div class="spec-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        <div class="spec-content">
          <span class="spec-label">Mileage</span>
          <span class="spec-value">{{ formatNumber(car.mileage) }} mi</span>
        </div>
      </div>

      <div class="spec-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 3h18v18H3z"></path>
          <path d="M12 7v10"></path>
          <path d="M8 11h8"></path>
        </svg>
        <div class="spec-content">
          <span class="spec-label">Fuel Type</span>
          <span class="spec-value">{{ car.fuelType }}</span>
        </div>
      </div>

      <div class="spec-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M12 6v6l4 2"></path>
        </svg>
        <div class="spec-content">
          <span class="spec-label">Transmission</span>
          <span class="spec-value">{{ car.transmission }}</span>
        </div>
      </div>

      <div class="spec-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
        <div class="spec-content">
          <span class="spec-label">Color</span>
          <span class="spec-value">{{ car.color }}</span>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div class="features-section">
      <h2 class="section-title">Key Features</h2>
      <div class="features-grid">
        <div v-for="feature in features" :key="feature" class="feature-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>{{ feature }}</span>
        </div>
      </div>
    </div>

    <!-- Vehicle Description -->
    <div class="description-section">
      <h2 class="section-title">Vehicle Description</h2>
      <p class="description-text">
        This {{ car.year }} {{ car.make }} {{ car.model }} is in excellent condition and comes fully loaded with premium features. 
        The {{ car.color.toLowerCase() }} exterior is complemented by a luxurious interior. 
        With only {{ formatNumber(car.mileage) }} miles, this vehicle has been well-maintained and is ready for its next owner.
        The {{ car.transmission.toLowerCase() }} transmission provides a smooth driving experience, while the {{ car.fuelType.toLowerCase() }} 
        engine delivers impressive performance and efficiency.
      </p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  car: {
    type: Object,
    required: true
  }
})

defineEmits(['contact-dealer', ])

const formatPrice = (price) => {
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

const formatNumber = (num) => {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}


const features = [
  'Leather Seats',
  'Sunroof/Moonroof',
  'Navigation System',
  'Bluetooth Connectivity',
  'Backup Camera',
  'Heated Seats',
  'Apple CarPlay/Android Auto',
  'Blind Spot Monitoring',
  'Lane Departure Warning',
  'Keyless Entry',
  'Premium Sound System',
  'Dual Zone Climate Control'
]
</script>

<style scoped>
.product-details {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.product-title {
  font-size: 2rem;
  font-weight: 700;
  color: #183B4E;
  margin: 0;
}

.product-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge {
  padding: 0.375rem 1rem;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge.year {
  background: rgba(221, 168, 83, 0.15);
  color: #183B4E;
  border: 1px solid rgba(221, 168, 83, 0.3);
}

.badge.condition {
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  color: #183B4E;
}

.current-price {
  font-size: 2.5rem;
  font-weight: 700;
  color: #DDA853;
  line-height: 1.2;
  margin-bottom: 0.5rem;
}

.price-details {
  font-size: 0.95rem;
  color: #27548A;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.calculate-link {
  background: none;
  border: none;
  color: #DDA853;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
  padding: 0;
}

.key-specs {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  padding: 1.5rem 0;
  border-top: 1px solid rgba(221, 168, 83, 0.2);
  border-bottom: 1px solid rgba(221, 168, 83, 0.2);
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.spec-item svg {
  color: #DDA853;
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.spec-content {
  display: flex;
  flex-direction: column;
}

.spec-label {
  font-size: 0.8rem;
  color: #27548A;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.spec-value {
  font-size: 1rem;
  font-weight: 600;
  color: #183B4E;
}

.btn-secondary {
  background: #FFFFFF;
  color: #183B4E;
  border: 2px solid rgba(221, 168, 83, 0.3);
}

.btn-secondary:hover {
  border-color: #DDA853;
  background: rgba(221, 168, 83, 0.05);
  transform: translateY(-2px);
}

.features-section, .description-section {
  padding-top: 1rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #183B4E;
  margin-bottom: 1rem;
  position: relative;
  padding-bottom: 0.5rem;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 60px;
  height: 3px;
  background: linear-gradient(90deg, #DDA853, #C19A4F);
  border-radius: 3px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
  color: #183B4E;
}

.feature-item svg {
  color: #DDA853;
  flex-shrink: 0;
}

.description-text {
  font-size: 1rem;
  line-height: 1.6;
  color: #27548A;
  margin: 0;
}

@media (max-width: 768px) {
  .product-title {
    font-size: 1.5rem;
  }
  
  .current-price {
    font-size: 2rem;
  }
  
  .key-specs {
    grid-template-rows: 1fr;
  }
  
  .action-buttons {
    grid-template-columns: 1fr;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>