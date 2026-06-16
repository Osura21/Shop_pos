<template>
    <MultiVendorLayout>
        <Banner
            :total="vehicles?.total || 0"
            :typesCount="(vehicleTypes || []).length"
            :makesCount="(manufacturers || []).length"
        />

        <Head :title="pageTitle" />

        <div class="theme-contact-page">
            <section class="theme-contact-hero">
                <div class="theme-contact-shell"></div>
            </section>

            <section class="theme-contact-body">
                <div class="theme-contact-shell">
                    <div class="preview-wrap">
                        <div class="preview-top">
                            <div>
                                <h2>{{ templateName }}</h2>
                            </div>
                        </div>

                        <div class="preview-canvas">
                            <div class="contact-preview-header">
                                <h3>{{ pageTitle }}</h3>
                                <p>{{ pageSubtitle }}</p>
                            </div>

                            <div class="contact-preview-grid">
                                <div class="contact-form-card">
                                    <div class="contact-form-card__header">
                                        <h4>Send Us a Message</h4>
                                        <p>Fill the form and we will get back to you soon.</p>
                                    </div>

                                    <form ref="contactFormRef" class="real-form" @submit.prevent="sendContactEmail">
                                        <input type="hidden" name="to_email" :value="contactUsTemplate?.contact_email || ''" />
                                        <input type="hidden" name="tenant_name" :value="tenantLabel" />
                                        <input type="hidden" name="vendor_phone" :value="contactPhone" />
                                        <input type="hidden" name="vendor_email" :value="contactEmail" />
                                        <input type="hidden" name="vendor_address" :value="contactAddress" />
                                        <input type="hidden" name="subject" :value="`New Contact Message - ${tenantLabel}`" />

                                        <div class="fake-field">
                                            <label for="contact-name">Name</label>
                                            <input
                                                id="contact-name"
                                                v-model="formState.from_name"
                                                type="text"
                                                name="from_name"
                                                class="real-input"
                                                placeholder="Enter your name"
                                                required
                                            />
                                        </div>

                                        <div class="fake-field">
                                            <label for="contact-email">Email</label>
                                            <input
                                                id="contact-email"
                                                v-model="formState.from_email"
                                                type="email"
                                                name="from_email"
                                                class="real-input"
                                                placeholder="Enter your email"
                                                required
                                            />
                                        </div>

                                        <div class="fake-field">
                                            <label for="contact-phone">Phone Number</label>
                                            <input
                                                id="contact-phone"
                                                v-model="formState.phone_number"
                                                type="text"
                                                name="phone_number"
                                                class="real-input"
                                                placeholder="Enter your phone number"
                                            />
                                        </div>

                                        <div class="fake-field">
                                            <label for="contact-message">Message</label>
                                            <textarea
                                                id="contact-message"
                                                v-model="formState.message"
                                                name="message"
                                                class="real-input real-input--textarea"
                                                placeholder="Write your message"
                                                required
                                            ></textarea>
                                        </div>

                                        <div
                                            v-if="sendNotice.text"
                                            class="form-notice"
                                            :class="sendNotice.type === 'success' ? 'form-notice--success' : 'form-notice--error'"
                                        >
                                            {{ sendNotice.text }}
                                        </div>

                                        <button type="submit" class="fake-submit" :disabled="isSending">
                                            <i class="bi bi-send-fill"></i>
                                            <span>{{ isSending ? 'Sending...' : 'Send Message' }}</span>
                                        </button>
                                    </form>
                                </div>

                                <div class="contact-info-card">
                                    <div class="contact-info-card__glow"></div>

                                    <div class="contact-info-card__content">
                                        <span class="info-pill">Contact Details</span>
                                        <h4>{{ contactBoxTitle }}</h4>

                                        <div class="info-list">
                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="bi bi-telephone-fill"></i>
                                                </div>
                                                <div>
                                                    <small>Phone</small>
                                                    <strong>
                                                        <a
                                                            v-if="contactUsTemplate?.contact_phone"
                                                            :href="phoneHref"
                                                            class="info-link"
                                                        >
                                                            {{ contactPhone }}
                                                        </a>
                                                        <template v-else>{{ contactPhone }}</template>
                                                    </strong>
                                                </div>
                                            </div>

                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="bi bi-envelope-fill"></i>
                                                </div>
                                                <div>
                                                    <small>Email</small>
                                                    <strong>
                                                        <a
                                                            v-if="contactUsTemplate?.contact_email"
                                                            :href="emailHref"
                                                            class="info-link"
                                                        >
                                                            {{ contactEmail }}
                                                        </a>
                                                        <template v-else>{{ contactEmail }}</template>
                                                    </strong>
                                                </div>
                                            </div>

                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="bi bi-geo-alt-fill"></i>
                                                </div>
                                                <div>
                                                    <small>Address</small>
                                                    <strong>{{ contactAddress }}</strong>
                                                </div>
                                            </div>

                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="bi bi-whatsapp"></i>
                                                </div>
                                                <div>
                                                    <small>WhatsApp</small>
                                                    <strong>
                                                        <a
                                                            v-if="contactUsTemplate?.contact_whatsapp"
                                                            :href="whatsAppHref"
                                                            class="info-link"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                        >
                                                            {{ contactWhatsapp }}
                                                        </a>
                                                        <template v-else>{{ contactWhatsapp }}</template>
                                                    </strong>
                                                </div>
                                            </div>

                                            <div class="info-item">
                                                <div class="info-icon">
                                                    <i class="bi bi-clock-fill"></i>
                                                </div>
                                                <div>
                                                    <small>Working Hours</small>
                                                    <strong>{{ contactWorkingHours }}</strong>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="info-note">
                                            {{ contactNote }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="map-preview-card">
                                <div class="map-preview-head">
                                    <h4>Our Location</h4>
                                    <p>Map preview</p>
                                </div>

                                <div class="map-preview-body">
                                    <iframe
                                        v-if="mapEmbedUrl"
                                        :src="mapEmbedUrl"
                                        width="100%"
                                        height="360"
                                        style="border: 0;"
                                        allowfullscreen=""
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        class="map-frame-direct"
                                    ></iframe>

                                    <div v-else class="map-placeholder">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <span>Map is not available for this vendor yet.</span>
                                    </div>
                                </div>

                                <div v-if="rawMapLink && !mapEmbedUrl" class="map-fallback-link">
                                    <a
                                        :href="rawMapLink"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Open map in Google Maps
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </MultiVendorLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import emailjs from '@emailjs/browser'
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import Banner from "./Banner.vue"

const props = defineProps({
    tenant: {
        type: Object,
        default: null,
    },
    contactUsTemplate: {
        type: Object,
        default: null,
    },
    vehicles: {
        type: Object,
        default: null,
    },
    vehicleTypes: {
        type: Array,
        default: () => [],
    },
    manufacturers: {
        type: Array,
        default: () => [],
    },
})

const contactFormRef = ref(null)
const isSending = ref(false)
const sendNotice = reactive({
    type: '',
    text: '',
})

const formState = reactive({
    from_name: '',
    from_email: '',
    phone_number: '',
    message: '',
})

const EMAILJS_SERVICE_ID = import.meta.env.VITE_EMAILJS_SERVICE_ID
const EMAILJS_TEMPLATE_ID = import.meta.env.VITE_EMAILJS_TEMPLATE_ID
const EMAILJS_PUBLIC_KEY = import.meta.env.VITE_EMAILJS_PUBLIC_KEY

const canSendEmail = computed(() => {
    return !!(EMAILJS_SERVICE_ID && EMAILJS_TEMPLATE_ID && EMAILJS_PUBLIC_KEY)
})

const templateName = computed(() => {
    return props.contactUsTemplate?.template_name || 'Contact Us Template'
})

const pageTitle = computed(() => {
    return props.contactUsTemplate?.page_title || 'Get In Touch With Us'
})

const pageSubtitle = computed(() => {
    return props.contactUsTemplate?.page_subtitle || 'Have questions or need support? Use the form below or reach out through our contact details.'
})

const contactBoxTitle = computed(() => {
    return props.contactUsTemplate?.contact_box_title || 'Contact Information'
})

const contactPhone = computed(() => {
    return props.contactUsTemplate?.contact_phone || '+94 77 123 4567'
})

const contactEmail = computed(() => {
    return props.contactUsTemplate?.contact_email || 'hello@example.com'
})

const contactAddress = computed(() => {
    return props.contactUsTemplate?.contact_address || '25 Main Street, Colombo, Sri Lanka'
})

const contactWhatsapp = computed(() => {
    return props.contactUsTemplate?.contact_whatsapp || '+94 77 123 4567'
})

const contactWorkingHours = computed(() => {
    return props.contactUsTemplate?.contact_working_hours || 'Mon - Sat | 8.00 AM - 6.00 PM'
})

const contactNote = computed(() => {
    return props.contactUsTemplate?.contact_note || 'We usually respond within 24 hours during working days.'
})

const tenantLabel = computed(() => {
    return (
        props.tenant?.name ||
        props.tenant?.title ||
        props.tenant?.business_name ||
        props.tenant?.company_name ||
        'our company'
    )
})

const cleanPhone = (value) => String(value || '').replace(/[^\d+]/g, '')
const cleanWhatsapp = (value) => String(value || '').replace(/[^\d]/g, '')

const phoneHref = computed(() => `tel:${cleanPhone(contactPhone.value)}`)
const emailHref = computed(() => `mailto:${contactEmail.value}`)
const whatsAppHref = computed(() => `https://wa.me/${cleanWhatsapp(contactWhatsapp.value)}`)

const rawMapLink = computed(() => String(props.contactUsTemplate?.map_iframe || '').trim())

const extractIframeSrc = (raw) => {
    if (!raw || !raw.includes('<iframe')) return ''

    const match =
        raw.match(/src\s*=\s*"([^"]+)"/i) ||
        raw.match(/src\s*=\s*'([^']+)'/i)

    return match?.[1] || ''
}

const safeDecode = (value) => {
    try {
        return decodeURIComponent(value)
    } catch (e) {
        return value
    }
}

const buildEmbedByQuery = (query) => {
    const cleaned = String(query || '').trim()
    if (!cleaned) return ''
    return `https://www.google.com/maps?q=${encodeURIComponent(cleaned)}&z=15&output=embed`
}

const extractPlaceFromGoogleUrl = (raw) => {
    if (!raw) return ''

    try {
        const url = new URL(raw)

        const q =
            url.searchParams.get('q') ||
            url.searchParams.get('query') ||
            url.searchParams.get('destination') ||
            url.searchParams.get('daddr')

        if (q) return safeDecode(q)

        const path = safeDecode(url.pathname)

        const placeMatch = path.match(/\/place\/([^/]+)/i)
        if (placeMatch?.[1]) {
            return placeMatch[1].replace(/\+/g, ' ')
        }

        const searchMatch = path.match(/\/search\/([^/]+)/i)
        if (searchMatch?.[1]) {
            return searchMatch[1].replace(/\+/g, ' ')
        }

        const dirMatch = path.match(/\/dir\/.*?\/([^/]+)/i)
        if (dirMatch?.[1]) {
            return dirMatch[1].replace(/\+/g, ' ')
        }

        const dataMatch =
            safeDecode(raw).match(/!1s([^!]+)/) ||
            safeDecode(raw).match(/!2s([^!]+)/)

        if (dataMatch?.[1]) {
            return dataMatch[1].replace(/\+/g, ' ')
        }

        return ''
    } catch (e) {
        return ''
    }
}

const mapEmbedUrl = computed(() => {
    const raw = rawMapLink.value
    if (!raw) return ''

    const iframeSrc = extractIframeSrc(raw)
    if (iframeSrc) {
        return iframeSrc
    }

    if (raw.includes('/maps/embed') || raw.includes('output=embed')) {
        return raw
    }

    if (
        raw.includes('google.com/maps') ||
        raw.includes('goo.gl/maps') ||
        raw.includes('maps.app.goo.gl')
    ) {
        const place = extractPlaceFromGoogleUrl(raw)
        if (place) {
            return buildEmbedByQuery(place)
        }

        return buildEmbedByQuery(raw)
    }

    return buildEmbedByQuery(raw)
})

const firstValidationMessage = (errors) => {
    if (!errors || typeof errors !== 'object') return ''
    const firstKey = Object.keys(errors)[0]
    if (!firstKey) return ''
    const value = errors[firstKey]
    return Array.isArray(value) ? value[0] : value
}

const resetForm = () => {
    formState.from_name = ''
    formState.from_email = ''
    formState.phone_number = ''
    formState.message = ''
}

const sendContactEmail = async () => {
    sendNotice.type = ''
    sendNotice.text = ''

    if (!formState.from_name.trim() || !formState.from_email.trim() || !formState.message.trim()) {
        sendNotice.type = 'error'
        sendNotice.text = 'Please fill in name, email, and message.'
        return
    }

    try {
        isSending.value = true

        await axios.post('/contact-inquiries', {
            name: formState.from_name,
            email: formState.from_email,
            phone_number: formState.phone_number,
            message: formState.message,
        })

        if (canSendEmail.value) {
            try {
                await emailjs.sendForm(
                    EMAILJS_SERVICE_ID,
                    EMAILJS_TEMPLATE_ID,
                    contactFormRef.value,
                    {
                        publicKey: EMAILJS_PUBLIC_KEY,
                    }
                )

                sendNotice.type = 'success'
                sendNotice.text = 'Your inquiry was saved and email was sent successfully.'
            } catch (emailError) {
                console.error('EmailJS send failed:', emailError)
                sendNotice.type = 'success'
                sendNotice.text = 'Your inquiry was saved successfully, but email sending failed.'
            }
        } else {
            sendNotice.type = 'success'
            sendNotice.text = 'Your inquiry was saved successfully.'
        }

        resetForm()
    } catch (error) {
        console.error('Inquiry save failed:', error)
        sendNotice.type = 'error'
        sendNotice.text =
            firstValidationMessage(error?.response?.data?.errors) ||
            error?.response?.data?.message ||
            'Failed to save your inquiry. Please try again.'
    } finally {
        isSending.value = false
    }
}
</script>

<style scoped>
.theme-contact-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top right, rgba(14, 165, 233, 0.12), transparent 22%),
        linear-gradient(180deg, #f8fafc 0%, #ffffff 32%, #f8fafc 100%);
}

.theme-contact-shell {
    width: min(1240px, calc(100% - 32px));
    margin: 0 auto;
}

.theme-contact-hero {
    padding: 44px 0 18px;
}

.theme-contact-hero__card {
    border-radius: 30px;
    padding: 34px;
    background:
        radial-gradient(circle at top right, rgba(14, 165, 233, 0.14), transparent 28%),
        linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.theme-contact-hero__kicker {
    margin: 0 0 8px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 12px;
    font-weight: 800;
    color: #0284c7;
}

.theme-contact-hero__card h1 {
    margin: 0;
    font-size: 38px;
    line-height: 1.08;
    font-weight: 900;
    color: #0f172a;
}

.theme-contact-hero__card p {
    margin: 12px 0 0;
    max-width: 820px;
    color: #475569;
    font-size: 15px;
    line-height: 1.8;
}

.theme-contact-body {
    padding: 6px 0 60px;
}

.preview-wrap {
    overflow: hidden;
    border-radius: 26px;
    backdrop-filter: blur(10px);
}

.preview-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    padding: 22px 22px 0;
}

.preview-top h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 900;
    color: #0f172a;
}

.preview-canvas {
    padding: 22px;
}

.contact-preview-header {
    text-align: center;
    padding: 8px 0 24px;
}

.contact-preview-header h3 {
    margin: 0 0 10px;
    font-size: 30px;
    line-height: 1.15;
    font-weight: 900;
    color: #0f172a;
}

.contact-preview-header p {
    margin: 0 auto;
    max-width: 680px;
    color: #64748b;
    line-height: 1.8;
}

.contact-preview-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.9fr);
    gap: 20px;
    margin-bottom: 22px;
}

.contact-form-card,
.contact-info-card,
.map-preview-card {
    border-radius: 24px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.contact-form-card {
    background: #ffffff;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
    padding: 22px;
}

.contact-form-card__header h4,
.map-preview-head h4 {
    margin: 0;
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
}

.contact-form-card__header p,
.map-preview-head p {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 14px;
}

.fake-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.fake-field label {
    font-size: 13px;
    font-weight: 800;
    color: #334155;
}

.real-form {
    margin-top: 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.real-input {
    width: 100%;
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: #f8fafc;
    padding: 0 14px;
    color: #0f172a;
    font-size: 14px;
    outline: none;
    transition: 0.2s ease;
}

.real-input:focus {
    border-color: rgba(2, 132, 199, 0.55);
    box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.10);
    background: #ffffff;
}

.real-input::placeholder {
    color: #94a3b8;
}

.real-input--textarea {
    min-height: 130px;
    padding-top: 14px;
    padding-bottom: 14px;
    resize: vertical;
}

.form-notice {
    border-radius: 14px;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 700;
}

.form-notice--success {
    background: #ecfdf5;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.form-notice--error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.fake-submit {
    margin-top: 8px;
    height: 48px;
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #0284c7, #2563eb);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 800;
    cursor: pointer;
}

.fake-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.contact-info-card {
    position: relative;
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.18);
}

.contact-info-card__glow {
    position: absolute;
    inset: auto -40px -40px auto;
    width: 180px;
    height: 180px;
    border-radius: 999px;
    background: rgba(56, 189, 248, 0.18);
    filter: blur(20px);
}

.contact-info-card__content {
    position: relative;
    z-index: 1;
    padding: 24px;
}

.info-pill {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: #e0f2fe;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 14px;
}

.contact-info-card__content h4 {
    margin: 0 0 18px;
    font-size: 24px;
    font-weight: 900;
    color: #fff;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.info-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 14px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.06);
}

.info-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(56, 189, 248, 0.16);
    color: #7dd3fc;
    flex: 0 0 auto;
}

.info-item small {
    display: block;
    color: #cbd5e1;
    font-size: 12px;
    margin-bottom: 4px;
}

.info-item strong {
    color: #fff;
    font-size: 14px;
    line-height: 1.6;
    white-space: pre-line;
}

.info-link {
    color: inherit;
    text-decoration: none;
}

.info-link:hover {
    text-decoration: underline;
}

.info-note {
    margin-top: 18px;
    padding: 14px 16px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.08);
    color: #e2e8f0;
    line-height: 1.7;
    font-size: 14px;
}

.map-preview-card {
    background: #fff;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
}

.map-preview-head {
    padding: 20px 22px 0;
}

.map-preview-body {
    padding: 20px 22px 22px;
}

.map-frame-direct {
    width: 100%;
    min-height: 360px;
    border: 0;
    border-radius: 20px;
    overflow: hidden;
    display: block;
}

.map-placeholder {
    min-height: 360px;
    border-radius: 20px;
    border: 1px dashed rgba(2, 132, 199, 0.28);
    background:
        linear-gradient(135deg, rgba(2, 132, 199, 0.06), rgba(37, 99, 235, 0.05)),
        #f8fbff;
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 20px;
    color: #64748b;
}

.map-placeholder i {
    font-size: 32px;
    color: #0284c7;
}

.map-placeholder span {
    max-width: 360px;
    line-height: 1.7;
    font-weight: 700;
}

.map-fallback-link {
    padding: 0 22px 22px;
}

.map-fallback-link a {
    color: #0284c7;
    font-weight: 700;
    text-decoration: none;
}

.map-fallback-link a:hover {
    text-decoration: underline;
}

@media (max-width: 1199.98px) {
    .contact-preview-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .theme-contact-shell {
        width: min(100% - 20px, 1240px);
    }

    .theme-contact-hero {
        padding-top: 24px;
    }

    .theme-contact-hero__card {
        padding: 24px 18px;
        border-radius: 22px;
    }

    .theme-contact-hero__card h1 {
        font-size: 28px;
    }

    .preview-wrap {
        border-radius: 22px;
    }

    .preview-top {
        flex-direction: column;
    }

    .preview-canvas {
        padding: 18px;
    }

    .contact-preview-header h3 {
        font-size: 24px;
    }

    .map-frame-direct,
    .map-placeholder {
        min-height: 260px !important;
    }
}
</style>