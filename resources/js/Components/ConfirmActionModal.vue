<template>
  <div class="modal fade" tabindex="-1" ref="modalEl" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered confirm-action-modal">
      <div class="modal-content confirm-action-card">
        <div class="modal-body">
          <div class="confirm-action-icon" :class="iconToneClass">
            <i :class="['bi', iconClass]"></i>
          </div>

          <h4 class="confirm-action-title">{{ title }}</h4>

          <p class="confirm-action-text">
            <slot name="description">
              {{ description }}
            </slot>
          </p>

          <div v-if="targetName" class="confirm-action-target">
            {{ targetName }}
          </div>

          <div class="confirm-action-footer">
            <button type="button" class="confirm-action-btn confirm-action-btn--secondary" @click="close"
              :disabled="loading">
              {{ cancelLabel }}
            </button>

            <button type="button" class="confirm-action-btn confirm-action-btn--primary" @click="emit('confirm')"
              :disabled="loading || !hasTarget">
              <span v-if="!loading">{{ confirmLabel }}</span>
              <span v-else class="spinner-border spinner-border-sm"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  targetId: { type: [String, Number], default: null },
  targetName: { type: String, default: '' },
  title: { type: String, default: 'Confirm action?' },
  description: { type: String, default: 'Please confirm before continuing.' },
  cancelLabel: { type: String, default: 'Cancel' },
  confirmLabel: { type: String, default: 'Confirm' },
  iconClass: { type: String, default: 'bi-check2-circle' },
  tone: { type: String, default: 'orange' },
})

const emit = defineEmits(['update:show', 'confirm', 'closed'])

const modalEl = ref(null)
let bsModal = null

const hasTarget = computed(() => props.targetId !== null && props.targetId !== undefined && props.targetId !== '')
const iconToneClass = computed(() => `confirm-action-icon--${props.tone}`)

function open() {
  bsModal?.show()
}

function close(force = false) {
  if (!force && props.loading) return
  bsModal?.hide()
}

defineExpose({ open, close })

watch(
  () => props.show,
  (value) => {
    if (value) open()
    else close(true)
  }
)

onMounted(() => {
  if (window.bootstrap?.Modal && modalEl.value) {
    bsModal = new window.bootstrap.Modal(modalEl.value)

    modalEl.value.addEventListener('hidden.bs.modal', () => {
      emit('update:show', false)
      emit('closed')
    })
  }
})

onUnmounted(() => {
  try { bsModal?.dispose?.() } catch { }
  bsModal = null
})
</script>

<style scoped>
.confirm-action-modal {
  max-width: 440px;
  margin-left: auto;
  margin-right: auto;
}

.confirm-action-card {
  border: 1px solid rgba(226, 232, 240, 0.95);
  border-radius: 22px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
}

.modal-body {
  padding: 28px;
}

.confirm-action-icon {
  width: 58px;
  height: 58px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 18px;
  font-size: 1.7rem;
}

.confirm-action-icon--orange {
  background: #fff7ed;
  color: #f97316;
  border: 1px solid #fed7aa;
}

.confirm-action-icon--green {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.confirm-action-title {
  color: #0f172a;
  font-size: 1.2rem;
  font-weight: 800;
  text-align: center;
  margin: 0;
}

.confirm-action-text {
  color: #64748b;
  font-size: 0.95rem;
  line-height: 1.55;
  text-align: center;
  margin: 10px 0 0;
}

.confirm-action-target {
  margin-top: 16px;
  padding: 10px 14px;
  border-radius: 12px;
  background: #f8fafc;
  color: #334155;
  font-size: 0.9rem;
  font-weight: 700;
  text-align: center;
}

.confirm-action-footer {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 24px;
}

.confirm-action-btn {
  min-height: 44px;
  border-radius: 999px;
  font-weight: 800;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.confirm-action-btn--secondary {
  background: #ffffff;
  color: #475569;
  border-color: #cbd5e1;
}

.confirm-action-btn--primary {
  background: linear-gradient(135deg, #ff8a00, #f97316);
  color: #ffffff;
  box-shadow: 0 12px 22px rgba(249, 115, 22, 0.22);
}

.confirm-action-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 575.98px) {
  .confirm-action-modal {
    margin-left: 20px;
    margin-right: 20px;
  }

  .confirm-action-footer {
    grid-template-columns: 1fr;
  }
}
</style>
