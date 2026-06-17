<template>
  <Head :title="isEdit ? 'Edit Floor' : 'Create Floor'" />

  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay  gradientOverlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              {{ isEdit ? 'Edit Floor' : 'Create Floor' }}
            </h1>
            <p class="header-subtitle">
              Manage restaurant floors and their branch association.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.floors.index')" class="btn btn-ghost">
              Cancel
            </Link>
            <button
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Floor' : 'Create Floor') }}
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
                <h2 class="card-title cardTitle">Floor Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Floor Name (English)</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="form-control formControl"
                      placeholder="Floor name (e.g. Ground Floor)"
                    >
                    <div v-if="form.errors.name" class="error-text">{{ form.errors.name }}</div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <label class="form-label formLabel">Branch</label>

                    <SelectInput
                      id="branch.id"
                      v-model="form.branch_id"
                      :options="branches"

                      valueKey="id"
                      labelKey="name"
                      placeholder="Select Branch"
                      :error="form.errors.branch_id"
                    />
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input
                        id="is_active"
                        class="form-check-input"
                        type="checkbox"
                        v-model="form.is_active"
                      >
                      <label class="form-check-label" for="is_active">Active Floor</label>
                    </div>
                  </div>
                </div>

                <div v-if="form.errors.general" class="alert alert-danger mt-3">
                  {{ form.errors.general }}
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
import { computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import SelectInput from '@/Components/SelectInput.vue'

defineOptions({ layout: VendorAdminLayout })

const props = defineProps({
  floor: { type: Object, default: null },
  branches: { type: Array, default: () => [] },
})

const form = useForm({
  name: props.floor?.name ?? '',
  branch_id: props.floor?.branch_id ?? '',
  is_active: !!(props.floor?.is_active ?? true),
})

const isEdit = computed(() => !!props.floor?.id)

function submit() {
  const payload = (data) => ({
    ...data,
    branch_id: data.branch_id || null,
    is_active: data.is_active ? 1 : 0,
  })

  if (isEdit.value) {
    form.transform((data) => ({ ...payload(data), _method: 'PUT' }))
      .post(route('vendor.floors.update', props.floor.id), { preserveScroll: true,
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong while creating the Reason.'

          alertError(message)
        }
      },)
    return
  }

  form.transform(payload).post(route('vendor.floors.store'), { preserveScroll: true,
    onError: (errors) => {
      const message =
        errors?.general ||
        Object.values(errors)?.flat()?.[0] ||
        'Something went wrong while creating the Reason.'

      alertError(message)
    }
  },)
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
.formControl {
  height: 40px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
}

.formControl:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
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

@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 640px) {
  .form-container { padding: 24px 16px; }
  .header-title { font-size: 24px; }
  .form-card { border-radius: 12px; }
  .card-header{ padding: 20px 16px; }
}
</style>
