<template>
  <MiniSettingsNav :activeItem="'kitchen-alert'" size="auto" :hideOnDesktop="true" />

  <Head title="Kitchen Alert Sound" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-bell me-2 text-warning"></i>Kitchen Alert Sound
            </h1>
            <p class="header-subtitle">
              Configure the kitchen viewer alert sound for newly placed orders.
            </p>
          </div>

          <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
            <span v-if="form.processing" class="spinner-icon"></span>
            {{ form.processing ? 'Saving...' : 'Update Settings' }}
          </button>
        </div>
      </div>

      <form class="form-content" @submit.prevent="submit">
        <div class="form-grid">
          <MiniSettingsNav :activeItem="'kitchen-alert'" size="auto" :hideOnMobile="true" />

          <div class="form-card">
            <div class="card-accent-line"></div>
            <div class="card-header">
              <h2 class="card-title cardTitle">Alert Configuration</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="kitchen-alert-settings">
                <div class="kitchen-alert-settings__intro">
                  <div class="kitchen-alert-settings__icon">
                    <i class="bi bi-bell"></i>
                  </div>
                  <div>
                    <h3>Kitchen Alert Sound</h3>
                    <p>Play a selected sound when a new POS or PMS order arrives in the kitchen viewer.</p>
                  </div>
                </div>

                <div class="kitchen-alert-settings__controls">
                  <label class="sound-toggle">
                    <input v-model="form.sound_enabled" type="checkbox" />
                    <span class="sound-toggle__track">
                      <span class="sound-toggle__thumb"></span>
                    </span>
                    <span class="sound-toggle__text">
                      {{ form.sound_enabled ? 'Sound Enabled' : 'Sound Disabled' }}
                    </span>
                  </label>

                  <div class="sound-select">
                    <label class="form-label formLabel">Alert Sound</label>
                    <SelectInput
                      id="sound"
                      v-model="form.sound"
                      :options="sounds"
                      valueKey="value"
                      labelKey="label"
                      placeholder="Select Alert Sound"
                    />
                    <div v-if="form.errors.sound" class="error-text">
                      {{ form.errors.sound }}
                    </div>
                  </div>

                  <button type="button" class="sound-test-btn" @click="testKitchenAlertSound">
                    <i class="bi bi-play-fill"></i>
                    Test Sound
                  </button>
                </div>
              </div>

              <div v-if="form.errors.general" class="alert alert-danger mt-4">
                {{ form.errors.general }}
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
import SelectInput from '@/Components/SelectInput.vue'
import { success as alertSuccess } from '@/Utils/modernAlert'

export default {
  name: 'KitchenAlertSettings',
  layout: VendorAdminLayout,
  components: { Head, MiniSettingsNav, SelectInput },

  props: {
    setting: { type: Object, default: () => ({}) },
    sounds: { type: Array, default: () => [] },
  },

  data() {
    return {
      form: useForm({
        sound_enabled: this.setting?.sound_enabled ?? true,
        sound: this.setting?.sound ?? 'bell',
      }),
    }
  },

  methods: {
    testKitchenAlertSound() {
      const AudioContext = window.AudioContext || window.webkitAudioContext

      if (!AudioContext) return

      const context = new AudioContext()
      const sound = this.form.sound || 'bell'
      const patterns = {
        bell: [[880, 0, 0.13], [1175, 0.16, 0.18]],
        chime: [[659, 0, 0.12], [880, 0.14, 0.12], [1318, 0.28, 0.2]],
        ding: [[1046, 0, 0.16]],
        pulse: [[523, 0, 0.1], [523, 0.14, 0.1], [784, 0.28, 0.16]],
        classic: [[740, 0, 0.18], [740, 0.24, 0.18]],
        double_chime: [[784, 0, 0.12], [1046, 0.12, 0.16], [784, 0.34, 0.12], [1318, 0.46, 0.2]],
        urgent: [[988, 0, 0.08], [988, 0.12, 0.08], [988, 0.24, 0.08], [1318, 0.38, 0.18]],
      }

      ;(patterns[sound] || patterns.bell).forEach(([frequency, delay, duration]) => {
        const oscillator = context.createOscillator()
        const gain = context.createGain()
        const startAt = context.currentTime + delay

        oscillator.type = ['classic', 'urgent'].includes(sound) ? 'square' : 'sine'
        oscillator.frequency.setValueAtTime(frequency, startAt)
        gain.gain.setValueAtTime(0.0001, startAt)
        gain.gain.exponentialRampToValueAtTime(0.26, startAt + 0.02)
        gain.gain.exponentialRampToValueAtTime(0.0001, startAt + duration)

        oscillator.connect(gain)
        gain.connect(context.destination)
        oscillator.start(startAt)
        oscillator.stop(startAt + duration + 0.02)
      })
    },

    submit() {
      this.form
        .transform((data) => ({
          ...data,
          sound_enabled: Boolean(data.sound_enabled),
          _method: 'PUT',
        }))
        .post(route('vendor.settings.kitchen-alert.update'), {
          preserveScroll: true,
          onSuccess: () => {
            this.showSuccessToast(this.$page?.props?.flash?.success || 'Kitchen alert sound settings updated successfully.')
          },
        })
    },

    showSuccessToast(message) {
      alertSuccess(message, { duration: 3000 })
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

.kitchen-alert-settings {
  display: grid;
  gap: 18px;
  padding: 18px;
  border: 1px solid rgba(251, 146, 60, 0.24);
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(255, 247, 237, 0.78), rgba(255, 255, 255, 0.96));
}

.kitchen-alert-settings__intro {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.kitchen-alert-settings__icon {
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: #ffedd5;
  color: #f97316;
  font-size: 20px;
  flex-shrink: 0;
}

.kitchen-alert-settings__intro h3 {
  margin: 0;
  color: #1f2937;
  font-size: 17px;
  font-weight: 900;
}

.kitchen-alert-settings__intro p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.45;
}

.kitchen-alert-settings__controls {
  display: grid;
  grid-template-columns: minmax(210px, auto) minmax(260px, 1fr) auto;
  gap: 14px;
  align-items: end;
}

.sound-toggle {
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
  border: 1px solid #fed7aa;
  border-radius: 13px;
  background: rgba(255, 255, 255, 0.78);
  cursor: pointer;
}

.sound-toggle input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.sound-toggle__track {
  width: 42px;
  height: 24px;
  border-radius: 999px;
  background: #cbd5e1;
  padding: 3px;
  transition: background 0.16s ease;
}

.sound-toggle__thumb {
  display: block;
  width: 18px;
  height: 18px;
  border-radius: 999px;
  background: #fff;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.22);
  transition: transform 0.16s ease;
}

.sound-toggle input:checked + .sound-toggle__track {
  background: #22c55e;
}

.sound-toggle input:checked + .sound-toggle__track .sound-toggle__thumb {
  transform: translateX(18px);
}

.sound-toggle__text {
  color: #334155;
  font-size: 13px;
  font-weight: 800;
}

.sound-select :deep(.form-group) {
  margin-bottom: 0;
}

.sound-test-btn {
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid #fdba74;
  border-radius: 13px;
  background: #fff7ed;
  color: #ea580c;
  padding: 0 16px;
  font-weight: 900;
  cursor: pointer;
  white-space: nowrap;
}

.sound-test-btn:hover {
  border-color: #fb923c;
  background: #ffedd5;
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
  .kitchen-alert-settings__controls {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
