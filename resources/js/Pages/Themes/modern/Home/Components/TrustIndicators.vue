<template>
  <section class="testimonials-section">
    <div class="container">
      
      <!-- HEADER -->
      <div class="header">
        <h2 class="title">Customer Experiences</h2>
        <p class="subtitle">Real feedback from our valued clients</p>
      </div>

      <!-- TESTIMONIALS GRID -->
      <div class="testimonials-grid">

        <div class="testimonial-card">
          <div class="card-content">
            <div class="quote-icon">"</div>
            <p class="testimonial-text">
              Exceptional service! My Tesla purchase was smooth and hassle-free.
            </p>
            <div class="rating">
              <div class="stars">
                <span v-for="n in 5" :key="n" class="star">★</span>
              </div>
              <span class="rating-value">5.0</span>
            </div>
          </div>
          <div class="card-footer">
            <div class="client-avatar">MJ</div>
            <div class="client-info">
              <h4>Michael Johnson</h4>
              <p>Tesla Model S</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="card-content">
            <div class="quote-icon">"</div>
            <p class="testimonial-text">
              Professional team, great communication. Very satisfied with my BMW.
            </p>
            <div class="rating">
              <div class="stars">
                <span v-for="n in 5" :key="n" class="star">★</span>
              </div>
              <span class="rating-value">5.0</span>
            </div>
          </div>
          <div class="card-footer">
            <div class="client-avatar">SR</div>
            <div class="client-info">
              <h4>Sarah Rodriguez</h4>
              <p>BMW X5 M</p>
            </div>
          </div>
        </div>

        <div class="testimonial-card">
          <div class="card-content">
            <div class="quote-icon">"</div>
            <p class="testimonial-text">
              Third purchase here. Consistent quality and outstanding service.
            </p>
            <div class="rating">
              <div class="stars">
                <span v-for="n in 5" :key="n" class="star">★</span>
              </div>
              <span class="rating-value">5.0</span>
            </div>
          </div>
          <div class="card-footer">
            <div class="client-avatar">DW</div>
            <div class="client-info">
              <h4>David Wilson</h4>
              <p>Mercedes E-Class</p>
            </div>
          </div>
        </div>

      </div>

            <!-- STATISTICS SLIDER -->
      <div class="statistics-slider-container">
        <div class="statistics-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
          <div class="statistics-slide">
            <div class="stat-item">
              <div class="stat-number">5000+</div>
              <div class="stat-label">Happy Clients</div>
            </div>
          </div>
          <div class="statistics-slide">
            <div class="stat-item">
              <div class="stat-number">4.9</div>
              <div class="stat-label">Avg Rating</div>
            </div>
          </div>
          <div class="statistics-slide">
            <div class="stat-item">
              <div class="stat-number">98%</div>
              <div class="stat-label">Satisfaction</div>
            </div>
          </div>
          <div class="statistics-slide">
            <div class="stat-item">
              <div class="stat-number">15+</div>
              <div class="stat-label">Years Experience</div>
            </div>
          </div>
        </div>
        
        <!-- Dots indicator -->
        <div class="slider-dots">
          <button 
            v-for="(_, index) in 4" 
            :key="index"
            class="dot"
            :class="{ 'active': currentSlide === index }"
            @click="goToSlide(index)"
          ></button>
        </div>
      </div>

      <div class="statistics-row desktop-only">
        <div class="stat-item">
          <div class="stat-number">5000+</div>
          <div class="stat-label">Happy Clients</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">4.9</div>
          <div class="stat-label">Avg Rating</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">98%</div>
          <div class="stat-label">Satisfaction</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">15+</div>
          <div class="stat-label">Years Experience</div>
        </div>
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const currentSlide = ref(0)
const totalSlides = 4
let slideInterval = null

const goToSlide = (index) => {
  currentSlide.value = index
}

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % totalSlides
}

onMounted(() => {

  const startAutoSlide = () => {
    if (window.innerWidth <= 768) {
      slideInterval = setInterval(nextSlide, 3000) 
    }
  }

  startAutoSlide()

  window.addEventListener('resize', () => {
    if (window.innerWidth <= 768) {
      if (!slideInterval) {
        slideInterval = setInterval(nextSlide, 3000)
      }
    } else {
      if (slideInterval) {
        clearInterval(slideInterval)
        slideInterval = null
      }
      currentSlide.value = 0 
    }
  })
})

onUnmounted(() => {
  if (slideInterval) {
    clearInterval(slideInterval)
  }
})
</script>

<style scoped>
.testimonials-section {
  background: #F5EEDC; 
  padding: 4rem 1rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

/* HEADER */
.header {
  text-align: center;
  margin-bottom: 3rem;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(90deg, #dab062, #CC8D1A);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  margin-bottom: 1rem;
}

.badge-icon {
  width: 14px;
  height: 14px;
}

.badge-text {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.title {
  color: #183B4E;
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  letter-spacing: -0.5px;
}

.subtitle {
  color: #27548A; 
  font-size: 1.1rem;
  max-width: 500px;
  margin: 0 auto;
  opacity: 0.9;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1.5rem;
  margin-bottom: 3rem;
}

@media (min-width: 768px) {
  .testimonials-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .testimonials-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.testimonial-card {
  background: #FFFFFF;
  border: 1px solid rgba(221, 168, 83, 0.2);
  border-radius: 16px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(24, 59, 78, 0.08);
  display: flex;
  flex-direction: column;
}

.testimonial-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(24, 59, 78, 0.15);
  border-color: #DDA853;;
}

.card-content {
  flex-grow: 1;
  margin-bottom: 1rem;
}

.quote-icon {
  color: #DDA853;
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.75rem;
}

.testimonial-text {
  color: #183B4E;
  font-size: 0.875rem;
  line-height: 1.5;
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.rating {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stars {
  display: flex;
  gap: 0.125rem;
}

.star {
  color: #DDA853;
  font-size: 1rem;
}

.rating-value {
  color: #27548A;
  font-size: 0.875rem;
  font-weight: 600;
}

.card-footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(221, 168, 83, 0.2);
}

.client-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  flex-shrink: 0;
  margin-bottom: 20px;
}

.client-info h4 {
  color: 183B4E;
  font-size: 0.875rem;
  font-weight: 700;
  margin-bottom: 0.125rem;
}

.client-info p {
  color: #27548A;
  font-size: 0.75rem;
}

.statistics-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  border: 1px solid rgba(221, 168, 83, 0.2);
  box-shadow: 0 8px 25px rgba(24, 59, 78, 0.1);
  backdrop-filter: blur(10px);
}

@media (min-width: 768px) {
  .statistics-row {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* Slider styles  */
.statistics-slider-container {
  display: none;
  position: relative;
  width: 100%;
  overflow: hidden;
  padding: 1rem 0 2rem;
}

@media (max-width: 767px) {
  .statistics-slider-container {
    display: block;
  }
  
  .desktop-only {
    display: none;
  }
}

.statistics-track {
  display: flex;
  transition: transform 0.5s ease-in-out;
}

.statistics-slide {
  flex: 0 0 100%;
  padding: 0 1rem;
  box-sizing: border-box;
}

.statistics-slide .stat-item {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid rgba(221, 168, 83, 0.2);
  box-shadow: 0 4px 15px rgba(24, 59, 78, 0.1);
  text-align: center;
  animation: slideFade 0.5s ease-out;
}

@keyframes slideFade {
  from {
    opacity: 0.3;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.statistics-slide .stat-number {
  color: #CC8D1A;
  font-size: 2.2rem;
  font-weight: 800;
  margin-bottom: 0.25rem;
  line-height: 1;
}

.statistics-slide .stat-label {
  color: #16232E;
  font-size: 1rem;
  font-weight: 600;
}

/* Dots indicator */
.slider-dots {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  margin-top: 1rem;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: rgba(221, 168, 83, 0.3);
  border: none;
  padding: 0;
  cursor: pointer;
  transition: all 0.3s ease;
}

.dot.active {
  background: #DDA853;
  transform: scale(1.3);
  width: 24px;
  border-radius: 12px;
}

.statistics-row .stat-number {
  color: #CC8D1A;
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 0.25rem;
  line-height: 1;
}

.statistics-row .stat-label {
  color: #16232E;
  font-size: 0.875rem;
  font-weight: 600;
}

/* Animation for the slider */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.statistics-slide {
  animation: slideIn 0.5s ease-out;
}

@media (max-width: 480px) {
  .statistics-slide .stat-number {
    font-size: 1.8rem;
  }
  
  .statistics-slide .stat-label {
    font-size: 0.9rem;
  }
  
  .statistics-slide .stat-item {
    padding: 1.25rem;
  }
}

.stat-item {
  text-align: center;
}

.stat-number {
  color: #CC8D1A;
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 0.25rem;
  line-height: 1;
}

.stat-label {
  color: #16232E;
  font-size: 0.875rem;
  font-weight: 600;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.testimonial-card {
  animation: fadeIn 0.4s ease-out forwards;
  opacity: 0;
}

.testimonial-card:nth-child(1) { animation-delay: 0.1s; }
.testimonial-card:nth-child(2) { animation-delay: 0.2s; }
.testimonial-card:nth-child(3) { animation-delay: 0.3s; }
.testimonial-card:nth-child(4) { animation-delay: 0.4s; }
.testimonial-card:nth-child(5) { animation-delay: 0.5s; }
.testimonial-card:nth-child(6) { animation-delay: 0.6s; }

.statistics-row {
  animation: fadeIn 0.5s ease-out 0.7s forwards;
  opacity: 0;
}

* {
  transition-property: transform, box-shadow, border-color;
  transition-duration: 300ms;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>