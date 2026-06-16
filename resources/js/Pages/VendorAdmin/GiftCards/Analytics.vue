<template>

  <Head title="Gift Card Analytics" />

  <main class="analytics-dashboard">
    <!-- Dashboard-style Hero -->
    <section class="dashboard-hero">
      <div class="hero-glow hero-glow--left"></div>
      <div class="hero-glow hero-glow--right"></div>

      <div class="hero-main">
        <span class="hero-badge">
          <i class="bi bi-graph-up-arrow"></i>
          Gift Card Overview
        </span>

        <h1>Gift Card Analytics</h1>

        <p class="hero-copy">
          Monitor gift card sales, redemptions, outstanding balances, batch performance
          and expiring cards in one clean workspace.
        </p>
      </div>

      <div class="hero-actions">
        <Link v-if="can('gift-cards.view')" :href="route('vendor.gift-cards.index')" class="soft-action">
          <i class="bi bi-gift"></i>
          <span>Gift Cards</span>
        </Link>

        <Link v-if="can('gift-cards.create')" :href="route('vendor.gift-cards.create')" class="primary-action">
          <i class="bi bi-plus-lg"></i>
          <span>Create Gift Card</span>
        </Link>
      </div>
    </section>

    <!-- KPIs -->
    <section class="stats-grid">
      <article v-for="stat in kpis" :key="stat.label" class="stat-card" :class="`stat-card--${stat.tone}`">
        <div class="stat-card__content">
          <span>{{ stat.label }}</span>
          <strong>{{ stat.value }}</strong>
          <small>{{ stat.help }}</small>
        </div>

        <div class="stat-card__icon">
          <i :class="stat.icon"></i>
        </div>
      </article>
    </section>

    <!-- Main charts -->
    <section class="charts-layout">
      <article class="panel panel--wide">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Performance</span>
            <h2>Gift Card Sales Over Time</h2>
            <p>Total issued / sold gift card value by date.</p>
          </div>

          <span class="panel-chip">
            {{ baseCurrencySymbol }} {{ moneyOnly(summary.sales_total) }}
          </span>
        </div>

        <div class="chart-box">
          <canvas ref="salesCanvas"></canvas>
          <div v-if="!hasArrayData(charts.sales_over_time?.values)" class="empty-chart">
            <i class="bi bi-check-circle"></i>
            <span>No sales data</span>
          </div>
        </div>
      </article>

      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Activity</span>
            <h2>Transaction Mix</h2>
            <p>Counts and value by transaction type.</p>
          </div>
          <span class="panel-chip panel-chip--blue">Transactions</span>
        </div>

        <div class="chart-box">
          <canvas ref="mixCanvas"></canvas>
          <div v-if="!hasArrayData(charts.transaction_mix?.counts)" class="empty-chart">
            <i class="bi bi-check-circle"></i>
            <span>No transaction data</span>
          </div>
        </div>
      </article>
    </section>

    <section class="charts-layout">
      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Cards</span>
            <h2>Cards Health</h2>
            <p>Status and scope overview.</p>
          </div>
        </div>

        <div class="chart-box">
          <canvas ref="healthCanvas"></canvas>
          <div v-if="!hasArrayData(charts.cards_health?.status)" class="empty-chart">
            <i class="bi bi-check-circle"></i>
            <span>No cards health data</span>
          </div>
        </div>
      </article>

      <article class="panel panel--wide">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Redemptions</span>
            <h2>Redemption Over Time</h2>
            <p>Gift card usage value by date.</p>
          </div>

          <span class="panel-chip panel-chip--green">
            {{ baseCurrencySymbol }} {{ moneyOnly(summary.redemption_total) }}
          </span>
        </div>

        <div class="chart-box">
          <canvas ref="redeemCanvas"></canvas>
          <div v-if="!hasArrayData(charts.redemption_over_time?.values)" class="empty-chart">
            <i class="bi bi-check-circle"></i>
            <span>No redemption data</span>
          </div>
        </div>
      </article>
    </section>

    <section class="charts-layout">
      <article class="panel panel--wide">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Weekly Usage</span>
            <h2>Gift Card Usage by Day</h2>
            <p>Redemption activity across the week.</p>
          </div>
        </div>

        <div class="chart-box">
          <canvas ref="dayCanvas"></canvas>
          <div v-if="!hasArrayData(charts.usage_by_day?.values)" class="empty-chart">
            <i class="bi bi-check-circle"></i>
            <span>No usage data</span>
          </div>
        </div>
      </article>

      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Balances</span>
            <h2>Top Balances</h2>
            <p>Cards with the highest remaining value.</p>
          </div>
        </div>

        <div class="clean-list">
          <article v-for="card in topBalances" :key="card.id" class="list-row">
            <div class="list-icon list-icon--amber">
              {{ initials(card.code) }}
            </div>

            <div class="list-main">
              <strong>{{ card.code }}</strong>
              <small>{{ card.branch?.name || 'No Branch' }}</small>
            </div>

            <b>{{ baseCurrencySymbol }} {{ moneyOnly(card.current_balance) }}</b>
          </article>

          <div v-if="!topBalances.length" class="empty-list">
            <i class="bi bi-check-circle"></i>
            <span>No top balance cards found</span>
          </div>
        </div>
      </article>
    </section>

    <!-- Tables -->
    <section class="content-grid">
      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Batch Flow</span>
            <h2>Batch Activity</h2>
            <p>Generated, used and remaining cards by batch.</p>
          </div>
        </div>

        <div class="modern-table-wrap">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Batch</th>
                <th>Branch</th>
                <th>Generated</th>
                <th>Used</th>
                <th>Remaining</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="batch in batchActivity" :key="batch.id">
                <td><strong>{{ batch.name }}</strong></td>
                <td>{{ batch.branch?.name || '-' }}</td>
                <td><span class="count-pill count-pill--blue">{{ number(batch.cards_generated) }}</span></td>
                <td><span class="count-pill count-pill--danger">{{ number(batch.cards_used) }}</span></td>
                <td><span class="count-pill count-pill--green">{{ number(remainingCards(batch)) }}</span></td>
              </tr>

              <tr v-if="!batchActivity.length">
                <td colspan="5" class="table-empty">No batch activity found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="panel">
        <div class="panel-head">
          <div>
            <span class="panel-kicker">Expiry Risk</span>
            <h2>Expiring Cards</h2>
            <p>Cards approaching expiry date.</p>
          </div>
        </div>

        <div class="modern-table-wrap">
          <table class="modern-table">
            <thead>
              <tr>
                <th>Card</th>
                <th>Customer</th>
                <th>Expiry</th>
                <th>Balance</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="card in expiringCards" :key="card.id">
                <td><strong>{{ card.code }}</strong></td>
                <td>{{ card.customer?.name || '-' }}</td>
                <td><span class="date-pill">{{ card.expires_at || '-' }}</span></td>
                <td>{{ baseCurrencySymbol }} {{ moneyOnly(card.current_balance) }}</td>
              </tr>

              <tr v-if="!expiringCards.length">
                <td colspan="4" class="table-empty">No expiring cards found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </section>

    <section class="panel">
      <div class="panel-head">
        <div>
          <span class="panel-kicker">Latest Activity</span>
          <h2>Recent Transactions</h2>
          <p>Latest purchase, redemption, recharge and adjustment records.</p>
        </div>
      </div>

      <div class="transaction-list">
        <article v-for="tx in recentTransactions" :key="tx.id" class="transaction-row">
          <div class="transaction-row__icon">
            <i :class="transactionIcon(tx.type)"></i>
          </div>

          <div class="transaction-row__main">
            <strong>{{ tx.card?.code || '-' }}</strong>
            <span>{{ labelize(tx.type) }} · {{ tx.branch?.name || 'No Branch' }}</span>
          </div>

          <div class="transaction-row__amount">
            {{ currencySymbol(tx.currency_code || baseCurrencyCode) }} {{ moneyOnly(tx.amount) }}
          </div>
        </article>

        <div v-if="!recentTransactions.length" class="empty-list">
          <i class="bi bi-check-circle"></i>
          <span>No recent transactions found</span>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Chart, registerables } from 'chart.js'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { currencySymbol } from '@/Utils/currency'
import { usePermission } from "@/composables/usePermission"

defineOptions({ layout: VendorAdminLayout })

const { can } = usePermission()

Chart.register(...registerables)

const props = defineProps({
  stats: { type: Object, required: true },
  charts: { type: Object, required: true },
  lists: { type: Object, required: true },
  summary: { type: Object, required: true },
})

const page = usePage()

const healthCanvas = ref(null)
const mixCanvas = ref(null)
const salesCanvas = ref(null)
const redeemCanvas = ref(null)
const dayCanvas = ref(null)

const chartInstances = []

const baseCurrencyCode = computed(() => {
  return page.props.currencySettings?.base_currency?.code || 'LKR'
})

const secondaryCurrencyCode = computed(() => {
  return page.props.currencySettings?.secondary_currency?.code || ''
})

const baseCurrencySymbol = computed(() => currencySymbol(baseCurrencyCode.value))
const secondaryCurrencySymbol = computed(() => currencySymbol(secondaryCurrencyCode.value))

const topBalances = computed(() => safeArray(props.lists?.top_balances))
const recentTransactions = computed(() => safeArray(props.lists?.recent_transactions))
const batchActivity = computed(() => safeArray(props.lists?.batch_activity))
const expiringCards = computed(() => safeArray(props.lists?.expiring_cards))

const kpis = computed(() => {
  const rows = [
    {
      label: 'Total Cards',
      value: number(props.stats.total_cards),
      help: 'Issued gift cards',
      icon: 'bi bi-postcard',
      tone: 'blue',
    },
    {
      label: 'Outstanding Balance',
      value: `${baseCurrencySymbol.value} ${moneyOnly(props.stats.outstanding_balance)}`,
      help: 'Unused customer value',
      icon: 'bi bi-wallet2',
      tone: 'amber',
    },
    {
      label: 'Sold Value',
      value: `${baseCurrencySymbol.value} ${moneyOnly(props.stats.sold_value)}`,
      help: 'Total gift card sales',
      icon: 'bi bi-cart4',
      tone: 'green',
    },
    // {
    //   label: 'Redeemed Value',
    //   value: `${baseCurrencyCode.value} ${moneyOnly(props.stats.redeemed_value)}`,
    //   help: 'Used in orders',
    //   icon: 'bi bi-cash-coin',
    //   tone: 'purple',
    // },
    {
      label: 'Transactions',
      value: number(props.stats.total_transactions),
      help: 'All card movements',
      icon: 'bi bi-arrow-left-right',
      tone: 'green',
    },
    {
      label: 'Batches',
      value: number(props.stats.total_batches),
      help: 'Generated batch groups',
      icon: 'bi bi-layers',
      tone: 'blue',
    },
    {
      label: 'Batch Face Value',
      value: `${baseCurrencySymbol.value} ${moneyOnly(props.stats.batch_face_value)}`,
      help: 'Total generated value',
      icon: 'bi bi-credit-card',
      tone: 'amber',
    },
    {
      label: 'Expiring Soon',
      value: number(props.stats.expiring_soon),
      help: 'Cards near expiry',
      icon: 'bi bi-calendar2-exclamation',
      tone: 'danger',
    },
  ]

  if (secondaryCurrencyCode.value) {
    rows.splice(2, 0, {
      label: 'Secondary Balance',
      value: `${secondaryCurrencySymbol.value} ${moneyOnly(props.stats.secondary_outstanding_balance)}`,
      help: 'Secondary currency value',
      icon: 'bi bi-wallet',
      tone: 'purple',
    })
  }

  return rows
})

function safeArray(value) {
  return Array.isArray(value) ? value : []
}

function number(value) {
  return Number(value || 0).toLocaleString()
}

function moneyOnly(value) {
  return Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 3,
    maximumFractionDigits: 3,
  })
}

function initials(value) {
  const text = String(value || 'GC').replaceAll('-', '').trim()
  return text.slice(0, 2).toUpperCase() || 'GC'
}

function labelize(value) {
  return String(value || '-')
    .replaceAll('_', ' ')
    .replaceAll('-', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function remainingCards(batch) {
  return Math.max(0, Number(batch.cards_generated || 0) - Number(batch.cards_used || 0))
}

function transactionIcon(type) {
  const key = String(type || '').toLowerCase()
  if (key.includes('redeem')) return 'bi bi-bag-check'
  if (key.includes('recharge')) return 'bi bi-plus-circle'
  if (key.includes('adjust')) return 'bi bi-sliders'
  if (key.includes('purchase') || key.includes('sale')) return 'bi bi-cart-check'
  return 'bi bi-arrow-left-right'
}

function hasArrayData(values) {
  return safeArray(values).some((value) => Number(value) > 0)
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

function chartOptions(formatter = (value) => value) {
  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
      legend: { display: false },
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

async function renderCharts() {
  destroyCharts()
  await nextTick()

  addChart(salesCanvas.value, {
    type: 'line',
    data: {
      labels: safeArray(props.charts.sales_over_time?.labels),
      datasets: [{
        label: 'Sales',
        data: safeArray(props.charts.sales_over_time?.values),
        borderColor: '#f57c00',
        backgroundColor: 'rgba(245, 124, 0, 0.22)',
        borderWidth: 3,
        fill: true,
        tension: 0.42,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#f57c00',
        pointBorderWidth: 2,
      }],
    },
    options: chartOptions((value) => `${baseCurrencySymbol.value} ${moneyOnly(value)}`),
  })

  addChart(redeemCanvas.value, {
    type: 'line',
    data: {
      labels: safeArray(props.charts.redemption_over_time?.labels),
      datasets: [{
        label: 'Redemptions',
        data: safeArray(props.charts.redemption_over_time?.values),
        borderColor: '#059669',
        backgroundColor: 'rgba(5, 150, 105, 0.2)',
        borderWidth: 3,
        fill: true,
        tension: 0.42,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#059669',
        pointBorderWidth: 2,
      }],
    },
    options: chartOptions((value) => `${baseCurrencySymbol.value} ${moneyOnly(value)}`),
  })

  addChart(healthCanvas.value, {
    type: 'doughnut',
    data: {
      labels: safeArray(props.charts.cards_health?.labels),
      datasets: [{
        data: safeArray(props.charts.cards_health?.status),
        backgroundColor: ['#22c55e', '#f57c00', '#e11d48', '#475569', '#2563eb'],
        borderColor: '#ffffff',
        borderWidth: 5,
        hoverOffset: 8,
      }],
    },
    options: doughnutOptions(),
  })

  addChart(mixCanvas.value, {
    type: 'bar',
    data: {
      labels: safeArray(props.charts.transaction_mix?.labels),
      datasets: [
        {
          label: 'Transactions',
          data: safeArray(props.charts.transaction_mix?.counts),
          backgroundColor: '#2563eb',
          borderRadius: 12,
          borderSkipped: false,
          maxBarThickness: 38,
          yAxisID: 'y',
        },
        {
          type: 'line',
          label: 'Amount',
          data: safeArray(props.charts.transaction_mix?.amounts),
          borderColor: '#f57c00',
          backgroundColor: '#f57c00',
          borderWidth: 3,
          pointRadius: 4,
          tension: 0.4,
          yAxisID: 'y1',
        },
      ],
    },
    options: {
      ...chartOptions(number),
      scales: {
        x: {
          border: { display: false },
          grid: { display: false },
          ticks: { color: '#64748b', font: { size: 11, weight: 700 } },
        },
        y: {
          beginAtZero: true,
          position: 'left',
          border: { display: false },
          grid: { color: 'rgba(148, 163, 184, 0.18)' },
          ticks: { color: '#94a3b8', precision: 0 },
        },
        y1: {
          beginAtZero: true,
          position: 'right',
          border: { display: false },
          grid: { drawOnChartArea: false },
          ticks: { color: '#94a3b8' },
        },
      },
    },
  })

  addChart(dayCanvas.value, {
    type: 'bar',
    data: {
      labels: safeArray(props.charts.usage_by_day?.labels),
      datasets: [{
        label: 'Usage',
        data: safeArray(props.charts.usage_by_day?.values),
        backgroundColor: ['#f57c00', '#2563eb', '#059669', '#7c3aed', '#e11d48', '#14b8a6', '#475569'],
        borderRadius: 12,
        borderSkipped: false,
        maxBarThickness: 42,
      }],
    },
    options: chartOptions(number),
  })
}

onMounted(renderCharts)
onUnmounted(destroyCharts)

watch(
  () => [props.stats, props.charts, props.lists, props.summary],
  renderCharts,
  { deep: true },
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

.stat-card--amber {
  color: #f57c00;
}

.stat-card--blue {
  color: #2563eb;
}

.stat-card--green {
  color: #059669;
}

.stat-card--purple {
  color: #7c3aed;
}

.stat-card--danger {
  color: #e11d48;
}

.stat-card--amber .stat-card__icon {
  background: #fff7ed;
  border: 1px solid #fed7aa;
}

.stat-card--blue .stat-card__icon {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.stat-card--green .stat-card__icon {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
}

.stat-card--purple .stat-card__icon {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
}

.stat-card--danger .stat-card__icon {
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.charts-layout,
.content-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.charts-layout {
  grid-template-columns: minmax(0, 1.45fr) minmax(340px, 0.85fr);
}

.content-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
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

.panel-chip--green {
  color: #047857;
  background: #ecfdf5;
  border-color: #a7f3d0;
}

.chart-box {
  position: relative;
  height: 310px;
}

.chart-box canvas {
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

.clean-list,
.transaction-list {
  display: grid;
  gap: 10px;
  max-height: 310px;
  overflow: auto;
}

.list-row,
.transaction-row {
  min-height: 64px;
  border-radius: 16px;
  padding: 12px;
  display: grid;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.list-row {
  grid-template-columns: 42px minmax(0, 1fr) auto;
}

.transaction-row {
  grid-template-columns: 42px minmax(0, 1fr) auto;
}

.list-icon,
.transaction-row__icon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: grid;
  place-items: center;
}

.list-icon--amber {
  color: #f57c00;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  font-weight: 950;
}

.transaction-row__icon {
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.list-main,
.transaction-row__main {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.list-main strong,
.transaction-row__main strong {
  color: #0f172a;
  font-weight: 900;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.list-main small,
.transaction-row__main span {
  color: #64748b;
  font-size: 12px;
  font-weight: 750;
}

.list-row b,
.transaction-row__amount {
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

.modern-table-wrap {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  min-width: 620px;
  border-collapse: separate;
  border-spacing: 0 10px;
}

.modern-table thead th {
  color: #94a3b8;
  font-size: 12px;
  font-weight: 950;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0 14px 4px;
  white-space: nowrap;
}

.modern-table tbody tr {
  background: #f8fafc;
  box-shadow: 0 0 0 1px #eef2f7;
}

.modern-table tbody td {
  padding: 14px;
  color: #475569;
  font-size: 13px;
  font-weight: 750;
  white-space: nowrap;
}

.modern-table tbody td:first-child {
  border-radius: 16px 0 0 16px;
}

.modern-table tbody td:last-child {
  border-radius: 0 16px 16px 0;
}

.modern-table strong {
  color: #0f172a;
  font-weight: 950;
}

.count-pill,
.date-pill {
  width: fit-content;
  min-height: 30px;
  border-radius: 999px;
  padding: 0 11px;
  display: inline-flex;
  align-items: center;
  font-size: 12px;
  font-weight: 950;
}

.count-pill--blue {
  color: #1d4ed8;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.count-pill--danger {
  color: #be123c;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.count-pill--green {
  color: #047857;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
}

.date-pill {
  color: #be123c;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.table-empty {
  text-align: center;
  color: #94a3b8 !important;
  font-weight: 850 !important;
}

@media (max-width: 1360px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .charts-layout,
  .content-grid {
    grid-template-columns: 1fr;
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

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .panel,
  .stat-card {
    border-radius: 18px;
  }

  .chart-box {
    height: 260px;
  }

  .list-row,
  .transaction-row {
    grid-template-columns: 42px minmax(0, 1fr);
  }

  .list-row b,
  .transaction-row__amount {
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
