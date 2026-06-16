<template>
  <Head title="Inventory Analytics" />

  <main class="analytics-dashboard">
    <!-- Dashboard-style Hero -->
    <section class="dashboard-hero">
      <div class="hero-glow hero-glow--left"></div>
      <div class="hero-glow hero-glow--right"></div>

      <div class="hero-main">
        <span class="hero-badge">
          <i class="bi bi-bar-chart-line"></i>
          Inventory Overview
        </span>

        <h1>Inventory Analytics</h1>

        <p class="hero-copy">
          Real-time insights into purchases, supplier performance, stock movements,
          low-stock risks, wastage and spoilage in one clean workspace.
        </p>
      </div>

    </section>

    <!-- Filters -->
    <section class="panel filter-panel">
      <div class="panel-head">
        <div>
          <span class="panel-kicker">Filters</span>
          <h2>Analytics Filters</h2>
          <p>Filter inventory analytics by branch and date range.</p>
        </div>
      </div>

      <div class="filter-grid">
        <div class="field-wrap">
          <label>Branch</label>
          <SelectInput
            id="branch_id"
            v-model="form.branch_id"
            :options="branchOptions"
            valueKey="id"
            labelKey="name"
            placeholder="All Branches"
          />
        </div>

        <div class="field-wrap">
          <label>From Date</label>
          <DatePicker v-model="form.from" placeholder="Select Date Time" />
        </div>

        <div class="field-wrap">
          <label>To Date</label>
          <DatePicker v-model="form.to" placeholder="Select Date Time" />
        </div>

        <div class="filter-actions">
          <button type="button" class="primary-action filter-btn" @click="applyFilters">
            Apply
          </button>
          <button type="button" class="soft-action filter-btn" @click="resetFilters">
            Reset
          </button>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
      <article
        v-for="stat in statsArray"
        :key="stat.label"
        class="stat-card"
        :class="`stat-card--${stat.tone}`"
      >
        <div class="stat-card__content">
          <span>{{ stat.label }}</span>
          <strong>{{ stat.formattedValue }}</strong>
          <small>{{ stat.help }}</small>
        </div>

        <div class="stat-card__icon">
          <i :class="stat.icon"></i>
        </div>
      </article>
    </section>

    <!-- Fast Moving -->
    <section class="panel">
      <div class="panel-head">
        <div>
          <span class="panel-kicker">Usage Trend</span>
          <h2>Fast Moving Ingredients</h2>
          <p>Ingredient usage trend over the selected period.</p>
        </div>

        <span class="panel-chip">
          {{ fastMovingDatasetCount }} Datasets
        </span>
      </div>

      <div class="chart-box chart-box--large">
        <canvas ref="fastMovingCanvas"></canvas>
        <div v-if="!hasDatasetData(charts.fast_moving?.datasets)" class="empty-chart">
          <i class="bi bi-check-circle"></i>
          <span>No fast moving ingredient data</span>
        </div>
      </div>
    </section>

    <!-- Main charts -->
    <section class="charts-layout">
      <article class="panel panel--wide">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Suppliers</span>
            <h2>Top Suppliers by Purchase Amount</h2>
            <p>Suppliers ranked by total purchase amount.</p>
          </div>
          <span class="panel-chip panel-chip--blue">Purchases</span>
        </div>

        <div class="chart-box">
          <canvas ref="topSuppliersCanvas"></canvas>
          <div v-if="!hasArrayData(charts.top_suppliers?.data)" class="empty-chart">
            <i class="bi bi-check-circle"></i>
            <span>No supplier data</span>
          </div>
        </div>
      </article>

      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Purchases</span>
            <h2>Ingredient Purchases</h2>
            <p>Purchased quantity by ingredient.</p>
          </div>
        </div>

        <div class="clean-list">
          <article
            v-for="item in list('ingredient_purchases_summary')"
            :key="item.name"
            class="list-row"
          >
            <div class="list-icon list-icon--blue">
              <i class="bi bi-box-seam"></i>
            </div>

            <div class="list-main">
              <strong>{{ item.name }}</strong>
              <small>Purchased quantity</small>
            </div>

            <b>{{ number(item.quantity) }} {{ item.unit_symbol || '' }}</b>
          </article>

          <div v-if="!list('ingredient_purchases_summary').length" class="empty-list">
            <i class="bi bi-check-circle"></i>
            <span>No purchase summary available</span>
          </div>
        </div>
      </article>
    </section>

    <section class="charts-layout">
      <article class="panel panel--wide">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Wastage</span>
            <h2>Most Wasted Ingredients</h2>
            <p>Ingredients with the highest wastage quantity.</p>
          </div>
          <span class="panel-chip panel-chip--danger">Risk</span>
        </div>

        <div class="chart-box">
          <canvas ref="mostWastedCanvas"></canvas>
          <div v-if="!hasArrayData(charts.most_wasted?.data)" class="empty-chart">
            <i class="bi bi-check-circle"></i>
            <span>No wastage data</span>
          </div>
        </div>
      </article>

      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Stock Alerts</span>
            <h2>Low Stock Ingredients</h2>
            <p>Items currently below alert quantity.</p>
          </div>
        </div>

        <div class="clean-list">
          <article
            v-for="item in list('low_stock_ingredients')"
            :key="item.name"
            class="list-row list-row--danger"
          >
            <div class="list-icon list-icon--danger">
              <i class="bi bi-exclamation-lg"></i>
            </div>

            <div class="list-main">
              <strong>{{ item.name }}</strong>
              <small>Alert: {{ number(item.alert_quantity) }} {{ item.unit_symbol || '' }}</small>
            </div>

            <b>{{ number(item.current_stock) }} / {{ number(item.alert_quantity) }}</b>
          </article>

          <div v-if="!list('low_stock_ingredients').length" class="empty-list">
            <i class="bi bi-check-circle"></i>
            <span>No low stock ingredients</span>
          </div>
        </div>
      </article>
    </section>

    <!-- Bottom charts -->
    <section class="bottom-grid">
      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Orders</span>
            <h2>Purchase Order Status</h2>
            <p>Purchase orders by status.</p>
          </div>
        </div>

        <div class="mini-chart">
          <canvas ref="purchaseStatusCanvas"></canvas>
          <div v-if="!hasArrayData(charts.purchase_status?.data)" class="empty-chart">
            No status data
          </div>
        </div>

        <div class="chart-legend">
          <div
            v-for="item in purchaseStatusLegend"
            :key="item.label"
            class="legend-item"
          >
            <div class="legend-left">
              <span class="legend-dot" :style="{ backgroundColor: item.color }"></span>
              <span>{{ item.label }}</span>
            </div>
            <strong>{{ number(item.value) }}</strong>
          </div>
        </div>
      </article>

      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Movements</span>
            <h2>Stock Movement Summary</h2>
            <p>Stock movement type distribution.</p>
          </div>
        </div>

        <div class="mini-chart">
          <canvas ref="stockMovementCanvas"></canvas>
          <div v-if="!hasArrayData(charts.stock_movement_summary?.data)" class="empty-chart">
            No movement data
          </div>
        </div>

        <div class="chart-legend">
          <div
            v-for="item in stockMovementLegend"
            :key="item.label"
            class="legend-item"
          >
            <div class="legend-left">
              <span class="legend-dot" :style="{ backgroundColor: item.color }"></span>
              <span>{{ item.label }}</span>
            </div>
            <strong>{{ number(item.value) }}</strong>
          </div>
        </div>
      </article>

      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Loss Control</span>
            <h2>Wastage & Spoilage</h2>
            <p>Wastage and spoilage split.</p>
          </div>
        </div>

        <div class="mini-chart">
          <canvas ref="wastageCanvas"></canvas>
          <div v-if="!hasArrayData(charts.wastage_spoilage?.data)" class="empty-chart">
            No wastage data
          </div>
        </div>

        <div class="chart-legend">
          <div
            v-for="item in wastageLegend"
            :key="item.label"
            class="legend-item"
          >
            <div class="legend-left">
              <span class="legend-dot" :style="{ backgroundColor: item.color }"></span>
              <span>{{ item.label }}</span>
            </div>
            <strong>{{ number(item.value) }}</strong>
          </div>
        </div>
      </article>
    </section>
  </main>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import { Chart, registerables } from 'chart.js'
import DatePicker from '@/Components/DatePicker.vue'
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: VendorAdminLayout })

Chart.register(...registerables)

const props = defineProps({
  branches: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
  lists: { type: Object, default: () => ({}) },
})

const page = usePage()

const form = ref({
  branch_id: props.filters?.branch_id ?? '',
  from: props.filters?.from ?? '',
  to: props.filters?.to ?? '',
})

const fastMovingCanvas = ref(null)
const topSuppliersCanvas = ref(null)
const mostWastedCanvas = ref(null)
const purchaseStatusCanvas = ref(null)
const stockMovementCanvas = ref(null)
const wastageCanvas = ref(null)

const chartInstances = []

const baseCurrencyCode = computed(() => {
  return page.props.currencySettings?.base_currency?.code || 'LKR'
})

const branchOptions = computed(() => [
  { id: '', name: 'All Branches' },
  ...props.branches,
])

const fastMovingDatasetCount = computed(() => safeArray(props.charts?.fast_moving?.datasets).length)

const statsArray = computed(() => [
  {
    label: 'Purchase Value',
    formattedValue: money(statValue(['purchase_total', 'total_purchase_amount', 'total_purchases'])),
    help: 'Total purchased amount',
    icon: 'bi bi-cash-stack',
    tone: 'amber',
  },
  {
    label: 'Purchase Orders',
    formattedValue: number(statValue(['purchase_orders', 'purchase_order_count', 'total_purchase_orders'])),
    help: 'Orders in selected period',
    icon: 'bi bi-receipt',
    tone: 'blue',
  },
  {
    label: 'Stock Movements',
    formattedValue: number(statValue(['stock_movements', 'stock_movement_count', 'total_stock_movements'])),
    help: 'Inventory movement records',
    icon: 'bi bi-arrow-left-right',
    tone: 'green',
  },
  {
    label: 'Wastage Value',
    formattedValue: money(statValue(['wastage_total', 'wastage_value', 'total_wastage'])),
    help: 'Estimated wastage value',
    icon: 'bi bi-trash3',
    tone: 'danger',
  },
  {
    label: 'Low Stock',
    formattedValue: number(statValue(['low_stock_count'], list('low_stock_ingredients').length)),
    help: 'Items below alert quantity',
    icon: 'bi bi-exclamation-triangle',
    tone: 'purple',
  },
  {
    label: 'Ingredients Purchased',
    formattedValue: number(list('ingredient_purchases_summary').length),
    help: 'Ingredients with purchase records',
    icon: 'bi bi-boxes',
    tone: 'green',
  },
  {
    label: 'Fast Moving Sets',
    formattedValue: number(fastMovingDatasetCount.value),
    help: 'Tracked ingredient trends',
    icon: 'bi bi-lightning-charge',
    tone: 'amber',
  },
  {
    label: 'Waste Items',
    formattedValue: number(safeArray(props.charts?.most_wasted?.labels).length),
    help: 'Ingredients in wastage chart',
    icon: 'bi bi-clipboard-x',
    tone: 'danger',
  },
])

const purchaseStatusLegend = computed(() => legendRows(
  props.charts.purchase_status?.labels,
  props.charts.purchase_status?.data,
  ['#22c55e', '#f57c00', '#2563eb', '#e11d48', '#7c3aed'],
))

const stockMovementLegend = computed(() => legendRows(
  props.charts.stock_movement_summary?.labels,
  props.charts.stock_movement_summary?.data,
  ['#f57c00', '#14b8a6', '#2563eb', '#7c3aed', '#e11d48', '#475569'],
))

const wastageLegend = computed(() => legendRows(
  props.charts.wastage_spoilage?.labels,
  props.charts.wastage_spoilage?.data,
  ['#e11d48', '#f57c00', '#facc15'],
))

function safeArray(value) {
  return Array.isArray(value) ? value : []
}

function list(key) {
  return safeArray(props.lists?.[key])
}

function statValue(keys, fallback = 0) {
  for (const key of keys) {
    const value = props.stats?.[key]
    if (value !== undefined && value !== null && value !== '') return value
  }
  return fallback
}

function number(value) {
  return Number(value || 0).toLocaleString()
}

function money(value) {
  const amount = Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 3,
    maximumFractionDigits: 3,
  })

  return `${baseCurrencyCode.value} ${amount}`
}

function hasArrayData(values) {
  return safeArray(values).some((value) => Number(value) > 0)
}

function hasDatasetData(datasets) {
  return safeArray(datasets).some((dataset) => hasArrayData(dataset?.data))
}

function legendRows(labels, values, colors) {
  return safeArray(labels).map((label, index) => ({
    label,
    value: Number(safeArray(values)[index] || 0),
    color: colors[index] || '#94a3b8',
  }))
}

function applyFilters() {
  router.get(
    route('vendor.inventory-analytics.index'),
    {
      branch_id: form.value.branch_id || null,
      from: form.value.from || null,
      to: form.value.to || null,
    },
    {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    },
  )
}

function resetFilters() {
  router.get(
    route('vendor.inventory-analytics.index'),
    {},
    {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    },
  )
}

function destroyCharts() {
  while (chartInstances.length) {
    chartInstances.pop()?.destroy()
  }
}

function addChart(canvas, config) {
  if (!canvas) return
  chartInstances.push(new Chart(canvas, config))
}

function chartOptions(formatter = number) {
  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        backgroundColor: '#111827',
        titleColor: '#ffffff',
        bodyColor: '#ffffff',
        padding: 12,
        cornerRadius: 12,
        callbacks: {
          label: (item) => ` ${item.dataset.label || 'Value'}: ${formatter(item.raw)}`,
        },
      },
    },
    scales: {
      x: {
        border: { display: false },
        grid: { display: false },
        ticks: { color: '#64748b', font: { size: 11, weight: 700 } },
      },
      y: {
        beginAtZero: true,
        border: { display: false },
        grid: { color: 'rgba(148, 163, 184, 0.18)' },
        ticks: { color: '#94a3b8', precision: 0, callback: formatter },
      },
    },
  }
}

function doughnutOptions() {
  return {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#111827',
        titleColor: '#ffffff',
        bodyColor: '#ffffff',
        padding: 12,
        cornerRadius: 12,
      },
    },
  }
}

function makeLineDatasets(datasets) {
  const colors = ['#f57c00', '#2563eb', '#14b8a6', '#7c3aed', '#e11d48', '#059669']

  return safeArray(datasets).map((dataset, index) => {
    const color = colors[index % colors.length]

    return {
      label: dataset.label,
      data: safeArray(dataset.data),
      borderColor: color,
      backgroundColor: `${color}24`,
      borderWidth: 3,
      fill: true,
      tension: 0.42,
      pointRadius: 4,
      pointHoverRadius: 6,
      pointBackgroundColor: '#ffffff',
      pointBorderColor: color,
      pointBorderWidth: 2,
    }
  })
}

async function renderCharts() {
  destroyCharts()
  await nextTick()

  addChart(fastMovingCanvas.value, {
    type: 'line',
    data: {
      labels: safeArray(props.charts.fast_moving?.labels),
      datasets: makeLineDatasets(props.charts.fast_moving?.datasets),
    },
    options: {
      ...chartOptions(number),
      plugins: {
        ...chartOptions(number).plugins,
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            usePointStyle: true,
            boxWidth: 9,
            boxHeight: 9,
            font: { size: 11, weight: 700 },
          },
        },
      },
    },
  })

  addChart(topSuppliersCanvas.value, {
    type: 'bar',
    data: {
      labels: safeArray(props.charts.top_suppliers?.labels),
      datasets: [{
        label: 'Purchase Amount',
        data: safeArray(props.charts.top_suppliers?.data),
        backgroundColor: '#2563eb',
        borderRadius: 12,
        borderSkipped: false,
        maxBarThickness: 38,
      }],
    },
    options: chartOptions(money),
  })

  addChart(mostWastedCanvas.value, {
    type: 'bar',
    data: {
      labels: safeArray(props.charts.most_wasted?.labels),
      datasets: [{
        label: 'Wasted Quantity',
        data: safeArray(props.charts.most_wasted?.data),
        backgroundColor: '#e11d48',
        borderRadius: 12,
        borderSkipped: false,
        maxBarThickness: 38,
      }],
    },
    options: chartOptions(number),
  })

  addChart(purchaseStatusCanvas.value, {
    type: 'doughnut',
    data: {
      labels: safeArray(props.charts.purchase_status?.labels),
      datasets: [{
        data: safeArray(props.charts.purchase_status?.data),
        backgroundColor: ['#22c55e', '#f57c00', '#2563eb', '#e11d48', '#7c3aed'],
        borderColor: '#ffffff',
        borderWidth: 5,
        hoverOffset: 8,
      }],
    },
    options: doughnutOptions(),
  })

  addChart(stockMovementCanvas.value, {
    type: 'doughnut',
    data: {
      labels: safeArray(props.charts.stock_movement_summary?.labels),
      datasets: [{
        data: safeArray(props.charts.stock_movement_summary?.data),
        backgroundColor: ['#f57c00', '#14b8a6', '#2563eb', '#7c3aed', '#e11d48', '#475569'],
        borderColor: '#ffffff',
        borderWidth: 5,
        hoverOffset: 8,
      }],
    },
    options: doughnutOptions(),
  })

  addChart(wastageCanvas.value, {
    type: 'doughnut',
    data: {
      labels: safeArray(props.charts.wastage_spoilage?.labels),
      datasets: [{
        data: safeArray(props.charts.wastage_spoilage?.data),
        backgroundColor: ['#e11d48', '#f57c00', '#facc15'],
        borderColor: '#ffffff',
        borderWidth: 5,
        hoverOffset: 8,
      }],
    },
    options: doughnutOptions(),
  })
}

onMounted(renderCharts)
onBeforeUnmount(destroyCharts)

watch(
  () => [props.charts, props.stats, props.lists],
  renderCharts,
  { deep: true },
)

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) {
      alertSuccess((flash.message))
    }

    if (flash?.error) {
      alertError((flash.error))
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.analytics-dashboard {
  min-height: 100vh;
  display: grid;
  gap: 18px;
}

.analytics-dashboard,
.analytics-dashboard * {
  box-sizing: border-box;
}

.dashboard-hero,
.panel,
.stat-card {
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
  max-width: 780px;
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
  max-width: 660px;
  margin: 12px 0 0;
  color: #64748b;
  font-size: 15px;
  line-height: 1.65;
  font-weight: 650;
}

.hero-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
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
  border: 0;
  text-decoration: none;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.primary-action:hover,
.soft-action:hover {
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

.filter-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
  gap: 14px;
  align-items: end;
}

.field-wrap label {
  display: block;
  margin-bottom: 8px;
  color: #475569;
  font-size: 12px;
  font-weight: 950;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.filter-actions {
  display: flex;
  gap: 10px;
}

.filter-btn {
  min-width: 110px;
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

.stat-card__content span {
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

.stat-card__icon {
  position: relative;
  z-index: 1;
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  font-size: 22px;
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

.charts-layout,
.bottom-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.charts-layout {
  grid-template-columns: minmax(0, 1.45fr) minmax(340px, 0.85fr);
}

.bottom-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.panel {
  border-radius: 22px;
  padding: 20px;
  min-width: 0;
}

.panel--wide {
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
  color: #64748b;
  font-size: 12px;
  font-weight: 750;
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
  white-space: nowrap;
}

.panel-chip--blue {
  color: #1d4ed8;
  background: #eff6ff;
  border-color: #bfdbfe;
}

.panel-chip--danger {
  color: #be123c;
  background: #fff1f2;
  border-color: #fecdd3;
}

.chart-box {
  position: relative;
  height: 310px;
}

.chart-box--large {
  height: 360px;
}

.mini-chart {
  position: relative;
  height: 245px;
}

.chart-box canvas,
.mini-chart canvas {
  width: 100% !important;
  height: 100% !important;
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
  color: #94a3b8;
  font-size: 13px;
  font-weight: 850;
}

.clean-list {
  display: grid;
  gap: 10px;
  max-height: 310px;
  overflow: auto;
}

.list-row {
  min-height: 64px;
  border-radius: 16px;
  padding: 12px;
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.list-row--danger {
  background: #fffafa;
}

.list-icon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: grid;
  place-items: center;
}

.list-icon--blue {
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.list-icon--danger {
  color: #e11d48;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.list-main {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.list-main strong {
  color: #0f172a;
  font-weight: 900;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.list-main small {
  color: #64748b;
  font-size: 12px;
  font-weight: 750;
}

.list-row b {
  color: #0f172a;
  font-weight: 950;
  white-space: nowrap;
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
  color: #94a3b8;
  font-size: 13px;
  font-weight: 850;
  text-align: center;
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

.legend-left span:last-child {
  color: #0f172a;
  font-size: 13px;
  font-weight: 800;
}

.legend-item strong {
  color: #0f172a;
  font-size: 13px;
  font-weight: 900;
}

:deep(.form-control),
:deep(.formControl),
:deep(.form-select) {
  min-height: 44px;
  border-radius: 14px;
  border-color: #d7dee7;
}

:deep(.form-control:focus),
:deep(.formControl:focus),
:deep(.form-select:focus) {
  border-color: #f57c00;
  box-shadow: 0 0 0 4px rgba(245, 124, 0, 0.12);
}

@media (max-width: 1360px) {
  .stats-grid,
  .bottom-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .charts-layout,
  .filter-grid {
    grid-template-columns: 1fr;
  }

  .filter-actions {
    width: 100%;
  }

  .filter-btn {
    flex: 1;
  }
}

@media (max-width: 820px) {
  .analytics-dashboard {
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

  .stats-grid,
  .bottom-grid {
    grid-template-columns: 1fr;
  }

  .panel,
  .stat-card {
    border-radius: 18px;
  }

  .chart-box,
  .chart-box--large {
    height: 260px;
  }

  .mini-chart {
    height: 220px;
  }

  .list-row {
    grid-template-columns: 42px minmax(0, 1fr);
  }

  .list-row b {
    grid-column: 2;
  }
}

@media (max-width: 520px) {
  .panel-head {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>