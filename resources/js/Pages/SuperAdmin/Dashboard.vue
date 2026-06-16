<script setup>
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import SuperAdminLayout from "@/Layouts/SuperAdminLayout.vue";
import SelectInput from "@/Components/SelectInput.vue";

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    dashboardDataUrl: { type: String, default: () => route("superadmin.dashboard.data") },
});

const emptyDashboard = () => ({
    currencyCode: "USD",
    stats: [],
    summary: {},
    charts: {},
    recentOrders: [],
    topProducts: [],
    lowStockItems: [],
    vendorOptions: [],
    vendorDetails: {},
});

const dashboard = ref(emptyDashboard());
const loading = ref(true);
const loadError = ref("");
const selectedVendorId = ref("");

const currencyCode = computed(() => dashboard.value.currencyCode || "USD");
const stats = computed(() => dashboard.value.stats || []);
const summary = computed(() => dashboard.value.summary || {});
const charts = computed(() => dashboard.value.charts || {});
const recentOrders = computed(() => dashboard.value.recentOrders || []);
const topProducts = computed(() => dashboard.value.topProducts || []);
const lowStockItems = computed(() => dashboard.value.lowStockItems || []);
const vendorOptions = computed(() => dashboard.value.vendorOptions || []);
const vendorDetails = computed(() => dashboard.value.vendorDetails || {});

watch(
    vendorOptions,
    (vendors) => {
        if (!selectedVendorId.value && vendors.length) {
            selectedVendorId.value = vendors[0].id;
        }
    },
);

const selectedVendor = computed(() => {
    if (!selectedVendorId.value) return null;
    return vendorDetails.value[String(selectedVendorId.value)] ?? null;
});

const systemTiles = computed(() => [
    {
        label: "Branches",
        value: summary.value.branches ?? 0,
        note: `${summary.value.active_branches ?? 0} active`,
        icon: "bi-diagram-3",
    },
    {
        label: "Domains",
        value: summary.value.domains ?? 0,
        note: "Connected vendor domains",
        icon: "bi-globe2",
    },
    {
        label: "Customers",
        value: summary.value.customers ?? 0,
        note: "Across all vendors",
        icon: "bi-person-heart",
    },
    {
        label: "Products",
        value: summary.value.products ?? 0,
        note: `${summary.value.new_products_this_week ?? 0} new this week`,
        icon: "bi-box-seam",
    },
    {
        label: "Low Stock",
        value: summary.value.low_stock ?? 0,
        note: "Items at or below alert level",
        icon: "bi-exclamation-triangle",
    },
   
]);

const vendorMetricTiles = computed(() => {
    const stats = selectedVendor.value?.stats ?? {};

    return [
        { label: `Revenue This Month (${vendorCurrencyText.value})`, value: formatVendorCurrency(stats.revenue_this_month), icon: "bi-cash-stack" },
        { label: "Orders This Month", value: stats.orders_this_month ?? 0, icon: "bi-receipt" },
        { label: `Average Order (${vendorCurrencyText.value})`, value: formatVendorCurrency(stats.average_order_value), icon: "bi-graph-up-arrow" },
        { label: "Pending Orders", value: stats.pending_orders ?? 0, icon: "bi-hourglass-split" },
        { label: "Customers", value: stats.customers ?? 0, icon: "bi-people" },
        { label: "Products", value: `${stats.active_products ?? 0}/${stats.products ?? 0}`, icon: "bi-basket" },
        { label: "Staff", value: `${stats.active_staff ?? 0}/${stats.staff ?? 0}`, icon: "bi-person-badge" },
        { label: "Low Stock", value: stats.low_stock ?? 0, icon: "bi-clipboard2-pulse" },
    ];
});

const maxRevenue = computed(() => Math.max(...(charts.value.revenue?.values ?? [0]), 1));
const maxOrders = computed(() => Math.max(...(charts.value.orders?.values ?? [0]), 1));
const vendorMaxRevenue = computed(() => Math.max(...(selectedVendor.value?.charts?.revenue?.values ?? [0]), 1));
const vendorMaxOrders = computed(() => Math.max(...(selectedVendor.value?.charts?.orders?.values ?? [0]), 1));

const formatNumber = (value) => new Intl.NumberFormat().format(Number(value ?? 0));

const formatCurrency = (value) =>
    new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: currencyCode.value || "USD",
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));

const formatMoney = (value, currency) =>
    new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: currency || currencyCode.value || "USD",
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));

const formatVendorCurrency = (value) => formatMoney(value, selectedVendor.value?.currencies?.base);

const vendorCurrencyText = computed(() => {
    const base = selectedVendor.value?.currencies?.base;
    const secondary = selectedVendor.value?.currencies?.secondary;

    if (base && secondary) return `${base} / ${secondary}`;
    return base || currencyCode.value || "USD";
});

const barHeight = (value, max) => `${Math.max((Number(value ?? 0) / max) * 100, value > 0 ? 8 : 2)}%`;

const statusClass = (status) => {
    const normalized = String(status ?? "").toLowerCase();
    if (["active", "served", "ready", "issued"].includes(normalized)) return "is-success";
    if (["pending", "preparing"].includes(normalized)) return "is-warning";
    if (["inactive", "suspended", "cancelled"].includes(normalized)) return "is-danger";
    return "is-muted";
};

const loadDashboard = async () => {
    loading.value = true;
    loadError.value = "";

    try {
        const { data } = await axios.get(props.dashboardDataUrl);
        dashboard.value = { ...emptyDashboard(), ...data };
    } catch (error) {
        loadError.value = "Dashboard data could not be loaded.";
    } finally {
        loading.value = false;
    }
};

onMounted(loadDashboard);
</script>

<template>
    <div class="sa-dashboard">
        <section class="sa-hero">
            <div>
                <span class="sa-eyebrow">Super Admin</span>
                <h1>System Dashboard</h1>
                <p>Live platform overview with vendor-wise performance, stock, orders, staff, branches, and domains.</p>
            </div>
            <div class="sa-hero__aside">
                <span>Monthly Average Order</span>
                <strong>{{ formatCurrency(summary.average_order_value) }}</strong>
                <small>{{ summary.themes ?? 0 }} themes available</small>
            </div>
        </section>

        <div v-if="loadError" class="sa-alert">{{ loadError }}</div>

        <section v-if="loading" class="sa-stat-grid">
            <article v-for="index in 4" :key="`stat-skeleton-${index}`" class="sa-stat-card sa-skeleton-card">
                <span class="sa-skeleton-line w-40"></span>
                <span class="sa-skeleton-line w-65"></span>
                <span class="sa-skeleton-line w-50"></span>
            </article>
        </section>
        <section v-else class="sa-stat-grid">
            <article v-for="stat in stats" :key="stat.title" class="sa-stat-card" :class="`sa-stat-card--${stat.accent}`">
                <div>
                    <span>{{ stat.title }}</span>
                    <strong>{{ stat.title === "Revenue" ? formatCurrency(stat.value) : formatNumber(stat.value) }}</strong>
                    <small>{{ stat.subtitle }}</small>
                </div>
                <i class="bi" :class="{
                    'bi-shop': stat.icon === 'shop',
                    'bi-cart3': stat.icon === 'shopping-cart',
                    'bi-wallet2': stat.icon === 'wallet',
                    'bi-people': stat.icon === 'users',
                }" />
            </article>
        </section>

        <section v-if="loading" class="sa-system-grid">
            <article v-for="index in 5" :key="`system-skeleton-${index}`" class="sa-info-card sa-skeleton-card">
                <span class="sa-skeleton-icon"></span>
                <div class="w-100">
                    <span class="sa-skeleton-line w-50"></span>
                    <span class="sa-skeleton-line w-65"></span>
                </div>
            </article>
        </section>
        <section v-else class="sa-system-grid">
            <article v-for="tile in systemTiles" :key="tile.label" class="sa-info-card">
                <i class="bi" :class="tile.icon" />
                <div>
                    <span>{{ tile.label }}</span>
                    <strong>{{ formatNumber(tile.value) }}</strong>
                    <small>{{ tile.note }}</small>
                </div>
            </article>
        </section>

        <section v-if="loading" class="sa-main-grid">
            <article class="sa-panel sa-panel--wide sa-skeleton-panel"></article>
            <article class="sa-panel sa-skeleton-panel"></article>
        </section>
        <section v-else class="sa-main-grid">
            <article class="sa-panel sa-panel--wide">
                <div class="sa-panel__head">
                    <div>
                        <span class="sa-section-label">System Revenue</span>
                        <h2>Last 7 days</h2>
                    </div>
                </div>
                <div class="sa-bars">
                    <div v-for="(label, index) in charts.revenue?.labels" :key="label" class="sa-bar">
                        <div class="sa-bar__track">
                            <span :style="{ height: barHeight(charts.revenue.values[index], maxRevenue) }" />
                        </div>
                        <small>{{ label }}</small>
                    </div>
                </div>
            </article>

            <article class="sa-panel">
                <div class="sa-panel__head">
                    <div>
                        <span class="sa-section-label">System Orders</span>
                        <h2>Last 7 days</h2>
                    </div>
                </div>
                <div class="sa-bars sa-bars--compact">
                    <div v-for="(label, index) in charts.orders?.labels" :key="label" class="sa-bar">
                        <div class="sa-bar__track">
                            <span :style="{ height: barHeight(charts.orders.values[index], maxOrders) }" />
                        </div>
                        <small>{{ label }}</small>
                    </div>
                </div>
            </article>
        </section>

    

        <section class="sa-vendor-section">
            <div class="sa-vendor-head">
                <div>
                    <span class="sa-section-label">Vendor Wise Details</span>
                    <h2>Selected vendor performance</h2>
                </div>
                <SelectInput
                    id="dashboard_vendor_id"
                    v-model="selectedVendorId"
                    className="sa-vendor-select-wrap"
                    label="Vendor"
                    :options="vendorOptions"
                    valueKey="id"
                    labelKey="name"
                    placeholder="Select vendor"
                    :disabled="loading"
                />
            </div>

            <div v-if="loading" class="sa-vendor-content">
                <article class="sa-vendor-profile sa-skeleton-card">
                    <dl>
                        <div v-for="index in 3" :key="`vendor-profile-skeleton-${index}`">
                            <span class="sa-skeleton-line w-40"></span>
                            <span class="sa-skeleton-line w-80"></span>
                        </div>
                    </dl>
                </article>
                <div class="sa-vendor-metrics">
                    <article v-for="index in 8" :key="`vendor-metric-skeleton-${index}`" class="sa-info-card sa-skeleton-card">
                        <span class="sa-skeleton-icon"></span>
                        <div class="w-100">
                            <span class="sa-skeleton-line w-70"></span>
                            <span class="sa-skeleton-line w-45"></span>
                        </div>
                    </article>
                </div>
            </div>
            <div v-else-if="selectedVendor" class="sa-vendor-content">
                <article class="sa-vendor-profile">
                    <dl>
                        <div>
                            <dt>Vendor Name</dt>
                            <dd>{{ selectedVendor.name }}</dd>
                        </div>
                        <div>
                            <dt>Domain</dt>
                            <dd>{{ selectedVendor.primary_domain }}</dd>
                        </div>
                        <div>
                            <dt>Contact</dt>
                            <dd>{{ selectedVendor.contact }}</dd>
                        </div>
                    </dl>
                </article>

                <div class="sa-vendor-metrics">
                    <article v-for="metric in vendorMetricTiles" :key="metric.label" class="sa-info-card">
                        <i class="bi" :class="metric.icon" />
                        <div>
                            <span>{{ metric.label }}</span>
                            <strong>{{ metric.value }}</strong>
                        </div>
                    </article>
                </div>

                <div class="sa-main-grid">
                    <article class="sa-panel">
                        <div class="sa-panel__head">
                            <div>
                                <span class="sa-section-label">Vendor Revenue</span>
                                <h2>Last 7 days</h2>
                            </div>
                        </div>
                        <div class="sa-bars sa-bars--compact">
                            <div v-for="(label, index) in selectedVendor.charts.revenue.labels" :key="label" class="sa-bar">
                                <div class="sa-bar__track">
                                    <span :style="{ height: barHeight(selectedVendor.charts.revenue.values[index], vendorMaxRevenue) }" />
                                </div>
                                <small>{{ label }}</small>
                            </div>
                        </div>
                    </article>

                    <article class="sa-panel">
                        <div class="sa-panel__head">
                            <div>
                                <span class="sa-section-label">Vendor Orders</span>
                                <h2>Last 7 days</h2>
                            </div>
                        </div>
                        <div class="sa-bars sa-bars--compact">
                            <div v-for="(label, index) in selectedVendor.charts.orders.labels" :key="label" class="sa-bar">
                                <div class="sa-bar__track">
                                    <span :style="{ height: barHeight(selectedVendor.charts.orders.values[index], vendorMaxOrders) }" />
                                </div>
                                <small>{{ label }}</small>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="sa-main-grid">
                    <article class="sa-panel">
                        <div class="sa-panel__head">
                            <div>
                                <span class="sa-section-label">Branches</span>
                                <h2>{{ selectedVendor.stats.active_branches }} active of {{ selectedVendor.stats.branches }}</h2>
                            </div>
                        </div>
                        <div class="sa-list">
                            <div v-for="branch in selectedVendor.branches" :key="branch.id" class="sa-list-row">
                                <div>
                                    <strong>{{ branch.name }}</strong>
                                    <small>{{ branch.location }} - {{ branch.currency }}</small>
                                </div>
                                <span class="sa-pill" :class="statusClass(branch.status)">{{ branch.status }}</span>
                            </div>
                            <div v-if="!selectedVendor.branches.length" class="sa-empty">No branches found.</div>
                        </div>
                    </article>

                    <article class="sa-panel">
                        <div class="sa-panel__head">
                            <div>
                                <span class="sa-section-label">Recent Orders</span>
                                <h2>{{ selectedVendor.name }}</h2>
                            </div>
                        </div>
                        <div class="sa-list">
                            <div v-for="order in selectedVendor.recent_orders" :key="order.id" class="sa-list-row">
                                <div>
                                    <strong>{{ order.customer }}</strong>
                                    <small>{{ order.channel }} - {{ order.date }}</small>
                                </div>
                                <div class="sa-row-end">
                                    <span class="sa-pill" :class="statusClass(order.status)">{{ order.status }}</span>
                                    <b>{{ formatVendorCurrency(order.amount) }}</b>
                                </div>
                            </div>
                            <div v-if="!selectedVendor.recent_orders.length" class="sa-empty">No orders found.</div>
                        </div>
                    </article>

                    <article class="sa-panel">
                        <div class="sa-panel__head">
                            <div>
                                <span class="sa-section-label">Low Stock</span>
                                <h2>{{ selectedVendor.name }}</h2>
                            </div>
                        </div>
                        <div class="sa-list">
                            <div v-for="item in selectedVendor.low_stock_items" :key="item.name" class="sa-list-row">
                                <div>
                                    <strong>{{ item.name }}</strong>
                                    <small>Alert at {{ formatNumber(item.alert_quantity) }} {{ item.unit }}</small>
                                </div>
                                <b>{{ formatNumber(item.current_stock) }} {{ item.unit }}</b>
                            </div>
                            <div v-if="!selectedVendor.low_stock_items.length" class="sa-empty">No low stock items.</div>
                        </div>
                    </article>
                </div>
            </div>

            <div v-else class="sa-empty sa-empty--large">No vendors found.</div>
        </section>
    </div>
</template>

<style scoped>
.sa-dashboard {
    background: #f6f7fb;
    min-height: 100vh;
    padding: 22px;
    color: #172033;
}

.sa-hero {
    display: flex;
    justify-content: space-between;
    gap: 24px;
    padding: 30px;
    border-radius: 18px;
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(255, 247, 237, 0.95)),
        url("/superadmin/images/cropped-Veyogo.png");
    background-size: auto, 220px;
    background-repeat: no-repeat;
    background-position: center, right 28px center;
    border: 1px solid rgba(245, 124, 0, 0.14);
    box-shadow: 0 18px 45px rgba(23, 32, 51, 0.08);
}

.sa-eyebrow,
.sa-section-label {
    color: #f57c00;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.sa-hero h1,
.sa-panel h2,
.sa-vendor-head h2,
.sa-vendor-profile h3 {
    margin: 0;
    color: #111827;
}

.sa-hero h1 {
    margin-top: 8px;
    font-size: 34px;
    font-weight: 850;
}

.sa-hero p {
    max-width: 620px;
    margin: 10px 0 0;
    color: #64748b;
    line-height: 1.6;
}

.sa-hero__aside {
    align-self: stretch;
    min-width: 230px;
    padding: 18px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(245, 124, 0, 0.16);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.sa-hero__aside span,
.sa-info-card span,
.sa-stat-card span,
.sa-list small,
.sa-vendor-profile dt {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

.sa-hero__aside strong {
    font-size: 28px;
    margin: 4px 0;
}

.sa-hero__aside small {
    color: #94a3b8;
}

.sa-stat-grid,
.sa-system-grid,
.sa-vendor-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-top: 16px;
}

.sa-system-grid,
.sa-vendor-metrics {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.sa-stat-card,
.sa-info-card,
.sa-panel,
.sa-vendor-section,
.sa-vendor-profile {
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}

.sa-stat-card {
    min-height: 132px;
    padding: 20px;
    border-radius: 16px;
    display: flex;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.sa-stat-card::after {
    content: "";
    position: absolute;
    right: -28px;
    bottom: -48px;
    width: 130px;
    height: 130px;
    border-radius: 999px;
    opacity: 0.13;
    background: currentColor;
}

.sa-stat-card strong {
    display: block;
    margin: 8px 0 4px;
    font-size: 30px;
    line-height: 1;
}

.sa-stat-card small {
    color: #94a3b8;
    font-weight: 700;
}

.sa-stat-card i {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: rgba(15, 23, 42, 0.05);
    font-size: 20px;
    z-index: 1;
}

.sa-stat-card--orange { color: #f57c00; }
.sa-stat-card--blue { color: #2563eb; }
.sa-stat-card--green { color: #059669; }
.sa-stat-card--purple { color: #7c3aed; }

.sa-info-card {
    min-height: 112px;
    padding: 16px;
    border-radius: 14px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.sa-info-card i {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    color: #f57c00;
    background: rgba(245, 124, 0, 0.09);
}

.sa-info-card strong {
    display: block;
    margin: 3px 0;
    font-size: 22px;
    color: #111827;
}

.sa-info-card small {
    color: #94a3b8;
    font-size: 12px;
}

.sa-main-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.sa-panel--wide {
    grid-column: span 2;
}

.sa-panel,
.sa-vendor-section {
    border-radius: 16px;
    padding: 18px;
}

.sa-panel__head,
.sa-vendor-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.sa-panel h2,
.sa-vendor-head h2 {
    margin-top: 4px;
    font-size: 17px;
    font-weight: 800;
}

.sa-bars {
    height: 235px;
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    align-items: end;
    gap: 12px;
}

.sa-bars--compact {
    height: 190px;
}

.sa-bar {
    height: 100%;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.sa-bar__track {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: end;
    padding: 0 8px;
    border-radius: 12px;
    background: #f1f5f9;
}

.sa-bar__track span {
    width: 100%;
    border-radius: 10px 10px 4px 4px;
    background: linear-gradient(180deg, #ffb86b, #f57c00);
}

.sa-bar small {
    color: #64748b;
    font-weight: 800;
}

.sa-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.sa-list-row {
    min-height: 58px;
    padding: 11px 12px;
    border: 1px solid #eef2f7;
    border-radius: 12px;
    background: #fbfcff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.sa-list-row strong {
    display: block;
    color: #172033;
    font-size: 13px;
}

.sa-row-end {
    display: flex;
    align-items: flex-end;
    flex-direction: column;
    gap: 5px;
}

.sa-pill {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    line-height: 1;
    background: #f1f5f9;
    color: #64748b;
}

.sa-pill.is-success { background: #ecfdf5; color: #059669; }
.sa-pill.is-warning { background: #fffbeb; color: #d97706; }
.sa-pill.is-danger { background: #fef2f2; color: #dc2626; }
.sa-pill.is-muted { background: #f1f5f9; color: #64748b; }

.sa-empty {
    padding: 16px;
    border-radius: 12px;
    background: #f8fafc;
    color: #94a3b8;
    text-align: center;
    font-weight: 700;
}

.sa-empty--large {
    margin-top: 16px;
    padding: 30px;
}

.sa-alert {
    margin-top: 16px;
    padding: 12px 14px;
    border: 1px solid #fecaca;
    border-radius: 12px;
    background: #fef2f2;
    color: #b91c1c;
    font-weight: 800;
}

.sa-skeleton-card,
.sa-skeleton-panel {
    pointer-events: none;
    overflow: hidden;
    position: relative;
}

.sa-skeleton-panel {
    min-height: 260px;
}

.sa-skeleton-card::before,
.sa-skeleton-panel::before {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.76), transparent);
    animation: sa-shimmer 1.35s infinite;
}

.sa-skeleton-line,
.sa-skeleton-icon {
    display: block;
    border-radius: 999px;
    background: #e5eaf2;
}

.sa-skeleton-line {
    height: 12px;
    margin-bottom: 12px;
}

.sa-skeleton-line.w-40 { width: 40%; }
.sa-skeleton-line.w-45 { width: 45%; }
.sa-skeleton-line.w-50 { width: 50%; }
.sa-skeleton-line.w-65 { width: 65%; }
.sa-skeleton-line.w-70 { width: 70%; }
.sa-skeleton-line.w-80 { width: 80%; }
.w-100 { width: 100%; }

.sa-skeleton-icon {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
}

@keyframes sa-shimmer {
    100% {
        transform: translateX(100%);
    }
}

.sa-vendor-section {
    margin-top: 16px;
}

.sa-vendor-select-wrap {
    width: min(360px, 100%);
}

.sa-vendor-content {
    display: grid;
    gap: 16px;
}

.sa-vendor-profile {
    border-radius: 16px;
    padding: 20px;
}

.sa-vendor-profile dl {
    margin: 0;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.sa-vendor-profile dd {
    margin: 3px 0 0;
    color: #111827;
    font-weight: 800;
    overflow-wrap: anywhere;
}

@media (max-width: 1199.98px) {
    .sa-stat-grid,
    .sa-system-grid,
    .sa-vendor-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sa-main-grid,
    .sa-vendor-profile dl {
        grid-template-columns: 1fr;
    }

    .sa-panel--wide {
        grid-column: auto;
    }
}

@media (max-width: 767.98px) {
    .sa-dashboard {
        padding: 14px;
    }

    .sa-hero,
    .sa-vendor-head {
        flex-direction: column;
    }

    .sa-hero {
        padding: 22px;
        background-image: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 247, 237, 0.98));
    }

    .sa-hero h1 {
        font-size: 28px;
    }

    .sa-stat-grid,
    .sa-system-grid,
    .sa-vendor-metrics {
        grid-template-columns: 1fr;
    }
}
</style>
