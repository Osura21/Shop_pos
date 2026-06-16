<script setup>
import { Heart, MapPin, Gauge, Fuel, Calendar } from "lucide-vue-next"
import { Link } from "@inertiajs/vue3"
import { ref, onMounted } from "vue"
import axios from "axios"

const featuredCars = ref([])
const loading = ref(true)

async function fetchFeatured() {
    loading.value = true
    try {
        const res = await axios.get(route("vendorsite.vehicles.featured"), {
            params: { limit: 6 },
        })

        featuredCars.value = res.data ?? []
    } catch (e) {
        console.error(e)
        featuredCars.value = []
    } finally {
        loading.value = false
    }
}

onMounted(fetchFeatured)

const fmtPrice = (p) => {
    if (p === null || p === undefined || p === "") return "Call"
    return `Rs. ${Number(p).toLocaleString()}`
}

const detailUrl = (car) => {
    if (car?.slug) {
        return route("vendorsite.product", { slug: car.slug })
    }

    return route("vendorsite.product", { slug: "ad" }) + `?id=${car.id}`
}
</script>

<template>
    <section v-if="featuredCars.length || loading" class="py-5 bg-white">
        <div class="container py-5">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-4 mb-5">
                <div>
                    <span class="badge rounded-pill custom-bg-primary bg-opacity-10 custom-text-primary px-4 py-2 mb-3">
                        Featured Vehicles
                    </span>
                    <h2 class="fw-bold display-7 mb-0">Premium Listings</h2>
                </div>

                <Link :href="route('vendorsite.vehicles.index', { featured: 1 })"
                    class="custom-btn custom-btn-secondary rounded-pill px-4">
                    View All Listings
                </Link>
            </div>

            <div v-if="loading" class="row g-4">
                <div v-for="n in 6" :key="n" class="col-12 col-md-6 col-lg-3">
                    <div class="card listing-card h-100 border-0 overflow-hidden">
                        <div class="listing-image sk"></div>
                        <div class="p-4">
                            <div class="sk sk-line w-80 mb-2"></div>
                            <div class="sk sk-line w-60 mb-3"></div>
                            <div class="d-flex gap-3 pt-3 border-top">
                                <div class="sk sk-chip"></div>
                                <div class="sk sk-chip"></div>
                                <div class="sk sk-chip"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="row g-4">
                <div v-for="(car, index) in featuredCars" :key="car.id" class="col-12 col-md-6 col-lg-3">
                    <Link :href="detailUrl(car)"
                        class="card listing-card h-100 border-0 overflow-hidden text-decoration-none"
                        :style="{ animationDelay: `${index * 100}ms` }">

                        <div class="position-relative listing-image">
                            <img :src="car.image || 'https://via.placeholder.com/1200x800?text=Vehicle'"
                                :alt="`${car?.year || ''} ${car?.make || ''} ${car?.model || ''}`"
                                class="w-100 h-100 object-fit-cover" />

                            <div class="image-overlay"></div>

                            <span v-if="car.featured"
                                class="badge custom-bg-primary position-absolute top-0 start-0 m-3">
                                Featured
                            </span>

                            <button type="button" class="favorite-btn" @click.stop.prevent>
                                <Heart size="18" />
                            </button>

                            <div class="quick-view">
                                <span class="btn btn-light rounded-pill fw-semibold w-100">
                                    View Details
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <h5 class="fw-normal mb-1 listing-title">
                                        {{ car.year }} {{ car.make }} {{ car.model }}
                                    </h5>

                                    <small v-if="car.location" class="text-muted d-flex align-items-center gap-1">
                                        <MapPin size="14" /> {{ car.location }}
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <div class="fw-bold fs-">
                                    {{ fmtPrice(car.price) }}
                                </div>
                            </div>

                            <div class="d-flex gap-3">
                                <small class="text-muted d-flex align-items-center gap-1">
                                    <MapPin size="16" /> {{ car.city || car.district || "Sri Lanka" }}
                                </small>
                            </div>

                            <div class="d-flex gap-3 pt-3 border-top">
                                <small class="text-muted d-flex align-items-center gap-1">
                                    <Gauge size="16" /> {{ Number(car.mileage || 0).toLocaleString() }} km
                                </small>
                                <small class="text-muted d-flex align-items-center gap-1">
                                    <Fuel size="16" /> {{ car.fuel }}
                                </small>
                                <small class="text-muted d-flex align-items-center gap-1">
                                    <Calendar size="16" /> {{ car.year }}
                                </small>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-if="!featuredCars.length" class="text-center text-muted py-5">
                    No featured vehicles found.
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.sk {
    background: linear-gradient(90deg, rgba(0, 0, 0, .06) 25%, rgba(0, 0, 0, .10) 37%, rgba(0, 0, 0, .06) 63%);
    background-size: 700% 200%;
    animation: sk 1.2s ease-in-out infinite;
    border-radius: 14px;
}

@keyframes sk {
    0% {
        background-position: 100% 0
    }

    100% {
        background-position: 0 0
    }
}

.sk-line {
    height: 14px;
    border-radius: 999px;
}

.w-80 {
    width: 80%;
}

.w-60 {
    width: 60%;
}

.sk-chip {
    height: 14px;
    width: 70px;
    border-radius: 999px;
}

.listing-image {
    height: 240px;
}
</style>