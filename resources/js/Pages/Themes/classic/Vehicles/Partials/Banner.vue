<template>
  <section class="stock-hero">
        <div class="stock-hero__bg-container">
      <!-- Skeleton Loader -->
      <div v-if="isLoading" class="stock-hero__skel"></div>
    <div class="stock-hero__bg"
       @load="onImageLoad"
           :class="{ 'stock-hero__bg--loaded': !isLoading }"></div>
    </div>
    <div class="stock-hero__overlay"></div>

    <div class="stock-hero__content">
        <div class="container">
      <div class="stock-hero__titleRow">
        <h1 class="stock-hero__title">{{ title }}</h1>
        <div class="stock-hero__line"></div>
      </div>

      <p class="stock-hero__sub">
        Filter by type, make, and model to find your vehicle.
      </p>

     
    </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "Banner",
  props: {
    title: { type: String, default: "Local Stock" },
    total: { type: Number, default: 0 },
    typesCount: { type: Number, default: 0 },
    makesCount: { type: Number, default: 0 },
  },

    data() {
    return {
      isLoading: true,
    };
  },
  
  methods: {
    onImageLoad() {
      this.isLoading = false;
    },
  },
  
  mounted() {
    const img = new Image();
    img.src = '/multivendor/stock.webp';
    
    if (img.complete) {
      this.isLoading = false;
    } else {
      img.onload = this.onImageLoad;
    }
  },
};
</script>

<style scoped>
.stock-hero {
  position: relative;
  width: 100%;
  height: clamp(240px, 38vw, 500px);
  overflow: hidden;
  /* border-radius: 18px; */
}

.stock-hero__bg {
  position: absolute;
  inset: 0;
background-image: url("/multivendor/stock.webp");
  background-size: cover;
  background-position: center;
  transform: scale(1.02);
}

.stock-hero__overlay {
  position: absolute;
  inset: 0;
  background:
   linear-gradient(
    90deg,
    rgba(0, 0, 0, 0.65) 0%,
    rgba(0, 0, 0, 0.35) 35%,
    rgba(0, 0, 0, 0.05) 70%,
    rgba(0, 0, 0, 0) 100%
  );
}

.stock-hero__content {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 70px;
  z-index: 2;
  color: #fff;
}


@media (max-width: 992px) {
  .stock-hero__content { bottom: 28px; }
  .pv-container { padding: 0 16px; }
}
.stock-hero__titleRow {
  display: inline-flex;
  flex-direction: column;
  gap: 10px;
}

.stock-hero__title {
  margin: 0;
  font-weight: 900;
  letter-spacing: 0.2px;
  font-size: clamp(26px, 3.2vw, 44px);
  text-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
}

.stock-hero__line {
  width: 260px;
  max-width: 70vw;
  height: 3px;
  border-radius: 999px;
  background: linear-gradient(135deg, #332e78, #da0cda);
  opacity: .95;
}

.stock-hero__sub {
  margin: 10px 0 0;
  font-size: 14px;
  opacity: 0.9;
  max-width: 520px;
}

.stock-hero__stats {
  margin-top: 14px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.stat {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 14px;
  padding: 10px 12px;
  min-width: 120px;
  backdrop-filter: blur(6px);
}

.stat__num {
  font-weight: 900;
  font-size: 16px;
}

.stat__lbl {
  font-size: 12px;
  opacity: 0.85;
  font-weight: 700;
}

@media (max-width: 576px) {

  .stock-hero__line {
    width: 180px;
  }
}

/* Skeleton Loader  */
.stock-hero__skel {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, #e5e7eb 0%, #f3f4f6 50%, #e5e7eb 100%);
  background-size: 200% 100%;
  animation: pvShimmer 1.2s infinite;
  z-index: 1;
}

@keyframes pvShimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}
</style>
