<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3"
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from "vue"

const props = defineProps({
    phone: String,
})

const open = ref(false)

const form = useForm({
    phone: props.phone,
    name: "",
    email: "",
    gender: "",
    location: "",
    dob: "",
    c_job: "",
})

const submit = () => {
    if (!form.name) {
        return
    }

    form.post(route("otp.register.store"), {
        preserveScroll: true,
    })
}


/* ---------------- SLIDER ---------------- */
const slides = ref([
    {
        image: "/multivendor/assets/images/login/two-cars-are-parked-road-one-is-white.jpg",
        title: "Start Your Journey",
        text: "Create an account and access premium vehicles.",
    },
    {
        image: "/multivendor/assets/images/login/powerful-headlights-particle-view-modern-luxury-cars-parked-indoors-daytime.jpg",
        title: "Buy & Sell With Confidence",
        text: "Verified sellers and seamless transactions.",
    },
])

const active = ref(0)
const intervalMs = 4500
let timer = null

const next = () => {
    active.value = (active.value + 1) % slides.value.length
}

const restartAuto = () => {
    if (timer) clearInterval(timer)
    timer = setInterval(next, intervalMs)
}

onMounted(() => restartAuto())
onBeforeUnmount(() => timer && clearInterval(timer))

const activeSlide = computed(() => slides.value[active.value])

const genders = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
]

const genderLabel = (value) => {
    return genders.find(g => g.value === value)?.label || 'Select gender'
}

const dobDay = ref('')
const dobMonth = ref('')
const dobYear = ref('')


const currentYear = new Date().getFullYear()
const minYear = 1900
const maxYear = currentYear - 18

const numbersOnly = (val) => val.replace(/\D/g, '')

/* DAY: 1–31 */
const onDayInput = (e) => {
    let val = numbersOnly(e.target.value)

    if (!val) {
        dobDay.value = ''
        return
    }

    let num = parseInt(val, 10)

    if (num < 1) num = 1
    if (num > 31) num = 31

    dobDay.value = String(num)
    updateDob()
}

/* MONTH: 1–12 */
const onMonthInput = (e) => {
    let val = numbersOnly(e.target.value)

    if (!val) {
        dobMonth.value = ''
        return
    }

    let num = parseInt(val, 10)

    if (num < 1) num = 1
    if (num > 12) num = 12

    dobMonth.value = String(num)
    updateDob()
}

const onYearInput = (e) => {
    let val = numbersOnly(e.target.value).slice(0, 4)

    // allow clear
    if (!val) {
        dobYear.value = ''
        form.dob = ''
        return
    }

    // only clamp when 4 digits
    if (val.length === 4) {
        let num = parseInt(val, 10)

        if (num < minYear) num = minYear
        if (num > maxYear) num = maxYear

        val = String(num)
    }

    dobYear.value = val
}

const onYearBlur = () => {
    if (!dobYear.value) return

    // if user leaves with incomplete year like 1 / 13 / 134
    if (dobYear.value.length < 4) {
        dobYear.value = String(minYear)
    }

    updateDob()
}



const updateDob = () => {
    if (!dobDay.value || !dobMonth.value || !dobYear.value) return

    const maxDays = new Date(
        dobYear.value,
        dobMonth.value,
        0
    ).getDate()

    if (+dobDay.value > maxDays) {
        dobDay.value = String(maxDays)
    }

    form.dob = `${dobYear.value}-${dobMonth.value.padStart(2, '0')}-${dobDay.value.padStart(2, '0')}`
}


</script>


<template>
    <div>

        <Head title="Sign Up" />

        <div class="signup-shell">
            <div class="signup-wrap">
                <div class="signup-card">

                    <!-- LEFT : SIGNUP FORM -->
                    <div class="signup-right">
                        <div class="signup-inner">

                            <div class="signup-brand">
                                <img src="/assets/images/cropped-Veyogo.png" class="signup-brand-logo" alt="Autosale.lk" />
                                <h3 class="signup-brand-title">
                                    Join <span class="signup-brand-accent">Autosale.lk</span>
                                </h3>
                                <p class="signup-brand-subtitle">
                                    Create your account to continue
                                </p>
                            </div>

                            <form class="signup-form" @submit.prevent="submit">
                                <div class="signup-field">
                                    <label class="dob-label">Mobile Number</label>
                                    <input v-model="form.phone" type="text" class="signup-input"
                                        placeholder="Mobile Number" disabled />
                                </div>
                                <div class="signup-field">
                                    <label class="dob-label">Full Name</label>
                                    <input v-model="form.name" type="text" class="signup-input"
                                        placeholder="Full Name" />
                                    <div v-if="form.errors.name" class="error-text">
                                        {{ form.errors.name }}
                                    </div>
                                </div>
                                <div class="signup-field">
                                    <label class="dob-label">Email</label>
                                    <input v-model="form.email" type="email" class="signup-input"
                                        placeholder="Email Address" />
                                    <div v-if="form.errors.email" class="error-text">
                                        {{ form.errors.email }}
                                    </div>
                                </div>
                                <div class="signup-field">
                                    <label class="dob-label">Gender</label>
                                    <div class="custom-select">
                                        <button type="button" class="custom-select-btn" @click="open = !open">
                                            {{ genderLabel(form.gender) }}

                                            <span class="arrow"></span>
                                        </button>

                                        <div v-if="open" class="custom-select-menu">
                                            <div v-for="g in genders" :key="g.value" class="custom-option"
                                                @click="form.gender = g.value; open = false">
                                                {{ g.label }}
                                            </div>

                                        </div>
                                    </div>

                                </div>
                                <div class="signup-field">
                                    <label class="dob-label">Date of Birth</label>

                                    <div class="dob-inline-grid">
                                        <!-- DAY -->
                                        <input v-model="dobDay" type="text" inputmode="numeric" maxlength="2"
                                            placeholder="DD" class="dob-input" @input="onDayInput" />

                                        <!-- MONTH -->
                                        <input v-model="dobMonth" type="text" inputmode="numeric" maxlength="2"
                                            placeholder="MM" class="dob-input" @input="onMonthInput" />

                                        <!-- YEAR -->
                                        <input v-model="dobYear" type="text" inputmode="numeric" maxlength="4"
                                            placeholder="YYYY" class="dob-input" @input="onYearInput"
                                            @blur="onYearBlur" />

                                    </div>
                                </div>







                                <button class="custom-btn custom-btn-primary w-100" type="submit"
                                    :disabled="form.processing || !form.name">
                                    CREATE ACCOUNT
                                </button>
                            </form>

                            <!-- <div class="signup-footer">
                                <span>Already have an account?</span>
                                <Link href="/login" class="signup-footer-link">Login</Link>
                            </div> -->

                        </div>
                    </div>

                    <!-- RIGHT : IMAGE SLIDER -->
                    <div class="signup-left">
                        <div class="signup-bg-stack">
                            <div v-for="(s, i) in slides" :key="i" class="signup-bg-layer"
                                :class="{ show: i === active }" :style="{ backgroundImage: `url('${s.image}')` }" />
                            <div class="signup-bg-overlay" />
                        </div>

                        <div class="signup-left-content">
                            <h2 class="signup-left-title">{{ activeSlide.title }}</h2>
                            <p class="signup-left-text">{{ activeSlide.text }}</p>

                            <div class="signup-indicators">
                                <button v-for="(s, i) in slides" :key="i" class="signup-indicator"
                                    :class="{ active: i === active }" />
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</template>

<style scoped>
/* Page shell */
.signup-shell {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #f6f7fb;
}

.signup-wrap {
    width: min(1120px, 100%);
}

/* Card */
.signup-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: 22px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 18px 50px rgba(20, 20, 40, 0.12);
    min-height: 620px;
}

/* RIGHT (FORM) */
.signup-right {
    display: grid;
    align-items: center;
    padding: 44px;
    order: 1;
}

.signup-inner {
    width: 100%;
}

.signup-brand {
    text-align: center;
    margin-bottom: 22px;
}

.signup-brand-logo {
    width: 140px;
}

.signup-brand-title {
    margin: 14px 0 6px;
    font-weight: 800;
}

.signup-brand-accent {
    color: #5c2d80;
}

.signup-brand-subtitle {
    font-size: 13px;
    color: #6b7280;
}

/* FORM */
.signup-field {
    margin-bottom: 12px;
}

.signup-input {
    width: 100%;
    height: 52px;
    border-radius: 999px;
    border: 1px solid #e6e7ee;
    padding: 0 18px;
    font-size: 14px;
}

.signup-btn-primary {
    width: 100%;
    height: 52px;
    border-radius: 999px;
    background: linear-gradient(135deg, #332E78, #5C2D80);
    color: #fff;
    font-weight: 700;
    border: 0;
}

/* LEFT (SLIDER) */
.signup-left {
    position: relative;
    min-height: 620px;
    background: #111;
    order: 2;
}

.signup-bg-stack {
    position: absolute;
    inset: 0;
}

.signup-bg-layer {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transform: scale(1.03);
    transition: opacity 700ms ease, transform 900ms ease;
}

.signup-bg-layer.show {
    opacity: 1;
    transform: scale(1);
}

.signup-bg-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, .75), rgba(0, 0, 0, .25));
}

.signup-left-content {
    position: absolute;
    left: 56px;
    right: 56px;
    bottom: 56px;
    color: #fff;
}

.signup-left-title {
    font-size: 34px;
    font-weight: 800;
}

.signup-left-text {
    max-width: 420px;
    font-size: 15px;
    opacity: .92;
}

/* INDICATORS */
.signup-indicators {
    display: flex;
    gap: 12px;
    margin-top: 18px;
}

.signup-indicator {
    width: 56px;
    height: 4px;
    border-radius: 999px;
    border: 0;
    background: rgba(255, 255, 255, .35);
}

.signup-indicator.active {
    background: rgba(255, 255, 255, .95);
}

/* FOOTER */
.signup-footer {
    margin-top: 16px;
    text-align: center;
    font-size: 13px;
    color: #6b7280;
}

.signup-footer-link {
    margin-left: 6px;
    font-weight: 700;
    color: #2563eb;
}

/* RESPONSIVE */
@media (max-width: 991.98px) {
    .signup-card {
        grid-template-columns: 1fr;
    }

    .signup-left {
        display: none;
    }

    .signup-right {
        padding: 26px;
    }
}


/* PHONE */
.phone-field {
    position: relative;
}

.phone-input-wrap {
    display: flex;
    align-items: center;
    height: 52px;
    border-radius: 999px;
    border: 1px solid #e6e7ee;
    padding: 0 14px;
    background: #fff;
}

.country-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
}

.country-btn .flag {
    font-size: 18px;
}

.country-btn .arrow {
    width: 6px;
    height: 6px;
    border-right: 2px solid #555;
    border-bottom: 2px solid #555;
    transform: rotate(45deg);
    margin-left: 4px;
}

.phone-input {
    border: none;
    outline: none;
    flex: 1;
    padding-left: 12px;
}

/* DROPDOWN */
.country-dropdown {
    position: absolute;
    top: 60px;
    left: 0;
    width: 100%;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, .15);
    z-index: 50;
    overflow: hidden;
}

.country-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
}

.country-item:hover {
    background: #f3f4f6;
}

.country-item .flag {
    font-size: 18px;
}

.country-item .name {
    flex: 1;
    font-size: 14px;
}

.fi {
    width: 22px;
    height: 16px;
    border-radius: 2px;
    background-size: cover;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, .08);
}

/* PASSWORD EYE */
.password-field {
    position: relative;
}

.password-eye {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: none;
    cursor: pointer;
    font-size: 16px;
    opacity: 0.7;
}

.password-eye:hover {
    opacity: 1;
}

.custom-select {
    position: relative;
}

.custom-select-btn {
    width: 100%;
    height: 52px;
    border-radius: 999px;
    border: 1px solid #e6e7ee;
    padding: 0 18px;
    background: #fff;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.custom-select-menu {
    position: absolute;
    top: 58px;
    left: 0;
    width: 100%;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, .15);
    overflow: hidden;
    z-index: 50;
}

.custom-option {
    padding: 14px 18px;
    font-size: 14px;
    cursor: pointer;
}

.custom-option:hover {
    background: linear-gradient(135deg, #332E78, #5C2D80);
    color: #fff;
}


.dob-label {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 6px;
    margin-left: 12px;
}

.dob-inline-grid {
    display: grid;
    grid-template-columns: 1fr 1.4fr 1fr;
    gap: 10px;
}

.dob-input {
    height: 52px;
    border-radius: 999px;
    border: 1px solid #e6e7ee;
    padding: 0 16px;
    font-size: 14px;
    background: #fff;
}

.dob-input:focus {
    outline: none;
    border-color: #5c2d80;
}
.error-text {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    margin-left: 12px;
}

</style>
