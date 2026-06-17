<template>
    <div class="pos-page">

        <Head :title="register ? 'Edit Register' : 'Create Register'" />

        <div class="form-container">
            <div class="gradient-overlay"></div>

            <div class="form-wrapper formWrapper">
                <!-- Header -->
                <div class="form-header formHeader">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h1 class="header-title">{{ register ? 'Edit Register' : 'Create Register' }}</h1>
                            <p class="header-subtitle">
                                Configure register identity, branch, printers, notes, and activation status.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <Link :href="route('vendor.pos.registers.index')" class="btn btn-ghost">Cancel</Link>
                            <button type="button" class="btn btn-primary-modern" :disabled="form.processing"
                                @click="submit">
                                <span v-if="form.processing" class="spinner-icon"></span>
                                {{ form.processing ? 'Saving...' : 'Submit' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <form @submit.prevent="submit" class="form-content">
                    <div class="form-grid">
                        <!-- Left Column -->
                        <div class="form-column">
                            <div class="form-card">
                                <div class="card-accent-line"></div>
                                <div class="card-header">
                                    <h2 class="card-title cardTitle">Register Information</h2>
                                </div>
                                <div class="card-body formCardBody">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Register Name</label>
                                            <input v-model="form.name" type="text" class="form-control formControl"
                                                placeholder="Front Counter Register" />
                                            <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Register Code</label>
                                            <input v-model="form.code" type="text" class="form-control formControl"
                                                placeholder="REG-001" />
                                            <div v-if="form.errors.code" class="error-text">{{ form.errors.code }}</div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Branch</label>
                                            <SelectInput id="branch_id" v-model="form.branch_id" :options="branches"
                                                valueKey="id" labelKey="name"
                                                placeholder="Select branch" :error="form.errors.branch_id" />
                                            <div v-if="form.errors.branch_id" class="error-text">{{
                                                form.errors.branch_id }}</div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check form-switch mt-2">
                                                <input id="is_active" class="form-check-input" type="checkbox"
                                                    v-model="form.is_active" />
                                                <label class="form-check-label" for="is_active">Active Register</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="form-column">
                            <div class="form-card">
                                <div class="card-accent-line"></div>
                                <div class="card-header">
                                    <h2 class="card-title cardTitle">Printer & Notes</h2>
                                </div>
                                <div class="card-body formCardBody">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Invoice Printer</label>
                                            <input v-model="form.invoice_printer" type="text"
                                                class="form-control formControl" placeholder="EPSON-TM-T20III" />
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Bill Printer</label>
                                            <input v-model="form.bill_printer" type="text"
                                                class="form-control formControl" placeholder="Kitchen Printer" />
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Notes</label>
                                            <textarea v-model="form.note" class="form-control formControl textarea"
                                                placeholder="Optional register notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, Link, useForm } from "@inertiajs/vue3";
import VendorAdminLayout from "@/Layouts/VendorAdminLayout.vue";
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

export default {
    name: "PosRegisterCreateUpdate",
    layout: VendorAdminLayout,
    components: { Head, Link, SelectInput },
    props: {
        register: { type: Object, default: null },
        branches: { type: Array, default: () => [] },
    },
    data() {
        return {
            form: useForm({
                branch_id: this.register?.branch_id ?? "",
                name: this.register?.name ?? "",
                code: this.register?.code ?? "",
                invoice_printer: this.register?.invoice_printer ?? "",
                bill_printer: this.register?.bill_printer ?? "",
                note: this.register?.note ?? "",
                is_active: this.register?.is_active ?? true,
            }),
        };
    },
    methods: {
        submit() {
            if (this.register) {
                this.form.put(route("vendor.pos.registers.update", this.register.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                    },
                    onError: (errors) => {
                        const message =
                            errors?.general ||
                            Object.values(errors)?.flat()?.[0] ||
                            'Something went wrong.'

                        alertError(message)
                    }
                });
                return;
            }
            this.form.post(route("vendor.pos.registers.store"), {
                preserveScroll: true,
                onError: (errors) => {
                    const message =
                        errors?.general ||
                        Object.values(errors)?.flat()?.[0] ||
                        'Something went wrong.'

                    alertError(message)
                }
            });
        },
    },
};
</script>

<style scoped>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.formControl {
    height: 44px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 10px;
    font-size: 14px;
    width: 100%;
    padding: 0 12px;
}

.formControl:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
    outline: none;
}

.textarea {
    height: auto;
    min-height: 130px;
    padding: 12px;
    resize: vertical;
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

@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 32px;
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
