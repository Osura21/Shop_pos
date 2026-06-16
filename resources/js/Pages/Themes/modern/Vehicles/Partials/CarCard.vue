<!-- CarCard.vue -->
<template>
  <div class="car-card" :class="{ 'featured': car.featured }">
    <div class="car-image-container">
      <img 
        :src="car.image" 
        :alt="`${car.make} ${car.model}`"
        class="car-image"
        loading="lazy"
      />
      <span class="badge condition">{{car.condition}}</span>
      <div class="price-tag">${{ formatPrice(car.price) }}</div>
    </div>
    
    <div class="car-content">
      <div class="car-header">
        <h3 class="car-title">{{ car.make }} {{ car.model }}</h3>
        <span class="car-year">{{ car.year }}</span>
      </div>

      <div class="car-specs">
        <div class="spec-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          <span>{{ car.mileage.toLocaleString() }} mi</span>
        </div>
        <div class="spec-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3h18v18H3z"></path>
            <path d="M12 7v10"></path>
            <path d="M8 11h8"></path>
          </svg>
          <span>{{ car.fuelType }}</span>
        </div>
        <div class="spec-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 6v6l4 2"></path>
          </svg>
          <span>{{ car.transmission }}</span>
        </div>
      </div>
      
      <button @click="goToDetails" class="view-details-btn">
        View Details
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
  car: {
    type: Object,
    required: true
  }
})

const formatPrice = (price) => {
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

const goToDetails = () => {
  sessionStorage.setItem('selectedCar', JSON.stringify(props.car))

  router.visit('/product-view', {
    preserveState: true,
    replace: true
  })
}
</script>

<style scoped>
.car-card {
  background: #FFFFFF;
  border: 1px solid rgba(221, 168, 83, 0.2);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(24, 59, 78, 0.08);
  height: 100%;
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 100%;
}

.car-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(24, 59, 78, 0.12);
  border-color: #DDA853;
}

.car-card {
  border-left: 3px solid #DDA853;
}

.car-image-container {
  position: relative;
  height: 150px;
  overflow: hidden;
  flex-shrink: 0;
}

.car-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.car-card:hover .car-image {
  transform: scale(1.05);
}

.badge.condition {
  position: absolute;
  top: 10px;
  left: 10px;
  background: linear-gradient(90deg, #DDA853, #C19A4F);
  color: #183B4E;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  z-index: 2;
}

.price-tag {
  position: absolute;
  bottom: 10px;
  left: 10px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(4px);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 700;
  color: #183B4E;
  z-index: 2;
  border: 1px solid rgba(221, 168, 83, 0.2);
}

.car-content {
  padding: 1rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.car-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  gap: 0.5rem;
}

.car-title {
  font-size: 1rem;
  font-weight: 700;
  color: #183B4E;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  line-height: 1.3;
}

.car-year {
  display: inline-flex;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 600;
  color: #27548A;
  padding: 4px 10px;
  background: rgba(39, 84, 138, 0.08);
  border-radius: 20px;
  white-space: nowrap;
  flex-shrink: 0;
  line-height: 1;
}

.car-specs {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-top: 1px solid rgba(221, 168, 83, 0.15);
  border-bottom: 1px solid rgba(221, 168, 83, 0.15);
  gap: 0.5rem;
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #183B4E;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
  min-width: 0;
  flex: 1;
  justify-content: center;
}

.spec-item:first-child {
  justify-content: flex-start;
}

.spec-item:last-child {
  justify-content: flex-end;
}

.spec-item svg {
  color: #DDA853;
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.spec-item span {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
}

.view-details-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #183B4E, #27548A);
  color: #FFFFFF;
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100%;
  margin-top: 0.25rem;
  flex-shrink: 0;
}

.view-details-btn:hover {
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(221, 168, 83, 0.3);
}

.view-details-btn svg {
  transition: transform 0.3s ease;
  width: 14px;
  height: 14px;
}

.view-details-btn:hover svg {
  transform: translateX(4px);
}

/* Responsive */
@media (max-width: 1024px) {
  .car-image-container {
    height: 130px;
  }
  
  .car-title {
    font-size: 0.95rem;
  }
  
  .spec-item {
    font-size: 0.7rem;
  }
}

@media (max-width: 768px) {
  .car-content {
    padding: 0.875rem;
  }
  
  .car-specs {
    padding: 0.625rem 0;
  }
}

@media (max-width: 640px) {
  .car-image-container {
    height: 140px;
  }
  
  .car-content {
    padding: 0.75rem;
  }
  
  .car-title {
    font-size: 0.9rem;
  }
  
  .car-year {
    font-size: 0.7rem;
    padding: 3px 8px;
  }
  
  .car-specs {
    gap: 0.25rem;
  }
  
  .spec-item {
    font-size: 0.65rem;
    gap: 0.25rem;
  }
  
  .spec-item svg {
    width: 12px;
    height: 12px;
  }
}
</style>