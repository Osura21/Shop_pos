<template>

    <Head title="Sessions" />

    <div class="page-container">
        <div class="card-modern">
            <div class="card-modern-header">
                <div class="header-content">
                    <div class="header-title-group">
                        <h1 class="header-title">Sessions</h1>
                        <p class="header-subtitle">Monitor open and closed POS sessions across all registers.</p>
                    </div>
                    <button v-if="this.can('pos-sessions.create')" type="button" class="btn btn-primary-modern" @click="openModal = true">
                        <i class="bi bi-plus-lg"></i>
                        <span>Open Session</span>
                    </button>
                </div>
            </div>

            <div class="table-container-modern">
                <DataTable ref="dtRef" :id="tableId" :url="datatableUrl" :columns="columns" :columnDefs="columnDefs"
                    :order="[[1, 'desc']]" searchPlaceholder="Search sessions...">
                    <template #header>
                        <tr>
                            <th>POS Register</th>
                            <th>Branch</th>
                            <th>Opened By</th>
                            <th>Closed By</th>
                            <th>Opening Float</th>
                            <th>Current Balance</th>
                            <th>Opened At</th>
                            <th>Closed At</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </template>
                </DataTable>
            </div>
        </div>
    </div>

    <!-- Open Session Modal -->
    <div v-if="openModal" class="modal-backdrop" @click.self="openModal = false">
        <div class="modal-card">
            <div class="modal-card__header">
                <div>
                    <h4 class="modal-card__title">Open New Session</h4>
                    <p class="modal-card__subtitle">Select a register and enter the opening float.</p>
                </div>
                <button type="button" class="btn-close-modal" @click="openModal = false">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-card__body">
                <div class="field-wrap">
                    <label class="formLabel">Register</label>
                    <SelectInput id="pos_register_id" v-model="form.pos_register_id" :options="registers.map(r => ({
                      ...r,
                      label: `${r.name} (${r.code})`
                    }))" valueKey="id" labelKey="label" placeholder="Select Register" />
                    <div v-if="form.errors.pos_register_id" class="error-text">{{ form.errors.pos_register_id }}</div>
                </div>

                <div class="field-wrap">
                    <label class="formLabel">Opening Float</label>
                    <input v-model="form.opening_float" type="number" step="0.001" min="0" class="form-control formControl"
                        placeholder="0.000" />
                    <div v-if="form.errors.opening_float" class="error-text">{{ form.errors.opening_float }}</div>
                </div>

                <div class="field-wrap">
                    <label class="formLabel">Notes</label>
                    <textarea v-model="form.notes" class="form-control field-textarea formControl"
                        placeholder="Optional opening note"></textarea>
                </div>
            </div>

            <div class="modal-card__footer">
                <button type="button" class="btn btn-ghost" @click="openModal = false">Cancel</button>
                <button type="button" class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
                    <span v-if="form.processing" class="spinner-icon"></span>
                    <i v-else class="bi bi-check2-circle"></i>
                    <span>{{ form.processing ? 'Submitting...' : 'Open Session' }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Close Session Modal -->
    <div v-if="closeModal" class="modal-backdrop" @click.self="cancelClose">
        <div class="modal-card modal-card--sm">
            <div class="modal-card__header">
                <div>
                    <h4 class="modal-card__title">Close this session?</h4>
                    <p class="modal-card__subtitle">This will end the session for <strong>{{ closeTarget.name || 'this register' }}</strong>.</p>
                </div>
                <button type="button" class="btn-close-modal" @click="cancelClose" :disabled="closing">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-card__body">
                <div class="close-session-icon">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <p class="close-session-text">
                    Once closed, no further transactions can be added to this session.
                    Make sure all sales have been recorded before proceeding.
                </p>
            </div>

            <div class="modal-card__footer">
                <button type="button" class="btn btn-ghost" @click="cancelClose" :disabled="closing">
                    Keep Open
                </button>
                <button type="button" class="btn btn-saas-close" @click="confirmClose"
                    :disabled="closing || !closeTarget.id">
                    <span v-if="!closing">
                        <i class="bi bi-lock"></i> Close Session
                    </span>
                    <div v-else class="spinner-border spinner-border-sm"></div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, router, useForm } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import DataTable from '@/Components/Datatable.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue';

export default {
    name: 'PosSessionIndex',
    layout: VendorAdminLayout,
    components: { Head, DataTable, SelectInput },

    props: {
        sessions: { type: Object, required: true },
        registers: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
    },

    data() {
        return {
            search: this.filters.search || '',
            openModal: false,
            closeModal: false,
            closeTarget: { id: null, name: '' },
            closing: false,
            form: useForm({
                pos_register_id: '',
                opening_float: '',
                notes: '',
                menu_id: '',
                currency_mode: 'base',
                currency_code: 'LKR',
            }),
            tableId: 'sessionTable',
        }
    },

    mounted() {
        const $ = window.jQuery
        if (!$) return
        const selector = `#${this.tableId}`

        $(document).on('click', `${selector} .js-close-session`, (e) => {
            const id = e.currentTarget?.dataset?.id
            const name = e.currentTarget?.dataset?.name || ''
            if (id) this.openCloseModal(id, name)
        })
    },

    beforeUnmount() {
        const $ = window.jQuery
        if ($) {
            const selector = `#${this.tableId}`
            $(document).off('click', `${selector} .js-close-session`)
        }
    },

    computed: {
        datatableUrl() {
            return route('vendor.pos.sessions.getdata')
        },

        flash() {
            return this.$page.props.flash;
        },


        columns() {
            return [
                {
                    data: 'session_info',
                    name: 'name',
                    render: (data, type, row) => `
            <div>
              <div class="fw-bold text-dark">${this.escapeHtml(data || 'â€”')}</div>
              <div class="text-muted x-small">SES-${row.id}</div>
            </div>
          `
                },
                {
                    data: 'branch',
                    name: 'branch',
                    render: (data) => data
                        ? `<span class="branch-chip"><i class="bi bi-building"></i> ${this.escapeHtml(data)}</span>`
                        : `<span class="text-muted small">â€”</span>`
                },
                {
                    data: 'opener',
                    name: 'opener',
                    render: (data) => data
                        ? `<span class="user-chip user-chip--opener"><i class="bi bi-person-fill"></i> ${this.escapeHtml(data)}</span>`
                        : `<span class="text-muted small">â€”</span>`
                },
                {
                    data: 'closer',
                    name: 'closer',
                    render: (data) => data
                        ? `<span class="user-chip user-chip--closer"><i class="bi bi-person-fill-slash"></i> ${this.escapeHtml(data)}</span>`
                        : `<span class="text-muted small">â€”</span>`
                },
                {
                    data: 'opening_float',
                    name: 'opening_float',
                    render: (data) => data
                        ? `<span class="amount-chip amount-chip--neutral"> ${this.escapeHtml(String(data))}</span>`
                        : `<span class="text-muted small">â€”</span>`
                },
                {
                    data: 'current_balance',
                    name: 'current_balance',
                    render: (data) => data
                        ? `<span class="amount-chip amount-chip--balance">${this.escapeHtml(String(data))}</span>`
                        : `<span class="text-muted small">â€”</span>`
                },
                {
                    data: 'opened_at',
                    name: 'opened_at',
                    render: (d) => `<span class="text-secondary text-wrap small">${d || 'â€”'}</span>`
                },
                {
                    data: 'closed_at',
                    name: 'closed_at',
                    render: (d) => d
                        ? `<span class="text-secondary small">${d}</span>`
                        : `<span class="closed-label">â€”</span>`
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false,
                    render: (data) => {
                        if (data === 'open') {
                            return `
                            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
                            <i class="bi bi-check-circle-fill"></i>
                            Open
                            </span>
                        `
                        }

                        if (data === 'cancelled') {
                            return `
                            <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
                            <i class="bi bi-x-circle-fill text-danger"></i>
                            Closed
                            </span>
                        `
                        }

                        return `<span class="text-muted small">â€”</span>`
                    }
                },
                {
                    data: 'id',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        const name = this.escapeHtml(row?.session_info || row?.name || '')
                        const isClosed =
                            'cancelled' === this.escapeHtml(String(row?.status)).toLowerCase()

                        const closeBtn =
                            this.can('pos-sessions.edit')
                                ? (
                                    isClosed
                                        ? `<button type="button" class="btn-circle" disabled title="Already closed" style="opacity:0.4;cursor:not-allowed">
                            <i class="bi bi-lock-fill"></i>
                            </button>`
                                        : `<button type="button" class="btn-circle btn-circle-warning js-close-session" data-id="${data}" data-name="${name}" title="Close Session">
                                <i class="bi bi-lock"></i>
                                </button>`
                                )
                                : ''

                        return `
                            <div class="d-flex gap-2 justify-content-end">
                         ${closeBtn}
                        </div>
                    `
                    }
                }
            ]
        },

        columnDefs() {
            return [
                { targets: -1, className: 'text-end align-middle', width: '80px' },
                { targets: '_all', className: 'align-middle' },
            ]
        },
    },

    methods: {
        submit() {
            this.form.post(route('vendor.pos.sessions.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.openModal = false
                    this.form.reset()
                    this.$refs.dtRef?.reloadDatatable?.()
                },
            })
        },

        can(permission) {
            return this.$page.props.auth.permissions.includes(permission)
        },

        openCloseModal(id, name = '') {
            this.closeTarget = { id, name }
            this.closeModal = true
        },

        cancelClose() {
            if (this.closing) return
            this.closeModal = false
            this.closeTarget = { id: null, name: '' }
        },

        confirmClose() {
            const id = this.closeTarget?.id
            if (!id) return
            this.closing = true
            router.patch(route('vendor.pos.sessions.close', id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal = false
                    this.closeTarget = { id: null, name: '' }
                    this.$refs.dtRef?.reloadDatatable?.()
                },
                onFinish: () => { this.closing = false },
            })
        },

        go(url) {
            if (!url) return
            router.visit(url, { preserveState: true, preserveScroll: true })
        },

        escapeHtml(value = '') {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;')
        },
    },
    watch: {
        flash: {
            handler(flash) {
                if (flash?.message) {
                    alertSuccess(flash.message);
                }

                if (flash?.error) {
                    alertError(flash.error);
                }
            },
            immediate: true,
            deep: true
        }
    },
}
</script>

<style scoped>


:deep(thead th) {
    background: transparent !important;
    color: var(--slate-400) !important;
    font-weight: 600 !important;
    font-size: 0.72rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 1px solid var(--slate-50) !important;
    padding: 1.25rem 1rem !important;
    white-space: nowrap !important;
}

:deep(tbody td) {
    padding: 1.1rem 1rem !important;
    border-bottom: 1px solid var(--slate-50) !important;
}

/* Branch chip */
:deep(.branch-chip) {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.7rem;
    border-radius: 8px;
    color: var(--slate-600);
    font-size: 0.78rem;
    font-weight: 500;
    white-space: nowrap;
}

/* User chips */
:deep(.user-chip) {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.7rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 500;
    white-space: nowrap;
}

:deep(.user-chip--opener) {
    color: #15803d;
}

:deep(.user-chip--closer) {
    color: #dc2626;
}

/* Amount chips */
:deep(.amount-chip) {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.7rem;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    font-family: monospace;
    white-space: nowrap;
}

:deep(.amount-chip--neutral) {
    color: var(--slate-600);
}

:deep(.amount-chip--balance) {
    color: #1d4ed8;
}

:deep(.closed-label) {
    color: var(--slate-400);
    font-size: 0.78rem;
    font-weight: 600;
}

/* Circle action buttons */
:deep(.btn-circle) {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--slate-200);
    background: white;
    color: var(--slate-600);
    transition: 0.2s;
    cursor: pointer;
}

:deep(.btn-circle:hover:not(:disabled)) {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--primary-soft);
}

:deep(.btn-circle-warning:hover:not(:disabled)) {
    border-color: #3b82f6;
    color: #3b82f6;
    background: #eff6ff;
}


/* â”€â”€ Modal Backdrop â”€â”€ */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 2100;
    display: grid;
    place-items: center;
    padding: 18px;
}

/* â”€â”€ Modal Card â”€â”€ */
.modal-card {
    width: 100%;
    max-width: 540px;
    background: white;
    border-radius: 24px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.2);
    overflow: hidden;
}

.modal-card--sm {
    max-width: 420px;
}

.modal-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--slate-50);
}

.modal-card__title {
    font-weight: 800;
    color: var(--slate-900);
    margin: 0;
    font-size: 1.15rem;
}

.modal-card__subtitle {
    color: var(--slate-600);
    font-size: 0.875rem;
    margin: 4px 0 0;
}

.modal-card__body {
    padding: 1.5rem 1.75rem;
    display: grid;
    gap: 14px;
}

.modal-card__footer {
    padding: 1.25rem 1.75rem;
    border-top: 1px solid var(--slate-50);
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

/* Close button (X) */
.btn-close-modal {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid #c0c0c0;
    background: white;
    color: var(--slate-600);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: 0.2s;
}

.btn-close-modal:hover:not(:disabled) {
    border-color: #ef4444;
    color: #ef4444;
    background: #fef2f2;
}

/* Close session confirm button */
.btn-saas-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.7rem 1.5rem;
    border-radius: 12px;
    border: none;
    background: #3b82f6;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: 0.2s;
}

.btn-saas-close:hover:not(:disabled) {
    background: #d97706;
}

.btn-saas-close:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Close session modal body content */
.close-session-icon {
    width: 60px;
    height: 60px;
    background: #eff6ff;
    color: #3b82f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto 0.5rem;
}

.close-session-text {
    text-align: center;
    color: var(--slate-600);
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.6;
}

/* Form fields */
.field-wrap {
    display: grid;
}

.formControl:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
}

.formControl {
  height: 44px;
  margin-top: 5px;
  border: 1px solid rgba(0, 0, 0, 0.342);
  border-radius: 10px;
  font-size: 14px;
}

.field-textarea {
    height: auto;
    min-height: 100px;
    padding: 0.75rem 0.9rem;
    resize: vertical;
}

.error-text {
    font-size: 0.75rem;
    color: #dc2626;
}

/* Spinner */
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
</style>
