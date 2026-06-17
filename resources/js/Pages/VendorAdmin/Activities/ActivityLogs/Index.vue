<template>
  <Head title="Activity Logs" />

  <div class="activity-page">
    <div class="activity-title">
      <History :size="20" />
      <h1>Activity Logs</h1>
    </div>

    <DataTable
      id="activityLogsTable"
      :url="route('vendor.activities.activity-logs.getdata')"
      :columns="columns"
      :columnDefs="columnDefs"
      :order="[[6, 'desc']]"
      searchPlaceholder="Search here..."
      wrapperClass="activity-table"
    >
      <template #header>
        <tr>
          <th>User</th>
          <th>IP Address</th>
          <th>Agent</th>
          <th>Log Name</th>
          <th>Event</th>
          <th>Subject</th>
          <th>Logged at</th>
          <th></th>
        </tr>
      </template>
    </DataTable>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import { History } from 'lucide-vue-next'

export default {
  name: 'ActivityLogsIndex',
  layout: VendorAdminLayout,
  components: { DataTable, Head, History },

  computed: {
    columns() {
      return [
        {
          data: 'user_display',
          name: 'user_name',
          render: (data, type, row) => this.userCell(row),
        },
        { data: 'ip_address', name: 'ip_address' },
        {
          data: 'user_agent',
          name: 'user_agent',
          orderable: false,
          searchable: false,
          render: (data, type, row) => this.agentCell(row),
        },
        {
          data: 'log_name',
          name: 'log_name',
          render: (data) => `<span class="log-chip">${this.escapeHtml(data)}</span>`,
        },
        {
          data: 'event',
          name: 'event',
          render: (data) => `<span class="event-chip event-chip--${this.escapeHtml(String(data).toLowerCase())}">${this.escapeHtml(data)}</span>`,
        },
        { data: 'subject_label', name: 'subject_label' },
        { data: 'created_at', name: 'created_at' },
        {
          data: 'show_url',
          name: 'show_url',
          orderable: false,
          searchable: false,
          render: (data) => `
            <button type="button" class="show-action" onclick="window.showActivityLog('${this.escapeHtml(data)}')" title="Show activity log">
              <i class="bi bi-eye"></i>
            </button>
          `,
        },
      ]
    },
      flash() {
      return this.$page.props.flash;
    },
    columnDefs() {
      return [
        { targets: '_all', className: 'align-middle' },
        { targets: 7, className: 'align-middle text-end action-column' },
      ]
    },
  },

  mounted() {
    window.showActivityLog = (url) => router.visit(url)
  },

  beforeUnmount() {
    delete window.showActivityLog
  },

  methods: {
    userCell(row) {
      return `
        <button type="button" class="activity-user" onclick="window.showActivityLog('${this.escapeHtml(row.show_url)}')">
          <span class="avatar-dot">${this.escapeHtml(row.user_initials || 'U')}</span>
          <span>
            <strong>${this.escapeHtml(row.user_name || 'System')}</strong>
            ${row.user_role ? `<em>${this.escapeHtml(row.user_role)}</em>` : ''}
            <small>${this.escapeHtml(row.user_email || '')}</small>
          </span>
        </button>
      `
    },
    agentCell(row) {
      return `
        <span class="agent-line">
          <i class="bi bi-display" title="From Desktop"></i><span>${this.escapeHtml(row.is_desktop || '-')}</span>
          <i class="bi bi-windows" title="Platform"></i><span>${this.escapeHtml(row.platform || '-')}</span>
          <i class="bi bi-globe2" title="Browser"></i><span>${this.escapeHtml(row.browser || '-')}</span>
        </span>
      `
    },
    escapeHtml(value = '') {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
    },
  },
  watch: {
    flash: {
      handler(flash) {
        if (flash?.message) {
          alertSuccess(flash.message);
        }

        if (flash?.error) {
          alertError(flash.error);
        }
      },
      immediate: true,
      deep: true
    }
  },
}
</script>

<style scoped>
.activity-page {
  padding: 22px;
}

.activity-title {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #3b82f6;
  margin-bottom: 28px;
}

.activity-title h1 {
  margin: 0;
  color: #334155;
  font-size: 20px;
  font-weight: 850;
}

:deep(.activity-table .dt-card) {
  border-radius: 8px;
  overflow: hidden;
}

:deep(.activity-user) {
  border: 0;
  background: transparent;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-align: left;
  padding: 0;
  color: #64748b;
}

:deep(.activity-user strong) {
  color: #5f6b7a;
  font-size: 14px;
}

:deep(.activity-user small) {
  display: block;
  color: #a7b0bd;
  margin-top: 2px;
}

:deep(.activity-user em) {
  margin-left: 6px;
  font-size: 10px;
  font-style: normal;
  color: #3b82f6;
  background: #fff2e4;
  border-radius: 4px;
  padding: 3px 7px;
}

:deep(.avatar-dot) {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: #3b82f6;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 12px;
  flex: 0 0 auto;
}

:deep(.agent-line) {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  color: #64748b;
}

:deep(.agent-line i) {
  color: #64748b;
}

:deep(.log-chip) {
  background: #cffafe;
  color: #06b6d4;
  border-radius: 5px;
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 800;
}

:deep(.event-chip) {
  border-radius: 5px;
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 800;
  background: #fef3c7;
  color: #3b82f6;
}

:deep(.event-chip--created) {
  background: #dcfce7;
  color: #22c55e;
}

:deep(.event-chip--deleted) {
  background: #fee2e2;
  color: #ef4444;
}

:deep(.show-action) {
  width: 34px;
  height: 34px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #3b82f6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.16s ease, border-color 0.16s ease, color 0.16s ease;
}

:deep(.show-action:hover) {
  background: #3b82f6;
  border-color: #3b82f6;
  color: #fff;
}

:deep(.action-column) {
  width: 58px !important;
}
</style>
