<template>
  <VendorAdminLayout>
    <Head :title="report.title" />

    <main class="report-detail">
      <header class="detail-header">
        <div class="title-block">
          <div class="title-icon">
            <component :is="iconFor(report.icon)" :size="24" />
          </div>

          <div>
            <div class="breadcrumb-title">
              Reports
              <span v-if="report.section_title">/ {{ report.section_title }}</span>
            </div>
            <h1>{{ report.title }}</h1>
            <p>{{ report.description }}</p>

           
          </div>
        </div>

        <Link :href="route('vendor.reports.index')" class="back-link">
          <component :is="ArrowLeftIcon" :size="16" />
          Back to reports
        </Link>
      </header>

      <section class="filter-panel">
        <div class="filter-panel__top">
          <div>
            <h2>Report Filters</h2>
            <p>Filter the shop POS report by period, branch, currency, and search terms.</p>
          </div>

           <div v-if="canAnyExport" ref="exportDropdownRef" class="export-dropdown">
  <button
    type="button"
    class="export-trigger"
    :class="{ 'export-trigger--open': exportDropdownOpen }"
    :disabled="!canExportRows || isExporting"
    @click="toggleExportDropdown"
  >
    <span class="export-trigger__icon">
      <component :is="isExporting ? LoadingIcon : ExportMenuIcon" :size="17" />
    </span>

    <span class="export-trigger__text">
      <strong>{{ isExporting ? 'Exporting...' : 'Export Report' }}</strong>
      <small>{{ filteredRows.length.toLocaleString() }} rows available</small>
    </span>

    <component :is="ChevronDownIcon" :size="16" class="export-trigger__chevron" />
  </button>

  <Transition name="export-menu">
    <div v-if="exportDropdownOpen" class="export-menu">
      <div class="export-menu__header">
        <strong>Download report</strong>
      </div>

      <button
        v-if="canExportType('pdf')"
        type="button"
        class="export-menu__item export-menu__item--pdf"
        @click="selectExport('pdf')"
      >
        <span class="export-menu__icon">
          <component :is="PdfIcon" :size="18" />
        </span>
        <span>
          <strong>PDF Document</strong>
        </span>
      </button>

      <button
        v-if="canExportType('xlsx')"
        type="button"
        class="export-menu__item export-menu__item--xlsx"
        @click="selectExport('xlsx')"
      >
        <span class="export-menu__icon">
          <component :is="XlsxIcon" :size="18" />
        </span>
        <span>
          <strong>Excel Workbook</strong>
        </span>
      </button>

      <button
        v-if="canExportType('csv')"
        type="button"
        class="export-menu__item export-menu__item--csv"
        @click="selectExport('csv')"
      >
        <span class="export-menu__icon">
          <component :is="CsvIcon" :size="18" />
        </span>
        <span>
          <strong>CSV File</strong>
        </span>
      </button>

      <button
        v-if="canExportType('json')"
        type="button"
        class="export-menu__item export-menu__item--json"
        @click="selectExport('json')"
      >
        <span class="export-menu__icon">
          <component :is="JsonIcon" :size="18" />
        </span>
        <span>
          <strong>JSON Data</strong>
        </span>
      </button>
    </div>
  </Transition>
</div>
        </div>

        <div class="filter-grid">
          <label>
            <span>From</span>
             <DatePicker v-model="form.date_from" />
          </label>

          <label>
            <span>To</span>
             <DatePicker v-model="form.date_to" />
          </label>

          <label>
            <span>Branch</span>
            <!-- <select v-model="form.branch_id">
              <option value="">All branches</option>
              <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select> -->
            <SelectInput id="branch.id" v-model="form.branch_id" :options="branches" valueKey="id"
              labelKey="name" placeholder="All Branches" />
          </label>

          <label>
            <span>Currency</span>
            <SelectInput id="currency_mode" v-model="form.currency_mode" :options="currencyOptions" valueKey="value"
              labelKey="label" placeholder="Select Currency Mode" />
          </label>

          <label class="search-field">
            <span>Search</span>
            <div class="search-box">
              <component :is="SearchIcon" :size="16" />
              <input
                v-model="form.search"
                type="search"
                placeholder="Search report rows..."
              />
            </div>
          </label>
        </div>

        <div class="filter-actions">
          <button type="button" class="ghost-btn" @click="resetFilters">
            Reset
          </button>

          <button type="button" class="primary-btn" @click="applyFilters">
            <component :is="FilterIcon" :size="16" />
            Apply filters
          </button>
        </div>
      </section>

      <section class="summary-strip">
        <div class="summary-card">
          <span>Rows</span>
          <strong>{{ filteredRows.length.toLocaleString() }}</strong>
        </div>

        <div class="summary-card">
          <span>Base Total</span>
          <strong>{{ formatMoney(baseGrandTotal, baseCurrency) }}</strong>
        </div>

        <div v-if="hasSecondaryCurrency" class="summary-card">
          <span>Secondary Total</span>
          <strong>{{ formatMoney(secondaryGrandTotal, secondaryCurrency) }}</strong>
        </div>

        <div class="summary-card">
          <span>Visible Columns</span>
          <strong>{{ visibleColumns.length }}</strong>
        </div>
      </section>

      <section class="table-card">
        <div class="table-toolbar">
          <div>
            <h2>{{ report.title }}</h2>
            <p>{{ resultLabel }}</p>
          </div>

          <div class="toolbar-controls">
            <label>
              Show
              <select v-model.number="perPage">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
              entries
            </label>
          </div>
        </div>

        <div class="table-wrap">
          <table v-if="visibleColumns.length && filteredRows.length">
            <thead>
              <tr>
                <th v-for="column in visibleColumns" :key="column.key">
                  {{ column.label }}
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(row, index) in paginatedRows" :key="index">
                <td v-for="column in visibleColumns" :key="column.key">
                  <span :class="{ money: isMoneyColumn(column.key) }">
                    {{ renderCell(row[column.key], column.key) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else class="empty-state">
            <component :is="EmptyIcon" :size="46" />
            <h3>No report data found</h3>
            <p>This report page is ready. Add matching records or relax the filters to see results.</p>
          </div>
        </div>

        <div v-if="visibleColumns.length && filteredRows.length" class="pagination-row">
          <span>
            Showing {{ pageStart }} to {{ pageEnd }} of {{ filteredRows.length }} entries
          </span>

          <div class="pager">
            <button type="button" :disabled="page === 1" @click="page = 1">
              <component :is="ChevronsLeftIcon" :size="16" />
            </button>

            <button type="button" :disabled="page === 1" @click="page--">
              <component :is="ChevronLeftIcon" :size="16" />
            </button>

            <strong>{{ page }}</strong>

            <button type="button" :disabled="page === totalPages" @click="page++">
              <component :is="ChevronRightIcon" :size="16" />
            </button>

            <button type="button" :disabled="page === totalPages" @click="page = totalPages">
              <component :is="ChevronsRightIcon" :size="16" />
            </button>
          </div>
        </div>
      </section>
    </main>
  </VendorAdminLayout>
</template>

<script setup>
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { usePermission } from '@/composables/usePermission'
import * as Lucide from 'lucide-vue-next'
import DatePicker from '@/Components/DatePicker.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { computed, reactive, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { currencySymbol } from '@/Utils/currency'

const props = defineProps({
  report: {
    type: Object,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
})

const { can } = usePermission()

function pickIcon(...names) {
  return names.map((name) => Lucide[name]).find(Boolean) || Lucide.Circle || 'span'
}

const currencyOptions = computed(() => {
  const options = [
    {
      value: 'all',
      label: 'All currencies'
    },
    {
      value: 'base',
      label: `Base ${baseCurrencySymbol.value}`
    }
  ]

  if (hasSecondaryCurrency.value) {
    options.push({
      value: 'secondary',
      label: `Secondary ${secondaryCurrencySymbol.value}`
    })
  }

  return options
})

const DefaultIcon = pickIcon('ReceiptText', 'Receipt', 'FileText', 'Square')
const ArrowLeftIcon = pickIcon('ArrowLeft', 'ChevronLeft')
const SearchIcon = pickIcon('Search')
const FilterIcon = pickIcon('Filter')
const EmptyIcon = pickIcon('FileSearch', 'Search', 'FileText')
const ChevronLeftIcon = pickIcon('ChevronLeft')
const ChevronRightIcon = pickIcon('ChevronRight')
const ChevronsLeftIcon = pickIcon('ChevronsLeft')
const ChevronsRightIcon = pickIcon('ChevronsRight')
const ExportMenuIcon = pickIcon('Download', 'FileDown')
const ChevronDownIcon = pickIcon('ChevronDown')
const LoadingIcon = pickIcon('LoaderCircle', 'RefreshCw')
const PdfIcon = pickIcon('FileText', 'FileType2', 'File')
const XlsxIcon = pickIcon('Sheet', 'Table2', 'FileSpreadsheet')
const CsvIcon = pickIcon('FileDown', 'Download')
const JsonIcon = pickIcon('Braces', 'FileJson', 'Code2')

const icons = {
  Activity: pickIcon('Activity'),
  ArrowLeftRight: pickIcon('ArrowLeftRight', 'RefreshCw'),
  BadgeDollarSign: pickIcon('BadgeDollarSign', 'CircleDollarSign', 'DollarSign'),
  BadgePercent: pickIcon('BadgePercent', 'Percent'),
  BadgePlus: pickIcon('BadgePlus', 'Badge', 'PlusCircle'),
  BoxSelect: pickIcon('BoxSelect', 'Box', 'Package'),
  Calculator: pickIcon('Calculator'),
  CalendarClock: pickIcon('CalendarClock', 'CalendarDays', 'Calendar'),
  ChartColumn: pickIcon('ChartColumn', 'BarChart3'),
  ChartNoAxesCombined: pickIcon('ChartNoAxesCombined', 'BarChart3', 'ChartColumn'),
  CircleDollarSign: pickIcon('CircleDollarSign', 'DollarSign'),
  CircleOff: pickIcon('CircleOff', 'Ban'),
  ClipboardDollar: pickIcon('ClipboardDollar', 'ClipboardList', 'Clipboard', 'FileText'),
  ClipboardList: pickIcon('ClipboardList', 'Clipboard'),
  ClockAlert: pickIcon('ClockAlert', 'Clock', 'AlarmClock'),
  Folders: pickIcon('Folders', 'Folder'),
  Gift: pickIcon('Gift'),
  Hand: pickIcon('Hand'),
  HandCoins: pickIcon('HandCoins', 'Coins', 'DollarSign'),
  Layers: pickIcon('Layers'),
  LayoutGrid: pickIcon('LayoutGrid', 'Grid3X3'),
  ListChecks: pickIcon('ListChecks', 'CheckSquare'),
  MonitorCog: pickIcon('MonitorCog', 'Monitor', 'Settings'),
  Package: pickIcon('Package', 'Box'),
  PieChart: pickIcon('PieChart'),
  ReceiptText: pickIcon('ReceiptText', 'Receipt', 'FileText'),
  Route: pickIcon('Route'),
  ScrollText: pickIcon('ScrollText', 'FileText'),
  Store: pickIcon('Store', 'Building2'),
  TicketCheck: pickIcon('TicketCheck', 'Ticket'),
  TrendingDown: pickIcon('TrendingDown'),
  TriangleAlert: pickIcon('TriangleAlert', 'AlertTriangle'),
  Trophy: pickIcon('Trophy'),
  UserPlus: pickIcon('UserPlus'),
  UserRoundCog: pickIcon('UserRoundCog', 'UserCog', 'User', 'Users'),
  UserRoundHelp: pickIcon('UserRoundHelp', 'CircleHelp', 'HelpCircle', 'User', 'Users'),
  UserRoundX: pickIcon('UserRoundX', 'UserX', 'UserMinus', 'User'),
  UsersRound: pickIcon('UsersRound', 'Users'),
  Utensils: pickIcon('Utensils'),
  Wallet: pickIcon('Wallet'),
  WalletCards: pickIcon('WalletCards', 'CreditCard', 'Wallet'),
  Zap: pickIcon('Zap'),
}

function iconFor(name) {
  return icons[name] || DefaultIcon
}

const form = reactive({
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  branch_id: props.filters.branch_id || '',
  currency_mode: props.filters.currency_mode || 'all',
  search: props.filters.search || '',
})

const perPage = ref(10)
const page = ref(1)
const isExporting = ref(false)

const exportDropdownOpen = ref(false)
const exportDropdownRef = ref(null)

function toggleExportDropdown() {
  if (!canExportRows.value || isExporting.value) return

  exportDropdownOpen.value = !exportDropdownOpen.value
}

function closeExportDropdown() {
  exportDropdownOpen.value = false
}

async function selectExport(type) {
  closeExportDropdown()
  await exportReport(type)
}

function handleExportOutsideClick(event) {
  if (!exportDropdownRef.value) return

  if (!exportDropdownRef.value.contains(event.target)) {
    closeExportDropdown()
  }
}

function handleExportEscape(event) {
  if (event.key === 'Escape') {
    closeExportDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleExportOutsideClick)
  document.addEventListener('keydown', handleExportEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleExportOutsideClick)
  document.removeEventListener('keydown', handleExportEscape)
})

const branches = computed(() => props.filterOptions.branches || [])
const baseCurrency = computed(() => props.filterOptions.baseCurrency || 'LKR')
const secondaryCurrency = computed(() => props.filterOptions.secondaryCurrency || '')
const baseCurrencySymbol = computed(() => props.filterOptions.baseCurrencySymbol || currencySymbol(baseCurrency.value))
const secondaryCurrencySymbol = computed(() => props.filterOptions.secondaryCurrencySymbol || currencySymbol(secondaryCurrency.value))
const hasSecondaryCurrency = computed(() => !!secondaryCurrency.value)

const canAnyExport = computed(() => {
  return can('reports.export')
    || can('reports.export.pdf')
    || can('reports.export.xlsx')
    || can('reports.export.csv')
    || can('reports.export.json')
})

const canExportRows = computed(() => {
  return filteredRows.value.length > 0 && visibleColumns.value.length > 0
})

function canExportType(type) {
  return can('reports.export') || can(`reports.export.${type}`)
}

const labelFor = (key) => {
  return String(key)
    .replace(/_base$/, ` (${baseCurrencySymbol.value})`)
    .replace(/_secondary$/, ` (${secondaryCurrencySymbol.value})`)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const columns = computed(() => {
  const keys = [
    ...new Set((props.rows || []).flatMap((row) => Object.keys(row || {}))),
  ]

  return keys.map((key) => ({
    key,
    label: labelFor(key),
  }))
})

const visibleColumns = computed(() => {
  if (hasSecondaryCurrency.value) {
    return columns.value
  }

  return columns.value.filter((column) => !column.key.endsWith('_secondary'))
})

const filteredRows = computed(() => {
  const sourceRows = props.rows || []
  const term = String(form.search || '').trim().toLowerCase()

  if (!term) return sourceRows

  return sourceRows.filter((row) => {
    return Object.values(row || {}).some((value) =>
      String(value ?? '').toLowerCase().includes(term)
    )
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredRows.value.length / perPage.value))
})

const paginatedRows = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredRows.value.slice(start, start + perPage.value)
})

const pageStart = computed(() => {
  return filteredRows.value.length ? (page.value - 1) * perPage.value + 1 : 0
})

const pageEnd = computed(() => {
  return Math.min(page.value * perPage.value, filteredRows.value.length)
})

const resultLabel = computed(() => {
  const total = filteredRows.value.length
  return `${total.toLocaleString()} matching ${total === 1 ? 'row' : 'rows'}`
})

const baseGrandTotal = computed(() => sumColumns('_base'))
const secondaryGrandTotal = computed(() => sumColumns('_secondary'))

function numberValue(value) {
  return Number.parseFloat(value || 0) || 0
}

function moneyKey(key) {
  return /(total|amount|tax|subtotal|discount|revenue|cost|profit|sales|balance|value|liability|payment|cash|card|paid|price)/i.test(key)
}

function isMoneyColumn(key) {
  return key.endsWith('_base') || key.endsWith('_secondary') || moneyKey(key)
}

function sumColumns(suffix) {
  const preferred = [
    'total',
    'amount',
    'revenue',
    'balance',
    'value',
    'sales',
    'profit',
    'paid',
  ]

  const keys = visibleColumns.value
    .map((column) => column.key)
    .filter((key) => key.endsWith(suffix))

  const selectedKeys = keys.filter((key) => {
    return preferred.some((name) => {
      return key === `${name}${suffix}` || key.endsWith(`_${name}${suffix}`)
    })
  })

  const totalKeys = selectedKeys.length
    ? selectedKeys
    : keys.filter((key) => moneyKey(key))

  return filteredRows.value.reduce((total, row) => {
    return total + totalKeys.reduce((sum, key) => {
      return sum + numberValue(row[key])
    }, 0)
  }, 0)
}

function formatMoney(value, currency) {
  const amount = numberValue(value)
  const symbol = currencySymbol(currency)

  return `${symbol || ''} ${amount.toLocaleString(undefined, {
    minimumFractionDigits: 3,
    maximumFractionDigits: 3,
  })}`
}

function renderCell(value, key) {
  if (value === null || value === undefined || value === '') return '-'

  if (key.endsWith('_base')) {
    return formatMoney(value, baseCurrency.value)
  }

  if (key.endsWith('_secondary')) {
    return formatMoney(value, secondaryCurrency.value)
  }

  if (typeof value === 'number') {
    return value.toLocaleString(undefined, {
      minimumFractionDigits: moneyKey(key) ? 3 : 0,
      maximumFractionDigits: moneyKey(key) ? 3 : 3,
    })
  }

  return value
}

function applyFilters() {
  const payload = Object.fromEntries(
    Object.entries(form).filter(([, value]) => {
      return value !== '' && value !== null && value !== undefined
    })
  )

  router.get(route('vendor.reports.show', props.report.slug), payload, {
    preserveScroll: true,
    preserveState: false,
  })
}

function resetFilters() {
  form.date_from = ''
  form.date_to = ''
  form.branch_id = ''
  form.currency_mode = 'all'
  form.search = ''
  applyFilters()
}

async function exportReport(type) {
  if (!canExportType(type) || !canExportRows.value || isExporting.value) return

  isExporting.value = true

  try {
    if (type === 'pdf') {
      await exportPdf()
      return
    }

    if (type === 'xlsx') {
      await exportXlsx()
      return
    }

    if (type === 'json') {
      exportJson()
      return
    }

    exportCsv()
  } finally {
    isExporting.value = false
  }
}

function exportFileName(extension) {
  const date = new Date().toISOString().slice(0, 10)
  const slug = props.report.slug || 'report'

  return `${slug}-${date}.${extension}`
    .replace(/[^\w.-]+/g, '-')
    .toLowerCase()
}

function sanitizeExportValue(value) {
  if (value === null || value === undefined) return ''
  if (typeof value === 'object') return JSON.stringify(value)
  return value
}

function exportRowsFormatted() {
  return filteredRows.value.map((row) => {
    const output = {}

    visibleColumns.value.forEach((column) => {
      output[column.label] = renderCell(row[column.key], column.key)
    })

    return output
  })
}

function exportRowsRaw() {
  return filteredRows.value.map((row) => {
    const output = {}

    visibleColumns.value.forEach((column) => {
      output[column.label] = sanitizeExportValue(row[column.key])
    })

    return output
  })
}

function downloadBlob(content, mimeType, filename) {
  const blob = content instanceof Blob
    ? content
    : new Blob([content], { type: mimeType })

  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

function csvEscape(value) {
  return `"${String(value ?? '').replace(/"/g, '""')}"`
}

function exportCsv() {
  const headers = visibleColumns.value.map((column) => column.label)

  const rows = filteredRows.value.map((row) => {
    return visibleColumns.value.map((column) => {
      return csvEscape(renderCell(row[column.key], column.key))
    })
  })

  const csv = [
    headers.map(csvEscape).join(','),
    ...rows.map((row) => row.join(',')),
  ].join('\n')

  downloadBlob(csv, 'text/csv;charset=utf-8;', exportFileName('csv'))
}

function exportJson() {
  const payload = {
    report: {
      slug: props.report.slug,
      title: props.report.title,
      description: props.report.description,
      section_title: props.report.section_title || null,
      section_key: props.report.section_key || null,
    },
    filters: {
      date_from: form.date_from,
      date_to: form.date_to,
      branch_id: form.branch_id,
      currency_mode: form.currency_mode,
      search: form.search,
    },
    summary: {
      rows: filteredRows.value.length,
      base_total: baseGrandTotal.value,
      base_currency: baseCurrencySymbol.value,
      secondary_total: secondaryGrandTotal.value,
      secondary_currency: secondaryCurrency.value ? secondaryCurrencySymbol.value : null,
      visible_columns: visibleColumns.value.length,
    },
    exported_at: new Date().toISOString(),
    rows: exportRowsRaw(),
  }

  downloadBlob(
    JSON.stringify(payload, null, 2),
    'application/json;charset=utf-8;',
    exportFileName('json')
  )
}

async function exportXlsx() {
  const XLSX = await import('xlsx')

  const workbook = XLSX.utils.book_new()

  const metaRows = [
    ['Report', props.report.title || 'Report'],
    ['Section', props.report.section_title || '-'],
    ['Exported At', new Date().toLocaleString()],
    ['Rows', filteredRows.value.length],
    ['Base Total', baseGrandTotal.value],
    ['Base Currency', baseCurrencySymbol.value],
    ['Secondary Total', secondaryGrandTotal.value],
    ['Secondary Currency', secondaryCurrency.value ? secondaryCurrencySymbol.value : '-'],
    [],
  ]

  const worksheet = XLSX.utils.aoa_to_sheet(metaRows)
  XLSX.utils.sheet_add_json(worksheet, exportRowsRaw(), {
    origin: -1,
    skipHeader: false,
  })

  worksheet['!cols'] = visibleColumns.value.map((column) => ({
    wch: Math.max(14, Math.min(36, column.label.length + 4)),
  }))

  XLSX.utils.book_append_sheet(workbook, worksheet, 'Report')
  XLSX.writeFile(workbook, exportFileName('xlsx'))
}

async function exportPdf() {
  const { jsPDF } = await import('jspdf')
  const autoTableModule = await import('jspdf-autotable')
  const autoTable = autoTableModule.default || autoTableModule.autoTable

  const landscape = visibleColumns.value.length > 6

  const doc = new jsPDF({
    orientation: landscape ? 'landscape' : 'portrait',
    unit: 'pt',
    format: 'a4',
  })

  const title = props.report.title || 'Report'
  const section = props.report.section_title || 'Reports'
  const exportedAt = new Date().toLocaleString()
  const pageWidth = doc.internal.pageSize.getWidth()

  doc.setFontSize(16)
  doc.setTextColor(17, 24, 39)
  doc.text(title, 40, 42)

  doc.setFontSize(9)
  doc.setTextColor(100, 116, 139)
  doc.text(section, 40, 60)
  doc.text(`Exported: ${exportedAt}`, 40, 76)
  doc.text(`Rows: ${filteredRows.value.length}`, 40, 92)

  doc.text(`Base Total: ${formatMoney(baseGrandTotal.value, baseCurrency.value)}`, pageWidth - 40, 76, {
    align: 'right',
  })

  if (hasSecondaryCurrency.value) {
    doc.text(
      `Secondary Total: ${formatMoney(secondaryGrandTotal.value, secondaryCurrency.value)}`,
      pageWidth - 40,
      92,
      { align: 'right' }
    )
  }

  const filtersText = [
    form.date_from ? `From: ${form.date_from}` : null,
    form.date_to ? `To: ${form.date_to}` : null,
    form.currency_mode ? `Currency: ${form.currency_mode}` : null,
    form.search ? `Search: ${form.search}` : null,
  ]
    .filter(Boolean)
    .join(' | ')

  if (filtersText) {
    doc.text(filtersText, 40, 110)
  }

  const head = [visibleColumns.value.map((column) => column.label)]
  const body = filteredRows.value.map((row) => {
    return visibleColumns.value.map((column) => {
      return String(renderCell(row[column.key], column.key))
    })
  })

  const options = {
    head,
    body,
    startY: filtersText ? 128 : 112,
    styles: {
      fontSize: visibleColumns.value.length > 8 ? 6 : 7,
      cellPadding: 4,
      overflow: 'linebreak',
      valign: 'middle',
    },
    headStyles: {
      fillColor: [249, 115, 22],
      textColor: 255,
      fontStyle: 'bold',
    },
    alternateRowStyles: {
      fillColor: [248, 250, 252],
    },
    margin: {
      left: 40,
      right: 40,
    },
  }

  if (typeof autoTable === 'function') {
    autoTable(doc, options)
  } else if (typeof doc.autoTable === 'function') {
    doc.autoTable(options)
  }

  doc.save(exportFileName('pdf'))
}

watch([filteredRows, perPage], () => {
  page.value = 1
})

watch(totalPages, (value) => {
  if (page.value > value) {
    page.value = value
  }
})
</script>

<style scoped>
.report-detail {
  padding: 24px;
  color: #334155;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 20px;
}

.title-block {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.title-icon {
  width: 52px;
  height: 52px;
  border-radius: 15px;
  background: #fff7ed;
  color: #f97316;
  border: 1px solid #fed7aa;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
}

.breadcrumb-title {
  color: #f97316;
  font-size: 12px;
  font-weight: 850;
  margin-bottom: 6px;
}

.title-block h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 900;
  color: #111827;
  letter-spacing: -0.02em;
}

.title-block p {
  margin: 8px 0 0;
  color: #64748b;
  max-width: 760px;
  line-height: 1.7;
}

.permission-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.permission-row span {
  display: inline-flex;
  align-items: center;
  min-height: 25px;
  border-radius: 999px;
  padding: 0 9px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 850;
}

.back-link,
.ghost-btn,
.primary-btn,
.export-btn,
.pager button {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  border-radius: 10px;
  min-height: 40px;
  padding: 0 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  font-weight: 800;
  transition: 0.16s ease;
}

.back-link:hover,
.ghost-btn:hover,
.pager button:hover:not(:disabled) {
  border-color: #fdba74;
  color: #f97316;
}

.primary-btn {
  background: #f97316;
  border-color: #f97316;
  color: #fff;
}

.primary-btn:hover {
  background: #ea580c;
  border-color: #ea580c;
}

.export-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.export-btn {
  min-height: 38px;
  padding: 0 13px;
  font-size: 12px;
  font-weight: 850;
}

.export-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

.export-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.export-btn--pdf {
  color: #b91c1c;
  background: #fef2f2;
  border-color: #fecaca;
}

.export-btn--xlsx {
  color: #15803d;
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.export-btn--csv {
  color: #0369a1;
  background: #f0f9ff;
  border-color: #bae6fd;
}

.export-btn--json {
  color: #7c3aed;
  background: #f5f3ff;
  border-color: #ddd6fe;
}

.filter-panel,
.table-card,
.summary-strip {
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.82);
  border-radius: 16px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.035);
}

.filter-panel {
  padding: 18px;
  margin-bottom: 16px;
}

.filter-panel__top {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: flex-start;
  margin-bottom: 18px;
}

.filter-panel__top h2 {
  margin: 0;
  font-size: 15px;
  font-weight: 900;
  color: #1f2937;
}

.filter-panel__top p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 12px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
}

label span {
  display: block;
  margin-bottom: 7px;
  font-size: 12px;
  font-weight: 850;
  color: #64748b;
}

input,
select {
  width: 100%;
  min-height: 42px;
  border: 1px solid #d8dee8;
  border-radius: 10px;
  padding: 0 12px;
  color: #334155;
  background: #fff;
  outline: 0;
}

input:focus,
select:focus,
.search-box:focus-within {
  border-color: #fb923c;
  box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #d8dee8;
  border-radius: 10px;
  padding: 0 11px;
  color: #94a3b8;
}

.search-box input {
  border: 0;
  padding: 0;
  box-shadow: none;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}

.summary-strip {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1px;
  overflow: hidden;
  margin-bottom: 16px;
}

.summary-card {
  padding: 17px 18px;
  background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
}

.summary-card span {
  display: block;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 850;
  margin-bottom: 7px;
}

.summary-card strong {
  color: #111827;
  font-size: 18px;
  font-weight: 900;
}

.table-card {
  overflow: hidden;
}

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.table-toolbar h2 {
  margin: 0;
  font-size: 15px;
  font-weight: 900;
  color: #1f2937;
}

.table-toolbar p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 12px;
}

.toolbar-controls label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 800;
}

.toolbar-controls select {
  width: 82px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 960px;
}

th,
td {
  text-align: left;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

th {
  background: #f8fafc;
  color: #334155;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

td {
  color: #64748b;
  font-size: 14px;
}

tbody tr:hover td {
  background: #fff7ed;
}

.money {
  font-variant-numeric: tabular-nums;
  color: #475569;
  font-weight: 750;
}

.empty-state {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #94a3b8;
  padding: 44px;
}

.empty-state h3 {
  margin: 14px 0 6px;
  color: #334155;
  font-weight: 900;
}

.empty-state p {
  margin: 0;
  max-width: 440px;
  line-height: 1.7;
}

.pagination-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  color: #64748b;
}

.pager {
  display: flex;
  align-items: center;
  gap: 7px;
}

.pager strong {
  min-width: 36px;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #f97316;
  color: #fff;
  font-weight: 900;
}

.pager button {
  min-width: 36px;
  min-height: 36px;
  padding: 0;
  color: #f97316;
}

.pager button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

@media (max-width: 1180px) {
  .filter-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .search-field {
    grid-column: span 2;
  }

  .summary-strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .report-detail {
    padding: 16px;
  }

  .detail-header,
  .filter-panel__top,
  .table-toolbar,
  .pagination-row {
    display: block;
  }

  .back-link,
  .export-actions,
  .filter-actions,
  .pager {
    margin-top: 14px;
  }

  .export-actions {
    justify-content: flex-start;
  }

  .filter-grid,
  .summary-strip {
    grid-template-columns: 1fr;
  }

  .search-field {
    grid-column: auto;
  }

  .filter-actions {
    justify-content: stretch;
  }

  .ghost-btn,
  .primary-btn {
    flex: 1;
  }
}
.export-dropdown {
  position: relative;
  display: flex;
  justify-content: flex-end;
}

.export-trigger {
  min-width: 210px;
  min-height: 48px;
  display: inline-flex;
  align-items: center;
  gap: 11px;
  border: 1px solid #fed7aa;
  border-radius: 14px;
  padding: 0 13px;
  background:
    radial-gradient(circle at top left, rgba(251, 146, 60, 0.18), transparent 38%),
    linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);
  color: #9a3412;
  box-shadow: 0 12px 28px rgba(249, 115, 22, 0.1);
  cursor: pointer;
  transition: 0.18s ease;
}

.export-trigger:hover:not(:disabled),
.export-trigger--open {
  transform: translateY(-1px);
  border-color: #fb923c;
  box-shadow: 0 18px 36px rgba(249, 115, 22, 0.16);
}

.export-trigger:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.export-trigger__icon {
  width: 34px;
  height: 34px;
  border-radius: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f97316;
  color: #fff;
  flex: 0 0 auto;
}

.export-trigger__text {
  display: grid;
  text-align: left;
  line-height: 1.15;
  flex: 1;
}

.export-trigger__text strong {
  font-size: 13px;
  font-weight: 900;
}

.export-trigger__text small {
  margin-top: 3px;
  font-size: 11px;
  color: #c2410c;
  font-weight: 750;
}

.export-trigger__chevron {
  transition: transform 0.18s ease;
}

.export-trigger--open .export-trigger__chevron {
  transform: rotate(180deg);
}

.export-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  z-index: 40;
  width: 292px;
  padding: 10px;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
  backdrop-filter: blur(14px);
}

.export-menu__header {
  padding: 10px 11px 12px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 7px;
}

.export-menu__header strong {
  display: block;
  color: #111827;
  font-size: 13px;
  font-weight: 900;
}

.export-menu__header span {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 750;
}

.export-menu__item {
  width: 100%;
  display: grid;
  grid-template-columns: 40px 1fr;
  gap: 11px;
  align-items: center;
  border: 0;
  border-radius: 13px;
  background: transparent;
  padding: 10px;
  text-align: left;
  cursor: pointer;
  transition: 0.16s ease;
}

.export-menu__item:hover {
  background: #f8fafc;
  transform: translateX(2px);
}

.export-menu__icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.export-menu__item strong {
  display: block;
  color: #334155;
  font-size: 13px;
  font-weight: 900;
}

.export-menu__item small {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 700;
}

.export-menu__item--pdf .export-menu__icon {
  color: #b91c1c;
  background: #fef2f2;
}

.export-menu__item--xlsx .export-menu__icon {
  color: #15803d;
  background: #f0fdf4;
}

.export-menu__item--csv .export-menu__icon {
  color: #0369a1;
  background: #f0f9ff;
}

.export-menu__item--json .export-menu__icon {
  color: #7c3aed;
  background: #f5f3ff;
}

.export-menu-enter-active,
.export-menu-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.export-menu-enter-from,
.export-menu-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}
</style>
