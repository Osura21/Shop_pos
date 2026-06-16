<template>
  <Head :title="isEdit ? 'Update User' : 'Create User'" />

  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay gradientOverlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update User' : 'Create User' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Update user information and security details.' : 'Create a new user for this tenant.' }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.users.index')" class="btn btn-ghost">
              Cancel
            </Link>

            <button
              type="button"
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update User' : 'Create User') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div v-if="form.errors.general" class="alert alert-danger">
          {{ form.errors.general }}
        </div>

        <div class="form-grid">
          <!-- Left Column - User Information -->
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">User Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <Input
                      id="name"
                      label="Full Name"
                      v-model="form.name"
                      placeholder="Enter full name"
                      :error="form.errors.name"
                    />
                  </div>

                  <div class="col-12 col-md-6">
                    <SelectInput
                      id="branch_id"
                      label="Branch"
                      v-model="form.branch_id"
                      :options="branches"
                      valueKey="id"
                      labelKey="name"
                      placeholder="Select branch"
                      :error="form.errors.branch_id"
                    />
                  </div>

                  <div class="col-12 col-md-6">
                    <Input
                      id="username"
                      label="Username"
                      v-model="form.username"
                      placeholder="Username"
                      :error="form.errors.username"
                    />
                  </div>

                  <div class="col-12 col-md-6">
                    <Input
                      id="email"
                      label="Email Address"
                      type="email"
                      v-model="form.email"
                      placeholder="user@restaurant.com"
                      :error="form.errors.email"
                    />
                  </div>

                  <div class="col-12 col-md-6">
                    <SelectInput
                      id="gender"
                      label="Gender"
                      v-model="form.gender"
                      :options="genderOptions"
                      valueKey="id"
                      labelKey="name"
                      placeholder="Select gender"
                      :error="form.errors.gender"
                    />
                  </div>

                  <div class="col-12 col-md-6">
                    <SelectInput
                      id="role_id"
                      label="Role"
                      v-model="form.role_id"
                      :options="roles"
                      valueKey="id"
                      labelKey="name"
                      placeholder="Select role"
                      :error="form.errors.role_id"
                    />
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input
                        id="status"
                        class="form-check-input"
                        type="checkbox"
                        v-model="form.status"
                      >
                      <label class="form-check-label" for="status">Active User</label>
                    </div>
                    <div v-if="form.errors.status" class="error-text mt-1">
                      {{ form.errors.status }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Security -->
          <div class="form-column">
            <div class="form-card ">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Security Settings</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12">
                    <Input
                      id="password"
                      label="Password"
                      type="password"
                      v-model="form.password"
                      :placeholder="isEdit ? 'Leave blank to keep current password' : 'Enter secure password'"
                      :error="form.errors.password"
                    />
                  </div>

                  <div class="col-12">
                    <Input
                      id="password_confirmation"
                      label="Confirm Password"
                      type="password"
                      v-model="form.password_confirmation"
                      placeholder="Confirm password"
                      :error="form.errors.password_confirmation"
                    />
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

<script>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import Input from '@/Components/InputField.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import SelectInput from '@/Components/SelectInput.vue'

export default {
  name: 'VendorUserCreateUpdate',
  layout: VendorAdminLayout,

  components: {
    Head,
    Link,
    Input,
    SelectInput,
  },

  props: {
    userData: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
  },

  data() {
    return {
      genderOptions: [
        { id: 'male', name: 'Male' },
        { id: 'female', name: 'Female' },
        { id: 'other', name: 'Other' },
      ],

      form: useForm({
        name: this.userData?.name ?? '',
        branch_id: this.userData?.branch_id ?? '',
        username: this.userData?.username ?? '',
        email: this.userData?.email ?? '',
        gender: this.userData?.gender ?? '',
        role_id: this.userData?.role_id ?? '',
        status: !!(this.userData?.status ?? true),
        password: '',
        password_confirmation: '',
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.userData?.id
    },
  },

  methods: {
    normalize(data) {
      return {
        ...data,
        branch_id: data.branch_id ? Number(data.branch_id) : null,
        role_id: data.role_id ? Number(data.role_id) : null,
        status: data.status ? 1 : 0,
      }
    },

    submit() {
      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.normalize(data),
            _method: 'PUT',
          }))
          .post(route('vendor.users.update', this.userData.id), {
            preserveScroll: true,
            onError: (errors) => {
              const message =
                errors?.general ||
                Object.values(errors)?.flat()?.[0] ||
                'Something went wrong.'

              alertError(message)
            }
          })

        return
      }

      this.form
        .transform((data) => this.normalize(data))
        .post(route('vendor.users.store'), {
          preserveScroll: true,
          onError: (errors) => {
            const message =
              errors?.general ||
              Object.values(errors)?.flat()?.[0] ||
              'Something went wrong.'

            alertError(message)
          }
        })
    },
  },
}
</script>

<style scoped>

/* Grid - Bootstrap + Custom */
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

.formCardBody {
  padding: 24px;
}

.alert-danger {
  border-radius: 12px;
  border-color: #fecaca;
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
  to { transform: rotate(360deg); }
}

.error-text {
  font-size: 12px;
  color: #dc2626;
  margin-top: 4px;
}

/* Responsive - Bootstrap + Custom */
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

  .card-header,
  .formCardBody {
    padding: 20px 16px;
  }
}
</style>