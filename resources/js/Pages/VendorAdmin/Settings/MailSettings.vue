<template>
  <MiniSettingsNav :activeItem="'mail'" size="auto" :hideOnDesktop="true" />

  <Head title="Mail Settings" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-envelope me-2 text-warning"></i>Mail Settings
            </h1>
            <p class="header-subtitle">
              Configure this vendor's mail sender. Inactive vendors use the SuperAdmin default mail settings.
            </p>
          </div>

          <button class="btn btn-primary-modern" :disabled="!canEdit || form.processing" @click="submit">
            <span v-if="form.processing" class="spinner-icon"></span>
            {{ form.processing ? 'Saving...' : 'Update Settings' }}
          </button>
        </div>
      </div>

      <form class="form-content" @submit.prevent="submit">
        <div class="form-grid">
          <MiniSettingsNav :activeItem="'mail'" size="auto" :hideOnMobile="true" />

          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="card-header">
              <h2 class="card-title cardTitle">Connection Details</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="mail-settings-grid">
                <div>
                  <label class="form-label formLabel">Status</label>
                  <label class="switch-row">
                    <input v-model="form.active" type="checkbox" :disabled="!canEdit || form.processing" />
                    <span>{{ form.active ? 'Active' : 'Inactive' }}</span>
                  </label>
                </div>

                <InputField
                  id="vendor_from_address"
                  v-model="form.from_address"
                  label="FROM ADDRESS"
                  type="email"
                  placeholder="support@vendor.com"
                  :error="form.errors.from_address"
                  :disabled="!canEdit || form.processing"
                  :isRequired="form.active"
                />

                <InputField
                  id="vendor_from_name"
                  v-model="form.from_name"
                  label="FROM NAME"
                  placeholder="Vendor Restaurant"
                  :error="form.errors.from_name"
                  :disabled="!canEdit || form.processing"
                  :isRequired="form.active"
                />

                <SelectInput
                  id="vendor_mail_method"
                  v-model="form.mail_method"
                  label="MAIL METHOD"
                  :options="mailMethodOptions"
                  :error="form.errors.mail_method"
                  :disabled="!canEdit || form.processing"
                  :clearable="false"
                  isRequired
                />

                <InputField
                  id="vendor_smtp_host"
                  v-model="form.smtp_host"
                  label="SMTP HOST"
                  placeholder="mail.example.com"
                  :error="form.errors.smtp_host"
                  :disabled="!isSmtp || !canEdit || form.processing"
                  :isRequired="form.active && isSmtp"
                />

                <InputField
                  id="vendor_smtp_port"
                  v-model="form.smtp_port"
                  label="SMTP PORT"
                  type="number"
                  placeholder="587"
                  :error="form.errors.smtp_port"
                  :disabled="!isSmtp || !canEdit || form.processing"
                  :isRequired="form.active && isSmtp"
                />

                <InputField
                  id="vendor_smtp_username"
                  v-model="form.smtp_username"
                  label="SMTP USERNAME"
                  placeholder="support@vendor.com"
                  :error="form.errors.smtp_username"
                  :disabled="!isSmtp || !canEdit || form.processing"
                />

                <InputField
                  id="vendor_smtp_password"
                  v-model="form.smtp_password"
                  label="SMTP PASSWORD"
                  placeholder="SMTP password"
                  :error="form.errors.smtp_password"
                  :disabled="!isSmtp || !canEdit || form.processing"
                />

                <SelectInput
                  id="vendor_mail_encryption"
                  v-model="form.mail_encryption"
                  label="MAIL ENCRYPTION"
                  :options="encryptionOptions"
                  :error="form.errors.mail_encryption"
                  :disabled="!isSmtp || !canEdit || form.processing"
                  :clearable="false"
                />
              </div>

              <div v-if="form.errors.general" class="alert alert-danger mt-4">
                {{ form.errors.general }}
              </div>

              <div class="test-mail-panel">
                <div class="test-mail-grid">
                  <InputField
                    id="vendor_test_email"
                    v-model="testForm.test_email"
                    label="SENDER MAIL"
                    type="email"
                    placeholder="owner@example.com"
                    :error="testForm.errors.test_email"
                    :disabled="!canEdit || testForm.processing"
                    isRequired
                  />

                  <button class="btn btn-primary-modern test-mail-button" type="button"
                    :disabled="!canEdit || form.processing || testForm.processing" @click="sendTestMail">
                    <span v-if="testForm.processing" class="spinner-icon"></span>
                    {{ testForm.processing ? 'Sending...' : 'Test Mail' }}
                  </button>
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
import { Head, useForm } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import MiniSettingsNav from '@/Components/MiniSettingsNav.vue'
import InputField from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'

export default {
  name: 'VendorMailSettings',
  layout: VendorAdminLayout,
  components: { Head, MiniSettingsNav, InputField, SelectInput },

  props: {
    setting: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
  },

  data() {
    return {
      form: useForm({
        active: Boolean(this.setting?.active),
        from_address: this.setting?.from_address ?? '',
        from_name: this.setting?.from_name ?? '',
        mail_method: this.setting?.mail_method ?? 'smtp',
        smtp_host: this.setting?.smtp_host ?? '',
        smtp_port: this.setting?.smtp_port ?? '',
        smtp_username: this.setting?.smtp_username ?? '',
        smtp_password: this.setting?.smtp_password ?? '',
        mail_encryption: this.setting?.mail_encryption ?? 'tls',
      }),
      testForm: useForm({
        test_email: '',
      }),
      mailMethodOptions: [
        { id: 'smtp', name: 'SMTP' },
        { id: 'sendmail', name: 'Sendmail' },
        { id: 'log', name: 'Log' },
        { id: 'array', name: 'Array' },
      ],
      encryptionOptions: [
        { id: 'tls', name: 'TLS' },
        { id: 'ssl', name: 'SSL' },
        { id: 'none', name: 'None' },
      ],
    }
  },

  computed: {
    isSmtp() {
      return this.form.mail_method === 'smtp'
    },
    flash() {
      return this.$page.props.flash
    },
  },

  methods: {
    submit() {
      this.form
        .transform((data) => ({
          ...data,
          smtp_password: data.smtp_password || null,
          _method: 'PUT',
        }))
        .post(route('vendor.settings.mail.update'), {
          preserveScroll: true,
        })
    },
    sendTestMail() {
      this.testForm
        .transform(() => ({
          ...this.form.data(),
          test_email: this.testForm.test_email,
        }))
        .post(route('vendor.settings.mail.test'), {
          preserveScroll: true,
          onSuccess: () => alertSuccess('Test mail sent successfully.'),
          onError: (errors) => {
            const message =
              Object.values(errors || {})?.flat()?.[0] ||
              'Test mail failed. Please check the mail settings.'

            alertError(message)
          },
          onFinish: () => this.testForm.transform((data) => data),
        })
    },
  },

  watch: {
    flash: {
      handler(flash) {
        if (flash?.success) alertSuccess(flash.success)
        if (flash?.message) alertSuccess(flash.message)
        if (flash?.error) alertError(flash.error)
      },
      immediate: true,
      deep: true,
    },
  },
}
</script>

<style scoped>
.form-grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 40px;
}

.switch-row {
  min-height: 42px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 14px;
  border: 1px solid #d9e0e8;
  border-radius: 12px;
  font-weight: 800;
  color: #334155;
}

.mail-settings-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.35rem 1.6rem;
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

.test-mail-button {
  min-height: 42px;
  min-width: 116px;
  white-space: nowrap;
}

.test-mail-panel {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid #eef2f7;
}

.test-mail-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: end;
  gap: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .mail-settings-grid,
  .test-mail-grid {
    grid-template-columns: 1fr;
  }

  .test-mail-button {
    width: 100%;
  }
}
</style>
