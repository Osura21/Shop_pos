<template>
  <section class="banner-section">
    <div class="banner-background">
      <img 
        src="/multivendor/black-luxury.webp" 
        alt="Browse premium vehicles" 
        class="banner-image"
        loading="eager"
      />
      <div class="banner-overlay"></div>
      <div class="banner-gradient"></div>
    </div>

    <div class="banner-container">
      <div class="banner-content">

        <!-- Headline -->
        <h1 class="banner-title">
          <span class="title-primary">Discover Your</span>
          <span class="title-accent">Perfect Match</span>
        </h1>

        <!-- Description -->
        <p class="banner-description">
          Browse our curated collection of luxury vehicles. 
          Each car is meticulously inspected and certified.
        </p>
      </div>
    </div>

    <!-- Decorative Elements -->
    <div class="banner-shapes">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
    </div>
  </section>
</template>

<script setup>
// Banner component 
</script>

<style scoped>
.banner-section {
  position: relative;
  min-height: 500px;
  display: flex;
  align-items: center;
  overflow: hidden;
  background: #183B4E;
}

/* Background */
.banner-background {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.banner-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.35) contrast(1.1);
  transform: scale(1.05);
  transition: transform 8s ease;
}

.banner-section:hover .banner-image {
  transform: scale(1.1);
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

.banner-gradient {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at 70% 50%,
    rgba(221, 168, 83, 0.15) 0%,
    transparent 70%
  );
  z-index: 2;
}

/* Container */
.banner-container {
  position: relative;
  z-index: 10;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
  width: 100%;
}

.banner-content {
  max-width: 700px;
  margin-top: 2rem;
}

/* Badge */
.banner-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(221, 168, 83, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(221, 168, 83, 0.3);
  border-radius: 100px;
  padding: 0.5rem 1.5rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}

.banner-badge::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
  animation: shimmer 3s infinite;
}

@keyframes shimmer {
  100% {
    left: 100%;
  }
}

.badge-pulse {
  width: 8px;
  height: 8px;
  background: #DDA853;
  border-radius: 50%;
  position: relative;
}

.badge-pulse::after {
  content: '';
  position: absolute;
  top: -4px;
  left: -4px;
  right: -4px;
  bottom: -4px;
  background: rgba(221, 168, 83, 0.4);
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 0.5;
  }
  50% {
    transform: scale(1.5);
    opacity: 0;
  }
}

.badge-text {
  color: #FFFFFF;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: 2px;
}

/* Title */
.banner-title {
  margin-bottom: 1.5rem;
}

.title-primary {
  display: block;
  color: #FFFFFF;
  font-size: clamp(1rem, 5vw, 2.5rem);
  font-weight: 700;
  line-height: 1.1;
  opacity: 0.9;
}

.title-accent {
  display: block;
  font-size: clamp(2rem, 6vw, 3rem);
  font-weight: 800;
  line-height: 1.1;
  background: linear-gradient(90deg, #DDA853, #F5EEDC);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: 0 2px 10px rgba(221, 168, 83, 0.2);
}

/* Description */
.banner-description {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  line-height: 1.6;
  max-width: 550px;
  margin-bottom: 2rem;
}

/* Stock Counter */
.stock-counter {
  display: inline-flex;
  align-items: center;
  gap: 2rem;
  padding: 1rem 2rem;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(221, 168, 83, 0.2);
  border-radius: 50px;
}

.counter-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.counter-number {
  color: #DDA853;
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1;
}

.counter-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 1px;
  text-transform: uppercase;
  margin-top: 0.25rem;
}

.counter-dot {
  width: 4px;
  height: 4px;
  background: rgba(221, 168, 83, 0.5);
  border-radius: 50%;
}

/* Decorative Shapes */
.banner-shapes {
  position: absolute;
  inset: 0;
  z-index: 5;
  pointer-events: none;
}

.shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.15;
}

.shape-1 {
  top: 20%;
  right: 15%;
  width: 300px;
  height: 300px;
  background: #DDA853;
  animation: float 20s infinite;
}

.shape-2 {
  bottom: 10%;
  left: 10%;
  width: 250px;
  height: 250px;
  background: #27548A;
  animation: float 25s infinite reverse;
}

.shape-3 {
  top: 50%;
  right: 30%;
  width: 200px;
  height: 200px;
  background: #183B4E;
  animation: float 22s infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-30px) rotate(5deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .banner-section {
    min-height: 450px;
  }

  .banner-content {
    margin-top: 1rem;
    text-align: center;
    max-width: 100%;
  }

  .banner-description {
    margin-left: auto;
    margin-right: auto;
  }

  .stock-counter {
    gap: 1rem;
    padding: 0.75rem 1.5rem;
  }

  .counter-number {
    font-size: 1.25rem;
  }
}

@media (max-width: 480px) {
  .stock-counter {
    flex-direction: column;
    gap: 0.75rem;
    border-radius: 20px;
    width: 100%;
  }

  .counter-dot {
    display: none;
  }
}
</style>