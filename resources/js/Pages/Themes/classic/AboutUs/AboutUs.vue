<template>

    <MultiVendorLayout>

         <Banner
      :total="vehicles?.total || 0"
      :typesCount="(vehicleTypes || []).length"
      :makesCount="(manufacturers || []).length"
    />

    <Head :title="pageTitle" />

    <div class="theme-about-page">
        <section class="theme-about-hero">
            <div class="theme-about-shell">
                <!-- <div class="theme-about-hero__card">
                    <p class="theme-about-hero__kicker">About Us</p>
                    <h1>{{ pageTitle }}</h1>
                    <p>
                        Discover more about {{ tenantLabel }}, our values, our story, and the experience we bring to every customer.
                    </p>
                </div> -->
            </div>
        </section>

        <section class="theme-about-body">
            <div class="theme-about-shell">
                <div class="preview-wrap">
                    <div class="preview-canvas">
                        <section class="modern-section modern-section--one">
                            <div class="modern-media modern-media--single">
                                <img
                                    v-if="aboutUsTemplate?.section1_image_url"
                                    :src="aboutUsTemplate.section1_image_url"
                                    :alt="aboutUsTemplate?.section1_title || 'Section 1 image'"
                                />
                                <div v-else class="modern-placeholder">
                                    <i class="bi bi-image"></i>
                                    <span>Section 1 Image</span>
                                </div>
                            </div>

                            <div class="modern-content">
                                <!-- <span class="modern-badge">Section 1</span> -->
                                <h3>{{ aboutUsTemplate?.section1_title || 'Craft a powerful brand introduction' }}</h3>
                                <div
                                    class="modern-description"
                                    v-html="aboutUsTemplate?.section1_description || defaultDescriptions.section1"
                                ></div>
                            </div>
                        </section>

                        <section class="modern-section modern-section--two">
                            <div class="modern-content">
                                <!-- <span class="modern-badge">Section 2</span> -->
                                <h3>{{ aboutUsTemplate?.section2_title || 'Tell the story behind your brand' }}</h3>
                                <div
                                    class="modern-description"
                                    v-html="aboutUsTemplate?.section2_description || defaultDescriptions.section2"
                                ></div>
                            </div>

                            <div class="collage-shell">
                                <div class="collage-shell__bg"></div>

                                <div class="collage-box collage-box--one">
                                    <img
                                        v-if="aboutUsTemplate?.section2_image_1_url"
                                        :src="aboutUsTemplate.section2_image_1_url"
                                        :alt="aboutUsTemplate?.section2_title || 'Section 2 image 1'"
                                    />
                                    <div v-else class="modern-placeholder modern-placeholder--small">
                                        <i class="bi bi-card-image"></i>
                                        <span>Image 1</span>
                                    </div>
                                </div>

                                <div class="collage-box collage-box--two">
                                    <img
                                        v-if="aboutUsTemplate?.section2_image_2_url"
                                        :src="aboutUsTemplate.section2_image_2_url"
                                        :alt="aboutUsTemplate?.section2_title || 'Section 2 image 2'"
                                    />
                                    <div v-else class="modern-placeholder modern-placeholder--small">
                                        <i class="bi bi-card-image"></i>
                                        <span>Image 2</span>
                                    </div>
                                </div>

                                <div class="collage-box collage-box--three">
                                    <img
                                        v-if="aboutUsTemplate?.section2_image_3_url"
                                        :src="aboutUsTemplate.section2_image_3_url"
                                        :alt="aboutUsTemplate?.section2_title || 'Section 2 image 3'"
                                    />
                                    <div v-else class="modern-placeholder modern-placeholder--small">
                                        <i class="bi bi-card-image"></i>
                                        <span>Image 3</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="modern-section modern-section--three">
                            <div class="modern-media modern-media--single">
                                <img
                                    v-if="aboutUsTemplate?.section3_image_url"
                                    :src="aboutUsTemplate.section3_image_url"
                                    :alt="aboutUsTemplate?.section3_title || 'Section 3 image'"
                                />
                                <div v-else class="modern-placeholder">
                                    <i class="bi bi-image"></i>
                                    <span>Section 3 Image</span>
                                </div>
                            </div>

                            <div class="modern-content">
                                <!-- <span class="modern-badge">Section 3</span> -->
                                <h3>{{ aboutUsTemplate?.section3_title || 'Finish with confidence and trust' }}</h3>
                                <div
                                    class="modern-description"
                                    v-html="aboutUsTemplate?.section3_description || defaultDescriptions.section3"
                                ></div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </section>
    </div>
    </MultiVendorLayout>
</template>



<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue";
import Banner from "./Banner.vue";

const props = defineProps({
    tenant: {
        type: Object,
        default: null,
    },
    aboutUsTemplate: {
        type: Object,
        default: null,
    },
     components: {
        MultiVendorLayout,
       
    },
})

const defaultDescriptions = {
    section1:
        '<p>Introduce your company with a warm and confident message that explains who you are, what you do, and why customers trust your service.</p>',
    section2:
        '<p>Use this section to explain your story, your values, and the experience behind your business while supporting it with a strong visual collage.</p>',
    section3:
        '<p>Close with a forward-looking statement that reinforces your commitment to quality, customer care, and long-term excellence.</p>',
}

const pageTitle = computed(() => props.aboutUsTemplate?.template_name || 'About Us')

const tenantLabel = computed(() => {
    return (
        props.tenant?.name ||
        props.tenant?.title ||
        props.tenant?.business_name ||
        props.tenant?.company_name ||
        'our company'
    )
})
</script>

<style scoped>
.theme-about-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top right, rgba(139, 92, 246, 0.12), transparent 22%),
        linear-gradient(180deg, #f8fafc 0%, #ffffff 32%, #f8fafc 100%);
}

.theme-about-shell {
    width: min(1240px, calc(100% - 32px));
    margin: 0 auto;
}

.theme-about-hero {
    padding: 44px 0 18px;
}

.theme-about-hero__card {
    border-radius: 30px;
    padding: 34px;
    background:
        radial-gradient(circle at top right, rgba(139, 92, 246, 0.16), transparent 28%),
        linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.theme-about-hero__kicker {
    margin: 0 0 8px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 12px;
    font-weight: 800;
    color: #7c3aed;
}

.theme-about-hero__card h1 {
    margin: 0;
    font-size: 38px;
    line-height: 1.08;
    font-weight: 900;
    color: #0f172a;
}

.theme-about-hero__card p {
    margin: 12px 0 0;
    max-width: 820px;
    color: #475569;
    font-size: 15px;
    line-height: 1.8;
}

.theme-about-body {
    padding: 6px 0 60px;
}

.preview-wrap {
    overflow: hidden;
    border-radius: 30px;
    
    
    backdrop-filter: blur(10px);
}

.preview-canvas {
    padding: 28px;
}

.modern-section {
    display: grid;
    gap: 26px;
    align-items: center;
    padding: 28px 0;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.modern-section--one,
.modern-section--three {
    grid-template-columns: minmax(240px, 0.95fr) minmax(0, 1fr);
}

.modern-section--two {
    grid-template-columns: minmax(0, 1fr) minmax(280px, 1.05fr);
}

.modern-section:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.modern-content {
    min-width: 0;
}

.modern-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(124, 58, 237, 0.09);
    color: #7c3aed;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 14px;
}

.modern-content h3 {
    margin: 0 0 12px;
    font-size: 28px;
    line-height: 1.18;
    font-weight: 900;
    color: #0f172a;
}

.modern-description {
    color: #475569;
    line-height: 1.8;
    font-size: 15px;
}

:deep(.modern-description p) {
    margin: 0 0 12px;
}

:deep(.modern-description p:last-child) {
    margin-bottom: 0;
}

:deep(.modern-description ul),
:deep(.modern-description ol) {
    margin: 0 0 12px 20px;
    padding: 0;
}

:deep(.modern-description a) {
    color: #2563eb;
    text-decoration: underline;
}

.modern-media--single {
    height: 320px;
    border-radius: 30px;
    overflow: hidden;
    background: #e2e8f0;
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
    position: relative;
}

.modern-media--single::after {
    content: '';
    position: absolute;
    inset: auto 18px 18px auto;
    width: 68px;
    height: 68px;
    border-radius: 20px;
    
   
}

.modern-media--single img,
.collage-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.collage-shell {
    position: relative;
    display: grid;
    grid-template-columns: 0.9fr 1.3fr;
    grid-template-rows: 1fr 0.55fr;
    gap: 14px;
    height: 430px;
}

.collage-shell__bg {
    position: absolute;
    inset: 16px 8px 10px 16px;
    border-radius: 32px;
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.10), rgba(37, 99, 235, 0.08));
    z-index: 0;
}

.collage-box {
    position: relative;
    z-index: 1;
    border-radius: 24px;
    overflow: hidden;
    background: #e2e8f0;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
}

.collage-box--one {
    grid-column: 1 / 2;
    grid-row: 1 / 2;
}

.collage-box--two {
    grid-column: 1 / 2;
    grid-row: 2 / 3;
}

.collage-box--three {
    grid-column: 2 / 3;
    grid-row: 1 / 3;
}

.modern-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 18px;
    color: #64748b;
    background:
        linear-gradient(135deg, rgba(124, 58, 237, 0.12), rgba(59, 130, 246, 0.08)),
        #f8fafc;
}

.modern-placeholder i {
    font-size: 28px;
    color: #7c3aed;
}

.modern-placeholder span {
    font-weight: 800;
}

.modern-placeholder--small i {
    font-size: 22px;
}

@media (max-width: 991.98px) {
    .modern-section--one,
    .modern-section--two,
    .modern-section--three {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .theme-about-shell {
        width: min(100% - 20px, 1240px);
    }

    .theme-about-hero {
        padding-top: 24px;
    }

    .theme-about-hero__card {
        padding: 24px 18px;
        border-radius: 22px;
    }

    .theme-about-hero__card h1 {
        font-size: 28px;
    }

    .preview-wrap {
        border-radius: 22px;
    }

    .preview-canvas {
        padding: 18px;
    }

    .modern-content h3 {
        font-size: 24px;
    }

    .collage-shell {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 160px 160px;
        height: auto;
    }

    .collage-box--one {
        grid-column: 1 / 2;
        grid-row: 1 / 2;
    }

    .collage-box--two {
        grid-column: 1 / 2;
        grid-row: 2 / 3;
    }

    .collage-box--three {
        grid-column: 2 / 3;
        grid-row: 1 / 3;
    }

    .modern-media--single {
        height: 240px;
    }
}
</style>