<template>
  <MiniSettingsNav :activeItem="'mail-recipients'" size="auto" :hideOnDesktop="true" />

  <Head title="Mail Recipients" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-envelope-paper me-2 text-warning"></i>Mail To
            </h1>
            <p class="header-subtitle">
              Configure who receives vendor emails, including CC and BCC recipients.
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
          <MiniSettingsNav :activeItem="'mail-recipients'" size="auto" :hideOnMobile="true" />

          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="card-header">
              <h2 class="card-title cardTitle">Recipient Details</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="mail-recipient-grid">
                <InputField
                  id="vendor_to_addresses"
                  v-model="form.to_addresses"
                  label="TO MAIL"
                  placeholder="owner@example.com, manager@example.com"
                  :error="form.errors.to_addresses"
                  :disabled="!canEdit || form.processing"
                />

                <InputField
                  id="vendor_cc_addresses"
                  v-model="form.cc_addresses"
                  label="CC MAIL"
                  placeholder="accounts@example.com"
                  :error="form.errors.cc_addresses"
                  :disabled="!canEdit || form.processing"
                />

                <InputField
                  id="vendor_bcc_addresses"
                  v-model="form.bcc_addresses"
                  label="BCC MAIL"
                  placeholder="audit@example.com"
                  :error="form.errors.bcc_addresses"
                  :disabled="!canEdit || form.processing"
                />
              </div>

              <div class="test-mail-panel">
                <button class="btn btn-primary-modern test-mail-button" type="button"
                  :disabled="!canEdit || form.processing" @click="sendTestMail">
                  <span v-if="form.processing" class="spinner-icon"></span>
                  Test Mail
                </button>
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
import { error as alertError, success as alertSuccess } from '@/Utils/modernAlert'

export default {
  name: 'VendorMailRecipients',
  layout: VendorAdminLayout,
  components: { Head, MiniSettingsNav, InputField },

  props: {
    setting: { type: Object, default: () => ({}) },
    canEdit: { type: Boolean, default: false },
  },

  data() {
    return {
      form: useForm({
        to_addresses: this.setting?.to_addresses ?? '',
        cc_addresses: this.setting?.cc_addresses ?? '',
        bcc_addresses: this.setting?.bcc_addresses ?? '',
      }),
    }
  },

  computed: {
    flash() {
      return this.$page.props.flash
    },
  },

  methods: {
    submit() {
      this.form
        .transform((data) => ({
          ...data,
          _method: 'PUT',
        }))
        .post(route('vendor.settings.mail.recipients.update'), {
          preserveScroll: true,
        })
    },
    sendTestMail() {
      this.form.post(route('vendor.settings.mail.recipients.test'), {
        preserveScroll: true,
        onSuccess: () => alertSuccess('Recipient test mail sent successfully.'),
        onError: (errors) => {
          const message =
            Object.values(errors || {})?.flat()?.[0] ||
            'Test mail failed. Please check the recipient details.'

          alertError(message)
        },
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

.mail-recipient-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.35rem 1.6rem;
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

.test-mail-panel {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid #eef2f7;
}

.test-mail-button {
  min-height: 42px;
  min-width: 116px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 768px) {
  .form-grid,
  .mail-recipient-grid {
    grid-template-columns: 1fr;
  }

  .test-mail-button {
    width: 100%;
  }
}
</style>
