<!--Multivendor/Auth/LoginPopup.vue-->

<script setup>
import { computed, nextTick, ref, watch } from "vue"
import axios from "axios"


const props = defineProps({
    redirectTo: { type: String, default: "/" },
})

const clearError = () => {
    errorMsg.value = ""
}

const changePhoneNumber = async () => {
    clearError()
    showCountryDropdown.value = false

    // reset otp state
    form.value.otp = ""
    otpInputs.value = Array(OTP_LENGTH).fill("")

    form.value.step = "input"
    await nextTick()
}


const emit = defineEmits(["need-register", "success"])

const form = ref({
    country: "lk",
    phone: "",
    otp: "",
    step: "input", // input | otp
})

const errorMsg = ref("")
const loading = ref(false)

/* ---------------- PHONE INPUT ---------------- */
const countries = ref([
    { code: "lk", name: "Sri Lanka", dial: "+94", iso: "lk", max: 9, format: [2, 3, 4] },
    { code: "in", name: "India", dial: "+91", iso: "in", max: 10, format: [5, 5] },
])
const selectedCountry = ref(countries.value[0])
const phoneRaw = ref("")
const showCountryDropdown = ref(false)

const formatPhone = (value) => {
    let digits = String(value || "").replace(/\D/g, "")
    digits = digits.slice(0, selectedCountry.value.max)

    let out = ""
    let index = 0
    selectedCountry.value.format.forEach((len) => {
        if (digits.length > index) {
            out += digits.slice(index, index + len) + " "
            index += len
        }
    })

    phoneRaw.value = digits
    return out.trim()
}

const onPhoneInput = (e) => {
    clearError()
    e.target.value = formatPhone(e.target.value)
    form.value.phone = phoneRaw.value
    form.value.country = selectedCountry.value.code
}


const selectCountry = (c) => {
    clearError()
    selectedCountry.value = c
    showCountryDropdown.value = false
    phoneRaw.value = ""
    form.value.phone = ""
    form.value.otp = ""
    otpInputs.value = Array(OTP_LENGTH).fill("")
    form.value.step = "input"
}


const fullPhone = computed(() => {
    if (!form.value.phone) return ""
    return `${selectedCountry.value.dial}${form.value.phone}`
})

/* ---------------- OTP INPUTS ---------------- */
const OTP_LENGTH = 6
const otpInputs = ref(Array(OTP_LENGTH).fill(""))
const otpRefs = ref([])

watch(
    () => otpInputs.value.join(""),
    (v) => {
        form.value.otp = v
    }
)


const onOtpInput = (e, index) => {
    clearError()
    const val = e.target.value.replace(/\D/g, "")
    if (!val) {
        otpInputs.value[index] = ""
        return
    }
    otpInputs.value[index] = val[0]
    if (index < OTP_LENGTH - 1) otpRefs.value[index + 1]?.focus()
}

const onOtpKeydown = (e, index) => {
    if (e.key === "Backspace" && !otpInputs.value[index] && index > 0) {
        otpRefs.value[index - 1]?.focus()
    }
}

const onOtpPaste = (e) => {
    const pasted = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, OTP_LENGTH)
    pasted.split("").forEach((d, i) => (otpInputs.value[i] = d))
    nextTick(() => otpRefs.value[Math.min(pasted.length, OTP_LENGTH - 1)]?.focus())
}

/* ---------------- API ---------------- */
const sendOtp = async () => {
    errorMsg.value = ""
    loading.value = true
    try {
        await axios.post(
            route("auth.send-otp"),
            { phone: fullPhone.value, country: selectedCountry.value.code },
            { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } }
        )

        form.value.step = "otp"
        otpInputs.value = Array(OTP_LENGTH).fill("")
        await nextTick()
        otpRefs.value[0]?.focus()
    } catch (e) {
        errorMsg.value = e?.response?.data?.message || "Failed to send OTP."
    } finally {
        loading.value = false
    }
}

const verifyOtp = async () => {
    errorMsg.value = ""
    loading.value = true
    try {
        const res = await axios.post(
            route("auth.verify-otp"),
            {
                phone: fullPhone.value,
                otp: form.value.otp,
                redirect: props.redirectTo,
            },
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            }
        )


        if (res?.data?.status === "need_register") {
            emit("need-register", fullPhone.value)
            return
        }
       if (res?.data?.status === "logged_in") {
  emit("success")
  return
}

errorMsg.value = "Something went wrong. Please try again."

    } catch (e) {
        errorMsg.value =
            e?.response?.data?.message ||
            e?.response?.data?.errors?.otp?.[0] ||
            "Your OTP is wrong. Please try again."
    } finally {

        loading.value = false
    }
}

const canSubmit = computed(() => {
    if (form.value.step === "input") {
        return form.value.phone.length === selectedCountry.value.max && !loading.value
    }
    return form.value.otp.length === OTP_LENGTH && !loading.value
})
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
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <h2 class="leftTitle">Post an ad</h2>
                    </div>

                    <p class="leftSub">Login to post your ad and keep track of it in your account.</p>

                    <div class="leftList">
                        <div class="leftItem">
                            <div class="leftItemIcon">
                                <i class="fa-solid fa-bullhorn"></i>
                            </div>
                            <div class="leftText">
                                <div class="leftItemTitle">Start posting your own ads</div>
                                <div class="leftItemSub">Create listings quickly and reach more customers.</div>
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
                                <div class="leftItemSub">Edit, pause, and track performance in one place.</div>
                            </div>
                        </div>
                    </div>

                    <div class="bottom-note">
                        <i class="fa-solid fa-arrow-right"></i>
                        <span>Join thousands of successful sellers</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SECTION-->
        <div class="right">
            <!-- <div class="right-image">
                <div class="image-container">
                    <div class="placeholder-image">
                        <div class="image-content">
                            <img src="/assets/images/car_vector.png" alt="Login Illustration" class="custom-image" />
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- Form Section -->
            <div class="form-container">
                <h3 class="title">Sign In</h3>

                <div v-if="errorMsg" class="err">{{ errorMsg }}</div>

                <form @submit.prevent="form.step === 'input' ? sendOtp() : verifyOtp()">
                    <div v-if="form.step === 'input'">
                        <div class="phone-input-wrap">
                            <button type="button" class="country-btn"
                                @click="showCountryDropdown = !showCountryDropdown">
                                <span class="fi" :class="`fi-${selectedCountry.iso}`"></span>
                                <span class="dial">{{ selectedCountry.dial }}</span>
                                <i class="arrow"></i>
                            </button>
                            <input type="tel" class="phone-input" placeholder="Phone number" @input="onPhoneInput" />
                        </div>

                        <div v-if="showCountryDropdown" class="country-dropdown">
                            <div v-for="c in countries" :key="c.code" class="country-item" @click="selectCountry(c)">
                                <span class="fi" :class="`fi-${c.iso}`"></span>
                                <span class="name">{{ c.name }}</span>
                                <span class="dial">{{ c.dial }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="otp-block">
                        <div class="otp-row" @paste="onOtpPaste">
                            <input v-for="(_, i) in OTP_LENGTH" :key="i" ref="otpRefs" v-model="otpInputs[i]"
                                class="otp" inputmode="numeric" maxlength="1" @input="(e) => onOtpInput(e, i)"
                                @keydown="(e) => onOtpKeydown(e, i)" />
                        </div>

                        <button type="button" class="link" @click="changePhoneNumber">
                            Change phone number
                        </button>
                    </div>

                    <button class="btn" type="submit" :disabled="!canSubmit">
                        <span v-if="form.step === 'input'">SEND OTP</span>
                        <span v-else>VERIFY OTP</span>
                    </button>
                </form>

                <div class="divider">
                    <span>or</span>
                </div>
                <!-- Social Auth -->
                <div class="social-auth">
                    <a href="/auth/google/redirect" class="social-btn google">
                        <img src="/assets/images/google2.png" alt="Google" />
                        <span>Continue with Google</span>
                    </a>

                    <a href="/auth/facebook/redirect" class="social-btn facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                        <span>Continue with Facebook</span>
                    </a>

                    <button type="button" class="social-btn email" @click="form.step = 'input'">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Continue with Email</span>
                    </button>
                </div>


            </div>
        </div>
    </div>
</template>

<style scoped>
.wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 560px;
}

/* ========== LEFT SECTION ========== */
.left {
    background:
        radial-gradient(ellipse at 20% 30%, rgba(92, 45, 128, 0.4) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 70%, rgba(51, 46, 120, 0.4) 0%, transparent 50%),
        linear-gradient(135deg, #332e78, #5c2d80);
    position: relative;
    overflow: hidden;
}

.bg-pattern {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.08) 2px, transparent 2px),
        radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 60px 60px, 40px 40px;
    opacity: 0.3;
}

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

    0%,
    100% {
        transform: translateY(0) rotate(0deg);
    }

    50% {
        transform: translateY(-20px) rotate(180deg);
    }
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
    font-size: 32px;
    font-weight: 900;
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
    max-width: 320px;
}

.leftList {
    display: grid;
    gap: 20px;
    margin-top: 8px;
}

.leftItem {
    display: grid;
    grid-template-columns: 36px 1fr;
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
    margin-top: 32px;
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

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.9;
    }
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
    padding: 0;
    display: flex;
    flex-direction: column;
    background: #f8f9ff;
}

.image-container {
    width: 100%;
    max-width: 250px;
}

.image-content {
    text-align: center;
    color: white;
}

.custom-image {
    width: 200%;
    height: 300px;
    object-fit: cover;
    border-radius: 10px;
    padding-top: 40px;
}

.image-content i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.9;
}

.image-text {
    font-size: 18px;
    font-weight: 700;
    opacity: 0.95;
}

.form-container {
    flex: 1;
    padding: 40px 40px 50px;
    display: grid;
    align-content: start;
}

.title {
    font-weight: 700;
    margin: 0 0 24px;
    text-align: center;
    font-size: 24px;
    color: #333;
}

.err {
    background: rgba(239, 68, 68, .12);
    border: 1px solid rgba(239, 68, 68, .25);
    color: #b91c1c;
    padding: 10px 12px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-weight: 700;
    font-size: 13px;
}

.phone-input-wrap {
    display: flex;
    align-items: center;
    height: 44px;
    border-radius: 12px;
    border: 1px solid #e1e5e9;
    padding: 0 14px;
    background: #fff;
    position: relative;
    transition: all 0.3s ease;
}

.phone-input-wrap:focus-within {
    border-color: #764ba2;
    box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
}

.country-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    border: none;
    background: none;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    color: #333;
}

.country-btn .arrow {
    width: 5px;
    height: 5px;
    border-right: 2px solid #666;
    border-bottom: 2px solid #666;
    transform: rotate(45deg);
    margin-left: 4px;
}

.phone-input {
    border: none;
    outline: none;
    flex: 1;
    padding-left: 12px;
    font-weight: 700;
    font-size: 14px;
    color: #333;
}

.phone-input::placeholder {
    color: #888;
}

.country-dropdown {
    position: absolute;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
    border: 1px solid rgba(0, 0, 0, .08);
    overflow: hidden;
    z-index: 50;
    /* make sure it overlays */
}

.country-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    cursor: pointer;
    background: #fff;
    font-size: 14px;
}

.country-item:hover {
    background: #f8f9ff;
}

.country-item .name {
    flex: 1;
    font-weight: 700;
}

.fi {
    width: 20px;
    height: 15px;
    border-radius: 2px;
    background-size: cover;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, .08);
}



.otp-row {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.otp {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid #e1e5e9;
    text-align: center;
    font-size: 16px;
    font-weight: 700;
    outline: none;
    transition: all 0.3s ease;
}

.otp:focus {
    border-color: #764ba2;
    box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
}

.link {
    margin-top: 12px;
    border: none;
    background: none;
    color: #667eea;
    font-weight: 700;
    cursor: pointer;
    padding: 0;
    text-align: center;
    font-size: 13px;
    width: 100%;
    transition: color 0.3s ease;
}

.link:hover {
    color: #764ba2;
}

.btn {
    margin-top: 20px;
    height: 44px;
    border-radius: 12px;
    border: 0;
    background: linear-gradient(135deg, #332e78, #5c2d80);
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(51, 46, 120, 0.3);
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
    .placeholder-image {
        height: 160px;
    }
}

@media (max-width: 767px) {
    .right-image {
        padding: 20px 16px 15px;
        display: flex;
        justify-content: center;
    }

    .image-container {
        max-width: 200px;
    }

    .custom-image {
        width: 100%;
        height: auto;
        max-height: 150px;
        object-fit: contain;
        padding-top: 0;
    }

    .title {
        font-size: 22px;
        margin-bottom: 20px;
    }
    .form-container{
        padding: 12px 25px;
    }

    .phone-input-wrap {
        height: 48px;
        padding: 0 12px;
    }

    .country-btn {
        font-size: 13px;
    }

    .phone-input {
        font-size: 14px;
        padding-left: 8px;
    }

    .otp-row {
        gap: 6px;
    }

    .otp {
        width: 42px;
        height: 42px;
        font-size: 16px;
    }

    .country-item {
        padding: 14px 16px;
    }
    
    .social-auth {
        gap: 10px;
    }

    .social-btn {
        height: 48px;
        font-size: 14px;
    }

    .social-btn img,
    .social-btn i {
        width: 20px;
        height: 20px;
        font-size: 18px;
    }

    .err {
        font-size: 12px;
        padding: 10px;
        margin-bottom: 16px;
    }

    .btn {
        height: 48px;
        font-size: 15px;
        margin-top: 24px;
    }

    .link {
        font-size: 13px;
        padding: 12px 0;
    }

    .divider {
        margin: 24px 0;
    }

    .divider::before,
    .divider::after {
        width: 35%;
    }
}

.social-auth {
    display: grid;
    gap: 12px;
    margin-bottom: 20px;
}

.social-btn {
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.25s ease;
    text-decoration: none;
}

/* Google */
.social-btn.google {
    background: #fff;
    border: 1px solid #ddd;
    color: #444;
}

.social-btn.google:hover {
    background: #f7f7f7;
}

/* Facebook */
.social-btn.facebook {
    background: #3b5998;
    color: #fff;
    border: none;
}

.social-btn.facebook:hover {
    background: #334d84;
}

/* Email */
.social-btn.email {
    background: #1e8e6a;
    color: #fff;
    border: none;
}

.social-btn.email:hover {
    background: #18795b;
}


.google-icon {
    width: 20px;
    height: 20px;
    object-fit: contain;
}
/* Divider */
.divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
    font-size: 13px;
    font-weight: 600;
    color: #999;
}

.divider::before,
.divider::after {
    content: "";
    position: absolute;
    top: 50%;
    width: 40%;
    height: 1px;
    background: #eee;
}

.divider::before {
    left: 0;
}

.divider::after {
    right: 0;
}
.social-btn.google {
    background: #fff;
    border: 1px solid #ddd;
    color: #444;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.social-btn.google:hover {
    background: #f7f7f7;
}

.social-btn.google img {
    width: 20px;
    height: 20px;
    object-fit: contain;
    display: block;
}
</style>
