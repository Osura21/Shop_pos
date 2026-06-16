<script setup>
import { ref } from "vue"
import { Link } from "@inertiajs/vue3"
import { Car, Truck, CarFront, Zap, ChevronRight } from "lucide-vue-next"

/* ================= DATA ================= */

const brands = [
  { name: "BMW", logo: "https://www.carlogos.org/car-logos/bmw-logo.png" },
  { name: "Mercedes", logo: "https://www.carlogos.org/car-logos/mercedes-benz-logo.png" },
  { name: "Porsche", logo: "https://www.carlogos.org/car-logos/porsche-logo.png" },
  { name: "Tesla", logo: "https://www.carlogos.org/car-logos/tesla-logo.png" },
  { name: "Audi", logo: "https://www.carlogos.org/car-logos/audi-logo.png" },
  { name: "Lexus", logo: "https://www.carlogos.org/car-logos/lexus-logo.png" },
]

const bodyTypes = [
  { name: "Sedan", icon: CarFront, count: 4523 },
  { name: "SUV", icon: Truck, count: 3892 },
  { name: "Coupe", icon: Car, count: 1756 },
  { name: "Electric", icon: Zap, count: 2134 },
]

const priceRanges = [
  { label: "Under $25K", value: "0-25000" },
  { label: "$25K - $50K", value: "25000-50000" },
  { label: "$50K - $100K", value: "50000-100000" },
  { label: "$100K+", value: "100000+" },
]

/* ================= STATE ================= */

const selectedType = ref(null)
const selectedPrice = ref(null)

const toggleType = (type) => {
  selectedType.value = selectedType.value === type ? null : type
}

const togglePrice = (price) => {
  selectedPrice.value = selectedPrice.value === price ? null : price
}
</script>

<template>
  <section id="browse" class="py-5 bg-light bg-opacity-50">
    <div class="container py-5">

      <!-- SECTION HEADER -->
      <div class="text-center mb-5">
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-4 py-2 mb-3">
          Browse Collection
        </span>
        <h2 class="fw-bold display-6 mb-3">Find Your Ideal Vehicle</h2>
        <p class="text-muted fs-5 mx-auto" style="max-width: 700px">
          Explore our curated selection of premium vehicles by brand, body type, or price range.
        </p>
      </div>

      <!-- BROWSE BY BRAND -->
      <div class="mb-5">
        <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
          <span class="bg-primary rounded" style="width:4px;height:24px"></span>
          Browse by Brand
        </h5>

        <div class="row g-3">
          <div v-for="brand in brands" :key="brand.name" class="col-4 col-md-2">
            <button class="brand-card w-100">
              <img :src="brand.logo" :alt="brand.name" />
              <span>{{ brand.name }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- BROWSE BY BODY TYPE -->
      <div class="mb-5">
        <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
          <span class="bg-primary rounded" style="width:4px;height:24px"></span>
          Browse by Body Type
        </h5>

        <div class="row g-3">
          <div v-for="type in bodyTypes" :key="type.name" class="col-6 col-md-3">
            <button
              class="type-card w-100"
              :class="{ active: selectedType === type.name }"
              @click="toggleType(type.name)"
            >
              <div class="icon-wrap">
                <component :is="type.icon" :size="28" />
              </div>
              <strong>{{ type.name }}</strong>
              <small class="text-muted">{{ type.count.toLocaleString() }} cars</small>
            </button>
          </div>
        </div>
      </div>



      <!-- CTA -->
      <div class="text-center mt-5">
        <Link
          href="/cars"
          class="fw-semibold text-primary text-decoration-none d-inline-flex align-items-center gap-2 view-all"
        >
          View All Listings
          <ChevronRight size="20" />
        </Link>
      </div>

    </div>
  </section>
</template>

<style scoped>
/* ================= BRAND ================= */
.brand-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid rgba(0,0,0,.05);
  transition: all .3s ease;
}

.brand-card img {
  height: 48px;
  object-fit: contain;
  filter: grayscale(1);
  transition: all .3s ease;
}

.brand-card span {
  display: block;
  margin-top: 10px;
  font-size: .875rem;
  color: #6c757d;
}

.brand-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(0,0,0,.08);
}

.brand-card:hover img {
  filter: grayscale(0);
}

/* ================= BODY TYPE ================= */
.type-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid rgba(0,0,0,.05);
  transition: all .3s ease;
  text-align: center;
}

.type-card .icon-wrap {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  transition: all .3s ease;
}

.type-card.active {
  border-color: var(--bs-primary);
  box-shadow: 0 10px 30px rgba(13,110,253,.15);
}

.type-card.active .icon-wrap {
  background: var(--bs-primary);
  color: #fff;
}

/* ================= PRICE ================= */
.price-pill {
  padding: 12px 24px;
  border-radius: 50px;
  border: 1px solid rgba(0,0,0,.1);
  background: #fff;
  transition: all .3s ease;
}

.price-pill.active {
  background: var(--bs-primary);
  color: #fff;
  border-color: var(--bs-primary);
  box-shadow: 0 10px 25px rgba(13,110,253,.25);
}

/* ================= CTA ================= */
.view-all {
  transition: gap .3s ease;
}

.view-all:hover {
  gap: 12px;
}
</style>
