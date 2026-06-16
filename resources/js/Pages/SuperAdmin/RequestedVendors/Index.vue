<template>
  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Requested Vendors</h1>
            <p class="header-subtitle">Review partner registrations, documents, and approval details.</p>
          </div>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable
          ref="dtRef"
          :id="tableId"
          :url="datatableUrl"
          :columns="columns"
          :columnDefs="columnDefs"
          :order="[[5, 'desc']]"
          searchPlaceholder="Search shop name, admin, email"
        >
          <template #header>
            <tr>
              <th>Shop Name</th>
              <th>Admin Name</th>
              <th>Email</th>
              <th>Contact</th>
              <th>Status</th>
              <th>Created</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>
    </div>

    <div v-if="canvasOpen" class="vendor-canvas-backdrop" @click="closeCanvas"></div>
    <aside class="vendor-canvas" :class="{ 'vendor-canvas--open': canvasOpen }" aria-label="Requested vendor details">
      <div class="canvas-head">
        <div>
          <span class="canvas-kicker">Vendor Request</span>
          <h2>{{ selectedVendor?.name || 'Request Details' }}</h2>
        </div>
        <button type="button" class="canvas-close" @click="closeCanvas" aria-label="Close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div v-if="loadingView" class="canvas-loading">
        <div class="spinner-border spinner-border-sm text-warning"></div>
        <span>Loading request...</span>
      </div>

      <template v-else-if="selectedVendor">
        <div class="status-line">
          <span class="status-pill" :class="`status-pill--${selectedVendor.status}`">
            {{ statusLabel(selectedVendor.status) }}
          </span>
          <span>{{ selectedVendor.created_at || '-' }}</span>
        </div>

        <div class="media-strip">
          <div v-if="selectedVendor.business_logo_url" class="logo-preview">
            <img :src="selectedVendor.business_logo_url" alt="Business logo">
          </div>
          <div v-else class="logo-preview logo-preview--empty">
            <i class="bi bi-shop"></i>
          </div>
          <div>
            <h3>{{ selectedVendor.store_display_name || selectedVendor.name }}</h3>
            <p>{{ selectedVendor.legal_business_name || 'Legal business name not provided' }}</p>
          </div>
        </div>

        <section class="canvas-section">
          <h3>Business Details</h3>
          <div class="detail-grid">
            <div class="detail-box">
              <span>Legal Business Name</span>
              <strong>{{ selectedVendor.legal_business_name || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Store Display Name</span>
              <strong>{{ selectedVendor.store_display_name || selectedVendor.name }}</strong>
            </div>
            <div class="detail-box detail-box--wide">
              <span>Business Address</span>
              <strong>{{ selectedVendor.business_address || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Store Contact Number</span>
              <strong>{{ selectedVendor.phone }}</strong>
            </div>
            <div class="detail-box">
              <span>Business Email</span>
              <strong>{{ selectedVendor.business_email || selectedVendor.email }}</strong>
            </div>
            <div class="detail-box">
              <span>Owner / Manager</span>
              <strong>{{ selectedVendor.owner_name || selectedVendor.admin_name }}</strong>
            </div>
            <div class="detail-box">
              <span>Business Registration No.</span>
              <strong>{{ selectedVendor.business_registration_number || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>City</span>
              <strong>{{ selectedVendor.city || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Country</span>
              <strong>{{ selectedVendor.country || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Country Code</span>
              <strong>{{ selectedVendor.country_code || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Google Place ID</span>
              <strong>{{ selectedVendor.google_place_id || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Address Line 1</span>
              <strong>{{ selectedVendor.address_line_1 || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Address Line 2</span>
              <strong>{{ selectedVendor.address_line_2 || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>State / Province</span>
              <strong>{{ selectedVendor.state_province || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Postal Code</span>
              <strong>{{ selectedVendor.postal_code || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Opening Hours</span>
              <strong>{{ selectedVendor.opening_from || '-' }} - {{ selectedVendor.opening_to || '-' }}</strong>
            </div>
            <div class="detail-box">
              <span>Request Code</span>
              <strong>{{ selectedVendor.slug }}</strong>
            </div>
            <div class="detail-box">
              <span>Updated</span>
              <strong>{{ selectedVendor.updated_at || '-' }}</strong>
            </div>
          </div>
        </section>

        <section class="canvas-section">
          <h3>Food Type</h3>
          <div class="chip-list">
            <span v-for="type in selectedVendor.food_types || []" :key="type" class="info-chip">{{ type }}</span>
            <span v-if="!selectedVendor.food_types?.length" class="empty-text">No food types selected.</span>
          </div>
        </section>

        <section class="canvas-section">
          <h3>Service Options</h3>
          <div class="chip-list">
            <span v-for="option in selectedVendor.service_options || []" :key="option" class="info-chip">{{ option }}</span>
            <span v-if="!selectedVendor.service_options?.length" class="empty-text">No service options selected.</span>
          </div>
        </section>

        <section class="canvas-section">
          <h3>Business Images & Documents</h3>
          <div class="photo-grid">
            <a
              v-for="(url, index) in selectedVendor.business_photo_urls || []"
              :key="url"
              :href="url"
              target="_blank"
              class="photo-thumb"
            >
              <img :src="url" :alt="`Business photo ${index + 1}`">
            </a>
          </div>
          <p v-if="!selectedVendor.business_photo_urls?.length" class="empty-text">No business photos uploaded.</p>

          <a
            v-if="selectedVendor.business_license_url"
            :href="selectedVendor.business_license_url"
            target="_blank"
            class="document-link"
          >
            <i class="bi bi-file-earmark-check"></i>
            View business / food license
          </a>
        </section>

        <section v-if="selectedVendor.restaurant_page_image_urls && Object.keys(selectedVendor.restaurant_page_image_urls).length" class="canvas-section">
          <h3>Restaurant Page Images</h3>
          <div class="request-page-images">
            <a
              v-for="(url, key) in selectedVendor.restaurant_page_image_urls"
              :key="key"
              :href="url"
              target="_blank"
              class="request-page-image"
            >
              <img :src="url" :alt="formatImageSlot(key)">
              <span>{{ formatImageSlot(key) }}</span>
            </a>
          </div>
        </section>

        <div v-if="selectedVendor.reason" class="reason-panel">
          <span>Reason</span>
          <p>{{ selectedVendor.reason }}</p>
        </div>

        <div v-if="selectedVendor.status !== 'approved'" class="action-panel">
          <h3>Approve Vendor</h3>
          <div class="field">
            <label>Theme</label>
            <select v-model="approveForm.theme_id">
              <option value="">Select theme</option>
              <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                {{ theme.name }}
              </option>
            </select>
            <small v-if="approveErrors.theme_id">{{ approveErrors.theme_id }}</small>
          </div>

          <div class="field">
            <label>Domain Name</label>
            <input v-model="approveForm.domain_name" type="text" placeholder="restaurant.example.com">
            <small v-if="approveErrors.domain_name">{{ approveErrors.domain_name }}</small>
          </div>

          <div class="field">
            <label>Admin Password</label>
            <input v-model="approveForm.admin_password" type="text" placeholder="Set vendor admin password">
            <small v-if="approveErrors.admin_password">{{ approveErrors.admin_password }}</small>
          </div>

          <div class="field">
            <label>Subscription Plan</label>
            <select v-model="approveForm.vendor_subscription_plan_id">
              <option value="">Use default active plan</option>
              <option v-for="plan in subscriptionPlans" :key="plan.id" :value="plan.id">
                {{ plan.label || plan.name }}
              </option>
            </select>
            <small v-if="approveErrors.vendor_subscription_plan_id">{{ approveErrors.vendor_subscription_plan_id }}</small>
          </div>

          <div class="field">
            <label>Subscription Status</label>
            <select v-model="approveForm.vendor_subscription_status">
              <option v-for="status in subscriptionStatusOptions" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
            <small v-if="approveErrors.vendor_subscription_status">{{ approveErrors.vendor_subscription_status }}</small>
          </div>

          <label class="request-checkbox">
            <input v-model="approveForm.vendor_panel_enabled" type="checkbox">
            <span>Vendor panel access enabled</span>
          </label>

          <button type="button" class="approve-btn" :disabled="submittingAction" @click="approveVendor">
            <span v-if="submittingAction">Approving...</span>
            <span v-else>Approve Request</span>
          </button>
        </div>

        <div v-if="selectedVendor.status !== 'approved'" class="action-panel action-panel--reject">
          <h3>Reject Request</h3>
          <div class="field">
            <label>Reject Reason</label>
            <textarea v-model="rejectForm.reason" rows="4" placeholder="Optional reason"></textarea>
            <small v-if="rejectErrors.reason">{{ rejectErrors.reason }}</small>
          </div>

          <button type="button" class="reject-btn" :disabled="submittingAction" @click="rejectVendor">
            <span v-if="submittingAction">Rejecting...</span>
            <span v-else>Reject Request</span>
          </button>
        </div>
      </template>
    </aside>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'

defineOptions({ layout: SuperAdminLayout })

const props = defineProps({
  themes: { type: Array, default: () => [] },
  subscriptionPlans: { type: Array, default: () => [] },
})

const tableId = 'requestedVendorsTable'
const datatableUrl = computed(() => route('requested-vendors.getdata'))
const dtRef = ref(null)
const canvasOpen = ref(false)
const loadingView = ref(false)
const submittingAction = ref(false)
const selectedVendor = ref(null)
const approveErrors = ref({})
const rejectErrors = ref({})
const approveForm = ref({
  theme_id: '',
  domain_name: '',
  admin_password: '',
  vendor_subscription_plan_id: '',
  vendor_subscription_status: 'active',
  vendor_panel_enabled: true,
})
const rejectForm = ref({
  reason: '',
})

const subscriptionStatusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'trialing', label: 'Trialing' },
  { value: 'past_due', label: 'Past Due' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'inactive', label: 'Inactive' },
]

function statusLabel(status) {
  if (status === 'approved') return 'Approved'
  if (status === 'rejected') return 'Rejected'
  return 'Pending'
}

function statusBadge(status) {
  return `<span class="request-status request-status--${status || 'pending'}">${statusLabel(status)}</span>`
}

function formatImageSlot(value = '') {
  return String(value)
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

const columns = computed(() => ([
  { data: 'name', name: 'name' },
  { data: 'admin_name', name: 'admin_name' },
  { data: 'email', name: 'email' },
  { data: 'phone', name: 'phone' },
  {
    data: 'status',
    name: 'status',
    render: (data) => statusBadge(data),
  },
  { data: 'created_at', name: 'created_at' },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const id = row?.id ?? data
      const name = escapeHtml(row?.name ?? '')

      return `
        <div class="d-flex justify-content-end">
          <button type="button" class="btn-circle js-view-request" data-id="${id}" data-name="${name}" title="View">
            <i class="bi bi-eye-fill"></i>
          </button>
        </div>
      `
    },
  },
]))

const columnDefs = [
  { targets: -1, className: 'text-end align-middle', width: '100px' },
  { targets: '_all', className: 'align-middle' },
]

function resetForms(vendor = selectedVendor.value) {
  approveErrors.value = {}
  rejectErrors.value = {}
  approveForm.value = {
    theme_id: props.themes?.[0]?.id || '',
    domain_name: vendor?.slug ? `${vendor.slug}.com` : '',
    admin_password: '',
    vendor_subscription_plan_id: props.subscriptionPlans?.find((plan) => plan.is_default)?.id || props.subscriptionPlans?.[0]?.id || '',
    vendor_subscription_status: 'active',
    vendor_panel_enabled: true,
  }
  rejectForm.value = {
    reason: vendor?.reason || '',
  }
}

async function openCanvas(id) {
  if (!id) return
  canvasOpen.value = true
  loadingView.value = true
  selectedVendor.value = null

  try {
    const { data } = await axios.get(route('requested-vendors.show', id))
    selectedVendor.value = data
    resetForms(data)
  } finally {
    loadingView.value = false
  }
}

function closeCanvas() {
  if (submittingAction.value) return
  canvasOpen.value = false
}

function validationErrors(error) {
  const errors = error?.response?.data?.errors || {}
  return Object.fromEntries(Object.entries(errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]))
}

async function approveVendor() {
  if (!selectedVendor.value?.id) return

  submittingAction.value = true
  approveErrors.value = {}

  try {
    const { data } = await axios.post(route('requested-vendors.approve', selectedVendor.value.id), approveForm.value)
    selectedVendor.value = data.vendor
    dtRef.value?.reloadDatatable?.()
    resetForms(data.vendor)
  } catch (error) {
    approveErrors.value = validationErrors(error)
  } finally {
    submittingAction.value = false
  }
}

async function rejectVendor() {
  if (!selectedVendor.value?.id) return

  submittingAction.value = true
  rejectErrors.value = {}

  try {
    const { data } = await axios.post(route('requested-vendors.reject', selectedVendor.value.id), rejectForm.value)
    selectedVendor.value = data.vendor
    dtRef.value?.reloadDatatable?.()
    resetForms(data.vendor)
  } catch (error) {
    rejectErrors.value = validationErrors(error)
  } finally {
    submittingAction.value = false
  }
}

function bindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`

  $(document).on('click', `${selector} .js-view-request`, (event) => {
    const id = event.currentTarget?.dataset?.id
    if (id) openCanvas(id)
  })
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`
  $(document).off('click', `${selector} .js-view-request`)
}

onMounted(() => bindActions())
onUnmounted(() => unbindActions())
</script>

<style scoped>
.table-container-modern {
  padding: 0.5rem 1.5rem 1.5rem;
}

:deep(thead th) {
  background: transparent !important;
  color: var(--slate-400) !important;
  font-weight: 600 !important;
  font-size: 0.75rem !important;
  text-transform: uppercase !important;
  letter-spacing: 0.05em !important;
  border-bottom: 1px solid var(--slate-50) !important;
  padding: 1.25rem 1rem !important;
}

:deep(tbody td) {
  padding: 1.25rem 1rem !important;
  border-bottom: 1px solid var(--slate-50) !important;
}

:deep(.request-status) {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.45rem 0.7rem;
  font-size: 0.75rem;
  font-weight: 800;
  border: 1px solid transparent;
}

:deep(.request-status--pending) {
  background: var(--primary-soft);
  color: var(--primary);
  border-color: rgba(245, 124, 0, 0.22);
}

:deep(.request-status--rejected) {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
}

:deep(.request-status--approved) {
  background: #ecfdf5;
  color: #059669;
  border-color: #bbf7d0;
}

:deep(.btn-circle) {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--slate-200);
  background: #ffffff;
  color: var(--slate-600);
  transition: 0.2s;
  cursor: pointer;
}

:deep(.btn-circle:hover) {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-soft);
}

.vendor-canvas-backdrop {
  position: fixed;
  inset: 0;
  z-index: 80;
  background: rgba(15, 23, 42, 0.28);
}

.vendor-canvas {
  position: fixed;
  top: 0;
  right: 0;
  z-index: 90;
  width: min(980px, 96vw);
  height: 100vh;
  overflow-y: auto;
  background: #ffffff;
  box-shadow: -30px 0 70px rgba(15, 23, 42, 0.18);
  transform: translateX(104%);
  transition: transform 0.22s ease;
  padding: 1.5rem;
}

.vendor-canvas--open {
  transform: translateX(0);
}

.canvas-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.canvas-kicker {
  display: block;
  color: var(--primary);
  font-size: 0.75rem;
  font-weight: 900;
  text-transform: uppercase;
  margin-bottom: 0.35rem;
}

.canvas-head h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 900;
  color: var(--slate-900);
}

.canvas-close {
  width: 38px;
  height: 38px;
  border: 1px solid var(--slate-200);
  border-radius: 50%;
  background: #ffffff;
  color: var(--slate-600);
}

.canvas-close:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-soft);
}

.canvas-loading,
.status-line {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  color: var(--slate-600);
}

.status-line {
  justify-content: space-between;
  margin-bottom: 1rem;
}

.status-pill {
  display: inline-flex;
  border-radius: 999px;
  padding: 0.45rem 0.75rem;
  font-weight: 900;
  font-size: 0.75rem;
}

.status-pill--pending {
  background: var(--primary-soft);
  color: var(--primary);
}

.status-pill--rejected {
  background: #fef2f2;
  color: #dc2626;
}

.status-pill--approved {
  background: #ecfdf5;
  color: #059669;
}

.media-strip {
  display: grid;
  grid-template-columns: 68px 1fr;
  gap: 0.9rem;
  align-items: center;
  padding: 1rem;
  border: 1px solid var(--slate-100);
  border-radius: 8px;
  background: #ffffff;
  margin-bottom: 1rem;
}

.logo-preview {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  overflow: hidden;
  background: var(--primary-soft);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.logo-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-strip h3 {
  margin: 0 0 0.25rem;
  font-size: 1.05rem;
  font-weight: 900;
  color: var(--slate-900);
}

.media-strip p {
  margin: 0;
  color: var(--slate-500);
}

.canvas-section,
.reason-panel,
.action-panel {
  border: 1px solid var(--slate-100);
  border-radius: 8px;
  background: #ffffff;
  padding: 1rem;
  margin-top: 1rem;
}

.canvas-section h3,
.action-panel h3 {
  margin: 0 0 0.85rem;
  font-size: 1rem;
  font-weight: 900;
  color: var(--slate-900);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.detail-box {
  padding: 0.85rem;
  border: 1px solid var(--slate-100);
  border-radius: 8px;
  background: var(--slate-50);
}

.detail-box--wide {
  grid-column: 1 / -1;
}

.detail-box span,
.reason-panel span {
  display: block;
  color: var(--slate-500);
  font-size: 0.68rem;
  font-weight: 900;
  text-transform: uppercase;
  margin-bottom: 0.35rem;
}

.detail-box strong {
  color: var(--slate-900);
  font-size: 0.88rem;
  word-break: break-word;
  white-space: pre-wrap;
}

.chip-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.info-chip {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  border-radius: 999px;
  padding: 0.35rem 0.7rem;
  background: var(--primary-soft);
  color: var(--primary);
  font-size: 0.78rem;
  font-weight: 800;
}

.empty-text {
  margin: 0;
  color: var(--slate-500);
  font-size: 0.88rem;
}

.photo-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.5rem;
}

.photo-thumb {
  display: block;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--slate-100);
  background: var(--slate-50);
}

.photo-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.document-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.85rem;
  color: var(--primary);
  font-weight: 900;
  text-decoration: none;
}

.request-page-images {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.65rem;
}

.request-page-image {
  min-width: 0;
  overflow: hidden;
  border: 1px solid var(--slate-100);
  border-radius: 8px;
  background: var(--slate-50);
  color: var(--slate-900);
  text-decoration: none;
}

.request-page-image img {
  width: 100%;
  aspect-ratio: 1.45;
  object-fit: cover;
}

.request-page-image span {
  display: block;
  padding: 0.55rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 900;
}

.reason-panel {
  background: #fff7ed;
}

.reason-panel p {
  margin: 0;
  color: var(--slate-700);
  white-space: pre-wrap;
}

.action-panel {
  background: var(--slate-50);
}

.action-panel--reject {
  background: #fffafa;
}

.field {
  display: grid;
  gap: 0.45rem;
  margin-bottom: 0.8rem;
}

.field label {
  color: var(--slate-700);
  font-size: 0.82rem;
  font-weight: 800;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  border: 1px solid var(--slate-200);
  border-radius: 8px;
  padding: 0.75rem 0.8rem;
  background: #ffffff;
  outline: none;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.12);
}

.field small {
  color: #dc2626;
  font-weight: 700;
}

.request-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  color: var(--slate-700);
  font-size: 0.82rem;
  font-weight: 800;
  margin: 0.3rem 0 0.8rem;
}

.request-checkbox input {
  width: 16px;
  height: 16px;
  accent-color: var(--primary);
}

.approve-btn,
.reject-btn {
  width: 100%;
  min-height: 46px;
  border: 0;
  border-radius: 8px;
  color: #ffffff;
  font-weight: 900;
}

.approve-btn {
  background: var(--primary);
}

.reject-btn {
  background: #dc2626;
}

.approve-btn:disabled,
.reject-btn:disabled {
  opacity: 0.65;
}

@media (max-width: 640px) {
  .table-container-modern {
    padding: 0.5rem 1rem 1rem;
  }

  .vendor-canvas {
    padding: 1rem;
  }

  .detail-grid,
  .media-strip {
    grid-template-columns: 1fr;
  }

  .photo-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .request-page-images {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
