<script setup>
import { onMounted, onUnmounted, computed, ref, watch } from 'vue'
import { router, usePage, Head } from '@inertiajs/vue3'
import SelectInput from '@/Components/SelectInput.vue'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

const page = usePage()

defineOptions({ layout: SuperAdminLayout })

const props = defineProps({
  roles: { type: Array, default: () => [] },
})

/** UI */
const search = ref('')
const guardFilter = ref('all')

const guardOptions = [
  { id: 'all', name: 'All Guards' },
  { id: 'superadmin', name: 'superadmin' },
  { id: 'vendor', name: 'vendor' },
]

const filteredRoles = computed(() => {
  const q = search.value.trim().toLowerCase()
  return (props.roles || []).filter(r => {
    const passGuard = guardFilter.value === 'all' ? true : r.guard_name === guardFilter.value
    if (!passGuard) return false

    if (!q) return true
    const name = String(r.name || '').toLowerCase()
    const guard = String(r.guard_name || '').toLowerCase()
    const perms = (r.permissions || []).map(p => String(p?.name || '')).join(' ').toLowerCase()
    return name.includes(q) || guard.includes(q) || perms.includes(q)
  })
})

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

function goCreate() {
  router.visit(route('settings.roles.create'))
}

function goEdit(id) {
  router.visit(route('settings.roles.edit', id))
}

function openDeleteModal(role) {
  deleteTarget.value = { id: role.id, name: role.name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  if (!deleteTarget.value?.id) return

  deleting.value = true

  router.delete(route('settings.roles.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
    },
    onError: () => {
      deleting.value = false
    },
    onFinish: () => {
      deleting.value = false
    },
  })
}

function isProtectedRole(r) {
  const n = String(r?.name || '').toLowerCase()
  return n === 'super admin' || n === ''
}

function isProtectedRoleDelete(r) {
  const n = String(r?.name || '').toLowerCase()
  return n === 'super admin' || n === 'vendor admin'
}

function isSuperAdmin(r) {
  return String(r?.guard_name || '') === 'superadmin'
} 
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) alertSuccess(flash.message)
    if (flash?.error) alertError(flash.error)
  },
  { immediate: true }
)
</script>

<template>

  <Head title="Roles" />

  <div class="page-container">
    <div class="card-modern">

      <!-- HEADER -->
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">System Roles</h1>
            <p class="header-subtitle">
              Manage platform roles and define permissions for different user groups.
            </p>
          </div>

          <button v-if="can('vendor.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Role</span>
            </span>
          </button>
        </div>
      </div>

      <!-- SEARCH BAR -->
      <div class="search-bar-wrap">
        <div class="search-input-wrapper">
          <i class="bi bi-search search-icon"></i>
          <input v-model="search" type="text" class="search-input"
            placeholder="Search by role name, guard or permission…" />
          <button v-if="search" class="search-clear" @click="search = ''">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="guard-select">
          <SelectInput id="select_guard" v-model="guardFilter" :options="guardOptions" valueKey="id" labelKey="name"
            placeholder="Select Guard" />
        </div>

        <div class="search-meta">
          <span class="result-count">
            {{ filteredRoles.length }}
            {{ filteredRoles.length === 1 ? 'role' : 'roles' }}
          </span>
        </div>
      </div>

      <!-- ROLE CARDS GRID -->
      <div class="cards-container">

        <div v-if="roles.length === 0" class="empty-state-modern">
          <div class="empty-icon"><i class="bi bi-shield-lock"></i></div>
          <div class="empty-title">No roles created yet</div>
          <div class="empty-subtitle">Create roles to manage staff permissions.</div>
        </div>

        <div v-else-if="filteredRoles.length === 0" class="empty-state-modern">
          <div class="empty-icon"><i class="bi bi-search"></i></div>
          <div class="empty-title">No roles match "{{ search }}"</div>
          <div class="empty-subtitle">Try a different search term or filter.</div>
        </div>

        <div v-else class="roles-grid">
          <div v-for="role in filteredRoles" :key="role.id" class="role-card">

            <!-- CARD TOP -->
            <div class="role-card-top">
              <div class="role-icon-wrap">
                <i class="bi bi-shield-fill-check role-icon"></i>
              </div>
              <div class="role-actions">
                <button class="btn-circle" v-if="can('vendor.edit')" title="Edit" :disabled="isProtectedRole(role)"
                  @click="goEdit(role.id)" :class="{ 'btn-circle--disabled': isProtectedRole(role) }">
                  <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="btn-circle btn-circle-danger" v-if="can('vendor.delete')" title="Delete"
                  :disabled="isProtectedRoleDelete(role)" @click="openDeleteModal(role)"
                  :class="{ 'btn-circle--disabled': isProtectedRoleDelete(role) }">
                  <i class="bi bi-trash3-fill"></i>
                </button>
              </div>
            </div>

            <!-- ROLE NAME + GUARD BADGE -->
            <div class="role-name-row">
              <div class="role-name">{{ role.name }}</div>
              <span class="guard-badge"
                :class="role.guard_name === 'superadmin' ? 'guard-badge--primary' : 'guard-badge--secondary'">
                {{ role.guard_name }}
              </span>
            </div>

            <!-- ROLE ID -->
            <div class="role-meta">
              <span class="role-meta-label">ID:</span>
              <span class="role-meta-value">{{ role.id }}</span>
            </div>

            <!-- PERMISSIONS -->
            <div class="role-permissions-section">
              <div class="permissions-label">
                <i class="bi bi-key-fill"></i>
                Permissions
                <span class="perm-count">{{ role.permissions?.length || 0 }}</span>
              </div>

              <div v-if="role.permissions?.length" class="permission-wrap">
                <span v-for="perm in role.permissions.slice(0, 5)" :key="perm.id" class="permission-chip">
                  {{ perm.name }}
                </span>
                <span v-if="role.permissions.length > 5" class="permission-chip permission-chip--more">
                  +{{ role.permissions.length - 5 }} more
                </span>
              </div>

              <span v-else class="no-permissions">No permissions assigned</span>
            </div>

            <!-- CARD FOOTER (timestamps) -->
            <div class="role-card-footer">
              <div class="timestamp-item">
                <i class="bi bi-calendar-plus"></i>
                <span>{{ role.created_at || '—' }}</span>
              </div>
              <div class="timestamp-divider"></div>
              <div class="timestamp-item">
                <i class="bi bi-pencil-square"></i>
                <span>{{ role.updated_at || '—' }}</span>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this role?" cancel-label="Keep Role" confirm-label="Delete Role"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<style scoped>
/* ... all styles unchanged ... */
.page-container {
  padding: 0;
}

.card-modern {
  background: #fff;
  border-radius: 20px;
  border: 1px solid #e8edf4;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.card-modern-header {
  padding: 1.5rem 1.5rem 1.25rem;
  border-bottom: 1px solid var(--slate-50, #f8fafc);
}

.header-content {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.header-title-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.header-title {
  font-size: 22px;
  font-weight: 800;
  color: #111827;
  margin: 0;
}

.header-subtitle {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
}

.btn-primary-modern {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: linear-gradient(135deg, #F57C00, #FF9500);
  color: #fff;
  border: none;
  border-radius: 999px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.2s, transform 0.15s;
}

.btn-primary-modern:hover {
  background: linear-gradient(135deg, #e06f00, #f08c00);
  transform: translateY(-1px);
}

.btn-primary-modern i {
  font-size: 16px;
}

.search-bar-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 20px;
  padding: 0 1.5rem 1.25rem;
  border-bottom: 1px solid var(--slate-50, #f8fafc);
  flex-wrap: wrap;
}

.search-input-wrapper {
  position: relative;
  flex: 1;
  max-width: 420px;
  min-width: 220px;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 14px;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.55rem 2.4rem 0.55rem 2.4rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  background: #f8fafc;
  color: #1e293b;
  transition: border-color 0.15s, box-shadow 0.15s;
  outline: none;
}

.search-input:focus {
  border-color: #FF9500;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(255, 149, 0, 0.12);
}

.search-input::placeholder {
  color: #94a3b8;
}

.search-clear {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 12px;
  cursor: pointer;
  padding: 2px 4px;
  line-height: 1;
}

.search-clear:hover {
  color: #475569;
}

.guard-select {
  border-radius: 10px;
  color: #1e293b;
  margin-bottom: -3px;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.guard-select:focus {
  border-color: #FF9500;
  box-shadow: 0 0 0 3px rgba(255, 149, 0, 0.12);
}

.search-meta {
  margin-left: auto;
}

.result-count {
  font-size: 13px;
  font-weight: 700;
  color: #f28c00;
  background: rgba(242, 140, 0, 0.10);
  padding: 6px 12px;
  border-radius: 999px;
}

.cards-container {
  padding: 1.5rem;
}

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}

.role-card {
  background: #fff;
  border: 1px solid #e8edf4;
  border-radius: 16px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
}

.role-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
  border-color: rgba(242, 140, 0, 0.3);
}

.role-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.role-icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: rgba(242, 140, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.role-icon {
  font-size: 18px;
  color: #f28c00;
}

.role-actions {
  display: flex;
  gap: 8px;
}

.role-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.role-name {
  font-size: 16px;
  font-weight: 700;
  color: #111827;
  line-height: 1.3;
}

.guard-badge {
  display: inline-flex;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.guard-badge--primary {
  background: rgba(255, 149, 0, 0.1);
  color: #FF9500;
  border: 1px solid rgba(255, 149, 0, 0.2);
}

.guard-badge--secondary {
  background: rgba(15, 23, 42, 0.05);
  color: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(15, 23, 42, 0.1);
}

.role-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
}

.role-meta-label {
  color: #94a3b8;
  font-weight: 600;
}

.role-meta-value {
  color: #64748b;
  font-family: 'Monaco', 'Courier New', monospace;
  font-size: 11px;
}

.role-permissions-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
  height: 140px;
  overflow: hidden;
}

.permissions-label {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: #94a3b8;
}

.perm-count {
  background: rgba(242, 140, 0, 0.12);
  color: #f28c00;
  border-radius: 999px;
  padding: 1px 7px;
  font-size: 11px;
  font-weight: 700;
  margin-left: 2px;
}

.permission-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.permission-chip {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  background: rgba(242, 140, 0, 0.12);
  color: #f28c00;
  border: 1px solid rgba(242, 140, 0, 0.25);
}

.permission-chip--more {
  background: rgba(15, 23, 42, 0.04);
  color: rgba(15, 23, 42, 0.7);
  border-color: rgba(15, 23, 42, 0.1);
}

.no-permissions {
  font-size: 12px;
  color: #94a3b8;
  font-style: italic;
}

.role-card-footer {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 0.75rem;
  border-top: 1px solid #f1f5f9;
  margin-top: auto;
}

.timestamp-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: #94a3b8;
}

.timestamp-item i {
  font-size: 11px;
}

.timestamp-divider {
  width: 1px;
  height: 12px;
  background: #e2e8f0;
  margin: 0 2px;
}

.btn-circle {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  color: #374151;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.btn-circle:hover:not(.btn-circle--disabled) {
  background: rgba(242, 140, 0, 0.08);
  color: #f28c00;
  border-color: rgba(242, 140, 0, 0.3);
}

.btn-circle-danger:hover:not(.btn-circle--disabled) {
  background: rgba(239, 68, 68, 0.08);
  color: #ef4444;
  border-color: rgba(239, 68, 68, 0.25);
}

.btn-circle--disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.empty-state-modern {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 36px;
  color: #cbd5e1;
  margin-bottom: 14px;
}

.empty-title {
  font-weight: 700;
  font-size: 18px;
  color: #111827;
}

.empty-subtitle {
  color: #64748b;
  margin-top: 6px;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
  }

  .btn-primary-modern {
    width: 100%;
    justify-content: center;
  }

  .search-bar-wrap {
    flex-direction: column;
    align-items: stretch;
  }

  .search-input-wrapper {
    max-width: 100%;
  }

  .guard-select {
    width: 100%;
  }

  .search-meta {
    margin-left: 0;
  }

  .roles-grid {
    grid-template-columns: 1fr;
  }
}
</style>