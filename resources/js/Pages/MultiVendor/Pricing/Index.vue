<script setup>
import { computed, ref } from "vue"
import { Head, Link } from "@inertiajs/vue3"
import {
    Award,
    Calendar,
    Check,
    CheckCircle2,
    Edit3,
    Globe,
    MessageSquare,
    ShoppingBag,
    Sparkles,
    Smartphone,
    Truck,
    X,
} from "lucide-vue-next"
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import MarketplaceFooterSections from "../Components/MarketplaceFooterSections.vue"

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    featureGroups: {
        type: Array,
        default: () => [],
    },
})

const billingCycle = ref("monthly")

const billingIndex = computed(() => billingCycle.value === "annual" ? 1 : 0)

const currencyLabels = {
    LKR: "Rs.",
    USD: "$",
}

const iconMap = {
    products_count: ShoppingBag,
    vendor_panel: Globe,
    reports: Calendar,
    kitchen: Truck,
    loyalty_points: Sparkles,
    gift_cards: Award,
    promotions: Edit3,
    activity_log: MessageSquare,
    seating_plan: Smartphone,
}

const fallbackFeatureGroups = computed(() => {
    if (props.featureGroups.length) return props.featureGroups

    return [
        {
            name: "Plan Features",
            features: props.plans.flatMap((plan) => plan.features || []).filter((feature, index, list) => {
                return list.findIndex((item) => item.key === feature.key) === index
            }),
        },
    ]
})

const formatMoney = (amount, currencyCode) => {
    const symbol = currencyLabels[currencyCode] || currencyCode || "Rs."
    const value = Number(amount || 0)

    if (value === 0) return "Free"

    return `${symbol} ${value.toLocaleString(undefined, {
        maximumFractionDigits: value % 1 === 0 ? 0 : 2,
    })}`
}

const monthlyEquivalent = (plan) => {
    if (billingCycle.value === "monthly") {
        return plan.monthly_price
    }

    if (plan.yearly_price !== null && plan.yearly_price !== undefined) {
        return Number(plan.yearly_price) / 12
    }

    return Number(plan.monthly_price || 0) * 0.8
}

const yearlyPrice = (plan) => {
    if (plan.yearly_price !== null && plan.yearly_price !== undefined) {
        return Number(plan.yearly_price)
    }

    return Number(plan.monthly_price || 0) * 12 * 0.8
}

const annualSavings = (plan) => {
    const monthlyTotal = Number(plan.monthly_price || 0) * 12
    const yearly = yearlyPrice(plan)

    return Math.max(0, monthlyTotal - yearly)
}

const annualSavingsPercent = (plan) => {
    const monthlyTotal = Number(plan.monthly_price || 0) * 12
    if (!monthlyTotal) return 0

    return Math.round((annualSavings(plan) / monthlyTotal) * 100)
}

const billingNote = (plan) => {
    if (billingCycle.value === "monthly") {
        return "/month"
    }

    const yearly = yearlyPrice(plan)

    return yearly > 0 ? `/month, billed ${formatMoney(yearly, plan.currency_code)} yearly` : "/month"
}

const setBillingCycle = (cycle) => {
    billingCycle.value = cycle
}

const featureForPlan = (plan, featureKey) => {
    return (plan.features || []).find((feature) => feature.key === featureKey)
}

const includedFeatures = (plan) => {
    return (plan.features || []).filter((feature) => feature.enabled).slice(0, 6)
}
</script>

<template>
    <Head title="Pricing | Sappy Eats" />

    <MultiVendorLayout>
        <main class="pricing-page">
            <section class="pricing-hero">
                <div class="pricing-container">
                    <div class="hero-badge">
                        <i></i>
                        Flexible Partner Plans
                    </div>

                    <h1>
                        Simple Plans. <span>Powerful Features.</span> Grow Your Restaurant.
                    </h1>

                    <p>
                        Choose a plan that fits your kitchen operations. Clear parameters, no hidden commission fees,
                        cancel anytime.
                    </p>

                    <div class="toggle-container">
                        <div class="toggle-box" :style="{ '--billing-index': billingIndex }">
                            <span class="toggle-thumb"></span>
                            <span class="save-badge">Save More</span>
                            <button
                                class="toggle-btn"
                                :class="{ active: billingCycle === 'monthly' }"
                                type="button"
                                @click="setBillingCycle('monthly')"
                            >
                                Monthly
                            </button>
                            <button
                                class="toggle-btn"
                                :class="{ active: billingCycle === 'annual' }"
                                type="button"
                                @click="setBillingCycle('annual')"
                            >
                                Annual
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cards-section">
                <div class="pricing-container">
                    <div v-if="plans.length" class="cards-grid">
                        <article
                            v-for="(plan, index) in plans"
                            :key="`${plan.id}-${billingCycle}`"
                            class="pricing-card"
                            :class="{ popular: plan.most_popular || plan.highlight_plan, 'is-annual': billingCycle === 'annual' }"
                            :style="{ '--card-delay': `${index * 70}ms` }"
                        >
                            <span v-if="plan.most_popular || plan.badge" class="popular-tag">
                                {{ plan.most_popular ? "Most Popular" : plan.badge }}
                            </span>

                            <div>
                                <div class="plan-header">
                                    <h3>{{ plan.name }}</h3>
                                    <p>{{ plan.description || "A focused partner plan for growing food businesses." }}</p>

                                    <div class="price-box">
                                        <Transition name="price-slide" mode="out-in">
                                            <div :key="`${plan.id}-${billingCycle}`" class="price-content">
                                                <div class="price-line">
                                                    <span class="price-amount">{{ formatMoney(monthlyEquivalent(plan), plan.currency_code) }}</span>
                                                    <span class="price-period">{{ billingNote(plan) }}</span>
                                                </div>
                                                <span v-if="billingCycle === 'annual' && annualSavings(plan) > 0" class="annual-save">
                                                    Save {{ annualSavingsPercent(plan) }}% yearly
                                                </span>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>

                                <Link href="/seller/register" class="plan-cta">Start Free Trial</Link>
                            </div>

                            <div class="plan-features">
                                <h4>{{ plan.trial_days ? `${plan.trial_days} day trial includes:` : "Includes:" }}</h4>
                                <ul class="features-list">
                                    <li v-for="feature in includedFeatures(plan)" :key="feature.key">
                                        <CheckCircle2 size="18" :stroke-width="2" />
                                        {{ feature.display_value === "Included" ? feature.name : `${feature.name}: ${feature.display_value}` }}
                                    </li>
                                </ul>
                            </div>
                        </article>
                    </div>

                    <div v-else class="empty-pricing">
                        <h2>No active plans found</h2>
                        <p>Create active vendor subscription plans in the admin panel to show pricing here.</p>
                    </div>
                </div>
            </section>

            <section v-if="plans.length" class="matrix-section">
                <div class="pricing-container">
                    <div class="matrix-header">
                        <h2>Detailed Plan Comparison</h2>
                    </div>

                    <div class="matrix-container">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th>Core Features</th>
                                    <th v-for="plan in plans" :key="plan.id">{{ plan.name }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="group in fallbackFeatureGroups" :key="group.name">
                                    <tr class="category-row">
                                        <td :colspan="plans.length + 1">{{ group.name }}</td>
                                    </tr>

                                    <tr v-for="feature in group.features" :key="feature.key">
                                        <td>
                                            <div class="feature-name">
                                                <component :is="iconMap[feature.key] || Sparkles" size="24" :stroke-width="2" />
                                                {{ feature.name }}
                                            </div>
                                        </td>
                                        <td v-for="plan in plans" :key="`${plan.id}-${feature.key}`">
                                            <template v-if="featureForPlan(plan, feature.key)?.enabled">
                                                <Check
                                                    v-if="featureForPlan(plan, feature.key)?.display_value === 'Included'"
                                                    class="check-icon"
                                                    size="20"
                                                    :stroke-width="2"
                                                />
                                                <span v-else>{{ featureForPlan(plan, feature.key)?.display_value }}</span>
                                            </template>
                                            <X v-else class="unsupported-icon" size="20" :stroke-width="2" />
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>

        <MarketplaceFooterSections />
    </MultiVendorLayout>
</template>

<style scoped>
.pricing-page {
    --primary: #c91a25;
    --primary-hover: #a7121c;
    --black: #050505;
    --white: #ffffff;
    --text: #111827;
    --muted: #667085;
    --border: #e9e9e9;
    --soft: #f8f8f8;
    --soft-primary: rgba(201, 26, 37, 0.06);
    --shadow: 0 24px 70px rgba(0, 0, 0, 0.08);
    width: 100%;
    overflow: hidden;
    color: var(--text);
    background:
        radial-gradient(circle at 10% 15%, rgba(201, 26, 37, 0.08), transparent 30%),
        radial-gradient(circle at 90% 55%, rgba(201, 26, 37, 0.06), transparent 30%),
        #ffffff;
}

.pricing-container {
    width: min(1320px, calc(100% - 40px));
    margin: 0 auto;
}

.pricing-hero {
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

.pricing-hero h1 {
    max-width: 820px;
    margin: 0 auto;
    font-size: clamp(38px, 5vw, 64px);
    line-height: 1.08;
    letter-spacing: 0;
    font-weight: 700;
}

.pricing-hero h1 span {
    color: var(--primary);
}

.pricing-hero p {
    max-width: 600px;
    margin: 20px auto 0;
    font-size: 15px;
    line-height: 1.7;
    color: var(--muted);
}

.toggle-container {
    display: flex;
    justify-content: center;
    margin: 40px 0 20px;
}

.toggle-box {
    background: var(--soft);
    padding: 6px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--border);
    position: relative;
    isolation: isolate;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.7);
}

.toggle-thumb {
    position: absolute;
    top: 6px;
    left: 6px;
    z-index: 0;
    width: calc((100% - 12px) / 2);
    height: calc(100% - 12px);
    border-radius: 999px;
    background: var(--white);
    box-shadow: 0 8px 22px rgba(17, 24, 39, 0.08);
    transform: translateX(calc(var(--billing-index) * 100%));
    transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.toggle-btn {
    min-height: 44px;
    padding: 0 28px;
    border-radius: 999px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 800;
    color: var(--muted);
    cursor: pointer;
    position: relative;
    z-index: 1;
    transition: color 0.2s ease;
}

.toggle-btn.active {
    color: var(--black);
}

.save-badge {
    position: absolute;
    top: -18px;
    right: -30px;
    z-index: 2;
    background: #22c55e;
    color: var(--white);
    font-size: 10px;
    font-weight: 900;
    padding: 4px 10px;
    border-radius: 999px;
    box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2);
}

.cards-section {
    padding: 30px 0 80px;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    align-items: stretch;
}

.pricing-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 40px 32px;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    animation: plan-pop 0.42s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: var(--card-delay);
    transition:
        border-color 0.24s ease,
        box-shadow 0.24s ease,
        transform 0.24s ease;
}

.pricing-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 24px 58px rgba(17, 24, 39, 0.08);
}

.pricing-card.is-annual {
    border-color: rgba(201, 26, 37, 0.35);
}

.pricing-card.popular {
    border: 2px solid var(--primary);
    box-shadow: 0 20px 50px rgba(201, 26, 37, 0.06);
}

.popular-tag {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--primary);
    color: var(--white);
    font-size: 11px;
    font-weight: 800;
    padding: 6px 16px;
    border-radius: 999px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    white-space: nowrap;
}

.plan-header h3 {
    margin: 0 0 12px;
    color: var(--black);
    font-size: 18px;
    font-weight: 800;
}

.plan-header p {
    min-height: 60px;
    margin: 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.5;
}

.price-box {
    min-height: 96px;
    margin: 28px 0;
}

.price-content {
    width: 100%;
}

.price-line {
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
}

.price-amount {
    color: var(--black);
    font-size: 44px;
    font-weight: 700;
    letter-spacing: 0;
}

.price-period {
    color: var(--muted);
    font-size: 13px;
    font-weight: 600;
}

.annual-save {
    display: inline-flex;
    align-items: center;
    margin-top: 10px;
    min-height: 24px;
    border-radius: 999px;
    background: rgba(34, 197, 94, 0.1);
    color: #138a42;
    padding: 0 10px;
    font-size: 11px;
    font-weight: 900;
}

.price-slide-enter-active,
.price-slide-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
}

.price-slide-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.price-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

@keyframes plan-pop {
    0% {
        opacity: 0;
        transform: translateY(14px) scale(0.985);
    }

    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.plan-cta {
    width: 100%;
    min-height: 48px;
    border-radius: 999px;
    border: none;
    background: var(--primary);
    color: var(--white);
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 30px;
}

.pricing-card.popular .plan-cta {
    background: var(--black);
}

.plan-features h4 {
    margin: 0 0 16px;
    color: var(--black);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.features-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 0;
    padding: 0;
}

.features-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #374151;
    font-size: 13.5px;
}

.features-list svg {
    color: #22c55e;
    flex: 0 0 auto;
}

.empty-pricing {
    padding: 64px 24px;
    border: 1px dashed rgba(201, 26, 37, 0.35);
    border-radius: 24px;
    background: #ffffff;
    text-align: center;
}

.empty-pricing h2 {
    margin: 0 0 10px;
    color: var(--black);
    font-weight: 900;
}

.empty-pricing p {
    margin: 0;
    color: var(--muted);
}

.matrix-section {
    padding: 60px 0 100px;
    background: #fafbfc;
    border-top: 1px solid var(--border);
}

.matrix-header {
    text-align: center;
    margin-bottom: 50px;
}

.matrix-header h2 {
    margin: 0;
    color: var(--black);
    font-size: clamp(28px, 3.5vw, 38px);
    font-weight: 700;
    letter-spacing: 0;
}

.matrix-container {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 20px;
    background: var(--white);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.01);
}

.matrix-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    min-width: 800px;
}

.matrix-table th,
.matrix-table td {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    font-size: 13.5px;
}

.matrix-table th {
    background: #fafbfc;
    color: var(--black);
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.matrix-table th:not(:first-child),
.matrix-table td:not(:first-child) {
    text-align: center;
}

.matrix-table tr.category-row td {
    background: var(--soft-primary);
    color: var(--primary);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 24px;
    text-align: left;
}

.feature-name {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--black);
    font-weight: 600;
}

.feature-name svg {
    color: var(--muted);
    flex: 0 0 auto;
}

.check-icon {
    color: #22c55e;
}

.unsupported-icon {
    color: #d1d5db;
}

@media (max-width: 1050px) {
    .cards-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .pricing-card {
        max-width: 480px;
        margin: 0 auto;
        width: 100%;
    }
}

@media (max-width: 760px) {
    .pricing-hero {
        padding-top: 128px;
    }
}

@media (max-width: 640px) {
    .pricing-container {
        width: min(100% - 24px, 1320px);
    }

    .toggle-box {
        width: 100%;
    }

    .toggle-btn {
        flex: 1;
        padding: 0 12px;
    }

    .save-badge {
        right: 10px;
    }
}
</style>
