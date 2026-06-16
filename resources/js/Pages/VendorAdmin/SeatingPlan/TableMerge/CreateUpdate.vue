<template>

    <Head :title="isEdit ? 'Edit Table Merge' : 'Create Table Merge'" />

    <div class="form-container">
        <!-- Gradient Background -->
        <div class="gradient-overlay gradientOverlay"></div>

        <!-- Form Wrapper -->
        <div class="form-wrapper formWrapper">
            <!-- Header Section -->
            <div class="form-header formHeader">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h1 class="header-title">
                            {{ isEdit ? 'Edit Table Merge' : 'Create Table Merge' }}
                        </h1>
                        <p class="header-subtitle">
                            Combine multiple tables into a single larger table for events or large groups.
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <Link :href="route('vendor.table-merges.index')" class="btn btn-ghost">
                            Cancel
                        </Link>
                        <button class="btn-primary-modern" :disabled="form.processing" @click="submit">
                            <span v-if="form.processing" class="spinner-icon"></span>
                            {{ form.processing ? 'Saving...' : (isEdit ? 'Update Merge' : 'Create Merge') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="submit" class="form-content">
                <div class="form-grid">
                    <div class="form-column">
                        <div class="form-card">
                            <div class="card-accent-line"></div>
                            <div class="card-header">
                                <h2 class="card-title cardTitle">Merge Information</h2>
                            </div>
                            <div class="card-body formCardBody">
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label formLabel">Branch</label>
                                        <SelectInput id="branch.id" v-model="form.branch_id" :options="branches"
                                           valueKey="id" labelKey="name"
                                            placeholder="Select Branch" :error="form.errors.branch_id" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label formLabel">Merge Type</label>
                                        <SelectInput id="type.id" v-model="form.type" :options="Object.entries(typeOptions).map(([value, label]) => ({
                                            value,
                                            label
                                        }))" valueKey="id" labelKey="name"
                                            placeholder="Select Type" :error="form.errors.type" />
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label formLabel">Tables to Merge</label>
                                        <div class="table-pick-grid">
                                            <label v-for="table in filteredTables" :key="table.id"
                                                class="table-pick-item">
                                                <input type="checkbox" :value="table.id"
                                                    v-model="form.member_table_ids" />
                                                <span>{{ table.label }}</span>
                                            </label>
                                        </div>
                                        <div v-if="form.errors.member_table_ids" class="error-text">
                                            {{ form.errors.member_table_ids }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import VendorAdminLayout from "@/Layouts/VendorAdminLayout.vue";
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: VendorAdminLayout });

const props = defineProps({
    merge: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    tables: { type: Array, default: () => [] },
    typeOptions: { type: Object, default: () => ({}) },
});

const form = useForm({
    branch_id: props.merge?.branch_id ?? "",
    type: props.merge?.type ?? "capacity",
    member_table_ids: props.merge?.member_table_ids ?? [],
});

const isEdit = computed(() => !!props.merge?.id);

const filteredTables = computed(() =>
    props.tables.filter(
        (t) => !form.branch_id || Number(t.branch_id) === Number(form.branch_id)
    )
);

function submit() {
    const payload = (d) => ({
        ...d,
        branch_id: d.branch_id || null,
        member_table_ids: (d.member_table_ids || []).map(Number),
    });

    if (isEdit.value) {
        form.transform((d) => ({ ...payload(d), _method: "PUT" })).post(
            route("vendor.table-merges.update", props.merge.id),
            { preserveScroll: true,
                onError: (errors) => {
                    const message =
                        errors?.general ||
                        Object.values(errors)?.flat()?.[0] ||
                        'Something went wrong.'

                    alertError(message)
                } }
        );
        return;
    }

    form.transform(payload).post(route("vendor.table-merges.store"), {
        preserveScroll: true,
        onError: (errors) => {
            const message =
                errors?.general ||
                Object.values(errors)?.flat()?.[0] ||
                'Something went wrong.'

            alertError(message)
        }
    });
}
</script>

<style scoped>

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 40px;
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.form-select {
    height: 44px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 10px;
    font-size: 14px;
}

.form-select:focus {
    border-color: #f28c00;
    box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
}

/* Table Picker */
.table-pick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 8px;
}

.table-pick-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.08);
    padding: 12px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.table-pick-item:hover {
    border-color: #f28c00;
    background: rgba(242, 140, 0, 0.03);
}

.table-pick-item input {
    accent-color: #f28c00;
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

.error-text {
    font-size: 12px;
    color: #dc2626;
    margin-top: 4px;
}

/* Responsive */
@media (max-width: 991px) {
  .table-pick-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .form-container { padding: 24px 16px; }
  .header-title { font-size: 24px; }
  .form-card { border-radius: 12px; }
  .card-header{ padding: 20px 16px; }
}
</style>