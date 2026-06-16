<template>
    <transition name="fp-fade">
        <div v-if="show" class="fp-backdrop" @click.self="close">
            <div class="fp-modal" role="dialog" aria-modal="true" aria-labelledby="finalize-payment-title">
                <div class="fp-main">
                    <div class="fp-header">
                        <div>
                            <p class="fp-eyebrow">Point of Sale</p>
                            <h2 id="finalize-payment-title">Finalize Payment</h2>
                        </div>

                        <button type="button" class="fp-close-btn" aria-label="Close payment modal" @click="close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="fp-section">
                        <label class="fp-label">Payment Mode</label>

                        <div class="fp-mode-grid">
                            <button type="button" class="fp-mode-card"
                                :class="{ 'fp-mode-card--active': paymentMode === 'full' }" @click="setMode('full')">
                                <span>Full Payment</span>
                                <i class="bi"
                                    :class="paymentMode === 'full' ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                            </button>

                            <button type="button" class="fp-mode-card"
                                :class="{ 'fp-mode-card--active': paymentMode === 'partial' }"
                                @click="setMode('partial')">
                                <span>Partial Payment</span>
                                <i class="bi"
                                    :class="paymentMode === 'partial' ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="fp-section">
                        <div class="fp-section-head">
                            <label class="fp-label">Payments</label>

                            <button v-if="paymentMode === 'partial'" type="button" class="fp-small-add"
                                @click="addPaymentRow">
                                <i class="bi bi-plus-lg"></i>
                                <span>Add Payment</span>
                            </button>
                        </div>

                        <div class="fp-payments">
                            <div v-for="(row, index) in payments" :key="index" class="fp-payment-row">
                                <SelectInput :id="'payment_method_' + index" v-model="row.payment_method" label=""
                                    :options="paymentMethods" valueKey="value" labelKey="label"
                                    placeholder="Select Method" :clearable="false"
                                    @change="onPaymentMethodChange(row)" />

                                <div v-if="row.payment_method === 'gift_card'" class="gift-card-input-container">
                                    <div class="gift-card-input-wrapper">
                                        <input v-model="row.gift_card_code" type="text"
                                            class="fp-input fp-gift-card-input" placeholder="Gift Card Code"
                                            @input="row.gift_card_applied = false; row.amount = money(0); row.gift_card_error = ''; row.gift_card_success = '';"
                                            @keydown.enter.prevent="lookupAndApplyGiftCard(row, index)" />
                                        <button type="button" class="btn-apply-gift-card"
                                            @click="lookupAndApplyGiftCard(row, index)"
                                            :disabled="row.loading_gift_card">
                                            <i v-if="row.loading_gift_card"
                                                class="spinner-border spinner-border-sm text-warning"
                                                style="width: 14px; height: 14px; display: block;"></i>
                                            <i v-else class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    <div v-if="row.gift_card_error" class="fp-error">{{ row.gift_card_error }}</div>
                                    <div v-if="row.gift_card_success" class="fp-success">{{ row.gift_card_success }}
                                    </div>
                                </div>

                                <input :value="row.amount" type="text" inputmode="decimal" class="fp-input"
                                    :placeholder="paymentMode === 'full' && index === 0 ? `${currencySymbol(currencyCode)} ${money(grandTotal)}` : 'Amount'"
                                    @focus="setActivePayment(index)"
                                    @input="onAmountInput(index, $event.target.value)" />

                                <input v-model="row.transaction_id" type="text" class="fp-input"
                                    placeholder="Transaction ID" />

                                <div class="fp-row-actions">
                                    <button type="button" class="fp-icon-btn fp-icon-btn--danger"
                                        :disabled="payments.length === 1" @click="removePaymentRow(index)">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                    <button v-if="index === payments.length - 1 && paymentMode === 'partial'"
                                        type="button" class="fp-icon-btn fp-icon-btn--add" @click="addPaymentRow">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 
                    <div v-if="usesRoomCharge" class="fp-section fp-room-charge">
                        <div class="fp-section-head">
                            <label class="fp-label">Charge to Room</label>
                            <button type="button" class="fp-small-add" :disabled="pmsLoading" @click="loadCheckedInGuests(true)">
                                <i class="bi bi-arrow-clockwise"></i>
                                <span>{{ pmsLoading ? 'Loading' : 'Refresh' }}</span>
                            </button>
                        </div>

                        <select v-model="selectedGuestKey" class="fp-input" :disabled="pmsLoading">
                            <option value="">{{ pmsLoading ? 'Loading checked-in guests...' : 'Select checked-in guest / room' }}</option>
                            <option v-for="guest in checkedInGuests" :key="guestOptionKey(guest)" :value="guestOptionKey(guest)">
                                {{ guestOptionLabel(guest) }}
                            </option>
                        </select>

                        <div v-if="selectedGuest" class="fp-pms-details">
                            <div><span>Room Key</span><strong>{{ guestRoomKeyLabel(selectedGuest) }}</strong></div>
                            <div><span>Room Type</span><strong>{{ guestRoomTypeLabel(selectedGuest) }}</strong></div>
                            <div><span>Booking ID</span><strong>{{ guestBookingLabel(selectedGuest) }}</strong></div>
                            <div><span>Currency</span><strong>{{ guestCurrencyCode(selectedGuest) || '-' }}</strong></div>
                        </div>

                        <div v-if="pmsCurrencyError" class="fp-error">{{ pmsCurrencyError }}</div>
                        <div v-if="pmsError" class="fp-error">{{ pmsError }}</div>
                    </div> -->

                    <div class="fp-grid">
                        <div class="fp-keypad-wrap">
                            <label class="fp-label">Numeric Keypad</label>

                            <div class="fp-keypad">
                                <button v-for="key in keypadKeys" :key="key" type="button" class="fp-key"
                                    @click="pressKey(key)">
                                    {{ key }}
                                </button>

                                <button type="button" class="fp-key fp-key--clear" @click="clearActiveInput">
                                    C
                                </button>
                            </div>

                            <button type="button" class="fp-calc-btn" @click="openCalculator">
                                <i class="bi bi-calculator"></i>
                                <span>Calculator</span>
                            </button>
                        </div>

                        <div class="fp-quick-wrap">
                            <label class="fp-label">Quick Pay</label>

                            <div class="fp-quick-grid">
                                <button v-for="amount in quickPayAmounts" :key="amount" type="button"
                                    class="fp-quick-btn"
                                    :class="{ 'fp-quick-btn--active': Number(customerGivenAmount) === amount }"
                                    @click="addQuickPay(amount)">
                                    +{{ currencySymbol(currencyCode) }} {{ money(amount) }}
                                </button>
                            </div>

                            <div class="fp-extra-fields">
                                <input :value="displayCustomerGiven" type="text" inputmode="decimal" class="fp-input"
                                    placeholder="Customer Given Amount" @focus="setCustomerGivenActive"
                                    @input="onCustomerGivenInput($event.target.value)" />

                                <input :value="`${currencySymbol(currencyCode)} ${money(changeReturn)}`" type="text"
                                    class="fp-input" placeholder="Change Return" readonly />
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="fp-summary">
                    <div class="fp-summary-inner">
                        <h3>Order Summary</h3>

                        <div class="fp-summary-list">
                            <div>
                                <span>Total Products</span>
                                <strong>{{ totalProducts }}</strong>
                            </div>

                            <div>
                                <span>Sub Total</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(subtotal) }}</strong>
                            </div>

                            <div>
                                <span>Discount</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(discountTotal) }}</strong>
                            </div>

                            <div>
                                <span>Total Tax</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(taxTotal) }}</strong>
                            </div>

                            <div class="fp-summary-list__grand">
                                <span>Grand Total</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(grandTotal) }}</strong>
                            </div>

                            <div v-if="giftCardTotal > 0" class="fp-summary-list__deduction">
                                <span>Gift Card Deduction</span>
                                <strong>- {{ currencySymbol(currencyCode) }} {{ money(giftCardTotal) }}</strong>
                            </div>

                            <div v-if="giftCardTotal > 0" class="fp-summary-list__grand-updated">
                                <span>Updated Total</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(grandTotal - giftCardTotal)
                                }}</strong>
                            </div>

                            <!-- <div>
                                <span>Total Paid</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(0) }}</strong>
                            </div> -->

                            <div>
                                <span>Current Total Paid</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(currentTotalPaid) }}</strong>
                            </div>

                            <div>
                                <span>Due Amount</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(dueAmount) }}</strong>
                            </div>

                            <div>
                                <span>Customer Given Amount</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(customerGivenAmount) }}</strong>
                            </div>

                            <div class="fp-summary-list__change">
                                <span>Change Return</span>
                                <strong>{{ currencySymbol(currencyCode) }} {{ money(changeReturn) }}</strong>
                            </div>
                        </div>

                        <div class="fp-actions">
                            <button type="button" class="fp-action fp-action--cancel" @click="close">
                                <i class="bi bi-x-lg"></i>
                                <span>Cancel</span>
                            </button>

                            <button type="button" class="fp-action fp-action--submit"
                                :disabled="submitting || !canSubmit" @click="submit(false)">
                                <i class="bi bi-check-lg"></i>
                                <span>{{ submitting ? 'Submitting...' : 'Submit' }}</span>
                            </button>

                            <button type="button" class="fp-action fp-action--print"
                                :disabled="submitting || !canSubmit" @click="submit(true)">
                                <i class="bi bi-printer"></i>
                                <span>{{ submitting ? 'Submitting...' : 'Submit & Print Bill' }}</span>
                            </button>
                        </div>
                    </div>
                </aside>

                <div v-if="showCalculator" class="fp-calc-backdrop" @click.self="closeCalculator">
                    <div class="fp-calc-modal">
                        <div class="fp-calc-screen">
                            <div class="fp-calc-expression">{{ calculatorExpression || '0' }}</div>
                            <div class="fp-calc-result">{{ calculatorPreview }}</div>
                        </div>

                        <div class="fp-calc-grid">
                            <button v-for="key in calculatorKeys" :key="key" type="button" class="fp-calc-key"
                                :class="calculatorKeyClass(key)" @click="pressCalculatorKey(key)">
                                {{ key }}
                            </button>
                        </div>

                        <div class="fp-calc-actions">
                            <button type="button" class="fp-calc-action fp-calc-action--ghost" @click="closeCalculator">
                                Cancel
                            </button>

                            <button type="button" class="fp-calc-action fp-calc-action--primary"
                                @click="applyCalculatorResult">
                                Submit Result
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import { router } from '@inertiajs/vue3'
import { currencySymbol, normalizeCurrencyCode } from '@/Utils/currency'
import SelectInput from '@/Components/SelectInput.vue'

export default {
    name: 'FinalizePaymentModal',
    components: { SelectInput },

    props: {
        show: { type: Boolean, default: false },
        session: { type: Object, default: null },
        currencyCode: { type: String, default: 'LKR' },
        fireAfterPayment: { type: Boolean, default: false },
        firePayload: { type: Object, default: () => ({}) },
        selectedPmsGuest: { type: Object, default: null },
    },

    emits: ['close', 'paid'],

    data() {
        return {
            submitting: false,
            paymentMode: 'full',
            activeInput: { type: 'payment', index: 0 },
            customerGivenInput: '',
            payments: [],
            paymentMethods: [
                { label: 'Cash', value: 'cash' },
                { label: 'Mobile Wallet', value: 'mobile_wallet' },
                { label: 'Card', value: 'card' },
                { label: 'Bank Transfer', value: 'bank_transfer' },
                { label: 'Gift Card', value: 'gift_card' },
                { label: 'Charge to Room', value: 'room_charge' },
            ],
            checkedInGuests: [],
            selectedGuestKey: '',
            pmsLoading: false,
            pmsLoaded: false,
            pmsError: '',
            quickPayAmounts: [10, 15, 20, 30, 50, 100],
            keypadKeys: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0'],
            showCalculator: false,
            calculatorExpression: '',
            calculatorKeys: ['7', '8', '9', '÷', '4', '5', '6', '×', '1', '2', '3', '−', '0', '.', 'C', '+'],
        }
    },

    computed: {
        totalProducts() {
            return Array.isArray(this.session?.items) ? this.session.items.length : 0
        },

        subtotal() {
            return Number(this.session?.subtotal || 0)
        },

        discountTotal() {
            return Number(this.session?.discount_total || 0)
        },

        taxTotal() {
            return Number(this.session?.tax_total || 0)
        },

        grandTotal() {
            return Number(this.session?.grand_total || 0)
        },

        giftCardTotal() {
            return (this.payments || [])
                .filter(row => row.payment_method === 'gift_card' && row.gift_card_applied)
                .reduce((sum, row) => sum + this.toNumber(row.amount), 0)
        },

        currentTotalPaid() {
            return this.payments.reduce((sum, row) => sum + this.toNumber(row.amount), 0)
        },

        dueAmount() {
            return Math.max(0, this.grandTotal - this.currentTotalPaid)
        },

        customerGivenAmount() {
            return this.toNumber(this.customerGivenInput)
        },

        displayCustomerGiven() {
            return this.customerGivenInput ? `${this.currencySymbol(this.currencyCode)} ${this.customerGivenInput}` : ''
        },

        changeReturn() {
            return Math.max(0, this.customerGivenAmount - this.grandTotal)
        },

        normalizedPayments() {
            return this.payments
                .map((row, index) => ({
                    payment_method: row.payment_method,
                    amount: this.toNumber(row.amount),
                    transaction_id: row.transaction_id || null,
                    gift_card_code: row.payment_method === 'gift_card' ? (row.gift_card_code || '').trim() : null,
                    sort_order: index,
                }))
                .filter((row) => row.amount > 0)
        },

        usesRoomCharge() {
            return this.payments.some((row) => row.payment_method === 'room_charge')
        },

        selectedGuest() {
            return this.checkedInGuests.find((guest) => this.guestOptionKey(guest) === this.selectedGuestKey) || null
        },

        roomChargePayload() {
            if (!this.usesRoomCharge || !this.selectedGuest) return null

            return {
                booking_id: String(this.selectedGuest.booking_id ?? this.selectedGuest.bookingId ?? this.selectedGuest.id ?? ''),
                room_key_id: String(this.selectedGuest.room_key_id ?? this.selectedGuest.roomKeyId ?? this.selectedGuest.room_id ?? this.selectedGuest.room?.id ?? ''),
                booking_reference: String(this.selectedGuest.booking_reference ?? this.selectedGuest.booking_no ?? this.selectedGuest.booking_id ?? ''),
                customer_id: this.selectedGuest.customer_id ?? null,
                guest_name: this.selectedGuest.guest_name ?? this.selectedGuest.name ?? this.selectedGuest.customer_name ?? null,
                room_no: String(this.selectedGuest.room_no ?? this.selectedGuest.room_number ?? this.selectedGuest.room?.number ?? ''),
                room_name: String(this.selectedGuest.room_name ?? this.selectedGuest.room?.name ?? ''),
                room_type: this.guestRoomTypeLabel(this.selectedGuest) === '-' ? null : this.guestRoomTypeLabel(this.selectedGuest),
                currency_code: this.guestCurrencyCode(this.selectedGuest) || null,
            }
        },

        pmsCurrencyError() {
            if (!this.usesRoomCharge || !this.selectedGuest) return ''

            const pmsCurrency = normalizeCurrencyCode(this.guestCurrencyCode(this.selectedGuest))
            const posCurrency = normalizeCurrencyCode(this.currencyCode)

            if (!pmsCurrency || !posCurrency || pmsCurrency === posCurrency) return ''

            return `Cannot place order: PMS currency ${pmsCurrency} is different from POS currency ${this.currencyCode}.`
        },

        canSubmit() {
            return this.currentTotalPaid + 0.0001 >= this.grandTotal
                && this.normalizedPayments.length > 0
                && (!this.usesRoomCharge || Boolean(this.roomChargePayload?.booking_id && this.roomChargePayload?.room_key_id))
                && !this.pmsCurrencyError
        },

        calculatorPreview() {
            return this.evaluateExpression(this.calculatorExpression)
        },
    },

    watch: {
        show: {
            immediate: true,
            handler(value) {
                if (value) this.resetModal()
            },
        },
    },

    methods: {
        resetModal() {
            const defaultMethod = this.selectedPmsGuest ? 'room_charge' : 'cash'
            this.paymentMode = 'full'
            this.payments = [this.makePaymentRow(defaultMethod, this.money(this.grandTotal))]
            this.activeInput = { type: 'payment', index: 0 }
            this.customerGivenInput = this.money(this.grandTotal)
            this.submitting = false
            this.showCalculator = false
            this.calculatorExpression = ''
            this.selectedGuestKey = ''
            this.pmsError = ''
            this.pmsLoaded = false

            if (this.selectedPmsGuest) {
                this.checkedInGuests = [this.selectedPmsGuest]
                this.selectedGuestKey = this.guestOptionKey(this.selectedPmsGuest)
                this.pmsLoaded = true
            } else {
                this.checkedInGuests = []
            }
        },

        makePaymentRow(method = 'cash', amount = '') {
            return {
                payment_method: method,
                amount,
                transaction_id: '',
                gift_card_code: '',
                loading_gift_card: false,
                gift_card_error: '',
                gift_card_success: '',
                gift_card_applied: false,
            }
        },

        setMode(mode) {
            this.paymentMode = mode

            if (mode === 'full') {
                this.payments = [this.makePaymentRow('cash', this.money(this.grandTotal))]
                this.activeInput = { type: 'payment', index: 0 }
                this.customerGivenInput = this.money(this.grandTotal)
                return
            }

            if (
                this.payments.length === 1 &&
                this.toNumber(this.payments[0].amount) === this.grandTotal
            ) {
                this.payments = [
                    this.makePaymentRow('cash', ''),
                    this.makePaymentRow('mobile_wallet', ''),
                ]
                this.activeInput = { type: 'payment', index: 0 }
                this.customerGivenInput = ''
            }
        },

        onPaymentMethodChange(row) {
            row.gift_card_code = ''
            row.gift_card_error = ''
            row.gift_card_success = ''
            row.loading_gift_card = false
            row.gift_card_applied = false
            if (row.payment_method === 'gift_card') {
                row.amount = this.money(0)
            } else if (row.payment_method === 'room_charge') {
                row.amount = this.money(this.grandTotal)
                row.transaction_id = ''
                if (this.selectedPmsGuest) {
                    this.checkedInGuests = [this.selectedPmsGuest, ...this.checkedInGuests.filter((guest) => this.guestOptionKey(guest) !== this.guestOptionKey(this.selectedPmsGuest))]
                    this.selectedGuestKey = this.guestOptionKey(this.selectedPmsGuest)
                    this.pmsLoaded = true
                }
                this.loadCheckedInGuests()
            } else {
                if (this.paymentMode === 'full') {
                    row.amount = this.money(this.grandTotal)
                }
            }
        },

        async lookupAndApplyGiftCard(row, index) {
            const code = (row.gift_card_code || '').trim()
            if (!code) {
                row.gift_card_error = 'Please enter a gift card code.'
                row.gift_card_success = ''
                return
            }

            row.loading_gift_card = true
            row.gift_card_error = ''
            row.gift_card_success = ''

            try {
                const response = await window.axios.post(route('vendor.gift-cards.lookup'), { code })
                const card = response.data

                // Check status
                if (card.status !== 'active') {
                    if (card.status === 'expired') {
                        row.gift_card_error = 'Gift card is expired.'
                    } else if (card.status === 'used') {
                        row.gift_card_error = 'Gift card has no balance.'
                    } else if (card.status === 'disabled') {
                        row.gift_card_error = 'Gift card is disabled.'
                    } else {
                        row.gift_card_error = `Gift card status is ${card.status}.`
                    }
                    row.amount = this.money(0)
                    row.gift_card_applied = false
                    row.gift_card_success = ''
                    return
                }

                // Check currency compatibility
                const cardCurrency = normalizeCurrencyCode(card.base_currency_code)
                const posCurrency = normalizeCurrencyCode(this.currencyCode)
                if (cardCurrency !== posCurrency) {
                    row.gift_card_error = `Currency mismatch: Gift card has ${cardCurrency}, but POS uses ${posCurrency}.`
                    row.amount = this.money(0)
                    row.gift_card_applied = false
                    row.gift_card_success = ''
                    return
                }

                // Get balance
                const balance = Number(card.current_balance || 0)
                if (balance <= 0) {
                    row.gift_card_error = 'Gift card has no balance.'
                    row.amount = this.money(0)
                    row.gift_card_applied = false
                    row.gift_card_success = ''
                    return
                }

                // Calculate applied amount
                const otherPaymentsTotal = this.payments
                    .filter((r, idx) => idx !== index)
                    .reduce((sum, r) => sum + this.toNumber(r.amount), 0)

                const remainingDue = Math.max(0, this.grandTotal - otherPaymentsTotal)
                const amountToApply = Math.min(balance, remainingDue)

                row.amount = this.money(amountToApply)
                row.gift_card_applied = true
                row.gift_card_error = ''
                row.gift_card_success = `Gift card added successfully!`
            } catch (error) {
                row.gift_card_error = error?.response?.data?.message || 'Gift card not found.'
                row.amount = this.money(0)
                row.gift_card_applied = false
                row.gift_card_success = ''
            } finally {
                row.loading_gift_card = false
            }
        },

        setActivePayment(index) {
            this.activeInput = { type: 'payment', index }
        },

        setCustomerGivenActive() {
            this.activeInput = { type: 'customer' }
        },

        onAmountInput(index, value) {
            this.payments[index].amount = this.cleanMoneyInput(this.stripCurrency(value))
            this.setActivePayment(index)
        },

        onCustomerGivenInput(value) {
            this.customerGivenInput = this.cleanMoneyInput(this.stripCurrency(value))
            this.setCustomerGivenActive()
        },

        addPaymentRow() {
            this.payments.push(this.makePaymentRow('card', ''))

            this.$nextTick(() => {
                this.activeInput = { type: 'payment', index: this.payments.length - 1 }
            })
        },

        removePaymentRow(index) {
            if (this.payments.length === 1) return

            this.payments.splice(index, 1)

            if (this.activeInput.type === 'payment' && this.activeInput.index >= this.payments.length) {
                this.activeInput = { type: 'payment', index: this.payments.length - 1 }
            }
        },

        async loadCheckedInGuests(force = false) {
            if (this.pmsLoading || (this.pmsLoaded && !force)) return

            this.pmsLoading = true
            this.pmsError = ''

            try {
                const response = await window.axios.get(route('vendor.pms.checked-in-guests'))
                const payload = response.data
                const guests = Array.isArray(payload) ? payload : (payload.data || payload.guests || [])
                this.checkedInGuests = this.selectedPmsGuest
                    ? [this.selectedPmsGuest, ...guests.filter((guest) => this.guestOptionKey(guest) !== this.guestOptionKey(this.selectedPmsGuest))]
                    : guests
                this.pmsLoaded = true
                if (this.selectedPmsGuest && !this.selectedGuestKey) {
                    this.selectedGuestKey = this.guestOptionKey(this.selectedPmsGuest)
                }
            } catch (error) {
                this.pmsError = error?.response?.data?.message || 'Unable to load checked-in guests.'
            } finally {
                this.pmsLoading = false
            }
        },

        guestOptionKey(guest) {
            const bookingId = guest?.booking_id ?? guest?.bookingId ?? guest?.id ?? ''
            const roomKeyId = guest?.room_key_id ?? guest?.roomKeyId ?? guest?.room_id ?? guest?.room?.id ?? ''

            return `${bookingId}:${roomKeyId}`
        },

        guestOptionLabel(guest) {
            const name = guest?.guest_name ?? guest?.name ?? guest?.customer_name ?? 'Guest'
            const roomNo = guest?.room_no ?? guest?.room_number ?? guest?.room?.number ?? ''
            const roomName = guest?.room_name ?? guest?.room?.name ?? ''
            const room = [roomNo, roomName].filter(Boolean).join(' / ') || 'Room'
            const booking = guest?.booking_reference ?? guest?.booking_no ?? guest?.booking_id ?? guest?.id ?? ''
            const currency = this.guestCurrencyCode(guest)

            return `${name} - ${room}${booking ? ` (${booking})` : ''}${currency ? ` - ${currency}` : ''}`
        },

        guestRoomKeyLabel(guest) {
            return guest?.room_key_id ?? guest?.roomKeyId ?? guest?.room_id ?? guest?.room?.id ?? '-'
        },

        guestRoomTypeLabel(guest) {
            return guest?.room_type ?? guest?.roomType ?? guest?.room?.type ?? guest?.room?.room_type ?? guest?.room_name ?? guest?.room?.name ?? '-'
        },

        guestBookingLabel(guest) {
            return guest?.booking_id ?? guest?.bookingId ?? guest?.id ?? '-'
        },

        guestCurrencyCode(guest) {
            return String(
                guest?.currency_code ??
                guest?.currencyCode ??
                guest?.booking_currency_code ??
                guest?.bookingCurrencyCode ??
                guest?.booking_currency ??
                guest?.currency ??
                ''
            ).toUpperCase()
        },

        pressKey(key) {
            this.appendToActive(key)
        },

        appendToActive(char) {
            if (this.activeInput.type === 'customer') {
                this.customerGivenInput = this.appendChar(this.customerGivenInput, char)
                return
            }

            const index = this.activeInput.index ?? 0
            if (!this.payments[index]) return

            this.payments[index].amount = this.appendChar(this.payments[index].amount, char)
        },

        appendChar(current, char) {
            let next = String(current || '')

            if (char === '.' && next.includes('.')) return next
            if (next === '0' && char !== '.') next = ''
            if (next.length >= 15) return next

            return this.cleanMoneyInput(next + char)
        },

        clearActiveInput() {
            if (this.activeInput.type === 'customer') {
                this.customerGivenInput = ''
                return
            }

            const index = this.activeInput.index ?? 0
            if (this.payments[index]) this.payments[index].amount = ''
        },

        addQuickPay(amount) {
            const current = this.toNumber(this.customerGivenInput)
            this.customerGivenInput = this.money(current + amount)
            this.setCustomerGivenActive()
        },

        cleanMoneyInput(value) {
            const cleaned = String(value || '').replace(/[^\d.]/g, '')
            const parts = cleaned.split('.')

            if (parts.length <= 1) return cleaned

            return `${parts[0]}.${parts.slice(1).join('').slice(0, 3)}`
        },

        toNumber(value) {
            return Number(String(value || '').replace(/[^\d.]/g, '')) || 0
        },

        money(value) {
            return Number(value || 0).toFixed(3)
        },
        currencySymbol,
        stripCurrency(value) {
            return String(value || '')
                .replace(this.currencyCode, '')
                .replace(this.currencySymbol(this.currencyCode), '')
                .trim()
        },

        close() {
            if (this.submitting) return
            this.$emit('close')
        },

        openCalculator() {
            this.showCalculator = true
            this.calculatorExpression = ''
        },

        closeCalculator() {
            this.showCalculator = false
            this.calculatorExpression = ''
        },

        pressCalculatorKey(key) {
            if (key === 'C') {
                this.calculatorExpression = ''
                return
            }

            const map = {
                '÷': '/',
                '×': '*',
                '−': '-',
            }

            const next = this.calculatorExpression + (map[key] || key)

            if (next.length > 40) return

            this.calculatorExpression = next
        },

        calculatorKeyClass(key) {
            if (['÷', '×', '−', '+'].includes(key)) return 'fp-calc-key--operator'
            if (key === 'C') return 'fp-calc-key--clear'
            return ''
        },

        evaluateExpression(expression) {
            if (!expression) return '0'
            if (!/^[0-9+\-*/.\s]+$/.test(expression)) return '0'

            try {
                // eslint-disable-next-line no-new-func
                const result = Function(`"use strict"; return (${expression})`)()

                if (!Number.isFinite(result)) return '0'

                return this.money(result)
            } catch (e) {
                return '0'
            }
        },

        applyCalculatorResult() {
            const result = this.evaluateExpression(this.calculatorExpression)

            if (this.activeInput.type === 'customer') {
                this.customerGivenInput = result
            } else {
                const index = this.activeInput.index ?? 0
                if (this.payments[index]) this.payments[index].amount = result
            }

            this.closeCalculator()
        },

        submit(printBill) {
            if (!this.session?.id || !this.canSubmit || this.submitting) return

            this.submitting = true

            router.post(
                route('vendor.pos.finalize-payment', this.session.id),
                {
                    payment_mode: this.paymentMode,
                    customer_given_amount: this.customerGivenAmount,
                    print_bill: printBill,
                    payments: this.normalizedPayments,
                    room_charge: this.roomChargePayload,
                    fire_after_payment: this.fireAfterPayment,
                    ...this.firePayload,
                },
                {
                    preserveScroll: true,
                    preserveState: false,
                    onSuccess: () => {
                        this.submitting = false
                        this.$emit('paid')
                        this.$emit('close')
                    },
                    onError: () => {
                        this.submitting = false
                    },
                }
            )
        },
    },
}
</script>

<style scoped>
.fp-fade-enter-active,
.fp-fade-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.fp-fade-enter-from,
.fp-fade-leave-to {
    opacity: 0;
}

.fp-backdrop {
    position: fixed;
    inset: 0;
    z-index: 4100;
    background: rgba(15, 23, 42, 0.54);
    display: grid;
    place-items: center;
    padding: 16px;
    overflow-y: auto;
}

.fp-modal {
    width: min(1440px, 100%);
    max-height: calc(100dvh - 32px);
    background: #ffffff;
    border-radius: 20px;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 370px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    position: relative;
}

.fp-main {
    padding: clamp(16px, 2vw, 26px);
    display: grid;
    gap: clamp(16px, 2vw, 22px);
    overflow-y: auto;
    min-height: 0;
}

.fp-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.fp-eyebrow {
    margin: 0 0 4px;
    color: #f97316;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.fp-header h2 {
    margin: 0;
    font-size: clamp(20px, 3vw, 26px);
    font-weight: 900;
    color: #334155;
    line-height: 1.2;
}

.fp-close-btn {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 12px;
    background: #f1f5f9;
    color: #475569;
    cursor: pointer;
    flex: 0 0 auto;
}

.fp-section {
    min-width: 0;
}

.fp-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.fp-label {
    display: block;
    margin-bottom: 10px;
    font-size: 13px;
    font-weight: 900;
    color: #475569;
}

.fp-section-head .fp-label {
    margin-bottom: 0;
}

.fp-small-add {
    border: none;
    border-radius: 999px;
    padding: 8px 12px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 12px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.fp-mode-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.fp-mode-card {
    min-height: 56px;
    padding: 0 18px;
    border-radius: 14px;
    border: 1px dashed #d9dee6;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: #64748b;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    transition: 0.18s ease;
}

.fp-mode-card--active {
    border-color: #f59e0b;
    background: #fff7ed;
    color: #d97706;
}

.fp-payments {
    display: grid;
    gap: 10px;
}

.fp-payment-row {
    display: grid;
    grid-template-columns:
        minmax(145px, 0.9fr) minmax(130px, 1fr) minmax(135px, 1fr) minmax(120px, 0.9fr) auto;
    gap: 8px;
    align-items: center;
}

.fp-row-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.fp-input {
    width: 100%;
    min-height: 44px;
    border: 1px solid #d9e0e8;
    border-radius: 12px;
    background: #ffffff;
    padding: 0 14px;
    font-size: 13px;
    color: #334155;
    outline: none;
    min-width: 0;
}

.fp-input:focus {
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.13);
}

.fp-input[readonly] {
    background: #f8fafc;
    cursor: not-allowed;
}

.fp-pms-details {
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    padding: 10px;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    background: #fff7ed;
}

.fp-pms-details div {
    min-width: 0;
}

.fp-pms-details span,
.fp-pms-details strong {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.fp-pms-details span {
    color: #9a3412;
    font-size: 11px;
    font-weight: 800;
}

.fp-pms-details strong {
    margin-top: 2px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
}

.fp-error {
    margin-top: 8px;
    color: #dc2626;
    font-size: 12px;
    font-weight: 700;
}

.fp-grid {
    display: grid;
    grid-template-columns: minmax(260px, 0.9fr) minmax(280px, 1.1fr);
    gap: clamp(18px, 3vw, 36px);
    align-items: start;
}

.fp-keypad,
.fp-quick-grid {
    display: grid;
    gap: 10px;
}

.fp-keypad {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.fp-key {
    min-height: clamp(54px, 8vw, 72px);
    border-radius: 14px;
    border: 1px dashed #d6dbe3;
    background: #ffffff;
    color: #5b6674;
    font-size: clamp(16px, 2vw, 18px);
    font-weight: 900;
    cursor: pointer;
    transition: 0.16s ease;
}

.fp-key:hover,
.fp-mode-card:hover,
.fp-quick-btn:hover,
.fp-icon-btn:hover,
.fp-action:hover,
.fp-calc-btn:hover,
.fp-close-btn:hover {
    transform: translateY(-1px);
}

.fp-key--clear {
    background: #fde8e8;
    color: #ef4444;
    border-color: #f8d0d0;
}

.fp-calc-btn {
    width: 100%;
    min-height: 50px;
    border: none;
    border-radius: 14px;
    background: #f5e7d2;
    color: #f59e0b;
    font-weight: 900;
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
}

.fp-quick-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.fp-quick-btn {
    min-height: clamp(54px, 8vw, 72px);
    border: none;
    border-radius: 14px;
    background: #f7efc8;
    color: #ca8a04;
    font-size: 14px;
    font-weight: 900;
    cursor: pointer;
    transition: 0.16s ease;
}

.fp-quick-btn--active {
    background: #f2c400;
    color: #ffffff;
}

.fp-extra-fields {
    display: grid;
    gap: 12px;
    margin-top: 14px;
}

.fp-summary {
    border-left: 1px solid #e8edf3;
    background: #fbfcfd;
    min-height: 0;
    overflow-y: auto;
}

.fp-summary-inner {
    min-height: 100%;
    padding: clamp(18px, 2vw, 26px) clamp(16px, 2vw, 20px);
    display: flex;
    flex-direction: column;
}

.fp-summary h3 {
    margin: 0 0 18px;
    font-size: 18px;
    font-weight: 900;
    color: #334155;
}

.fp-summary-list {
    display: grid;
    gap: 12px;
}

.fp-summary-list div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: #64748b;
    padding-bottom: 10px;
    border-bottom: 1px dashed #dde3ea;
    font-size: 13px;
}

.fp-summary-list strong {
    color: #334155;
    text-align: right;
    white-space: nowrap;
}

.fp-summary-list__grand span,
.fp-summary-list__grand strong,
.fp-summary-list__grand-updated span,
.fp-summary-list__grand-updated strong {
    color: #f97316;
    font-weight: 900;
}

.fp-summary-list__deduction strong {
    color: #ef4444;
}

.fp-summary-list__change span,
.fp-summary-list__change strong {
    color: #22c55e;
    font-weight: 900;
}

.fp-actions {
    margin-top: auto;
    padding-top: 18px;
    display: grid;
    gap: 12px;
}

.fp-action {
    min-height: 50px;
    border: none;
    border-radius: 14px;
    font-weight: 900;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
}

.fp-action--cancel {
    background: #ececec;
    color: #334155;
}

.fp-action--submit {
    background: #077bd4;
    color: #ffffff;
}

.fp-action--print {
    background: #f78307;
    color: #ffffff;
}

.fp-action:disabled,
.fp-icon-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
}

.fp-icon-btn {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    flex: 0 0 auto;
}

.fp-icon-btn--danger {
    background: #fde2e2;
    color: #ef4444;
}

.fp-icon-btn--add {
    background: #dce7f1;
    color: #3b82f6;
}

.fp-calc-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.38);
    display: grid;
    place-items: center;
    padding: 16px;
    z-index: 2;
}

.fp-calc-modal {
    width: min(450px, 100%);
    max-height: calc(100dvh - 64px);
    overflow-y: auto;
    background: #ffffff;
    border-radius: 18px;
    padding: clamp(18px, 3vw, 24px);
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.25);
}

.fp-calc-screen {
    min-height: 96px;
    border: 1px solid #d9e0e8;
    border-radius: 16px;
    padding: 14px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    margin-bottom: 18px;
}

.fp-calc-expression {
    color: #9aa4b2;
    font-size: 18px;
    text-align: right;
    word-break: break-all;
}

.fp-calc-result {
    color: #1f2937;
    font-size: clamp(24px, 5vw, 28px);
    font-weight: 900;
    text-align: right;
    word-break: break-all;
}

.fp-calc-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.fp-calc-key {
    min-height: clamp(54px, 10vw, 68px);
    border: none;
    border-radius: 14px;
    background: #eaecf0;
    color: #5b6674;
    font-size: 20px;
    font-weight: 900;
    cursor: pointer;
}

.fp-calc-key--operator {
    background: #f5e7d2;
    color: #f59e0b;
}

.fp-calc-key--clear {
    background: #fde2e2;
    color: #ef4444;
}

.fp-calc-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 18px;
    flex-wrap: wrap;
}

.fp-calc-action {
    border: none;
    border-radius: 999px;
    background: transparent;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    padding: 10px 14px;
}

.fp-calc-action--ghost {
    color: #64748b;
    background: #f1f5f9;
}

.fp-calc-action--primary {
    color: #ffffff;
    background: #f59e0b;
}

@media (max-width: 1200px) {
    .fp-modal {
        grid-template-columns: 1fr;
        width: min(980px, 100%);
        overflow-y: auto;
    }

    .fp-main,
    .fp-summary {
        overflow: visible;
    }

    .fp-summary {
        border-left: none;
        border-top: 1px solid #e8edf3;
    }

    .fp-summary-inner {
        min-height: auto;
    }

    .fp-actions {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 920px) {
    .fp-payment-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: stretch;
    }

    .fp-row-actions {
        grid-column: 1 / -1;
        justify-content: flex-end;
    }

    .fp-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .fp-backdrop {
        padding: 10px;
        align-items: start;
    }

    .fp-modal {
        border-radius: 16px;
        max-height: calc(100dvh - 20px);
    }

    .fp-mode-grid,
    .fp-payment-row,
    .fp-actions {
        grid-template-columns: 1fr;
    }

    .fp-small-add span {
        display: none;
    }

    .fp-quick-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .fp-summary-list div {
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .fp-backdrop {
        padding: 0;
    }

    .fp-modal {
        width: 100%;
        min-height: 100dvh;
        max-height: 100dvh;
        border-radius: 0;
    }

    .fp-main {
        padding: 14px;
    }

    .fp-summary-inner {
        padding: 16px 14px;
    }

    .fp-header {
        align-items: center;
    }

    .fp-close-btn {
        width: 40px;
        height: 40px;
    }

    .fp-quick-grid {
        grid-template-columns: 1fr;
    }

    .fp-keypad {
        gap: 8px;
    }

    .fp-key,
    .fp-quick-btn {
        min-height: 52px;
    }

    .fp-action {
        min-height: 48px;
        font-size: 14px;
    }

    .fp-calc-backdrop {
        padding: 10px;
    }

    .fp-calc-modal {
        border-radius: 16px;
    }

    .fp-calc-grid {
        gap: 8px;
    }
}

@media (min-width: 1201px) {
    .fp-modal {
        width: min(1440px, calc(100vw - 64px));
        max-width: 1440px;
        overflow-y: auto;
    }

    .fp-modal::-webkit-scrollbar {
        width: 4px;
    }

    .fp-modal::-webkit-scrollbar-track {
        margin: 19px 0;
        background: transparent;
    }

    .fp-modal::-webkit-scrollbar-thumb {
        background: #8f8f8f;
        border-radius: 999px;
    }

    .fp-key {
        min-height: clamp(50px, 7.5vw, 68px);
        ;
    }

    .fp-mode-card {
        min-height: 45px;
    }

    .fp-main,
    .fp-summary,
    .fp-payments,
    .fp-payment-row,
    .fp-mode-grid,
    .fp-keypad,
    .fp-quick-grid,
    .fp-summary-list,
    .fp-actions {
        min-width: 0;
        box-sizing: border-box;
    }

    .fp-payment-row {
        grid-template-columns:
            minmax(120px, 0.9fr) minmax(120px, 1fr) minmax(120px, 1fr) minmax(110px, 0.9fr) auto;
    }

    .fp-action {
        white-space: nowrap;
    }

    .fp-summary {
        overflow: hidden;
    }
}

.gift-card-input-container {
    position: relative;
    width: 100%;
}

.gift-card-input-wrapper {
    position: relative;
    width: 100%;
}

.fp-gift-card-input {
    padding-right: 44px !important;
}

.btn-apply-gift-card {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: #f59e0b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
    box-sizing: border-box;
}

.btn-apply-gift-card:hover {
    background: #d97706;
}

.btn-apply-gift-card:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.gift-card-input-container .fp-error,
.gift-card-input-container .fp-success {
    position: absolute;
    top: 100%;
    left: 0;
    font-size: 11px;
    font-weight: 700;
    margin-top: 3px;
    white-space: nowrap;
    z-index: 10;
}

.gift-card-input-container .fp-error {
    color: #ef4444;
}

.gift-card-input-container .fp-success {
    color: #10b981;
}
</style>
