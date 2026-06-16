<template>
  <section class="pv-hero">
        <div class="pv-hero__bg-container">
      <!-- Skeleton Loader -->
      <div v-if="isLoading" class="pv-hero__skel"></div>
    <div
      class="pv-hero__bg"
      :style="{ backgroundImage: `url('/multivendor/product.webp')` }"
        @load="onImageLoad"
      aria-hidden="true"
      :class="{ 'pv-hero__bg--loaded': !isLoading }"
    ></div>
        </div>
    <div class="pv-hero__overlay" aria-hidden="true"></div>

<div class="pv-hero__content">
  <div class="container">
          <div class="pv-breadcrumbs">
        <Link :href="route('multivendor.vehicles.index')" class="pv-bc__link">Stock</Link>
        <span class="pv-bc__sep">/</span>
        <span class="pv-bc__current">{{ heroTitle }}</span>
      </div>

      <h1 class="pv-hero__title">{{ heroTitle }}</h1>

      <!-- <div class="pv-hero__meta">
        <span class="pv-chip">
          <i class="fa-solid fa-calendar-days"></i>
          {{ vehicle?.year ?? "—" }}
        </span>

        <span class="pv-chip">
          <i class="fa-solid fa-road"></i>
          {{ vehicle?.mileage ? `${vehicle.mileage} Miles` : "N/A Miles" }}
        </span>

        <span class="pv-chip">
          <i class="fa-solid fa-gears"></i>
          {{ transmissionLabel(vehicle?.transmission) }}
        </span>

        <span class="pv-chip">
          <i class="fa-solid fa-gas-pump"></i>
          {{ fuelLabel(vehicle?.fuel_type) }}
        </span>

        <span class="pv-pill" :class="availabilityClass(vehicle?.availability)">
          {{ availabilityLabel(vehicle?.availability) }}
        </span>
      </div> -->
    </div>
        </div>

  </section>
</template>

<script>
import { Link } from "@inertiajs/vue3";

export default {
  name: "ProductBanner",
  components: { Link },
  props: {
    vehicle: { type: Object, default: null },
    fallbackBg: { type: String, default: "/multivendor/stockbanner.jpg" },
  },

    data() {
    return {
      isLoading: true,
    };
  },

  computed: {
    heroTitle() {
      const make = this.vehicle?.manufacture?.title || "Vehicle";
      const model = this.vehicle?.vehicle_model?.title || "";
      const year = this.vehicle?.year ? ` • ${this.vehicle.year}` : "";
      return `${make} ${model}${year}`.trim();
    },

    // bgUrl() {
    //   // prefer vehicle main image
    //   const media = this.vehicle?.media || [];
    //   const main =
    //     media.find((m) => m.collection_name === "vehicle_main" || m.custom_properties?.type === "vehicle_main") ||
    //     media[0];

    //   return main?.original_url || this.fallbackBg;
    // },
  },

  methods: {
    transmissionLabel(val) {
      const n = Number(val);
      if (n === 0) return "Auto";
      if (n === 1) return "Manual";
      if (n === 2) return "Triptonic";
      return "—";
    },

    fuelLabel(val) {
      const n = Number(val);
      if (n === 0) return "Diesel";
      if (n === 1) return "Petrol";
      if (n === 2) return "Hybrid";
      if (n === 3) return "Electric";
      return "—";
    },

    availabilityLabel(val) {
      const n = Number(val);
      if (n === 0) return "Available";
      if (n === 1) return "Arriving";
      if (n === 2) return "Sold";
      return "—";
    },

    availabilityClass(val) {
      const n = Number(val);
      if (n === 0) return "is-green";
      if (n === 1) return "is-amber";
      if (n === 2) return "is-red";
      return "is-muted";
    },

        onImageLoad() {
      this.isLoading = false;
    },
  },

    mounted() {
    const img = new Image();
    img.src =  '/multivendor/product.webp';
    
    if (img.complete) {
      this.isLoading = false;
    } else {
      img.onload = this.onImageLoad; 
    }
  },
};
</script>

<style scoped>
.pv-hero {
  position: relative;
  width: 100%;
  height: clamp(240px, 38vw, 500px);
  overflow: hidden;
}

.pv-hero__bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  transform: scale(1.03);
  filter: saturate(1.02);
}

.pv-hero__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    rgba(0, 0, 0, 0.72) 0%,
    rgba(0, 0, 0, 0.42) 35%,
    rgba(0, 0, 0, 0.10) 70%,
    rgba(0, 0, 0, 0) 100%
  );
}

.pv-hero__content {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 70px;     
  z-index: 2;
  color: #fff;
}

.pv-container {
  max-width: 1320px;    
  margin: 0 auto;
  padding: 0 24px;      
}

/* responsive */
@media (max-width: 992px) {
  .pv-hero__content {
    bottom: 28px;
  }
  .pv-container {
    padding: 0 16px;
  }
}

.pv-breadcrumbs {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  opacity: 0.95;
 margin-bottom: 6px;
}
.pv-bc__link {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-weight: 800;
}
.pv-bc__link:hover {
  text-decoration: underline;
}
.pv-bc__sep {
  opacity: 0.7;
}
.pv-bc__current {
  font-weight: 900;
}

.pv-hero__title {
  margin: 0;
  font-weight: 900;
  letter-spacing: 0.2px;
  font-size: clamp(24px, 3.2vw, 42px);
  line-height: 1.1;
  text-shadow: 0 10px 26px rgba(0, 0, 0, 0.35);
}

.pv-hero__meta {
  margin-top: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.pv-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
}
.pv-chip i {
  opacity: 0.95;
}

.pv-pill {
  display: inline-flex;
  align-items: center;
  padding: 10px 12px;
  border-radius: 999px;
  font-weight: 950;
  font-size: 12px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
}

.pv-pill.is-green { background: rgba(34, 197, 94, 0.18); color: #d9ffe6; }
.pv-pill.is-amber { background: rgba(245, 158, 11, 0.18); color: #fff1d5; }
.pv-pill.is-red   { background: rgba(239, 68, 68, 0.18); color: #ffe0e0; }
.pv-pill.is-muted { background: rgba(255, 255, 255, 0.10); color: rgba(255,255,255,.85); }

/* Skeleton Loader */
.pv-hero__skel {
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
