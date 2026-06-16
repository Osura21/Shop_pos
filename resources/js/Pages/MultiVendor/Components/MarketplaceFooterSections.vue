<script setup>
import { computed, ref, watch } from "vue"
import { Link, router, usePage } from "@inertiajs/vue3"

const page = usePage()
const sections = computed(() => page.props.seoFooterSections || [])
const countries = computed(() => sections.value.map((section) => section.country))
const activeCountry = ref(countries.value[0] || "")
const activeSection = computed(() => sections.value.find((section) => section.country === activeCountry.value) || sections.value[0] || null)
const activeCities = computed(() => activeSection.value?.cities || [])

watch(countries, (value) => {
    if (!activeCountry.value && value.length) {
        activeCountry.value = value[0]
    }
})

function applyFooterLink(link) {
    router.get(restaurantFilterUrl({
        food_type: link.food_type || "",
        delivery: link.delivery || "",
        location: link.location || "",
        country: link.country || activeCountry.value || "",
    }), {}, {
        preserveScroll: false,
        preserveState: false,
    })
}

function slug(value) {
    return String(value || "")
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "")
}

function restaurantFilterUrl(filters) {
    const parts = []
    const country = slug(filters.country)
    const location = slug(filters.location)
    const foodType = slug(filters.food_type)
    const delivery = slug(filters.delivery)

    if (country) parts.push(country)
    if (location) parts.push(location)
    if (foodType && delivery) {
        parts.push(`${foodType}-${delivery}`)
    } else if (foodType) {
        parts.push(foodType)
    } else if (delivery) {
        parts.push(delivery)
    }

    return parts.length ? `/resturents/${parts.join("/")}` : "/resturents"
}
</script>

<template>
    <section class="market-footer-sections">
        <div class="market-footer-sections__inner">
            <section class="food-grow-cta">
                <h2>Ready To Grow Your Food Business?</h2>
                <p>Start receiving online orders, managing deliveries, and reaching more hungry customers with Sappy Eats.</p>
                <Link href="/seller/register">Become a Partner</Link>
            </section>

            <section class="city-browser" aria-labelledby="browse-city-title">
                <h2 id="browse-city-title">Browse By City</h2>
                <div class="country-tabs">
                    <button
                        v-for="country in countries"
                        :key="country"
                        type="button"
                        :class="{ active: activeCountry === country }"
                        @click="activeCountry = country"
                    >
                        {{ country }}
                    </button>
                </div>
                <div v-if="activeCities.length" class="city-grid">
                    <article v-for="city in activeCities" :key="city.location">
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
        </div>
    </section>
</template>

<style scoped>
.market-footer-sections {
    background: #ffffff;
    padding: 104px 0 110px;
}

.market-footer-sections__inner {
    width: min(100% - 48px, 1320px);
    margin: 0 auto;
}

.food-grow-cta {
    margin: 0 auto 70px;
    padding: 56px 24px;
    text-align: center;
    color: #ffffff;
    border-radius: 18px;
    background: linear-gradient(135deg, #b91422, #ef3342, #b8121f);
}

.food-grow-cta h2 {
    margin: 0;
    font-size: clamp(34px, 4vw, 48px);
    line-height: 1.15;
    font-weight: 900;
    letter-spacing: 0;
}

.food-grow-cta p {
    max-width: 560px;
    margin: 20px auto 30px;
    line-height: 1.55;
    font-weight: 700;
}

.food-grow-cta a {
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 0 28px;
    color: #050505;
    background: #ffffff;
    text-decoration: none;
    font-size: 13px;
    font-weight: 900;
}

.city-browser h2 {
    margin: 0;
    color: #050505;
    font-size: 20px;
    font-weight: 900;
    letter-spacing: 0;
}

.country-tabs {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    margin: 20px 0 0;
    padding-bottom: 18px;
    border-bottom: 1px solid #e4e7ed;
}

.country-tabs button {
    min-height: 38px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    padding: 0 20px;
    color: #050505;
    font-weight: 900;
}

.country-tabs .active {
    background: #050505;
    color: #ffffff;
}

.city-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 42px;
    margin-top: 26px;
}

.city-grid h3 {
    color: #050505;
    font-size: 15px;
    font-weight: 900;
    margin: 0 0 14px;
}

.city-grid button {
    display: block;
    border: 0;
    background: transparent;
    padding: 0;
    margin: 10px 0;
    color: #36465b;
    font-size: 14px;
    text-align: left;
}

.city-grid button:hover {
    color: #df1f2d;
}

@media (max-width: 760px) {
    .market-footer-sections {
        padding: 64px 0 74px;
    }

    .market-footer-sections__inner {
        width: min(100% - 32px, 1320px);
    }

    .food-grow-cta {
        margin-bottom: 54px;
        padding: 42px 18px;
    }

    .city-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 28px;
    }
}
</style>
