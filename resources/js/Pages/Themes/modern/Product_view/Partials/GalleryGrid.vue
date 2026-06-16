
<template>
  <div class="gallery-grid">
    <!-- Main Image -->
    <div class="main-image-container">
      <img 
        :src="mainImage" 
        alt="Main vehicle image"
        class="main-image"
        @click="openLightbox(0)"
      />
      <button class="expand-btn" @click="openLightbox(0)">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 3 21 3 21 9"></polyline>
          <polyline points="9 21 3 21 3 15"></polyline>
          <line x1="21" y1="3" x2="14" y2="10"></line>
          <line x1="3" y1="21" x2="10" y2="14"></line>
        </svg>
      </button>
    </div>

    <!-- Thumbnail Grid -->
    <div class="thumbnail-grid">
      <div 
        v-for="(image, index) in images" 
        :key="index"
        class="thumbnail-item"
        :class="{ 'active': selectedImage === image }"
        @click="selectImage(image)"
      >
        <img :src="image" :alt="`Vehicle image ${index + 1}`" />
      </div>
    </div>

    <!-- Lightbox Modal -->
    <div v-if="lightboxOpen" class="lightbox" @click="closeLightbox">
      <div class="lightbox-content" @click.stop>
        <button class="lightbox-close" @click="closeLightbox">×</button>
        <button class="lightbox-nav prev" @click="prevImage">‹</button>
        <img :src="images[currentLightboxIndex]" alt="Lightbox image" />
        <button class="lightbox-nav next" @click="nextImage">›</button>
        <div class="lightbox-counter">{{ currentLightboxIndex + 1 }} / {{ images.length }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  images: {
    type: Array,
    required: true
  },
  mainImage: {
    type: String,
    required: true
  }
})

const selectedImage = ref(props.mainImage)
const lightboxOpen = ref(false)
const currentLightboxIndex = ref(0)

const selectImage = (image) => {
  selectedImage.value = image
}

const openLightbox = (index) => {
  currentLightboxIndex.value = index
  lightboxOpen.value = true
  document.body.style.overflow = 'hidden'
}

const closeLightbox = () => {
  lightboxOpen.value = false
  document.body.style.overflow = 'auto'
}

const prevImage = () => {
  currentLightboxIndex.value = (currentLightboxIndex.value - 1 + props.images.length) % props.images.length
}

const nextImage = () => {
  currentLightboxIndex.value = (currentLightboxIndex.value + 1) % props.images.length
}
</script>

<style scoped>
.gallery-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.main-image-container {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  aspect-ratio: 16/9;
  cursor: pointer;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.main-image-container:hover .main-image {
  transform: scale(1.05);
}

.expand-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #183B4E;
  transition: all 0.3s ease;
  z-index: 2;
}

.expand-btn:hover {
  background: #DDA853;
  color: #FFFFFF;
  transform: scale(1.1);
}

.thumbnail-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.thumbnail-item {
  aspect-ratio: 4/3;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.thumbnail-item.active {
  border-color: #DDA853;
  transform: scale(1.05);
}

.thumbnail-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.thumbnail-item:hover img {
  transform: scale(1.1);
}

/* Lightbox */
.lightbox {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.95);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lightbox-content {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
}

.lightbox-content img {
  max-width: 100%;
  max-height: 90vh;
  object-fit: contain;
}

.lightbox-close {
  position: absolute;
  top: -40px;
  right: 0;
  background: none;
  border: none;
  color: #FFFFFF;
  font-size: 40px;
  cursor: pointer;
  z-index: 1001;
}

.lightbox-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: #FFFFFF;
  font-size: 40px;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.lightbox-nav:hover {
  background: #DDA853;
}

.lightbox-nav.prev {
  left: 20px;
}

.lightbox-nav.next {
  right: 20px;
}

.lightbox-counter {
  position: absolute;
  bottom: -40px;
  left: 50%;
  transform: translateX(-50%);
  color: #FFFFFF;
  font-size: 1rem;
}

@media (max-width: 768px) {
  .thumbnail-grid {
    gap: 0.5rem;
  }
  
  .lightbox-nav {
    width: 40px;
    height: 40px;
    font-size: 30px;
  }
}
</style>