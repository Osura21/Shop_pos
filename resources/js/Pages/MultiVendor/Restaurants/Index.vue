<script>
import { Head, router } from "@inertiajs/vue3"
import { ArrowLeft, ArrowRight, ChevronDown, ClipboardList, MapPin, Search } from "lucide-vue-next"
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import MarketplaceFooterSections from "../Components/MarketplaceFooterSections.vue"

const fallbackImages = [
    "https://images.unsplash.com/photo-1604382355076-af4b0eb60143?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1562967916-eb82221dfb36?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1551024506-0bccd828d307?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?auto=format&fit=crop&w=900&q=85",
    "https://images.unsplash.com/photo-1596797038530-2c107229654b?auto=format&fit=crop&w=900&q=85",
]

const fallbackFoodTypes = [
    { name: "Pizza", slug: "pizza", image_url: "https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=200&q=80" },
    { name: "Pasta", slug: "pasta", image_url: "https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=200&q=80" },
    { name: "Burgers", slug: "burgers", image_url: "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=200&q=80" },
    { name: "Rice & Curry", slug: "rice", image_url: "https://images.unsplash.com/photo-1603133872878-684f208fb84b?auto=format&fit=crop&w=200&q=80" },
    { name: "Fried Chicken", slug: "fried-chicken", image_url: "https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?auto=format&fit=crop&w=200&q=80" },
]

export default {
    name: "RestaurantsIndex",

    components: {
        ArrowLeft,
        ArrowRight,
        ChevronDown,
        ClipboardList,
        Head,
        MapPin,
        MarketplaceFooterSections,
        MultiVendorLayout,
        Search,
    },

    props: {
        restaurants: { type: Object, default: () => ({ data: [], meta: {} }) },
        foodTypes: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
        stats: { type: Object, default: () => ({}) },
    },

    data() {
        return {
            activeType: this.filters.food_type || "",
            searchQuery: this.filters.search || "",
            deliveryMode: this.filters.delivery || "",
            locationQuery: this.filters.location || "",
            countryFilter: this.filters.country || "",
            cards: this.restaurants.data || [],
            isFiltering: false,
            isLoadingMore: false,
            serviceDropdownOpen: false,
            observer: null,
            debounceTimer: null,
        }
    },

    computed: {
        displayFoodTypes() {
            return this.foodTypes.length ? this.foodTypes : fallbackFoodTypes
        },

        currentPage() {
            return Number(this.restaurants?.current_page || this.restaurants?.meta?.current_page || 1)
        },

        lastPage() {
            return Number(this.restaurants?.last_page || this.restaurants?.meta?.last_page || 1)
        },

        totalRestaurants() {
            return Number(this.stats?.restaurants || this.restaurants?.total || this.cards.length)
        },

        hasActiveFilter() {
            return Boolean(this.activeType || this.searchQuery || this.deliveryMode || this.locationQuery || this.countryFilter)
        },

        hasMore() {
            return this.currentPage < this.lastPage
        },

        serviceOptions() {
            return [
                { value: "", label: "Select Service Type" },
                { value: "delivery", label: "Delivery" },
                { value: "pickup", label: "Pickup" },
                { value: "scheduled", label: "Scheduled" },
            ]
        },

        selectedServiceLabel() {
            return this.serviceOptions.find((option) => option.value === this.deliveryMode)?.label || "Select Service Type"
        },
    },

    watch: {
        restaurants: {
            deep: true,
            handler(value) {
                const nextCards = value?.data || []

                if (this.currentPage <= 1 || this.isFiltering) {
                    this.cards = nextCards
                } else {
                    const existing = new Set(this.cards.map((restaurant) => restaurant.id))
                    this.cards = this.cards.concat(nextCards.filter((restaurant) => !existing.has(restaurant.id)))
                }

                this.isFiltering = false
                this.isLoadingMore = false
                this.$nextTick(this.observeSentinel)
            },
        },
    },

    mounted() {
        this.observeSentinel()
        document.addEventListener("click", this.closeServiceDropdown)
    },

    beforeUnmount() {
        this.observer?.disconnect()
        document.removeEventListener("click", this.closeServiceDropdown)
        clearTimeout(this.debounceTimer)
    },

    methods: {
        submitFilters() {
            this.isFiltering = true
            router.get(this.restaurantFilterUrl(), {}, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                only: ["restaurants", "filters", "stats"],
                onFinish: () => {
                    this.isFiltering = false
                },
            })
        },

        submitFiltersDebounced() {
            clearTimeout(this.debounceTimer)
            this.debounceTimer = setTimeout(this.submitFilters, 220)
        },

        slug(value) {
            return String(value || "")
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-+|-+$/g, "")
        },

        restaurantFilterUrl(overrides = {}) {
            const filters = {
                search: this.searchQuery,
                food_type: this.activeType,
                delivery: this.deliveryMode,
                location: this.locationQuery,
                country: this.countryFilter,
                ...overrides,
            }
            const parts = []
            const country = this.slug(filters.country)
            const location = this.slug(filters.location)
            const foodType = this.slug(filters.food_type)
            const delivery = this.slug(filters.delivery)
            const search = this.slug(filters.search)

            if (country) parts.push(country)
            if (location) parts.push(location)
            if (foodType && delivery) {
                parts.push(`${foodType}-${delivery}`)
            } else if (foodType) {
                parts.push(foodType)
            } else if (delivery) {
                parts.push(delivery)
            }
            if (search) parts.push("search", search)

            return parts.length ? `/resturents/${parts.join("/")}` : "/resturents"
        },

        chooseType(slug) {
            this.activeType = slug
            this.submitFilters()
        },

        clearFilters() {
            this.activeType = ""
            this.searchQuery = ""
            this.deliveryMode = ""
            this.locationQuery = ""
            this.countryFilter = ""
            this.serviceDropdownOpen = false
            this.submitFilters()
        },

        toggleServiceDropdown() {
            this.serviceDropdownOpen = !this.serviceDropdownOpen
        },

        chooseService(value) {
            this.deliveryMode = value
            this.serviceDropdownOpen = false
            this.submitFilters()
        },

        closeServiceDropdown(event) {
            if (!this.$refs.serviceDropdown || this.$refs.serviceDropdown.contains(event.target)) return
            this.serviceDropdownOpen = false
        },

        scrollFoodTypes(direction) {
            const el = this.$refs.foodTypePills
            if (!el) return

            el.scrollBy({
                left: direction * 320,
                behavior: "smooth",
            })
        },

        loadMore() {
            if (!this.hasMore || this.isLoadingMore || this.isFiltering) return

            this.isLoadingMore = true
            router.get(this.restaurantFilterUrl(), {
                page: this.currentPage + 1,
            }, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                only: ["restaurants", "filters", "stats"],
                onFinish: () => {
                    this.isLoadingMore = false
                },
            })
        },

        observeSentinel() {
            this.observer?.disconnect()

            if (!this.$refs.loadMoreSentinel || !this.hasMore) return

            this.observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    this.loadMore()
                }
            }, { rootMargin: "520px 0px" })

            this.observer.observe(this.$refs.loadMoreSentinel)
        },

        restaurantImage(restaurant, index) {
            return restaurant.image_url || fallbackImages[index % fallbackImages.length]
        },

        foodTypeImage(type, index) {
            return type.image_url || fallbackFoodTypes[index % fallbackFoodTypes.length]?.image_url || fallbackImages[index % fallbackImages.length]
        },

        openRestaurant(restaurant) {
            if (!restaurant?.url) return
            router.visit(restaurant.url)
        },
    },
}
</script>

<template>
    <Head title="Restaurants | Sappy Eats" />

    <MultiVendorLayout>
        <main class="restaurants-page">
            <section class="restaurants-hero">
                <div class="restaurants-container">
                    <div class="page-label">
                        <span></span>
                        Explore restaurants
                    </div>

                    <h1>
                        Discover The Best <span>Restaurants</span> Near You
                    </h1>

                    <p class="hero-subtitle">Find nearby restaurants by food type, service option, location, and restaurant name.</p>
                </div>
            </section>

            <section class="food-type-section">
                <div class="restaurants-container">
                    <div class="food-type-filter-title">
                        <h2>Choose Food Type</h2>

                        <div class="food-pill-arrows">
                            <button class="food-pill-arrow" type="button" aria-label="Previous food types" @click="scrollFoodTypes(-1)">
                                <ArrowLeft size="20" />
                            </button>
                            <button class="food-pill-arrow" type="button" aria-label="Next food types" @click="scrollFoodTypes(1)">
                                <ArrowRight size="20" />
                            </button>
                        </div>
                    </div>

                    <div ref="foodTypePills" class="food-type-pills">
                        <button
                            class="food-pill all-types-pill"
                            :class="{ active: !activeType }"
                            type="button"
                            @click="chooseType('')"
                        >
                            All Types
                        </button>

                        <button
                            v-for="(type, index) in displayFoodTypes"
                            :key="type.slug"
                            class="food-pill"
                            :class="{ active: activeType === type.slug }"
                            type="button"
                            @click="chooseType(type.slug)"
                        >
                            <span class="food-pill-img">
                                <img :src="foodTypeImage(type, index)" :alt="type.name" loading="lazy" />
                            </span>
                            {{ type.name }}
                        </button>
                    </div>

                    <form class="search-area" @submit.prevent="submitFilters">
                        <div ref="serviceDropdown" class="service-dropdown" :class="{ 'is-open': serviceDropdownOpen }">
                            <button
                                type="button"
                                class="service-trigger"
                                :aria-expanded="serviceDropdownOpen ? 'true' : 'false'"
                                aria-haspopup="listbox"
                                @click.stop="toggleServiceDropdown"
                            >
                                <ClipboardList size="18" />
                                <span :class="{ 'is-placeholder': !deliveryMode }">{{ selectedServiceLabel }}</span>
                                <ChevronDown class="service-chevron" size="17" />
                            </button>

                            <div v-if="serviceDropdownOpen" class="service-menu" role="listbox">
                                <button
                                    v-for="option in serviceOptions"
                                    :key="option.value || 'all-services'"
                                    type="button"
                                    class="service-option"
                                    :class="{ active: deliveryMode === option.value }"
                                    role="option"
                                    :aria-selected="deliveryMode === option.value ? 'true' : 'false'"
                                    @click="chooseService(option.value)"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>

                        <label>
                            <MapPin size="18" />
                            <input
                                v-model="locationQuery"
                                type="search"
                                placeholder="Location"
                                aria-label="Location"
                                @input="submitFiltersDebounced"
                            />
                        </label>

                        <label class="search-area__name">
                            <Search size="18" />
                            <input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search Restaurant Name"
                                aria-label="Search restaurant name"
                                @input="submitFiltersDebounced"
                            />
                            <button
                                v-if="hasActiveFilter"
                                class="field-clear-btn"
                                type="button"
                                @click="clearFilters"
                            >
                                Clear
                            </button>
                        </label>

                        <button class="search-submit-btn" type="submit">Search</button>
                    </form>
                </div>
            </section>

            <section id="restaurants" class="restaurants-section">
                <div class="restaurants-container">
                    <div class="restaurants-top">
                        <div>
                            <h2>Available Restaurants</h2>
                            <p>Use the food type carousel and search bar above to find a restaurant faster.</p>
                        </div>

                        <div class="result-count">{{ totalRestaurants }} {{ totalRestaurants === 1 ? "Restaurant" : "Restaurants" }}</div>
                    </div>

                    <div v-if="isFiltering" class="restaurant-grid">
                        <article v-for="n in 6" :key="n" class="restaurant-card skeleton-card" aria-hidden="true">
                            <div class="restaurant-img skeleton"></div>
                            <div class="restaurant-body">
                                <div class="skeleton skeleton-line skeleton-title"></div>
                                <div class="skeleton skeleton-line"></div>
                                <div class="skeleton skeleton-line skeleton-short"></div>
                                <div class="restaurant-meta">
                                    <span class="skeleton skeleton-chip"></span>
                                    <span class="skeleton skeleton-chip"></span>
                                    <span class="skeleton skeleton-chip"></span>
                                </div>
                                <div class="skeleton skeleton-button"></div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="restaurant-grid">
                        <article v-for="(restaurant, index) in cards" :key="restaurant.id" class="restaurant-card">
                            <div class="restaurant-img" :class="{ 'restaurant-img--logo': restaurant.has_logo }">
                                <img :src="restaurantImage(restaurant, index)" :alt="restaurant.name" loading="lazy" />
                                <div class="rating">&#9733; {{ restaurant.rating }}</div>
                                <div class="food-type-tag">{{ restaurant.type_label }}</div>
                            </div>

                            <div class="restaurant-body">
                                <h3>{{ restaurant.name }}</h3>
                                <p>{{ restaurant.desc }}</p>

                                <div class="restaurant-meta">
                                    <span>{{ restaurant.location }}</span>
                                    <span>{{ restaurant.time }}</span>
                                    <span>{{ restaurant.price }}</span>
                                </div>

                                <button class="view-btn" type="button" @click="openRestaurant(restaurant)">View Restaurant</button>
                            </div>
                        </article>

                        <div v-if="!cards.length" class="no-results">
                            <h3>No restaurants found</h3>
                            <p>Try another food type or search keyword.</p>
                            <button class="clear-btn" type="button" @click="clearFilters">Clear Filters</button>
                        </div>
                    </div>

                    <div ref="loadMoreSentinel" class="load-more-sentinel" aria-hidden="true"></div>

                    <div v-if="isLoadingMore" class="restaurant-grid load-more-grid">
                        <article v-for="n in 3" :key="n" class="restaurant-card skeleton-card" aria-hidden="true">
                            <div class="restaurant-img skeleton"></div>
                            <div class="restaurant-body">
                                <div class="skeleton skeleton-line skeleton-title"></div>
                                <div class="skeleton skeleton-line"></div>
                                <div class="skeleton skeleton-line skeleton-short"></div>
                                <div class="skeleton skeleton-button"></div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        <MarketplaceFooterSections />
    </MultiVendorLayout>
</template>

<style scoped>
.restaurants-page {
    --primary: #c91a25;
    --black: #050505;
    --white: #ffffff;
    --text: #111827;
    --muted: #667085;
    --border: #e5e7eb;
    --soft-primary: rgba(201, 26, 37, 0.08);
    width: 100%;
    min-height: 100vh;
    overflow-x: hidden;
    background:
        radial-gradient(circle at 10% 8%, rgba(201, 26, 37, 0.10), transparent 30%),
        radial-gradient(circle at 90% 22%, rgba(201, 26, 37, 0.06), transparent 26%),
        #ffffff;
    color: var(--text);
}

.restaurants-container {
    width: min(1320px, calc(100% - 40px));
    margin: 0 auto;
}

.restaurants-hero {
    padding: 130px 0 28px;
    text-align: center;
}

.page-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 0 15px;
    border-radius: 999px;
    background: var(--soft-primary);
    border: 1px solid rgba(201, 26, 37, 0.22);
    color: var(--black);
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 20px;
}

.page-label span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary);
}

.restaurants-hero h1 {
    max-width: 760px;
    margin: 0 auto;
    color: var(--black);
    font-size: clamp(38px, 5vw, 66px);
    line-height: 1.04;
    font-weight: 700;
    letter-spacing: 0;
}

.restaurants-hero h1 span {
    color: var(--primary);
}

.hero-subtitle {
    margin: 22px auto 0;
    max-width: 650px;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.55;
}

.search-area {
    width: min(960px, 100%);
    margin: 26px auto 0;
    display: grid;
    grid-template-columns: 250px 250px 1fr auto;
    align-items: center;
    gap: 0;
    background: #ffffff;
    border: 1.5px solid var(--primary);
    border-radius: 999px;
    padding: 5px;
    box-shadow: 0 20px 60px rgba(201, 26, 37, 0.08);
}

.search-area label,
.service-dropdown {
    height: 54px;
    min-width: 0;
    border-right: 1px solid #e4e7ed;
}

.search-area label {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 22px;
    color: #111827;
}

.search-area__name {
    border-right: 0;
}

.service-dropdown {
    position: relative;
}

.service-trigger {
    width: 100%;
    height: 54px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 0;
    background: transparent;
    color: var(--black);
    padding: 0 18px 0 22px;
    cursor: pointer;
}

.service-trigger span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 800;
}

.service-trigger .is-placeholder {
    color: #111827;
}

.service-chevron {
    margin-left: auto;
    transition: transform 0.18s ease;
}

.service-dropdown.is-open .service-chevron {
    transform: rotate(180deg);
}

.service-menu {
    position: absolute;
    top: calc(100% + 12px);
    left: 8px;
    z-index: 20;
    width: calc(100% - 16px);
    overflow: hidden;
    border: 1px solid #f5b3ba;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 22px 50px rgba(17, 24, 39, 0.16);
}

.service-option {
    width: 100%;
    display: flex;
    align-items: center;
    border: 0;
    background: #ffffff;
    color: var(--black);
    padding: 13px 16px;
    font-size: 14px;
    font-weight: 800;
    text-align: left;
    cursor: pointer;
}

.service-option:hover,
.service-option.active {
    background: #fff0f1;
    color: var(--primary);
}

.search-area svg {
    flex: 0 0 auto;
    color: #111827;
}

.search-area input {
    height: 48px;
    border: none;
    outline: none;
    width: 100%;
    min-width: 0;
    padding: 0;
    background: transparent;
    color: var(--black);
    font-size: 14px;
    font-weight: 700;
}

.search-area input::placeholder {
    color: #111827;
    opacity: 0.75;
}

.field-clear-btn {
    flex: 0 0 auto;
    height: 30px;
    border: 0;
    border-radius: 999px;
    background: #fff0f1;
    color: var(--primary);
    padding: 0 10px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.search-submit-btn {
    height: 48px;
    min-width: 92px;
    padding: 0 24px;
    border: none;
    border-radius: 999px;
    background: var(--black);
    color: var(--white);
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    white-space: nowrap;
}

.food-type-section {
    padding: 12px 0 20px;
}

.food-type-filter-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin: 0 auto 14px;
    width: min(960px, 100%);
}

.food-type-filter-title h2 {
    margin: 0;
    color: var(--black);
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0;
}

.food-pill-arrows {
    display: flex;
    align-items: center;
    gap: 10px;
}

.food-pill-arrow {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    border: 1.5px solid rgba(201, 26, 37, 0.25);
    background: #ffffff;
    color: var(--primary);
    cursor: pointer;
    display: grid;
    place-items: center;
}

.food-type-pills {
    display: flex;
    align-items: center;
    gap: 14px;
    width: min(960px, 100%);
    margin: 0 auto;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 8px 2px 14px;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    touch-action: pan-x;
}

.food-type-pills::-webkit-scrollbar {
    display: none;
}

.food-pill {
    flex: 0 0 auto;
    min-height: 54px;
    padding: 7px 20px 7px 7px;
    border-radius: 999px;
    border: 2px solid rgba(17, 24, 39, 0.12);
    background: #ffffff;
    color: var(--black);
    display: inline-flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    white-space: nowrap;
}

.food-pill.active {
    border-color: var(--primary);
}

.all-types-pill {
    padding: 7px 28px;
}

.food-pill-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--soft-primary);
    flex: 0 0 auto;
}

.food-pill-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.restaurants-section {
    padding: 58px 0 90px;
}

.restaurants-top {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 24px;
}

.restaurants-top h2 {
    margin: 0;
    color: var(--black);
    font-size: clamp(28px, 3vw, 42px);
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: 0;
}

.restaurants-top p {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: 14px;
}

.result-count {
    flex: 0 0 auto;
    min-height: 40px;
    padding: 0 16px;
    border-radius: 999px;
    background: var(--soft-primary);
    border: 1px solid rgba(201, 26, 37, 0.15);
    color: var(--primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 900;
}

.restaurant-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.restaurant-card {
    background: #ffffff;
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 26px;
    overflow: hidden;
    box-shadow: 0 18px 60px rgba(0, 0, 0, 0.06);
    min-height: 100%;
    display: flex;
    flex-direction: column;
}

.restaurant-img {
    height: 210px;
    position: relative;
    overflow: hidden;
    background: var(--soft-primary);
}

.restaurant-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.restaurant-img--logo {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 28px;
    background: #fff2f3;
}

.restaurant-img--logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.rating {
    position: absolute;
    top: 14px;
    left: 14px;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    background: #ffffff;
    color: var(--black);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 900;
    z-index: 2;
}

.food-type-tag {
    position: absolute;
    right: 14px;
    bottom: 14px;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    background: var(--primary);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    z-index: 2;
}

.restaurant-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.restaurant-body h3 {
    margin: 0 0 8px;
    color: var(--black);
    font-size: 20px;
    font-weight: 800;
}

.restaurant-body p {
    min-height: 42px;
    margin: 0 0 18px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.6;
}

.restaurant-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 18px;
}

.restaurant-meta span {
    min-height: 30px;
    padding: 0 11px;
    border-radius: 999px;
    background: #f8f8f8;
    border: 1px solid var(--border);
    color: #344054;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
}

.view-btn {
    width: 100%;
    height: 46px;
    margin-top: auto;
    border-radius: 999px;
    border: none;
    background: var(--black);
    color: #ffffff;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
}

.no-results {
    grid-column: 1 / -1;
    padding: 60px 20px;
    border-radius: 26px;
    background: #ffffff;
    border: 1px dashed rgba(201, 26, 37, 0.35);
    text-align: center;
}

.no-results h3 {
    margin: 0 0 8px;
    color: var(--black);
    font-size: 24px;
    font-weight: 900;
}

.no-results p {
    margin: 0 0 18px;
    color: var(--muted);
    font-size: 14px;
}

.clear-btn {
    height: 44px;
    padding: 0 22px;
    border-radius: 999px;
    border: none;
    background: var(--black);
    color: #ffffff;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
}

.load-more-sentinel {
    height: 1px;
}

.load-more-grid {
    margin-top: 24px;
}

.skeleton {
    background: linear-gradient(90deg, rgba(17, 24, 39, 0.06) 25%, rgba(17, 24, 39, 0.12) 37%, rgba(17, 24, 39, 0.06) 63%);
    background-size: 700% 200%;
    animation: skeleton-wave 0.8s ease-in-out infinite;
}

.skeleton-line {
    height: 14px;
    border-radius: 999px;
    margin-bottom: 12px;
}

.skeleton-title {
    width: 70%;
    height: 22px;
}

.skeleton-short {
    width: 58%;
}

.skeleton-chip {
    width: 78px;
    border: 0;
}

.skeleton-button {
    height: 46px;
    border-radius: 999px;
}

@keyframes skeleton-wave {
    0% {
        background-position: 100% 0;
    }

    100% {
        background-position: 0 0;
    }
}

@media (max-width: 1050px) {
    .restaurant-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .food-pill {
        min-height: 64px;
        font-size: 15px;
    }

    .food-pill-img {
        width: 46px;
        height: 46px;
    }

    .search-area {
        grid-template-columns: 1fr 1fr;
        border-radius: 28px;
    }

    .search-area label:first-of-type {
        border-right: 0;
    }

    .search-area__name {
        border-top: 1px solid #e4e7ed;
    }

    .search-submit-btn {
        margin: 4px;
    }
}

@media (max-width: 760px) {
    .restaurants-hero {
        padding-top: 128px;
    }

    .search-area {
        grid-template-columns: 1fr;
        border-radius: 24px;
    }

    .search-area label {
        border-right: 0;
        border-bottom: 1px solid #e4e7ed;
    }

    .service-dropdown {
        border-right: 0;
        border-bottom: 1px solid #e4e7ed;
    }

    .search-area__name {
        border-top: 0;
    }

    .search-submit-btn {
        width: 100%;
    }

    .restaurants-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .restaurant-grid {
        grid-template-columns: 1fr;
    }

    .restaurant-img {
        height: 230px;
    }
}

@media (max-width: 640px) {
    .restaurants-container {
        width: min(100% - 24px, 1320px);
    }

    .food-type-filter-title {
        align-items: flex-start;
        flex-direction: column;
    }

    .food-pill-arrow {
        width: 42px;
        height: 42px;
        border-radius: 14px;
    }

    .food-pill {
        min-height: 58px;
        padding: 8px 20px 8px 8px;
        gap: 12px;
        font-size: 14px;
    }

    .all-types-pill {
        padding: 8px 24px;
    }

    .food-pill-img {
        width: 42px;
        height: 42px;
    }
}
</style>
