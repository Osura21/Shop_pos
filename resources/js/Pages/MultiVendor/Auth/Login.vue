
<!-- Multivendor/Auth/Login.vue-->
<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3"
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import axios from "axios"


const loginMode = ref("phone")
const form = useForm({
    phone: "",
    country: "lk",
    otp: "",
    step: "input", // input | otp
})


const OTP_LENGTH = 6
const otpInputs = ref(Array(OTP_LENGTH).fill(""))
const otpRefs = ref([])

// combine OTP digits into form.otp
watch(otpInputs, () => {
    form.otp = otpInputs.value.join("")
})

const onOtpInput = (e, index) => {
    const val = e.target.value.replace(/\D/g, "")

    if (!val) {
        otpInputs.value[index] = ""
        return
    }

    otpInputs.value[index] = val[0]

    // move to next input
    if (index < OTP_LENGTH - 1) {
        otpRefs.value[index + 1]?.focus()
    }
}

const onOtpKeydown = (e, index) => {
    if (e.key === "Backspace" && !otpInputs.value[index] && index > 0) {
        otpRefs.value[index - 1]?.focus()
    }
}

// paste full OTP
const onOtpPaste = (e) => {
    const pasted = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, OTP_LENGTH)
    pasted.split("").forEach((digit, i) => {
        otpInputs.value[i] = digit
    })

    nextTick(() => {
        otpRefs.value[Math.min(pasted.length, OTP_LENGTH - 1)]?.focus()
    })
}


const submitEmailLogin = () => {
    form.post(route("multivendor.make.login"))
}

const sendOtp = () => {
    form.phone = fullPhone.value
    form.post(route("auth.send-otp"), {
        onSuccess: () => {
            form.step = "otp"

            setTimeout(() => {
                document.querySelector('input[placeholder="Enter OTP"]')?.focus()
            }, 100)
        },
    })
}

const verifyOtp = () => {
    form.post(route("auth.verify-otp"), {
        data: {
            phone: fullPhone.value,
            otp: form.otp,
        },
    })
}





/**
 * Left slider data (public/multivendor/... paths)
 * NOTE: do NOT include "/public" in URLs.
 */
const slides = ref([
    {
        image: "/multivendor/assets/images/login/two-cars-are-parked-road-one-is-white.jpg",
        title: "Discover New Roads",
        text: "Find premium vehicles tailored to your journey.",
    },
    {
        image: "/multivendor/assets/images/login/powerful-headlights-particle-view-modern-luxury-cars-parked-indoors-daytime.jpg",
        title: "Luxury Meets Performance",
        text: "Drive smarter with trusted sellers and verified listings.",
    },
    {
        image: "/multivendor/assets/images/login/two-cars-are-parked-road-one-is-white.jpg",
        title: "Your Journey Starts Here",
        text: "Buy, sell, or explore — all in one platform.",
    },
])

const active = ref(0)
const intervalMs = 4500
let timer = null

const next = () => {
    active.value = (active.value + 1) % slides.value.length
}

const goTo = (i) => {
    active.value = i
    restartAuto()
}

watch(loginMode, () => {
    form.step = "input"
    form.otp = ""
})


const restartAuto = () => {
    if (timer) clearInterval(timer)
    timer = setInterval(next, intervalMs)
}

onMounted(() => restartAuto())
onBeforeUnmount(() => {
    if (timer) clearInterval(timer)
})

const activeSlide = computed(() => slides.value[active.value])


/* ---------------- PHONE INPUT ---------------- */
const countries = ref([
    {
        code: "lk",
        name: "Sri Lanka",
        dial: "+94",
        iso: "lk",
        max: 9,
        format: [2, 3, 4],
    },
    {
        code: "in",
        name: "India",
        dial: "+91",
        iso: "in",
        max: 10,
        format: [5, 5],
    },
])

const selectedCountry = ref(countries.value[0])
const phoneRaw = ref("")
const showCountryDropdown = ref(false)

const formatPhone = (value) => {
    let digits = value.replace(/\D/g, "")
    digits = digits.slice(0, selectedCountry.value.max)

    let formatted = ""
    let index = 0

    selectedCountry.value.format.forEach(len => {
        if (digits.length > index) {
            formatted += digits.slice(index, index + len) + " "
            index += len
        }
    })

    phoneRaw.value = digits
    return formatted.trim()
}

const onPhoneInput = (e) => {
    e.target.value = formatPhone(e.target.value)
    form.phone = phoneRaw.value
    form.country = selectedCountry.value.code
}

const selectCountry = (c) => {
    selectedCountry.value = c
    showCountryDropdown.value = false

    // RESET EVERYTHING
    phoneRaw.value = ""
    form.phone = ""
    form.step = "input"
    form.otp = ""

    // Clear visible input value
    const input = document.querySelector(".phone-input")
    if (input) input.value = ""
}

const fullPhone = computed(() => {
    if (!form.phone) return ""
    return `${selectedCountry.value.dial}${form.phone}`
})

const isSubmitEnabled = computed(() => {
    // STEP 1: Sending OTP
    if (form.step === 'input') {
        return (
            form.phone &&
            form.phone.length === selectedCountry.value.max &&
            !form.processing
        )
    }

    // STEP 2: Verifying OTP
    if (form.step === 'otp') {
        return (
            form.otp &&
            form.otp.length >= 4 && // or 6 if your OTP is 6 digits
            !form.processing
        )
    }

    return false
})


</script>

<template>
    <div>

        <Head title="Login" />

        <div class="login-shell">
            <div class="login-wrap">
                <div class="login-card">

                    <!-- LEFT: Full image slider with overlay -->
                    <div class="login-left" aria-label="Login marketing slider">
                        <!-- Background image layers (crossfade) -->
                        <div class="bg-stack">
                            <div v-for="(s, i) in slides" :key="i" class="bg-layer" :class="{ show: i === active }"
                                :style="{ backgroundImage: `url('${s.image}')` }" />
                            <div class="bg-overlay" />
                        </div>

                        <!-- Overlay content -->
                        <div class="left-content">
                            <h2 class="left-title">{{ activeSlide.title }}</h2>
                            <p class="left-text">{{ activeSlide.text }}</p>

                            <!-- Indicator bars -->
                            <div class="indicators" role="tablist" aria-label="Slide indicators">
                                <button v-for="(s, i) in slides" :key="`ind-${i}`" class="indicator"
                                    :class="{ active: i === active }" type="button" @click="goTo(i)"
                                    :aria-label="`Go to slide ${i + 1}`" />
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Login form -->
                    <div class="login-right">
                        <div class="right-inner">
                            <div class="brand">
                                <img src="/assets/images/cropped-Veyogo.png" class="brand-logo" alt="Autosale.lk" />
                                <h3 class="brand-title">
                                    Welcome to <span class="brand-accent">Autosale.lk</span>
                                </h3>
                                <p class="brand-subtitle">Login to access exclusive deals</p>
                            </div>

                            <form class="form" @submit.prevent="
                                form.step === 'input'
                                    ? sendOtp()
                                    : verifyOtp()
                                ">




                                <div v-if="loginMode === 'phone'">
                                    <div class="field" v-if="form.step === 'input'">
                                        <div class="signup-field phone-field">
                                            <div class="phone-input-wrap">
                                                <button type="button" class="country-btn"
                                                    @click="showCountryDropdown = !showCountryDropdown">
                                                    <span class="fi" :class="`fi-${selectedCountry.iso}`"></span>
                                                    <span class="dial">{{ selectedCountry.dial }}</span>
                                                    <i class="arrow"></i>
                                                </button>

                                                <input type="tel" class="signup-input phone-input"
                                                    placeholder="Phone number" @input="onPhoneInput" />
                                            </div>

                                            <div v-if="showCountryDropdown" class="country-dropdown">
                                                <div v-for="c in countries" :key="c.code" class="country-item"
                                                    @click="selectCountry(c)">
                                                    <span class="fi" :class="`fi-${c.iso}`"></span>
                                                    <span class="name">{{ c.name }}</span>
                                                    <span class="dial">{{ c.dial }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="field" v-if="form.step === 'otp'">
                                        <input v-model="form.otp" class="input" placeholder="Enter OTP" />
                                    </div>
                                </div>



                                <!-- <div class="actions">
                                    <Link href="#" class="forgot">Forgot Password?</Link>
                                </div> -->

                                <button class="custom-btn custom-btn-primary w-100" type="submit"
                                    :disabled="form.processing || !isSubmitEnabled">
                                    <span v-if="form.step === 'input'">SEND OTP</span>
                                    <span v-else>VERIFY OTP</span>
                                </button>

                            </form>

                            <!-- <div class="signup">
                                <span>Don’t have an account?</span>
                                <Link :href="route('multivendor.register')" class="signup-link">Sign up</Link>
                            </div> -->

                            <!-- <div class="divider">
                                <span></span><small>or</small><span></span>
                            </div>

                            <div class="social">
                                <button class="social-btn" type="button">Google</button>
                                <button class="social-btn" type="button">Facebook</button>
                                <button class="social-btn" type="button">Apple</button>
                            </div> -->
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</template>

<style scoped>
/* Page shell */
.login-shell {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #f6f7fb;
}

.login-wrap {
    width: min(1120px, 100%);
}

/* Card */
.login-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: 22px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 18px 50px rgba(20, 20, 40, 0.12);
    min-height: 620px;
}

/* LEFT */
.login-left {
    position: relative;
    min-height: 620px;
    background: #111;
}

.bg-stack {
    position: absolute;
    inset: 0;
}

.bg-layer {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transform: scale(1.03);
    transition: opacity 700ms ease, transform 900ms ease;
}

.bg-layer.show {
    opacity: 1;
    transform: scale(1);
}

.bg-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.25));
}

/* Overlay text */
.left-content {
    position: absolute;
    left: 56px;
    right: 56px;
    bottom: 56px;
    color: #fff;
    z-index: 2;
}

.left-title {
    font-size: 34px;
    font-weight: 800;
    line-height: 1.15;
    margin: 0 0 10px;
    letter-spacing: -0.3px;
}

.left-text {
    margin: 0;
    max-width: 420px;
    opacity: 0.92;
    font-size: 15px;
    line-height: 1.55;
}

/* Indicator bars (like your reference) */
.indicators {
    display: flex;
    gap: 12px;
    margin-top: 18px;
}

.indicator {
    width: 56px;
    height: 4px;
    border-radius: 999px;
    border: 0;
    background: rgba(255, 255, 255, 0.35);
    cursor: pointer;
    transition: background 200ms ease, transform 200ms ease;
}

.indicator.active {
    background: rgba(255, 255, 255, 0.95);
    transform: scaleX(1.02);
}

/* RIGHT */
.login-right {
    display: grid;
    align-items: center;
    padding: 44px;
}

.right-inner {
    width: 100%;
}

.brand {
    text-align: center;
    margin-bottom: 22px;
}

.brand-logo {
    width: 140px;
    height: auto;
}

.brand-title {
    margin: 14px 0 6px;
    font-weight: 800;
}

.brand-accent {
    color: #5c2d80;
}

.brand-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 13px;
}

/* Form */
.form {
    margin-top: 18px;
}

.field {
    margin-bottom: 12px;
}

.input {
    width: 100%;
    height: 52px;
    border-radius: 999px;
    border: 1px solid #e6e7ee;
    padding: 0 18px;
    outline: none;
    font-size: 14px;
    transition: border 150ms ease, box-shadow 150ms ease;
    background: #fff;
}

.input:focus {
    border-color: rgba(92, 45, 128, 0.6);
    box-shadow: 0 0 0 4px rgba(92, 45, 128, 0.12);
}

.actions {
    display: flex;
    justify-content: flex-end;
    margin: 6px 0 14px;
}

.forgot {
    font-size: 13px;
    color: #2563eb;
    text-decoration: none;
}



/* Signup + divider + social */
.signup {
    margin-top: 16px;
    text-align: center;
    font-size: 13px;
    color: #6b7280;
}

.signup-link {
    margin-left: 6px;
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.divider {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 12px;
    margin: 18px 0 14px;
    color: #9aa0aa;
}

.divider span {
    height: 1px;
    background: #eceef4;
}

.social {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}

.social-btn {
    height: 44px;
    border-radius: 999px;
    border: 1px solid #e6e7ee;
    background: #fff;
    font-weight: 600;
    cursor: pointer;
}

/* Responsive: hide slider on small, make single column */
@media (max-width: 991.98px) {
    .login-card {
        grid-template-columns: 1fr;
        min-height: auto;
    }

    .login-left {
        display: none;
    }

    .login-right {
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
</style>
