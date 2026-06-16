<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import { usePermission } from "@/composables/usePermission";
import DeleteModal from '@/Components/DeleteModal.vue'

const { can } = usePermission()

defineOptions({
  layout: VendorAdminLayout,
})

const page = usePage()

const props = defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
})

const deleting = ref(false)
const deleteTarget = ref({ name: '', id: null })
const searchQuery = ref('')

const filteredRoles = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return props.roles
  return props.roles.filter(role =>
    role.name.toLowerCase().includes(q) ||
    role.permissions.some(p => p.name.toLowerCase().includes(q))
  )
})

const showDeleteModal = ref(false)

const openDeleteModal = (role) => {
  deleteTarget.value = role
  showDeleteModal.value = true
}

const goToCreateRole = () => {
  router.get(route('vendor.roles.create'))
}

const onModalClosed = () => { }

const confirmDelete = () => {
  if (!deleteTarget.value?.id) return

  deleting.value = true

  router.delete(route('vendor.roles.destroy', deleteTarget.value.id), {
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

  <Head title="Staff Roles" />

  <div class="page-container">
    <div class="card-modern">

      <!-- HEADER -->
      <div class="card-modern-header">
        <div class="header-content">

          <div class="header-title-group">
            <h1 class="header-title">Staff Roles</h1>
            <p class="header-subtitle">
              Manage roles and permissions for your staff members.
            </p>
          </div>

          <button v-if="can('roles-permissions.create')" type="button" class="btn-primary-modern"
            @click="goToCreateRole">
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
          <input v-model="searchQuery" type="text" class="search-input"
            placeholder="Search by role name or permission…" />
          <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''">
            <i class="bi bi-x-lg"></i>
          </button>
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
          <div class="empty-title">No roles match "{{ searchQuery }}"</div>
          <div class="empty-subtitle">Try a different search term.</div>
        </div>

        <div v-else class="roles-grid">
          <div v-for="role in filteredRoles" :key="role.id" class="role-card">

            <!-- CARD TOP -->
            <div class="role-card-top">
              <div class="role-icon-wrap">
                <i class="bi bi-shield-fill-check role-icon"></i>
              </div>
              <div class="role-actions">
                <Link v-if="can('roles-permissions.edit')" :href="route('vendor.roles.edit', role.id)"
                  class="btn-circle" title="Edit">
                  <i class="bi bi-pencil-fill"></i>
                </Link>
                <button v-if="can('roles-permissions.delete')" class="btn-circle btn-circle-danger" title="Delete"
                  @click="openDeleteModal(role)">
                  <i class="bi bi-trash3-fill"></i>
                </button>
              </div>
            </div>

            <!-- ROLE NAME -->
            <div class="role-name">{{ role.name }}</div>

            <!-- PERMISSIONS -->
            <div class="role-permissions-section">
              <div class="permissions-label">
                <i class="bi bi-key-fill"></i>
                Permissions
                <span class="perm-count">{{ role.permissions.length }}</span>
              </div>

              <div v-if="role.permissions.length" class="permission-wrap">
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
.search-bar-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 20px;
  padding: 0 1.5rem 1.25rem;
  border-bottom: 1px solid var(--slate-50);
}

.search-input-wrapper {
  position: relative;
  flex: 1;
  max-width: 420px;
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
  border-color: var(--primary);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.12);
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
  color: var(--primary);
}

.role-actions {
  display: flex;
  gap: 8px;
}

.role-name {
  font-size: 16px;
  font-weight: 700;
  color: #111827;
  line-height: 1.3;
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
  color: var(--primary);
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
  color: var(--primary);
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
  border: 1px solid var(--slate-200);
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  color: #374151;
  font-size: 13px;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.btn-circle:hover {
  background: var(--primary-soft);
  color: var(--primary);
  border-color: rgba(242, 140, 0, 0.3);
}

.btn-circle-danger:hover {
  background: rgba(239, 68, 68, 0.08);
  color: #ef4444;
  border-color: rgba(239, 68, 68, 0.25);
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
  color: var(--slate-600);
  margin-top: 6px;
}

.modal-sm-custom {
  max-width: 380px;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>