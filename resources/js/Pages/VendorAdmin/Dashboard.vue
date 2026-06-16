<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import axios from "axios";
import { Head, Link } from "@inertiajs/vue3";
import VendorAdminLayout from "@/Layouts/VendorAdminLayout.vue";
import { usePermission } from "@/composables/usePermission";
import {
    AlertTriangle,
    ArrowRight,
    BarChart3,
    Boxes,
    CheckCircle2,
    ClipboardList,
    Package,
    Plus,
    ShoppingCart,
    Sparkles,
    TrendingUp,
    Users,
    WalletCards,
} from "lucide-vue-next";
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from "chart.js";

defineOptions({ layout: VendorAdminLayout });

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip
);

const props = defineProps({
    dashboardDataUrl: { type: String, default: () => route('vendor.dashboard.data') },
});

const emptyDashboard = () => ({
    currencyCode: "LKR",
    stats: [],
    summary: {},
    charts: {},
    recentOrders: [],
    topProducts: [],
    lowStockItems: [],
});

const dashboard = ref(emptyDashboard());
const loading = ref(true);
const loadError = ref("");
const currencyCode = computed(() => dashboard.value.currencyCode || "LKR");
const stats = computed(() => dashboard.value.stats || []);
const summary = computed(() => dashboard.value.summary || {});
const charts = computed(() => dashboard.value.charts || {});
const recentOrders = computed(() => dashboard.value.recentOrders || []);
const topProducts = computed(() => dashboard.value.topProducts || []);
const lowStockItems = computed(() => dashboard.value.lowStockItems || []);

const { can } = usePermission();

const revenueCanvas = ref(null);
const ordersCanvas = ref(null);
const statusesCanvas = ref(null);
const channelsCanvas = ref(null);
const chartInstances = [];

const iconMap = {
    Package,
    ShoppingCart,
    WalletCards,
    Users,
};

const quickActions = [
    { label: "Add Product", caption: "Create a new menu item", href: route('vendor.products.create'), icon: Plus },
    { label: "Open POS", caption: "Start counter billing", href: route('vendor.pos.open'), icon: ShoppingCart },
    { label: "View Orders", caption: "Manage kitchen tickets", href: route('vendor.sales.orders.index'), icon: ClipboardList },
    { label: "Stock Movements", caption: "Track inventory flow", href: route('vendor.stock-movements.index'), icon: Boxes },
];

const insights = computed(() => [
    {
        label: "Branches",
        value: formatNumber(summary.value?.branches || 0),
        permission: 'branches.view',
        icon: BarChart3,
        tone: "blue",
    },
    {
        label: "Customers",
        value: formatNumber(summary.value?.customers || 0),
        permission: 'customers.view',
        icon: Users,
        tone: "purple",
    },
    {
        label: "Low Stock",
        value: formatNumber(summary.value?.low_stock || 0),
        permission: 'stock-movements.view',
        icon: AlertTriangle,
        tone: "danger",
    },
    {
        label: "Avg Order",
        value: money(summary.value?.average_order_value || 0),
        permission: 'sales-orders.view',
        icon: TrendingUp,
        tone: "green",
    },
]);

const heroRevenue = computed(() => money(summary.value?.revenue || summary.value?.total_revenue || 0));
const heroOrders = computed(() => formatNumber(summary.value?.orders || summary.value?.total_orders || 0));

function money(value) {
    return `${currencyCode.value} ${Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString();
}

function statValue(stat) {
    return stat?.money ? money(stat.value) : formatNumber(stat?.value);
}

function statIcon(stat) {
    return iconMap[stat?.icon] || Package;
}

function statTone(stat) {
    return stat?.tone || "amber";
}

function statusClass(status) {
    const value = String(status || "").toLowerCase();

    if (value === "served" || value === "completed") return "status-pill--success";
    if (value === "pending") return "status-pill--warning";
    if (value === "cancelled") return "status-pill--danger";
    if (value === "ready") return "status-pill--ready";

    return "status-pill--neutral";
}

function compactQty(value) {
    const numeric = Number(value || 0);
    return Number.isInteger(numeric) ? numeric : numeric.toFixed(2);
}

function hasChartData(dataset) {
    return Array.isArray(dataset?.values) && dataset.values.some((value) => Number(value) > 0);
}

function destroyCharts() {
    while (chartInstances.length) {
        chartInstances.pop()?.destroy();
    }
}

function makeLineChart(canvas, labels, values) {
    if (!canvas) return;

    const context = canvas.getContext("2d");
    const gradient = context.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, "rgba(245, 124, 0, 0.34)");
    gradient.addColorStop(0.55, "rgba(245, 124, 0, 0.12)");
    gradient.addColorStop(1, "rgba(245, 124, 0, 0)");

    chartInstances.push(new Chart(canvas, {
        type: "line",
        data: {
            labels,
            datasets: [{
                label: "Revenue",
                data: values,
                borderColor: "#f57c00",
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.42,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: "#ffffff",
                pointBorderColor: "#f57c00",
                pointBorderWidth: 2,
            }],
        },
        options: chartOptions((value) => money(value)),
    }));
}

function makeBarChart(canvas, labels, values) {
    if (!canvas) return;

    chartInstances.push(new Chart(canvas, {
        type: "bar",
        data: {
            labels,
            datasets: [{
                label: "Orders",
                data: values,
                backgroundColor: ["#2563eb", "#14b8a6", "#f57c00", "#7c3aed", "#e11d48", "#475569", "#22c55e"],
                borderRadius: 12,
                borderSkipped: false,
                maxBarThickness: 36,
            }],
        },
        options: chartOptions((value) => formatNumber(value)),
    }));
}

function makeDoughnutChart(canvas, labels, values, palette) {
    if (!canvas) return;

    chartInstances.push(new Chart(canvas, {
        type: "doughnut",
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: palette,
                borderColor: "#ffffff",
                borderWidth: 5,
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "70%",
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#111827",
                    titleColor: "#ffffff",
                    bodyColor: "#ffffff",
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: true,
                },
            },
        },
    }));
}

function chartOptions(formatter) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: "index" },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: "#111827",
                titleColor: "#ffffff",
                bodyColor: "#ffffff",
                padding: 12,
                cornerRadius: 12,
                callbacks: {
                    label: (item) => ` ${item.dataset.label}: ${formatter(item.raw)}`,
                },
            },
        },
        scales: {
            x: {
                border: { display: false },
                grid: { display: false },
                ticks: { color: "#64748b", font: { size: 11, weight: 700 } },
            },
            y: {
                beginAtZero: true,
                border: { display: false },
                grid: { color: "rgba(148, 163, 184, 0.18)" },
                ticks: { color: "#94a3b8", precision: 0, callback: formatter },
            },
        },
    };
}
const statusLegend = computed(() => {
    const labels = charts.value?.statuses?.labels || [];
    const values = charts.value?.statuses?.values || [];
    const colors = ["#22c55e", "#e11d48", "#14b8a6", "#7c3aed", "#f59e0b"];

    const total = values.reduce((sum, item) => sum + Number(item || 0), 0);

    return labels.map((label, index) => ({
        label,
        value: Number(values[index] || 0),
        color: colors[index] || "#94a3b8",
        percentage: total ? Math.round((Number(values[index] || 0) / total) * 100) : 0,
    }));
});

const channelLegend = computed(() => {
    const labels = charts.value?.channels?.labels || [];
    const values = charts.value?.channels?.values || [];
    const colors = ["#2563eb", "#f57c00", "#14b8a6", "#e11d48", "#7c3aed", "#475569"];

    const total = values.reduce((sum, item) => sum + Number(item || 0), 0);

    return labels.map((label, index) => ({
        label,
        value: Number(values[index] || 0),
        color: colors[index] || "#94a3b8",
        percentage: total ? Math.round((Number(values[index] || 0) / total) * 100) : 0,
    }));
});
async function renderCharts() {
    destroyCharts();
    await nextTick();

    makeLineChart(
        revenueCanvas.value,
        charts.value?.revenue?.labels || [],
        charts.value?.revenue?.values || []
    );

    makeBarChart(
        ordersCanvas.value,
        charts.value?.orders?.labels || [],
        charts.value?.orders?.values || []
    );

   makeDoughnutChart(
    statusesCanvas.value,
    charts.value?.statuses?.labels || [],
    charts.value?.statuses?.values || [],
    ["#22c55e", "#e11d48", "#14b8a6", "#7c3aed", "#f59e0b"]
);

makeDoughnutChart(
    channelsCanvas.value,
    charts.value?.channels?.labels || [],
    charts.value?.channels?.values || [],
    ["#2563eb", "#f57c00", "#14b8a6", "#e11d48", "#7c3aed", "#475569"]
);
}

const loadDashboard = async () => {
    loading.value = true;
    loadError.value = "";
    let loaded = false;

    try {
        const { data } = await axios.get(props.dashboardDataUrl);
        dashboard.value = { ...emptyDashboard(), ...data };
        loaded = true;
    } catch (error) {
        loadError.value = "Dashboard data could not be loaded.";
    } finally {
        loading.value = false;
        if (loaded) {
            await nextTick();
            await renderCharts();
        }
    }
};

onMounted(loadDashboard);
onBeforeUnmount(destroyCharts);
watch(charts, renderCharts, { deep: true });
</script>

<template>
    <Head title="Vendor Dashboard" />

    <main class="dashboard-page">
        <section class="dashboard-hero">
            <div class="hero-glow hero-glow--left"></div>
            <div class="hero-glow hero-glow--right"></div>

            <div class="hero-main">
                <span class="hero-badge">
                    <Sparkles :size="15" />
                    Store Overview
                </span>

                <h1>Dashboard</h1>
                <p class="hero-copy">
                    Live sales, kitchen activity, stock signals, and customer movement in one clean workspace.
                </p>

             
            </div>

            <div class="hero-actions">
                <Link v-if="can('inventory-analytics.view')" :href="route('vendor.inventory-analytics.index')" class="soft-action">
                    <BarChart3 :size="17" />
                    <span>View Analytics</span>
                </Link>
                <Link v-if="can('products.create')" :href="route('vendor.products.create')" class="primary-action">
                    <Plus :size="17" />
                    <span>Add Product</span>
                </Link>
            </div>
        </section>

        <div v-if="loadError" class="dashboard-alert">{{ loadError }}</div>

        <section v-if="loading" class="stats-grid">
            <article v-for="index in 4" :key="`stat-skeleton-${index}`" class="stat-card skeleton-card">
                <span class="skeleton-line w-45"></span>
                <span class="skeleton-line w-70"></span>
                <span class="skeleton-line w-55"></span>
            </article>
        </section>
        <section v-else class="stats-grid">
            <article v-for="stat in stats" :key="stat.title" class="stat-card" :class="[
                `stat-card--${statTone(stat)}`,
                !can(stat.permission) ? 'stat-card--locked' : ''
            ]">
                <div class="stat-card__content">
                    <span>{{ stat.title }}</span>
                    <strong>{{ statValue(stat) }}</strong>
                    <small>{{ stat.subtitle }}</small>
                </div>

                <div class="stat-card__icon">
                    <component :is="statIcon(stat)" :size="23" />
                </div>
            </article>
        </section>

        <section v-if="loading" class="insight-strip">
            <article v-for="index in 4" :key="`insight-skeleton-${index}`" class="insight-item skeleton-card">
                <span class="skeleton-icon"></span>
                <div class="w-100">
                    <span class="skeleton-line w-45"></span>
                    <span class="skeleton-line w-65"></span>
                </div>
            </article>
        </section>
        <section v-else-if="can('branches.view') ||
            can('customers.view') ||
            can('stock-movements.view') ||
            can('sales-orders.view')
        " class="insight-strip">

            <article v-for="item in insights" :key="item.label" class="insight-item" :class="[
                `insight-item--${item.tone}`,
                !can(item.permission) ? 'insight-item--locked' : ''
            ]">
                <div class="insight-icon">
                    <component :is="item.icon" :size="19" />
                </div>

                <div class="insight-content">
                    <span>{{ item.label }}</span>
                    <strong>{{ item.value }}</strong>
                </div>
            </article>
        </section>


        <section class="charts-layout">
            <article v-if="loading" class="panel panel--wide skeleton-panel"></article>
            <article v-if="loading" class="panel skeleton-panel"></article>
            <article v-if="!loading && can('dashboard.revenue.view')" class="panel panel--wide">
                <div class="panel-head">
                    <div>
                        <span class="panel-kicker">Performance</span>
                        <h2>Revenue Trend</h2>
                        <p>Last 7 days sales movement</p>
                    </div>
                    <span class="panel-chip">Revenue</span>
                </div>

                <div class="chart-box">
                    <canvas ref="revenueCanvas"></canvas>
                    <div v-if="!hasChartData(charts?.revenue)" class="empty-chart">
                        <CheckCircle2 :size="24" />
                        <span>No revenue yet</span>
                    </div>
                </div>
            </article>

            <article v-if="!loading && can('dashboard.daily-orders.view')" class="panel">
                <div class="panel-head">
                    <div>
                        <span class="panel-kicker">Activity</span>
                        <h2>Daily Orders</h2>
                        <p>Last 7 days order count</p>
                    </div>
                    <span class="panel-chip panel-chip--blue">Orders</span>
                </div>

                <div class="chart-box">
                    <canvas ref="ordersCanvas"></canvas>
                    <div v-if="!hasChartData(charts?.orders)" class="empty-chart">
                        <CheckCircle2 :size="24" />
                        <span>No orders yet</span>
                    </div>
                </div>
            </article>
        </section>

        <section v-if="loading" class="content-grid">
            <article class="panel orders-panel skeleton-panel"></article>
            <aside class="side-stack">
                <article class="panel skeleton-panel skeleton-panel--short"></article>
                <article class="panel skeleton-panel skeleton-panel--short"></article>
            </aside>
        </section>
        <section v-else class="content-grid">
            <article v-if="can('dashboard.recent-orders.view')" class="panel orders-panel">
                <div class="panel-head">
                    <div>
                        <span class="panel-kicker">Kitchen Flow</span>
                        <h2>Recent Orders</h2>
                        <p>Latest kitchen tickets and sales</p>
                    </div>
                    <Link :href="route('vendor.sales.orders.index')" class="text-link">
                        View all <ArrowRight :size="15" />
                    </Link>
                </div>

                <div class="orders-table">
                    <div class="orders-table__head">
                        <span>Order</span>
                        <span>Customer</span>
                        <span>Status</span>
                        <span>Amount</span>
                    </div>

                    <div v-for="order in recentOrders" :key="order.id" class="orders-row">
                        <div class="order-cell">
                            <strong>#{{ order.id }}</strong>
                            <small>{{ order.date }}</small>
                        </div>
                        <div class="order-cell">
                            <strong>{{ order.customer }}</strong>
                            <small>{{ order.channel }}</small>
                        </div>
                        <div>
                            <span class="status-pill" :class="statusClass(order.status)">{{ order.status }}</span>
                        </div>
                        <strong class="amount">{{ money(order.amount) }}</strong>
                    </div>

                    <div v-if="!recentOrders.length" class="empty-list empty-list--table">
                        <CheckCircle2 :size="22" />
                        <span>No orders found</span>
                    </div>
                </div>
            </article>

            <aside class="side-stack">
                <article v-if="can('dashboard.shortcuts.view')" class="panel quick-panel">
                    <div class="panel-head">
                        <div>
                            <span class="panel-kicker">Shortcuts</span>
                            <h2>Quick Actions</h2>
                            <p>Daily operations in one tap</p>
                        </div>
                    </div>

                    <div class="quick-list">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.href"
                            class="quick-action"
                        >
                            <span class="quick-action__icon">
                                <component :is="action.icon" :size="18" />
                            </span>
                            <span class="quick-action__text">
                                <strong>{{ action.label }}</strong>
                                <small>{{ action.caption }}</small>
                            </span>
                            <ArrowRight :size="16" />
                        </Link>
                    </div>
                </article>

                <article v-if="can('dashboard.top-products.view')" class="panel">
                    <div class="panel-head">
                        <div>
                            <span class="panel-kicker">Sales Ranking</span>
                            <h2>Top Products</h2>
                            <p>Best sellers from the last 30 days</p>
                        </div>
                    </div>

                    <div class="rank-list">
                        <div v-for="(product, index) in topProducts" :key="product.name" class="rank-row">
                            <span class="rank-index">{{ index + 1 }}</span>
                            <div>
                                <strong>{{ product.name }}</strong>
                                <small>{{ compactQty(product.qty) }} sold</small>
                            </div>
                            <strong class="rank-amount">{{ money(product.total) }}</strong>
                        </div>

                        <div v-if="!topProducts.length" class="empty-list">
                            <CheckCircle2 :size="21" />
                            <span>No product sales yet</span>
                        </div>
                    </div>
                </article>
            </aside>
        </section>

      <section v-if="loading" class="bottom-grid">
    <article class="panel skeleton-panel skeleton-panel--short"></article>
    <article class="panel skeleton-panel skeleton-panel--short"></article>
</section>
      <section v-else class="bottom-grid">
    <article v-if="can('dashboard.order-status.view')" class="panel">
        <div class="panel-head">
            <div>
                <h2>Order Status</h2>
                <p>Last 30 days</p>
            </div>
        </div>

        <div class="mini-chart">
            <canvas ref="statusesCanvas"></canvas>
            <div v-if="!hasChartData(charts?.statuses)" class="empty-chart">
                No status data
            </div>
        </div>

        <div v-if="statusLegend.length" class="chart-legend">
            <div v-for="item in statusLegend" :key="item.label" class="legend-item">
                <div class="legend-left">
                    <span class="legend-dot" :style="{ backgroundColor: item.color }"></span>
                    <span class="legend-label">{{ item.label }}</span>
                </div>

                <div class="legend-right">
                    <strong>{{ formatNumber(item.value) }}</strong>
                    <small>{{ item.percentage }}%</small>
                </div>
            </div>
        </div>
    </article>

    <article v-if="can('dashboard.order-types.view')" class="panel">
        <div class="panel-head">
            <div>
                <h2>Order Types</h2>
                <p>Last 30 days</p>
            </div>
        </div>

        <div class="mini-chart">
            <canvas ref="channelsCanvas"></canvas>
            <div v-if="!hasChartData(charts?.channels)" class="empty-chart">
                No type data
            </div>
        </div>

        <div v-if="channelLegend.length" class="chart-legend">
            <div v-for="item in channelLegend" :key="item.label" class="legend-item">
                <div class="legend-left">
                    <span class="legend-dot" :style="{ backgroundColor: item.color }"></span>
                    <span class="legend-label">{{ item.label }}</span>
                </div>

                <div class="legend-right">
                    <strong>{{ formatNumber(item.value) }}</strong>
                    <small>{{ item.percentage }}%</small>
                </div>
            </div>
        </div>
    </article>
</section>
    </main>
</template>

<style scoped>
.dashboard-page {
    min-height: 100vh;
    display: grid;
    gap: 18px;
    padding: 4px;
    color: #0f172a;
}

.dashboard-page,
.dashboard-page * {
    box-sizing: border-box;
}

.dashboard-alert {
    padding: 12px 14px;
    border: 1px solid #fecaca;
    border-radius: 14px;
    background: #fef2f2;
    color: #b91c1c;
    font-weight: 800;
}

.skeleton-card,
.skeleton-panel {
    position: relative;
    overflow: hidden;
    pointer-events: none;
}

.skeleton-card::before,
.skeleton-panel::before {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.74), transparent);
    animation: dashboard-shimmer 1.35s infinite;
}

.skeleton-panel {
    min-height: 310px;
}

.skeleton-panel--short {
    min-height: 230px;
}

.skeleton-line,
.skeleton-icon {
    display: block;
    border-radius: 999px;
    background: #e5eaf2;
}

.skeleton-line {
    height: 12px;
    margin-bottom: 12px;
}

.skeleton-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 auto;
}

.w-45 { width: 45%; }
.w-55 { width: 55%; }
.w-65 { width: 65%; }
.w-70 { width: 70%; }
.w-100 { width: 100%; }

@keyframes dashboard-shimmer {
    100% {
        transform: translateX(100%);
    }
}

.dashboard-hero,
.panel,
.stat-card,
.insight-strip {
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid rgba(226, 232, 240, 0.92);
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.075);
}

.dashboard-hero {
    position: relative;
    overflow: hidden;
    min-height: 184px;
    border-radius: 26px;
    padding: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    background:
        radial-gradient(circle at 8% 0%, rgba(245, 124, 0, 0.18), transparent 31%),
        radial-gradient(circle at 88% 16%, rgba(37, 99, 235, 0.12), transparent 30%),
        linear-gradient(135deg, #ffffff 0%, #fff7ed 48%, #ffffff 100%);
}

.hero-glow {
    position: absolute;
    width: 210px;
    height: 210px;
    border-radius: 999px;
    filter: blur(12px);
    opacity: 0.42;
    pointer-events: none;
}

.hero-glow--left {
    left: -88px;
    top: -118px;
    background: #fed7aa;
}

.hero-glow--right {
    right: -90px;
    bottom: -120px;
    background: #bfdbfe;
}

.hero-main,
.hero-actions {
    position: relative;
    z-index: 1;
}

.hero-main {
    max-width: 730px;
}

.insight-item--locked .insight-content {
    visibility: hidden;   /* hides text but keeps layout */
}

.insight-item--locked .insight-content {
    display: none;
}
.insight-item--locked {
    position: relative;
}
.insight-item--locked::after {
    content: "RESTRICTED";
    position: absolute;
    inset: 0;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: #55555580;
    backdrop-filter: blur(1.7px);
}

.stat-card--locked .stat-card__content {
    visibility: hidden;
}

.stat-card--locked {
    position: relative;
}

.stat-card--locked::before {
    content: "RESTRICTED";
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(1.5px);
    z-index: 2;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 14px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #55555580;
}

.hero-badge {
    width: fit-content;
    min-height: 30px;
    border-radius: 999px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #9a3412;
    background: rgba(255, 247, 237, 0.88);
    border: 1px solid #fed7aa;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.dashboard-hero h1 {
    margin: 13px 0 0;
    font-size: clamp(30px, 4vw, 46px);
    line-height: 1.02;
    font-weight: 950;
    letter-spacing: -0.05em;
    color: #111827;
}

.hero-copy {
    max-width: 600px;
    margin: 12px 0 0;
    color: #64748b;
    font-size: 15px;
    line-height: 1.65;
    font-weight: 650;
}

.hero-metrics {
    width: fit-content;
    margin-top: 20px;
    border-radius: 18px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    backdrop-filter: blur(16px);
}

.hero-metric {
    display: grid;
    gap: 4px;
}

.hero-metric span {
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hero-metric strong {
    color: #0f172a;
    font-size: 18px;
    font-weight: 950;
}

.hero-divider {
    width: 1px;
    height: 34px;
    background: #e2e8f0;
}

.hero-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.primary-action,
.soft-action,
.quick-action,
.text-link {
    text-decoration: none;
}

.primary-action,
.soft-action {
    min-height: 46px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 0 18px;
    font-size: 14px;
    font-weight: 900;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.primary-action:hover,
.soft-action:hover,
.quick-action:hover,
.text-link:hover {
    transform: translateY(-1px);
}

.primary-action {
    color: #ffffff;
    background: linear-gradient(135deg, #f57c00, #fb923c);
    box-shadow: 0 14px 28px rgba(245, 124, 0, 0.28);
}

.soft-action {
    color: #92400e;
    background: rgba(255, 247, 237, 0.88);
    border: 1px solid #fed7aa;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    min-height: 138px;
    border-radius: 22px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.stat-card::after {
    content: "";
    position: absolute;
    right: -46px;
    bottom: -54px;
    width: 128px;
    height: 128px;
    border-radius: 999px;
    opacity: 0.12;
    background: currentColor;
}

.stat-card__content {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 8px;
}

.stat-card__content span,
.insight-item span,
.panel-head p,
.rank-row small,
.stock-row small,
.orders-row small,
.quick-action small {
    color: #64748b;
    font-size: 12px;
    font-weight: 750;
}

.stat-card__content strong {
    font-size: clamp(24px, 2.4vw, 31px);
    line-height: 1;
    font-weight: 950;
    letter-spacing: -0.04em;
}

.stat-card__content small {
    color: #475569;
    font-size: 12px;
    line-height: 1.45;
}

.stat-card__icon,
.insight-icon,
.quick-action__icon {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
}

.stat-card__icon {
    position: relative;
    z-index: 1;
    width: 48px;
    height: 48px;
    border-radius: 16px;
}

.stat-card--amber { color: #f57c00; }
.stat-card--blue { color: #2563eb; }
.stat-card--green { color: #059669; }
.stat-card--purple { color: #7c3aed; }
.stat-card--danger { color: #e11d48; }

.stat-card--amber .stat-card__icon { background: #fff7ed; border: 1px solid #fed7aa; }
.stat-card--blue .stat-card__icon { background: #eff6ff; border: 1px solid #bfdbfe; }
.stat-card--green .stat-card__icon { background: #ecfdf5; border: 1px solid #a7f3d0; }
.stat-card--purple .stat-card__icon { background: #f5f3ff; border: 1px solid #ddd6fe; }
.stat-card--danger .stat-card__icon { background: #fff1f2; border: 1px solid #fecdd3; }

.insight-strip {
    border-radius: 22px;
    padding: 12px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.insight-item {
    min-height: 76px;
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 14px;
    border-radius: 18px;
    background: #f8fafc;
}

.insight-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
}

.insight-item--blue .insight-icon { color: #2563eb; background: #eff6ff; border-color: #bfdbfe; }
.insight-item--purple .insight-icon { color: #7c3aed; background: #f5f3ff; border-color: #ddd6fe; }
.insight-item--danger .insight-icon { color: #e11d48; background: #fff1f2; border-color: #fecdd3; }
.insight-item--green .insight-icon { color: #059669; background: #ecfdf5; border-color: #a7f3d0; }

.insight-item div:last-child {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.insight-item strong {
    font-size: 18px;
    font-weight: 950;
    letter-spacing: -0.03em;
    color: #0f172a;
}

.charts-layout,
.content-grid,
.bottom-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.charts-layout {
    grid-template-columns: minmax(0, 1.45fr) minmax(340px, 0.85fr);
}

.content-grid {
    grid-template-columns: minmax(0, 1.62fr) minmax(340px, 0.78fr);
}


.panel {
    border-radius: 22px;
    padding: 20px;
    min-width: 0;
}

.panel-head {
    min-height: 46px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 16px;
}

.panel-kicker {
    display: block;
    margin-bottom: 4px;
    color: #f57c00;
    font-size: 11px;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.075em;
}

.panel-head h2 {
    margin: 0;
    color: #111827;
    font-size: 18px;
    font-weight: 950;
    letter-spacing: -0.035em;
}

.panel-head p {
    margin: 5px 0 0;
    line-height: 1.4;
}

.panel-chip {
    min-height: 30px;
    border-radius: 999px;
    padding: 0 11px;
    display: inline-flex;
    align-items: center;
    color: #92400e;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    font-size: 12px;
    font-weight: 900;
}

.panel-chip--blue {
    color: #1d4ed8;
    background: #eff6ff;
    border-color: #bfdbfe;
}

.text-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #f57c00;
    font-size: 13px;
    font-weight: 950;
    white-space: nowrap;
    transition: transform 0.18s ease;
}

.chart-box {
    position: relative;
    height: 310px;
}

.mini-chart {
    position: relative;
    height: 245px;
}

.empty-chart,
.empty-list {
    color: #94a3b8;
    font-size: 13px;
    font-weight: 850;
}

.empty-chart {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    gap: 8px;
    align-content: center;
    pointer-events: none;
    text-align: center;
}

.empty-list {
    min-height: 72px;
    border-radius: 16px;
    padding: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    text-align: center;
}

.empty-list--table {
    margin-top: 12px;
}

.orders-table {
    display: grid;
}

.orders-table__head,
.orders-row {
    display: grid;
    grid-template-columns: minmax(120px, 1fr) minmax(150px, 1.3fr) minmax(110px, 0.8fr) minmax(115px, 0.9fr);
    gap: 14px;
    align-items: center;
}

.orders-table__head {
    min-height: 44px;
    padding: 0 14px;
    border-radius: 14px;
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.orders-row {
    min-height: 72px;
    padding: 0 14px;
    border-bottom: 1px solid #eef2f7;
}

.orders-row:last-of-type {
    border-bottom: 0;
}

.order-cell {
    display: grid;
    gap: 5px;
    min-width: 0;
}

.order-cell strong,
.rank-row strong,
.stock-row strong {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.amount,
.rank-amount {
    text-align: right;
    color: #0f172a;
}

.status-pill {
    width: fit-content;
    min-height: 30px;
    border-radius: 999px;
    padding: 0 11px;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 950;
    text-transform: capitalize;
}

.status-pill--success { color: #047857; background: #d1fae5; border: 1px solid #a7f3d0; }
.status-pill--warning { color: #b45309; background: #fef3c7; border: 1px solid #fde68a; }
.status-pill--danger { color: #e11d48; background: #ffe4e6; border: 1px solid #fecdd3; }
.status-pill--ready { color: #0f766e; background: #ccfbf1; border: 1px solid #99f6e4; }
.status-pill--neutral { color: #475569; background: #e2e8f0; border: 1px solid #cbd5e1; }

.side-stack {
    display: grid;
    gap: 14px;
    align-content: start;
}

.quick-list,
.rank-list,
.stock-list {
    display: grid;
    gap: 10px;
}

.quick-action {
    min-height: 62px;
    border-radius: 16px;
    padding: 12px;
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 18px;
    align-items: center;
    gap: 12px;
    color: #0f172a;
    background: linear-gradient(135deg, #fff7ed, #ffffff);
    border: 1px solid #fed7aa;
    font-weight: 850;
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.quick-action:hover {
    border-color: #fb923c;
    box-shadow: 0 14px 24px rgba(245, 124, 0, 0.12);
}

.quick-action__icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    color: #f57c00;
    background: #ffffff;
    border: 1px solid #fed7aa;
}

.quick-action__text {
    min-width: 0;
    display: grid;
    gap: 4px;
}

.quick-action__text strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rank-row,
.stock-row {
    min-height: 64px;
    border-radius: 16px;
    padding: 12px;
    display: grid;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.rank-row {
    grid-template-columns: 32px minmax(0, 1fr) auto;
}

.stock-row {
    grid-template-columns: minmax(0, 1fr) auto;
}

.rank-index {
    width: 30px;
    height: 30px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    color: #ffffff;
    background: #0f172a;
    box-shadow: 0 10px 18px rgba(15, 23, 42, 0.18);
    font-size: 12px;
    font-weight: 950;
}

.rank-row div,
.stock-row div {
    min-width: 0;
    display: grid;
    gap: 5px;
}

.stock-row span {
    color: #e11d48;
    font-weight: 950;
    white-space: nowrap;
}

@media (max-width: 1360px) {
    .stats-grid,
    .insight-strip,
    .bottom-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .charts-layout,
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 820px) {
    .dashboard-page {
        gap: 14px;
        padding: 0;
    }

    .dashboard-hero {
        align-items: stretch;
        flex-direction: column;
        border-radius: 20px;
        padding: 22px;
    }

    .hero-actions {
        width: 100%;
        justify-content: stretch;
    }

    .primary-action,
    .soft-action {
        flex: 1 1 170px;
    }

    .hero-metrics {
        width: 100%;
        align-items: stretch;
        justify-content: space-between;
    }

    .stats-grid,
    .insight-strip,
    .bottom-grid {
        grid-template-columns: 1fr;
    }

    .panel,
    .stat-card,
    .insight-strip {
        border-radius: 18px;
    }

    .chart-box {
        height: 260px;
    }

    .mini-chart {
        height: 220px;
    }

    .orders-table__head {
        display: none;
    }

    .orders-row {
        grid-template-columns: 1fr;
        align-items: start;
        gap: 10px;
        min-height: unset;
        margin-bottom: 10px;
        padding: 14px;
        border: 1px solid #eef2f7;
        border-radius: 16px;
        background: #ffffff;
    }

    .amount {
        text-align: left;
    }

    .rank-row,
    .stock-row {
        grid-template-columns: 1fr;
    }

    .rank-index {
        display: none;
    }

    .rank-amount {
        text-align: left;
    }
}

@media (max-width: 520px) {
    .hero-metrics {
        flex-direction: column;
    }

    .hero-divider {
        width: 100%;
        height: 1px;
    }

    .panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .quick-action {
        grid-template-columns: 38px minmax(0, 1fr);
    }

    .quick-action > svg:last-child {
        display: none;
    }
}
.chart-legend {
    margin-top: 14px;
    display: grid;
    gap: 10px;
}

.legend-item {
    min-height: 44px;
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
@media (max-width: 1360px) {
    .stats-grid,
    .insight-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .bottom-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .charts-layout,
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 820px) {
    .bottom-grid {
        grid-template-columns: 1fr;
    }

    .bottom-grid > article:nth-child(3) {
        grid-column: auto;
    }
}
.legend-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    flex: 0 0 auto;
}

.legend-label {
    color: #0f172a;
    font-size: 13px;
    font-weight: 800;
}

.legend-right {
    display: grid;
    justify-items: end;
    gap: 2px;
}

.legend-right strong {
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
}

.legend-right small {
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
}

</style>
