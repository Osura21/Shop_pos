<template>
  <Head title="Show Activity Log" />

  <div class="activity-show">
    <div class="activity-title-row">
      <div class="activity-title">
        <History :size="20" />
        <div>
          <h1>Show Activity Log</h1>
          <p>{{ log.log_name }} Â· {{ log.logged_at }}</p>
        </div>
      </div>

      <Link :href="route('vendor.activities.activity-logs.index')" class="back-link">
        <ArrowLeft :size="16" />
        Activity Logs
      </Link>
    </div>

    <div class="summary-grid">
      <section class="info-card info-card--wide">
        <div class="card-heading">
          <UserRound :size="18" />
          <span>Actor & Subject</span>
        </div>
        <InfoRow label="User" :value="userLine" />
        <InfoRow label="Subject" :value="log.subject_label" />
        <InfoRow label="Description" :value="log.description" />
      </section>

      <section class="info-card">
        <div class="card-heading">
          <FileClock :size="18" />
          <span>Log Details</span>
        </div>
        <InfoRow label="Log Name" :value="log.log_name" />
        <InfoRow label="Event" :value="log.event" />
        <InfoRow label="Logged at" :value="log.logged_at" />
      </section>

      <section class="info-card">
        <div class="card-heading">
          <MonitorCog :size="18" />
          <span>Request</span>
        </div>
        <InfoRow label="IP Address" :value="log.ip_address" />
        <InfoRow label="HTTP Method" :value="log.http_method" />
        <div>
          <strong>Agent</strong>
          <div class="agent-line">
            <Monitor :size="16" />
            <span>{{ log.is_desktop }}</span>
            <PanelTop :size="16" />
            <span>{{ log.platform }}</span>
            <Globe2 :size="16" />
            <span>{{ log.browser }}</span>
          </div>
        </div>
      </section>
    </div>

    <section class="changes-card">
      <h2>Changes</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Field</th>
              <th>Old</th>
              <th>New</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="change in log.changes" :key="change.field">
              <td>{{ change.field }}</td>
              <td>{{ change.old }}</td>
              <td>{{ change.new }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, h } from 'vue'
import { ArrowLeft, FileClock, Globe2, History, Monitor, MonitorCog, PanelTop, UserRound } from 'lucide-vue-next'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  log: {
    type: Object,
    required: true,
  },
})

const userLine = computed(() => {
  const name = props.log.user_name || 'System'
  return props.log.user_email ? `${name} - ${props.log.user_email}` : name
})

const InfoRow = (props) => h('div', { class: 'info-row' }, [
  h('strong', props.label),
  h('span', props.value || '-'),
])
</script>

<style scoped>
.activity-show {
  padding: 22px;
  color: #64748b;
}

.activity-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  margin-bottom: 28px;
}

.activity-title {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #3b82f6;
}

.activity-title h1 {
  margin: 0;
  color: #334155;
  font-size: 20px;
  font-weight: 850;
}

.activity-title p {
  margin: 4px 0 0;
  color: #94a3b8;
  font-size: 13px;
}

.back-link {
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 13px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  text-decoration: none;
  font-weight: 750;
}

.summary-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 24px;
  margin-bottom: 26px;
}

.info-card,
.changes-card {
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.62);
  border-radius: 8px;
}

.info-card {
  padding: 26px;
  min-height: 210px;
}

.card-heading {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #3b82f6;
  font-weight: 850;
  margin-bottom: 22px;
}

.info-row {
  margin-bottom: 22px;
}

.info-row:last-child {
  margin-bottom: 0;
}

strong {
  display: block;
  color: #5f6b7a;
  font-weight: 850;
  margin-bottom: 6px;
}

.agent-line {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #f8fafc;
}

.changes-card {
  padding: 28px;
}

.changes-card h2 {
  margin: 0 0 22px;
  color: #334155;
  font-size: 15px;
  font-weight: 850;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 760px;
}

th,
td {
  text-align: left;
  padding: 10px 12px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: top;
}

th {
  color: #334155;
  font-size: 13px;
  text-transform: uppercase;
  background: #fafafa;
}

td {
  color: #64748b;
}

@media (max-width: 1100px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .activity-title-row {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
