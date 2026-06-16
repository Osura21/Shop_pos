<template>
    <!-- Delete Modal -->
    <div class="modal fade" tabindex="-1" ref="modalEl" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm-custom delete-modal">
            <div class="modal-content modal-saas">
                <div class="modal-body p-4">
                    <div class="delete-warning-icon">
                        <i :class="['bi', iconClass]"></i>
                    </div>
                    <h4 class="modal-saas-title">{{ title }}</h4>
                    <p class="modal-saas-text">
                        <slot name="description">
                            This will permanently remove
                            <strong>{{ targetName || 'this item' }}</strong>.
                            This action cannot be undone.
                        </slot>
                    </p>

                    <div class="modal-saas-footer mt-4">
                        <button type="button" class="btn-saas-secondary" @click="close" :disabled="loading">
                            {{ cancelButtonText }}
                        </button>

                        <button type="button" class="btn-saas-danger" @click="emit('confirm')"
                            :disabled="loading || !hasTarget">
                            <span v-if="!loading">{{ confirmButtonText }}</span>
                            <div v-else class="spinner-border spinner-border-sm"></div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const windowWidth = ref(window.innerWidth)

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    targetName: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Delete this item?',
    },
    cancelLabel: {
        type: String,
        default: 'Keep Item',
    },
    confirmLabel: {
        type: String,
        default: 'Delete Item',
    },
    iconClass: {
        type: String,
        default: 'bi-exclamation-triangle-fill',
    },
    targetId: {
        type: [String, Number],
        default: null,
    },
})

const emit = defineEmits([
    'update:show',
    'confirm',
    'closed',
])

const updateWidth = () => {
    windowWidth.value = window.innerWidth
}

const cancelButtonText = computed(() =>
    windowWidth.value <= 352 ? 'Keep' : props.cancelLabel
)

const confirmButtonText = computed(() =>
    windowWidth.value <= 352 ? 'Delete' : props.confirmLabel
)

const modalEl = ref(null)
let bsModal = null

const hasTarget = computed(() => !!props.targetId)

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
    (val) => {
        if (val) open()
        else close(true)
    }
)

onMounted(() => {
    window.addEventListener('resize', updateWidth)
    if (window.bootstrap?.Modal && modalEl.value) {
        bsModal = new window.bootstrap.Modal(modalEl.value)

        modalEl.value.addEventListener('hidden.bs.modal', () => {
            emit('update:show', false)
            emit('closed')
        })
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', updateWidth)

    try { bsModal?.dispose?.() } catch { }
    bsModal = null
})
</script>

<style scoped>
.modal-saas {
    border-radius: 24px;
    border: none;
}

.delete-modal {
    margin-left: auto !important;
    margin-right: auto !important;
}

.delete-warning-icon {
    width: 60px;
    height: 60px;
    background: #fef2f2;
    color: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto 1.5rem;
}

.modal-saas-title {
    font-weight: 800;
    text-align: center;
    color: var(--slate-900);
}

.modal-saas-text {
    text-align: center;
    color: var(--slate-600);
    font-size: 0.95rem;
}

.modal-saas-footer {
    display: flex;
    gap: 0.75rem;
}

.btn-saas-danger {
    flex: 1;
    padding: 0.75rem;
    border-radius: 12px;
    border: none;
    background: #ef4444;
    color: white;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-saas-secondary {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    background: #ffffff;
    color: #374151;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-saas-secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.btn-saas-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 575.98px) {
    .delete-modal {
        margin-left: 24px !important;
        margin-right: 24px !important;
    }
}
</style>