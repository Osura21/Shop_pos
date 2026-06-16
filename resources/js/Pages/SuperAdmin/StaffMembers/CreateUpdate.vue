<template>
  <Head :title="isEdit ? 'Update Staff' : 'Create Staff'" />

  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Staff' : 'Create Staff' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Edit staff details.' : 'Create a new staff account.' }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('settings.staff.index')" class="btn btn-ghost">
              Cancel
            </Link>

            <button
              type="button"
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Staff' : 'Create Staff') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <!-- Single Column (full width) -->
          <div class="form-column">

            <!-- Staff Information -->
            <div class="form-card">
              <div class="card-header">
                <h2 class="card-title cardTitle">Staff Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <Input id="name" label="Name" v-model="form.name" placeholder="Enter name"
                      :error="form.errors.name" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <Input id="email" label="Email" type="email" v-model="form.email" placeholder="Enter email"
                      :error="form.errors.email" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                      <PhoneInput v-model="form.phone" :error="form.errors.phone" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput id="role_id" label="Role" v-model="form.role_id" :options="roles" valueKey="id"
                      labelKey="name" placeholder="Select role" :error="form.errors.role_id" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <SelectInput id="status" label="Status" v-model="form.status" :options="statusOptions" valueKey="id"
                      labelKey="name" placeholder="Select status" :error="form.errors.status" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Security -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Security</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <Input id="password" label="Password" type="password" v-model="form.password"
                      :placeholder="isEdit ? 'Leave blank to keep same' : 'Enter password'"
                      :error="form.errors.password" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <Input id="password_confirmation" label="Confirm Password" type="password"
                      v-model="form.password_confirmation" placeholder="Confirm password"
                      :error="form.errors.password_confirmation" />
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- General errors (non-field) -->
        <div v-if="hasGeneralErrors" class="alert alert-danger mt-4">
          <ul class="mb-0">
            <li v-for="(error, field) in generalErrors" :key="field">
              {{ error }}
            </li>
          </ul>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import Input from '../../../Components/InputField.vue'
import SelectInput from '../../../Components/SelectInput.vue'
import PhoneInput from '@/Components/PhoneInput.vue'

export default {
  name: 'StaffForm',
  layout: SuperAdminLayout,

  components: {
    Head,
    Link,
    Input,
    SelectInput,
    PhoneInput
  },

  props: {
    staff: { type: Object, default: null },
    roles: { type: Array, default: () => [] },
  },

  data() {
    const statusOptions = [
      { id: '1', name: 'Active' },
      { id: '0', name: 'Inactive' },
    ]

    const defaultRoleId = this.staff?.role_id ?? (this.roles?.[0]?.id ?? '')

    return {
      statusOptions,
      form: useForm({
        name: this.staff?.name ?? '',
        email: this.staff?.email ?? '',
        phone: this.staff?.phone ?? '',
        role_id: defaultRoleId,
        status: this.staff
          ? (this.staff.status ? '1' : '0')
          : '1',
        password: '',
        password_confirmation: '',
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.staff?.id
    },

    generalErrors() {
      const omit = ['name', 'email', 'phone', 'role_id', 'status', 'password', 'password_confirmation']
      const out = {}
      const errs = this.form?.errors || {}
      Object.keys(errs).forEach((k) => {
        if (!omit.includes(k)) out[k] = errs[k]
      })
      return out
    },

    hasGeneralErrors() {
      return this.form?.hasErrors && Object.keys(this.generalErrors).length > 0
    },
  },

  methods: {
    submit() {
      const normalize = (data) => ({
        ...data,
        role_id: data.role_id ? Number(data.role_id) : null,
        status: data.status === '' || data.status === null ? null : String(data.status),
        password_confirmation: data.password ? data.password_confirmation : '',
      })

      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...normalize(data),
            _method: 'PUT',
          }))
          .post(route('settings.staff.update', this.staff.id), {
            preserveScroll: true,
            onSuccess: () => this.form.reset('password', 'password_confirmation'),
          })

        return
      }

      this.form
        .transform((data) => normalize(data))
        .post(route('settings.staff.store'), {
          preserveScroll: true,
          onSuccess: () => router.visit(route('settings.staff.index')),
        })
    },
  },
}
</script>

<style scoped>

/* ── Form layout ── */
.form-content {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 28px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

/* ── Spinner ── */
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

/* ── Responsive ── */
@media (max-width: 768px) {
  .form-container { padding: 24px 16px; }
}

@media (max-width: 640px) {
  .header-title { font-size: 24px; }
  .form-card { border-radius: 12px; }
  .card-header { padding: 20px 16px 0; }
  .form-card-body { padding: 16px; }
}
</style>