<template>

  <Head :title="title" />

  <div class="page-container">
    <div class="card-modern">
      <div class="card-modern-header">
        <div class="header-content">
          <div class="header-title-group">
            <h1 class="header-title">
              <i :class="icon" class="me-2 text-warning"></i>{{ title }}
            </h1>
            <p class="header-subtitle">{{ subtitle }}</p>
          </div>

          <button v-if="createRoute && can(`${props.permission}.create`)" type="button" class="btn-primary-modern"
            @click="router.visit(route(createRoute))">
            <i class="bi bi-plus-lg"></i>
            <span>{{ createLabel }}</span>
          </button>
        </div>
      </div>

      <div class="table-container-modern">
        <DataTable ref="dtRef" :id="tableId" :url="route(dataRoute)" :columns="finalColumns" :columnDefs="columnDefs"
          searchPlaceholder="Search here..." :order="order">
          <template #header>
            <tr>
              <th v-for="column in headers" :key="column.label" :class="column.class || ''" :style="column.style || ''">
                {{ column.label }}
              </th>
            </tr>
          </template>
        </DataTable>
      </div>
    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" :title="deleteModalTitle" :cancel-label="deleteModalCancelLabel"
    :confirm-label="deleteModalConfirmLabel" @confirm="confirmDelete" @closed="onModalClosed" />
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import DataTable from '@/Components/Datatable.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from '@/composables/usePermission'
import { actionsColumn } from '../Shared/helpers'

const { can } = usePermission()

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: String, default: 'bi bi-gift' },
  permission: { type: String, default: '' },
  tableId: { type: String, required: true },
  dataRoute: { type: String, required: true },
  createRoute: { type: String, default: '' },
  createLabel: { type: String, default: 'Create' },
  editRoute: { type: String, default: '' },
  destroyRoute: { type: String, default: '' },
  headers: { type: Array, required: true },
  columns: { type: Array, required: true },
  order: { type: Array, default: () => [[1, 'asc']] },

  deleteModalTitle: { type: String, default: 'Delete this item?' },
  deleteModalCancelLabel: { type: String, default: 'Keep Item' },
  deleteModalConfirmLabel: { type: String, default: 'Delete Item' },
})

const dtRef = ref(null)

const columnDefs = [
  { targets: -1, className: 'text-end align-middle', width: '110px' },
  { targets: '_all', className: 'align-middle' },
]

const finalColumns = computed(() => [
  ...props.columns,
  actionsColumn(can, props.permission),
])

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

function openDeleteModal(id, name = '') {
  deleteTarget.value = { id, name }
  showDeleteModal.value = true
}

function resetModal() {
  showDeleteModal.value = false
  deleteTarget.value = { id: null, name: '' }
  deleting.value = false
}

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id || !props.destroyRoute) return

  deleting.value = true

  router.delete(route(props.destroyRoute, id), {
    preserveScroll: true,
    onSuccess: () => {
      resetModal()
      setTimeout(() => {
        dtRef.value?.reloadDatatable?.()
      }, 300)
    },
    onError: () => {
      deleting.value = false
    },
  })
}

function onModalClosed() { }

function bindActions() {
  const $ = window.jQuery
  if (!$) return

  const selector = `#${props.tableId}`

  $(document).on('click', `${selector} .js-edit`, (event) => {
    if (!can(`${props.permission}.edit`)) return
    const id = event.currentTarget?.dataset?.id
    if (id && props.editRoute) {
      router.visit(route(props.editRoute, id))
    }
  })

  $(document).on('click', `${selector} .js-delete`, (event) => {
    if (!can(`${props.permission}.delete`)) return
    const id = event.currentTarget?.dataset?.id
    const label = event.currentTarget?.dataset?.label || ''
    if (!id || !props.destroyRoute) return
    openDeleteModal(id, label)
  })
}

function unbindActions() {
  const $ = window.jQuery
  if (!$) return
  const selector = `#${props.tableId}`
  $(document).off('click', `${selector} .js-edit`)
  $(document).off('click', `${selector} .js-delete`)
}

onMounted(bindActions)
onUnmounted(unbindActions)
</script>

<style scoped>
.table-container-modern {
  padding: 0.5rem 1.5rem 1.5rem;
}

:deep(thead th) {
  background: transparent !important;
  color: var(--slate-400) !important;
  font-weight: 700 !important;
  font-size: 0.75rem !important;
  text-transform: uppercase !important;
  border-bottom: 1px solid var(--slate-50) !important;
  padding: 1rem !important;
}

:deep(tbody td) {
  padding: 1rem !important;
  border-bottom: 1px solid var(--slate-50) !important;
}

:deep(.btn-row) {
  display: inline-flex;
  justify-content: flex-end;
  gap: 8px;
}

:deep(.btn-circle) {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid #dbe3ec;
  background: #fff;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

:deep(.btn-circle:hover) {
  border-color: #3b82f6;
  color: #3b82f6;
  background: #eff6ff;
}

:deep(.btn-circle-danger:hover) {
  border-color: #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

:deep(.loyalty-name-cell) {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  min-width: 180px;
}

:deep(.loyalty-table-avatar) {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 12px;
  overflow: hidden;
  background: #eff6ff;
  color: #3b82f6;
  border: 1px solid rgba(249, 115, 22, 0.22);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.82rem;
}

:deep(.loyalty-table-avatar img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
</style>
