<template>
  <div :class="className">
    <!-- Label -->
   <label :for="id" class="form-label formLabel mb-1">
  {{ label }}
  <span v-if="isRequired" class="text-danger ms-1">*</span>
</label>


    <!-- Input wrapper -->
    <div
      class="position-relative"
      :class="disabled ? 'opacity-50 pe-none' : ''"
    >
      <input
        :id="id"
        :type="resolvedType"
        class="form-control"
        :class="[
          error ? 'is-invalid' : '',
          isPassword ? 'pe-5' : ''
        ]"
        :placeholder="placeholder"
        :value="modelValue"
        :required="isRequired"
        :disabled="disabled"
        autocomplete="off"
        @input="$emit('update:modelValue', $event.target.value)"
        @change="$emit('change', $event.target.value)"
      />

      <!-- 👁 Password Toggle -->
      <button
        v-if="isPassword"
        type="button"
        class="password-toggle"
        @click="togglePassword"
        tabindex="-1"
      >
        <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
      </button>
    </div>

    <!-- Error -->
    <div v-if="error" class="invalid-feedback d-block">
      {{ error }}
    </div>
  </div>
</template>

<script>
export default {
  name: 'InputFieldBootstrap',
  emits: ['update:modelValue', 'change'],
  props: {
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },

    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },

    error: { type: String, default: '' },
    isRequired: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },

    className: { type: String, default: 'w-100' },
    
  },
  data() {
    return {
      showPassword: false,
    }
  },
  computed: {
    isPassword() {
      return this.type === 'password'
    },
    resolvedType() {
      return this.isPassword
        ? (this.showPassword ? 'text' : 'password')
        : this.type
    },
  },
  methods: {
    togglePassword() {
      this.showPassword = !this.showPassword
    },
  },
}
</script>


<style scoped>
/* ========== INPUT FIELD (MATCH SELECT) ========== */
.form-control {
  height: 42px;
  border-radius: 10px;
  border: 1px solid #d1d5db;
  padding: 0.55rem 0.75rem;
  font-size: 0.9rem;
  background-color: #fff;
  transition:
    border-color .2s ease,
    box-shadow .2s ease,
    background-color .2s ease;
}

.form-control::placeholder {
  color: #9ca3af;
}

.form-control:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}


/* Error */
.form-control.is-invalid {
  border-color: #ef4444;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
}

/* Disabled */
.opacity-50 {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Label */
/* .form-label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #374151;
} */

/* Error text */
.invalid-feedback {
  font-size: 0.8rem;
  margin-top: 4px;
}

/* ========== PASSWORD TOGGLE ========== */
.password-toggle {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
}

.password-toggle:hover {
  color: #2563eb;
}
</style>
