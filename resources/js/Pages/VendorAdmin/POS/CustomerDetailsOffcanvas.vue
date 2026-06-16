<template>
    <teleport to="body">
        <transition name="pcd-fade">
            <div v-if="show" class="pcd-backdrop" @click="$emit('close')"></div>
        </transition>

        <transition name="pcd-slide">
            <aside v-if="show" class="pcd-panel" role="dialog" aria-modal="true" aria-label="Customer details">
                <header class="pcd-header">
                    <div class="pcd-header__main">
                        <div class="pcd-avatar">
                            <img v-if="details?.customer?.avatar_url" :src="details.customer.avatar_url" :alt="details.customer.name" />
                            <span v-else>{{ customerInitial }}</span>
                        </div>

                        <div class="pcd-header__copy">
                            <strong>{{ details?.customer?.name || 'Customer' }}</strong>
                            <span>{{ details?.customer?.phone || 'No phone' }}</span>
                            <small>{{ details?.customer?.email || 'No email' }}</small>
                        </div>
                    </div>

                    <div class="pcd-header__actions">
                        <button
                            v-if="details?.customer?.edit_url"
                            type="button"
                            class="pcd-icon-btn"
                            title="Edit customer"
                            @click="openEdit"
                        >
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <button type="button" class="pcd-icon-btn" title="Close" @click="$emit('close')">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </header>

                <div class="pcd-body">
                    <div v-if="loading" class="pcd-state">
                        <span class="spinner-border spinner-border-sm"></span>
                        <span>Loading customer details...</span>
                    </div>

                    <template v-else-if="details">
                        <section class="pcd-hero">
                            <div class="pcd-stat pcd-stat--accent">
                                <span>Outstanding Credit</span>
                                <strong>{{ money(outstandingCredit) }}</strong>
                                <small>{{ money(creditPaidTotal) }} received</small>
                            </div>

                            <div class="pcd-stat">
                                <span>Credit Sales</span>
                                <strong>{{ money(creditSalesTotal) }}</strong>
                                <small>{{ stats.orders_count || 0 }} POS orders</small>
                            </div>

                            <div class="pcd-stat">
                                <span>Receipts</span>
                                <strong>{{ stats.receipts_count || 0 }}</strong>
                                <small>{{ stats.last_sale_at || 'No sales yet' }}</small>
                            </div>

                            <div class="pcd-stat">
                                <span>Loyalty</span>
                                <strong>{{ stats.loyalty_points || 0 }} pts</strong>
                                <small>{{ stats.loyalty_tier || 'Member' }}</small>
                            </div>
                        </section>

                        <nav class="pcd-tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.value"
                                type="button"
                                class="pcd-tab"
                                :class="{ 'pcd-tab--active': activeTab === tab.value }"
                                @click="activeTab = tab.value"
                            >
                                <i :class="tab.icon"></i>
                                <span>{{ tab.label }}</span>
                            </button>
                        </nav>

                        <section v-if="activeTab === 'overview'" class="pcd-section">
                            <div class="pcd-grid">
                                <article class="pcd-card">
                                    <span>Name</span>
                                    <strong>{{ details.customer.name || '-' }}</strong>
                                </article>
                                <article class="pcd-card">
                                    <span>Customer Type</span>
                                    <strong>{{ pretty(details.customer.customer_type) }}</strong>
                                </article>
                                <article class="pcd-card">
                                    <span>Phone</span>
                                    <strong>{{ details.customer.phone || '-' }}</strong>
                                </article>
                                <article class="pcd-card">
                                    <span>Email</span>
                                    <strong>{{ details.customer.email || '-' }}</strong>
                                </article>
                                <article class="pcd-card">
                                    <span>Username</span>
                                    <strong>{{ details.customer.username || '-' }}</strong>
                                </article>
                                <article class="pcd-card">
                                    <span>Created</span>
                                    <strong>{{ details.customer.created_at || '-' }}</strong>
                                </article>
                                <article class="pcd-card pcd-card--wide">
                                    <span>Notes</span>
                                    <strong>{{ details.customer.note || 'No notes added.' }}</strong>
                                </article>
                            </div>
                        </section>

                        <section v-else-if="activeTab === 'credit'" class="pcd-section pcd-section--stack">
                            <article v-if="canCollectCredit" class="pcd-form-card">
                                <div class="pcd-section-head">
                                    <div>
                                        <h4>Collect Credit Payment</h4>
                                        <p>Record a customer payment and print a receipt from the same place.</p>
                                    </div>
                                </div>

                                <div class="pcd-quick-row">
                                    <button type="button" @click="setCreditAmount(outstandingCredit)">
                                        Full Due
                                    </button>
                                    <button type="button" @click="setCreditAmount(outstandingCredit / 2)">
                                        Half Due
                                    </button>
                                </div>

                                <div class="pcd-form-grid">
                                    <label>
                                        <span>Amount</span>
                                        <input v-model="paymentForm.amount" type="number" min="0" step="0.01" />
                                    </label>

                                    <label>
                                        <span>Method</span>
                                        <select v-model="paymentForm.payment_method">
                                            <option
                                                v-for="method in details.payment_methods || []"
                                                :key="method.value"
                                                :value="method.value"
                                            >
                                                {{ method.label }}
                                            </option>
                                        </select>
                                    </label>

                                    <label>
                                        <span>Reference</span>
                                        <input v-model="paymentForm.reference" type="text" placeholder="Optional ref" />
                                    </label>

                                    <label class="pcd-form-grid__wide">
                                        <span>Notes</span>
                                        <textarea v-model="paymentForm.notes" rows="3" placeholder="Optional notes"></textarea>
                                    </label>
                                </div>

                                <div class="pcd-form-actions">
                                    <button
                                        type="button"
                                        class="pcd-main-btn pcd-main-btn--ghost"
                                        :disabled="paymentSubmitting || !canSubmitPayment"
                                        @click="submitCreditPayment(false)"
                                    >
                                        {{ paymentSubmitting ? 'Saving...' : 'Save Payment' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="pcd-main-btn"
                                        :disabled="paymentSubmitting || !canSubmitPayment"
                                        @click="submitCreditPayment(true)"
                                    >
                                        {{ paymentSubmitting ? 'Saving...' : 'Pay & Print Receipt' }}
                                    </button>
                                </div>
                            </article>

                            <article v-else class="pcd-list-card">
                                <div class="pcd-section-head">
                                    <div>
                                        <h4>Credit Overview</h4>
                                        <p>Credit collection is available from the POS view where an active register session is open.</p>
                                    </div>
                                </div>
                            </article>

                            <article class="pcd-list-card">
                                <div class="pcd-section-head">
                                    <div>
                                        <h4>Credit Sales</h4>
                                        <p>Orders completed on customer credit.</p>
                                    </div>
                                </div>

                                <div v-if="details.credit_sales?.length" class="pcd-list">
                                    <div v-for="sale in details.credit_sales" :key="`sale-${sale.id}`" class="pcd-row">
                                        <div>
                                            <strong>{{ sale.invoice_no || sale.transaction_uuid || `Sale #${sale.transaction_id}` }}</strong>
                                            <span>{{ sale.branch_name || '-' }}</span>
                                            <small>{{ sale.paid_at || '-' }}</small>
                                        </div>
                                        <strong>{{ money(sale.amount) }}</strong>
                                    </div>
                                </div>

                                <div v-else class="pcd-empty">No credit sales found for this customer.</div>
                            </article>

                            <article class="pcd-list-card">
                                <div class="pcd-section-head">
                                    <div>
                                        <h4>Credit Payments</h4>
                                        <p>Receipts collected against the customer credit balance.</p>
                                    </div>
                                </div>

                                <div v-if="details.credit_payments?.length" class="pcd-list">
                                    <div v-for="payment in details.credit_payments" :key="`payment-${payment.id}`" class="pcd-row">
                                        <div>
                                            <strong>{{ payment.receipt_no }}</strong>
                                            <span>{{ pretty(payment.payment_method) }}</span>
                                            <small>{{ payment.received_at || '-' }}</small>
                                        </div>
                                        <div class="pcd-row__right">
                                            <strong>{{ money(payment.amount) }}</strong>
                                            <button type="button" class="pcd-link-btn" @click="openPrint(payment.print_url)">
                                                Receipt
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="pcd-empty">No credit payments have been received yet.</div>
                            </article>
                        </section>

                        <section v-else class="pcd-section">
                            <article class="pcd-list-card">
                                <div class="pcd-section-head">
                                    <div>
                                        <h4>Receipts</h4>
                                        <p>Recent customer invoices and thermal print access.</p>
                                    </div>
                                </div>

                                <div v-if="details.receipts?.length" class="pcd-list">
                                    <div v-for="receipt in details.receipts" :key="receipt.id" class="pcd-row">
                                        <div>
                                            <strong>{{ receipt.invoice_no }}</strong>
                                            <span>{{ pretty(receipt.status) }}</span>
                                            <small>{{ receipt.issued_at || '-' }}</small>
                                        </div>
                                        <div class="pcd-row__right">
                                            <strong>{{ money(receipt.total) }}</strong>
                                            <button type="button" class="pcd-link-btn" @click="openPrint(receipt.print_url)">
                                                Print
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="pcd-empty">No receipts found for this customer.</div>
                            </article>
                        </section>
                    </template>

                    <div v-else class="pcd-empty">Select a customer to view details.</div>
                </div>
            </aside>
        </transition>
    </teleport>
</template>

<script>
import axios from 'axios'
import { currencySymbol } from '@/Utils/currency'

export default {
    name: 'CustomerDetailsOffcanvas',
    props: {
        show: { type: Boolean, default: false },
        customerId: { type: [Number, String], default: null },
        sessionId: { type: [Number, String], default: null },
        currencyCode: { type: String, default: 'LKR' },
        mode: { type: String, default: 'pos' },
    },
    emits: ['close', 'toast', 'updated'],
    data() {
        return {
            loading: false,
            paymentSubmitting: false,
            activeTab: 'overview',
            details: null,
            paymentForm: {
                amount: '',
                payment_method: 'cash',
                reference: '',
                notes: '',
            },
            tabs: [
                { value: 'overview', label: 'Overview', icon: 'bi bi-person-badge' },
                { value: 'credit', label: 'Credit', icon: 'bi bi-wallet2' },
                { value: 'receipts', label: 'Receipts', icon: 'bi bi-receipt' },
            ],
        }
    },
    computed: {
        customerInitial() {
            return String(this.details?.customer?.name || 'C').trim().charAt(0).toUpperCase() || 'C'
        },
        stats() {
            return this.details?.stats || {}
        },
        outstandingCredit() {
            return Number(this.stats.outstanding_credit || 0)
        },
        creditSalesTotal() {
            return Number(this.stats.credit_sales_total || 0)
        },
        creditPaidTotal() {
            return Number(this.stats.credit_payments_total || 0)
        },
        canSubmitPayment() {
            const amount = Number(this.paymentForm.amount || 0)
            return this.canCollectCredit && amount > 0 && amount <= this.outstandingCredit + 0.0001
        },
        canCollectCredit() {
            return this.mode === 'pos' && !!this.sessionId
        },
    },
    watch: {
        show: {
            immediate: true,
            handler(value) {
                if (value && this.customerId) {
                    this.loadDetails()
                }
            },
        },
        customerId() {
            if (this.show && this.customerId) {
                this.loadDetails()
            }
        },
    },
    methods: {
        async loadDetails() {
            if (!this.customerId) return

            this.loading = true
            this.activeTab = 'overview'

            try {
                const url = this.mode === 'pos'
                    ? route('vendor.pos.customers.details', {
                        session: this.sessionId,
                        customer: this.customerId,
                    })
                    : route('vendor.customers.view', this.customerId)

                const { data } = await axios.get(url)

                this.details = data
                this.paymentForm.amount = Number(data?.stats?.outstanding_credit || 0) > 0
                    ? Number(data.stats.outstanding_credit).toFixed(2)
                    : ''
                this.paymentForm.payment_method = data?.payment_methods?.[0]?.value || 'cash'
                this.paymentForm.reference = ''
                this.paymentForm.notes = ''
            } catch (error) {
                this.$emit('toast', {
                    type: 'error',
                    message: error?.response?.data?.message || 'Unable to load customer details.',
                })
            } finally {
                this.loading = false
            }
        },
        async submitCreditPayment(printReceipt) {
            if (!this.canSubmitPayment || this.paymentSubmitting || !this.sessionId) return

            this.paymentSubmitting = true

            try {
                const { data } = await axios.post(route('vendor.pos.customers.credit-payments.store', {
                    session: this.sessionId,
                    customer: this.customerId,
                }), {
                    amount: this.paymentForm.amount,
                    payment_method: this.paymentForm.payment_method,
                    reference: this.paymentForm.reference || null,
                    notes: this.paymentForm.notes || null,
                    print_receipt: printReceipt,
                })

                this.$emit('toast', {
                    type: 'success',
                    message: data?.message || 'Customer credit payment recorded successfully.',
                })
                this.$emit('updated')
                await this.loadDetails()

                if (printReceipt && data?.print_url) {
                    this.openPrint(data.print_url)
                }
            } catch (error) {
                this.$emit('toast', {
                    type: 'error',
                    message: error?.response?.data?.message || error?.response?.data?.errors?.amount?.[0] || 'Unable to save customer credit payment.',
                })
            } finally {
                this.paymentSubmitting = false
            }
        },
        setCreditAmount(amount) {
            this.paymentForm.amount = Math.max(0, Number(amount || 0)).toFixed(2)
        },
        openPrint(url) {
            if (!url) return
            window.open(url, '_blank')
        },
        openEdit() {
            if (this.details?.customer?.edit_url) {
                window.location.href = this.details.customer.edit_url
            }
        },
        money(value) {
            return `${currencySymbol(this.currencyCode)} ${Number(value || 0).toFixed(2)}`
        },
        pretty(value) {
            return String(value || '-')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (char) => char.toUpperCase())
        },
    },
}
</script>

<style scoped>
.pcd-fade-enter-active,
.pcd-fade-leave-active {
    transition: opacity 0.18s ease;
}

.pcd-fade-enter-from,
.pcd-fade-leave-to {
    opacity: 0;
}

.pcd-slide-enter-active,
.pcd-slide-leave-active {
    transition: transform 0.22s ease, opacity 0.22s ease;
}

.pcd-slide-enter-from,
.pcd-slide-leave-to {
    transform: translateX(24px);
    opacity: 0;
}

.pcd-backdrop {
    position: fixed;
    inset: 0;
    z-index: 4200;
    background: rgba(15, 23, 42, 0.42);
}

.pcd-panel {
    position: fixed;
    top: 0;
    right: 0;
    z-index: 4201;
    width: min(760px, 100vw);
    height: 100vh;
    background:
        linear-gradient(180deg, #fff8ef 0%, #ffffff 16%),
        #ffffff;
    box-shadow: -20px 0 60px rgba(15, 23, 42, 0.18);
    display: flex;
    flex-direction: column;
}

.pcd-header {
    padding: 22px 22px 18px;
    border-bottom: 1px solid #f1e4cf;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.pcd-header__main {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.pcd-avatar {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #f59e0b, #fb7185);
    color: #fff;
    display: grid;
    place-items: center;
    font-size: 24px;
    font-weight: 800;
    overflow: hidden;
    flex: 0 0 auto;
}

.pcd-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pcd-header__copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.pcd-header__copy strong {
    color: #1f2937;
    font-size: 22px;
    line-height: 1.1;
}

.pcd-header__copy span,
.pcd-header__copy small {
    color: #6b7280;
    font-size: 13px;
    line-height: 1.4;
}

.pcd-header__actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pcd-icon-btn,
.pcd-link-btn,
.pcd-main-btn,
.pcd-tabs button,
.pcd-quick-row button {
    cursor: pointer;
}

.pcd-icon-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #ead7ba;
    border-radius: 10px;
    background: #fff;
    color: #7c5f37;
}

.pcd-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 20px 22px 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.pcd-state,
.pcd-empty {
    min-height: 180px;
    border: 1px dashed #e7d7bb;
    border-radius: 16px;
    background: #fffdf9;
    color: #7c6a53;
    display: grid;
    place-items: center;
    text-align: center;
    padding: 20px;
}

.pcd-hero,
.pcd-grid,
.pcd-form-grid {
    display: grid;
    gap: 12px;
}

.pcd-hero {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.pcd-stat,
.pcd-card,
.pcd-form-card,
.pcd-list-card {
    border: 1px solid #efe0c8;
    border-radius: 16px;
    background: #fff;
}

.pcd-stat {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pcd-stat--accent {
    background: linear-gradient(135deg, #fff4df 0%, #fff 100%);
    border-color: #f2c57c;
}

.pcd-stat span,
.pcd-card span {
    color: #8a7352;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.pcd-stat strong {
    color: #1f2937;
    font-size: 20px;
    line-height: 1.1;
}

.pcd-card strong {
    color: #1f2937;
    font-size: 14px;
    line-height: 1.35;
    font-weight: 700;
}

.pcd-stat small {
    color: #6b7280;
    font-size: 12px;
}

.pcd-tabs {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.pcd-tab {
    min-height: 46px;
    border: 1px solid #efdcbc;
    border-radius: 12px;
    background: #fffaf3;
    color: #7c6242;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.pcd-tab--active {
    background: #f59e0b;
    color: #fff;
    border-color: #f59e0b;
}

.pcd-section--stack {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.pcd-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.pcd-card {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pcd-card--wide,
.pcd-form-grid__wide {
    grid-column: 1 / -1;
}

.pcd-form-card,
.pcd-list-card {
    padding: 16px;
}

.pcd-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.pcd-section-head h4 {
    margin: 0;
    color: #1f2937;
    font-size: 18px;
}

.pcd-section-head p {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.pcd-quick-row {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
}

.pcd-quick-row button {
    min-height: 36px;
    padding: 0 14px;
    border: 1px solid #efdcbc;
    border-radius: 10px;
    background: #fff8ef;
    color: #875d1f;
    font-size: 12px;
    font-weight: 800;
}

.pcd-form-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.pcd-form-grid label {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.pcd-form-grid label span {
    color: #6b7280;
    font-size: 12px;
    font-weight: 700;
}

.pcd-form-grid input,
.pcd-form-grid select,
.pcd-form-grid textarea {
    width: 100%;
    min-height: 42px;
    border: 1px solid #dfd1ba;
    border-radius: 12px;
    background: #fff;
    color: #1f2937;
    font-size: 14px;
    padding: 10px 12px;
    outline: none;
}

.pcd-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 14px;
}

.pcd-main-btn {
    min-height: 44px;
    padding: 0 16px;
    border: none;
    border-radius: 12px;
    background: #f59e0b;
    color: #fff;
    font-size: 13px;
    font-weight: 800;
}

.pcd-main-btn--ghost {
    background: #fff3dc;
    color: #8b5e1a;
}

.pcd-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.pcd-row {
    border: 1px solid #f0e4d0;
    border-radius: 14px;
    padding: 13px 14px;
    background: #fffdfa;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.pcd-row strong {
    display: block;
    color: #1f2937;
    font-size: 14px;
}

.pcd-row span,
.pcd-row small {
    display: block;
    color: #6b7280;
    font-size: 12px;
    line-height: 1.4;
}

.pcd-row__right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.pcd-link-btn {
    border: none;
    background: transparent;
    color: #ea580c;
    font-size: 12px;
    font-weight: 800;
    padding: 0;
}

@media (max-width: 900px) {
    .pcd-panel {
        width: 100vw;
    }

    .pcd-hero,
    .pcd-grid,
    .pcd-form-grid,
    .pcd-tabs {
        grid-template-columns: 1fr;
    }

    .pcd-form-actions,
    .pcd-quick-row {
        flex-direction: column;
    }
}
</style>
