<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert"
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import InputField from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: SuperAdminLayout })

const props = defineProps({
  role: { type: Object, default: null },
  superPermissions: { type: Array, default: () => [] },
  vendorPermissions: { type: Array, default: () => [] },
})

const isEdit = computed(() => !!props.role?.id)

const guardOptions = [
  { id: 'superadmin', name: 'Super Admin Role' },
  { id: 'vendor', name: 'Vendor Capability Role' },
]

const form = useForm({
  name: props.role?.name ?? '',
  guard_name: props.role?.guard_name ?? '',
  permissions: props.role?.permissions?.map(p => p.name) ?? [],
})

const guard = computed(() => (isEdit.value ? props.role?.guard_name : form.guard_name) || '')

const availablePermissions = computed(() => {
  if (guard.value === 'superadmin') return props.superPermissions
  if (guard.value === 'vendor') return props.vendorPermissions
  return []
})

const q = ref('')
const showOnlySelected = ref(false)

function prettyLabel(s) {
  if (!s) return ''
  const withSpaces = String(s)
    .replace(/[_-]+/g, ' ')
    .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
    .trim()
  return withSpaces.charAt(0).toUpperCase() + withSpaces.slice(1)
}

function parsePermissionName(name) {
  const raw = String(name || '').trim()
  const parts = raw.split('.').map(p => p.trim()).filter(Boolean)
  if (parts.length === 0) return { group: 'General', action: raw || 'Unknown', name: raw }
  if (parts.length === 1) return { group: parts[0], action: parts[0], name: raw }
  return { group: parts[0], action: parts.slice(1).join('.'), name: raw }
}

const matrix = computed(() => {
  const rows = new Map()
  for (const p of availablePermissions.value || []) {
    const parsed = parsePermissionName(p?.name)
    const groupKey = parsed.group || 'General'
    const actionKey = parsed.action || 'Unknown'
    if (!rows.has(groupKey)) rows.set(groupKey, new Map())
    rows.get(groupKey).set(actionKey, parsed.name)
  }

  const query = q.value.trim().toLowerCase()
  const filtered = new Map()
  for (const [g, actions] of rows.entries()) {
    if (query && !RoughGroupMatch(query, g, actions)) continue
    filtered.set(g, actions)
  }

  if (showOnlySelected.value) {
    const onlySel = new Map()
    for (const [g, actions] of filtered.entries()) {
      const hasAnySelected = [...actions.values()].some(name => form.permissions.includes(name))
      if (hasAnySelected) onlySel.set(g, actions)
    }
    return onlySel
  }

  return filtered

  function RoughGroupMatch(query, groupKey, actionsMap) {
    const groupText = `${groupKey} ${prettyLabel(groupKey)}`.toLowerCase()
    if (groupText.includes(query)) return true
    for (const [a, permName] of actionsMap.entries()) {
      const actionText = `${a} ${prettyLabel(a)} ${permName}`.toLowerCase()
      if (actionText.includes(query)) return true
    }
    return false
  }
})

const actions = computed(() => {
  const set = new Set()
  for (const actionsMap of matrix.value.values()) {
    for (const a of actionsMap.keys()) set.add(a)
  }
  const priority = ['View', 'Create', 'Edit', 'Update', 'Delete', 'UpdatePhoto', 'UpdateInfo', 'UpdatePassword', '2faAuth', 'Deactivate']
  const arr = [...set]
  arr.sort((a, b) => {
    const ai = priority.indexOf(a)
    const bi = priority.indexOf(b)
    if (ai !== -1 && bi !== -1) return ai - bi
    if (ai !== -1) return -1
    if (bi !== -1) return 1
    return a.localeCompare(b)
  })
  return arr
})

const allPermissionNames = computed(() =>
  (availablePermissions.value || []).map(p => String(p?.name || '')).filter(Boolean)
)
const selectableCount = computed(() => allPermissionNames.value.length)
const selectedCount = computed(() => form.permissions.length)

function hasPerm(name) { return form.permissions.includes(name) }
function setPerm(name, checked) {
  if (!name) return
  const idx = form.permissions.indexOf(name)
  if (checked && idx === -1) form.permissions.push(name)
  if (!checked && idx !== -1) form.permissions.splice(idx, 1)
}
function togglePerm(name) { setPerm(name, !hasPerm(name)) }
function groupPermNames(groupKey) {
  const actionsMap = matrix.value.get(groupKey)
  if (!actionsMap) return []
  return [...actionsMap.values()].filter(Boolean)
}
function actionPermNames(actionKey) {
  const names = []
  for (const actionsMap of matrix.value.values()) {
    const n = actionsMap.get(actionKey)
    if (n) names.push(n)
  }
  return names
}
function setMany(names, checked) {
  const unique = Array.from(new Set(names.filter(Boolean)))
  for (const n of unique) setPerm(n, checked)
}

const allChecked = computed(() => {
  if (selectableCount.value === 0) return false
  return allPermissionNames.value.every(n => form.permissions.includes(n))
})
const someChecked = computed(() => allPermissionNames.value.some(n => form.permissions.includes(n)))

function toggleAll(checked) { setMany(allPermissionNames.value, checked) }
function groupAllChecked(groupKey) {
  const names = groupPermNames(groupKey)
  if (!names.length) return false
  return names.every(n => form.permissions.includes(n))
}
function groupSomeChecked(groupKey) { return groupPermNames(groupKey).some(n => form.permissions.includes(n)) }
function toggleGroup(groupKey, checked) { setMany(groupPermNames(groupKey), checked) }
function actionAllChecked(actionKey) {
  const names = actionPermNames(actionKey)
  if (!names.length) return false
  return names.every(n => form.permissions.includes(n))
}
function actionSomeChecked(actionKey) { return actionPermNames(actionKey).some(n => form.permissions.includes(n)) }
function toggleAction(actionKey, checked) { setMany(actionPermNames(actionKey), checked) }

const vIndeterminate = {
  mounted(el, binding) { el.indeterminate = !!binding.value },
  updated(el, binding) { el.indeterminate = !!binding.value },
}

watch(() => form.guard_name, (val, oldVal) => {
  if (isEdit.value) return
  if (!val || val === oldVal) return
  form.permissions = []
  q.value = ''
  showOnlySelected.value = false
})

function submit() {
  if (isEdit.value) {
    form.put(route('settings.roles.update', props.role.id), {
      preserveScroll: true,
      onError: (errors) => {
        const message =
          errors?.general ||
          Object.values(errors)?.flat()?.[0] ||
          'Something went wrong.'

        alertError(message)
      }
    })
    return
  }
  form.post(route('settings.roles.store'), {
    preserveScroll: true,
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong.'

      alertError(message)
    }
  })
}

const mobileSections = computed(() => {
  const sections = []
  for (const [groupKey, actionsMap] of matrix.value.entries()) {
    const permissions = [...actionsMap.entries()].map(([action, name]) => ({ action, name }))
    sections.push({ groupKey, permissions })
  }
  return sections
})
</script>

<template>

  <Head :title="isEdit ? 'Update Role' : 'Create Role'" />

  <div class="page-wrap">

    <!--MOBILE LAYOUT  (< 768px)-->
    <div class="layout-mobile">

      <!-- Gradient overlay -->
      <div class="gradientOverlay"></div>

      <!-- Header -->
      <div class="formHeader">
        <h1 class="header-title">{{ isEdit ? `Update Role — ${role?.name ?? ''}` : 'Create Role' }}</h1>
        <p class="header-subtitle">{{ isEdit ? 'Edit role name and permissions.' : 'Create a new role and assign permissions.' }}</p>
        <div class="m-header-badges">
          <span v-if="guard" class="m-badge" :class="guard === 'superadmin' ? 'bg-secondary' : 'bg-black'">
            {{ guard }}
          </span>
          <span class="m-badge m-badge--count">{{ selectedCount }} / {{ selectableCount }} selected</span>
        </div>
      </div>

      <!-- Role Info Card -->
      <div class="form-card">
        <div class="card-accent-line"></div>
        <div class="card-header">
          <h2 class="cardTitle">Role Information</h2>
        </div>
        <div class="formCardBody">
          <form @submit.prevent="submit">
            <div class="m-field">
              <InputField id="roleName" label="Role Name" v-model="form.name"
                placeholder="e.g. system_admin / vendor_admin" :error="form.errors.name" />
            </div>
            <div class="m-field" v-if="!isEdit">
              <SelectInput label="Role Type" v-model="form.guard_name" :options="guardOptions" valueKey="id"
                labelKey="name" placeholder="Select role type" :error="form.errors.guard_name" />
            </div>
          </form>
        </div>
      </div>

      <!-- Permissions Card -->
      <div class="form-card" v-if="guard">
        <div class="card-accent-line"></div>
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <h2 class="cardTitle">Permissions</h2>
          <span class="selected-count">{{ selectedCount }} selected</span>
        </div>
        <div class="formCardBody">

          <!-- Search + filters -->
          <div class="m-search-wrap">
            <div class="m-search-input-wrap">
              <i class="bi bi-search m-search-icon"></i>
              <input v-model="q" type="text" class="m-search-field" placeholder="Search module / permission..." />
            </div>
            <div class="m-filter-row">
              <label class="m-chip">
                <input type="checkbox" v-model="showOnlySelected" />
                <span>Only selected</span>
              </label>
              <button type="button" class="m-btn-clear" :disabled="form.permissions.length === 0"
                @click="form.permissions = []">
                Clear all
              </button>
            </div>
          </div>

          <!-- Global select all -->
          <label class="m-select-all">
            <input type="checkbox" :checked="allChecked" v-indeterminate="someChecked && !allChecked"
              @change="toggleAll($event.target.checked)" />
            <span>Select all permissions</span>
          </label>

          <!-- Empty state -->
          <div v-if="mobileSections.length === 0" class="empty-state">
            No permissions found.
          </div>

          <!-- Sections -->
          <div v-for="section in mobileSections" :key="section.groupKey" class="m-section">
            <div class="permission-section__header">
              <div>
                <h6 class="permission-section__title">{{ prettyLabel(section.groupKey) }}</h6>
                <div class="permission-section__subtitle">{{ section.permissions.length }} permission(s)</div>
              </div>
              <label class="m-group-toggle">
                <input type="checkbox" :checked="groupAllChecked(section.groupKey)"
                  v-indeterminate="groupSomeChecked(section.groupKey) && !groupAllChecked(section.groupKey)"
                  @change="toggleGroup(section.groupKey, $event.target.checked)" />
                <span>All</span>
              </label>
            </div>

            <div class="permission-grid">
              <label v-for="p in section.permissions" :key="p.name" class="permission-card"
                :class="{ 'permission-card--checked': hasPerm(p.name) }">
                <input type="checkbox" :checked="hasPerm(p.name)" @change="togglePerm(p.name)" />
                <span class="permission-card__label">{{ prettyLabel(p.action) }}</span>
              </label>
            </div>
          </div>

          <div v-if="form.errors.permissions" class="error-text mt-3">{{ form.errors.permissions }}</div>
        </div>
      </div>

      <!-- No guard selected -->
      <div class="form-card" v-else>
        <div class="formCardBody">
          <div class="helper-box">
            <div class="helper-box__title">Getting started</div>
            <p class="helper-box__text mb-0">Select <b>Role Type</b> above to load available permissions.</p>
          </div>
        </div>
      </div>

      <!-- General errors -->
      <div v-if="form.hasErrors && Object.keys(form.errors).length > 0" class="alert alert-danger">
        <ul class="mb-0">
          <li v-for="(error, field) in form.errors" :key="field"
            v-if="!['name', 'guard_name', 'permissions'].includes(field)">
            {{ error }}
          </li>
        </ul>
      </div>

      <!-- Footer actions -->
      <div class="m-footer">
        <Link :href="route('settings.roles.index')" class="btn btn-ghost">Cancel</Link>
        <button type="button" class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
          <span v-if="form.processing" class="spinner-icon"></span>
          {{ form.processing ? 'Saving...' : (isEdit ? 'Update Role' : 'Create Role') }}
        </button>
      </div>

    </div>
    <!-- end layout-mobile -->


    <!-- DESKTOP LAYOUT  (≥ 768px)-->
    <div class="layout-desktop">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Role' : 'Create Role' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Update role name and permissions.' : 'Create a staff role and assign permissions.' }}
            </p>
          </div>
          <div class="d-flex gap-2">
            <Link :href="route('settings.roles.index')" class="btn btn-ghost">Cancel</Link>
            <button type="button" class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Role' : 'Create Role') }}
            </button>
          </div>
        </div>
      </div>
      <div class="card border-0 shadow-sm card-shell">
        <div class="card-header">
          <h2 class="card-title cardTitle">Role Information</h2>
        </div>
        <div class="card-body px-4 py-4">
          <form @submit.prevent="submit">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <InputField id="roleName" label="Role Name" v-model="form.name"
                  placeholder="e.g. system_admin / vendor_admin" :error="form.errors.name" />
              </div>
              <div class="col-12 col-md-6" v-if="!isEdit">
                <SelectInput label="Role Type" v-model="form.guard_name" :options="guardOptions" valueKey="id"
                  labelKey="name" placeholder="Select role type" :error="form.errors.guard_name" />
              </div>
            </div>

            <div class="mt-4">
              <div class="d-flex w-100 mb-2">
                <div class="d-flex align-items-center gap-2 ms-auto">
                  <span v-if="guard" class="badge rounded-pill px-3 py-2"
                    :class="guard === 'superadmin' ? 'bg-secondary' : 'bg-black'">
                    {{ guard }}
                  </span>

                  <span class="badge rounded-pill bg-light text-dark px-3 py-2 border">
                    {{ selectedCount }} / {{ selectableCount }} selected
                  </span>
                </div>
              </div>
              <div v-if="guard" class="perm-table-wrap">

                <!-- Search + filters -->
                <div class="m-search-wrap">
                  <div class="m-search-input-wrap">
                    <i class="bi bi-search m-search-icon"></i>
                    <input v-model="q" type="text" class="m-search-field" placeholder="Search module / permission..." />
                  </div>
                  <div class="m-filter-row">
                    <label class="m-chip">
                      <input type="checkbox" v-model="showOnlySelected" />
                      <span>Only selected</span>
                    </label>
                    <button type="button" class="m-btn-clear" :disabled="form.permissions.length === 0"
                      @click="form.permissions = []">
                      Clear all
                    </button>
                  </div>
                </div>

                <!-- Global select all -->
                <label class="m-select-all">
                  <input type="checkbox" :checked="allChecked" v-indeterminate="someChecked && !allChecked"
                    @change="toggleAll($event.target.checked)" />
                  <span>Select all permissions</span>
                </label>

                <!-- Empty state -->
                <div v-if="mobileSections.length === 0" class="empty-state">
                  No permissions found.
                </div>

                <!-- Sections -->
                <div v-for="section in mobileSections" :key="section.groupKey" class="m-section">
                  <div class="permission-section__header">
                    <div>
                      <h6 class="permission-section__title">{{ prettyLabel(section.groupKey) }}</h6>
                      <div class="permission-section__subtitle">{{ section.permissions.length }} permission(s)</div>
                    </div>
                    <label class="m-group-toggle">
                      <input type="checkbox" :checked="groupAllChecked(section.groupKey)"
                        v-indeterminate="groupSomeChecked(section.groupKey) && !groupAllChecked(section.groupKey)"
                        @change="toggleGroup(section.groupKey, $event.target.checked)" />
                      <span>All</span>
                    </label>
                  </div>

                  <div class="permission-grid">
                    <label v-for="p in section.permissions" :key="p.name" class="permission-card"
                      :class="{ 'permission-card--checked': hasPerm(p.name) }">
                      <input type="checkbox" :checked="hasPerm(p.name)" @change="togglePerm(p.name)" />
                      <span class="permission-card__label">{{ prettyLabel(p.action) }}</span>
                    </label>
                  </div>
                </div>

                <div v-if="form.errors.permissions" class="error-text mt-3">{{ form.errors.permissions }}</div>
              </div>

              <div v-else class="empty-perms">
                <div class="text-muted small">Select <b>Role Type</b> to load permissions.</div>
              </div>

              <div v-if="form.hasErrors && Object.keys(form.errors).length > 0" class="alert alert-danger mt-4">
                <ul class="mb-0">
                  <li v-for="(error, field) in form.errors" :key="field"
                    v-if="!['name', 'guard_name', 'permissions'].includes(field)">
                    {{ error }}
                  </li>
                </ul>
              </div>
            </div>
          </form>
        </div>

      </div>
    </div>
    <!-- end layout-desktop -->

  </div>
</template>

<style scoped>
/* LAYOUT SWITCHES */
.layout-mobile {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.layout-desktop {
  display: none;
}

/* PAGE WRAP */
.page-wrap {
  position: relative;
  padding: 0 16px;
  overflow: hidden;
}

.m-header-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.m-badge {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 12px;
  color: #fff;
  border-radius: 999px;
  white-space: nowrap;
}

.m-badge--count {
  background: var(--primary-light);
  color: var(--primary);
  border: 1px solid var(--primary-mid);
}

/* MOBILE — cards */
.card-accent-line {
  height: 3px;
  background: linear-gradient(135deg, var(--primary) 0%, #f5a623 100%);
}

/* MOBILE — selected count badge */
.selected-count {
  font-size: 13px;
  font-weight: 700;
  color: var(--primary);
  background: var(--primary-light);
  padding: 5px 12px;
  border-radius: 999px;
  white-space: nowrap;
}

/* MOBILE — helper box */
.helper-box {
  border: 1px dashed var(--primary-mid);
  background: #fffaf0;
  border-radius: 14px;
  padding: 16px;
}

.helper-box__title {
  font-size: 13px;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 6px;
}

.helper-box__text {
  color: var(--slate-600);
  font-size: 14px;
  line-height: 1.5;
}

/* MOBILE — search */
.m-search-wrap {
  margin-bottom: 16px;
}

.m-search-input-wrap {
  position: relative;
  margin-bottom: 10px;
}

.m-search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--slate-400);
  font-size: 14px;
  pointer-events: none;
}

.m-search-field {
  width: 100%;
  height: 42px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  padding: 0 12px 0 36px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.m-search-field:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
}

.m-filter-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.m-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: #fff;
  border-radius: 999px;
  padding: 5px 12px;
  font-size: 13px;
  cursor: pointer;
  color: var(--slate-700);
}

.m-btn-clear {
  font-size: 13px;
  padding: 5px 14px;
  border-radius: 999px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  background: #fff;
  color: var(--slate-700);
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
}

.m-btn-clear:hover:not(:disabled) {
  border-color: var(--primary);
  color: var(--primary);
}

.m-btn-clear:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* MOBILE — select-all row */
.m-select-all {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--slate-700);
  padding: 10px 14px;
  background: var(--primary-light);
  border-radius: 10px;
  margin-bottom: 16px;
  cursor: pointer;
  border: 1px solid var(--primary-mid);
}

/* MOBILE — permission sections */
.m-section {
  border-top: 1px solid rgba(242, 140, 0, 0.1);
  padding-top: 16px;
  margin-top: 16px;
}

.m-section:first-of-type {
  border-top: none;
  padding-top: 0;
  margin-top: 0;
}

.permission-section__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.permission-section__title {
  font-weight: 700;
  color: var(--slate-900);
  margin: 0;
  font-size: 14px;
}

.permission-section__subtitle {
  font-size: 12px;
  color: var(--slate-400);
  margin-top: 2px;
}

.m-group-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--slate-600);
  cursor: pointer;
  white-space: nowrap;
  padding: 4px 10px;
  border-radius: 999px;
}

.m-group-toggle:hover {
  border-color: var(--primary);
  color: var(--primary);
}

/* MOBILE — permission cards */
.permission-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}


.permission-card {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: #fafafa;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.permission-card:hover {
  border-color: var(--primary);
  background: #fffaf0;
}

.permission-card--checked {
  border-color: rgba(242, 140, 0, 0.45);
  background: #fffaf0;
}

.permission-card__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--slate-700);
  line-height: 1.3;
}

/* MOBILE — empty + error */
.empty-state {
  text-align: center;
  color: var(--slate-400);
  padding: 32px 0;
  font-style: italic;
  font-size: 14px;
}

.error-text {
  font-size: 12px;
  color: #dc2626;
}

/* MOBILE — footer actions */
.m-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 4px 0 8px;
}

.m-field+.m-field {
  margin-top: 16px;
}

/* MOBILE — spinner */
.spinner-icon {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* DESKTOP */
.card-shell {
  border-radius: 16px;
}

.perm-table-wrap {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 14px;
  overflow: hidden;
  background: #fff;
}

.empty-perms {
  border: 1px dashed rgba(0, 0, 0, 0.2);
  border-radius: 14px;
  padding: 16px;
  background: #fff;
}


@media (min-width: 768px) {
  .layout-mobile {
    display: none;
  }

  .layout-desktop {
    display: block;
  }

  .perm-table-wrap {
    border: none !important;
    border-radius: 0 !important;
  }

  .permission-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 400px) {
  .permission-grid {
    grid-template-columns: 1fr;
  }
}
</style>