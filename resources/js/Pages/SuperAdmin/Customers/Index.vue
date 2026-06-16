<template>
  <div class="container-fluid page-wrap">
    <div class="card border-0">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            <h4 class="card-title mb-1 fw-semibold">Customers</h4>
            <p class="text-muted mb-0">Manage customer accounts, status, and removal.</p>
          </div>
        </div>
      </div>

      <DataTable
        ref="dtRef"
        :id="tableId"
        :url="datatableUrl"
        :columns="columns"
        :columnDefs="columnDefs"
        :order="[[3, 'desc']]"
        searchPlaceholder="Search name or email"
      >
        <template #header>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Created</th>
            <th class="text-end">Actions</th>
          </tr>
        </template>
      </DataTable>

<ViewOffcanvas
  :open="viewOpen"
  :customer="viewCustomer"
  :loading="viewLoading"
  @close="closeViewOffcanvas"
  @subscription-updated="handleSubscriptionUpdated"
/>
      <!-- Delete Modal -->
      <div class="modal fade" tabindex="-1" ref="deleteModalEl" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
          <div class="modal-content delete-modal">
            <div class="modal-header border-0 pb-0">
              <div class="d-flex align-items-center gap-3">
                <div class="danger-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path
                      d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"
                    />
                  </svg>
                </div>

                <div>
                  <h5 class="modal-title mb-1">Delete customer?</h5>
                  <div class="text-muted small">This action cannot be undone.</div>
                </div>
              </div>

              <button type="button" class="btn-close" @click="closeDeleteModal" :disabled="deleting"></button>
            </div>

            <div class="modal-body pt-3">
              <div class="p-3 rounded-3 info-box">
                <div class="fw-semibold text-dark mb-1">You are about to delete:</div>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <div class="text-dark">
                    <span class="badge rounded-pill me-2">Customer</span>
                    <span class="fw-semibold">{{ deleteTarget.name || 'Selected record' }}</span>
                  </div>
                  <span class="text-muted small">ID: {{ deleteTarget.id || '-' }}</span>
                </div>
              </div>
            </div>

            <div class="modal-footer border-0 pt-0">
              <button type="button" class="btn btn-light btn-pill" @click="closeDeleteModal" :disabled="deleting">
                Cancel
              </button>

              <button
                type="button"
                class="btn btn-danger btn-pill px-4"
                @click="confirmDelete"
                :disabled="deleting || !deleteTarget.id"
              >
                <span v-if="!deleting">Delete</span>
                <span v-else class="d-inline-flex align-items-center gap-2">
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  Deleting...
                </span>
              </button>
            </div>
          </div>

          
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import ViewOffcanvas from '@/Pages/SuperAdmin/Customers/ViewOffcanvas.vue'

defineOptions({ layout: SuperAdminLayout })

const tableId = 'customersTable'
const datatableUrl = computed(() => route('customers.getdata'))
const dtRef = ref(null)
const viewOpen = ref(false)
const viewCustomer = ref(null)
const viewLoading = ref(false)

/* Delete modal state */
const deleteModalEl = ref(null)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)
let bsDeleteModal = null

function reloadTable() {
  dtRef.value?.reloadDatatable?.()
}

async function openViewOffcanvas(customer) {
  viewOpen.value = true
  viewLoading.value = true

 viewCustomer.value = {
  id: customer?.id || null,
  name: customer?.name || '',
  email: customer?.email || '',
  status: customer?.status || 'active',
  created_at: customer?.created_at || '',
  vehicles: [],
  default_subscribed_plan_id: null,
  subscribed_plan_id: null,
  default_subscription_plan: null,
  subscribed_subscription_plan: null,
  effective_subscription_plan: null,
  available_subscription_plans: [],
}

  try {
    const response = await fetch(route('customers.view', customer.id), {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    const data = await response.json()

    if (data?.customer) {
      viewCustomer.value = data.customer
    }
  } catch (error) {
    console.error('Failed to load customer view data', error)
  } finally {
    viewLoading.value = false
  }
}

function closeViewOffcanvas() {
  viewOpen.value = false
  setTimeout(() => {
    viewCustomer.value = null
    viewLoading.value = false
  }, 250)
}

function handleSubscriptionUpdated(updatedCustomer) {
  viewCustomer.value = updatedCustomer
}

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  bsDeleteModal?.show()
}

function closeDeleteModal() {
  if (deleting.value) return
  bsDeleteModal?.hide()
}

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return

  deleting.value = true

  router.delete(route('customers.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      bsDeleteModal?.hide()

      const el = deleteModalEl.value
      if (el) {
        const handler = () => {
          el.removeEventListener('hidden.bs.modal', handler)
          deleteTarget.value = { id: null, name: '' }
          reloadTable()
        }
        el.addEventListener('hidden.bs.modal', handler)
      } else {
        deleteTarget.value = { id: null, name: '' }
        reloadTable()
      }
    },
    onFinish: () => {
      deleting.value = false
    },
  })
}

function toggleStatus(id) {
  router.patch(route('customers.toggleStatus', id), {}, {
    preserveScroll: true,
    onSuccess: () => reloadTable(),
  })
}

/* Datatable columns */
const columns = computed(() => ([
  { data: 'name', name: 'name' },
  { data: 'email', name: 'email' },
  {
    data: 'status',
    name: 'status',
    orderable: false,
    render: (data) => {
      const s = String(data || 'active').toLowerCase()
      const cls = s === 'active' ? 'badge btn-active' : 'badge bg-secondary'
      const label = s === 'active' ? 'Active' : 'Inactive'
      return `<span class="${cls}">${label}</span>`
    },
  },
  { data: 'created_at', name: 'created_at' },
  {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const id = row?.id ?? data
  const name = String(row?.name ?? '').replaceAll('"', '&quot;')
  const email = String(row?.email ?? '').replaceAll('"', '&quot;')
  const status = String(row?.status ?? 'active').replaceAll('"', '&quot;')
  const created = String(row?.created_at ?? '').replaceAll('"', '&quot;')
      const toggleLabel = status === 'active' ? 'Deactivate' : 'Activate'

      return `
        <div class="d-inline-flex gap-2 justify-content-end">

              <button
        type="button"
        class="btn btn-sm btn-icon js-view"
        data-id="${id}"
        data-name="${name}"
        data-email="${email}"
        data-status="${status}"
        data-created="${created}"
        title="View"
      >
        <i class="bi bi-eye"></i>
      </button>
          <!--<button
            type="button"
            class="btn btn-sm btn-icon js-toggle"
            data-id="${id}"
            title="${toggleLabel}"
          >
            <i class="bi ${status === 'active' ? 'bi-pause-circle' : 'bi-play-circle'}"></i>
          </button>-->

          <button
            type="button"
            class="btn btn-sm btn-icon btn-icon-danger js-delete"
            data-id="${id}"
            data-name="${name}"
            title="Delete"
          >
            <i class="bi bi-trash"></i>
          </button>
        </div>
      `
    },
  },
]))

const columnDefs = [{ targets: -1, className: 'text-end', width: '220px' }]

/* Bind action buttons (jQuery datatable buttons) */
function bindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`

 $(document).on('click', `${selector} .js-view`, (e) => {
  const el = e.currentTarget
  openViewOffcanvas({
    id: el?.dataset?.id || null,
    name: el?.dataset?.name || '',
    email: el?.dataset?.email || '',
    status: el?.dataset?.status || 'active',
    created_at: el?.dataset?.created || '',
  })
})

$(document).on('click', `${selector} .js-delete`, (e) => {
  const id = e.currentTarget?.dataset?.id
  const name = e.currentTarget?.dataset?.name || ''
  if (id) openDeleteModal(id, name)
})
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${tableId}`
 $(document).off('click', `${selector} .js-view`)
$(document).off('click', `${selector} .js-delete`)
}

onMounted(() => {
  const Modal = window.bootstrap?.Modal
  if (Modal && deleteModalEl.value) {
    bsDeleteModal = new Modal(deleteModalEl.value, { backdrop: 'static', keyboard: false })
  }
  bindActions()
})

onUnmounted(() => {
  unbindActions()
  try { bsDeleteModal?.dispose?.() } catch {}
  bsDeleteModal = null
})
</script>

<style scoped>
.page-wrap {
  background: #ffffff;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
}

:deep(.btn-icon) {
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: #fff;
  padding: 8px 10px;
  line-height: 1;
}

:deep(.btn-icon:hover) {
  background: rgba(51, 46, 120, 0.05);
  transform: translateY(-1px);
  transition: all 0.15s ease;
}

:deep(.btn-icon-danger:hover) {
  background: rgba(220, 38, 38, 0.06);
  border-color: rgba(220, 38, 38, 0.2);
}

:deep(.badge.btn-active) {
  background: linear-gradient(135deg, #4CAF50, #45a049) !important;
  border-color: #4CAF50 !important;
  color: white !important;
}

/* Delete modal styles (same vibe as vendors) */
.btn-pill { border-radius: 9999px; }

.delete-modal {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.18);
}

.danger-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(220, 38, 38, 0.10);
  color: #dc2626;
  border: 1px solid rgba(220, 38, 38, 0.18);
}

.info-box {
  background: rgba(2, 6, 23, 0.03);
  border: 1px solid rgba(2, 6, 23, 0.08);
}

.info-box .badge {
  background: rgba(51, 46, 120, 0.08);
  border: 1px solid rgba(51, 46, 120, 0.14);
  color: #332e78;
}

@media (max-width:768px) {
  :deep(.text-end) { text-align: left !important; }
}

:deep(.btn-icon:hover) {
  background: rgba(51, 46, 120, 0.05);
  transform: translateY(-1px);
  transition: all 0.15s ease;
}
</style>