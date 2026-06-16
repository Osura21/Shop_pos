<script setup>
import { Head } from "@inertiajs/vue3"
import {
    Box,
    Clock,
    Frown,
    Megaphone,
    ShieldCheck,
    Smile,
    TrendingDown,
    TrendingUp,
    User,
    UserX,
} from "lucide-vue-next"
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import MarketplaceFooterSections from "../Components/MarketplaceFooterSections.vue"

const gains = [
    { icon: Clock, text: "Save 2-3 Hours Every Day" },
    { icon: TrendingUp, text: "Increase Bookings & Revenue" },
    { icon: ShieldCheck, text: "Reduce No-Shows & Cancellations" },
    { icon: Smile, text: "Happy Diners & More Reviews" },
]

const testimonials = [
    {
        quote: "Since using the system, we saved two hours a day on phone coordination and increased our overall delivery revenue by 30%.",
        name: "Neka S.",
        role: "Pizza House Owner",
    },
    {
        quote: "No more double-bookings or missed pickup times. Sappy Eats makes our order management streamlined and customer satisfaction is up.",
        name: "Arjun M.",
        role: "Mocha Cafe Manager",
    },
    {
        quote: "Menu inventory tracking and delivery route reports helped us cut food waste and grow our profit margins significantly.",
        name: "Priya K.",
        role: "Cloud Kitchen Director",
    },
]

const costs = [
    {
        icon: Clock,
        title: "Time Wasted Every Day",
        text: "Manual orders, phone tags, and delivery coordination take valuable kitchen preparation time.",
        metric: "Lose: 1 - 2 Hours / Day",
    },
    {
        icon: UserX,
        title: "Customers You Lose",
        text: "Busy lines, incorrect notes, and delayed responses drive hungry diners to ordering elsewhere.",
        metric: "Lose: 1 - 3 Customers / Week",
    },
    {
        icon: TrendingDown,
        title: "Revenue You Miss",
        text: "Unfilled pickup slots, missing pre-orders, and dispatch gaps reduce total ticket opportunities.",
        metric: "Lose: Rs. 5K - 10K / Day",
    },
    {
        icon: Megaphone,
        title: "No Marketing & Promotions",
        text: "Without structured tools to run repeat-order campaigns and loyalty offers, your business loses easy visits.",
        metric: "Lose: Up to 20% More Business",
    },
    {
        icon: Box,
        title: "Inventory & Stock Issues",
        text: "Lacking automated inventory reviews leads directly to raw stockouts, manual counting errors, and waste.",
        metric: "Lose: Rs. 1K - 5K / Day",
    },
    {
        icon: Frown,
        title: "Stress & Burnout",
        text: "Coordinating orders manually causes ticket slip-ups, staff friction, and workplace fatigue.",
        metric: "Lose: Your Peace of Mind",
    },
]
</script>

<template>
    <Head title="Why Us | Sappy Eats" />

    <MultiVendorLayout>
        <main class="why-page">
            <section class="why-hero">
                <div class="why-container">
                    <div class="hero-badge">
                        <i></i>
                        Why Sappy Eats
                    </div>

                    <h1>
                        Your Kitchen Deserves <span>Smarter Management</span>
                    </h1>

                    <p>
                        Ditch phone orders and disorganized tickets. Sappy Eats connects your culinary workspace to
                        customers cleanly, protecting your margins and kitchen workflow.
                    </p>
                </div>
            </section>

            <section class="gain-section">
                <div class="why-container">
                    <div class="section-header">
                        <h2>What You Gain With Sappy Eats</h2>
                    </div>

                    <div class="gain-grid">
                        <article v-for="gain in gains" :key="gain.text" class="gain-card">
                            <div class="gain-icon">
                                <component :is="gain.icon" size="22" :stroke-width="2" />
                            </div>
                            <span>{{ gain.text }}</span>
                        </article>
                    </div>
                </div>
            </section>

            <section class="switch-section">
                <div class="why-container">
                    <div class="section-header">
                        <h2>Restaurants That Made the Switch</h2>
                        <p>They stopped losing. You can too.</p>
                    </div>

                    <div class="switch-grid">
                        <article v-for="testimonial in testimonials" :key="testimonial.name" class="switch-card">
                            <p class="switch-quote">"{{ testimonial.quote }}"</p>
                            <div class="switch-profile">
                                <div class="profile-avatar">
                                    <User size="22" :stroke-width="2" />
                                </div>
                                <div class="profile-info">
                                    <h4>{{ testimonial.name }}</h4>
                                    <p>{{ testimonial.role }}</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="costs-section">
                <div class="why-container">
                    <div class="section-header">
                        <h2>The Hidden Costs of Running Without Sappy Eats</h2>
                    </div>

                    <div class="costs-grid">
                        <article v-for="cost in costs" :key="cost.title" class="costs-card">
                            <div class="costs-icon">
                                <component :is="cost.icon" size="24" :stroke-width="2" />
                            </div>
                            <div class="costs-content">
                                <h3>{{ cost.title }}</h3>
                                <p>{{ cost.text }}</p>
                                <span class="costs-metric">{{ cost.metric }}</span>
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
.why-page {
    --primary: #c91a25;
    --black: #050505;
    --white: #ffffff;
    --text: #111827;
    --muted: #667085;
    --border: #e9e9e9;
    --soft-primary: rgba(201, 26, 37, 0.06);
    width: 100%;
    overflow: hidden;
    color: var(--text);
    background:
        radial-gradient(circle at 10% 15%, rgba(201, 26, 37, 0.08), transparent 30%),
        radial-gradient(circle at 90% 55%, rgba(201, 26, 37, 0.06), transparent 30%),
        #ffffff;
}

.why-container {
    width: min(1320px, calc(100% - 40px));
    margin: 0 auto;
}

.why-hero {
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

.why-hero h1 {
    max-width: 820px;
    margin: 0 auto;
    font-size: clamp(38px, 5vw, 64px);
    line-height: 1.08;
    letter-spacing: 0;
    font-weight: 700;
}

.why-hero h1 span {
    color: var(--primary);
}

.why-hero p {
    max-width: 600px;
    margin: 20px auto 0;
    font-size: 15px;
    line-height: 1.7;
    color: var(--muted);
}

.section-header {
    text-align: center;
    margin-bottom: 44px;
}

.section-header h2 {
    margin: 0;
    color: var(--black);
    font-size: clamp(28px, 3vw, 38px);
    font-weight: 700;
    letter-spacing: 0;
}

.section-header p {
    margin: 10px 0 0;
    color: var(--muted);
    font-size: 15px;
}

.gain-section {
    padding: 60px 0;
}

.gain-grid {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 20px;
    scrollbar-width: none;
}

.gain-grid::-webkit-scrollbar {
    display: none;
}

.gain-card {
    flex: 0 0 auto;
    width: 280px;
    background: rgba(201, 26, 37, 0.02);
    border: 1px solid rgba(201, 26, 37, 0.08);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.gain-icon,
.profile-avatar {
    background: var(--soft-primary);
    color: var(--primary);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.gain-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
}

.gain-card span {
    color: var(--black);
    font-size: 13.5px;
    font-weight: 700;
    line-height: 1.4;
}

.switch-section {
    padding: 60px 0 100px;
}

.switch-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.switch-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 30px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 220px;
}

.switch-quote {
    margin: 0 0 24px;
    color: #374151;
    font-size: 14px;
    line-height: 1.6;
    font-style: italic;
}

.switch-profile {
    display: flex;
    align-items: center;
    gap: 14px;
}

.profile-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
}

.profile-info h4 {
    margin: 0;
    color: var(--black);
    font-size: 14px;
    font-weight: 800;
}

.profile-info p {
    margin: 2px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.costs-section {
    padding: 100px 0;
    background: #fafbfc;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}

.costs-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.costs-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 30px;
    display: flex;
    gap: 20px;
    text-align: left;
}

.costs-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: var(--soft-primary);
    color: var(--primary);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.costs-content h3 {
    margin: 0 0 8px;
    color: var(--black);
    font-size: 16px;
    font-weight: 800;
}

.costs-content p {
    margin: 0 0 14px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.6;
}

.costs-metric {
    color: var(--primary);
    font-size: 13px;
    font-weight: 700;
}

@media (max-width: 1050px) {
    .switch-grid,
    .costs-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 760px) {
    .why-hero {
        padding-top: 128px;
    }

    .switch-grid,
    .costs-grid {
        grid-template-columns: 1fr;
    }

    .costs-card {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .why-container {
        width: min(100% - 24px, 1320px);
    }
}
</style>
