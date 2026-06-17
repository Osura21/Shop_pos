<template>
    <transition name="tdo-fade">
        <div
            v-if="show && table"
            ref="tableDetailsRoot"
            class="tdo-backdrop"
            @click.self="close"
        >
            <aside class="tdo-panel">
                <header class="tdo-header">
                    <div class="tdo-title-wrap">
                        <div class="tdo-title-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>

                        <div>
                            <h3>{{ table.name }}</h3>
                            <p>{{ table.zone || '-' }} Â· {{ table.floor || '-' }}</p>
                        </div>
                    </div>

                    <div class="tdo-header-actions">
                        <span class="tdo-status" :class="'tdo-status--' + String(table.status || '').toLowerCase()">
                            {{ label(table.status) }}
                        </span>

                        <button type="button" class="tdo-close" @click="close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </header>

                <section class="tdo-meta-grid">
                    <div class="tdo-meta-card">
                        <span>Capacity</span>
                        <strong>{{ table.capacity || '-' }}</strong>
                    </div>

                    <div class="tdo-meta-card">
                        <span>Floor</span>
                        <strong>{{ table.floor || '-' }}</strong>
                    </div>

                    <div class="tdo-meta-card">
                        <span>Zone</span>
                        <strong>{{ table.zone || '-' }}</strong>
                    </div>
                </section>

                <section class="tdo-card">
                    <label class="tdo-label">Waiter</label>

                    <SelectInput
                        v-model="waiterName"
                        :options="waiterOptions"
                        labelKey="label"
                        valueKey="value"
                        placeholder="Select Waiter"
                        @update:modelValue="saveWaiter"
                    />

                    <p v-if="waiterSaving" class="tdo-hint">
                        Saving waiter...
                    </p>
                </section>

                <section v-if="table.merge" class="tdo-card">
                    <div class="tdo-section-title">
                        <i class="bi bi-bezier2"></i>
                        <span>Merge Details</span>
                    </div>

                    <div class="tdo-merge-chip-row">
                        <span
                            v-for="member in table.merge.members"
                            :key="member.id"
                            class="tdo-merge-chip"
                            :class="{ 'tdo-merge-chip--primary': member.is_primary }"
                        >
                            {{ member.name }}
                        </span>
                    </div>
                </section>

                <section class="tdo-card">
                    <div class="tdo-section-title">
                        <i class="bi bi-basket"></i>
                        <span>Order Details</span>
                    </div>

                    <div v-if="orders.length" class="tdo-order-list">
                        <article
                            v-for="order in orders"
                            :key="order.id"
                            class="tdo-order-card"
                        >
                            <div class="tdo-order-top">
                                <div>
                                    <span class="tdo-order-time">
                                        {{ formatTime(order.sent_to_kitchen_at || order.created_at) }}
                                    </span>

                                    <strong class="tdo-order-title">
                                        #{{ order.id }} - {{ order.customer_name || 'Walk-In Customer' }}
                                    </strong>

                                    <small class="tdo-order-items">
                                        {{ order.items?.length || 0 }} Items: {{ itemSummary(order.items) }}
                                    </small>
                                </div>

                                <strong class="tdo-order-total">
                                    {{ currencySymbol(order.currency_code || activeCurrencyCode) }} {{ money(order.grand_total) }}
                                </strong>
                            </div>

                            <div class="tdo-badges">
                                <span
                                    class="tdo-chip"
                                    :class="'tdo-chip--' + String(order.status || 'pending').toLowerCase()"
                                >
                                    <i v-if="statusIcon(order.status)" :class="statusIcon(order.status)"></i>
                                    {{ label(order.status || 'pending') }}
                                </span>

                                <span
                                    class="tdo-chip"
                                    :class="'tdo-chip--' + String(order.payment_status || 'unpaid').toLowerCase()"
                                >
                                    {{ label(order.payment_status || 'unpaid') }}
                                </span>
                            </div>

                            <div class="tdo-order-actions">
                                <div class="tdo-action-left">
                                    <button
                                        type="button"
                                        class="tdo-icon-btn"
                                        title="View"
                                        @click="openOrderDetails(order)"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="tdo-icon-btn"
                                        title="Edit"
                                        :disabled="order.status === 'cancelled'"
                                        @click="$emit('edit-order', order)"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        v-if="order.payment_status !== 'paid' && order.status !== 'cancelled' && order.status !== 'preparing'"
                                        type="button"
                                        class="tdo-icon-btn"
                                        title="Cancel"
                                        @click="openCancel(order)"
                                    >
                                        <i class="bi bi-x-lg"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="tdo-icon-btn tdo-icon-btn--print"
                                        title="Print"
                                        @click="printOrder(order)"
                                    >
                                        <i class="bi bi-printer"></i>
                                        <span>Print</span>
                                    </button>
                                </div>

                                <div class="tdo-action-right">
                                    <button
                                        v-if="order.payment_status !== 'paid' && order.status !== 'cancelled'"
                                        type="button"
                                        class="tdo-main-btn tdo-main-btn--pay"
                                        @click="$emit('pay-order', order)"
                                    >
                                        <i class="bi bi-cash-stack"></i>
                                        <span>Pay</span>
                                    </button>

                                    <button
                                        v-if="nextStatusAction(order)"
                                        type="button"
                                        class="tdo-main-btn"
                                        :class="nextStatusAction(order).class"
                                        :disabled="statusSubmittingId === order.id"
                                        @click="changeStatus(order)"
                                    >
                                        <i :class="nextStatusAction(order).icon"></i>
                                        <span>
                                            {{ statusSubmittingId === order.id ? 'Please wait...' : nextStatusAction(order).label }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="tdo-empty">
                        <i class="bi bi-basket"></i>
                        <strong>No active order</strong>
                        <span>Create a new order for this table.</span>
                    </div>
                </section>

                <footer class="tdo-footer">
                    <button
                        type="button"
                        class="tdo-footer-btn tdo-footer-btn--merge"
                        @click="openMerge"
                    >
                        <i class="bi bi-bezier2"></i>
                        <span>Merge Table</span>
                    </button>

                    <button
                        v-if="!orders.length"
                        type="button"
                        class="tdo-footer-btn tdo-footer-btn--order"
                        @click="$emit('create-order', table)"
                    >
                        <i class="bi bi-plus-lg"></i>
                        <span>Create Order</span>
                    </button>
                </footer>
            </aside>

            <!-- Order Details Modal -->
            <div
                v-if="showDetailsModal"
                class="tdo-inner-backdrop"
                @click.self="closeOrderDetails"
            >
                <div class="tdo-details-modal">
                    <header class="tdo-modal-header">
                        <div>
                            <p>Order Information</p>
                            <h3>Order Details</h3>
                        </div>

                        <button type="button" class="tdo-close" @click="closeOrderDetails">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>

                    <div v-if="detailsLoading" class="tdo-skeleton-wrap">
                        <div v-for="n in 8" :key="n" class="tdo-skeleton"></div>
                    </div>

                    <template v-else-if="viewOrderData">
                        <div class="tdo-details-layout">
                            <main class="tdo-details-main">
                                <section class="tdo-details-card">
                                    <h4>Order Information</h4>

                                    <div class="tdo-info-grid">
                                        <div>
                                            <span>Reference No</span>
                                            <strong>{{ viewOrderData.reference_no || '-' }}</strong>
                                        </div>

                                        <div>
                                            <span>Order Number</span>
                                            <strong>{{ viewOrderData.order_number || viewOrderData.id || '-' }}</strong>
                                        </div>

                                        <div>
                                            <span>Branch</span>
                                            <strong>{{ viewOrderData.branch || '-' }}</strong>
                                        </div>

                                        <div>
                                            <span>Status</span>
                                            <strong>{{ label(viewOrderData.status) }}</strong>
                                        </div>

                                        <div>
                                            <span>Type</span>
                                            <strong>{{ label(viewOrderData.type || viewOrderData.channel) }}</strong>
                                        </div>

                                        <div>
                                            <span>Payment Status</span>
                                            <strong>{{ label(viewOrderData.payment_status || 'unpaid') }}</strong>
                                        </div>

                                        <div>
                                            <span>Guest Count</span>
                                            <strong>{{ viewOrderData.guest_count || '-' }}</strong>
                                        </div>

                                        <div>
                                            <span>Waiter</span>
                                            <strong>{{ viewOrderData.waiter || viewOrderData.waiter_name || '-' }}</strong>
                                        </div>

                                        <div>
                                            <span>Cashier</span>
                                            <strong>{{ viewOrderData.cashier || '-' }}</strong>
                                        </div>

                                        <div>
                                            <span>Register</span>
                                            <strong>{{ viewOrderData.pos_register || '-' }}</strong>
                                        </div>

                                        <div>
                                            <span>Created At</span>
                                            <strong>{{ formatDateTime(viewOrderData.created_at) }}</strong>
                                        </div>

                                        <div>
                                            <span>Updated At</span>
                                            <strong>{{ formatDateTime(viewOrderData.updated_at) }}</strong>
                                        </div>
                                    </div>
                                </section>

                                <section class="tdo-details-card">
                                    <h4>Customer Information</h4>

                                    <div class="tdo-info-grid tdo-info-grid--two">
                                        <div>
                                            <span>Name</span>
                                            <strong>{{ viewOrderData.customer_name || viewOrderData.customer?.name || 'Walk-In Customer' }}</strong>
                                        </div>

                                        <div>
                                            <span>Phone</span>
                                            <strong>{{ viewOrderData.customer_phone || '-' }}</strong>
                                        </div>
                                    </div>
                                </section>

                                <section class="tdo-details-card">
                                    <h4>Products</h4>

                                    <div class="tdo-products">
                                        <div class="tdo-products-head">
                                            <span>Product</span>
                                            <span>Status</span>
                                            <span>Unit Price</span>
                                            <span>Qty</span>
                                            <span>Total</span>
                                        </div>

                                        <div
                                            v-for="item in viewOrderData.items || []"
                                            :key="item.id || item.product_name"
                                            class="tdo-products-row"
                                        >
                                            <div>
                                                <strong>{{ item.product_name }}</strong>

                                                <small
                                                    v-for="option in item.options || []"
                                                    :key="`${option.label}-${option.value}`"
                                                >
                                                    {{ option.label }}: {{ option.value }}
                                                    <template v-if="Number(option.price) > 0">
                                                        ({{ currencySymbol(detailsCurrency) }} {{ money(option.price) }})
                                                    </template>
                                                </small>
                                            </div>

                                            <span>{{ label(item.status) }}</span>
                                            <span>{{ currencySymbol(detailsCurrency) }} {{ money(item.unit_price) }}</span>
                                            <span>{{ trimQty(item.qty) }}</span>
                                            <span>{{ currencySymbol(detailsCurrency) }} {{ money(item.line_total || item.total) }}</span>
                                        </div>
                                    </div>
                                </section>
                            </main>

                            <aside class="tdo-details-side">
                                <section class="tdo-details-card">
                                    <h4>Order Summary</h4>

                                    <div class="tdo-summary-list">
                                        <div>
                                            <span>Currency</span>
                                            <strong>{{ detailsCurrency }}</strong>
                                        </div>

                                        <div>
                                            <span>Subtotal</span>
                                            <strong>{{ currencySymbol(detailsCurrency) }} {{ money(viewOrderData.subtotal) }}</strong>
                                        </div>

                                        <div>
                                            <span>Tax</span>
                                            <strong>{{ currencySymbol(detailsCurrency) }} {{ money(viewOrderData.tax_total) }}</strong>
                                        </div>

                                        <div class="tdo-summary-total">
                                            <span>Total</span>
                                            <strong>{{ currencySymbol(detailsCurrency) }} {{ money(viewOrderData.grand_total) }}</strong>
                                        </div>
                                    </div>
                                </section>

                                <section class="tdo-details-card">
                                    <h4>Notes</h4>
                                    <p class="tdo-side-text">{{ viewOrderData.notes || 'No notes available' }}</p>
                                </section>

                                <section class="tdo-details-card">
                                    <h4>Payments</h4>
                                    <p class="tdo-side-text">
                                        {{ viewOrderData.payments?.length ? 'Payment data loaded' : 'No payment data available' }}
                                    </p>
                                </section>
                            </aside>
                        </div>
                    </template>

                    <div v-else class="tdo-empty tdo-empty--modal">
                        <i class="bi bi-exclamation-circle"></i>
                        <strong>No order data found</strong>
                    </div>
                </div>
            </div>

            <!-- Cancel Modal -->
            <div
                v-if="showCancelModal"
                class="tdo-inner-backdrop"
                @click.self="closeCancel"
            >
                <div class="tdo-action-modal">
                    <header class="tdo-modal-header">
                        <div>
                            <p>Order Action</p>
                            <h3>Cancel Order</h3>
                        </div>

                        <button type="button" class="tdo-close" @click="closeCancel">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>

                    <p class="tdo-modal-text">
                        Select a cancellation reason and add a note if needed.
                    </p>

                    <div class="tdo-form-grid">
                        <div class="tdo-field">
                            <label>Order</label>
                            <input
                                type="text"
                                class="tdo-input"
                                :value="cancelTarget ? `#${cancelTarget.id}` : '-'"
                                readonly
                            />
                        </div>

                        <div class="tdo-field">
                            <label>Reason</label>
                            <select v-model="cancelForm.cancel_reason" class="tdo-input">
                                <option value="">Select reason</option>
                                <option
                                    v-for="reason in cancelReasons"
                                    :key="reason"
                                    :value="reason"
                                >
                                    {{ reason }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="tdo-field">
                        <label>Note</label>
                        <textarea
                            v-model="cancelForm.cancel_note"
                            class="tdo-input tdo-input--textarea"
                            placeholder="Optional note"
                        ></textarea>
                    </div>

                    <div class="tdo-modal-actions">
                        <button type="button" class="tdo-modal-btn tdo-modal-btn--ghost" @click="closeCancel">
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="tdo-modal-btn tdo-modal-btn--danger"
                            :disabled="cancelSubmitting || !cancelForm.cancel_reason"
                            @click="submitCancel"
                        >
                            {{ cancelSubmitting ? 'Cancelling...' : 'Submit' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Merge Modal -->
            <div
                v-if="showMergeModal"
                class="tdo-inner-backdrop"
                @click.self="closeMerge"
            >
                <div class="tdo-action-modal tdo-action-modal--merge">
                    <header class="tdo-modal-header">
                        <div>
                            <p>Table Action</p>
                            <h3>Merge Table</h3>
                        </div>

                        <button type="button" class="tdo-close" @click="closeMerge">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>

                    <p class="tdo-modal-text">
                        Select merge type and choose one or more tables to merge with
                        <strong>{{ table.name }}</strong>.
                    </p>

                    <div class="tdo-field">
                        <label>Merge Type</label>
                        <select v-model="mergeForm.type" class="tdo-input">
                            <option value="">Select type</option>
                            <option value="billing">Billing</option>
                            <option value="capacity">Capacity</option>
                        </select>
                    </div>

                    <div class="tdo-field">
                        <label>Tables</label>

                        <div v-if="mergeCandidateTables.length" class="tdo-merge-options">
                            <label
                                v-for="candidate in mergeCandidateTables"
                                :key="candidate.id"
                                class="tdo-merge-option"
                            >
                                <input
                                    type="checkbox"
                                    :value="candidate.id"
                                    v-model="mergeForm.member_table_ids"
                                />

                                <span>
                                    <strong>{{ candidate.name }}</strong>
                                    <small>{{ candidate.zone || '-' }} Â· {{ candidate.floor || '-' }}</small>
                                </span>

                                <em :class="'tdo-mini-status--' + String(candidate.status || '').toLowerCase()">
                                    {{ label(candidate.status) }}
                                </em>
                            </label>
                        </div>

                        <div v-else class="tdo-empty tdo-empty--small">
                            No tables available for merge.
                        </div>
                    </div>

                    <div class="tdo-modal-actions">
                        <button type="button" class="tdo-modal-btn tdo-modal-btn--ghost" @click="closeMerge">
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="tdo-modal-btn tdo-modal-btn--primary"
                            :disabled="mergeSubmitting || !canSubmitMerge"
                            @click="submitMerge"
                        >
                            {{ mergeSubmitting ? 'Merging...' : 'Merge Tables' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import SelectInput from '@/Components/SelectInput.vue'
import { currencySymbol } from '@/Utils/currency'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'

export default {
    name: 'TableDetailsOffcanvas',

    components: { SelectInput },

    props: {
        show: { type: Boolean, default: false },
        table: { type: Object, default: null },
        allTables: { type: Array, default: () => [] },
        sessionId: { type: [Number, String], default: null },
        waiterOptions: { type: Array, default: () => [] },
        activeCurrencyCode: { type: String, default: 'LKR' },
    },

    emits: [
        'close',
        'updated',
        'pay-order',
        'edit-order',
        'create-order',
    ],

    data() {
        return {
            waiterName: '',
            waiterSaving: false,
            statusSubmittingId: null,

            showDetailsModal: false,
            detailsLoading: false,
            viewOrderData: null,

            showCancelModal: false,
            cancelSubmitting: false,
            cancelTarget: null,
            cancelForm: {
                cancel_reason: '',
                cancel_note: '',
            },
            cancelReasons: [
                'Customer Request',
                'Item Not Available',
                'Duplicate Order',
                'Kitchen Delay',
                'Wrong Order',
            ],

            showMergeModal: false,
            mergeSubmitting: false,
            mergeForm: {
                type: '',
                member_table_ids: [],
            },
        }
    },

    computed: {
        orders() {
            return Array.isArray(this.table?.orders) ? this.table.orders : []
        },

        detailsCurrency() {
            return this.viewOrderData?.currency || this.viewOrderData?.currency_code || this.activeCurrencyCode
        },

        mergeCandidateTables() {
            if (!this.table?.id) return []

            return (this.allTables || []).filter((candidate) => {
                const status = String(candidate.status || '').toLowerCase()

                return Number(candidate.id) !== Number(this.table.id)
                    && ['available', 'occupied'].includes(status)
            })
        },

        canSubmitMerge() {
            return !!this.mergeForm.type && this.mergeForm.member_table_ids.length > 0
        },
    },

    watch: {
        show(value) {
            if (!value) this.resetInternalModals()
        },

        table: {
            immediate: true,
            deep: true,
            handler(table) {
                const orderWaiter = (table?.orders || []).find((order) => order.waiter_name)?.waiter_name
                this.waiterName = orderWaiter || ''
            },
        },
    },

    methods: {
        currencySymbol,
        close() {
            this.resetInternalModals()
            this.$emit('close')
        },

        resetInternalModals() {
            this.showDetailsModal = false
            this.detailsLoading = false
            this.viewOrderData = null

            this.showCancelModal = false
            this.cancelSubmitting = false
            this.cancelTarget = null
            this.cancelForm.cancel_reason = ''
            this.cancelForm.cancel_note = ''

            this.showMergeModal = false
            this.mergeSubmitting = false
            this.mergeForm.type = ''
            this.mergeForm.member_table_ids = []
        },

        label(value) {
            return String(value || '')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (char) => char.toUpperCase())
        },

        money(value) {
            return Number(value || 0).toFixed(3)
        },

        trimQty(value) {
            const numeric = Number(value || 0)
            return Number.isInteger(numeric) ? numeric : numeric.toFixed(3)
        },

        itemSummary(items) {
            return (items || [])
                .slice(0, 3)
                .map((item) => item.product_name)
                .join(', ')
        },

        formatTime(value) {
            if (!value) return '-'

            try {
                return new Date(value).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                })
            } catch (error) {
                return value
            }
        },

        formatDateTime(value) {
            if (!value) return '-'

            try {
                return new Date(value).toLocaleString()
            } catch (error) {
                return value
            }
        },

        statusIcon(status) {
            const map = {
                pending: 'bi bi-hourglass-split',
                confirmed: 'bi bi-check-lg',
                preparing: 'bi bi-bag',
                ready: 'bi bi-check2-circle',
                served: 'bi bi-box-seam',
                held: 'bi bi-pause-circle',
            }

            return map[String(status || '').toLowerCase()] || ''
        },

        nextStatusAction(order) {
            const status = String(order?.status || '').toLowerCase()

            if (status === 'pending' || status === 'confirmed') {
                return {
                    label: 'Prepare',
                    icon: 'bi bi-bag',
                    class: 'tdo-main-btn--prepare',
                    routeName: 'vendor.pos.kitchen.start-preparing',
                }
            }

            if (status === 'preparing') {
                return {
                    label: 'Ready',
                    icon: 'bi bi-check2-circle',
                    class: 'tdo-main-btn--ready',
                    routeName: 'vendor.pos.kitchen.mark-ready',
                }
            }

            if (status === 'ready') {
                return {
                    label: 'Complete',
                    icon: 'bi bi-check-circle',
                    class: 'tdo-main-btn--complete',
                    routeName: 'vendor.pos.kitchen.mark-served',
                }
            }

            return null
        },

        async saveWaiter() {
            if (this.waiterSaving) return

            const editableOrders = this.orders.filter((order) =>
                order?.id &&
                order.status !== 'cancelled' &&
                order.payment_status !== 'paid'
            )

            const message = this.waiterName
                ? 'Waiter has been successfully assigned to the table.'
                : 'Waiter has been successfully removed from the table.'

            if (!editableOrders.length) {
                this.toastSuccess(message)
                return
            }

            this.waiterSaving = true

            try {
                await Promise.all(
                    editableOrders.map((order) =>
                        axios.patch(
                            route('vendor.pos.orders.waiter', order.id),
                            { waiter_name: this.waiterName || null },
                            {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    Accept: 'application/json',
                                },
                            }
                        )
                    )
                )

                this.$emit('updated')
                this.toastSuccess(message)
            } catch (error) {
                const defaultError = this.waiterName ? 'Unable to assign waiter.' : 'Unable to remove waiter.'
                this.toastError(error?.response?.data?.message || defaultError)
            } finally {
                this.waiterSaving = false
            }
        },

        changeStatus(order) {
            const action = this.nextStatusAction(order)
            if (!action || !order?.id || this.statusSubmittingId) return

            this.statusSubmittingId = order.id

            router.patch(
                route(action.routeName, order.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.$emit('updated')
                        this.toastSuccess(`Order ${action.label.toLowerCase()} successfully.`)
                    },
                    onError: (errors) => {
                        this.toastError(this.firstError(errors) || 'Unable to update order status.')
                    },
                    onFinish: () => {
                        this.statusSubmittingId = null
                    },
                }
            )
        },

        async openOrderDetails(order) {
            if (!order?.id) return

            this.showDetailsModal = true
            this.detailsLoading = true
            this.viewOrderData = null

            try {
                const { data } = await axios.get(
                    route('vendor.pos.orders.details', order.id),
                    {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    }
                )

                this.viewOrderData = data.order || null
            } catch (error) {
                this.toastError(error?.response?.data?.message || 'Unable to load order details.')
            } finally {
                this.detailsLoading = false
            }
        },

        closeOrderDetails() {
            this.showDetailsModal = false
            this.detailsLoading = false
            this.viewOrderData = null
        },

        openCancel(order) {
            this.cancelTarget = order
            this.cancelForm.cancel_reason = ''
            this.cancelForm.cancel_note = ''
            this.showCancelModal = true
        },

        closeCancel() {
            this.showCancelModal = false
            this.cancelSubmitting = false
            this.cancelTarget = null
            this.cancelForm.cancel_reason = ''
            this.cancelForm.cancel_note = ''
        },

        async submitCancel() {
            if (!this.cancelTarget?.id || !this.cancelForm.cancel_reason) return

            this.cancelSubmitting = true

            try {
                const { data } = await axios.patch(
                    route('vendor.pos.orders.cancel-ticket', this.cancelTarget.id),
                    {
                        cancel_reason: this.cancelForm.cancel_reason,
                        cancel_note: this.cancelForm.cancel_note || null,
                    },
                    {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    }
                )

                this.closeCancel()
                this.$emit('updated')
                this.toastSuccess(data?.message || 'Order cancelled successfully.')
            } catch (error) {
                this.toastError(error?.response?.data?.message || 'Unable to cancel order.')
            } finally {
                this.cancelSubmitting = false
            }
        },

        openMerge() {
            this.mergeForm.type = ''
            this.mergeForm.member_table_ids = []
            this.showMergeModal = true
        },

        closeMerge() {
            this.showMergeModal = false
            this.mergeSubmitting = false
            this.mergeForm.type = ''
            this.mergeForm.member_table_ids = []
        },

        async submitMerge() {
            if (!this.sessionId || !this.table?.id || !this.canSubmitMerge || this.mergeSubmitting) return

            this.mergeSubmitting = true

            try {
                const { data } = await axios.post(
                    route('vendor.pos.tables.merge', this.sessionId),
                    {
                        primary_table_id: this.table.id,
                        member_table_ids: this.mergeForm.member_table_ids,
                        type: this.mergeForm.type,
                    },
                    {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    }
                )

                this.closeMerge()
                this.$emit('updated')
                this.toastSuccess(data?.message || 'Tables merged successfully.')
            } catch (error) {
                this.toastError(error?.response?.data?.message || 'Unable to merge tables.')
            } finally {
                this.mergeSubmitting = false
            }
        },

        printOrder(order) {
            if (!order?.id) return
            window.open(route('vendor.pos.orders.print', order.id) + '?copy=bill&print=1', '_blank')
        },

        firstError(errors) {
            if (errors?.general) return errors.general

            const first = Object.values(errors || {})[0]
            if (Array.isArray(first)) return first[0]
            if (typeof first === 'string') return first

            return ''
        },

        toastSuccess(message) {
            alertSuccess(message)
        },

        toastError(message) {
            alertError(message)
        },
    },
}
</script>

<style scoped>
.tdo-fade-enter-active,
.tdo-fade-leave-active {
    transition: opacity 0.18s ease;
}

.tdo-fade-enter-from,
.tdo-fade-leave-to {
    opacity: 0;
}

.tdo-backdrop {
    position: fixed;
    inset: 0;
    z-index: 3600;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: flex-end;
}

.tdo-panel {
    width: min(790px, 100%);
    height: 100%;
    background:
        radial-gradient(circle at top right, rgba(139, 92, 246, 0.08), transparent 34%),
        #ffffff;
    box-shadow: -28px 0 80px rgba(15, 23, 42, 0.24);
    padding: 26px;
    overflow: auto;
}

.tdo-panel::-webkit-scrollbar {
    width: 7px;
}

.tdo-panel::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.tdo-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.tdo-title-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tdo-title-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #f4f0ff;
    color: #8b5cf6;
    display: grid;
    place-items: center;
    font-size: 21px;
}

.tdo-title-wrap h3 {
    margin: 0;
    color: #243447;
    font-size: 19px;
    font-weight: 900;
}

.tdo-title-wrap p {
    margin: 4px 0 0;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 700;
}

.tdo-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tdo-close {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: all 0.16s ease;
}

.tdo-close:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.tdo-status {
    min-height: 28px;
    padding: 0 12px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 900;
}

.tdo-status--occupied {
    background: #ffe4e0;
    color: #ef4444;
}

.tdo-status--available {
    background: #dcfce7;
    color: #16a34a;
}

.tdo-status--merged {
    background: #dbeafe;
    color: #3b82f6;
}

.tdo-meta-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}

.tdo-meta-card {
    border: 1px solid #edf2f7;
    background: rgba(248, 250, 252, 0.75);
    border-radius: 14px;
    padding: 14px;
}

.tdo-meta-card span {
    display: block;
    margin-bottom: 7px;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 800;
}

.tdo-meta-card strong {
    color: #475569;
    font-size: 15px;
    font-weight: 900;
}

.tdo-card {
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid #edf2f7;
    border-radius: 18px;
    padding: 16px;
    margin-top: 14px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.035);
}

.tdo-label {
    display: block;
    margin-bottom: 8px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 900;
}

.tdo-hint {
    margin: 8px 0 0;
    color: #8b5cf6;
    font-size: 12px;
    font-weight: 800;
}

.tdo-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    color: #334155;
    font-size: 15px;
    font-weight: 900;
}

.tdo-section-title i {
    color: #06b6d4;
}

.tdo-merge-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tdo-merge-chip {
    min-height: 30px;
    padding: 0 14px;
    border-radius: 9px;
    background: #e5e7eb;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 900;
}

.tdo-merge-chip--primary {
    background: #dbeafe;
    color: #3b82f6;
}

.tdo-order-list {
    display: grid;
    gap: 12px;
}

.tdo-order-card {
    border: 1px solid #eef2f7;
    border-radius: 16px;
    background: #ffffff;
    padding: 15px;
}

.tdo-order-top {
    display: flex;
    justify-content: space-between;
    gap: 18px;
}

.tdo-order-time {
    display: block;
    margin-bottom: 10px;
    color: #9ca3af;
    font-size: 12px;
    font-weight: 900;
}

.tdo-order-title {
    display: block;
    margin-bottom: 5px;
    color: #475569;
    font-size: 15px;
    font-weight: 900;
}

.tdo-order-items {
    display: block;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 700;
}

.tdo-order-total {
    color: #475569;
    font-size: 15px;
    font-weight: 900;
    white-space: nowrap;
}

.tdo-badges {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 12px;
}

.tdo-chip {
    min-height: 27px;
    padding: 0 10px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 900;
}

.tdo-chip--pending,
.tdo-chip--confirmed {
    background: #fff5dc;
    color: #d97706;
}

.tdo-chip--preparing {
    background: #efe5ff;
    color: #8b5cf6;
}

.tdo-chip--ready,
.tdo-chip--paid {
    background: #dcfce7;
    color: #16a34a;
}

.tdo-chip--served {
    background: #d7f6f1;
    color: #0f766e;
}

.tdo-chip--unpaid,
.tdo-chip--cancelled {
    background: #ffe4e0;
    color: #ef4444;
}

.tdo-chip--partial {
    background: #fff5dc;
    color: #d97706;
}

.tdo-order-actions {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px dashed #e5e7eb;
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.tdo-action-left,
.tdo-action-right {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.tdo-icon-btn,
.tdo-main-btn,
.tdo-footer-btn,
.tdo-modal-btn {
    min-height: 38px;
    border: none;
    border-radius: 9px;
    padding: 0 13px;
    font-size: 13px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.16s ease;
}

.tdo-icon-btn {
    min-width: 40px;
    background: #eef0f3;
    color: #64748b;
}

.tdo-icon-btn:hover {
    background: #e2e8f0;
}

.tdo-icon-btn--print {
    min-width: 98px;
}

.tdo-main-btn--pay,
.tdo-main-btn--ready,
.tdo-main-btn--complete {
    background: #d8f5e5;
    color: #22b36f;
}

.tdo-main-btn--prepare {
    background: #efe5ff;
    color: #8b5cf6;
}

.tdo-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 18px;
}

.tdo-footer-btn--merge {
    background: #e5e7eb;
    color: #64748b;
}

.tdo-footer-btn--order {
    background: #dcfce7;
    color: #16a34a;
}

.tdo-empty {
    min-height: 120px;
    display: grid;
    place-items: center;
    text-align: center;
    color: #94a3b8;
}

.tdo-empty i {
    font-size: 34px;
    color: #cbd5e1;
}

.tdo-empty strong {
    display: block;
    color: #475569;
    font-size: 14px;
}

.tdo-empty span {
    display: block;
    font-size: 12px;
}

.tdo-empty--small {
    min-height: 70px;
    border: 1px dashed #e5e7eb;
    border-radius: 12px;
}

.tdo-empty--modal {
    min-height: 280px;
}

.tdo-inner-backdrop {
    position: fixed;
    inset: 0;
    z-index: 3900;
    background: rgba(15, 23, 42, 0.48);
    backdrop-filter: blur(4px);
    display: grid;
    place-items: center;
    padding: 18px;
}

.tdo-details-modal,
.tdo-action-modal {
    width: 100%;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 28px 90px rgba(15, 23, 42, 0.25);
    overflow: hidden;
}

.tdo-details-modal {
    max-width: 1320px;
    max-height: calc(100vh - 36px);
    overflow: auto;
}

.tdo-action-modal {
    max-width: 640px;
    padding: 22px;
}

.tdo-action-modal--merge {
    max-width: 720px;
}

.tdo-modal-header {
    padding: 20px 22px;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.tdo-action-modal .tdo-modal-header {
    padding: 0 0 16px;
}

.tdo-modal-header p {
    margin: 0 0 4px;
    color: #8b5cf6;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.tdo-modal-header h3 {
    margin: 0;
    color: #334155;
    font-size: 18px;
    font-weight: 900;
}

.tdo-modal-text {
    margin: 14px 0 18px;
    color: #64748b;
    font-size: 14px;
    line-height: 1.7;
}

.tdo-details-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 14px;
    padding: 14px;
}

.tdo-details-main,
.tdo-details-side {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.tdo-details-card {
    border: 1px solid #edf2f7;
    border-radius: 16px;
    background: #ffffff;
    padding: 16px;
}

.tdo-details-card h4 {
    margin: 0 0 14px;
    color: #334155;
    font-size: 15px;
    font-weight: 900;
}

.tdo-info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.tdo-info-grid--two {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.tdo-info-grid div {
    display: grid;
    gap: 6px;
}

.tdo-info-grid span {
    color: #94a3b8;
    font-size: 12px;
    font-weight: 800;
}

.tdo-info-grid strong {
    color: #475569;
    font-size: 13px;
    font-weight: 900;
    word-break: break-word;
}

.tdo-products {
    display: grid;
}

.tdo-products-head,
.tdo-products-row {
    display: grid;
    grid-template-columns: 2fr 0.8fr 0.9fr 0.5fr 0.9fr;
    gap: 12px;
    align-items: start;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
}

.tdo-products-head {
    color: #475569;
    font-size: 12px;
    font-weight: 900;
}

.tdo-products-row {
    color: #64748b;
    font-size: 13px;
}

.tdo-products-row div {
    display: grid;
    gap: 4px;
}

.tdo-products-row small {
    color: #94a3b8;
    font-size: 12px;
}

.tdo-summary-list {
    display: grid;
    gap: 12px;
}

.tdo-summary-list > div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #e5e7eb;
    color: #64748b;
    font-size: 13px;
}

.tdo-summary-total span,
.tdo-summary-total strong {
    color: #3b82f6;
    font-weight: 900;
}

.tdo-side-text {
    margin: 0;
    color: #64748b;
    text-align: center;
    padding: 18px 0;
}

.tdo-skeleton-wrap {
    padding: 22px;
}

.tdo-skeleton {
    height: 20px;
    border-radius: 10px;
    background: linear-gradient(90deg, #eef2f7, #ffffff, #eef2f7);
    background-size: 200% 100%;
    animation: tdoSkeleton 1.1s infinite;
    margin-bottom: 12px;
}

@keyframes tdoSkeleton {
    100% {
        background-position: -200% 0;
    }
}

.tdo-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
}

.tdo-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 14px;
}

.tdo-field label {
    color: #475569;
    font-size: 13px;
    font-weight: 900;
}

.tdo-input {
    width: 100%;
    min-height: 44px;
    border: 1px solid #d8dee6;
    border-radius: 11px;
    background: #ffffff;
    color: #334155;
    padding: 0 14px;
    outline: none;
    transition: all 0.16s ease;
}

.tdo-input:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.12);
}

.tdo-input--textarea {
    min-height: 112px;
    resize: none;
    padding: 12px 14px;
}

.tdo-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 16px;
}

.tdo-modal-btn--ghost {
    background: #f1f5f9;
    color: #64748b;
}

.tdo-modal-btn--danger {
    background: #ef4444;
    color: #ffffff;
}

.tdo-modal-btn--primary {
    background: #8b5cf6;
    color: #ffffff;
}

.tdo-merge-options {
    display: grid;
    gap: 10px;
    max-height: 300px;
    overflow: auto;
    padding-right: 4px;
}

.tdo-merge-option {
    min-height: 58px;
    border: 1px solid #edf2f7;
    border-radius: 14px;
    padding: 10px 12px;
    display: grid;
    grid-template-columns: 22px 1fr auto;
    gap: 10px;
    align-items: center;
    cursor: pointer;
    transition: all 0.16s ease;
}

.tdo-merge-option:hover {
    border-color: #8b5cf6;
    background: #faf7ff;
}

.tdo-merge-option span {
    display: grid;
    gap: 3px;
}

.tdo-merge-option strong {
    color: #334155;
    font-size: 14px;
}

.tdo-merge-option small {
    color: #94a3b8;
    font-size: 12px;
}

.tdo-merge-option em {
    font-style: normal;
    font-size: 11px;
    font-weight: 900;
    border-radius: 999px;
    padding: 5px 9px;
}

.tdo-mini-status--available {
    background: #dcfce7;
    color: #16a34a;
}

.tdo-mini-status--occupied {
    background: #ffe4e0;
    color: #ef4444;
}

@media (max-width: 900px) {
    .tdo-details-layout {
        grid-template-columns: 1fr;
    }

    .tdo-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .tdo-products-head,
    .tdo-products-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 720px) {
    .tdo-panel {
        padding: 18px;
    }

    .tdo-meta-grid,
    .tdo-form-grid,
    .tdo-info-grid,
    .tdo-info-grid--two {
        grid-template-columns: 1fr;
    }

    .tdo-order-top,
    .tdo-order-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .tdo-badges {
        justify-content: flex-start;
    }

    .tdo-footer,
    .tdo-modal-actions {
        flex-direction: column;
    }
}
</style>
