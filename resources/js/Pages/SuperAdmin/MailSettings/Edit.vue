<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import InputField from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'

defineOptions({ layout: SuperAdminLayout })

const props = defineProps({
  setting: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
})

const form = useForm({
  from_address: props.setting.from_address ?? '',
  from_name: props.setting.from_name ?? '',
  mail_method: props.setting.mail_method ?? 'smtp',
  smtp_host: props.setting.smtp_host ?? '',
  smtp_port: props.setting.smtp_port ?? '',
  smtp_username: props.setting.smtp_username ?? '',
  smtp_password: props.setting.smtp_password ?? '',
  mail_encryption: props.setting.mail_encryption ?? 'tls',
  smtp_verify_peer: props.setting.smtp_verify_peer ?? true,
})

const testForm = useForm({
  test_email: '',
})

const mailMethodOptions = [
  { id: 'smtp', name: 'SMTP' },
  { id: 'sendmail', name: 'Sendmail' },
  { id: 'log', name: 'Log' },
  { id: 'array', name: 'Array' },
]

const encryptionOptions = [
  { id: 'tls', name: 'TLS' },
  { id: 'ssl', name: 'SSL' },
  { id: 'none', name: 'None' },
]

const verifyPeerOptions = [
  { id: true, name: 'Yes' },
  { id: false, name: 'No' },
]

const isSmtp = computed(() => form.mail_method === 'smtp')
const passwordPlaceholder = computed(() =>
  props.setting.has_smtp_password ? 'Saved SMTP password' : 'SMTP password'
)

function submit() {
  form.put(route('settings.mail.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('smtp_password')
      alertSuccess('Mail settings updated successfully.')
    },
    onError: (errors) => {
      const message =
        Object.values(errors || {})?.flat()?.[0] ||
        'Please check the highlighted fields.'

      alertError(message)
    },
  })
}

function sendTestMail() {
  testForm
    .transform(() => ({
      ...form.data(),
      test_email: testForm.test_email,
    }))
    .post(route('settings.mail.test'), {
      preserveScroll: true,
      onSuccess: () => {
        alertSuccess('Test mail sent successfully.')
      },
      onError: (errors) => {
        const message =
          Object.values(errors || {})?.flat()?.[0] ||
          'Test mail failed. Please check the mail settings.'

        alertError(message)
      },
      onFinish: () => testForm.transform((data) => data),
    })
}
</script>

<template>
  <div class="mail-settings-page">
    <Head title="Mail Settings" />

    <div class="form-container">
      <div class="gradient-overlay"></div>

      <div class="form-wrapper">
        <div class="form-header formHeader">
          <div>
            <h2 class="header-title">Mail Settings</h2>
            <p class="header-subtitle">Configure Mail settings.</p>
          </div>
        </div>

        <form class="form-card" @submit.prevent="submit">
          <div class="card-body formCardBody">
            <div class="mail-grid">
              <InputField
                id="from_address"
                v-model="form.from_address"
                label="FROM ADDRESS"
                placeholder="support@saasbeds.com"
                :error="form.errors.from_address"
                :disabled="!canEdit || form.processing"
                isRequired
              />

              <InputField
                id="from_name"
                v-model="form.from_name"
                label="FROM NAME"
                placeholder="Saasbeds.com"
                :error="form.errors.from_name"
                :disabled="!canEdit || form.processing"
                isRequired
              />

              <SelectInput
                id="mail_method"
                v-model="form.mail_method"
                label="MAIL METHOD"
                :options="mailMethodOptions"
                :error="form.errors.mail_method"
                :disabled="!canEdit || form.processing"
                :clearable="false"
                isRequired
              />

              <InputField
                id="smtp_host"
                v-model="form.smtp_host"
                label="SMTP HOST"
                placeholder="mail.smardove.com"
                :error="form.errors.smtp_host"
                :disabled="!isSmtp || !canEdit || form.processing"
                :isRequired="isSmtp"
              />

              <InputField
                id="smtp_port"
                v-model="form.smtp_port"
                label="SMTP PORT"
                type="number"
                placeholder="587"
                :error="form.errors.smtp_port"
                :disabled="!isSmtp || !canEdit || form.processing"
                :isRequired="isSmtp"
              />

              <InputField
                id="smtp_username"
                v-model="form.smtp_username"
                label="SMTP USERNAME"
                placeholder="support@saasbeds.com"
                :error="form.errors.smtp_username"
                :disabled="!isSmtp || !canEdit || form.processing"
              />

              <InputField
                id="smtp_password"
                v-model="form.smtp_password"
                label="SMTP PASSWORD"
                type="text"
                :placeholder="passwordPlaceholder"
                :error="form.errors.smtp_password"
                :disabled="!isSmtp || !canEdit || form.processing"
              />

              <SelectInput
                id="mail_encryption"
                v-model="form.mail_encryption"
                label="MAIL ENCRYPTION"
                :options="encryptionOptions"
                :error="form.errors.mail_encryption"
                :disabled="!isSmtp || !canEdit || form.processing"
                :clearable="false"
              />

              <SelectInput
                id="smtp_verify_peer"
                v-model="form.smtp_verify_peer"
                label="SSL VERIFY"
                :options="verifyPeerOptions"
                :error="form.errors.smtp_verify_peer"
                :disabled="!isSmtp || !canEdit || form.processing"
                :clearable="false"
              />
            </div>

            <div class="form-actions">
              <button
                type="submit"
                class="btn btn-primary-modern"
                :disabled="!canEdit || form.processing"
              >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" />
                Update
              </button>
            </div>
          </div>
        </form>

        <form class="form-card test-mail-card" @submit.prevent="sendTestMail">
          <div class="card-body formCardBody">
            <div class="test-mail-row">
              <InputField
                id="test_email"
                v-model="testForm.test_email"
                label="SENDER MAIL"
                placeholder="owner@example.com"
                :error="testForm.errors.test_email"
                :disabled="!canEdit || testForm.processing"
                isRequired
              />

              <button
                type="submit"
                class="btn btn-primary-modern test-mail-button"
                :disabled="!canEdit || form.processing || testForm.processing"
              >
                <span v-if="testForm.processing" class="spinner-border spinner-border-sm me-2" />
                Test Mail
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mail-settings-page {
  min-height: 100%;
}

.mail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.35rem 1.6rem;
}

.form-actions {
  display: flex;
  align-items: center;
  margin-top: 1.45rem;
}

.btn-primary-modern {
  min-width: 94px;
}

.test-mail-card {
  margin-top: 1rem;
}

.test-mail-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: end;
  gap: 1rem;
}

.test-mail-button {
  min-width: 116px;
  height: 42px;
  white-space: nowrap;
}

@media (max-width: 768px) {
  .mail-grid {
    grid-template-columns: 1fr;
  }

  .test-mail-row {
    grid-template-columns: 1fr;
  }

  .test-mail-button {
    width: 100%;
  }
}
</style>
