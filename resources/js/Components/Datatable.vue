<template>
<div :class="[wrapperClass, { 'dt-hide-buttons': showKebabDropdown }]">
    <div class="card border-0 dt-card">
      <div class="card-body position-relative p-0">
        <div v-if="$slots.top" class="card-header bg-transparent border-0 px-3 pt-3 pb-0">
          <slot name="top" />
        </div>

        <!-- Top toolbar -->
        <div class="dt-toolbar-container px-3 pt-3">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div></div>

            <div class="d-flex align-items-center gap-3">

              <!-- Search input -->
              <div class="dt-search-container">
                <div class="dt-search-wrap input-group input-group-sm">
                  <span class="input-group-text dt-search-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="8"></circle>
                      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                  </span>
                  <input type="search" class="form-control dt-pill-input" :placeholder="searchPlaceholder">
                </div>
              </div>

              <!-- Kebab dropdown -->
              <div v-if="showKebabDropdown" class="dt-kebab-container">
                <div class="dropdown">
                  <button
                    class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                      class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                      <path
                        d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                    </svg>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li v-for="(btn, index) in kebabButtons" :key="index">
                      <a class="dropdown-item" href="#" @click.prevent="handleKebabButtonClick(btn)">
                        <i v-if="btn.icon" :class="btn.icon" class="me-2"></i>
                        {{ btn.text }}
                      </a>
                    </li>
                  </ul>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- ✅ Skeleton Loading -->
        <div v-if="useSkeleton && isSkeletonLoading" class="dt-skeleton-wrap px-3 pb-3 pt-3">
          <div class="table-responsive">
            <table class="table align-middle w-100 mb-0">
              <thead>
                <tr>
                  <th v-for="n in skeletonCols" :key="n">
                    <div class="sk sk-h sk-w-60"></div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in skeletonRows" :key="r">
                  <td v-for="c in skeletonCols" :key="c">
                    <div class="sk sk-line"></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Real Table -->
        <div class="table-responsive px-3 pb-3 pt-3" v-show="!useSkeleton || !isSkeletonLoading">
          <table :id="id" class="table table-hover align-middle w-100 mb-0 dt-table" :class="tableClass">
            <thead>
              <slot name="header" />
            </thead>
            <tbody></tbody>
          </table>
        </div>

                <!-- Custom Pagination Container -->
        <div v-if="showCustomPagination" class="custom-pagination-container px-3 pb-3 pt-0">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <!-- Info text -->
            <div class="dt-info-text small text-muted">
              Showing <strong>{{ currentStart }}</strong>–<strong>{{ currentEnd }}</strong> of <strong>{{ totalRecords }}</strong>
            </div>
            
            <!-- Pagination controls -->
            <div class="d-flex align-items-center gap-2">
              <!-- Entries per page -->
              <div class="dt-entries-selector d-none d-md-flex align-items-center gap-2">
                <span class="small text-muted">Rows per page:</span>
                <select 
                  class="form-select form-select-sm dt-pill-select" 
                  style="width: auto;"
                  @change="onPageSizeChange"
                  :value="currentPageSize"
                >
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                  <option value="-1">All</option>
                </select>
              </div>
              
              <!-- Page navigation -->
              <nav aria-label="Pagination">
                <ul class="pagination pagination-sm mb-0">
                  <!-- First page -->
                  <li class="page-item" :class="{ disabled: currentPage === 0 }">
                    <button 
                      class="page-link dt-page-pill" 
                      @click="goToFirstPage"
                      :disabled="currentPage === 0"
                    >
                      <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8.354 1.646a.5.5 0 0 1 0 .708L2.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                        <path fill-rule="evenodd" d="M12.354 1.646a.5.5 0 0 1 0 .708L6.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                      </svg>
                    </button>
                  </li>
                  
                  <!-- Previous page -->
                  <li class="page-item" :class="{ disabled: currentPage === 0 }">
                    <button 
                      class="page-link dt-page-pill" 
                      @click="goToPreviousPage"
                      :disabled="currentPage === 0"
                    >
                      <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                      </svg>
                    </button>
                  </li>
                  
                  <!-- Page numbers -->
                  <li v-for="page in visiblePages" :key="page" class="page-item">
                    <button 
                      class="page-link dt-page-pill" 
                      :class="{ active: currentPage === page - 1 }"
                      @click="goToPage(page - 1)"
                    >
                      {{ page }}
                    </button>
                  </li>
                  
                  <!-- Ellipsis for many pages -->
                  <li v-if="totalPages > maxVisiblePages && currentPage < totalPages - 3" class="page-item disabled">
                    <span class="page-link dt-page-pill">...</span>
                  </li>
                  
                  <!-- Next page -->
                  <li class="page-item" :class="{ disabled: currentPage === totalPages - 1 }">
                    <button 
                      class="page-link dt-page-pill" 
                      @click="goToNextPage"
                      :disabled="currentPage === totalPages - 1"
                    >
                      <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                      </svg>
                    </button>
                  </li>
                  
                  <!-- Last page -->
                  <li class="page-item" :class="{ disabled: currentPage === totalPages - 1 }">
                    <button 
                      class="page-link dt-page-pill" 
                      @click="goToLastPage"
                      :disabled="currentPage === totalPages - 1"
                    >
                      <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708z"/>
                        <path fill-rule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708z"/>
                      </svg>
                    </button>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
      </div>
    </div>
  </div>
  </div>
</template>

<script>
export default {
  name: 'DataTable',
  emits: ['ready', 'draw', 'error', 'kebab-click'],

  props: {
    id: { type: String, required: true },
    url: { type: String, required: true },

    // Kebab dropdown
    showKebabDropdown: { type: Boolean, default: false },
    kebabButtons: {
      type: Array,
      default: () => [
        { text: 'Copy to clipboard', action: 'copy', icon: 'bi bi-clipboard' },
        { text: 'Export to Excel', action: 'excel', icon: 'bi bi-file-earmark-excel' },
        { text: 'Export CSV', action: 'csv', icon: 'bi bi-file-earmark-arrow-down' },
        { text: 'Print', action: 'print', icon: 'bi bi-printer' },
      ],
    },

    columns: { type: Array, required: true },
    columnDefs: { type: Array, default: () => [] },
    order: { type: Array, default: () => [] },

    method: { type: String, default: 'GET' },
    submitData: { type: Object, default: () => ({}) },
    reloadKey: { type: [Number, String, Boolean], default: null },

    processing: { type: Boolean, default: true },
    serverSide: { type: Boolean, default: true },
    responsive: { type: Boolean, default: true },
    stateSave: { type: Boolean, default: false },

    displayLength: { type: Number, default: 10 },
    lengthMenu: {
      type: Array,
      default: () => [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'All']],
    },

    buttons: { type: Array, default: () => [] },
    useDefaultButtons: { type: Boolean, default: false },
    exportColumns: { type: String, default: ':visible:not(:last-child)' },

    dom: {
      type: String,
      default: `
<'px-3 mt-3' t>
<'dt-footer row g-2 align-items-center px-3 pb-3 mt-3'
>`,
    },

    wrapperClass: { type: String, default: '' },
    tableClass: { type: String, default: '' },

    searchPlaceholder: { type: String, default: 'Search…' },
    emptyText: { type: String, default: 'No records found' },

    useSkeleton: { type: Boolean, default: true },
    skeletonRows: { type: Number, default: 8 },

    showCustomPagination: { type: Boolean, default: true },
    maxVisiblePages: { type: Number, default: 5 },
  },

  data() {
    return {
      dt: null,
      isSkeletonLoading: true,
      _bound: false,

      currentPage: 0,
      currentPageSize: 10,
      totalRecords: 0,
      filteredRecords: 0,
      totalPages: 0,
    }
  },

  computed: {
    skeletonCols() {
      return (this.columns?.length || 6)
    },

        currentStart() {
      return this.totalRecords > 0 ? (this.currentPage * this.currentPageSize) + 1 : 0
    },
    
    currentEnd() {
      const end = (this.currentPage + 1) * this.currentPageSize
      return end > this.totalRecords ? this.totalRecords : end
    },
    
    visiblePages() {
      const pages = []
      const half = Math.floor(this.maxVisiblePages / 2)
      let start = this.currentPage - half
      let end = this.currentPage + half
      
      if (start < 0) {
        start = 0
        end = Math.min(this.maxVisiblePages - 1, this.totalPages - 1)
      }
      
      if (end >= this.totalPages) {
        end = this.totalPages - 1
        start = Math.max(0, end - this.maxVisiblePages + 1)
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i + 1)
      }
      
      return pages
    },
  },

  mounted() {
    this.initDataTable()
  },

  beforeUnmount() {
    this.destroyDataTable()
  },

  watch: {
    submitData: { deep: true, handler() { this.reloadDatatable() } },
    url() { this.reloadDatatable() },
    reloadKey() { this.reloadDatatable() },

    columns: { deep: true, handler() { this.reInit() } },
    columnDefs: { deep: true, handler() { this.reInit() } },
    buttons: { deep: true, handler() { this.reInit() } },
    dom() { this.reInit() },
  },

  methods: {
    resolvedButtons() {
  const userButtons = Array.isArray(this.buttons) ? this.buttons : []

  const needsDefaults = this.useDefaultButtons || this.showKebabDropdown
  if (!needsDefaults) return userButtons

  const defaults = [
    { extend: 'copyHtml5', exportOptions: { columns: this.exportColumns } },
    { extend: 'excelHtml5', exportOptions: { columns: this.exportColumns } },
    { extend: 'csvHtml5', exportOptions: { columns: this.exportColumns } },
    { extend: 'print', exportOptions: { columns: this.exportColumns } },
  ]

  return [...defaults, ...userButtons]
},

    csrfToken() {
      return (
        this.$page?.props?.csrf_token ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        ''
      )
    },

    ensureJquery() {
      const $ = window.jQuery
      if (!$ || !$.fn?.DataTable) {
        console.error('[DataTable] jQuery / DataTables not loaded.')
        return null
      }
      return $
    },

    handleKebabButtonClick(btn) {
  this.$emit('kebab-click', btn.action, btn)
  if (!this.dt) return

  const map = {
    copy: '.buttons-copy',
    excel: '.buttons-excel',
    csv: '.buttons-csv',
    print: '.buttons-print',
  }

  const selector = map[btn.action]
  if (!selector) return

  try {
    this.dt.button(selector).trigger()
  } catch (e) {
    console.error('[DataTable] Buttons not ready / missing extension', e)
    this.$emit('error', e)
  }
},


        onPageSizeChange(event) {
      const value = parseInt(event.target.value)
      this.currentPageSize = value === -1 ? this.totalRecords : value
      this.currentPage = 0
      if (this.dt) {
        this.dt.page.len(this.currentPageSize).draw()
      }
    },
    
    goToFirstPage() {
      if (this.dt && this.currentPage > 0) {
        this.currentPage = 0
        this.dt.page('first').draw('page')
      }
    },
    
    goToPreviousPage() {
      if (this.dt && this.currentPage > 0) {
        this.currentPage--
        this.dt.page('previous').draw('page')
      }
    },
    
    goToPage(page) {
      if (this.dt && page >= 0 && page < this.totalPages && page !== this.currentPage) {
        this.currentPage = page
        this.dt.page(page).draw('page')
      }
    },
    
    goToNextPage() {
      if (this.dt && this.currentPage < this.totalPages - 1) {
        this.currentPage++
        this.dt.page('next').draw('page')
      }
    },
    
    goToLastPage() {
      if (this.dt && this.currentPage < this.totalPages - 1) {
        this.currentPage = this.totalPages - 1
        this.dt.page('last').draw('page')
      }
    },
    
    updatePaginationInfo() {
      if (!this.dt) return
      
      const info = this.dt.page.info()
      this.currentPage = info.page
      this.currentPageSize = info.length
      this.totalRecords = info.recordsDisplay
      this.filteredRecords = info.recordsTotal
      this.totalPages = info.pages
    },

    initDataTable() {
      const $ = this.ensureJquery()
      if (!$) {
        this.isSkeletonLoading = false
        return
      }

      if (!this.url) {
        console.error('[DataTable] url is empty')
        this.isSkeletonLoading = false
        return
      }

      const tableId = `#${this.id}`
      this.destroyDataTable()
      this.isSkeletonLoading = true

      $.fn.dataTable.ext.errMode = 'none'

const hasButtons = (this.resolvedButtons()?.length || 0) > 0

      const options = {
 dom: this.showCustomPagination
    ? (hasButtons ? 'tB' : 't')
    : (hasButtons ? `B${this.dom}` : this.dom),        processing: false, 
        serverSide: this.serverSide,
        responsive: this.responsive,
        stateSave: this.stateSave,

        pageLength: this.displayLength,
        lengthMenu: this.lengthMenu,

        order: this.order,
        columns: this.columns,
        columnDefs: this.columnDefs,
  buttons: this.resolvedButtons(),

        language: {
          search: '',
          lengthMenu: '_MENU_',
          zeroRecords: this.emptyText,
          info: 'Showing _START_–_END_ of _TOTAL_',
          infoEmpty: 'Showing 0–0 of 0',
          infoFiltered: '(filtered from _MAX_)',
          paginate: { previous: '‹', next: '›' },
        },

        preDrawCallback: () => {
          if (this.useSkeleton) this.isSkeletonLoading = true
        },

        ajax: {
          url: this.url,
          type: this.method,
          data: (d) => {
            d._token = this.csrfToken()
            Object.assign(d, this.submitData || {})
          },

          dataSrc: (json) => {
            if (!json) return []
            if (json.data && Array.isArray(json.data)) return json.data
            if (Array.isArray(json)) return json
            console.error('[DataTable] Unexpected JSON shape:', json)
            this.$emit('error', json)
            return []
          },

          error: (xhr) => {
            console.error('[DataTable] AJAX error:', xhr?.status, xhr?.responseText)
            this.isSkeletonLoading = false
            this.$emit('error', xhr)
          },
        },

        initComplete: () => {
          this.updatePaginationInfo()
          this.decorateUi($, tableId)
          this.bindCustomControls($, tableId)
          this.isSkeletonLoading = false
          this.$emit('ready', this.dt)

          if (!this._bound) {
            this._bound = true
            $(tableId).on('error.dt', (e, settings, techNote, message) => {
              console.error('[DataTable] dt error:', message)
              this.$emit('error', message)
              this.isSkeletonLoading = false
            })
          }
        },

        drawCallback: () => {
          this.updatePaginationInfo()
          this.decorateUi($, tableId)
          this.isSkeletonLoading = false
          this.$emit('draw')
        },
      }

      this.dt = $(tableId).DataTable(options)
    },

    bindCustomControls($, tableId) {
      // Entries
      const $entriesSelect = $(`.dt-entries-container select`)
      if ($entriesSelect.length) {
        $entriesSelect.off('change.dtCustom').on('change.dtCustom', (e) => {
          if (this.dt){
             this.currentPageSize = e.target.value === '-1' ? this.totalRecords : parseInt(e.target.value)
            this.currentPage = 0
            this.dt.page.len(e.target.value).draw()
          }
        })
      }

      // Search
      const $searchInput = $(`.dt-search-container input`)
      if ($searchInput.length) {
        let searchTimeout
        $searchInput.off('keyup.dtCustom').on('keyup.dtCustom', (e) => {
          clearTimeout(searchTimeout)
          searchTimeout = setTimeout(() => {
                        if (this.dt) {
              this.currentPage = 0
              this.dt.search(e.target.value).draw()
            }
          }, 300)
        })
      }
    },

    decorateUi($, tableId) {
      if (this.dt) {
        const $entriesSelect = $(`.dt-entries-container select`)
        if ($entriesSelect.length) $entriesSelect.val(this.dt.page.len())

        const $searchInput = $(`.dt-search-container input`)
        if ($searchInput.length) $searchInput.val(this.dt.search())
      }

            // Hide default pagination if using custom
      if (this.showCustomPagination) {
        $(`${tableId}_paginate`).hide()
        $(`${tableId}_info`).hide()
      } else {
        $(`${tableId}_paginate`).addClass('dt-paginate')
        $(`${tableId}_paginate .paginate_button`).addClass('dt-page-pill')
      }
      
      $(tableId).addClass('table-hover')
    },

    destroyDataTable() {
      if (!this.dt) return
      try { this.dt.destroy(true) } catch {}
      this.dt = null
      this.isSkeletonLoading = true

      this.currentPage = 0
      this.currentPageSize = 10
      this.totalRecords = 0
      this.filteredRecords = 0
      this.totalPages = 0
    },

    reloadDatatable(done) {
      if (!this.dt) return done && done()
      this.isSkeletonLoading = true
      try {
        this.dt.ajax.reload(() => {
          this.isSkeletonLoading = false
          done && done()
        }, false)
      } catch {
        this.reInit()
        done && done()
      }
    },

    reInit() {
      this.$nextTick(() => this.initDataTable())
    },

    getApi() {
      return this.dt
    },
  },
}
</script>

<style scoped>
.dt-card { --dt-accent: #FF9500; }

/* Toolbar container */
.dt-toolbar-container {
  padding-top: 1rem !important;
  padding-bottom: 0.5rem !important;
}

/* Table head/body */
.dt-table :deep(thead th){
  background: #f8f9fa;
  border-bottom: 1px solid rgba(0,0,0,0.08) !important;
  font-weight: 700;
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #495057;
  white-space: nowrap;
}
.dt-table :deep(tbody td){
  border-bottom: 1px solid rgba(0,0,0,0.06) !important;
  color: #212529;
}
.dt-table :deep(tbody tr:hover){
  background: rgba(255,149,0,0.06) !important;
}

/* Controls */
:deep(.dt-search-container) { min-width: 250px; }
:deep(.dt-search-wrap){ width: 100%; }
:deep(.dt-search-icon){
  border-radius: 9999px 0 0 9999px;
  background: #fff;
  border: 1px solid rgba(0,0,0,0.12);
  border-right: 0;
  color: #6c757d;
  padding: 0.375rem 0.75rem;
}
:deep(.dt-pill-input){
  border-radius: 0 9999px 9999px 0;
  border: 1px solid rgba(0,0,0,0.12);
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}
:deep(.dt-pill-input:focus){
  border-color: rgba(255,149,0,.55) !important;
  box-shadow: 0 0 0 .25rem rgba(255,149,0,.18) !important;
}
:deep(.dt-pill-select){
  border-radius: 9999px;
  border: 1px solid rgba(0,0,0,0.12);
  padding: 0.375rem 1.75rem 0.375rem 0.75rem;
  font-size: 0.875rem;
  min-width: 70px;
}
:deep(.dt-pill-select:focus){
  border-color: rgba(255,149,0,.55) !important;
  box-shadow: 0 0 0 .25rem rgba(255,149,0,.18) !important;
}

:deep(.dt-kebab-container .btn-outline-secondary) {
  border-radius: 9999px;
  padding: 0.375rem 0.75rem;
  border-color: rgba(0,0,0,0.12);
  color: #6c757d;
}
:deep(.dt-kebab-container .btn-outline-secondary:hover) {
  background-color: rgba(0,0,0,0.04);
  border-color: rgba(0,0,0,0.2);
}

/* Custom Pagination Styles */
.custom-pagination-container {
  margin-top: 1rem;
}

.dt-info-text {
  font-size: 0.875rem;
}

.dt-entries-selector {
  font-size: 0.875rem;
}

/* Pagination controls */
:deep(.pagination) {
  gap: 6px;
  margin: 0;
}

:deep(.page-link.dt-page-pill) {
  border-radius: 9999px !important;
  min-width: 36px;
  height: 36px;
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(0,0,0,0.12) !important;
  background: #fff !important;
  color: #212529 !important;
  font-weight: 600;
  padding: 0.375rem 0.75rem;
  transition: all 0.2s ease;
}

:deep(.page-link.dt-page-pill:hover) {
  border-color: rgba(255,149,0,.55) !important;
  background: rgba(255,149,0,.06) !important;
  color: #212529 !important;
}

:deep(.page-link.dt-page-pill:focus) {
  box-shadow: 0 0 0 0.25rem rgba(255,149,0,.25) !important;
}

:deep(.page-link.dt-page-pill.active),
:deep(.page-link.dt-page-pill.active:hover) {
  background: var(--dt-accent) !important;
  border-color: var(--dt-accent) !important;
  color: #fff !important;
}

:deep(.page-item.disabled .page-link.dt-page-pill) {
  background: #f8f9fa !important;
  border-color: rgba(0,0,0,0.1) !important;
  color: #6c757d !important;
  cursor: not-allowed;
}

:deep(.page-item .page-link.dt-page-pill svg) {
  margin: 0;
}


/* Dropdown menu */
:deep(.dropdown-menu) {
  border-radius: 12px !important;
  border: 1px solid rgba(0,0,0,.08) !important;
  box-shadow: 0 12px 28px rgba(0,0,0,.12) !important;
  padding: 8px !important;
  min-width: 200px;
}
:deep(.dropdown-item) {
  border-radius: 10px !important;
  padding: 0.5rem 1rem !important;
  margin: 2px 0 !important;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
}
:deep(.dropdown-item:hover) {
  background-color: rgba(255,149,0,.06) !important;
}

.sk{
  background: linear-gradient(
    90deg,
    rgba(0,0,0,.06) 25%,
    rgba(0,0,0,.10) 37%,
    rgba(0,0,0,.06) 63%
  );
  background-size: 700% 200%;
  animation: sk 1.2s ease-in-out infinite;
  border-radius: 9999px;
}
.sk-h{ height: 15px; }
.sk-w-60{ width: 80%; }
.sk-line{ height: 17px; width: 90%; }

.dt-skeleton-wrap table thead th{
  background: #f8f9fa;
  border-bottom: 1px solid rgba(0,0,0,0.08);
  padding: 12px 8px;
}
.dt-skeleton-wrap table tbody td{
  padding: 12px 12px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}

@keyframes sk{
  0%{ background-position: 100% 0; }
  100%{ background-position: 0 0; }
}

/* Responsive tweaks */
@media (max-width: 768px) {
  :deep(.dt-search-container) { min-width: 150px; }
}
@media (max-width: 576px) {
  .dt-toolbar-container .d-flex {
    display: block !important;
    flex-wrap: wrap;
    justify-content: flex-end !important;
    gap: 1rem;
  }
  :deep(.dt-search-container) {
    order: 1;
    min-width: 100%;
    margin-top: 0.5rem;
  }
}
:deep(.table.dataTable th.dt-type-numeric, table.dataTable th.dt-type-date, 
table.dataTable td.dt-type-numeric, table.dataTable td.dt-type-date) {
  text-align: left !important;
}
.dt-hide-buttons :deep(.dt-buttons) {
  display: none !important;
}
</style>
