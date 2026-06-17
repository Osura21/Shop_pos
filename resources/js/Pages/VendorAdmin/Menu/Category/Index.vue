<template>

  <Head title="Categories" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-folder2-open me-2 text-warning"></i>
              Categories
            </h1>
            <p class="header-subtitle">
              Create and manage product category trees for the shop POS.
            </p>
          </div>
        </div>
      </div>

      <div v-if="form.errors.general" class="alert alert-danger">
        {{ form.errors.general }}
      </div>

      <div class="category-layout">
        <!-- Tree Section -->
        <section class="form-card category-tree-card">
          <div class="category-tree-card__top">
            <div class="category-tree-card__search">
              <i class="bi bi-search"></i>
              <input v-model="searchQuery" type="text" class="form-control formControl"
                placeholder="Search categories...">
            </div>
            <div class="category-tree-card__actions">
              <button v-if="can('categories.create')" type="button" class="btn btn-root" @click="startCreateRoot">
                Root Category
              </button>
              <button v-if="can('categories.create')" type="button" class="btn btn-sub"
                :disabled="!selectedCategory || isCreateMode" @click="startCreateSub">
                Sub Category
              </button>
            </div>
          </div>

          <div class="category-tree-card__meta">
            <button type="button" class="tree-link" @click="expandAll">Expand All</button>
            <span>|</span>
            <button type="button" class="tree-link" @click="collapseAll">Collapse All</button>
          </div>

          <div class="category-tree-card__body">
            <div class="tree-scroll">
              <div v-if="!filteredTree.length" class="tree-empty">
                <i class="bi bi-search"></i>
                <div>{{ searchQuery ? 'No matching categories found.' : 'No categories yet.' }}</div>
              </div>

              <CategoryTreeNode v-for="node in filteredTree" :key="node.id" :node="node"
                :selected-id="selectedCategoryId" :expanded-ids="expandedIds" @select="handleSelect"
                @toggle="toggleExpand" />
            </div>
          </div>
        </section>

        <!-- Editor Section -->
        <section class="form-card category-editor-card">
          <div class="category-editor-card__header">
            <div>
              <h4 class="category-editor-card__title">{{ panelTitle }}</h4>
              <p class="category-editor-card__subtitle">{{ panelSubtitle }}</p>
            </div>

            <div class="category-editor-card__actions">
              <button v-if="isEditMode && can('categories.delete')" type="button" class="btn btn-delete"
                @click="openDeleteModal">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>

              <button v-if="isEditMode ? can('categories.edit') : can('categories.create')" type="button"
                class="btn btn-primary-modern" :disabled="form.processing || !currentMenuId" @click="submit">
                <i class="bi bi-check2 me-2"></i>
                {{ isEditMode ? 'Update' : 'Create' }}
              </button>
            </div>
          </div>

          <div class="category-editor-card__body">
            <div class="row g-4">

              <!-- LEFT SIDE -->
              <div class="col-12 col-lg-6 d-flex flex-column gap-3">

                <div>
                  <label class="form-label formLabel">Name (English)</label>
                  <input v-model="form.name" type="text" class="form-control formControl" placeholder="Category name">
                  <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                </div>

                <div>
                  <label class="form-label formLabel">Slug</label>
                  <input v-model="form.slug" type="text" class="form-control formControl" placeholder="category-slug"
                    @input="slugTouched = true">
                  <div v-if="form.errors.slug" class="error-text">{{ form.errors.slug }}</div>
                </div>

                <div>
                  <label class="checkbox-wrap">
                    <input v-model="form.is_active" type="checkbox">
                    <span class="checkbox-wrap__box">
                      <i class="bi bi-check"></i>
                    </span>
                    <span class="checkbox-wrap__label">Active</span>
                  </label>
                </div>
              </div>

              <!-- RIGHT SIDE -->
              <div class="col-12 col-lg-6 d-flex flex-column gap-3">
                <div>
                  <label class="formLabel">Category Logo</label>

                  <div class="media-box" @click="openFilePicker">
                    <div class="d-flex flex-column text-center">
                      <div class="media-box__inner">
                        <template v-if="logoPreview" class="d-flex flex-column">
                          <img :src="logoPreview" alt="Product image" class="media-box__image">
                        </template>
                        <template v-else class="d-flex flex-column">
                          <i class="bi bi-images media-box__icon"></i>
                        </template>
                      </div>
                      <div class="fw-semibold mt-2">Upload Logo</div>
                      <div class="small text-muted mt-1 text-center">JPG or PNG (max 5MB)</div>
                    </div>
                  </div>

                  <input ref="fileInput" type="file" class="d-none" accept=".jpg,.jpeg,.png,.webp"
                    @change="onLogoChange">

                  <div class="d-flex gap-2 mt-3">
                    <button v-if="logoPreview" type="button" class="btn btn-outline-danger btn-sm" @click="removeLogo">
                      Remove
                    </button>
                  </div>

                  <div v-if="form.errors.logo" class="error-text mt-2">
                    {{ form.errors.logo }}
                  </div>
                </div>
              </div>

              <div class="d-flex flex-column gap-3">
                <div class="info-panel">
                  <div class="info-panel__row">
                    <span class="info-panel__label">Parent Category</span>
                    <span class="info-panel__value">{{ parentCategoryName }}</span>
                  </div>

                  <div class="info-panel__row">
                    <span class="info-panel__label">Sort Order</span>
                    <input v-model="form.sort_order" type="number" min="0" class="form-control mt-2 formControl"
                      placeholder="0">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <DeleteModal v-model:show="showDeleteModal" :target-id="deleteTarget.id" :target-name="deleteTarget.name"
    :loading="deleting" title="Delete this category?" cancel-label="Keep Category" confirm-label="Delete Category"
    @confirm="confirmDelete" @closed="onModalClosed" />

</template>

<script setup>
import { computed, onMounted, watch, onUnmounted, ref } from 'vue'
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import CategoryTreeNode from './CategoryTreeNode.vue'
import DeleteModal from '@/Components/DeleteModal.vue'
import { usePermission } from "@/composables/usePermission";

const { can } = usePermission()

defineOptions({ layout: VendorAdminLayout })

const page = usePage()

const props = defineProps({
  currentMenuId: { type: [Number, String, null], default: null },
  tree: { type: Array, default: () => [] },
  selectedCategoryId: { type: [Number, String, null], default: null },
})

const language = ref('en')
const currentMenuId = ref(props.currentMenuId ? Number(props.currentMenuId) : '')
const selectedCategoryId = ref(props.selectedCategoryId ? Number(props.selectedCategoryId) : null)
const searchQuery = ref('')
const slugTouched = ref(false)
const fileInput = ref(null)
const logoPreview = ref('')
const removeLogoFlag = ref(false)
const mode = ref(selectedCategoryId.value ? 'edit' : 'create-root')

const showDeleteModal = ref(false)
const deleteTarget = ref({ id: null, name: '' })
const deleting = ref(false)

const form = useForm({
  id: null,
  menu_id: currentMenuId.value || '',
  parent_id: null,
  food_category_id: '',
  name: '',
  slug: '',
  sort_order: 0,
  is_active: true,
  logo: null,
  remove_logo: 0,
})

const flatTree = computed(() => flattenTree(props.tree))
const selectedCategory = computed(() =>
  flatTree.value.find((item) => item.id === selectedCategoryId.value) || null
)

const isEditMode = computed(() => mode.value === 'edit')
const isCreateMode = computed(() => mode.value !== 'edit')

const panelTitle = computed(() => {
  if (mode.value === 'create-sub') return 'Create Sub Category'
  if (mode.value === 'create-root') return 'Create Root Category'
  return 'Edit Category'
})

const panelSubtitle = computed(() => {
  if (mode.value === 'create-sub') {
    return selectedCategory.value
      ? `Creating a child category under "${selectedCategory.value.name}".`
      : 'Create a child category.'
  }
  if (mode.value === 'create-root') {
    return 'Create a root category for shop products.'
  }
  return 'Update category information and logo.'
})

const parentCategoryName = computed(() => {
  if (mode.value === 'create-sub' && selectedCategory.value) {
    return selectedCategory.value.name
  }
  if (form.parent_id) {
    const parent = flatTree.value.find((item) => item.id === Number(form.parent_id))
    return parent?.name || '-'
  }
  return 'Root Category'
})

function flattenTree(nodes, level = 0, result = []) {
  nodes.forEach((node) => {
    result.push({ ...node, level })
    if (node.children?.length) {
      flattenTree(node.children, level + 1, result)
    }
  })
  return result
}

function collectExpandableIds(nodes, result = []) {
  nodes.forEach((node) => {
    if (node.children?.length) {
      result.push(node.id)
      collectExpandableIds(node.children, result)
    }
  })
  return result
}

const expandedIds = ref(collectExpandableIds(props.tree))

function toggleExpand(id) {
  if (expandedIds.value.includes(id)) {
    expandedIds.value = expandedIds.value.filter((item) => item !== id)
    return
  }
  expandedIds.value = [...expandedIds.value, id]
}

function expandAll() {
  expandedIds.value = collectExpandableIds(props.tree)
}

function collapseAll() {
  expandedIds.value = []
}

function filterTree(nodes, term) {
  if (!term) return nodes
  const lower = term.toLowerCase()
  return nodes
    .map((node) => {
      const children = filterTree(node.children || [], term)
      const matched = String(node.name || '').toLowerCase().includes(lower)
      if (matched || children.length) {
        return { ...node, children }
      }
      return null
    })
    .filter(Boolean)
}

const filteredTree = computed(() => filterTree(props.tree, searchQuery.value.trim()))

watch(() => searchQuery.value, (value) => {
  if (value.trim()) {
    expandedIds.value = collectExpandableIds(filteredTree.value)
  }
})

watch(() => form.name, (value) => {
  if (!slugTouched.value) {
    form.slug = slugify(value)
  }
})

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.message) alertSuccess(flash.message)
    if (flash?.error) alertError(flash.error)
  },
  { immediate: true }
)

function slugify(value) {
  return String(value || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

function syncFormFromCategory(category) {
  if (!category) {
    form.reset()
    form.id = null
    form.menu_id = currentMenuId.value || ''
    form.parent_id = null
    form.food_category_id = ''
    form.name = ''
    form.slug = ''
    form.sort_order = 0
    form.is_active = true
    form.logo = null
    form.remove_logo = 0
    logoPreview.value = ''
    removeLogoFlag.value = false
    slugTouched.value = false
    return
  }

  form.id = category.id
  form.menu_id = category.menu_id
  form.parent_id = category.parent_id
  form.food_category_id = category.food_category_id || ''
  form.name = category.name
  form.slug = category.slug
  form.sort_order = category.sort_order ?? 0
  form.is_active = !!category.is_active
  form.logo = null
  form.remove_logo = 0

  if (logoPreview.value && logoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreview.value)
  }

  logoPreview.value = category.logo_url || ''
  removeLogoFlag.value = false
  slugTouched.value = true
}

watch(() => selectedCategory.value, (value) => {
  if (mode.value === 'edit') {
    syncFormFromCategory(value)
  }
}, { immediate: true })

function handleSelect(id) {
  selectedCategoryId.value = Number(id)
  mode.value = 'edit'
  syncFormFromCategory(selectedCategory.value)
}

function startCreateRoot() {
  mode.value = 'create-root'
  selectedCategoryId.value = null
  syncFormFromCategory(null)
  form.menu_id = currentMenuId.value || ''
  form.parent_id = null
  form.food_category_id = ''
}

function startCreateSub() {
  if (!selectedCategory.value) return
  mode.value = 'create-sub'
  syncFormFromCategory(null)
  form.menu_id = currentMenuId.value || ''
  form.parent_id = selectedCategory.value.id
  form.food_category_id = selectedCategory.value.food_category_id || ''
  form.is_active = true
}

function openFilePicker() {
  fileInput.value?.click()
}

function onLogoChange(event) {
  const file = event.target.files?.[0]
  if (!file) return

  form.logo = file
  form.remove_logo = 0
  removeLogoFlag.value = false

  if (logoPreview.value && logoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreview.value)
  }
  logoPreview.value = URL.createObjectURL(file)
}

function removeLogo() {
  if (logoPreview.value && logoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreview.value)
  }
  logoPreview.value = ''
  form.logo = null
  form.remove_logo = 1
  removeLogoFlag.value = true

  if (fileInput.value) fileInput.value.value = ''
}

function normalizedPayload(data) {
  return {
    ...data,
    menu_id: currentMenuId.value || data.menu_id,
    parent_id: data.parent_id || null,
    food_category_id: null,
    slug: slugify(data.slug || data.name),
    sort_order: Number(data.sort_order || 0),
    is_active: data.is_active ? 1 : 0,
    remove_logo: removeLogoFlag.value ? 1 : 0,
  }
}

function submit() {
  if (!currentMenuId.value) return

  const options = {
    preserveScroll: true,
    forceFormData: true,
  }

  if (isEditMode.value && form.id) {
    form
      .transform((data) => ({
        ...normalizedPayload(data),
        _method: 'PUT',
      }))
      .post(route('vendor.categories.update', form.id), options)
    return
  }

  form
    .transform((data) => normalizedPayload(data))
    .post(route('vendor.categories.store'), options)
}

function openDeleteModal() {
  if (!form.id) return
  deleteTarget.value = { id: form.id, name: form.name || '' }
  showDeleteModal.value = true
}

function onModalClosed() { }

function confirmDelete() {
  const id = deleteTarget.value?.id
  if (!id) return
  deleting.value = true
  router.delete(route('vendor.categories.destroy', id), {
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

</script>

<style scoped>
/* ... all styles unchanged ... */
.category-page {
  min-height: 100%;
}

.category-page__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.category-page__title {
  font-weight: 800;
  color: #334155;
  margin: 0;
}

.category-page__subtitle {
  margin: 6px 0 0;
  color: #64748b;
}

.category-page__header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.category-layout {
  display: grid;
  grid-template-columns: 380px minmax(0, 1fr);
  gap: 22px;
}

.category-tree-card,
.category-editor-card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  border: 1px solid rgba(148, 163, 184, 0.14);
}

.category-editor-card {
  max-height: fit-content;
}

.category-tree-card {
  padding: 16px;
  display: flex;
  flex-direction: column;
  min-height: 660px;
}

.category-tree-card__top {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.category-tree-card__actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
  width: 100%;
}

.btn-root,
.btn-sub,
.btn-update,
.btn-delete {
  border: none;
  border-radius: 14px;
  height: 40px !important;
  font-weight: 700;
  padding: 0 16px;
}

.btn-root {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
  color: #fff !important;
}

.btn-sub {
  background: linear-gradient(135deg, #60a5fa, #2563eb) !important;
  color: #ffffff !important;
}

.category-tree-card__search {
  position: relative;
}

.category-tree-card__search i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.category-tree-card__search input {
  padding-left: 40px;
  min-height: 44px;
  border-radius: 12px;
  border: 1px solid #d7dee7;
}

.category-tree-card__meta {
  margin-top: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #cbd5e1;
  font-size: 14px;
}

.tree-link {
  border: none;
  background: transparent;
  color: #2563eb;
  font-weight: 700;
  padding: 0;
}

.category-tree-card__body {
  margin-top: 14px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.tree-scroll {
  overflow: scroll;
  scroll-behavior: smooth;
}

.tree-scroll::-webkit-scrollbar {
  width: 4px !important;
  height: 4px !important;
}

.tree-scroll::-webkit-scrollbar-thumb {
  width: 3px !important;
}

.tree-scroll::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #93c5fd, #3b82f6);
  border-radius: 10px;
}

.tree-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.tree-empty {
  min-height: 240px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #94a3b8;
  gap: 10px;
  border: 1px dashed #e2e8f0;
  border-radius: 14px;
  background: #fafafa;
}

.tree-empty i {
  font-size: 34px;
  color: #cbd5f5;
}

.category-editor-card {
  padding: 18px;
}

.category-editor-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.category-editor-card__title {
  margin: 0;
  font-weight: 800;
  color: #334155;
}

.category-editor-card__subtitle {
  margin: 6px 0 0;
  color: #64748b;
}

.category-editor-card__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-update {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #fff;
}

.btn-delete {
  background: linear-gradient(135deg, #f87171, #ef4444);
  color: #fff;
}

.pretty-input {
  min-height: 46px;
  border-radius: 16px;
  border-color: #d7dee7;
}

.pretty-input:focus,
.formControl:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.formControl {
  height: 44px;
  margin-top: 5px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
}

.floating-label {
  font-weight: 700;
  color: #475569;
  margin-bottom: 8px;
}

.media-box {
  width: 100%;
  height: 250px;
  border: 2px dashed #bfdbfe;
  background: #eff6ff;
  border-radius: 16px;
  display: grid;
  place-items: center;
  overflow: hidden;
  cursor: pointer;
}

.media-box__placeholder {
  font-size: 52px;
  color: #d0d5dd;
}

.media-box__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-box__icon {
  font-size: 58px;
  color: #a0a0a0;
}

.media-box__inner {
  width: 100%;
  max-width: 120px;
  aspect-ratio: 1 / 1;
  margin: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: #eff6ff;
  overflow: hidden;
}

.media-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.info-panel {
  min-height: 160px;
  background: #f8fafc;
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 16px;
  padding: 16px;
}

.info-panel__row+.info-panel__row {
  margin-top: 14px;
}

.info-panel__label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.info-panel__value {
  display: block;
  margin-top: 6px;
  font-weight: 700;
  color: #334155;
}

.checkbox-wrap {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}

.checkbox-wrap input {
  display: none;
}

.checkbox-wrap__box {
  width: 22px;
  height: 22px;
  border-radius: 7px;
  border: 1px solid #d7dee7;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  color: transparent;
  transition: all .15s ease;
}

.checkbox-wrap input:checked+.checkbox-wrap__box {
  background: #3b82f6;
  border-color: #3b82f6;
  color: #fff;
}

.checkbox-wrap__label {
  font-weight: 700;
  color: #475569;
}

.empty-state {
  background: #fff;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  padding: 48px 24px;
  text-align: center;
}

.empty-state__icon {
  width: 70px;
  height: 70px;
  margin: 0 auto 16px;
  border-radius: 18px;
  display: grid;
  place-items: center;
  background: #eff6ff;
  color: #2563eb;
  font-size: 28px;
}

.category-layout {
  display: grid;
  grid-template-columns: 380px minmax(0, 1fr);
  gap: 32px;
}

.category-tree-card__top,
.category-tree-card__meta,
.category-editor-card__header {
  margin-bottom: 16px;
}

.btn-root,
.btn-sub,
.btn-update,
.btn-delete {
  border: none;
  border-radius: 10px;
  font-weight: 600;
  padding: 10px 16px;
}

.btn-delete {
  background: linear-gradient(135deg, #ef4444, #f87171);
  color: white;
}

.error-text {
  font-size: 12px;
  color: #dc2626;
  margin-top: 4px;
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

@media (max-width: 1199.98px) {
  .category-layout {
    grid-template-columns: 1fr;
  }

  .category-tree-card {
    min-height: auto;
  }
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
}
</style>
