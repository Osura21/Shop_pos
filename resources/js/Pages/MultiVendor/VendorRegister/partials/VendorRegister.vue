<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { Link, useForm } from "@inertiajs/vue3"
import axios from "axios"

const props = defineProps({
    authUser: {
        type: Object,
        default: null,
    },
    existingRequest: {
        type: Object,
        default: null,
    },
    submittedRequest: {
        type: Object,
        default: null,
    },
    foodTypes: {
        type: Array,
        default: () => [],
    },
    restaurantPageImageDefaults: {
        type: Object,
        default: () => ({}),
    },
})

const requestRecord = computed(() => props.submittedRequest || props.existingRequest || null)

const fallbackFoodTypeOptions = [
    "Pizza",
    "Pasta",
    "Burgers",
    "Short Eats",
    "Desserts",
    "Cafe",
    "Fried Chicken",
    "Rice & Curry",
]

const foodTypeOptions = computed(() => {
    if (props.foodTypes?.length) {
        return props.foodTypes.map((type) => type.name || type.slug).filter(Boolean)
    }

    return fallbackFoodTypeOptions
})

const serviceOptions = [
    "Delivery",
    "Pickup",
    "Scheduled Orders",
    "Table Booking",
]

const restaurantImageSlots = [
    {
        key: "hero_main",
        group: "hero",
        title: "Main Hero Image",
        description: "Large left hero image",
        defaultSrc: "/multivendor/product.webp",
    },
    {
        key: "hero_deal",
        group: "hero",
        title: "Top Hero Card Image",
        description: "Burger combo card image",
        defaultSrc: "/multivendor/stock.webp",
    },
    {
        key: "hero_pizza",
        group: "hero",
        title: "Bottom Hero Card Image",
        description: "Family pizza night image",
        defaultSrc: "/multivendor/post-ad2.webp",
    },
    {
        key: "middle_banner_top",
        group: "middle",
        title: "Top Left Banner",
        description: "Yellow offer banner",
        defaultSrc: "/multivendor/contact.webp",
    },
    {
        key: "middle_banner_bottom",
        group: "middle",
        title: "Bottom Left Banner",
        description: "Pizza offer banner",
        defaultSrc: "/multivendor/aboutbanner.webp",
    },
    {
        key: "middle_banner_large",
        group: "middle",
        title: "Large Right Banner",
        description: "Loaded burger offer banner",
        defaultSrc: "/multivendor/business-auth-image.avif",
    },
]

const restaurantImageDefaults = computed(() => Object.fromEntries(
    restaurantImageSlots.map((slot) => [
        slot.key,
        props.restaurantPageImageDefaults?.[slot.key] || slot.defaultSrc,
    ])
))

const form = useForm({
    name: "",
    admin_name: "",
    email: "",
    phone: "",
    legal_business_name: "",
    store_display_name: "",
    business_address: "",
    business_email: "",
    owner_name: "",
    business_registration_number: "",
    city: "",
    country: "Sri Lanka",
    country_code: "LK",
    search_location: "",
    google_place_id: "",
    postal_code: "",
    address_line_1: "",
    address_line_2: "",
    state_province: "",
    food_types: [],
    opening_from: "",
    opening_to: "",
    service_options: [],
    terms_accepted: false,
    business_logo: null,
    business_photos: [],
    restaurant_page_images: {},
    restaurant_page_image_defaults: restaurantImageDefaults.value,
    business_license: null,
})

const currentStep = ref(1)
const phoneRaw = ref("")
const verifiedPhone = ref("")
const otpSent = ref(false)
const phoneVerified = ref(false)
const loadingSend = ref(false)
const loadingVerify = ref(false)
const apiError = ref("")
const apiSuccess = ref("")
const logoName = ref("")
const photoNames = ref([])
const licenseName = ref("")
const restaurantImageNames = ref({})
const restaurantImagePreviews = ref({ ...restaurantImageDefaults.value })
const previewSection = ref(null)
const addressSuggestions = ref([])
const addressLoading = ref(false)
const addressError = ref("")
let addressSearchTimer = null
let placesService = null

const OTP_LENGTH = 6
const otpInputs = ref(Array(OTP_LENGTH).fill(""))
const otpRefs = ref([])

const fullPhone = computed(() => {
    if (!phoneRaw.value) return ""
    return `+94${phoneRaw.value}`
})

const otpValue = computed(() => otpInputs.value.join(""))

const canSendOtp = computed(() => {
    return phoneRaw.value.length === 9 && phoneRaw.value.startsWith("7") && !loadingSend.value
})

const canVerifyOtp = computed(() => {
    return otpValue.value.length === OTP_LENGTH && otpSent.value && !loadingVerify.value
})

const canSubmit = computed(() => {
    return (
        !form.processing &&
        phoneVerified.value &&
        verifiedPhone.value === fullPhone.value &&
        !!form.legal_business_name &&
        !!form.store_display_name &&
        !!form.business_address &&
        !!form.business_email &&
        !!form.owner_name &&
        !!form.city &&
        form.food_types.length > 0 &&
        !!form.opening_from &&
        !!form.opening_to &&
        form.service_options.length > 0 &&
        form.terms_accepted
    )
})

const canContinueToImages = computed(() => canSubmit.value)

const stepTitle = computed(() => {
    if (currentStep.value === 2) return "Your partner dashboard is ready"
    if (currentStep.value === 3) return "Customize your restaurant page"
    return "Join the food delivery network"
})

const stepText = computed(() => {
    if (currentStep.value === 2) return "After registration, you can manage orders, menus, payments, and customer requests from one place."
    if (currentStep.value === 3) return "Hero images and promotional banners will be shown inside your restaurant page after you save them."
    return "Verify your phone number and register your food shop to start receiving orders."
})

const clearApiMessages = () => {
    apiError.value = ""
    apiSuccess.value = ""
}

const resetOtpState = () => {
    otpSent.value = false
    phoneVerified.value = false
    verifiedPhone.value = ""
    otpInputs.value = Array(OTP_LENGTH).fill("")
}

const onPhoneInput = (event) => {
    clearApiMessages()
    form.clearErrors("phone")

    let digits = String(event.target.value || "").replace(/\D/g, "")

    if (digits.startsWith("0")) {
        digits = digits.slice(1)
    }

    phoneRaw.value = digits.slice(0, 9)
}

watch(fullPhone, (newValue) => {
    if (verifiedPhone.value && verifiedPhone.value !== newValue) {
        resetOtpState()
    }
})

const onOtpInput = (event, index) => {
    clearApiMessages()

    const value = String(event.target.value || "").replace(/\D/g, "")

    if (!value) {
        otpInputs.value[index] = ""
        return
    }

    otpInputs.value[index] = value[0]

    if (index < OTP_LENGTH - 1) {
        otpRefs.value[index + 1]?.focus()
    }
}

const onOtpKeydown = (event, index) => {
    if (event.key === "Backspace" && !otpInputs.value[index] && index > 0) {
        otpRefs.value[index - 1]?.focus()
    }
}

const onOtpPaste = (event) => {
    const pasted = event.clipboardData.getData("text").replace(/\D/g, "").slice(0, OTP_LENGTH)

    pasted.split("").forEach((digit, index) => {
        otpInputs.value[index] = digit
    })

    nextTick(() => {
        otpRefs.value[Math.min(pasted.length, OTP_LENGTH - 1)]?.focus()
    })
}

const sendOtp = async () => {
    clearApiMessages()
    form.clearErrors("phone")

    if (!canSendOtp.value) return

    loadingSend.value = true

    try {
        await axios.post(
            route("seller.register.send-otp"),
            { phone: fullPhone.value },
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            }
        )

        otpSent.value = true
        otpInputs.value = Array(OTP_LENGTH).fill("")
        apiSuccess.value = ""

        await nextTick()
        otpRefs.value[0]?.focus()
    } catch (error) {
        apiError.value = error?.response?.data?.message || "Failed to send OTP. Please try again."
    } finally {
        loadingSend.value = false
    }
}

const verifyOtp = async () => {
    clearApiMessages()
    form.clearErrors("phone")

    if (!canVerifyOtp.value) return

    loadingVerify.value = true

    try {
        await axios.post(
            route("seller.register.verify-otp"),
            {
                phone: fullPhone.value,
                otp: otpValue.value,
            },
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            }
        )

        phoneVerified.value = true
        verifiedPhone.value = fullPhone.value
        form.phone = fullPhone.value
        currentStep.value = 2
        apiSuccess.value = ""
    } catch (error) {
        apiError.value = error?.response?.data?.message || "OTP verification failed. Please try again."
    } finally {
        loadingVerify.value = false
    }
}

const toggleArrayValue = (field, value) => {
    const values = form[field]
    form[field] = values.includes(value)
        ? values.filter((item) => item !== value)
        : [...values, value]
}

const countryCodeForAddress = computed(() => {
    if (form.country_code) return form.country_code
    if (form.country === "Sri Lanka") return "LK"
    if (form.country === "Australia") return "AU"
    return ""
})

const clearAddressSuggestions = () => {
    addressSuggestions.value = []
    addressError.value = ""
}

const loadGoogleMapsApi = async () => {
    if (window.google?.maps?.places) {
        addressError.value = ""
        return window.google
    }

    const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

    if (!apiKey) {
        addressError.value = "Missing VITE_GOOGLE_MAPS_API_KEY in .env"
        return null
    }

    try {
        if (!window.__vendorGoogleMapsPromise) {
            window.__vendorGoogleMapsPromise = new Promise((resolve, reject) => {
                const existingScript = document.querySelector('script[data-google-maps="vendor-form"]')
                if (existingScript) {
                    existingScript.addEventListener("load", () => resolve(window.google))
                    existingScript.addEventListener("error", () => reject(new Error("Failed to load Google Maps script")))
                    return
                }

                const script = document.createElement("script")
                script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`
                script.async = true
                script.defer = true
                script.dataset.googleMaps = "vendor-form"
                script.onload = () => resolve(window.google)
                script.onerror = () => reject(new Error("Failed to load Google Maps script"))
                document.head.appendChild(script)
            })
        }

        await window.__vendorGoogleMapsPromise
        return window.google
    } catch {
        addressError.value = "Google Maps could not be loaded. Check API key, billing, and Places API access."
        return null
    }
}

const getAddressComponent = (components = [], type, key = "long_name") => {
    const found = components.find((component) => component.types?.includes(type))
    return found ? found[key] || "" : ""
}

const googleLocationPayload = (place = {}) => {
    const components = place.address_components || []
    const streetNumber = getAddressComponent(components, "street_number")
    const routeName = getAddressComponent(components, "route")
    const addressLine1 = [streetNumber, routeName].filter(Boolean).join(" ").trim()
    const city =
        getAddressComponent(components, "locality") ||
        getAddressComponent(components, "postal_town") ||
        getAddressComponent(components, "administrative_area_level_2") ||
        getAddressComponent(components, "sublocality")

    return {
        google_place_id: place.place_id || "",
        search_location: place.formatted_address || place.name || "",
        business_address: place.formatted_address || place.name || "",
        address_line_1: addressLine1 || (place.formatted_address || "").split(",")[0]?.trim() || place.name || "",
        address_line_2: getAddressComponent(components, "sublocality") || getAddressComponent(components, "neighborhood"),
        city,
        state_province: getAddressComponent(components, "administrative_area_level_1"),
        postal_code: getAddressComponent(components, "postal_code"),
        country: getAddressComponent(components, "country"),
        country_code: getAddressComponent(components, "country", "short_name")?.toUpperCase() || "",
    }
}

const searchBusinessAddress = () => {
    window.clearTimeout(addressSearchTimer)
    addressError.value = ""

    const query = String(form.business_address || "").trim()

    if (query.length < 3) {
        addressSuggestions.value = []
        addressLoading.value = false
        return
    }

    addressSearchTimer = window.setTimeout(async () => {
        addressLoading.value = true

    try {
            const googleObject = await loadGoogleMapsApi()
            if (!googleObject) {
                const { data } = await axios.get(route("seller.register.locations"), {
                    params: {
                        query,
                        country_code: countryCodeForAddress.value,
                    },
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })

                addressSuggestions.value = data?.predictions || []
                return
            }

            const service = new google.maps.places.AutocompleteService()
            const predictions = await new Promise((resolve) => {
                service.getPlacePredictions(
                    {
                        input: query,
                        types: ["geocode"],
                        componentRestrictions: countryCodeForAddress.value
                            ? { country: countryCodeForAddress.value.toLowerCase() }
                            : undefined,
                    },
                    (items, status) => {
                        if (status !== google.maps.places.PlacesServiceStatus.OK || !items) {
                            resolve([])
                            return
                        }

                        resolve(items)
                    }
                )
            })

            addressSuggestions.value = predictions.map((item) => ({
                place_id: item.place_id,
                description: item.description,
                main_text: item.structured_formatting?.main_text || item.description,
                secondary_text: item.structured_formatting?.secondary_text || "",
            }))
        } catch {
            addressSuggestions.value = []
            addressError.value = "Address suggestions are unavailable right now."
        } finally {
            addressLoading.value = false
        }
    }, 260)
}

const selectBusinessAddress = async (suggestion) => {
    if (!suggestion?.place_id) return

    addressLoading.value = true
    addressError.value = ""

        try {
        const googleObject = await loadGoogleMapsApi()
        if (!googleObject) {
            const { data } = await axios.get(route("seller.register.location-details"), {
                params: { place_id: suggestion.place_id },
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            })

            const location = data?.location || {}

            form.google_place_id = location.google_place_id || suggestion.place_id
            form.search_location = location.search_location || suggestion.description
            form.business_address = location.business_address || location.search_location || suggestion.description
            form.address_line_1 = location.address_line_1 || form.address_line_1
            form.address_line_2 = location.address_line_2 || form.address_line_2
            form.city = location.city || form.city
            form.state_province = location.state_province || form.state_province
            form.postal_code = location.postal_code || form.postal_code
            form.country = location.country || form.country
            form.country_code = location.country_code || form.country_code
            addressSuggestions.value = []
            return
        }

        if (!placesService) {
            placesService = new google.maps.places.PlacesService(document.createElement("div"))
        }

        const place = await new Promise((resolve, reject) => {
            placesService.getDetails(
                {
                    placeId: suggestion.place_id,
                    fields: ["place_id", "name", "formatted_address", "address_components"],
                },
                (result, status) => {
                    if (status !== google.maps.places.PlacesServiceStatus.OK || !result) {
                        reject(new Error("Place details unavailable"))
                        return
                    }

                    resolve(result)
                }
            )
        })

        const location = googleLocationPayload(place)

        form.google_place_id = location.google_place_id || suggestion.place_id
        form.search_location = location.search_location || suggestion.description
        form.business_address = location.business_address || location.search_location || suggestion.description
        form.address_line_1 = location.address_line_1 || form.address_line_1
        form.address_line_2 = location.address_line_2 || form.address_line_2
        form.city = location.city || form.city
        form.state_province = location.state_province || form.state_province
        form.postal_code = location.postal_code || form.postal_code
        form.country = location.country || form.country
        form.country_code = location.country_code || form.country_code
        addressSuggestions.value = []
    } catch {
        addressError.value = "Could not load that address. Please type it manually."
    } finally {
        addressLoading.value = false
    }
}

onMounted(() => {
    loadGoogleMapsApi()
})

const onLogoChange = (event) => {
    const file = event.target.files?.[0] || null
    form.business_logo = file
    logoName.value = file?.name || ""
}

const onPhotosChange = (event) => {
    const files = Array.from(event.target.files || [])
    form.business_photos = files
    photoNames.value = files.map((file) => file.name)
}

const onLicenseChange = (event) => {
    const file = event.target.files?.[0] || null
    form.business_license = file
    licenseName.value = file?.name || ""
}

const restaurantImageSrc = (key) => {
    return restaurantImagePreviews.value[key] || restaurantImageDefaults.value[key]
}

const onRestaurantImageChange = (event, slot) => {
    const file = event.target.files?.[0] || null

    if (!file) return

    form.restaurant_page_images = {
        ...form.restaurant_page_images,
        [slot.key]: file,
    }

    restaurantImageNames.value = {
        ...restaurantImageNames.value,
        [slot.key]: file.name,
    }

    const reader = new FileReader()
    reader.onload = () => {
        restaurantImagePreviews.value = {
            ...restaurantImagePreviews.value,
            [slot.key]: reader.result,
        }
    }
    reader.readAsDataURL(file)
}

const openImagePreview = (section) => {
    previewSection.value = section
}

const closeImagePreview = () => {
    previewSection.value = null
}

const goBack = () => {
    clearApiMessages()

    if (currentStep.value === 3) {
        currentStep.value = 2
        return
    }

    if (currentStep.value === 2) {
        currentStep.value = 1
    }
}

const continueToImageSetup = () => {
    clearApiMessages()
    form.clearErrors()

    if (!canContinueToImages.value) {
        form.setError("business", "Please complete the required business details before continuing.")
        return
    }

    currentStep.value = 3
}

const submitRequest = () => {
    clearApiMessages()
    form.clearErrors()

    if (!phoneVerified.value || verifiedPhone.value !== fullPhone.value) {
        form.setError("phone", "Please verify your phone number before submitting the partner request.")
        currentStep.value = 1
        return
    }

    form.transform((data) => ({
        ...data,
        name: data.store_display_name || data.legal_business_name,
        admin_name: data.owner_name,
        email: data.business_email,
        phone: fullPhone.value,
        search_location: data.search_location || data.business_address,
        terms_accepted: data.terms_accepted ? 1 : 0,
        restaurant_page_image_defaults: restaurantImageDefaults.value,
    })).post(route("seller.register.store"), {
        forceFormData: true,
        preserveScroll: true,
    })
}

const maskEmail = (value) => {
    if (!value) return "-"
    const [name, domain] = value.split("@")
    if (!domain) return value
    if (name.length <= 2) return `${name[0] || ""}***@${domain}`
    return `${name.slice(0, 2)}***@${domain}`
}

const maskPhone = (value) => {
    if (!value) return "-"
    const str = String(value)
    if (str.length <= 4) return str
    return `${str.slice(0, 4)}******${str.slice(-2)}`
}

const statusLabel = computed(() => {
    if (!requestRecord.value) return ""
    if (requestRecord.value.status === "approved") return "Approved"
    if (requestRecord.value.status === "rejected") return "Rejected"
    return "Pending Review"
})

const statusTitle = computed(() => {
    if (!requestRecord.value) return ""
    if (requestRecord.value.status === "approved") return "Partner registration approved"
    if (requestRecord.value.status === "rejected") return "Partner registration rejected"
    return "Partner registration request is pending"
})

const statusText = computed(() => {
    if (!requestRecord.value) return ""

    if (requestRecord.value.status === "approved") {
        return "Your partner registration has been approved. Please keep this page link safe for future reference."
    }

    if (requestRecord.value.status === "rejected") {
        return "Your partner registration request was rejected. Please review the rejection reason below."
    }

    return "We have received your partner registration request successfully. Our team will review your details."
})
</script>

<template>
    <section class="partner-auth-page">
        <div class="partner-auth-shell">
            <div class="partner-auth-main">
                <template v-if="requestRecord">
                    <div class="status-panel" :class="`status-panel--${requestRecord.status}`">
                        <span class="step-badge">Registration Status</span>
                        <h1>{{ statusTitle }}</h1>
                        <p>{{ statusText }}</p>
                        <div class="success-callout" v-if="requestRecord.status === 'pending'">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Registration submitted successfully. Your restaurant-page images were saved with this request.</span>
                        </div>

                        <div class="status-grid">
                            <div class="status-box">
                                <span>Shop Name</span>
                                <strong>{{ requestRecord.name }}</strong>
                            </div>
                            <div class="status-box">
                                <span>Owner / Manager</span>
                                <strong>{{ requestRecord.owner_name || requestRecord.admin_name }}</strong>
                            </div>
                            <div class="status-box">
                                <span>Email</span>
                                <strong>{{ maskEmail(requestRecord.email) }}</strong>
                            </div>
                            <div class="status-box">
                                <span>Phone</span>
                                <strong>{{ maskPhone(requestRecord.phone) }}</strong>
                            </div>
                            <div class="status-box">
                                <span>Status</span>
                                <strong>{{ statusLabel }}</strong>
                            </div>
                            <div class="status-box">
                                <span>Request Code</span>
                                <strong>{{ requestRecord.slug }}</strong>
                            </div>
                        </div>

                        <div v-if="requestRecord.status === 'rejected' && requestRecord.reason" class="reason-box">
                            <span>Rejection Reason</span>
                            <p>{{ requestRecord.reason }}</p>
                        </div>

                        <div v-if="requestRecord.restaurant_page_image_urls" class="saved-images-panel">
                            <h3>Restaurant Page Images</h3>
                            <div class="saved-image-grid">
                                <a
                                    v-for="slot in restaurantImageSlots"
                                    :key="slot.key"
                                    :href="requestRecord.restaurant_page_image_urls[slot.key]"
                                    target="_blank"
                                    class="saved-image"
                                >
                                    <img :src="requestRecord.restaurant_page_image_urls[slot.key]" :alt="slot.title">
                                    <span>{{ slot.title }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        class="back-step-btn"
                        aria-label="Back"
                        @click="goBack"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <div class="partner-form-wrap">
                        <div class="step-badge">Step {{ currentStep }} of 3</div>
                        <div class="step-bars">
                            <span :class="{ active: currentStep >= 1 }"></span>
                            <span :class="{ active: currentStep >= 2 }"></span>
                            <span :class="{ active: currentStep >= 3 }"></span>
                        </div>

                        <div v-if="apiError" class="alert-box alert-box--error">{{ apiError }}</div>
                        <div v-if="apiSuccess && currentStep !== 3" class="alert-box alert-box--success">{{ apiSuccess }}</div>

                        <div v-if="Object.keys(form.errors).length" class="alert-box alert-box--error">
                            <div v-for="(error, key) in form.errors" :key="key">{{ error }}</div>
                        </div>

                        <form v-if="currentStep === 1" class="partner-form" @submit.prevent="otpSent ? verifyOtp() : sendOtp()">
                            <div class="form-head">
                                <h1>Become a Partner</h1>
                                <p>Start by verifying your mobile number. We will send a 6-digit OTP code.</p>
                            </div>

                            <div class="field">
                                <label>Mobile number</label>
                                <div class="phone-row">
                                    <select class="field-input country-select" disabled>
                                        <option>LK +94</option>
                                    </select>
                                    <input
                                        class="field-input"
                                        type="tel"
                                        :value="phoneRaw"
                                        inputmode="numeric"
                                        placeholder="76555456"
                                        @input="onPhoneInput"
                                    />
                                </div>
                            </div>

                            <template v-if="otpSent">
                                <p class="otp-sent-text">We sent a 6-digit code to {{ fullPhone }}</p>

                                <div class="otp-row" @paste="onOtpPaste">
                                    <input
                                        v-for="(_, index) in OTP_LENGTH"
                                        :key="index"
                                        ref="otpRefs"
                                        v-model="otpInputs[index]"
                                        class="otp-input"
                                        inputmode="numeric"
                                        maxlength="1"
                                        @input="(event) => onOtpInput(event, index)"
                                        @keydown="(event) => onOtpKeydown(event, index)"
                                    />
                                </div>
                            </template>

                            <button type="submit" class="black-btn" :disabled="otpSent ? !canVerifyOtp : !canSendOtp">
                                <span v-if="loadingSend">Sending...</span>
                                <span v-else-if="loadingVerify">Verifying...</span>
                                <span v-else-if="otpSent">Verify & Continue</span>
                                <span v-else>Send OTP</span>
                            </button>

                            <button v-if="otpSent" type="button" class="resend-btn" :disabled="loadingSend" @click="sendOtp">
                                Resend code
                            </button>

                            <p class="login-text">
                                Already registered?
                                <Link :href="route('vendor.login')" class="red-link">Login as Partner</Link>
                            </p>
                        </form>

                        <form v-else-if="currentStep === 2" class="partner-form business-form" @submit.prevent="continueToImageSetup">
                            <div class="form-head">
                                <h1>Register your food business</h1>
                                <p>Complete your business profile. This information will be used to approve your partner account.</p>
                            </div>

                            <div class="field-grid">
                                <div class="field">
                                    <label>Legal Business Name</label>
                                    <input v-model="form.legal_business_name" class="field-input" type="text" placeholder="Red Oven Foods Pvt Ltd">
                                </div>
                                <div class="field">
                                    <label>Store Display Name</label>
                                    <input v-model="form.store_display_name" class="field-input" type="text" placeholder="Red Oven Pizza">
                                </div>
                            </div>

                            <div class="field">
                                <label>Business Address</label>
                                <div class="address-search-wrap">
                                    <textarea
                                        v-model="form.business_address"
                                        class="field-input"
                                        rows="3"
                                        placeholder="No. 25, Main Street, Colombo"
                                        autocomplete="off"
                                        @input="searchBusinessAddress"
                                        @focus="searchBusinessAddress"
                                    ></textarea>
                                    <div v-if="addressLoading || addressSuggestions.length || addressError" class="address-suggestions">
                                        <div v-if="addressLoading" class="address-suggestion address-suggestion--muted">
                                            Searching Google addresses...
                                        </div>
                                        <button
                                            v-for="suggestion in addressSuggestions"
                                            :key="suggestion.place_id"
                                            type="button"
                                            class="address-suggestion"
                                            @click="selectBusinessAddress(suggestion)"
                                        >
                                            <strong>{{ suggestion.main_text }}</strong>
                                            <span>{{ suggestion.secondary_text || suggestion.description }}</span>
                                        </button>
                                        <div v-if="addressError" class="address-suggestion address-suggestion--error">
                                            {{ addressError }}
                                        </div>
                                    </div>
                                </div>
                                <button v-if="addressSuggestions.length" type="button" class="address-clear-btn" @click="clearAddressSuggestions">
                                    Hide address suggestions
                                </button>
                            </div>

                            <div class="field-grid">
                                <div class="field">
                                    <label>Store Contact Number</label>
                                    <input class="field-input" type="text" :value="fullPhone" readonly>
                                </div>
                                <div class="field">
                                    <label>Business Email</label>
                                    <input v-model="form.business_email" class="field-input" type="email" placeholder="restaurant@email.com">
                                </div>
                                <div class="field">
                                    <label>Owner / Manager Name</label>
                                    <input v-model="form.owner_name" class="field-input" type="text" placeholder="Owner name">
                                </div>
                                <div class="field">
                                    <label>Business Registration Number</label>
                                    <input v-model="form.business_registration_number" class="field-input" type="text" placeholder="BR / PV number">
                                </div>
                                <div class="field">
                                    <label>City</label>
                                    <input v-model="form.city" class="field-input" type="text" placeholder="Colombo">
                                </div>
                                <div class="field">
                                    <label>Country</label>
                                    <select v-model="form.country" class="field-input" @change="form.country_code = form.country === 'Australia' ? 'AU' : 'LK'">
                                        <option>Sri Lanka</option>
                                        <option>Australia</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Address Line 1</label>
                                    <input v-model="form.address_line_1" class="field-input" type="text" placeholder="Street and building">
                                </div>
                                <div class="field">
                                    <label>Address Line 2</label>
                                    <input v-model="form.address_line_2" class="field-input" type="text" placeholder="Area or landmark">
                                </div>
                                <div class="field">
                                    <label>State / Province</label>
                                    <input v-model="form.state_province" class="field-input" type="text" placeholder="Western Province">
                                </div>
                                <div class="field">
                                    <label>Postal Code</label>
                                    <input v-model="form.postal_code" class="field-input" type="text" placeholder="00700">
                                </div>
                            </div>

                            <div class="field">
                                <label>Food Type</label>
                                <div class="check-grid">
                                    <label v-for="option in foodTypeOptions" :key="option" class="check-option">
                                        <input
                                            type="checkbox"
                                            :checked="form.food_types.includes(option)"
                                            @change="toggleArrayValue('food_types', option)"
                                        >
                                        <span>{{ option }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="field">
                                <label>Opening Hours</label>
                                <div class="field-grid">
                                    <div class="field">
                                        <label>From</label>
                                        <input v-model="form.opening_from" class="field-input" type="time">
                                    </div>
                                    <div class="field">
                                        <label>To</label>
                                        <input v-model="form.opening_to" class="field-input" type="time">
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label>Service Options</label>
                                <div class="check-grid">
                                    <label v-for="option in serviceOptions" :key="option" class="check-option">
                                        <input
                                            type="checkbox"
                                            :checked="form.service_options.includes(option)"
                                            @change="toggleArrayValue('service_options', option)"
                                        >
                                        <span>{{ option }}</span>
                                    </label>
                                </div>
                            </div>

                            <label class="terms-row">
                                <input v-model="form.terms_accepted" type="checkbox">
                                <span>I confirm that the information is correct and agree to Sappy Eats Terms, Privacy Policy, commission, refund, and cancellation policies.</span>
                            </label>

                            <button type="submit" class="black-btn" :disabled="!canContinueToImages">
                                Continue to Image Setup
                            </button>
                        </form>

                        <form v-else class="partner-form business-form" @submit.prevent="submitRequest">
                            <div class="form-head">
                                <h1>Setup restaurant page images</h1>
                                <p>Upload the 6 images used on your restaurant page. These images will automatically update the matching areas in restaurant.html.</p>
                            </div>

                            <div class="logo-upload">
                                <label class="logo-circle">
                                    <input type="file" accept="image/*" @change="onLogoChange">
                                    <i class="bi bi-building"></i>
                                </label>
                                <strong>Upload Restaurant Logo</strong>
                                <span>{{ logoName || "No logo selected." }}</span>
                            </div>

                            <div class="restaurant-images-section">
                                <div class="setup-note">
                                    Upload 2 image sets: <strong>Hero section images</strong> and <strong>middle promotional banners</strong>. For this static frontend, images are saved with the partner request and can be reviewed by Super Admin.
                                </div>

                                <div class="image-set-head">
                                    <h3>Hero Section Images</h3>
                                    <button type="button" @click="openImagePreview('hero')">View</button>
                                </div>

                                <div class="restaurant-image-grid">
                                    <label
                                        v-for="slot in restaurantImageSlots.filter((item) => item.group === 'hero')"
                                        :key="slot.key"
                                        class="restaurant-image-card"
                                    >
                                        <span class="image-card-title">{{ slot.title }}</span>
                                        <small>{{ slot.description }}</small>
                                        <span v-if="!restaurantImageNames[slot.key]" class="image-placeholder">Choose Image</span>
                                        <img v-else :src="restaurantImageSrc(slot.key)" :alt="slot.title">
                                        <span class="upload-pill">Upload</span>
                                        <em>{{ restaurantImageNames[slot.key] || "No image selected." }}</em>
                                        <input type="file" accept="image/*" @change="(event) => onRestaurantImageChange(event, slot)">
                                    </label>
                                </div>

                                <div class="image-set-head">
                                    <h3>Middle Banner Images</h3>
                                    <button type="button" @click="openImagePreview('middle')">View</button>
                                </div>

                                <div class="restaurant-image-grid">
                                    <label
                                        v-for="slot in restaurantImageSlots.filter((item) => item.group === 'middle')"
                                        :key="slot.key"
                                        class="restaurant-image-card"
                                    >
                                        <span class="image-card-title">{{ slot.title }}</span>
                                        <small>{{ slot.description }}</small>
                                        <span v-if="!restaurantImageNames[slot.key]" class="image-placeholder">Choose Image</span>
                                        <img v-else :src="restaurantImageSrc(slot.key)" :alt="slot.title">
                                        <span class="upload-pill">Upload</span>
                                        <em>{{ restaurantImageNames[slot.key] || "No image selected." }}</em>
                                        <input type="file" accept="image/*" @change="(event) => onRestaurantImageChange(event, slot)">
                                    </label>
                                </div>
                            </div>

                            <div class="field">
                                <label>Optional Documents</label>
                                <div class="upload-box">
                                    <div>
                                        <strong>Business / Food License</strong>
                                        <span>Recommended for faster approval</span>
                                        <small>{{ licenseName || "No license uploaded." }}</small>
                                    </div>
                                    <label class="file-pill">
                                        Upload File
                                        <input type="file" accept=".pdf,image/*" @change="onLicenseChange">
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="black-btn" :disabled="!canSubmit">
                                <span v-if="form.processing">Submitting...</span>
                                <span v-else>Save Images & Finish Registration</span>
                            </button>

                            <button type="button" class="preview-link-btn" @click="openImagePreview('hero')">
                                Preview restaurant page
                            </button>
                        </form>
                    </div>
                </template>
            </div>

            <aside class="partner-auth-visual">
                <Link :href="route('multivendor.home')" class="close-btn" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </Link>
                <img src="/multivendor/business-auth-image.avif" alt="Restaurant partner onboarding">
                <div class="visual-overlay"></div>
                <div class="visual-copy">
                    <h2>{{ stepTitle }}</h2>
                    <p>{{ stepText }}</p>
                </div>
            </aside>
        </div>

        <div
            v-if="previewSection"
            class="section-preview-overlay"
            role="dialog"
            aria-modal="true"
            @click.self="closeImagePreview"
        >
            <div class="section-preview-modal">
                <div class="section-preview-head">
                    <h2>{{ previewSection === 'hero' ? 'Hero Section Preview' : 'Middle Banner Section Preview' }}</h2>
                    <button type="button" aria-label="Close preview" @click="closeImagePreview">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div v-if="previewSection === 'hero'" class="hero-page-preview">
                    <div class="hero-preview-main-card">
                        <div class="hero-preview-copy">
                            <span>Hot & Fresh</span>
                            <h3>Favorites, Delivered Fast</h3>
                            <p>Burgers, pizzas, wings and more. Order now and enjoy fresh meals at home.</p>
                            <div class="hero-preview-features">
                                <strong>Fast Delivery<br><small>30-40 min</small></strong>
                                <strong>Top Rated<br><small>4.8 ★</small></strong>
                                <strong>Safe & Secure<br><small>100% Protected</small></strong>
                            </div>
                            <b>Order Now</b>
                        </div>
                        <img class="hero-preview-main" :src="restaurantImageSrc('hero_main')" alt="Hero main preview">
                    </div>
                    <div class="hero-preview-side">
                        <img :src="restaurantImageSrc('hero_deal')" alt="Hero deal preview">
                        <img :src="restaurantImageSrc('hero_pizza')" alt="Hero pizza preview">
                    </div>
                </div>

                <div v-else class="middle-page-preview">
                    <img :src="restaurantImageSrc('middle_banner_top')" alt="Top banner preview">
                    <img :src="restaurantImageSrc('middle_banner_bottom')" alt="Bottom banner preview">
                    <img class="middle-page-preview__large" :src="restaurantImageSrc('middle_banner_large')" alt="Large banner preview">
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.partner-auth-page {
    min-height: 100vh;
    background: #ffffff;
    color: #000000;
}

.partner-auth-shell {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.partner-auth-main {
    position: relative;
    min-height: 100vh;
    overflow-y: auto;
    background: #ffffff;
}

.partner-form-wrap,
.status-panel {
    width: min(820px, calc(100% - 120px));
    margin: 74px auto 40px;
}

.step-badge {
    display: block;
    width: 100%;
    border-radius: 999px;
    background: #fdebed;
    color: #c91a25;
    padding: 15px 28px;
    font-size: 14px;
    font-weight: 900;
}

.step-bars {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin: 22px 0 28px;
}

.step-bars span {
    height: 4px;
    border-radius: 999px;
    background: #eeeeee;
}

.step-bars span.active {
    background: #c91a25;
}

.partner-form {
    display: grid;
    gap: 22px;
}

.business-form {
    padding-bottom: 26px;
}

.form-head h1,
.status-panel h1 {
    margin: 0 0 8px;
    font-size: 26px;
    line-height: 1.2;
    font-weight: 900;
    color: #000000;
}

.form-head p,
.status-panel p {
    margin: 0;
    color: #53617b;
    line-height: 1.7;
}

.field,
.field-grid {
    min-width: 0;
}

.field label {
    display: block;
    margin-bottom: 8px;
    color: #000000;
    font-size: 13px;
    font-weight: 900;
}

.field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.field-input {
    width: 100%;
    min-height: 44px;
    border: 1px solid #e1e1e1;
    border-radius: 11px;
    background: #ffffff;
    color: #000000;
    font-size: 14px;
    font-weight: 700;
    padding: 12px 14px;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

textarea.field-input {
    resize: vertical;
}

.address-search-wrap {
    position: relative;
}

.address-suggestions {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    z-index: 8;
    overflow: hidden;
    border: 1px solid #f3c4ca;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
}

.address-suggestion {
    width: 100%;
    border: 0;
    border-bottom: 1px solid #f3f4f6;
    background: #ffffff;
    padding: 12px 14px;
    color: #111827;
    text-align: left;
}

.address-suggestion:last-child {
    border-bottom: 0;
}

.address-suggestion:hover {
    background: #fff1f3;
}

.address-suggestion strong,
.address-suggestion span {
    display: block;
}

.address-suggestion strong {
    font-size: 13px;
    font-weight: 900;
}

.address-suggestion span {
    margin-top: 2px;
    color: #637083;
    font-size: 12px;
    line-height: 1.35;
}

.address-suggestion--muted,
.address-suggestion--error {
    color: #637083;
    font-size: 12px;
    font-weight: 800;
}

.address-suggestion--error {
    color: #b91c1c;
}

.address-clear-btn {
    margin-top: 8px;
    border: 0;
    background: transparent;
    color: #d71928;
    font-size: 12px;
    font-weight: 900;
    padding: 0;
}

.field-input:focus,
.otp-input:focus {
    border-color: #e21b2d;
    box-shadow: 0 0 0 4px rgba(226, 27, 45, 0.12);
}

.phone-row {
    display: grid;
    grid-template-columns: 110px 1fr;
    gap: 8px;
}

.country-select {
    appearance: auto;
}

.black-btn {
    width: 100%;
    min-height: 48px;
    border: 0;
    border-radius: 12px;
    background: #000000;
    color: #ffffff;
    font-weight: 900;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.black-btn:not(:disabled):hover {
    transform: translateY(-1px);
}

.black-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.login-text {
    margin: 4px 0 0;
    color: #53617b;
    font-size: 13px;
}

.red-link,
.resend-btn {
    color: #c91a25;
    font-weight: 900;
    text-decoration: none;
}

.security-icon,
.logo-circle {
    width: 58px;
    height: 58px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    border: 1px solid #fac7cc;
    background: #fff1f3;
    color: #c91a25;
    font-size: 24px;
}

.security-icon {
    margin: 0 auto;
}

.otp-row {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 8px;
}

.otp-sent-text {
    margin: -4px 0 0;
    color: #53617b;
    font-size: 13px;
    line-height: 1.6;
}

.otp-input {
    width: 100%;
    height: 54px;
    border: 1px solid #e1e1e1;
    border-radius: 12px;
    text-align: center;
    font-size: 22px;
    font-weight: 900;
    outline: none;
}

.resend-btn {
    width: fit-content;
    margin: -4px auto 0;
    border: 0;
    background: transparent;
}

.back-step-btn {
    position: fixed;
    top: 25px;
    left: 20px;
    z-index: 5;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 50%;
    background: #f3f4f6;
    color: #000000;
}

.logo-upload {
    display: grid;
    justify-items: center;
    gap: 8px;
    color: #c91a25;
    font-size: 13px;
    font-weight: 900;
}

.logo-circle {
    cursor: pointer;
}

.logo-circle input,
.file-pill input {
    display: none;
}

.check-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.check-option {
    display: flex !important;
    align-items: center;
    gap: 10px;
    min-height: 42px;
    margin: 0 !important;
    padding: 10px 12px;
    border: 1px solid #e1e1e1;
    border-radius: 11px;
    cursor: pointer;
}

.check-option input,
.terms-row input {
    width: 14px;
    height: 14px;
    accent-color: #c91a25;
}

.check-option span {
    font-weight: 900;
    font-size: 13px;
}

.upload-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 90px;
    margin-top: 10px;
    padding: 14px;
    border: 1px dashed #ff9ca5;
    border-radius: 14px;
    background: #fffafa;
}

.restaurant-images-section {
    display: grid;
    gap: 20px;
    padding-top: 8px;
}

.restaurant-images-head h2 {
    margin: 0 0 8px;
    color: #000000;
    font-size: 24px;
    line-height: 1.2;
    font-weight: 900;
}

.restaurant-images-head p {
    margin: 0;
    color: #53617b;
    font-size: 13px;
    line-height: 1.7;
}

.setup-note {
    padding: 14px 16px;
    border: 1px solid #ffc8ce;
    border-radius: 12px;
    background: #fff7f8;
    color: #53617b;
    font-size: 12px;
    line-height: 1.6;
}

.image-set-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 10px;
}

.image-set-head h3 {
    margin: 0;
    color: #000000;
    font-size: 15px;
    font-weight: 900;
}

.image-set-head button {
    min-width: 66px;
    min-height: 34px;
    border: 1px solid #ffb7be;
    border-radius: 999px;
    background: #ffffff;
    color: #c91a25;
    font-size: 12px;
    font-weight: 900;
}

.restaurant-image-grid,
.saved-image-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.restaurant-image-card {
    display: block !important;
    min-width: 0;
    margin: 0 !important;
    padding: 14px;
    border: 1px dashed #ff9ca5;
    border-radius: 12px;
    background: #fffafa;
    cursor: pointer;
}

.restaurant-image-card input {
    display: none;
}

.image-card-title {
    display: block;
    color: #000000;
    font-size: 13px;
    font-weight: 900;
}

.restaurant-image-card small,
.restaurant-image-card em {
    display: block;
    color: #53617b;
    font-size: 11px;
    font-style: normal;
    font-weight: 700;
}

.restaurant-image-card img,
.image-placeholder {
    width: 100%;
    aspect-ratio: 1.55;
    margin: 12px 0 10px;
    border-radius: 10px;
}

.restaurant-image-card img {
    background: #000000;
    object-fit: cover;
}

.image-placeholder {
    display: grid;
    place-items: center;
    border: 1px solid #eadfe1;
    background: linear-gradient(135deg, #fff3f4, #fffafa);
    color: #c91a25;
    font-size: 12px;
    font-weight: 900;
}

.upload-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    background: #000000;
    color: #ffffff;
    font-size: 12px;
    font-weight: 900;
}

.restaurant-image-card em {
    margin-top: 8px;
    word-break: break-word;
}

.upload-box strong,
.upload-box span,
.upload-box small {
    display: block;
}

.upload-box strong {
    color: #000000;
    font-size: 13px;
    font-weight: 900;
}

.upload-box span,
.upload-box small {
    margin-top: 6px;
    color: #53617b;
    font-size: 12px;
    font-weight: 800;
}

.file-pill {
    flex: 0 0 auto;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    margin: 0 !important;
    padding: 8px 14px;
    border-radius: 999px;
    background: #000000;
    color: #ffffff !important;
    font-size: 12px !important;
    font-weight: 900 !important;
    cursor: pointer;
}

.terms-row {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    color: #53617b;
    font-size: 12px;
    line-height: 1.5;
}

.preview-link-btn {
    width: fit-content;
    margin: -4px auto 0;
    border: 0;
    background: transparent;
    color: #c91a25;
    font-size: 13px;
    font-weight: 900;
}

.alert-box {
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 800;
    line-height: 1.6;
}

.alert-box--error {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #be123c;
}

.alert-box--success {
    background: #effdf5;
    border: 1px solid #bbf7d0;
    color: #15803d;
}

.success-callout {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 18px;
    padding: 14px;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    background: #effdf5;
    color: #15803d;
    font-size: 13px;
    font-weight: 900;
    line-height: 1.5;
}

.saved-images-panel {
    margin-top: 20px;
}

.saved-images-panel h3 {
    margin: 0 0 12px;
    color: #000000;
    font-size: 16px;
    font-weight: 900;
}

.saved-image {
    min-width: 0;
    overflow: hidden;
    border: 1px solid #eeeeee;
    border-radius: 12px;
    background: #ffffff;
    color: #000000;
    text-decoration: none;
}

.saved-image img {
    width: 100%;
    aspect-ratio: 1.45;
    object-fit: cover;
}

.saved-image span {
    display: block;
    padding: 9px 10px;
    font-size: 12px;
    font-weight: 900;
}

.partner-auth-visual {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow: hidden;
    background: #111111;
}

.partner-auth-visual img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.visual-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.24), rgba(0, 0, 0, 0.46));
}

.close-btn {
    position: absolute;
    top: 24px;
    right: 24px;
    z-index: 2;
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #ffffff;
    color: #000000;
    text-decoration: none;
}

.visual-copy {
    position: absolute;
    z-index: 2;
    left: 48px;
    right: 48px;
    bottom: 46px;
    color: #ffffff;
}

.visual-copy h2 {
    margin: 0 0 12px;
    max-width: 620px;
    font-size: clamp(30px, 3vw, 42px);
    line-height: 1.05;
    font-weight: 900;
}

.visual-copy p {
    max-width: 460px;
    margin: 0;
    font-size: 16px;
    line-height: 1.6;
    font-weight: 700;
}

.status-panel {
    padding: 28px;
    border: 1px solid #eeeeee;
    border-radius: 18px;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 24px;
}

.status-box {
    padding: 14px;
    border: 1px solid #eeeeee;
    border-radius: 12px;
}

.status-box span,
.reason-box span {
    display: block;
    margin-bottom: 6px;
    color: #53617b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}

.status-box strong {
    color: #000000;
    word-break: break-word;
}

.reason-box {
    margin-top: 14px;
    padding: 14px;
    border-radius: 12px;
    background: #fff1f2;
    color: #be123c;
}

.reason-box p {
    color: #be123c;
}

.section-preview-overlay {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 64px 48px;
    overflow-y: auto;
    background: #f5f6f8;
}

.section-preview-modal {
    width: min(1120px, 100%);
    background: transparent;
}

.section-preview-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 0 0 28px;
}

.section-preview-head h2 {
    margin: 0;
    color: #000000;
    font-size: 18px;
    font-weight: 900;
}

.section-preview-head button {
    width: 38px;
    height: 38px;
    border: 1px solid #dde1e8;
    border-radius: 50%;
    background: #ffffff;
    color: #000000;
}

.hero-page-preview {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 318px;
    gap: 22px;
    align-items: stretch;
}

.hero-preview-main-card {
    position: relative;
    min-height: 555px;
    overflow: hidden;
    border-radius: 26px;
    background: #fff7f1;
    padding: 58px 46px;
}

.hero-preview-copy {
    position: relative;
    z-index: 2;
    width: min(430px, 54%);
}

.hero-preview-copy span {
    color: #c91a25;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 14px;
    font-weight: 900;
}

.hero-preview-copy h3 {
    margin: 22px 0 18px;
    color: #20242b;
    font-size: 58px;
    line-height: 1.02;
    font-weight: 900;
}

.hero-preview-copy p {
    color: #30343b;
    font-size: 16px;
    line-height: 1.55;
    font-weight: 800;
}

.hero-preview-features {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px 32px;
    margin: 32px 0;
}

.hero-preview-features strong {
    color: #262a30;
    font-size: 14px;
    line-height: 1.45;
}

.hero-preview-features small {
    color: #262a30;
    font-size: 13px;
}

.hero-preview-copy b {
    display: inline-flex;
    min-width: 154px;
    height: 48px;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #d71928;
    color: #ffffff;
    font-size: 17px;
}

.hero-preview-main {
    position: absolute;
    right: -5%;
    bottom: -2%;
    width: 62%;
    max-height: 78%;
    object-fit: contain;
}

.hero-preview-side {
    display: grid;
    grid-template-rows: 1fr 1fr;
    gap: 22px;
}

.hero-preview-side img {
    width: 100%;
    height: 100%;
    min-height: 266px;
    border-radius: 24px;
    background: #fff7f1;
    object-fit: cover;
}

.middle-page-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: repeat(2, 290px);
    gap: 26px;
}

.middle-page-preview img {
    width: 100%;
    height: 100%;
    border-radius: 18px;
    background: #ffffff;
    object-fit: cover;
}

.middle-page-preview__large {
    grid-column: 2;
    grid-row: 1 / span 2;
}

@media (max-width: 1100px) {
    .partner-auth-shell {
        grid-template-columns: 1fr;
    }

    .partner-auth-visual {
        position: relative;
        height: 360px;
        order: -1;
    }

    .partner-auth-main {
        min-height: auto;
        overflow: visible;
    }

    .partner-form-wrap,
    .status-panel {
        width: min(820px, calc(100% - 32px));
        margin-top: 28px;
    }

    .hero-page-preview {
        grid-template-columns: 1fr;
    }

    .hero-preview-side {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: auto;
    }

    .hero-preview-side img {
        min-height: 220px;
    }
}

@media (max-width: 640px) {
    .field-grid,
    .check-grid,
    .status-grid,
    .restaurant-image-grid,
    .saved-image-grid,
    .middle-page-preview,
    .hero-preview-side {
        grid-template-columns: 1fr;
    }

    .partner-form-wrap,
    .status-panel {
        width: calc(100% - 24px);
        margin-top: 18px;
    }

    .partner-auth-visual {
        height: 300px;
    }

    .visual-copy {
        left: 22px;
        right: 22px;
        bottom: 24px;
    }

    .visual-copy h2 {
        font-size: 28px;
    }

    .phone-row {
        grid-template-columns: 1fr;
    }

    .otp-row {
        gap: 6px;
    }

    .otp-input {
        height: 48px;
    }

    .upload-box {
        align-items: flex-start;
        flex-direction: column;
    }

    .hero-page-preview {
        display: block;
    }

    .hero-preview-main-card {
        min-height: 590px;
        padding: 28px;
    }

    .hero-preview-copy {
        width: 100%;
    }

    .hero-preview-copy h3 {
        font-size: 42px;
    }

    .hero-preview-features {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .hero-preview-main {
        right: -22%;
        bottom: 0;
        width: 92%;
    }

    .hero-preview-side {
        margin-top: 18px;
    }

    .middle-page-preview {
        grid-template-rows: repeat(3, 180px);
    }

    .middle-page-preview__large {
        grid-column: auto;
        grid-row: auto;
    }
}
</style>
