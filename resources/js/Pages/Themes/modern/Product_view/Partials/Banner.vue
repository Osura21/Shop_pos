<template>
  <div class="product-banner" :style="{ backgroundImage: `url(${bannerImage})` }">
    <!-- Overlay for better text readability -->
    <div class="banner-overlay"></div>
    
    <!-- Content positioned at bottom -->
    <div class="banner-content">
      <h1 class="banner-title">{{ title }}</h1>
      <nav class="breadcrumb">
        <Link href="/" class="breadcrumb-link">Home</Link>
        <span class="breadcrumb-separator">/</span>
        <Link href="/vehicles" class="breadcrumb-link">Browse Cars</Link>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-current">{{ title }}</span>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  imageUrl: {
    type: String,
    default: '/multivendor/post-ad2.webp' 
  }
})

// Use provided image or default
const bannerImage = computed(() => {
  return props.imageUrl || '/multivendor/post-ad2.webp'
})
</script>

<style scoped>
.product-banner {
  position: relative;
  height: 500px; 
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  align-items: flex-end; 
  padding: 0;
}

.banner-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to right,
    rgba(24, 59, 78, 0.9) 0%,
    rgba(39, 84, 138, 0.7) 50%,
    rgba(24, 59, 78, 0.9) 100%
  );
}

.banner-content {
  position: relative;
  z-index: 2;
  max-width: 1440px;
  width: 100%;
  margin: 0 auto;
  padding: 2rem;
  color: white;
  animation: slideUp 0.5s ease-out;
}

.banner-title {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
  line-height: 1.2;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  font-weight: 500;
  padding: 0.5rem 0;
}

.breadcrumb-link {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  transition: all 0.3s ease;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.breadcrumb-link:hover {
  color: #DDA853;
  background: rgba(255, 255, 255, 0.1);
}

.breadcrumb-separator {
  color: rgba(255, 255, 255, 0.5);
  font-size: 1.2rem;
  line-height: 1;
}

.breadcrumb-current {
  color: #DDA853;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  background: rgba(221, 168, 83, 0.15);
  border-radius: 4px;
}

/* Optional: Add a decorative element */
.breadcrumb-current::before {
  content: '';
  display: inline-block;
  width: 4px;
  height: 16px;
  background: #DDA853;
  margin-right: 8px;
  vertical-align: middle;
  border-radius: 2px;
}

/* Animation */
@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Responsive styles */
@media (max-width: 1024px) {
  .product-banner {
    height: 350px;
  }
  
  .banner-title {
    font-size: 2.5rem;
  }
}

@media (max-width: 768px) {
  .product-banner {
    height: 300px;
  }
  
  .banner-content {
    padding: 1.5rem;
  }
  
  .banner-title {
    font-size: 2rem;
  }
  
  .breadcrumb {
    font-size: 0.9rem;
    flex-wrap: wrap;
  }
}

@media (max-width: 480px) {
  .product-banner {
    height: 250px;
  }
  
  .banner-title {
    font-size: 1.5rem;
  }
  
  .breadcrumb {
    font-size: 0.8rem;
  }
}
</style>