<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  role: {
    type: Object,
    default: null,
  },
  permissionSections: {
    type: Array,
    default: () => [],
  },
})

const isEdit = computed(() => !!props.role?.id)

const form = useForm({
  name: props.role?.name ?? '',
  permissions: props.role?.permissions?.map(p => p.name) ?? [],
})

const toggleSection = (sectionPermissions) => {
  const names = sectionPermissions.map(p => p.name)
  const allSelected = names.every(name => form.permissions.includes(name))
  if (allSelected) {
    form.permissions = form.permissions.filter(name => !names.includes(name))
  } else {
    form.permissions = [...new Set([...form.permissions, ...names])]
  }
}

const sectionChecked = (sectionPermissions) => {
  const names = sectionPermissions.map(p => p.name)
  return names.length > 0 && names.every(name => form.permissions.includes(name))
}

const toggleAll = () => {
  const allNames = props.permissionSections.flatMap(s => s.permissions.map(p => p.name))
  const allSelected = allNames.every(name => form.permissions.includes(name))
  form.permissions = allSelected ? [] : allNames
}

const allSelected = computed(() => {
  const allNames = props.permissionSections.flatMap(s => s.permissions.map(p => p.name))
  return allNames.length > 0 && allNames.every(name => form.permissions.includes(name))
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('vendor.roles.update', props.role.id), {
      preserveScroll: true,
      onError: (errors) => {
        const message =
          errors?.general ||
          Object.values(errors)?.flat()?.[0] ||
          'Something went wrong.'

        alertError(message)
      }
    },)
    return
  }
  form.post(route('vendor.roles.store'), {
    preserveScroll: true,
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong.'

      alertError(message)
    }
  },)
}
</script>

<template>

  <Head :title="isEdit ? 'Update Role' : 'Create Role'" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <!-- Header -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Role' : 'Create Role' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Update role name and permissions.' : 'Create a staff role and assign permissions.' }}
            </p>
          </div>
          <div class="d-flex gap-2">
            <Link :href="route('vendor.roles.index')" class="btn btn-ghost">Cancel</Link>
            <button type="button" class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Role' : 'Create Role') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit">
        <div v-if="form.errors.general" class="alert alert-danger mb-4">
          {{ form.errors.general }}
        </div>

        <div class="layout-mobile">

          <!-- Card 1: Role Info -->
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="card-header">
              <h2 class="card-title cardTitle">Role Information</h2>
            </div>
            <div class="card-body formCardBody">
              <div class="mb-4">
                <label class="form-label formLabel">Role Name</label>
                <input v-model="form.name" type="text" class="form-control formControl"
                  placeholder="e.g. Store Manager">
                <div v-if="form.errors.name" class="error-text">
                  {{ form.errors.name }}
                </div>
              </div>
              <div class="helper-box">
                <div class="helper-box__title">Tips</div>
                <p class="helper-box__text mb-0">
                  Use a clear role name and only enable permissions this staff role really needs.
                </p>
              </div>
            </div>
          </div>

          <!-- Card 2: Permissions -->
          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="card-header d-flex justify-content-between align-items-center">
              <h2 class="card-title cardTitle">Permissions</h2>
              <div class="permissions-meta">
                <span class="selected-count">{{ form.permissions.length }} selected</span>
                <label class="m-group-toggle">
                  <input type="checkbox" @change="toggleAll" />
                  <span>All</span>
                </label>
              </div>
            </div>
            <div class="card-body formCardBody">
              <div v-if="permissionSections.length === 0" class="empty-state">
                No permissions available.
              </div>

              <div v-for="section in permissionSections" :key="section.section" class="permission-section">
                <div class="permission-section__header">
                  <div>
                    <h6 class="permission-section__title">{{ section.label }}</h6>
                    <div class="permission-section__subtitle">
                      {{ section.permissions.length }} permissions
                    </div>
                  </div>

                  <label class="m-group-toggle">
                    <input type="checkbox" @change="toggleSection(section.permissions)" />
                    <span>All</span>
                  </label>
                </div>

                <div class="permission-grid mobile-permission-grid">
                  <label v-for="permission in section.permissions" :key="permission.id" class="permission-card"
                    :class="{ 'permission-card--checked': form.permissions.includes(permission.name) }">
                    <input type="checkbox" :value="permission.name" v-model="form.permissions">
                    <span class="permission-card__label">{{ permission.name }}</span>
                  </label>
                </div>
              </div>

              <div v-if="form.errors.permissions" class="error-text mt-3">
                {{ form.errors.permissions }}
              </div>
            </div>
          </div>

        </div>

        <div class="layout-desktop">

          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="card-header">
              <h2 class="card-title cardTitle">Role Information</h2>
            </div>
            <div class="card-top-bar">
              <div class="role-name-group">
                <label class="form-label formLabel">Role Name</label>
                <div class="role-name-input-wrap">
                  <input v-model="form.name" type="text" class="form-control formControl"
                    placeholder="e.g. Store Manager">
                  <div v-if="form.errors.name" class="error-text mt-1">
                    {{ form.errors.name }}
                  </div>
                </div>
                <div class="helper-hint">
                  Use a clear name and only enable permissions this role really needs.
                </div>
              </div>

              <div class="permissions-meta">
                <span class="selected-count">{{ form.permissions.length }} selected</span>
                <label class="m-group-toggle">
                  <input type="checkbox" @change="toggleAll" />
                  <span>All</span>
                </label>
              </div>
            </div>

            <!-- Divider -->
            <div class="section-divider"></div>

            <!-- Permissions body -->
            <div class="permissions-body">
              <div v-if="permissionSections.length === 0" class="empty-state">
                No permissions available.
              </div>

              <div v-for="section in permissionSections" :key="section.section" class="permission-section">
                <div class="permission-section__header">
                  <div>
                    <h6 class="permission-section__title">{{ section.label }}</h6>
                    <div class="permission-section__subtitle">{{ section.permissions.length }} permissions</div>
                  </div>
                  <label class="m-group-toggle">
                    <input type="checkbox" @change="toggleSection(section.permissions)" />
                    <span>All</span>
                  </label>
                </div>

                <div class="permission-grid desktop-permission-grid">
                  <label v-for="permission in section.permissions" :key="permission.id" class="permission-card"
                    :class="{ 'permission-card--checked': form.permissions.includes(permission.name) }">
                    <input type="checkbox" :value="permission.name" v-model="form.permissions">
                    <span class="permission-card__label">{{ permission.name }}</span>
                  </label>
                </div>
              </div>

              <div v-if="form.errors.permissions" class="error-text mt-3">
                {{ form.errors.permissions }}
              </div>
            </div>
          </div>

        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.layout-mobile {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.layout-desktop {
  display: none;
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


@media (min-width: 768px) {
  .layout-mobile {
    display: none;
  }

  .layout-desktop {
    display: block;
  }
}

.form-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.formControl {
  height: 44px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
  width: 100%;
}

.formControl:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
  outline: none;
}

.selected-count {
  font-size: 13px;
  font-weight: 700;
  color: #2563eb;
  background: rgba(59, 130, 246, 0.10);
  padding: 5px 12px;
  border-radius: 999px;
  white-space: nowrap;
}

.permission-section+.permission-section {
  margin-top: 28px;
  padding-top: 28px;
  border-top: 1px solid rgba(59, 130, 246, 0.12);
}

.permission-section__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 14px;
}

.permission-section__title {
  font-weight: 700;
  color: #111827;
  margin: 0;
  font-size: 14px;
}

.permission-section__subtitle {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 2px;
}

.permission-grid {
  display: grid;
  gap: 10px;
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
  border-color: #2563eb;
  background: #fffaf0;
}

.permission-card--checked {
  border-color: rgba(59, 130, 246, 0.5);
  background: #fffaf0;
}

.permission-card input {
  width: 16px;
  height: 16px;
  accent-color: #2563eb;
  flex-shrink: 0;
}

.permission-card__label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  line-height: 1.3;
}

.empty-state {
  text-align: center;
  color: #6b7280;
  padding: 40px 0;
  font-style: italic;
}

.error-text {
  font-size: 12px;
  color: #dc2626;
}

.spinner-icon {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.helper-box {
  border: 1px dashed rgba(59, 130, 246, 0.3);
  background: #fffaf0;
  border-radius: 14px;
  padding: 16px;
}

.helper-box__title {
  font-size: 13px;
  font-weight: 700;
  color: #2563eb;
  margin-bottom: 6px;
}

.helper-box__text {
  color: #6b7280;
  font-size: 14px;
  line-height: 1.5;
}

.mobile-permission-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

@media (max-width: 640px) {
  .form-container {
    padding: 24px 16px;
  }

  .header-title {
    font-size: 24px;
  }

  .form-card {
    border-radius: 12px;
  }

  .card-header {
    padding: 20px 16px;
  }

  .mobile-permission-grid {
    grid-template-columns: 1fr;
  }
}


.card-top-bar {
  display: flex;
  align-items: flex-start;
  gap: 24px;
  padding: 28px 32px 24px;
  flex-wrap: wrap;
}

.role-name-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex: 1;
  flex-wrap: nowrap;
}

.role-name-group .formLabel {
  white-space: nowrap;
  padding-top: 10px;
  min-width: 90px;
  font-weight: 600;
  font-size: 14px;
  color: #374151;
  margin: 0;
}

.role-name-input-wrap {
  width: 280px;
  flex-shrink: 0;
}

.helper-hint {
  font-size: 13px;
  color: #9ca3af;
  padding-top: 10px;
  max-width: 320px;
  line-height: 1.5;
}

.permissions-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-top: 6px;
  margin-left: auto;
  flex-shrink: 0;
}

.section-divider {
  height: 1px;
  background: rgba(59, 130, 246, 0.12);
  margin: 0 32px;
}

.permissions-body {
  padding: 24px 32px 32px;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.desktop-permission-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}


@media (min-width: 768px) and (max-width: 1280px) {
  .desktop-permission-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 768px) and (max-width: 900px) {
  .desktop-permission-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .card-top-bar {
    padding: 20px 20px 16px;
  }

  .permissions-body {
    padding: 20px 20px 28px;
  }

  .section-divider {
    margin: 0 20px;
  }

  .role-name-input-wrap {
    width: 100%;
  }
}
</style>
