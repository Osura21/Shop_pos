<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import axios from "axios"

const props = defineProps({
  phone: { type: String, default: "" },
  redirectTo: { type: String, default: "/" },
})

const emit = defineEmits(["back", "success"])

const loading = ref(false)
const errorMsg = ref("")
const fieldErrors = ref({}) // Laravel validation errors

const form = ref({
  phone: props.phone,
  name: "",
  email: "",
  gender: "",
  location: "",
  c_job: "",
  dob: "", // YYYY-MM-DD
})

watch(
  () => props.phone,
  (v) => {
    form.value.phone = v || ""
  }
)

/* ---------------- UI: Gender dropdown ---------------- */
const genderOpen = ref(false)
const genders = [
  { value: "male", label: "Male" },
  { value: "female", label: "Female" },
  { value: "other", label: "Other" },
]
const genderLabel = computed(() => {
  return genders.find((g) => g.value === form.value.gender)?.label || "Select gender"
})

const closeAllPopups = () => {
  genderOpen.value = false
}

const onWindowClick = (e) => {
  // Close dropdown when clicking outside
  const target = e.target
  if (!target?.closest?.(".custom-select")) closeAllPopups()
}

onMounted(() => window.addEventListener("click", onWindowClick))
onBeforeUnmount(() => window.removeEventListener("click", onWindowClick))

/* ---------------- UI: DOB (DD / MM / YYYY) ---------------- */
const dobDay = ref("")
const dobMonth = ref("")
const dobYear = ref("")

const currentYear = new Date().getFullYear()
const minYear = 1900
const maxYear = currentYear - 18

const numbersOnly = (val) => String(val || "").replace(/\D/g, "")

const updateDob = () => {
  if (!dobDay.value || !dobMonth.value || !dobYear.value) {
    form.value.dob = ""
    return
  }

  const y = parseInt(dobYear.value, 10)
  const m = parseInt(dobMonth.value, 10)
  let d = parseInt(dobDay.value, 10)

  if (!y || !m || !d) {
    form.value.dob = ""
    return
  }

  // clamp month
  const month = Math.min(Math.max(m, 1), 12)

  // max days in month
  const maxDays = new Date(y, month, 0).getDate()
  if (d > maxDays) d = maxDays
  if (d < 1) d = 1

  dobMonth.value = String(month)
  dobDay.value = String(d)

  form.value.dob = `${String(y).padStart(4, "0")}-${String(month).padStart(2, "0")}-${String(d).padStart(2, "0")}`
}

const onDayInput = (e) => {
  clearErrors()
  let val = numbersOnly(e.target.value).slice(0, 2)
  if (!val) {
    dobDay.value = ""
    updateDob()
    return
  }
  let num = parseInt(val, 10)
  if (num < 1) num = 1
  if (num > 31) num = 31
  dobDay.value = String(num)
  updateDob()
}

const onMonthInput = (e) => {
  clearErrors()
  let val = numbersOnly(e.target.value).slice(0, 2)
  if (!val) {
    dobMonth.value = ""
    updateDob()
    return
  }
  let num = parseInt(val, 10)
  if (num < 1) num = 1
  if (num > 12) num = 12
  dobMonth.value = String(num)
  updateDob()
}

const onYearInput = (e) => {
  clearErrors()
  let val = numbersOnly(e.target.value).slice(0, 4)
  if (!val) {
    dobYear.value = ""
    updateDob()
    return
  }
  // clamp only when 4 digits
  if (val.length === 4) {
    let num = parseInt(val, 10)
    if (num < minYear) num = minYear
    if (num > maxYear) num = maxYear
    val = String(num)
  }
  dobYear.value = val
  updateDob()
}

const onYearBlur = () => {
  if (!dobYear.value) return
  if (dobYear.value.length < 4) dobYear.value = String(minYear)
  updateDob()
}

/* ---------------- Validation + submit ---------------- */
const clearErrors = () => {
  errorMsg.value = ""
  fieldErrors.value = {}
}

const isEmailValid = (email) => {
  if (!email) return true
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

const canSubmit = computed(() => {
  if (loading.value) return false
  if (!form.value.phone) return false
  if (!form.value.name?.trim()) return false
  return true
})

const submit = async () => {
  clearErrors()

  // basic client-side validation
  if (!form.value.name?.trim()) {
    fieldErrors.value = { ...fieldErrors.value, name: ["Full name is required."] }
    return
  }
  if (!isEmailValid(form.value.email)) {
    fieldErrors.value = { ...fieldErrors.value, email: ["Please enter a valid email address."] }
    return
  }

  loading.value = true
  try {
    await axios.post(route("otp.register.store"), {
      ...form.value,
      redirect: props.redirectTo,
    })

    emit("success")
  } catch (e) {
    const data = e?.response?.data
    // Laravel 422 validation
    if (e?.response?.status === 422) {
      fieldErrors.value = data?.errors || {}
      errorMsg.value = data?.message || "Please fix the highlighted fields."
      return
    }

    errorMsg.value = data?.message || "Registration failed."
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="wrap">
    <!-- LEFT SECTION  -->
    <div class="left">
      <div class="bg-pattern"></div>
      <div class="floating-elements">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
      </div>
      
      <div class="overlay">
        <div class="leftInfo">
          <!-- Header with icon -->
          <div class="header-wrapper">
            <div class="icon-wrapper">
              <i class="fa-solid fa-user-plus"></i>
            </div>
            <h2 class="leftTitle">Create Account</h2>
          </div>
          
          <p class="leftSub">
            Complete registration to continue and access your account features.
          </p>

          <div class="leftList">
            <div class="leftItem">
              <div class="leftItemIcon">
                <i class="fa-solid fa-user-plus"></i>
              </div>
              <div class="leftText">
                <div class="leftItemTitle">Quick sign up</div>
                <div class="leftItemSub">Register in seconds with your mobile number.</div>
              </div>
            </div>

            <div class="leftItem">
              <div class="leftItemIcon">
                <i class="fa-solid fa-heart"></i>
              </div>
              <div class="leftText">
                <div class="leftItemTitle">Save favorites</div>
                <div class="leftItemSub">Bookmark ads and view them later anytime.</div>
              </div>
            </div>

            <div class="leftItem">
              <div class="leftItemIcon">
                <i class="fa-solid fa-clipboard-list"></i>
              </div>
              <div class="leftText">
                <div class="leftItemTitle">Manage your ads</div>
                <div class="leftItemSub">Edit, pause, and track everything in one place.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT SECTION (Enhanced) -->
    <div class="right">
      <div class="form-container">
        <button type="button" class="back" @click="emit('back')">
          <i class="fa-solid fa-arrow-left"></i>
          Back to Login
        </button>

        <div class="header">
          <div class="header-icon">
            <i class="fa-solid fa-user-plus"></i>
          </div>
          <h3 class="title">Sign Up</h3>
          <p class="subtitle">Complete your profile to get started</p>
        </div>

        <div v-if="errorMsg" class="err">
          <i class="fa-solid fa-exclamation-circle"></i>
          {{ errorMsg }}
        </div>

        <form @submit.prevent="submit">
          <div class="form-grid">
            <!-- Phone -->
            <div class="field">
              <label>
                <i class="fa-solid fa-phone"></i>
                Mobile Number
              </label>
              <div class="input-with-icon">
                <input class="input" v-model="form.phone" disabled />
              </div>
              <div v-if="fieldErrors.phone?.[0]" class="error-text">
                <i class="fa-solid fa-exclamation"></i>
                {{ fieldErrors.phone[0] }}
              </div>
            </div>

            <!-- Full Name -->
            <div class="field">
              <label>
                <i class="fa-solid fa-user"></i>
                Full Name
              </label>
              <div class="input-with-icon">
                <input class="input" v-model="form.name" placeholder="John Doe" @input="clearErrors" />
              </div>
              <div v-if="fieldErrors.name?.[0]" class="error-text">
                <i class="fa-solid fa-exclamation"></i>
                {{ fieldErrors.name[0] }}
              </div>
            </div>

            <!-- Email -->
            <div class="field">
              <label>
                <i class="fa-solid fa-envelope"></i>
                Email (optional)
              </label>
              <div class="input-with-icon">
                <input class="input" v-model="form.email" placeholder="john@example.com" @input="clearErrors" />
              </div>
              <div v-if="fieldErrors.email?.[0]" class="error-text">
                <i class="fa-solid fa-exclamation"></i>
                {{ fieldErrors.email[0] }}
              </div>
            </div>

            <!-- Gender -->
            <div class="field">
              <label>
                <i class="fa-solid fa-venus-mars"></i>
                Gender
              </label>
              <div class="custom-select">
                <button type="button" class="custom-select-btn" @click.stop="genderOpen = !genderOpen">
                  <span>{{ genderLabel }}</span>
                  <span class="arrow"></span>
                </button>

                <div v-if="genderOpen" class="custom-select-menu">
                  <div
                    v-for="g in genders"
                    :key="g.value"
                    class="custom-option"
                    @click="form.gender = g.value; genderOpen = false; clearErrors()"
                  >
                    <i :class="g.value === 'male' ? 'fa-solid fa-mars' : g.value === 'female' ? 'fa-solid fa-venus' : 'fa-solid fa-genderless'"></i>
                    {{ g.label }}
                  </div>
                </div>
              </div>
              <div v-if="fieldErrors.gender?.[0]" class="error-text">
                <i class="fa-solid fa-exclamation"></i>
                {{ fieldErrors.gender[0] }}
              </div>
            </div>

            <!-- DOB -->
            <div class="field">
              <label>
                <i class="fa-solid fa-calendar-alt"></i>
                Date of Birth (optional)
              </label>
              <div class="dob-inline-grid">
                <div class="dob-input-wrapper">
                  <input
                    v-model="dobDay"
                    type="text"
                    inputmode="numeric"
                    maxlength="2"
                    placeholder="DD"
                    class="dob-input"
                    @input="onDayInput"
                  />
                </div>
                <div class="dob-input-wrapper">
                  <input
                    v-model="dobMonth"
                    type="text"
                    inputmode="numeric"
                    maxlength="2"
                    placeholder="MM"
                    class="dob-input"
                    @input="onMonthInput"
                  />
                </div>
                <div class="dob-input-wrapper">
                  <input
                    v-model="dobYear"
                    type="text"
                    inputmode="numeric"
                    maxlength="4"
                    placeholder="YYYY"
                    class="dob-input"
                    @input="onYearInput"
                    @blur="onYearBlur"
                  />
                </div>
              </div>
              <div v-if="fieldErrors.dob?.[0]" class="error-text">
                <i class="fa-solid fa-exclamation"></i>
                {{ fieldErrors.dob[0] }}
              </div>
            </div>
          </div>

          <button class="btn" type="submit" :disabled="!canSubmit">
            <span v-if="loading">
              <i class="fa-solid fa-spinner fa-spin"></i>
              CREATING ACCOUNT...
            </span>
            <span v-else>
              <i class="fa-solid fa-user-plus"></i>
              CREATE ACCOUNT
            </span>
          </button>

        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wrap {
  display: flex;
  grid-template-columns: 1fr 1fr;
  height: 560px;
  border-radius: 22px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 25px 60px rgba(20, 20, 40, 0.15);
}

/* ========== LEFT SECTION ========== */
.left {
  width: 35%;
  background: 
    radial-gradient(ellipse at 20% 30%, rgba(92, 45, 128, 0.4) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 70%, rgba(51, 46, 120, 0.4) 0%, transparent 50%),
    linear-gradient(135deg, #332e78, #5c2d80);
  position: relative;
  overflow: hidden;
}

/* Background pattern */
.bg-pattern {
  position: absolute;
  inset: 0;
  background-image: 
    radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.08) 2px, transparent 2px),
    radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
  background-size: 60px 60px, 40px 40px;
  opacity: 0.3;
}

/* Floating elements */
.floating-elements {
  position: absolute;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  filter: blur(40px);
}

.circle-1 {
  width: 200px;
  height: 200px;
  top: -80px;
  right: -80px;
  animation: float 25s infinite ease-in-out;
}

.circle-2 {
  width: 150px;
  height: 150px;
  bottom: 60px;
  left: -60px;
  animation: float 20s infinite ease-in-out reverse;
}

.circle-3 {
  width: 120px;
  height: 120px;
  top: 40%;
  right: 30%;
  animation: float 30s infinite ease-in-out;
}

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

.overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  padding: 48px;
  color: #fff;
  text-align: left;
  z-index: 2;
}

.leftInfo {
  max-width: 420px;
  width: 100%;
  display: grid;
  gap: 24px;
}

/* Header with icon */
.header-wrapper {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 6px;
}

.icon-wrapper {
  width: 50px;
  height: 50px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  display: grid;
  place-items: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.icon-wrapper i {
  font-size: 20px;
  color: rgba(255, 255, 255, 0.95);
}

.leftTitle {
  font-size: 28px;
  font-weight: 800;
  margin: 0;
  letter-spacing: .2px;
  background: linear-gradient(to right, #ffffff, rgba(255, 255, 255, 0.9));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.2;
}

.leftSub {
  margin: 0;
  opacity: 0.9;
  font-weight: 600;
  line-height: 1.6;
  font-size: 15px;
  max-width: 300px;
}

.leftList {
  display: grid;
  gap: 20px;
  margin-top: 8px;
}

.leftItem {
  display: grid;
  grid-template-columns: 30px 1fr;
  gap: 16px;
  align-items: start;
  padding: 0;
  background: transparent;
  border: none;
  backdrop-filter: none;
  transition: all 0.3s ease;
}

.leftItem:hover {
  transform: translateY(-2px);
}

.leftItemIcon {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, 
    rgba(255, 255, 255, 0.15), 
    rgba(255, 255, 255, 0.08));
  border: none;
  transition: all 0.3s ease;
}

.leftItem:hover .leftItemIcon {
  background: linear-gradient(135deg, 
    rgba(255, 255, 255, 0.25), 
    rgba(255, 255, 255, 0.15));
  transform: scale(1.1);
}

.leftItemIcon i {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.95);
}

.leftText {
  display: grid;
  gap: 6px;
}

.leftItemTitle {
  font-weight: 800;
  line-height: 1.3;
  font-size: 16px;
  color: rgba(255, 255, 255, 0.95);
}

.leftItemSub {
  opacity: 0.8;
  font-weight: 500;
  font-size: 14px;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.85);
}

/* Bottom note */
.bottom-note {
  margin-top: 42px;
  padding: 16px 20px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: none;
  backdrop-filter: blur(8px);
  animation: pulse 2s infinite;
  transition: all 0.3s ease;
}

.bottom-note:hover {
  background: rgba(255, 255, 255, 0.12);
  transform: translateY(-2px);
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.9; }
}

.bottom-note i {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.95);
}

.bottom-note span {
  font-weight: 700;
  font-size: 14px;
  opacity: 0.9;
}

/* ========== RIGHT SECTION ========== */
.right {
  width:65%;
  padding: 0;
  display: flex;
  flex-direction: column;
  background: #f8f9ff;
}

.form-container {
  flex: 1;
  padding: 40px 50px;
  display: grid;
  align-content: start;
}

.back {
  display: flex;
  align-items: center;
  gap: 8px;
  border: none;
  background: none;
  padding: 4px 0;
  margin-bottom: 5px;
  color: #667eea;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.back:hover {
  color: #764ba2;
  transform: translateX(-4px);
}

.back i {
  font-size: 12px;
}

.header {
  text-align: center;
  margin-bottom: 20px;
}

.header-icon {
  width: 42px;
  height: 42px;
  margin: 0 auto 12px;
  border-radius: 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: grid;
  place-items: center;
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.header-icon i {
  font-size: 18px;
  color: white;
}

.title {
  font-weight: 600;
  margin: 0 0 8px;
  font-size: 20px;
  color: #333;
}

.subtitle {
  margin: 0;
  color: #666;
  font-size: 12px;
  font-weight: 500;
}

.err {
  background: rgba(239, 68, 68, .12);
  border: 1px solid rgba(239, 68, 68, .25);
  color: #b91c1c;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-weight: 700;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.err i {
  font-size: 16px;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-bottom: 30px;
  width:100%;
}

.field {
  margin-bottom: 0;
}

label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 13px;
  color: #374151;
  margin: 0 0 10px;
}

label i {
  font-size: 14px;
  color: #764ba2;
}

.input-with-icon {
  position: relative;
}

.icon-left {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 16px;
}

.input {
  width: 100%;
  height: 40px;
  border-radius: 12px;
  border: 1px solid #e1e5e9;
  padding: 0 16px 0 16px;
  font-weight: 600;
  outline: none;
  background: white;
  font-size: 14px;
  transition: all 0.3s ease;
}

.input:focus {
  border-color: #764ba2;
  box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
}

.input:disabled {
  background: #f8f9fa;
  color: #666;
}

/* Custom select */
.custom-select {
  position: relative;
}

.custom-select-btn {
  width: 100%;
  height: 40px;
  border-radius: 12px;
  border: 1px solid #e1e5e9;
  padding: 0 16px 0 16px;
  background: #fff;
  text-align: left;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s ease;
}

.custom-select-btn:hover {
  border-color: #764ba2;
}

.custom-select-btn .arrow {
  width: 6px;
  height: 6px;
  border-right: 2px solid #555;
  border-bottom: 2px solid #555;
  transform: rotate(45deg);
}

.custom-select-menu {
  position: absolute;
  top: 50px;
  left: 0;
  width: 100%;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  z-index: 100;
  border: 1px solid #e1e5e9;
}

.custom-option {
  padding: 12px 16px;
  font-size: 14px;
  cursor: pointer;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s ease;
}

.custom-option:hover {
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
}

.custom-option i {
  font-size: 14px;
}

/* DOB */
.dob-inline-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
}

.dob-input-wrapper {
  position: relative;
}

.dob-input {
  width: 100%;
  height: 44px;
  border-radius: 12px;
  border: 1px solid #e1e5e9;
  padding: 0 0 0 3px;
  font-size: 14px;
  font-weight: 600;
  background: #fff;
  outline: none;
  text-align: center;
  transition: all 0.3s ease;
}

.dob-input:focus {
  border-color: #764ba2;
  box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
}

/* Error text */
.error-text {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #dc2626;
  font-size: 12px;
  margin-top: 6px;
  margin-left: 4px;
  font-weight: 500;
}

.error-text i {
  font-size: 12px;
}

/* Button */
.btn {
  margin-top: 10px;
  height: 46px;
  border-radius: 12px;
  border: 0;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
  font-weight: 700;
  font-size: 14px;
  width: 100%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.3s ease;
}

.btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 30px rgba(51, 46, 120, 0.3);
}

.btn:disabled {
  opacity: .6;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}



/* ========== RESPONSIVE ========== */
@media (max-width: 991.98px) {
  .wrap {
    grid-template-columns: 1fr;
    min-height: auto;
  }
  
  .left {
    display: none;
  }
  
  .right {
    width: 100%;
    display: inline;
  }
}

@media screen and (max-width: 575px) {
  .overlay {
    padding: 20px;
  }
  
  .header-wrapper {
    gap: 10px;
  }
  
  .icon-wrapper {
    width: 40px;
    height: 40px;
  }
  
  .icon-wrapper i {
    font-size: 16px;
  }
  
  .leftTitle {
    font-size: 24px;
  }
  
  .leftItemIcon {
    width: 32px;
    height: 32px;
  }
  
  .leftItemSub {
    font-size: 12px;
  }
  
  .bottom-note {
    margin-top: 15px;
    padding: 12px 15px;
  }
  
  .bottom-note span {
    font-size: 12px;
  }
  
  .form-container {
    padding: 20px 15px;
  }
  
  .back {
    padding: 5px 0;
    font-size: 14px;
  }
  
  .header {
    margin-bottom: 20px;
  }
  
  .header-icon {
    width: 36px;
    height: 36px;
  }
  
  .header-icon i {
    font-size: 16px;
  }
  
  .title {
    font-size: 20px;
    margin-bottom: 5px;
  }
  
  .subtitle {
    font-size: 12px;
  }
  
  .err {
    padding: 10px 12px;
    font-size: 12px;
    margin-bottom: 15px;
  }
  
  .form-grid {
    gap: 15px;
    margin-bottom: 20px;
  }
  
  label {
    font-size: 13px;
  }
  
  label i {
    font-size: 13px;
  }
  
  .input, .custom-select-btn, .dob-input {
    height: 42px;
    padding: 0 15px;
    font-size: 13px;
  }
  
  .custom-select-btn .arrow {
    width: 5px;
    height: 5px;
  }
  
  .custom-select-menu {
    top: 46px;
  }
  
  .custom-option {
    padding: 12px 15px;
    font-size: 13px;
  }
  
  .dob-inline-grid {
    gap: 6px;
  }
  
  .error-text {
    font-size: 12px;
    margin-top: 5px;
  }
  
  .btn {
    height: 44px;
    font-size: 13px;
    margin-top: 15px;
  }
}
</style>