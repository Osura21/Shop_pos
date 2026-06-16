<template>
  <Head :title="isEdit ? 'Update Customer' : 'Create Customer'" />

  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay gradientOverlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Customer' : 'Create Customer' }}</h1>
            <p class="header-subtitle">
              {{ isEdit ? 'Update customer information and account settings.' : 'Create a new customer profile.' }}
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.customers.index')" class="btn btn-ghost">
              Cancel
            </Link>

            <button
              type="button"
              class="btn btn-primary-modern"
              :disabled="form.processing"
              @click="submit"
            >
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Customer' : 'Create Customer') }}
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
          <!-- Left Column -->
          <div class="form-column">
            <!-- Customer Information -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Customer Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <Input
                      id="name"
                      label="Full Name"
                      v-model="form.name"
                      placeholder="Enter customer name"
                      :error="form.errors.name"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <label class="form-label formLabel">customer_type</label>
                    <SelectInput
                      id="customer_type"
                      v-model="form.customer_type"
                      :options="customerTypeOptionList"
                      valueKey="value"
                      labelKey="name"
                      placeholder="Select customer type"
                      :error="form.errors.customer_type"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <div class="phone-wrap">
                      <PhoneInput v-model="form.phone" />
                    </div>
                    <div v-if="form.errors.phone" class="error-text">
                      {{ form.errors.phone }}
                    </div>
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <Input
                      id="phone_country"
                      label="Phone Country"
                      v-model="form.phone_country"
                      placeholder="Auto detected / optional"
                      :error="form.errors.phone_country"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <label class="form-label formLabel">Birthdate</label>
                    <DatePicker v-model="form.birthdate" />
                    <div v-if="form.errors.birthdate" class="error-text">
                      {{ form.errors.birthdate }}
                    </div>
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <label class="form-label formLabel">Gender</label>
                    <SelectInput
                      id="select_gender"
                      v-model="form.gender"
                      :options="genderOptionList"
                      valueKey="value"
                      labelKey="name"
                      placeholder="Select customer type"
                      :error="form.errors.customer_type"
                    />
                    <!-- <div v-if="form.errors.gender" class="error-text">
                      {{ form.errors.gender }}
                    </div> -->
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input
                        id="is_active"
                        class="form-check-input"
                        type="checkbox"
                        v-model="form.is_active"
                      >
                      <label class="form-check-label" for="is_active">Active Customer</label>
                    </div>
                    <div v-if="form.errors.is_active" class="error-text">
                      {{ form.errors.is_active }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Details -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Customer Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <Input
                      id="username"
                      label="Username"
                      v-model="form.username"
                      placeholder="Username"
                      :error="form.errors.username"
                    />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <Input
                      id="email"
                      label="Email Address"
                      type="email"
                      v-model="form.email"
                      placeholder="customer@email.com"
                      :error="form.errors.email"
                    />
                  </div>

                  <div class="col-12">
                    <label class="form-label formLabel">Note</label>
                    <textarea
                      class="form-control formControl"
                      rows="4"
                      v-model="form.note"
                      placeholder="Add a note about this customer..."
                    ></textarea>
                    <div v-if="form.errors.note" class="error-text">
                      {{ form.errors.note }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="form-column">
            <!-- Avatar -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Avatar</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="avatar-box" @click="triggerAvatarSelect">
                  <template v-if="avatarPreview">
                    <div class="avatar-placeholder">

                      <!-- IMAGE INSTEAD OF ICON -->
                      <div class="avatar-image-wrapper">
                        <img :src="avatarPreview" alt="Avatar" class="avatar-img" />
                      </div>

                      <div class="fw-semibold mt-2">Upload Avatar</div>
                      <div class="small text-muted mt-1">JPG or PNG (max 5MB)</div>
                    </div>
                  </template>
                  <template v-else>
                    <div class="avatar-placeholder">
                      <i class="bi bi-person-circle avatar-icon"></i>
                      <div class="fw-semibold mt-2">Upload Avatar</div>
                      <div class="small text-muted mt-1">JPG or PNG (max 5MB)</div>
                    </div>
                  </template>
                </div>

                <input
                  ref="avatarInput"
                  type="file"
                  class="d-none"
                  accept=".jpg,.jpeg,.png,.webp"
                  @change="onAvatarChange"
                >

                <div class="d-flex gap-2 mt-3">
                  <button type="button" class="btn btn-outline-secondary btn-sm" @click="triggerAvatarSelect">
                    Upload Image
                  </button>
                  <button
                    v-if="avatarPreview"
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    @click="clearAvatar"
                  >
                    Remove
                  </button>
                </div>

                <div v-if="form.errors.avatar" class="error-text mt-2">
                  {{ form.errors.avatar }}
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
                  <div class="col-12 mb-2">
                    <Input
                      id="password"
                      label="Password"
                      type="password"
                      v-model="form.password"
                      :placeholder="isEdit ? 'Leave blank to keep current' : 'Enter password'"
                      :error="form.errors.password"
                    />
                  </div>
                  <div class="col-12 mb-2">
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

            <!-- Business Details -->
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Business Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 mb-2">
                    <Input
                      id="registration_number"
                      label="Registration Number"
                      v-model="form.registration_number"
                      placeholder="Registration Number"
                      :error="form.errors.registration_number"
                    />
                  </div>
                  <div class="col-12 mb-2">
                    <Input
                      id="vat_tin"
                      label="VAT / TIN"
                      v-model="form.vat_tin"
                      placeholder="VAT TIN"
                      :error="form.errors.vat_tin"
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
import MazInputPhoneNumber from 'maz-ui/components/MazInputPhoneNumber'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import PhoneInput from '@/Components/PhoneInput.vue'
import DatePicker from '@/Components/DatePicker.vue'


export default {
  name: 'CustomerCreateUpdate',
  layout: VendorAdminLayout,

  components: {
    Head,
    Link,
    Input,
    MazInputPhoneNumber,
    SelectInput,
    PhoneInput,
    DatePicker,
  },

  props: {
    customer: { type: Object, default: null },
    customerTypeOptions: { type: [Object, Array], default: () => ({}) },
    genderOptions: { type: [Object, Array], default: () => ({}) },
  },

  data() {
    return {
      phoneCountryCode: '',
      avatarPreview: this.customer?.avatar_url ?? '',
      removeAvatar: false,

      form: useForm({
        name: this.customer?.name ?? '',
        customer_type: this.customer?.customer_type ?? 'regular',
        phone: this.customer?.phone ?? '',
        phone_country: this.customer?.phone_country ?? '',
        birthdate: this.customer?.birthdate ?? '',
        gender: this.customer?.gender ?? '',
        is_active: !!(this.customer?.is_active ?? true),

        username: this.customer?.username ?? '',
        email: this.customer?.email ?? '',
        note: this.customer?.note ?? '',

        password: '',
        password_confirmation: '',

        registration_number: this.customer?.registration_number ?? '',
        vat_tin: this.customer?.vat_tin ?? '',

        avatar: null,
        remove_avatar: 0,
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.customer?.id
    },

    customerTypeOptionList() {
      return this.normalizeOptions(this.customerTypeOptions)
    },

    genderOptionList() {
      return this.normalizeOptions(this.genderOptions)
    },
  },

  methods: {
    normalizeOptions(source) {
      if (Array.isArray(source)) {
        return source.map((item) => {
          if (typeof item === 'string') {
            return { value: item, label: item }
          }

          return {
            value: item.value ?? item.id ?? item.code ?? '',
            label: item.label ?? item.name ?? item.title ?? item.value ?? '',
          }
        }).filter((item) => item.value !== '')
      }

      return Object.entries(source || {}).map(([value, label]) => ({
        value,
        label,
      }))
    },

    nullable(value) {
      return value === '' || value === undefined ? null : value
    },

    triggerAvatarSelect() {
      this.$refs.avatarInput?.click()
    },

    onAvatarChange(event) {
      const file = event.target.files?.[0]

      if (!file) return

      this.form.avatar = file
      this.removeAvatar = false

      if (this.avatarPreview && this.avatarPreview.startsWith('blob:')) {
        URL.revokeObjectURL(this.avatarPreview)
      }

      this.avatarPreview = URL.createObjectURL(file)
    },

    clearAvatar() {
      if (this.avatarPreview && this.avatarPreview.startsWith('blob:')) {
        URL.revokeObjectURL(this.avatarPreview)
      }

      this.avatarPreview = ''
      this.form.avatar = null
      this.removeAvatar = !!this.customer?.avatar_url
      this.form.remove_avatar = this.removeAvatar ? 1 : 0

      if (this.$refs.avatarInput) {
        this.$refs.avatarInput.value = ''
      }
    },

    normalizedPayload(data) {
      return {
        ...data,
        is_active: data.is_active ? 1 : 0,
        phone: this.nullable(data.phone),
        phone_country: this.nullable(data.phone_country),
        birthdate: this.nullable(data.birthdate),
        gender: this.nullable(data.gender),
        username: this.nullable(data.username),
        email: this.nullable(data.email),
        note: this.nullable(data.note),
        registration_number: this.nullable(data.registration_number),
        vat_tin: this.nullable(data.vat_tin),
        remove_avatar: this.removeAvatar ? 1 : 0,
      }
    },

    submit() {
      const requestOptions = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => router.visit(route('vendor.customers.index')),
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong.'

          alertError(message)
        }
      }

      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.normalizedPayload(data),
            _method: 'PUT',
          }))
          .post(route('vendor.customers.update', this.customer.id), requestOptions)

        return
      }

      this.form
        .transform((data) => this.normalizedPayload(data))
        .post(route('vendor.customers.store'), requestOptions)
    },
  },

  beforeUnmount() {
    if (this.avatarPreview && this.avatarPreview.startsWith('blob:')) {
      URL.revokeObjectURL(this.avatarPreview)
    }
  },
}
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

.note-area  {
  height: 44px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
  min-height: 130px;
  resize: vertical;
}

.note-area :focus {
  border-color: #f28c00 !important;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15)!important;
}

.formControl {
  height: 104px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  font-size: 14px;
}

.formControl:focus {
  border-color: #f28c00;
  box-shadow: 0 0 0 3px rgba(242, 140, 0, 0.15);
}

.avatar-box {
  min-height: 240px;
  border: 2px dashed rgba(242, 140, 0, 0.3);
  border-radius: 16px;
  background: #fcfaf7;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-align: center;
  padding: 20px;
}

.avatar-placeholder { color: #6b7280; }
.avatar-icon { font-size: 68px; color: #f28c00; }

.avatar-image-wrapper {
  width: 80px;             
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;     
  margin: 0 auto;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;        
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
@media (max-width: 1024px) {
  .form-grid { grid-template-columns: 1fr; gap: 32px; }
}

@media (max-width: 640px) {
  .form-container { padding: 24px 16px; }
  .header-title { font-size: 24px; }
  .form-card { border-radius: 12px; }
  .card-header { padding: 20px 16px; }
}
</style>