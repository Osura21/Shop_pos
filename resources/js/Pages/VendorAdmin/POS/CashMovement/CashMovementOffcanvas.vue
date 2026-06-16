<template>
    <div v-if="show" class="cm-backdrop" @click.self="closePanel">
        <div class="cm-panel">
            <div class="cm-header">
                <div class="cm-header__content">
                    <div class="cm-badge">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Register Cash Flow</span>
                    </div>
                    <h2 class="cm-title">{{ movement ? 'Update Cash Movement' : 'Create Cash Movement' }}</h2>
                    <p class="cm-subtitle">
                        {{ movement ? 'Update incoming and outgoing cash movements with references and notes.' : 'Record incoming and outgoing cash movements with references and notes.' }}
                    </p>
                </div>

                <button type="button" class="cm-close" @click="closePanel">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="cm-direction-row">
                <button
                    type="button"
                    class="cm-toggle"
                    :class="{ 'cm-toggle--active': form.direction === 'in' }"
                    @click="setDirection('in')"
                >
                    <i class="bi bi-arrow-down-circle"></i>
                    <span>Cash In</span>
                </button>

                <button
                    type="button"
                    class="cm-toggle"
                    :class="{ 'cm-toggle--active': form.direction === 'out' }"
                    @click="setDirection('out')"
                >
                    <i class="bi bi-arrow-up-circle"></i>
                    <span>Cash Out</span>
                </button>
            </div>

            <div class="cm-section">
                <label class="cm-label">Reason</label>
                <div class="cm-reasons">
                    <button
                        v-for="reason in reasonOptions"
                        :key="reason.value"
                        type="button"
                        class="cm-reason"
                        :class="{ 'cm-reason--active': form.reason === reason.value }"
                        @click="form.reason = reason.value"
                    >
                        {{ reason.label }}
                    </button>
                </div>
            </div>

            <div class="cm-section cm-form-grid">
                <!-- Session Selector when creating from index page -->
                <div v-if="!movement && !sessionId" class="cm-field cm-field--full">
                    <label class="cm-label">POS Session</label>
                    <select v-model="form.pos_session_id" class="cm-input">
                        <option value="" disabled>Select an open session</option>
                        <option v-for="session in openSessions" :key="session.id" :value="session.id">
                            {{ session.register_name }} (Session #{{ session.id }})
                        </option>
                    </select>
                    <div v-if="form.errors.pos_session_id" class="cm-error">{{ form.errors.pos_session_id }}</div>
                </div>
                <!-- Warning when no open sessions are found -->
                <div v-if="!movement && !sessionId && openSessions.length === 0" class="cm-field cm-field--full">
                    <div class="alert alert-warning mb-0 py-2 border border-warning bg-warning-subtle text-warning d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>No open POS sessions found. Please open a session first.</span>
                    </div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Amount</label>
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.001"
                        min="0"
                        class="cm-input"
                        :placeholder="`${currencySymbol(activeCurrencyCode)} Amount`"
                    />
                    <div v-if="form.errors.amount" class="cm-error">{{ form.errors.amount }}</div>
                </div>

                <div class="cm-field">
                    <label class="cm-label">Reference</label>
                    <input
                        v-model="form.reference"
                        type="text"
                        class="cm-input"
                        placeholder="Invoice / drawer / note reference"
                    />
                    <div v-if="form.errors.reference" class="cm-error">{{ form.errors.reference }}</div>
                </div>

                <div class="cm-field cm-field--full">
                    <label class="cm-label">Notes</label>
                    <textarea
                        v-model="form.notes"
                        class="cm-textarea"
                        placeholder="Add details for this cash movement"
                    ></textarea>
                    <div v-if="form.errors.notes" class="cm-error">{{ form.errors.notes }}</div>
                </div>
            </div>

            <div class="cm-actions">
                <button type="button" class="cm-cancel" @click="closePanel">
                    Cancel
                </button>

                <button
                    type="button"
                    class="cm-submit"
                    :disabled="form.processing || (!movement && !sessionId && openSessions.length === 0)"
                    @click="submit"
                >
                    <i class="bi bi-check2-circle"></i>
                    <span>{{ form.processing ? 'Creating...' : (movement ? 'Update' : 'Create') }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import { currencySymbol } from "@/Utils/currency";

export default {
    name: "CashMovementOffcanvas",
    props: {
        show: { type: Boolean, default: false },
        sessionId: { type: [Number, String], default: null },
        currencyCode: { type: String, default: "LKR" },
        movement: { type: Object, default: null },
        openSessions: { type: Array, default: () => [] },
    },
    emits: ["close"],
    data() {
        return {
            form: useForm({
                pos_session_id: this.sessionId || "",
                direction: "out",
                reason: "pay_out",
                amount: "",
                reference: "",
                notes: "",
            }),
        };
    },
    computed: {
        reasonOptions() {
            if (this.form.direction === "in") {
                return [
                    { value: "pay_in", label: "Pay-In" },
                    { value: "tip_in", label: "Tip-In" },
                    { value: "correction", label: "Correction" },
                ];
            }

            return [
                { value: "pay_out", label: "Pay-Out" },
                { value: "tip_out", label: "Tip-Out" },
                { value: "refund", label: "Refund" },
                { value: "cash_drop", label: "Cash Drop" },
                { value: "correction", label: "Correction" },
            ];
        },
        activeCurrencyCode() {
            if (this.movement) {
                return this.movement.currency_code || this.currencyCode;
            }
            if (this.form.pos_session_id) {
                const selected = this.openSessions.find(s => s.id === this.form.pos_session_id);
                if (selected) {
                    return selected.currency_code;
                }
            }
            return this.currencyCode;
        },
    },
    watch: {
        sessionId: {
            immediate: true,
            handler(value) {
                if (!this.movement) {
                    this.form.pos_session_id = value || "";
                }
            },
        },
        movement: {
            immediate: true,
            handler(value) {
                if (value) {
                    this.form.pos_session_id = value.pos_session_id;
                    this.form.direction = value.direction;
                    this.form.reason = value.reason;
                    this.form.amount = String(value.amount);
                    this.form.reference = value.reference || "";
                    this.form.notes = value.notes || "";
                    this.form.clearErrors();
                } else {
                    this.form.pos_session_id = this.sessionId || "";
                    this.form.direction = "out";
                    this.form.reason = "pay_out";
                    this.form.amount = "";
                    this.form.reference = "";
                    this.form.notes = "";
                    this.form.clearErrors();
                }
            }
        }
    },
    methods: {
        currencySymbol,
        setDirection(direction) {
            this.form.direction = direction;
            this.form.reason = direction === "in" ? "pay_in" : "pay_out";
        },
        closePanel() {
            this.$emit("close");
        },
        submit() {
            if (this.movement) {
                this.form.put(route("vendor.pos.cash-movements.update", this.movement.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.form.reset();
                        this.closePanel();
                    },
                });
            } else {
                this.form.post(route("vendor.pos.cash-movements.store"), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.form.reset("amount", "reference", "notes");
                        this.closePanel();
                    },
                });
            }
        },
    },
};
</script>

<style scoped>
.cm-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: flex-end;
    z-index: 2500;
}

.cm-panel {
    width: 640px;
    max-width: 100%;
    background: #fff;
    height: 100%;
    overflow: auto;
    padding: 28px;
    box-shadow: -16px 0 40px rgba(15, 23, 42, 0.18);
}

.cm-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.cm-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #c2410c;
    border: 1px solid #fed7aa;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 12px;
}

.cm-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
}

.cm-subtitle {
    margin: 8px 0 0;
    font-size: 14px;
    color: #64748b;
}

.cm-close {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    color: #475569;
    cursor: pointer;
    transition: all 0.16s ease;
}

.cm-close:hover {
    background: #fff7ed;
    color: #ea580c;
}

.cm-direction-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.cm-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border: 1px solid #e5e7eb;
    background: #fff;
    padding: 16px;
    border-radius: 18px;
    font-weight: 800;
    color: #334155;
    cursor: pointer;
    transition: all 0.16s ease;
}

.cm-toggle--active {
    border-color: #f59e0b;
    background: #fff7ed;
    color: #c2410c;
    box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.16);
}

.cm-section {
    margin-bottom: 20px;
}

.cm-label {
    display: block;
    font-size: 13px;
    font-weight: 800;
    color: #334155;
    margin-bottom: 10px;
}

.cm-reasons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.cm-reason {
    border: 1px solid #e5e7eb;
    background: #fff;
    padding: 12px 14px;
    border-radius: 14px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    transition: all 0.16s ease;
}

.cm-reason--active {
    border-color: #f59e0b;
    background: #fff7ed;
    color: #c2410c;
}

.cm-form-grid {
    display: grid;
    gap: 16px;
}

.cm-field {
    display: grid;
    gap: 8px;
}

.cm-field--full {
    grid-column: 1 / -1;
}

.cm-input,
.cm-textarea {
    width: 100%;
    border: 1px solid #dbe2ea;
    background: #fff;
    color: #0f172a;
    border-radius: 14px;
    padding: 13px 14px;
    font-size: 14px;
    outline: none;
    transition: all 0.15s ease;
}

.cm-input:focus,
.cm-textarea:focus {
    border-color: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
}

.cm-textarea {
    min-height: 130px;
    resize: vertical;
}

.cm-error {
    color: #dc2626;
    font-size: 12px;
    font-weight: 600;
}

.cm-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-top: 24px;
}

.cm-cancel,
.cm-submit {
    border: none;
    border-radius: 16px;
    padding: 16px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.16s ease;
}

.cm-cancel {
    background: #f1f5f9;
    color: #0f172a;
}

.cm-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #f59e0b, #ea580c);
    color: white;
    box-shadow: 0 12px 24px rgba(245, 158, 11, 0.22);
}

.cm-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .cm-panel {
        width: 100%;
        padding: 18px;
    }

    .cm-title {
        font-size: 22px;
    }

    .cm-direction-row,
    .cm-actions {
        grid-template-columns: 1fr;
    }
}
</style>
