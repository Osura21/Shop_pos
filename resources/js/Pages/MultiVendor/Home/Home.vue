<script>
import { Head, Link, router } from "@inertiajs/vue3"
import {
    ArrowLeft,
    ArrowRight,
    ChevronDown,
    ClipboardList,
    Heart,
    List,
    MapPin,
    Menu,
    Minus,
    Plus,
    Search,
    Store,
    Utensils,
    Zap,
} from "lucide-vue-next"
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"

export default {
    name: "MultiVendorHome",

    components: {
        ArrowLeft,
        ArrowRight,
        ChevronDown,
        ClipboardList,
        Head,
        Heart,
        Link,
        List,
        MapPin,
        Menu,
        Minus,
        MultiVendorLayout,
        Plus,
        Search,
        Store,
        Utensils,
        Zap,
    },

    props: {
        vendors: { type: Array, default: () => [] },
        foodTypes: { type: Array, default: () => [] },
        featuredProducts: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
        stats: { type: Object, default: () => ({}) },
        seoFooterSections: { type: Array, default: () => [] },
    },

    data() {
        return {
            fallbackVendors: [],
            searchQuery: this.filters.search || "",
            selectedType: this.filters.food_type || "",
            deliveryMode: this.filters.delivery || "",
            locationQuery: this.filters.location || "",
            countryFilter: this.filters.country || "",
            serviceDropdownOpen: false,
            openFaq: 2,
            activeCountry: this.filters.country || "",
            faqs: [
                ["How do I order food through Sappy Eats?", "Search by restaurant, food type, or location, then open the vendor shop to place your order."],
                ["Can restaurants manage their own menus?", "Yes. Vendors manage products, categories, availability, branches, and POS activity from their dashboard."],
                ["Can customers schedule a food delivery?", "Yes. Customers can choose instant delivery, pickup, or scheduled delivery based on restaurant availability."],
                ["Do you provide support for food shop owners?", "Vendor requests can be submitted online, and approved partners get access to the restaurant admin tools."],
                ["Is Sappy Eats suitable for small food shops?", "Yes. The public site is designed to keep discovery fast even as vendors and menus grow."],
            ],
        }
    },

    computed: {
        displayTypes() {
            return this.foodTypes
        },

        hasActiveFilter() {
            return Boolean(this.searchQuery || this.selectedType || this.deliveryMode || this.locationQuery || this.countryFilter)
        },

        displayVendors() {
            if (this.vendors.length) return this.vendors
            return this.hasActiveFilter ? [] : this.fallbackVendors
        },

        activeTypeName() {
            if (!this.selectedType) return "Recommended for you"
            return this.displayTypes.find((type) => type.slug === this.selectedType)?.name || "Recommended for you"
        },

        matchedCount() {
            return this.stats.vendors || this.displayVendors.length
        },

        footerCountries() {
            return this.seoFooterSections.map((section) => section.country)
        },

        activeFooterSection() {
            return this.seoFooterSections.find((section) => section.country === this.activeCountry)
                || this.seoFooterSections[0]
                || null
        },

        activeFooterCities() {
            return this.activeFooterSection?.cities || []
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

    mounted() {
        this.syncFiltersFromProps()
        document.addEventListener("click", this.closeServiceDropdown)
    },

    beforeUnmount() {
        document.removeEventListener("click", this.closeServiceDropdown)
    },

    watch: {
        filters: {
            deep: true,
            handler() {
                this.syncFiltersFromProps()
            },
        },
    },

    methods: {
        syncFiltersFromProps() {
            this.searchQuery = this.filters.search || ""
            this.selectedType = this.filters.food_type || ""
            this.deliveryMode = this.filters.delivery || ""
            this.locationQuery = this.filters.location || ""
            this.countryFilter = this.filters.country || ""
            if (this.countryFilter) {
                this.activeCountry = this.countryFilter
            } else if (!this.activeCountry && this.footerCountries.length) {
                this.activeCountry = this.footerCountries[0]
            }
        },

        submitFilters() {
            router.get("/", {
                search: this.searchQuery || undefined,
                food_type: this.selectedType || undefined,
                delivery: this.deliveryMode || undefined,
                location: this.locationQuery || undefined,
                country: this.countryFilter || undefined,
            }, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                only: ["vendors", "foodTypes", "featuredProducts", "filters", "stats"],
                onSuccess: () => this.syncFiltersFromProps(),
            })
        },

        chooseFooterCountry(country) {
            this.activeCountry = country
        },

        applyFooterLink(link) {
            this.selectedType = link.food_type || ""
            this.deliveryMode = link.delivery || ""
            this.locationQuery = link.location || ""
            this.countryFilter = link.country || this.activeCountry || ""
            router.get(this.restaurantFilterUrl(), {}, {
                preserveScroll: false,
                preserveState: false,
            })
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
                food_type: this.selectedType,
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

        toggleServiceDropdown() {
            this.serviceDropdownOpen = !this.serviceDropdownOpen
        },

        chooseService(value) {
            this.deliveryMode = value
            this.serviceDropdownOpen = false
            this.submitFilters()
        },

        clearFilters() {
            this.searchQuery = ""
            this.selectedType = ""
            this.deliveryMode = ""
            this.locationQuery = ""
            this.countryFilter = ""
            this.serviceDropdownOpen = false
            this.submitFilters()
        },

        closeServiceDropdown(event) {
            if (!this.$refs.serviceDropdown || this.$refs.serviceDropdown.contains(event.target)) return
            this.serviceDropdownOpen = false
        },

        chooseType(slug) {
            this.selectedType = this.selectedType === slug ? "" : slug
            this.submitFilters()
        },

        scrollFoodTypes(direction) {
            const el = this.$refs.foodTypeRail
            if (!el) return

            el.scrollBy({
                left: direction * Math.max(220, el.clientWidth * 0.75),
                behavior: "smooth",
            })
        },

        vendorImage(vendor, index) {
            return vendor.logo_url || [
                "/multivendor/product.webp",
                "/multivendor/stock.webp",
                "/multivendor/contact.webp",
                "/multivendor/post-ad2.webp",
            ][index % 4]
        },

        openVendor(vendor) {
            if (!vendor?.url) return

            router.visit(vendor.url, {
                preserveScroll: false,
                preserveState: false,
            })
        },

        toggleFaq(index) {
            this.openFaq = this.openFaq === index ? -1 : index
        },

        scrollToSection(id) {
            document.getElementById(id)?.scrollIntoView({
                behavior: "smooth",
                block: "start",
            })
        },
    },
}
</script>

<template>
    <Head title="Sappy Eats Food Marketplace" />

    <MultiVendorLayout>
        <main class="sappy-page">
            <section class="hero-section" id="restaurants">
                <div class="hero-shell">
                    <div class="hero-pill">
                        <span></span>
                        Fast food delivery booking platform
                    </div>

                    <h1>
                        Order Food Faster And
                        <br />
                        Grow Your <strong>Food Business</strong>
                    </h1>

                    <p>
                        Stop wasting time with phone calls and manual order tracking. Sappy Eats helps customers
                        discover restaurants, book meals, schedule deliveries, and track orders in one simple platform.
                    </p>

                    <div class="food-type-head">
                        <h2>Choose Food Type</h2>
                        <div class="arrow-pair">
                            <button type="button" aria-label="Previous food types" @click="scrollFoodTypes(-1)"><ArrowLeft size="16" /></button>
                            <button type="button" aria-label="Next food types" @click="scrollFoodTypes(1)"><ArrowRight size="16" /></button>
                        </div>
                    </div>

                    <div ref="foodTypeRail" class="food-types" aria-label="Choose food type filters">
                        <button
                            type="button"
                            class="food-chip food-chip--all"
                            :class="{ 'food-chip--active': !selectedType }"
                            @click="chooseType('')"
                        >
                            <span>All Types</span>
                        </button>

                        <button
                            v-for="type in displayTypes"
                            :key="type.slug"
                            type="button"
                            class="food-chip"
                            :class="{ 'food-chip--active': selectedType === type.slug }"
                            @click="chooseType(type.slug)"
                        >
                            <img :src="type.image_url || '/multivendor/product.webp'" :alt="type.name" loading="lazy" />
                            <span>{{ type.name }}</span>
                        </button>
                    </div>

                    <form class="search-bar" @submit.prevent="submitFilters">
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
                            <input v-model="locationQuery" type="search" placeholder="Location" aria-label="Location" />
                        </label>
                        <label class="search-bar__name">
                            <Search size="19" />
                            <input v-model="searchQuery" type="search" placeholder="Search Restaurant Name" aria-label="Search restaurant name" />
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

            <section class="recommended-shell" aria-labelledby="recommended-title">
                <div class="recommended-card">
                    <div class="section-row">
                        <div>
                            <h2 id="recommended-title">{{ activeTypeName }}</h2>
                            <p>{{ matchedCount }} food shops matched your search</p>
                        </div>
                        <div class="arrow-pair">
                            <button type="button"><ArrowLeft size="16" /></button>
                            <button type="button"><ArrowRight size="16" /></button>
                        </div>
                    </div>

                    <div v-if="displayVendors.length" class="vendor-row">
                        <article v-for="(vendor, index) in displayVendors.slice(0, 4)" :key="vendor.id" class="vendor-card">
                            <button
                                type="button"
                                class="vendor-link"
                                :class="{ 'vendor-link--disabled': !vendor.url }"
                                @click="openVendor(vendor)"
                            >
                                <div class="vendor-image">
                                    <img :src="vendorImage(vendor, index)" :alt="vendor.name" loading="lazy" />
                                    <span class="rating">{{ vendor.rating }} ★</span>
                                    <span class="category">{{ (vendor.categories || [])[0] || 'Restaurant' }}</span>
                                </div>
                                <h3>{{ vendor.name }}</h3>
                                <p>{{ vendor.address || [vendor.city, 'Fast delivery'].filter(Boolean).join(' • ') }}</p>
                            </button>
                        </article>
                    </div>

                    <div v-else class="empty-vendors">
                        <h3>No restaurants matched</h3>
                        <p>Try a different food type, restaurant name, or location.</p>
                    </div>
                </div>
            </section>

            <section class="why-section" id="features">
                <div class="section-title">
                    <h2>Why Food Businesses Choose Sappy Eats</h2>
                    <p>Simplify online orders, manage deliveries, and grow your restaurant without the hassle of manual order handling.</p>
                </div>

                <div class="feature-grid">
                    <article>
                        <span><Zap size="22" /></span>
                        <h3>Seamless Ordering</h3>
                        <p>Let customers order food online, schedule delivery, or select pickup with a smooth booking process.</p>
                    </article>
                    <article>
                        <span><List size="22" /></span>
                        <h3>Order Management</h3>
                        <p>Manage incoming orders, kitchen status, delivery updates, and completed bookings from one dashboard.</p>
                    </article>
                    <article>
                        <span><Heart size="22" /></span>
                        <h3>Customer Retention</h3>
                        <p>Build loyalty with repeat orders, ratings, offers, customer history, and quick reorder features.</p>
                    </article>
                </div>
            </section>

            <section class="track-section" id="delivery-network">
                <div class="dashboard-mock">
                    <div class="mock-header">Sappy <strong>Eats</strong></div>
                    <div class="mock-body">
                        <aside>
                            <span></span><span></span><span class="active"></span><span></span><span></span>
                        </aside>
                        <div class="mock-orders">
                            <nav><span>Today</span><span>Orders</span><span>Team</span></nav>
                            <article><Utensils size="25" /><div><b>Cheesy Chicken Pizza</b><small>Order #1042 • Colombo 03</small></div><em>Preparing</em></article>
                            <article><Store size="25" /><div><b>Double Beef Burger</b><small>Order #1043 • Nugegoda</small></div><em>On Delivery</em></article>
                            <article><Menu size="25" /><div><b>Rice & Curry Meal</b><small>Order #1044 • Kandy</small></div><em>Completed</em></article>
                        </div>
                    </div>
                </div>

                <div class="track-copy">
                    <h2>Track Every Order With Confidence, Clarity, And Full Control</h2>
                    <p>Our platform gives restaurants and food shops the tools they need to manage every food order, payment, pickup, and delivery in real time.</p>
                    <button type="button" class="section-nav-btn" @click="scrollToSection('restaurants')">Learn More</button>
                </div>
            </section>

            <section class="faq-section" id="why-us">
                <div class="faq-intro">
                    <h2>Frequently Asked Questions</h2>
                    <p>Have another question? Please contact our team.</p>
                    <Link href="/contact">Contact Our Team</Link>
                </div>
                <div class="faq-list">
                    <article v-for="(faq, index) in faqs" :key="faq[0]" :class="{ active: openFaq === index }">
                        <button type="button" @click="toggleFaq(index)">
                            {{ faq[0] }}
                            <span class="faq-icon">
                                <Minus v-if="openFaq === index" size="18" />
                                <Plus v-else size="18" />
                            </span>
                        </button>
                        <Transition name="faq-collapse">
                            <div v-if="openFaq === index" class="faq-answer">
                                <p>{{ faq[1] }}</p>
                            </div>
                        </Transition>
                    </article>
                </div>
            </section>

            <section class="cta-section" id="pricing">
                <h2>Ready To Grow Your Food Business?</h2>
                <p>Start receiving online orders, managing deliveries, and reaching more hungry customers with Sappy Eats.</p>
                <Link href="/seller/register">Become a Partner</Link>
            </section>

            <section class="browse-section" id="franchise">
                <h2>Browse By City</h2>
                <div class="country-tabs">
                    <button
                        v-for="country in footerCountries"
                        :key="country"
                        type="button"
                        :class="{ active: activeCountry === country }"
                        @click="chooseFooterCountry(country)"
                    >
                        {{ country }}
                    </button>
                </div>
                <div v-if="activeFooterCities.length" class="city-grid">
                    <article v-for="city in activeFooterCities" :key="city.location">
                        <h3>{{ city.location }}</h3>
                        <button
                            v-for="link in city.links"
                            :key="`${city.location}-${link.label}`"
                            type="button"
                            @click="applyFooterLink(link)"
                        >
                            {{ link.label }}
                        </button>
                    </article>
                </div>
            </section>
        </main>
    </MultiVendorLayout>
</template>

<style scoped>
.sappy-page {
    --red: #df1f2d;
    --dark: #111827;
    --muted: #657089;
    color: var(--dark);
    background: linear-gradient(90deg, #fff0f1 0 27%, #fff 52%);
    font-family: "Noto Sans", sans-serif;
    overflow-x: hidden;
}

.hero-section {
    height: auto;
    min-height: 760px;
    padding: 156px 24px 54px;
    border-top: 1px solid #f2d4d7;
}

.hero-shell,
.recommended-shell,
.why-section,
.track-section,
.faq-section,
.browse-section {
    width: min(100% - 48px, 1320px);
    margin: 0 auto;
}

.hero-shell {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #ffa3ab;
    border-radius: 999px;
    padding: 8px 16px;
    background: #fff3f4;
    font-weight: 700;
    font-size: 14px;
}

.hero-pill span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--red);
}

.hero-section h1 {
    margin: 24px 0 20px;
    font-size: clamp(42px, 5vw, 72px);
    line-height: 1;
    font-weight: 900;
    letter-spacing: 0;
}

.hero-section h1 strong {
    color: #cf1928;
}

.hero-section p,
.section-title p,
.track-copy p,
.faq-intro p {
    color: var(--muted);
    line-height: 1.7;
}

.hero-section > .hero-shell > p {
    max-width: 660px;
}

.food-type-head,
.section-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    width: 100%;
    gap: 20px;
}

.food-type-head {
    max-width: 810px;
    margin-top: 44px;
}

.food-type-head h2,
.recommended-card h2,
.browse-section h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 900;
}

.arrow-pair {
    display: flex;
    gap: 10px;
}

.arrow-pair button {
    width: 32px;
    height: 32px;
    border: 1px solid #fa8f99;
    border-radius: 12px;
    background: #fff;
    color: var(--red);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.arrow-pair button:hover {
    background: var(--red);
    border-color: var(--red);
    color: #ffffff;
    transform: translateY(-1px);
}

.food-types {
    width: min(100%, 810px);
    display: flex;
    gap: 16px;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 24px 2px 32px;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    touch-action: pan-x;
}

.food-types::-webkit-scrollbar {
    display: none;
}

.food-chip {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 11px;
    min-width: 112px;
    height: 50px;
    border: 1px solid #e6e8ee;
    border-radius: 999px;
    background: #fff;
    padding: 6px 18px 6px 7px;
    font-size: 14px;
    font-weight: 900;
    transition: background 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease, color 0.22s ease, transform 0.22s ease;
}

.food-chip:hover {
    border-color: #fa8f99;
    box-shadow: 0 12px 28px rgba(223, 31, 45, 0.1);
    transform: translateY(-1px);
}

.food-chip--all {
    min-width: 108px;
    justify-content: center;
    padding: 6px 24px;
}

.food-chip img {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
}

.food-chip--active {
    border-color: var(--red);
    background: #ffffff;
    box-shadow: inset 0 0 0 1px var(--red);
    color: var(--red);
}

.search-bar {
    width: min(100%, 810px);
    display: grid;
    grid-template-columns: 230px 225px minmax(220px, 1fr) auto;
    align-items: center;
    margin-top: 4px;
    padding: 5px;
    border: 1.5px solid var(--red);
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(223, 31, 45, 0.08);
}

.search-bar label,
.service-dropdown {
    height: 50px;
    min-width: 0;
    border-right: 1px solid #e7e9ef;
}

.search-bar label {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 20px;
}

.service-dropdown {
    position: relative;
}

.service-trigger {
    width: 100%;
    height: 50px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 0;
    background: transparent;
    color: var(--black);
    padding: 0 16px 0 20px;
    cursor: pointer;
}

.service-trigger span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 900;
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
    color: var(--red);
}

.search-bar input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--black);
    font-size: 14px;
    font-weight: 800;
}

.search-bar input::placeholder {
    color: #4b5563;
    opacity: 0.9;
}

.field-clear-btn {
    flex: 0 0 auto;
    height: 28px;
    border: 0;
    border-radius: 999px;
    background: #fff0f1;
    color: var(--red);
    padding: 0 10px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.track-copy a,
.section-nav-btn,
.faq-intro a,
.cta-section a {
    border: 0;
    background: #050505;
    color: #fff;
    border-radius: 999px;
    padding: 15px 27px;
    font-weight: 900;
    text-decoration: none;
    white-space: nowrap;
}

.search-submit-btn {
    height: 44px;
    min-width: 86px;
    padding: 0 22px;
    border: 0;
    border-radius: 999px;
    font-weight: 900;
    white-space: nowrap;
    background: #050505;
    color: #fff;
}

.section-nav-btn {
    display: inline-flex;
}

.recommended-shell {
    margin-top: -8px;
    margin-bottom: 92px;
}

.recommended-card {
    background: #fff;
    border: 1px solid #eef0f5;
    border-radius: 24px;
    padding: 36px;
    box-shadow: 0 34px 80px rgba(17, 24, 39, 0.08);
}

.recommended-card .section-row {
    margin-bottom: 26px;
}

.recommended-card p {
    margin: 18px 0 0;
    color: var(--muted);
}

.vendor-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
}

.vendor-link {
    display: block;
    width: 100%;
    border: 0;
    background: transparent;
    padding: 0;
    color: inherit;
    font: inherit;
    text-align: left;
    text-decoration: none;
}

.vendor-link--disabled {
    cursor: default;
}

.vendor-image {
    position: relative;
    aspect-ratio: 1.78;
    overflow: hidden;
    border-radius: 14px;
    background: #f2f3f5;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
}

.vendor-image img {
    width: 100%;
    height: 100%;
    max-width: 82%;
    max-height: 76%;
    object-fit: contain;
    display: block;
}

.rating,
.category {
    position: absolute;
    z-index: 1;
    font-weight: 900;
    font-size: 12px;
}

.rating {
    top: 12px;
    left: 12px;
    background: #fff;
    border-radius: 999px;
    padding: 6px 12px;
}

.category {
    right: 12px;
    bottom: 12px;
    color: #fff;
    background: var(--red);
    border-radius: 999px;
    padding: 7px 12px;
}

.vendor-card h3 {
    margin: 14px 0 12px;
    font-size: 16px;
    font-weight: 900;
}

.vendor-card p {
    margin: 0;
    font-size: 14px;
    line-height: 1.45;
}

.empty-vendors {
    border: 1px dashed #d8dce5;
    border-radius: 14px;
    padding: 36px;
    text-align: center;
    background: #fffafa;
}

.empty-vendors h3 {
    margin: 0 0 10px;
    font-weight: 900;
}

.empty-vendors p {
    margin: 0;
}

.why-section,
.faq-section {
    padding: 26px 0 80px;
}

.section-title {
    text-align: center;
    margin-bottom: 46px;
}

.section-title h2,
.track-copy h2,
.faq-intro h2,
.cta-section h2 {
    margin: 0;
    font-size: clamp(34px, 4vw, 46px);
    line-height: 1.15;
    font-weight: 900;
}

.section-title p {
    max-width: 540px;
    margin: 18px auto 0;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 96px;
}

.feature-grid article {
    min-height: 230px;
    border: 1px solid #ffb9bf;
    border-radius: 8px;
    padding: 26px;
    background: rgba(255, 255, 255, 0.68);
}

.feature-grid span {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    color: var(--red);
    background: #fff0f1;
}

.feature-grid h3 {
    margin: 26px 0 12px;
    font-size: 19px;
    font-weight: 900;
}

.feature-grid p {
    color: var(--muted);
    line-height: 1.6;
}

.track-section {
    display: grid;
    grid-template-columns: minmax(0, 570px) 1fr;
    gap: 60px;
    align-items: center;
    padding: 14px 0 84px;
}

.dashboard-mock {
    overflow: hidden;
    border: 1px solid #d9dce3;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 32px 80px rgba(17, 24, 39, 0.08);
}

.mock-header {
    padding: 18px 20px;
    border-bottom: 1px solid #e6e8ee;
    font-weight: 900;
    font-size: 18px;
}

.mock-header strong {
    color: var(--red);
}

.mock-body {
    display: grid;
    grid-template-columns: 70px 1fr;
}

.mock-body aside {
    background: #050505;
    min-height: 356px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.mock-body aside span {
    width: 28px;
    height: 28px;
    border-radius: 9px;
    background: #282828;
}

.mock-body aside .active {
    background: var(--red);
}

.mock-orders {
    padding: 20px;
}

.mock-orders nav,
.mock-orders article {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.mock-orders nav span,
.mock-orders em {
    border-radius: 999px;
    background: #fff3f4;
    padding: 8px 18px;
    font-style: normal;
    font-weight: 900;
    font-size: 12px;
}

.mock-orders article {
    margin-top: 16px;
    border: 1px solid #e6e8ee;
    border-radius: 14px;
    padding: 16px;
}

.mock-orders article > svg {
    color: var(--red);
    background: #fff3f4;
    border: 1px solid #ffc6cb;
    border-radius: 12px;
    box-sizing: content-box;
    padding: 10px;
}

.mock-orders b,
.mock-orders small {
    display: block;
}

.mock-orders small {
    color: var(--muted);
}

.track-copy p {
    margin: 24px 0 28px;
    font-size: 16px;
}

.faq-section {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 102px;
    align-items: flex-start;
}

.faq-intro p {
    margin: 14px 0 22px;
}

.faq-list {
    display: grid;
    gap: 14px;
}

.faq-list article {
    border: 1px solid #e5e8ee;
    border-radius: 14px;
    background: #fff;
    padding: 0 20px;
    overflow: hidden;
    transition: border-color 0.24s ease, box-shadow 0.24s ease, background 0.24s ease, transform 0.24s ease;
}

.faq-list article.active {
    border-color: #ff9fa7;
    box-shadow: 0 24px 70px rgba(223, 31, 45, 0.08);
    background: #fffafa;
}

.faq-list button {
    width: 100%;
    min-height: 60px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    border: 0;
    background: transparent;
    text-align: left;
    font-weight: 900;
}

.faq-icon {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #050505;
    transition: color 0.2s ease, transform 0.2s ease;
}

.faq-list article.active .faq-icon {
    color: var(--red);
    transform: rotate(180deg);
}

.faq-answer {
    overflow: hidden;
}

.faq-list p {
    margin: 0 0 22px;
    color: var(--muted);
    line-height: 1.7;
}

.faq-collapse-enter-active,
.faq-collapse-leave-active {
    transition: max-height 0.28s ease, opacity 0.22s ease, transform 0.28s ease;
    max-height: 120px;
}

.faq-collapse-enter-from,
.faq-collapse-leave-to {
    max-height: 0;
    opacity: 0;
    transform: translateY(-6px);
}

.faq-collapse-enter-to,
.faq-collapse-leave-from {
    max-height: 120px;
    opacity: 1;
    transform: translateY(0);
}

.cta-section {
    width: min(100% - 48px, 1320px);
    margin: 0 auto 72px;
    padding: 56px 24px;
    text-align: center;
    color: #fff;
    border-radius: 18px;
    background: linear-gradient(135deg, #b91422, #ef3342, #b8121f);
}

.cta-section p {
    max-width: 560px;
    margin: 20px auto 30px;
}

.cta-section a {
    display: inline-flex;
    background: #fff;
    color: #050505;
}

.browse-section {
    padding-bottom: 90px;
}

.country-tabs {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin: 24px 0 18px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e4e7ed;
}

.country-tabs button {
    border: 0;
    border-radius: 999px;
    background: transparent;
    padding: 11px 18px;
    font-weight: 900;
}

.country-tabs .active {
    background: #050505;
    color: #fff;
}

.city-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 42px;
}

.city-grid h3 {
    font-size: 16px;
    font-weight: 900;
    margin: 10px 0 14px;
}

.city-grid a,
.city-grid button {
    display: block;
    border: 0;
    background: transparent;
    padding: 0;
    margin: 10px 0;
    color: #36465b;
    text-decoration: none;
    font-size: 14px;
    text-align: left;
}

@media (max-width: 1120px) {
    .vendor-row,
    .feature-grid,
    .track-section,
    .faq-section,
    .city-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .search-bar {
        grid-template-columns: 1fr 1fr;
        border-radius: 24px;
    }

    .search-bar label:first-of-type {
        border-right: 0;
    }

    .search-bar__name {
        border-top: 1px solid #e7e9ef;
    }
}

@media (max-width: 760px) {
    .hero-shell,
    .recommended-shell,
    .why-section,
    .track-section,
    .faq-section,
    .browse-section,
    .cta-section {
        width: min(100% - 32px, 1320px);
    }

    .hero-section {
        padding: 120px 0 44px;
        min-height: auto;
    }

    .hero-section h1 {
        font-size: 42px;
    }

    .food-type-head,
    .section-row {
        align-items: flex-start;
        flex-direction: column;
    }

    .search-bar,
    .vendor-row,
    .feature-grid,
    .track-section,
    .faq-section,
    .city-grid {
        grid-template-columns: 1fr;
    }

    .search-bar label {
        border-right: 0;
        border-bottom: 1px solid #e7e9ef;
    }

    .service-dropdown {
        border-right: 0;
        border-bottom: 1px solid #e7e9ef;
    }

    .search-bar__name {
        border-top: 0;
    }

    .search-submit-btn {
        width: 100%;
    }

    .recommended-card {
        padding: 24px;
    }

    .feature-grid,
    .faq-section {
        gap: 20px;
    }

    .mock-body {
        grid-template-columns: 52px 1fr;
    }

    .mock-body aside {
        padding: 16px 12px;
    }

    .mock-orders nav {
        display: none;
    }

    .mock-orders article {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
