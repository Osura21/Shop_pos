<template>
    <MultiVendorLayout>
        <section
            class="hero-section position-relative d-flex align-items-center justify-content-center overflow-hidden"
        >
            <div class="position-absolute top-0 start-0 w-100 h-100 hero-video-wrapper">
                <div class="hero-video-fallback"></div>

                <video
                    class="hero-video"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="auto"
                    poster="/assets/images/hero-fallback-image.png"
                    onloadeddata="this.classList.add('video-loaded')"
                >
                    <source src="/assets/videos/pikaso-untitled-project-2026-01-19.mp4" type="video/mp4" />
                </video>

                <div class="overlay overlay-vertical"></div>
                <div class="overlay overlay-horizontal"></div>
            </div>

            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10 grid-overlay"></div>

            <div class="container position-relative z-2 px-4 hero-content-wrap">
                <div class="col-lg-7">
                    <h1 class="hero-title fw-bold text-white mb-4 fade-in-up delay-1">
                        Find Your
                        <span class="custom-text-hero">Perfect</span> Drive
                    </h1>

                    <p class="lead text-white-50 mb-4 fade-in-up delay-2">
                        Discover exceptional vehicles from trusted sellers.
                        Buy, sell, and experience automotive excellence.
                    </p>
                </div>
            </div>

            <div class="scroll-indicator">
                <div class="scroll-circle">
                    <svg viewBox="0 0 100 100" class="scroll-text">
                        <defs>
                            <path
                                id="circlePath"
                                d="
                                    M 50,50
                                    m -36,0
                                    a 36,36 0 1,1 72,0
                                    a 36,36 0 1,1 -72,0
                                "
                            />
                        </defs>

                        <text fill="white" font-size="6" letter-spacing="1.5">
                            <textPath href="#circlePath" startOffset="0%" textLength="226" lengthAdjust="spacing">
                                Autosale.lk • SCROLL DOWN • Autosale.lk • SCROLL DOWN •
                            </textPath>
                        </text>
                    </svg>

                    <div class="scroll-arrow">
                        <i class="bi bi-arrow-down"></i>
                    </div>
                </div>
            </div>
        </section>

        <div class="home-filter-overlap">
            <FilterSection />
        </div>

        <FeaturedListings />
        <BrandNewListings />
        <SellCTA />
        <UsedListings />
        <TrustIndicators />
    </MultiVendorLayout>
</template>

<script>
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue";
import FeaturedListings from "./Components/FeaturedListings.vue";
import SellCTA from "./Components/SellCTA.vue";
import TrustIndicators from "./Components/TrustIndicators.vue";
import FilterSection from "./Components/FilterSection.vue";
import BrandNewListings from "./Components/BrandNewListings.vue";
import UsedListings from "./Components/UsedListings.vue";

export default {
    name: "Home",
    components: {
        MultiVendorLayout,
        FeaturedListings,
        SellCTA,
        TrustIndicators,
        FilterSection,
        BrandNewListings,
        UsedListings,
    },
};
</script>

<style scoped>
.hero-section {
    min-height: 88vh;
    padding-bottom: 130px;
}

.hero-content-wrap {
    padding-top: 70px;
}

.hero-title {
    font-size: clamp(3.4rem, 8vw, 6.8rem);
    line-height: 0.95;
    font-weight: 900;
    letter-spacing: -0.03em;
    max-width: 900px;
}

.home-filter-overlap {
    position: relative;
    z-index: 5;
    margin-top: -90px;
}

@media (max-width: 991.98px) {
    .hero-section {
        min-height: 78vh;
        padding-bottom: 110px;
    }

    .hero-title {
        font-size: clamp(3rem, 8vw, 5rem);
        line-height: 1;
        max-width: 720px;
    }

    .home-filter-overlap {
        margin-top: -80px;
    }
}

@media (max-width: 576px) {
    .hero-section {
        min-height: 80vh;
        display: flex;
        align-items: flex-start;
        padding-bottom: 90px;
    }

    .hero-section .container {
        padding-left: 20px;
        padding-right: 20px;
        width: 100%;
    }

    .hero-title {
        font-size: clamp(2.8rem, 11vw, 4rem);
        line-height: 1.05;
        margin-bottom: 1rem;
        word-wrap: break-word;
        max-width: 100%;
    }

    .hero-section .lead {
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }

    .hero-section .scroll-indicator {
        bottom: 20px !important;
        transform: scale(0.8);
    }

    .home-filter-overlap {
        margin-top: -60px;
    }
}
</style>