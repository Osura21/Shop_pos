<script setup>
import { computed } from "vue"
import { useForm } from "@inertiajs/vue3"

const props = defineProps({
  customer: { type: Object, required: true },
  districts: { type: Array, default: () => [] },
  citiesAll: { type: Array, default: () => [] },
})

const form = useForm({
  name: props.customer?.name || "",
  email: props.customer?.email || "",
  gender: props.customer?.gender || "",
  dob: props.customer?.dob || "",
  c_job: props.customer?.c_job || "",
  district_id: props.customer?.district_id || "",
  city_id: props.customer?.city_id || "",
})

const citiesForDistrict = computed(() => {
  if (!form.district_id) return []
  return (props.citiesAll || []).filter(
    (c) => String(c.district_id) === String(form.district_id)
  )
})

const onDistrictChange = () => {
  form.city_id = ""
}

const save = () => {
  form.patch(route("customer.account.update"), {
    preserveScroll: true,
  })
}
</script>

<template>
  <div>
    <h5 class="fw-bold mb-3" :style="{ color: 'var(--purple)' }">My Profile</h5>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full name</label>
        <input v-model="form.name" class="form-control" />
        <div v-if="form.errors.name" class="text-danger small mt-1">{{ form.errors.name }}</div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input v-model="form.email" class="form-control" />
        <div v-if="form.errors.email" class="text-danger small mt-1">{{ form.errors.email }}</div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Location (District)</label>
        <select v-model="form.district_id" class="form-select" @change="onDistrictChange">
          <option value="">Select Location</option>
          <option v-for="d in districts" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <div v-if="form.errors.district_id" class="text-danger small mt-1">
          {{ form.errors.district_id }}
        </div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Sub location (City)</label>
        <select v-model="form.city_id" class="form-select" :disabled="!form.district_id">
          <option value="">Sublocation</option>
          <option v-for="c in citiesForDistrict" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <div v-if="form.errors.city_id" class="text-danger small mt-1">
          {{ form.errors.city_id }}
        </div>
      </div>
    </div>

    <button 
      class="btn btn-purple mt-4" 
      :disabled="form.processing" 
      @click="save"
    >
      Save changes
    </button>
  </div>
</template>

<style scoped>
.form-control, .form-select {
  border-radius: 12px;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
  border-color: var(--purple);
  box-shadow: 0 0 0 3px rgba(92, 45, 128, 0.1);
  outline: none;
}

.form-label {
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 6px;
  font-size: 0.9rem;
}

.btn-purple {
  background: linear-gradient(135deg, #5c2d80, #332e78);
  border: none;
  color: white;
  border-radius: 14px;
  padding: 12px 28px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 10px 20px -8px rgba(92, 45, 128, 0.3);
}

.btn-purple:hover:not(:disabled) {
  background: linear-gradient(135deg, #332e78, #5c2d80);
  transform: translateY(-2px);
  box-shadow: 0 15px 25px -10px rgba(92, 45, 128, 0.4);
}

.btn-purple:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.text-danger {
  color: #dc2626;
  font-size: 0.85rem;
}
</style>