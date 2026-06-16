<template>
  <div class="premium-dp-wrapper" :class="{ 'is-time-enabled': timeEnabled }">
    <VueDatePicker
      v-model="internalValue"
      :enable-time-picker="timeEnabled"
      :placeholder="placeholder"
      :min-date="minDate"
      hide-offset-dates
      auto-apply
    >
      <template #trigger>
        <div class="dp-custom-trigger">
          <div class="trigger-content">
            <span class="label" v-if="!internalValue">{{ placeholder }}</span>
            <span class="value" v-else>{{ formattedDisplay }}</span>
          </div>
          <div class="trigger-icon">
            <i class="bi bi-calendar3"></i>
          </div>
        </div>
      </template>
    </VueDatePicker>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
  modelValue: [String, Date],
  timeEnabled: { type: Boolean, default: false },
  placeholder: { type: String, default: "Select Date" },
  minDate: [String, Date]
});

const emit = defineEmits(['update:modelValue']);
const internalValue = ref(props.modelValue);

watch(() => props.modelValue, (newVal) => internalValue.value = newVal);
watch(internalValue, (newVal) => emit('update:modelValue', newVal));

const formattedDisplay = computed(() => {
  if (!internalValue.value) return "";
  const d = new Date(internalValue.value);
  return props.timeEnabled 
    ? d.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' }) 
    : d.toLocaleDateString([], { dateStyle: 'long' });
});
</script>

<style scoped>
.premium-dp-wrapper {
  --dp-primary-color: #f97316;
  --dp-border-radius: 16px;
}

.dp-custom-trigger {
  height: 42px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  margin-top: -4px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 18px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dp-custom-trigger:active {
  border-color: #f97316;
  box-shadow: 0 4px 15px rgba(249, 115, 22, 0.1);
}

.trigger-content .label { color: #9ca3af; font-size: 14px; }
.trigger-content .value { color: #111827; font-weight: 500; font-size: 15px; }
.trigger-icon { color: #f97316; }
</style>

<style>
.dp__menu {
  border-radius: 20px !important;
  border: 1px solid rgba(249, 115, 22, 0.1) !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
  overflow: hidden !important;
  font-family: inherit;
}

.dp__overlay_cell_active{
    background: linear-gradient(135deg, #f28c00 0%, #f5a623 100%) !important;
}

.dp__calendar_header_item {
  text-transform: uppercase;
  font-size: 11px;
  font-weight: 700;
  color: #9ca3af;
}

.dp__cell_inner {
  border-radius: 12px !important;
}

.dp__active_date {
    background: linear-gradient(135deg, #f28c00 0%, #f5a623 100%) !important;
}

.dp__today {
  border: 1px solid #f97316 !important;
}

.premium-dp-wrapper .dp__button_bottom {
  display: none !important;
}

.premium-dp-wrapper.is-time-enabled .dp__button_bottom {
  display: flex !important;
  background: rgba(255, 255, 255, 0.5);
  border-top: 1px solid #f3f4f6;
  padding: 12px;
}

.dp__button_bottom {
  background: #fffbf4 !important;
  font-weight: 700 !important;
}

.dp__action_cancel {
  color: #9ca3af !important;
}

</style>