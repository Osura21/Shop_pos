<script setup>
import { ref } from "vue"
import { Head } from "@inertiajs/vue3"
import {
    Clock,
    CreditCard,
    Edit3,
    Heart,
    Image,
    Navigation,
    PhoneCall,
    Pizza,
    Salad,
    Sandwich,
    Search,
    SlidersHorizontal,
    Star,
    Store,
    TrendingUp,
    Truck,
    User,
} from "lucide-vue-next"
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import MarketplaceFooterSections from "../Components/MarketplaceFooterSections.vue"

const activeAudience = ref("customers")

const customerFeatures = [
    {
        icon: Search,
        title: "Smart Filter System",
        text: "Narrow down your food choices instantly by matching your desired delivery type, local neighborhood distance, and dietary focus.",
    },
    {
        icon: Clock,
        title: "Flexible Scheduling",
        text: "Plan meals ahead of time. Secure immediate delivery, choose an offline venue pickup, or set an order schedule days in advance.",
    },
    {
        icon: CreditCard,
        title: "Secure Transactions",
        text: "Enjoy hassle-free checkout parameters configured safely within browser-based environments, providing straightforward local payment routes.",
    },
    {
        icon: Navigation,
        title: "Intuitive Status Tracking",
        text: "Stay updated throughout the entire route. Track when meals are accepted, prepared, dispatched, and when they arrive at your door.",
    },
    {
        icon: Heart,
        title: "Favorited Local Outlets",
        text: "Save and organize highly rated diners, cafes, and bakeries inside your customer overview portal for efficient future ordering.",
    },
    {
        icon: Star,
        title: "Authentic Reviews",
        text: "Verify options using ratings provided by community foodies, ensuring your next chosen dish matches local expectations.",
    },
]

const restaurantFeatures = [
    {
        icon: SlidersHorizontal,
        title: "Responsive Dashboard",
        text: "Coordinate daily sales volumes, active delivery routes, preparation lists, and kitchen task loads inside a single layout.",
    },
    {
        icon: Edit3,
        title: "Modular Menu Editor",
        text: "Alter dish pricing indexes, adjust category tags, handle daily ingredient stocks, and highlight specialty dishes dynamically.",
    },
    {
        icon: Image,
        title: "Dynamic Promotional Space",
        text: "Configure and review local page hero graphics, discount banners, and promotional cards instantly with visual browser preview modules.",
    },
    {
        icon: PhoneCall,
        title: "Automated OTP Security",
        text: "Maintain safe store administration accesses with verified phone setups using built-in multi-step onboarding verifications.",
    },
    {
        icon: TrendingUp,
        title: "Activity Analytics",
        text: "Receive clear summaries covering store performance metrics, overall delivery run times, and general revenue milestones.",
    },
    {
        icon: Truck,
        title: "Fulfillment Settings",
        text: "Fine-tune pickup times, structure localized delivery parameters, and coordinate customer settings seamlessly.",
    },
]

const orders = [
    {
        icon: Pizza,
        title: "Cheesy Chicken Pizza",
        meta: "Order #1042 • Colombo 03",
        status: "Preparing",
    },
    {
        icon: Sandwich,
        title: "Double Beef Burger",
        meta: "Order #1043 • Nugegoda",
        status: "On Delivery",
    },
    {
        icon: Salad,
        title: "Rice & Curry Meal",
        meta: "Order #1044 • Kandy",
        status: "Completed",
    },
]
</script>

<template>
    <Head title="Features | Sappy Eats" />

    <MultiVendorLayout>
        <main class="features-page">
            <section class="features-hero">
                <div class="features-container">
                    <div class="hero-badge">
                        <i></i>
                        Customized ordering experience
                    </div>

                    <h1>
                        Platform Elements Built For <span>Excellent Dining</span>
                    </h1>

                    <p>
                        Explore our thoughtfully curated ecosystem of tools designed to elevate how customers order meals
                        and how culinary businesses scale operations.
                    </p>

                    <div class="tab-container">
                        <div class="tab-box" role="tablist" aria-label="Audience Selector">
                            <button
                                class="tab-btn"
                                :class="{ active: activeAudience === 'customers' }"
                                type="button"
                                role="tab"
                                :aria-selected="activeAudience === 'customers'"
                                @click="activeAudience = 'customers'"
                            >
                                <User size="18" />
                                For Customers
                            </button>

                            <button
                                class="tab-btn"
                                :class="{ active: activeAudience === 'restaurants' }"
                                type="button"
                                role="tab"
                                :aria-selected="activeAudience === 'restaurants'"
                                @click="activeAudience = 'restaurants'"
                            >
                                <Store size="18" />
                                For Restaurants
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="features-section">
                <div class="features-container">
                    <Transition name="feature-panel" mode="out-in">
                        <div v-if="activeAudience === 'customers'" key="customers" class="feature-grid">
                            <article v-for="feature in customerFeatures" :key="feature.title" class="feature-card">
                                <div class="feature-icon-wrapper">
                                    <component :is="feature.icon" size="25" />
                                </div>
                                <h3>{{ feature.title }}</h3>
                                <p>{{ feature.text }}</p>
                            </article>
                        </div>

                        <div v-else key="restaurants" class="feature-grid">
                            <article v-for="feature in restaurantFeatures" :key="feature.title" class="feature-card">
                                <div class="feature-icon-wrapper">
                                    <component :is="feature.icon" size="25" />
                                </div>
                                <h3>{{ feature.title }}</h3>
                                <p>{{ feature.text }}</p>
                            </article>
                        </div>
                    </Transition>
                </div>
            </section>

            <section class="feature-block">
                <div class="features-container">
                    <div class="feature-grid-alt">
                        <div class="dashboard-preview" aria-label="Order tracking preview">
                            <div class="mock-top">
                                <div class="mock-logo">Sappy <span>Eats</span></div>
                            </div>

                            <div class="mock-screen">
                                <div class="mock-sidebar">
                                    <div class="side-dot"></div>
                                    <div class="side-dot"></div>
                                    <div class="side-dot active"></div>
                                    <div class="side-dot"></div>
                                    <div class="side-dot"></div>
                                </div>

                                <div class="mock-content">
                                    <div class="mock-filter">
                                        <span class="mock-chip">Today</span>
                                        <span class="mock-chip">Orders</span>
                                        <span class="mock-chip">Team</span>
                                    </div>

                                    <div class="order-list">
                                        <article v-for="order in orders" :key="order.title" class="order-item">
                                            <div class="order-food">
                                                <component :is="order.icon" size="25" />
                                            </div>
                                            <div>
                                                <h4>{{ order.title }}</h4>
                                                <p>{{ order.meta }}</p>
                                            </div>
                                            <span class="status">{{ order.status }}</span>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="feature-content">
                            <h2>Track Every Order With Confidence, Clarity, And Full Control</h2>
                            <p>
                                Our platform gives restaurants and food shops the tools they need to manage every food order,
                                payment, pickup, and delivery in real time. Customers stay informed, and businesses never miss an order.
                            </p>

                            <button class="learn-btn" type="button">Learn More</button>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <MarketplaceFooterSections />
    </MultiVendorLayout>
</template>

<style scoped>
.features-page {
    --primary: #c91a25;
    --black: #050505;
    --white: #ffffff;
    --text: #111827;
    --muted: #667085;
    --border: #e9e9e9;
    --soft: #f8f8f8;
    --soft-primary: rgba(201, 26, 37, 0.08);
    width: 100%;
    overflow: hidden;
    color: var(--text);
    background:
        radial-gradient(circle at 10% 15%, rgba(201, 26, 37, 0.08), transparent 30%),
        radial-gradient(circle at 90% 55%, rgba(201, 26, 37, 0.06), transparent 30%),
        #ffffff;
}

.features-container {
    width: min(1320px, calc(100% - 40px));
    margin: 0 auto;
}

.features-hero {
    padding: 168px 0 40px;
    text-align: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 34px;
    padding: 0 15px;
    border-radius: 999px;
    background: var(--soft-primary);
    color: var(--black);
    border: 1px solid rgba(201, 26, 37, 0.24);
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 22px;
}

.hero-badge i {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary);
    display: inline-block;
}

.features-hero h1 {
    max-width: 820px;
    margin: 0 auto;
    font-size: clamp(38px, 5vw, 64px);
    line-height: 1.08;
    letter-spacing: 0;
    font-weight: 700;
}

.features-hero h1 span {
    color: var(--primary);
}

.features-hero p {
    max-width: 600px;
    margin: 20px auto 0;
    font-size: 15px;
    line-height: 1.7;
    color: var(--muted);
}

.tab-container {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.tab-box {
    background: var(--soft);
    padding: 6px;
    border-radius: 999px;
    display: inline-flex;
    border: 1px solid var(--border);
}

.tab-btn {
    min-height: 48px;
    padding: 0 28px;
    border-radius: 999px;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 700;
    color: var(--muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-btn.active {
    background: var(--white);
    color: var(--black);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
}

.features-section {
    padding: 50px 0 60px;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.feature-card {
    background: var(--white);
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 22px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
    text-align: left;
}

.feature-icon-wrapper {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: var(--soft-primary);
    color: var(--primary);
    display: grid;
    place-items: center;
    margin-bottom: 24px;
}

.feature-card h3 {
    margin: 0 0 12px;
    color: var(--black);
    font-size: 18px;
    font-weight: 700;
}

.feature-card p {
    margin: 0;
    color: var(--muted);
    font-size: 13.5px;
    line-height: 1.6;
}

.feature-panel-enter-active,
.feature-panel-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
}

.feature-panel-enter-from,
.feature-panel-leave-to {
    opacity: 0;
    transform: translateY(12px);
}

.feature-block {
    padding: 42px 0 70px;
}

.feature-grid-alt {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.dashboard-preview {
    min-height: 410px;
    border-radius: 20px;
    border: 1px solid #d6d6d6;
    background: #f4f4f4;
    overflow: hidden;
    box-shadow: 0 18px 50px rgba(0, 0, 0, 0.06);
}

.mock-top {
    height: 58px;
    background: var(--white);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 20px;
}

.mock-logo {
    font-weight: 900;
    color: var(--black);
}

.mock-logo span {
    color: var(--primary);
}

.mock-screen {
    display: grid;
    grid-template-columns: 70px 1fr;
    min-height: 352px;
}

.mock-sidebar {
    background: var(--black);
    padding: 22px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
}

.side-dot {
    width: 28px;
    height: 28px;
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.12);
}

.side-dot.active {
    background: var(--primary);
}

.mock-content {
    padding: 20px;
    background: #fafafa;
}

.mock-filter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 22px;
}

.mock-chip {
    height: 32px;
    border-radius: 999px;
    background: var(--white);
    border: 1px solid var(--border);
    padding: 0 14px;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 800;
    color: #344054;
}

.order-list {
    display: grid;
    gap: 14px;
}

.order-item {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 16px;
    display: grid;
    grid-template-columns: 44px 1fr auto;
    align-items: center;
    gap: 14px;
}

.order-food {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(201, 26, 37, 0.10), rgba(201, 26, 37, 0.04));
    border: 1px solid rgba(201, 26, 37, 0.10);
    color: var(--primary);
    display: grid;
    place-items: center;
}

.order-item h4 {
    margin: 0 0 4px;
    color: var(--black);
    font-size: 13px;
    font-weight: 900;
}

.order-item p {
    margin: 0;
    color: var(--muted);
    font-size: 12px;
}

.status {
    padding: 7px 10px;
    border-radius: 999px;
    background: var(--soft-primary);
    color: var(--black);
    font-size: 11px;
    font-weight: 900;
}

.feature-content h2 {
    margin: 0 0 18px;
    color: #111827;
    font-size: clamp(30px, 3vw, 42px);
    line-height: 1.13;
    letter-spacing: 0;
    font-weight: 700;
}

.feature-content p {
    max-width: 520px;
    margin: 0 0 26px;
    color: var(--muted);
    font-size: 15px;
    line-height: 1.8;
}

.learn-btn {
    height: 44px;
    padding: 0 24px;
    border-radius: 999px;
    border: none;
    background: var(--black);
    color: var(--white);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 1050px) {
    .feature-grid,
    .feature-grid-alt {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .feature-grid-alt {
        gap: 36px;
    }
}

@media (max-width: 760px) {
    .features-hero {
        padding-top: 128px;
    }

    .feature-grid,
    .feature-grid-alt {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .features-container {
        width: min(100% - 24px, 1320px);
    }

    .tab-box {
        width: 100%;
        border-radius: 24px;
    }

    .tab-btn {
        flex: 1;
        justify-content: center;
        padding: 0 12px;
    }

    .dashboard-preview {
        min-height: auto;
    }

    .mock-screen {
        grid-template-columns: 52px 1fr;
    }

    .mock-sidebar {
        padding: 18px 0;
    }

    .order-item {
        grid-template-columns: 40px 1fr;
    }

    .status {
        grid-column: 1 / -1;
        width: fit-content;
    }
}
</style>
