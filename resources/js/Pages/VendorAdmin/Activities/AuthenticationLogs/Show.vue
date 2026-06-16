<template>
  <Head title="Show Authentication Log" />

  <div class="auth-show">
    <div class="activity-title-row">
      <div class="activity-title">
        <History :size="20" />
        <div>
          <h1>Show Authentication Log</h1>
          <p>{{ log.login_at }} · {{ log.browser }}</p>
        </div>
      </div>

      <Link :href="route('vendor.activities.authentication-logs.index')" class="back-link">
        <ArrowLeft :size="16" />
        Authentication Logs
      </Link>
    </div>

    <div class="summary-grid">
      <section class="info-card">
        <div class="card-heading">
          <UserRound :size="18" />
          <span>User</span>
        </div>
        <InfoRow label="Name" :value="log.user_name" />
        <InfoRow label="Email" :value="log.user_email" />
        <InfoRow label="Role" :value="log.user_role" />
      </section>

      <section class="info-card">
        <div class="card-heading">
          <Clock3 :size="18" />
          <span>Session</span>
        </div>
        <InfoRow label="Login At" :value="log.login_at" />
        <InfoRow label="Logout At" :value="log.logout_at" />
        <InfoRow label="IP Address" :value="log.ip_address" />
      </section>

      <section class="info-card">
        <div class="card-heading">
          <MonitorCog :size="18" />
          <span>Agent</span>
        </div>
        <div class="agent-line">
          <Monitor :size="16" />
          <span>{{ log.is_desktop }}</span>
          <PanelTop :size="16" />
          <span>{{ log.platform }}</span>
          <Globe2 :size="16" />
          <span>{{ log.browser }}</span>
        </div>
      </section>
    </div>

    <section class="agent-card">
      <h2>User Agent</h2>
      <p>{{ log.user_agent }}</p>
    </section>
  </div>
</template>

<script setup>
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { h } from 'vue'
import { ArrowLeft, Clock3, Globe2, History, Monitor, MonitorCog, PanelTop, UserRound } from 'lucide-vue-next'

defineOptions({ layout: VendorAdminLayout })

defineProps({
  log: {
    type: Object,
    required: true,
  },
})

const InfoRow = (props) => h('div', { class: 'info-row' }, [
  h('strong', props.label),
  h('span', props.value || '-'),
])
</script>

<style scoped>
.auth-show {
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
  color: #f97316;
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
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
  margin-bottom: 26px;
}

.info-card,
.agent-card {
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.62);
  border-radius: 8px;
  padding: 26px;
}

.card-heading {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #f97316;
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
  padding: 10px 12px;
  border-radius: 8px;
  background: #f8fafc;
}

.agent-card h2 {
  margin: 0 0 12px;
  color: #334155;
  font-size: 15px;
  font-weight: 850;
}

.agent-card p {
  margin: 0;
  overflow-wrap: anywhere;
  line-height: 1.7;
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
