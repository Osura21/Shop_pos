<template>

  <Head title="Branches" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">Branches</h1>
            <p class="header-subtitle">Centralized management for your retail locations and POS nodes.</p>
          </div>
          <button v-if="can('branches.create')" type="button" class="btn-primary-modern" @click="goCreate">
            <i class="bi bi-plus" />
            <span class="d-inline-flex align-items-center gap-1 text-nowrap">
              Create <span class="create-text">Branch</span>
            </span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
          :order="[[5, 'desc']]" searchPlaceholder="Search by name, email or city...">
          <template #header>
            <tr>
              <th>Branch Details</th>
              <th>Contact Info</th>
              <th>Regional Details</th>
              <th>Map Location</th>
              <th>Status</th>
              <th>Created</th>
              <th>Updated</th>
              <th class="text-end">Actions</th>
            </tr>
          </template>
        </DataTable>
      </div>

      <Teleport to="body">
      <Transition name="fade">
        <div v-if="preview.visible" class="preview-backdrop" @click="closePreview"></div>
      </Transition>

      <Transition name="slide-up">
        <div v-if="preview.visible" ref="previewRef" class="preview-card-modern" :style="previewStyle">
          <div class="preview-card-inner">
            <div class="preview-header">
              <div class="preview-icon-box" :class="preview.type">
                <i v-if="preview.type === 'contact'" class="bi bi-person-badge"></i>
                <i v-else-if="preview.type === 'map'" class="bi bi-geo-alt"></i>
                <i v-else class="bi bi-globe"></i>
              </div>
              <div class="ms-3">
                <div class="preview-eyebrow">{{ preview.type }} info</div>
                <div class="preview-title">{{ preview.row?.name }}</div>
              </div>
              <button type="button" class="btn-close-minimal preview-close-btn ms-auto" @click="closePreview">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="preview-body">
              <div v-if="preview.type === 'map'">
                <div class="preview-data-row">
                  <span class="p-label">Address</span>
                  <span class="p-value">{{ preview.row?.full_address || 'N/A' }}</span>
                </div>
                <div class="preview-data-row mt-2">
                  <span class="p-label">Coords</span>
                  <span class="p-value font-monospace">{{ preview.row?.latitude }}, {{ preview.row?.longitude }}</span>
                </div>
                <a :href="previewGoogleMapsUrl" target="_blank" class="btn-preview-action mt-3">
                  <span>View in Google Maps</span>
                  <i class="bi bi-arrow-up-right"></i>
                </a>
              </div>

              <div v-else-if="preview.type === 'contact'">
                <div class="preview-data-row">
                  <span class="p-label">Phone</span>
                  <span class="p-value">{{ preview.row?.phone || '-' }}</span>
                </div>
                <div class="preview-data-row mt-2">
                  <span class="p-label">Email</span>
                  <span class="p-value text-break">{{ preview.row?.email || '-' }}</span>
                </div>
              </div>

              <div v-else-if="preview.type === 'regional'">
                <div class="preview-data-row">
                  <span class="p-label">Currency / Timezone</span>
                  <span class="p-value">{{ preview.row?.currency }} ({{ preview.row?.timezone }})</span>
                </div>
                <div class="preview-data-row mt-2">
                  <span class="p-label">Country</span>
                  <span class="p-value">{{ preview.row?.country || '-' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
      </Teleport>

    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this branch?" cancel-label="Keep Branch" confirm-label="Delete Branch"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from "@/composables/usePermission";
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

defineOptions({ layout: VendorAdminLayout })

const { can } = usePermission()
const tableId = 'branchTable'
const page = usePage()
const dtRef = ref(null)
const previewRef = ref(null)
const rowCache = new Map()

const datatableUrl = computed(() => route('vendor.branches.getdata'))

const preview = ref({ visible: false, type: '', row: null, x: 0, y: 0 })

const previewStyle = computed(() => ({
  left: `${preview.value.x}px`,
  top: `${preview.value.y}px`,
}))

const previewGoogleMapsUrl = computed(() => {
  if (!preview.value.row) return '#'
  const query = mapQuery(preview.value.row)
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`
})

function cacheRow(row, data) {
  const id = String(row?.id ?? data)
  rowCache.set(id, { ...row })
  return id
}

function mapQuery(row) {
  if (row?.latitude && row?.longitude) return `${row.latitude},${row.longitude}`
  return row?.full_address || [row?.address_line_1, row?.city, row?.country].filter(Boolean).join(', ')
}

const columns = computed(() => ([
  {
    data: 'name',
    name: 'name',
    render: (data, type, row) => `
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold text-dark mb-0">
            ${data}
          </div>
          <div class="text-muted x-small">
            BR - ${row.id}
          </div>
        </div>
      </div>
    `
  },
  {
    data: 'contact_summary',
    render: (data, type, row) => {
      const id = cacheRow(row, row?.id)
      return `
        <button type="button" class="row-data-box js-open-preview" data-preview-type="contact" data-id="${id}">
          <i class="bi bi-envelope"></i>
          <span> Details</span>
        </button>
      `
    }
  },
  {
    data: 'regional_summary',
    render: (data, type, row) => {
      const id = cacheRow(row, row?.id)
      return `
        <button type="button" class="row-data-box js-open-preview" data-preview-type="regional" data-id="${id}">
          <i class="bi bi-globe"></i>
          <span>${row?.currency || 'Regional'}</span>
        </button>
      `
    }
  },
  {
    data: 'full_address',
    render: (data, type, row) => {
      const id = cacheRow(row, row?.id)
      return `
        <button type="button" class="row-data-box highlight js-open-preview" data-preview-type="map" data-id="${id}">
          <i class="bi bi-geo-alt"></i>
          <span>Location</span>
        </button>
      `
    }
  },
  {
    data: 'status',
    render: (data) => {
      const active = data.toLowerCase().includes('active')
      return `<span class="modern-badge ${active ? 'active' : 'inactive'}">${data}</span>`
    }
  },
  { data: 'created_at', render: d => `<span class="text-secondary small">${d}</span>` },
  { data: 'updated_at', render: d => `<span class="text-secondary small">${d}</span>` },
  {
    data: 'id',
    orderable: false,
    render: (data, type, row) => {
      const id = cacheRow(row, data)
      const name = String(row?.name ?? '').replace(/"/g, '&quot;')

      return `
  <div class="d-flex gap-2 justify-content-end">
    ${can('branches.edit')
          ? `<button type="button" class="btn-circle js-edit" data-id="${id}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
          : ''
        }

    ${can('branches.delete')
          ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${id}" data-name="${name}">
            <i class="bi bi-trash3-fill"></i>
          </button>`
          : ''
        }
  </div>
`
    }
  }
]))

const columnDefs = [{ targets: -1, width: '100px' }, { targets: '_all', className: 'align-middle' }]

function goCreate() { router.visit(route('vendor.branches.create')) }
function goEdit(id) { router.visit(route('vendor.branches.edit', id)) }

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.branches.destroy', id), {
    onSuccess: () => {
      showDeleteModal.value = false
      setTimeout(() => {
        dtRef.value?.reloadDatatable?.()
      }, 300)
    },
    onError: () => {
      deleting.value = false
    },
    onFinish: () => deleting.value = false
  })
}

function toastHtml(message) {
  const lines = String(message || "").split(/\r?\n/).filter(Boolean);
  const escape = (value) => String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");

  if (lines.length <= 1) {
    return `<div class="kitchen-toast-message"><strong>${escape(lines[0] || message)}</strong></div>`;
  }

  const [title, ...details] = lines;

  return `
    <div class="kitchen-toast-message">
      <strong>${escape(title)}</strong>
      ${details.map((line) => `<span>${escape(line)}</span>`).join("")}
    </div>
  `;
}

function openPreview(type, row, el) {
  const rect = el.getBoundingClientRect()
  
  // Ensure x doesn't go off screen horizontally
  let calcX = rect.left + window.scrollX - 120
  if (calcX < 16) calcX = 16
  if (calcX + 300 > window.innerWidth + window.scrollX) calcX = window.innerWidth + window.scrollX - 316
  
  // Ensure y correctly maps to the document by adding scrollY
  let calcY = rect.bottom + window.scrollY + 12
  
  preview.value = { visible: true, type, row, x: calcX, y: calcY }
}

function closePreview() { preview.value.visible = false }

onMounted(() => {
  const $ = window.jQuery
  $(document).on('click', `#${tableId} .js-edit`, (e) => goEdit(e.currentTarget.dataset.id))
  $(document).on('click', `#${tableId} .js-delete`, (e) => openDeleteModal(e.currentTarget.dataset.id, e.currentTarget.dataset.name))
  $(document).on('click', `#${tableId} .js-open-preview`, (e) => {
    const row = rowCache.get(e.currentTarget.dataset.id)
    if (row) openPreview(e.currentTarget.dataset.previewType, row, e.currentTarget)
  })
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.js-open-preview') && !e.target.closest('.preview-card-modern')) closePreview()
  }, true)
})

onUnmounted(() => {
  const $ = window.jQuery
  if (!$) return
  $(document).off('click', `#${tableId} .js-edit`)
  $(document).off('click', `#${tableId} .js-delete`)
  $(document).off('click', `#${tableId} .js-open-preview`)
})

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) {
      alertSuccess(toastHtml(flash.message))
    }

    if (flash?.error) {
      alertError(toastHtml(flash.error))
    }
  },
  { immediate: true }
)
</script>

<style scoped>
/* Table Styling */
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

:deep(.row-data-box) {
  background: transparent;
  border: 1px solid var(--slate-200);
  color: var(--slate-600);
  padding: 0.45rem 0.85rem;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.15s ease;
  cursor: pointer;
}

:deep(.row-data-box:hover) {
  border-color: var(--primary);
  color: var(--primary);
  background: white;
}

:deep(.row-data-box.highlight) {
  background: #ecfdf5;
  border-color: transparent;
  color: #528172;
}

:deep(.row-data-box.highlight:hover) {
  background: #ffffff;
  color: #059669;
  border-color: #059669;
}

/* Badges */
.modern-badge {
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
}

.modern-badge.active {
  background: #ecfdf5;
  color: #059669;
}

.modern-badge.inactive {
  background: #f1f5f9;
  color: #64748b;
}

/* Circle Actions */
:deep(.btn-circle) {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--slate-200);
  background: white;
  color: var(--slate-600);
  transition: 0.2s;
}

:deep(.btn-circle:hover) {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-soft);
}

:deep(.btn-circle-danger:hover) {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

/* Preview Card */
.preview-card-modern {
  position: absolute;
  z-index: 1050;
  width: 300px;
  filter: drop-shadow(0 20px 25px rgba(0, 0, 0, 0.15));
}

.preview-card-inner {
  background: white;
  border-radius: 16px;
  border: 1px solid var(--slate-200);
  overflow: hidden;
}

.preview-header {
  padding: 1rem;
  display: flex;
  align-items: center;
  border-bottom: 1px solid var(--slate-50);
}

.preview-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.preview-icon-box.contact {
  background: #eff6ff;
  color: #3b82f6;
}

.preview-icon-box.map {
  background: #fef2f2;
  color: #ef4444;
}

.preview-icon-box.regional {
  background: #fdf2f8;
  color: #db2777;
}

.preview-eyebrow {
  font-size: 0.65rem;
  text-transform: uppercase;
  color: var(--slate-400);
  font-weight: 700;
}

.preview-title {
  font-weight: 700;
  color: var(--slate-900);
  font-size: 0.95rem;
}

.preview-body {
  padding: 1rem;
}

.p-label {
  display: block;
  font-size: 0.7rem;
  color: var(--slate-400);
  font-weight: 600;
  margin-bottom: 2px;
}

.p-value {
  font-size: 0.85rem;
  color: var(--slate-600);
  font-weight: 500;
}

.btn-preview-action {
  width: 100%;
  padding: 0.6rem;
  border-radius: 8px;
  background: var(--slate-900);
  color: white;
  text-decoration: none;
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Transitions */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.preview-backdrop {
  display: none;
}

@media (max-width: 768px) {
  .preview-backdrop {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 1040;
  }

  .preview-card-modern {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    width: calc(100% - 32px) !important;
    max-width: 400px;
    z-index: 1050;
  }

  .slide-up-enter-from,
  .slide-up-leave-to {
    opacity: 0;
    transform: translate(-50%, -45%) !important;
  }
}

/* Avatar initials */
.avatar-initials {
  width: 40px;
  height: 40px;
  background: var(--primary-soft);
  color: var(--primary);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
}

.preview-close-btn {
  width: 38px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(15, 23, 42, 0.08);
  color: #0f172a;
  cursor: pointer;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
  position: relative;
  overflow: hidden;
}

.preview-close-btn:hover {
  transform: translateY(-2px);
  background: rgba(255, 255, 255, 0.95);
  color: #ef4444;
}

.preview-close-btn i {
  font-size: 14px;
  transition: transform 0.2s ease;
}

.preview-close-btn:hover i {
  transform: rotate(90deg);
}
</style>