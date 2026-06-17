<template>
  <VendorAdminLayout>
    <Head title="Reports" />

    <main class="reports-page">
      <header class="reports-hero">
        <div class="reports-hero__content">
          <div class="reports-kicker">
            <component :is="HeroIcon" :size="18" />
            Reports Center
          </div>

          <h1>Shop POS Reports</h1>
          <p>
            Sales, cash, and product stock reporting for your current shop POS in one focused workspace.
          </p>
        </div>

        <div class="reports-hero__meta">
          <span>{{ totalReports }} reports</span>
          <span>{{ totalSections }} sections</span>
        </div>
      </header>

      <section class="reports-search-card">
        <div class="reports-search">
          <component :is="SearchIcon" :size="18" />
          <input
            v-model="search"
            type="search"
            placeholder="Search shop POS reports..."
          />
        </div>
      </section>

      <template v-for="section in filteredSections" :key="section.key || section.title">
        <section class="report-section">
          <div class="report-section__header">
            <div>
              <h2>{{ section.title }}</h2>
              <p>{{ section.items.length }} available reports</p>
            </div>

           
          </div>

          <div class="report-grid">
            <Link
              v-for="item in section.items"
              :key="item.slug"
              :href="route('vendor.reports.show', item.slug)"
              class="report-card"
            >
              <div class="report-card__icon">
                <component :is="iconFor(item.icon)" :size="32" stroke-width="1.9" />
              </div>

              <div class="report-card__copy">
                <h3>{{ item.title }}</h3>
                <p>{{ item.description }}</p>

                <!-- <span v-if="item.permission" class="report-card__permission">
                  {{ item.permission }}
                </span> -->
              </div>

              <div class="report-card__arrow">
                <component :is="ArrowRightIcon" :size="17" />
              </div>
            </Link>
          </div>
        </section>
      </template>

      <section v-if="!filteredSections.length" class="empty-state">
        <component :is="EmptyIcon" :size="46" />
        <h3>No reports available</h3>
        <p>
          No report matches this role's permissions or the current search term.
          Assign the required report permissions to display reports here.
        </p>
      </section>
    </main>
  </VendorAdminLayout>
</template>

<script setup>
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { usePermission } from '@/composables/usePermission'
import * as Lucide from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps({
  sections: {
    type: Array,
    default: () => [],
  },
})

const { can } = usePermission()
const search = ref('')

function pickIcon(...names) {
  return names.map((name) => Lucide[name]).find(Boolean) || Lucide.Circle || 'span'
}

const DefaultIcon = pickIcon('ReceiptText', 'Receipt', 'FileText', 'Square')
const HeroIcon = pickIcon('BarChart3', 'ChartColumn', 'Activity')
const SearchIcon = pickIcon('Search')
const ArrowRightIcon = pickIcon('ArrowRight', 'ChevronRight')
const EmptyIcon = pickIcon('FileSearch', 'Search', 'FileText')

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

function canAccess(permission) {
  if (!permission) return true
  return can(permission)
}

function canSection(section) {
  if (!section) return false

  return canAccess('reports.view') && canAccess(section.permission)
}

function canReport(item) {
  if (!item) return false

  return canAccess('reports.view') && canAccess(item.permission)
}

const visibleSections = computed(() => {
  return (props.sections || [])
    .filter(Boolean)
    .filter((section) => canSection(section))
    .map((section) => {
      return {
        ...section,
        items: (section.items || [])
          .filter(Boolean)
          .filter((item) => canReport(item)),
      }
    })
    .filter((section) => section.items.length > 0)
})

const filteredSections = computed(() => {
  const term = search.value.trim().toLowerCase()

  if (!term) return visibleSections.value

  return visibleSections.value
    .map((section) => {
      const items = (section.items || []).filter((item) => {
        return [
          section.title,
          section.key,
          section.permission,
          item.title,
          item.description,
          item.slug,
          item.permission,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()
          .includes(term)
      })

      return {
        ...section,
        items,
      }
    })
    .filter((section) => section.items.length > 0)
})

const totalReports = computed(() => {
  return visibleSections.value.reduce((sum, section) => {
    return sum + (section.items?.length || 0)
  }, 0)
})

const totalSections = computed(() => visibleSections.value.length)
</script>

<style scoped>
.reports-page {
  padding: 24px;
  color: #334155;
}

.reports-hero {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  align-items: flex-end;
  margin-bottom: 18px;
  padding: 26px;
  border-radius: 18px;
  background:
    radial-gradient(circle at top left, rgba(249, 115, 22, 0.12), transparent 34%),
    linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);
  border: 1px solid rgba(249, 115, 22, 0.16);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.045);
}

.reports-kicker {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: #f97316;
  font-weight: 850;
  margin-bottom: 10px;
}

.reports-hero h1 {
  margin: 0;
  font-size: 32px;
  font-weight: 900;
  color: #111827;
  letter-spacing: -0.03em;
}

.reports-hero p {
  margin: 10px 0 0;
  color: #64748b;
  max-width: 760px;
  line-height: 1.7;
}

.reports-hero__meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.reports-hero__meta span {
  border: 1px solid #fed7aa;
  background: #fff;
  border-radius: 999px;
  padding: 9px 14px;
  font-size: 13px;
  font-weight: 800;
  color: #9a3412;
}

.reports-search-card {
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 14px;
  padding: 14px;
  margin-bottom: 26px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.035);
}

.reports-search {
  display: flex;
  align-items: center;
  gap: 11px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 12px;
  padding: 0 14px;
  min-height: 46px;
  color: #94a3b8;
}

.reports-search input {
  border: 0;
  outline: 0;
  background: transparent;
  width: 100%;
  min-height: 44px;
  color: #334155;
}

.report-section {
  margin-bottom: 30px;
}

.report-section__header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
}

.report-section__header h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 900;
  color: #1f2937;
}

.report-section__header p {
  margin: 5px 0 0;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 750;
}

.permission-badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  border-radius: 999px;
  padding: 0 11px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}

.report-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}

.report-card {
  min-height: 124px;
  display: grid;
  grid-template-columns: 54px 1fr 24px;
  align-items: center;
  gap: 18px;
  padding: 22px;
  border-radius: 16px;
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.82);
  text-decoration: none;
  color: inherit;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.035);
  transition:
    transform 0.16s ease,
    box-shadow 0.16s ease,
    border-color 0.16s ease;
}

.report-card:hover {
  transform: translateY(-3px);
  border-color: rgba(249, 115, 22, 0.42);
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
}

.report-card__icon {
  width: 54px;
  height: 54px;
  border-radius: 15px;
  color: #f97316;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  display: flex;
  align-items: center;
  justify-content: center;
}

.report-card__copy h3 {
  margin: 0 0 7px;
  color: #475569;
  font-size: 14px;
  font-weight: 900;
}

.report-card__copy p {
  margin: 0;
  color: #768394;
  font-size: 12px;
  line-height: 1.65;
}

.report-card__permission {
  display: inline-flex;
  margin-top: 9px;
  color: #f97316;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 10px;
  font-weight: 850;
}

.report-card__arrow {
  color: #f97316;
  opacity: 0.65;
}

.empty-state {
  min-height: 260px;
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 18px;
  display: grid;
  place-items: center;
  text-align: center;
  padding: 40px;
  color: #94a3b8;
}

.empty-state h3 {
  margin: 14px 0 6px;
  color: #334155;
  font-weight: 900;
}

.empty-state p {
  margin: 0;
  max-width: 480px;
  line-height: 1.7;
}

@media (max-width: 1200px) {
  .report-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .reports-page {
    padding: 16px;
  }

  .reports-hero {
    display: block;
    padding: 20px;
  }

  .reports-hero h1 {
    font-size: 26px;
  }

  .reports-hero__meta {
    justify-content: flex-start;
    margin-top: 16px;
  }

  .report-section__header {
    display: block;
  }

  .permission-badge {
    margin-top: 10px;
  }

  .report-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .report-card {
    grid-template-columns: 48px 1fr;
  }

  .report-card__arrow {
    display: none;
  }
}
</style>
