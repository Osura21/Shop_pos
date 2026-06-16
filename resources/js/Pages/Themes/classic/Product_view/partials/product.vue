<!--Themes/classic/Product_view/partials/product.vue-->

<template>
    <section class="pv-wrap container">
        <div class="pv-shell">

             <nav class="pv-breadcrumb" aria-label="Breadcrumb">
          <Link class="pv-breadcrumb__link" :href="route('vendorsite.vehicles.index')">Cars</Link>

            <span class="pv-breadcrumb__sep">
                <i class="fa-solid fa-chevron-right"></i>
            </span>

            <Link class="pv-breadcrumb__link" :href="route('multivendor.vehicles.index')">Cars</Link>

            <template v-if="vehicle?.manufacture?.title">
                <span class="pv-breadcrumb__sep">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>

                <span class="pv-breadcrumb__link pv-breadcrumb__muted">
                    {{ vehicle.manufacture.title }}  {{ vehicle.vehicle_model.title }}
                </span>
            </template>

            <!-- <template v-if="vehicle?.vehicle_model?.title">
                <span class="pv-breadcrumb__sep">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>

                <span class="pv-breadcrumb__link pv-breadcrumb__muted">
                    {{ vehicle.vehicle_model.title }}
                </span>
            </template> -->

          

           
        </nav>
            <!-- TITLE + PILLS -->
            <div class="pv-top">
                <div class="pv-top__left">
                    <h2 class="pv-title">
                        {{ vehicle?.manufacture?.title || "—" }}
                        {{ vehicle?.vehicle_model?.title || "" }}
                    </h2>

                    <div class="pv-pills">
                        <span class="pv-pill pv-pill--dark">
                            <i class="fa-solid fa-calendar-days"></i>
                            {{ vehicle?.year ?? "—" }}
                        </span>

                        <span class="pv-pill pv-pill--dark">
                            <i class="fa-solid fa-road"></i>
                            {{ vehicle?.mileage ? `${vehicle.mileage} Miles` : "N/A Miles" }}
                        </span>

                        <span class="pv-pill pv-pill--dark">
                            <i class="fa-solid fa-gears"></i>
                            {{ transmissionLabel(vehicle?.transmission) }}
                        </span>

                        <span class="pv-pill pv-pill--dark">
                            <i class="fa-solid fa-gas-pump"></i>
                            {{ fuelLabel(vehicle?.fuel_type) }}
                        </span>

                        <span class="pv-pill" :class="availabilityPillClass(vehicle?.availability)">
                            <i class="fa-solid fa-circle"></i>
                            {{ availabilityLabel(vehicle?.availability) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- IMAGES -->
            <div class="pv-images">
                <!-- Main image -->
                <div class="pv-mainImg" @click="openLightbox(0)">
                    <div v-if="!mainLoaded" class="pv-skel"></div>
                    <img class="pv-mainImg__img" :class="{ ready: mainLoaded }"
                        :src="allImages[activeIndex]?.original_url || fallbackImage" alt="vehicle"
                        @load="mainLoaded = true" />

                   <div class="pv-photoCount" v-if="allImages.length">
    <i class="fa-solid fa-images"></i>
    {{ allImages.length }}
</div>
                </div>

                <!-- Thumbs -->
                <div class="pv-thumbs">
                    <button v-for="(img, i) in thumbImages" :key="img.id || i" type="button" class="pv-thumb"
                        @click="setActive(i + 1)">
                        <div v-if="!thumbLoaded[i]" class="pv-skel"></div>
                        <img class="pv-thumb__img" :class="{ ready: thumbLoaded[i] }" :src="img.original_url"
                            alt="thumb" @load="setThumbLoaded(i)" />

                        <!-- last thumb overlay -->
                        <div v-if="i === 3 && allImages.length > 5" class="pv-more" @click.stop="openGrid">

                            <i class="fa-solid fa-images"></i>
                            <span>More Images</span>
                        </div>
                    </button>
                </div>
            </div>
            <GalleryGrid :visible="gridOpen" :images="allImages"
                :categories="['All', ...new Set((allImages || []).map(i => i?.custom_properties?.category).filter(Boolean))]"
                @close="gridOpen = false" @select="openLightbox" />






            <div class="pv-summaryRow">
                <!-- LEFT -->
                <div class="pv-summaryCol">

                    <div class="pv-priceBar">
                        <div class="pv-priceBar__left">
                            <div class="pv-priceBar__label">Price</div>

                            <div class="pv-priceBar__value">
                                {{ priceDisplay }}
                                <span v-if="isNegotiable" class="pv-priceBar__neg">Negotiable</span>
                            </div>
                        </div>

                        <div class="pv-priceBar__right">
                            <i class="fa-solid fa-location-dot"></i>
                            <span class="pv-location">
                                {{ vehicle?.city?.name || "—" }}
                            </span>
                        </div>
                    </div>

                    <div class="pv-summary2">
                        <div class="pv-summary2__head">
                            <h3 class="pv-summary2__title">Vehicle summary</h3>

                            <span class="pv-summary2__badge" :class="availabilityBadgeClass(vehicle?.availability)">
                                <i class="fa-solid fa-circle"></i>
                                {{ availabilityLabel(vehicle?.availability) }}
                            </span>
                        </div>

                        <div class="pv-summary2__grid">
                            <div v-for="(item, idx) in summaryItems" :key="idx" class="pv-sumCard">
                                <div class="pv-sumCard__icon">
                                   <i :class="['fa-solid', item.icon]"></i>
                                </div>

                                <div class="pv-sumCard__meta">
                                    <div class="pv-sumCard__label">{{ item.label }}</div>
                                    <div class="pv-sumCard__value" :class="item.valueClass || ''">
                                        {{ item.value }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="pv-desc pv-desc__box">
                        <h3 class="pv-h3">Description</h3>
                        <div class="" v-html="vehicle?.editorContent || ''"></div>
                    </div>
                </div>

                <!-- RIGHT -->
                <aside class="pv-seller">
                    <div class="pv-seller__head">
                        <h3 class="pv-seller__title">Seller details</h3>
                    </div>

                    <div class="pv-seller__body">
                      <div class="pv-seller__item">
    <div class="k">Name</div>
    <div class="v">{{ seller?.name || "—" }}</div>
</div>

<div class="pv-seller__item">
    <div class="k">Status</div>
    <div class="v">{{ seller?.status || "Seller" }}</div>
</div>

                        <div class="pv-seller__item">
                            <div class="k">Phone</div>

                            <div class="v" v-if="sellerPhones.length">
                                <div v-for="(p, i) in sellerPhones" :key="i"
                                    style="display:flex; gap:8px; align-items:center; margin-top:4px;">
                                    <span>{{ p.phone }}</span>

                                    <span v-if="p.primary" style="font-size:11px; font-weight:900; color:#6b7280;">
                                        (Primary)
                                    </span>

                                    <span v-else-if="p.verified"
                                        style="font-size:11px; font-weight:900; color:#10b981;">
                                        ✓ Verified
                                    </span>

                                    <span v-else style="font-size:11px; font-weight:900; color:#f59e0b;">
                                        Not verified
                                    </span>
                                </div>
                            </div>

                            <div class="v" v-else>—</div>
                        </div>

                        <!-- <div class="pv-seller__item">
                            <div class="k">Email</div>
                            <div class="v">{{ vehicle?.customer?.email || "—" }}</div>
                        </div> -->

                        <!-- <div class="pv-seller__item">
                            <div class="k">Location</div>
                            <div class="v">{{ vehicle?.customer?.location || "—" }}</div>
                        </div> -->
                    </div>

                    <div class="pv-seller__ads" v-if="sellerAds.length">
                        <div class="pv-seller__adsTitle">Sponsored</div>

                        <a v-for="(ad, index) in sellerAds" :key="index" :href="ad.link" target="_blank"
                            class="pv-adCard">
                            <img :src="ad.image" alt="advertisement" />
                        </a>
                    </div>

                </aside>


            </div>




            <!-- SIMILAR (SAME CARD UI AS STOCK LIST) -->
            <div v-if="randomVehicles?.length" class="pv-similar">
                <h3 class="pv-h3">You might also be interested</h3>

                <div class="pv-similarGrid">
                    <Link v-for="rv in randomVehicles.slice(0, 4)" :key="rv.id" class="stock-card"
                        :href="detailUrl(rv)">
                    <!-- IMAGE -->
                    <div class="stock-card__media">
                        <img class="stock-card__img" :src="cardImage(rv)" alt="vehicle" />

                        <!-- availability top-left -->
                        <div class="stock-badge" :class="availabilityBadgeClass(rv.availability)">
                            {{ availabilityLabel(rv.availability) }}
                        </div>

                        <!-- photos count bottom-right -->
                        <div class="stock-photos" v-if="rv.media?.length">
                            <i class="fa-regular fa-images stock-photos__ic"></i>
                            {{ rv.media.length }}
                        </div>
                    </div>

                    <!-- BODY -->
                    <div class="stock-card__body">
                        <!-- header -->
                        <div class="stock-head">
                            <div class="stock-head__left">
                                <div class="stock-name">
                                    {{ (rv.manufacture?.title || '—') }} {{ (rv.vehicle_model?.title || '') }}
                                </div>
                                <div class="stock-year">{{ rv.year || '—' }}</div>
                            </div>

                            <div class="stock-head__right">
                                <div class="stock-cc">{{ rv.engine_capacity || '—' }}cc</div>
                                <div class="stock-cond" :class="badgeClass(rv.condition)">
                                    {{ (rv.condition || '—').toUpperCase() }}
                                </div>
                            </div>
                        </div>

                        <div class="stock-divider"></div>

                        <!-- specs -->
                        <div class="stock-icons">
                            <div class="sitem">
                                <i class="fa-solid fa-gas-pump sico"></i>
                                <span class="stxt">{{ fuelLabel(rv.fuel_type) }}</span>
                            </div>

                            <div class="sitem">
                                <i class="fa-solid fa-gears sico"></i>
                                <span class="stxt">{{ transmissionLabel(rv.transmission) }}</span>
                            </div>

                            <div class="sitem">
                                <i class="fa-solid fa-road sico"></i>
                                <span class="stxt stxt--nowrap">
                                    {{ rv.mileage ? `${rv.mileage} Miles` : 'N/A Miles' }}
                                </span>
                            </div>
                        </div>

                        <!-- footer -->
                        <div class="stock-footer">
                            <div class="stock-footer__price">
                                {{ money(rv.price || 0, rv.price || 'USD') }}
                            </div>

                            <span class="stock-footer__btn">
                                <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                    </Link>
                </div>

            </div>
        </div>

        <!-- LIGHTBOX -->
        <div v-if="lightboxOpen" class="pv-lb" @click.self="closeLightbox">
            <button class="pv-lb__close" type="button" @click="closeLightbox" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <button class="pv-lb__nav pv-lb__prev" type="button" @click="prev" aria-label="Previous">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <img class="pv-lb__img" :src="allImages[lbIndex]?.original_url" alt="preview" />

            <button class="pv-lb__nav pv-lb__next" type="button" @click="next" aria-label="Next">
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <div class="pv-lb__foot">
                {{ lbIndex + 1 }} / {{ allImages.length }}
            </div>
        </div>


    </section>
</template>

<script>
import { Link } from "@inertiajs/vue3";
import GalleryGrid from "./GalleryGrid.vue";

export default {
    name: "ProductDetails",
    components: { Link, GalleryGrid },

    props: {
        vehicle: { type: Object, default: null },
        seller: { type: Object, default: null },
        randomVehicles: { type: Array, default: () => [] },
        fallbackImage: { type: String, default: "/images/placeholder-car.png" },
        // sellerAds: { type: Array, default: () => [] },
    },

    data() {
        return {
            activeIndex: 0,
            mainLoaded: false,
            thumbLoaded: [false, false, false, false],
            lightboxOpen: false,
            lbIndex: 0,
            gridOpen: false,

            sellerAds: [
                {
                    image: "/assets/images/ads1.png",
                    link: "https://example.com"
                },

            ]
        };
    },

    computed: {
        sellerPhones() {
    if (this.seller?.phones?.length) {
        return this.seller.phones;
    }

    const c = this.vehicle?.customer || {};
    const out = [];

    if (c.phone) out.push({ phone: c.phone, primary: true });

    const extras = (c.phone_numbers || c.phoneNumbers || [])
        .map(p => ({
            phone: p.phone,
            verified: !!p.verified_at,
            primary: !!p.primary,
        }))
        .sort((a, b) => Number(b.verified) - Number(a.verified));

    extras.forEach(x => {
        if (!out.some(o => String(o.phone) === String(x.phone))) {
            out.push(x);
        }
    });

    return out;
},
        isNegotiable() {
            const v = this.vehicle || {};
            return (
                v.negotiable === 1 || v.negotiable === true ||
                v.is_negotiable === 1 || v.is_negotiable === true ||
                v.price_negotiable === 1 || v.price_negotiable === true
            );
        },
        priceDisplay() {
            const n = Number(this.vehicle?.price || 0);
            if (!n) return "Price on request";

            const prefix = "Rs ";
            return prefix + this.formatNumber(n);
        },
summaryItems() {
    const v = this.vehicle || {};

    const make = v?.manufacture?.title || "—";
    const model = v?.vehicle_model?.title || "—";
    const year = v?.year || "—";
    const vehicleType = v?.vehicle_type?.title || v?.vehicle_type?.name || "—";

    return [
    { icon: "fa-car-side", label: "Vehicle Name", value: `${make} ${model} : ${year}` },
    { icon: "fa-industry", label: "Make", value: make },
    { icon: "fa-car", label: "Vehicle Type", value: vehicleType },
    { icon: "fa-tag", label: "Condition", value: this.safe(v?.condition) },

    { icon: "fa-gas-pump", label: "Fuel", value: this.fuelLabel(v?.fuel_type) },
    { icon: "fa-gears", label: "Transmission", value: this.transmissionLabel(v?.transmission) },
    { icon: "fa-gauge-high", label: "Engine CC", value: this.safe(v?.engine_capacity, "—", "CC") },
    { icon: "fa-road", label: "Mileage", value: this.safe(v?.mileage, "N/A", "KM") },
];
},


        allImages() {
            const media = this.vehicle?.media || [];
            const main =
                media.find((m) => m.collection_name === "vehicle_main" || m.custom_properties?.type === "vehicle_main") ||
                null;

            const gallery = media.filter(
                (m) => m.collection_name === "vehicle_gallery" || m.custom_properties?.type === "vehicle_gallery"
            );

            const list = [];
            if (main) list.push(main);
            gallery.forEach((g) => list.push(g));

            // fallback: if nothing tagged, show whatever exists
            if (!list.length && media.length) return media;

            return list;
        },

        thumbImages() {
            return this.allImages.slice(1, 5); // 4 thumbs
        },
    },

    mounted() {
        window.addEventListener("keydown", this.onKey);
    },
    beforeUnmount() {
        window.removeEventListener("keydown", this.onKey);
    },

    methods: {
        formatNumber(n) {
            return new Intl.NumberFormat("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(n || 0));
        },
        openGrid() {
            this.gridOpen = true;
        },

        availabilityBadgeClass(val) {
            const n = Number(val);
            if (n === 0) return "is-green";
            if (n === 1) return "is-amber";
            if (n === 2) return "is-red";
            return "is-muted";
        },

        safe(val, fallback = "—", suffix = "") {
            if (val === null || val === undefined || String(val).trim() === "") return fallback;
            return suffix ? `${val}${suffix}` : String(val);
        },

        availabilityTextClass(val) {
            const n = Number(val);
            if (n === 0) return "pv-ok";
            if (n === 1) return "pv-warn";
            if (n === 2) return "pv-bad";
            return "";
        },

        setThumbLoaded(i) {
            this.thumbLoaded.splice(i, 1, true);
        },

        setActive(i) {
            if (this.activeIndex === i) return;
            this.activeIndex = i;
            this.mainLoaded = false;
        },
        openLightbox(i) {
            if (!this.allImages.length) return;
            this.lbIndex = Math.max(0, Math.min(i, this.allImages.length - 1));
            this.lightboxOpen = true;
        },

        closeLightbox() {
            this.lightboxOpen = false;
        },

        next() {
            this.lbIndex = (this.lbIndex + 1) % this.allImages.length;
        },

        prev() {
            this.lbIndex = (this.lbIndex - 1 + this.allImages.length) % this.allImages.length;
        },

        onKey(e) {
            if (!this.lightboxOpen) return;
            if (e.key === "Escape") this.closeLightbox();
            if (e.key === "ArrowRight") this.next();
            if (e.key === "ArrowLeft") this.prev();
        },

        money(amount, currency = "USD") {
            const n = Number(amount || 0);
            const sym = String(currency).toUpperCase() === "USD" ? "RS " : "LKR ";
            return sym + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        transmissionLabel(val) {
            const n = Number(val);
            if (n === 0) return "Auto";
            if (n === 1) return "Manual";
            if (n === 2) return "Triptonic";
            return "—";
        },

        fuelLabel(val) {
            const n = Number(val);
            if (n === 0) return "Diesel";
            if (n === 1) return "Petrol";
            if (n === 2) return "Hybrid";
            if (n === 3) return "Electric";
            return "—";
        },

        availabilityLabel(val) {
            const n = Number(val);
            if (n === 0) return "Available";
            if (n === 1) return "Arriving";
            if (n === 2) return "Sold";
            return "—";
        },

        availabilityClass(val) {
            const n = Number(val);
            if (n === 0) return "avail-green";
            if (n === 1) return "avail-amber";
            if (n === 2) return "avail-red";
            return "avail-muted";
        },

        availabilityPillClass(val) {
            const n = Number(val);
            if (n === 0) return "pv-pill--green";
            if (n === 1) return "pv-pill--amber";
            if (n === 2) return "pv-pill--red";
            return "pv-pill--muted";
        },

        badgeClass(condition) {
            const s = String(condition || "").toLowerCase();
            if (s.includes("new")) return "badge-green";
            if (s.includes("used")) return "badge-blue";
            return "badge-gray";
        },

        cardImage(v) {
            const media = v?.media || [];
            const main =
                media.find((m) => m.collection_name === "vehicle_main" || m.custom_properties?.type === "vehicle_main") ||
                media[0];
            return main?.original_url || this.fallbackImage;
        },

        slugify(str) {
            return String(str || "")
                .toLowerCase()
                .trim()
                .replace(/&/g, "and")
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/-+/g, "-")
                .replace(/(^-|-$)/g, "");
        },

       detailUrl(v) {
    if (v?.seo_url) {
        return route("vendorsite.product", { slug: v.seo_url });
    }

    return route("vendorsite.product", { slug: "ad" }) + "?id=" + v.id;
}

    },
};
</script>

<style scoped>
.pv-summary2 {
    margin-top: 26px;
    border-radius: 18px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: #fff;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
    padding: 18px 18px;
}

.pv-summary1 {
    margin-top: 6px;
    border-radius: 18px;
    padding: 18px 18px;
}

.pv-summary2__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    margin-bottom: 16px;
}

.pv-summary2__title {
    margin: 0;
    font-weight: 950;
    font-size: 22px;
    color: #111827;
}

.pv-summary2__badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 12px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: rgba(17, 24, 39, 0.04);
    color: #374151;
}

.pv-summary2__badge i {
    font-size: 10px;
}

.pv-summary2__badge.is-green {
    background: rgba(34, 197, 94, 0.12);
    color: #0f7a32;
    border-color: rgba(34, 197, 94, 0.22);
}

.pv-summary2__badge.is-amber {
    background: rgba(245, 158, 11, 0.12);
    color: #9a4d00;
    border-color: rgba(245, 158, 11, 0.22);
}

.pv-summary2__badge.is-red {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, 0.22);
}

.pv-summary2__badge.is-muted {
    background: rgba(17, 24, 39, 0.06);
    color: #6b7280;
    border-color: rgba(17, 24, 39, 0.10);
}

.pv-summary2__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

@media (max-width: 992px) {
    .pv-summary2__grid {
        grid-template-columns: 1fr;
    }
}

.pv-sumCard {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: #fbfbfd;
    padding: 14px 14px;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.pv-sumCard:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 22px rgba(0, 0, 0, 0.06);
}

.pv-sumCard__icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: rgba(92, 45, 128, 0.10);
    color: #5c2d80;
    flex: 0 0 auto;
}

.pv-sumCard__icon i {
    font-size: 16px;
}

.pv-sumCard__meta {
    min-width: 0;
    flex: 1;
}

.pv-sumCard__label {
    font-size: 12px;
    font-weight: 900;
    color: #6b7280;
    margin-bottom: 4px;
}

.pv-sumCard__value {
    font-size: 15px;
    font-weight: 950;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pv-wrap {
    background: #fff;
}

.pv-shell {
    max-width: 1400px;
    margin: 0 auto;
    padding: 18px 16px 42px;
}

/* top */
.pv-top {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
    align-items: start;
    margin-top: 14px;
}

.pv-title {
    font-size: 28px;
    font-weight: 950;
    margin: 0 0 10px;
    color: #111827;
}

.pv-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.pv-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 12px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: #fff;
    color: #111827;
}

.pv-pill--dark {
    background: linear-gradient(135deg, #332e78, #5c2d80);
    color: #fff;
    border-color: rgba(255, 255, 255, .12);
}

.pv-pill--green {
    background: rgba(34, 197, 94, .12);
    color: #0f7a32;
    border-color: rgba(34, 197, 94, .22);
}

.pv-pill--amber {
    background: rgba(245, 158, 11, .12);
    color: #9a4d00;
    border-color: rgba(245, 158, 11, .22);
}

.pv-pill--red {
    background: rgba(239, 68, 68, .12);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, .22);
}

.pv-pill--muted {
    background: rgba(17, 24, 39, .06);
    color: #6b7280;
    border-color: rgba(17, 24, 39, .10);
}

.pv-pill i {
    opacity: .95;
}

/* === IMAGES LAYOUT (FIXED) === */
.pv-images {
    margin-top: 18px;
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 12px;
    align-items: stretch;
}

@media (max-width: 992px) {
    .pv-images {
        grid-template-columns: 1fr;
    }
}

.pv-mainImg {
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: #eef2f7;
    cursor: pointer;

    aspect-ratio: 16 / 9;
}

/* Make the img truly fill the box */
.pv-mainImg__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;

    opacity: 0;
    transition: opacity 0.35s ease;
}

.pv-mainImg__img.ready {
    opacity: 1;
}

.pv-photoCount {
    position: absolute;
    right: 12px;
    bottom: 12px;
    background: rgba(0, 0, 0, .65);
    color: #fff;
    border-radius: 999px;
    padding: 8px 10px;
    font-weight: 900;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.pv-thumbs {
    align-self: stretch;
    height: 100%;
    min-height: 0;

    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

/* Each thumb cell fills its grid area */
.pv-thumb {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 0;

    border: 0;
    padding: 0;
    border-radius: 18px;
    overflow: hidden;
    background: #eef2f7;
    border: 1px solid rgba(0, 0, 0, 0.08);
    cursor: pointer;
}

.pv-thumb__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;

    opacity: 0;
    transition: opacity 0.35s ease;
}

.pv-thumb__img.ready {
    opacity: 1;
}

.pv-more {
    position: absolute;
    right: 10px;
    bottom: 10px;
    background: rgba(255, 255, 255, .92);
    color: #111827;
    border-radius: 999px;
    padding: 8px 10px;
    font-weight: 950;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(0, 0, 0, .08);
}

/* skeleton */
.pv-skel {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #e5e7eb 0%, #f3f4f6 50%, #e5e7eb 100%);
    background-size: 200% 100%;
    animation: pvShimmer 1.2s infinite;
}

@keyframes pvShimmer {
    0% {
        background-position: -200% 0;
    }

    100% {
        background-position: 200% 0;
    }
}

/* specs */
.pv-specGrid {
    margin-top: 18px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

@media (max-width: 992px) {
    .pv-specGrid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .pv-specGrid {
        grid-template-columns: 1fr;
    }
}

.pv-spec {
    border-radius: 18px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: #fff;
    box-shadow: 0 10px 22px rgba(0, 0, 0, .05);
    padding: 12px 12px;
}

.pv-spec__k {
    font-weight: 950;
    font-size: 12px;
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.pv-spec__v {
    margin-top: 6px;
    font-weight: 950;
    font-size: 14px;
    color: #111827;
}

/* description */
.pv-desc {
    margin-top: 18px;
}

.pv-h3 {
    font-weight: 950;
    font-size: 18px;
    color: #111827;
    margin: 0 0 10px;
}

.pv-desc__box {
    border-radius: 18px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: #fff;
    box-shadow: 0 10px 22px rgba(0, 0, 0, .05);
    padding: 14px 14px;
    color: #111827;
}

/* similar */
.pv-similar {
    margin-top: 22px;
}

.pv-similarGrid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

@media (max-width: 1200px) {
    .pv-similarGrid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .pv-similarGrid {
        grid-template-columns: 1fr;
    }
}

/* ===== SAME CARD STYLE AS STOCK PAGE ===== */
.stock-card {
    display: flex;
    flex-direction: column;
    border-radius: 15px;
    overflow: hidden;
    background: #fff;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
    transition: transform .18s ease, box-shadow .18s ease;
}

.stock-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.12);
}

/* image */
.stock-card__media {
    position: relative;
    height: 200px;
    background: #eef2f7;
}

.stock-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* availability badge top-left */
.stock-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 5;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 12px;
    color: #fff;
    backdrop-filter: blur(6px);
}

.stock-badge.is-green {
    background: rgba(34, 197, 94, 0.80);
}

.stock-badge.is-amber {
    background: rgba(245, 158, 11, 0.82);
}

.stock-badge.is-red {
    background: rgba(239, 68, 68, 0.82);
}

.stock-badge.is-muted {
    background: rgba(107, 114, 128, 0.70);
}

/* photos count */
.stock-photos {
    position: absolute;
    right: 12px;
    bottom: 12px;
    background: rgba(0, 0, 0, 0.65);
    color: #fff;
    font-weight: 800;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.stock-photos__ic {
    font-size: 12px;
    opacity: .95;
}

/* body layout */
.stock-card__body {
    padding: 14px 14px 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* head */
.stock-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.stock-head__left {
    min-width: 0;
}

.stock-name {
    font-weight: 950;
    font-size: 15px;
    color: #111827;
    line-height: 1.2;
    /* 2 lines max, NO "..." */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.stock-year {
    margin-top: 6px;
    font-weight: 800;
    font-size: 12px;
    color: #6b7280;
}

.stock-head__right {
    text-align: right;
    display: grid;
    gap: 6px;
    justify-items: end;
}

.stock-cc {
    font-weight: 950;
    font-size: 13px;
    color: #111827;
}

.stock-cond {
    font-weight: 950;
    font-size: 12px;
}

/* divider */
.stock-divider {
    height: 1px;
    background: rgba(0, 0, 0, 0.08);
    margin: 2px 0;
}

/* specs row */
.stock-icons {
    display: grid;
    grid-template-columns: 1fr 1fr 1.35fr;
    /* last col wider -> avoids Miles wrap */
    gap: 10px 12px;
    padding-top: 6px;
}

.sitem {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111827;
    min-width: 0;
}

.sico {
    width: 18px;
    display: inline-flex;
    justify-content: center;
    font-size: 14px;
    color: #111827;
}

.stxt {
    font-size: 11px;
    font-weight: 800;
    color: #111827;
    min-width: 0;
}

.stxt--nowrap {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* footer */
.stock-footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-top: 10px;
}

.stock-footer__price {
    font-weight: 950;
    font-size: 20px;
    color: #111827;
    line-height: 1;
}

.stock-footer__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 12px;
    font-weight: 950;
    font-size: 12px;
    background: linear-gradient(135deg, #332e78, #5c2d80);
    color: #fff;
    white-space: nowrap;
}

.stock-photos {
    position: absolute;
    right: 12px;
    bottom: 12px;
    background: rgba(0, 0, 0, 0.65);
    color: #fff;
    font-weight: 800;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.stock-photos__ic {
    font-size: 12px;
    opacity: .95;
}

.stock-card__body {
    padding: 14px 14px 12px;
}

.stock-top {
    display: grid;
    grid-template-columns: 1fr 130px;
    gap: 12px;
}

.stock-price__label {
    font-size: 11px;
    font-weight: 900;
    color: #6b7280;
    letter-spacing: .3px;
}

.stock-price__val {
    font-size: 22px;
    font-weight: 900;
    color: #111827;
    line-height: 1.1;
    margin-top: 4px;
}

.stock-price__sub {
    font-size: 12px;
    color: #6b7280;
    font-weight: 700;
    margin-top: 6px;
}

.stock-price__sub2 {
    font-size: 12px;
    color: #111827;
    font-weight: 800;
    margin-top: 3px;
}

.stock-kpis {
    display: grid;
    gap: 8px;
    align-content: start;
}

.kpi {
    display: grid;
    grid-template-columns: 1fr;
    justify-items: end;
}

.kpi__v {
    font-weight: 900;
    font-size: 13px;
    color: #111827;
}

.kpi__k {
    font-size: 11px;
    color: #6b7280;
    font-weight: 800;
}

.badge-green {
    color: #0f7a32;
    font-weight: 900;
}

.badge-blue {
    color: #0b5ed7;
    font-weight: 900;
}

.badge-gray {
    color: #6b7280;
    font-weight: 900;
}

.stock-divider {
    height: 1px;
    background: rgba(0, 0, 0, 0.08);
    margin: 12px 0;
}

.stock-title {
    font-weight: 900;
    font-size: 16px;
    color: #111827;
    margin-bottom: 6px;
}

.stock-availability {
    font-weight: 900;
    font-size: 13px;
    margin-bottom: 12px;
}

.avail-green {
    color: #0f7a32;
}

.avail-amber {
    color: #b45309;
}

.avail-red {
    color: #b91c1c;
}

.avail-muted {
    color: #6b7280;
}

.stock-icons {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px 12px;
    padding-top: 4px;
}

.sitem {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111827;
}

.sico {
    width: 18px;
    display: inline-flex;
    justify-content: center;
    font-size: 14px;
    color: #111827;
}

.stxt {
    font-size: 11px;
    font-weight: 800;
    color: #111827;
}

/* lightbox */
.pv-lb {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .86);
    z-index: 3000;
    display: grid;
    place-items: center;
    padding: 18px;
}

.pv-lb__img {
    max-width: min(1100px, 96vw);
    max-height: 86vh;
    object-fit: contain;
    border-radius: 14px;
}

.pv-lb__close {
    position: absolute;
    right: 16px;
    top: 16px;
    width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 0;
    background: rgba(255, 255, 255, .92);
    color: #111827;
    font-size: 18px;
    display: grid;
    place-items: center;
}

.pv-lb__nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 0;
    background: rgba(255, 255, 255, .92);
    color: #111827;
    font-size: 16px;
    display: grid;
    place-items: center;
}

.pv-lb__prev {
    left: 16px;
}

.pv-lb__next {
    right: 16px;
}

.pv-lb__foot {
    position: absolute;
    bottom: 18px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, .65);
    color: #fff;
    border-radius: 999px;
    padding: 8px 12px;
    font-weight: 950;
    font-size: 12px;
}

.pv-priceBar {
    border-radius: 18px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
    padding: 14px 16px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.pv-priceBar__label {
    font-size: 12px;
    font-weight: 900;
    color: #6b7280;
}

.pv-priceBar__value {
    font-size: 28px;
    font-weight: 950;
    color: #111827;
    line-height: 1.1;
    margin-top: 4px;
}

.pv-priceBar__note {
    font-size: 12px;
    font-weight: 900;
    color: #6b7280;
    text-align: right;
}

.pv-priceBar__left {
    display: flex;
    flex-direction: column;
}

.pv-priceBar__right {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 900;
    color: #6b7280;
    white-space: nowrap;
}

.pv-priceBar__right i {
    color: #5c2d80;
}

.pv-location {
    font-weight: 900;
}

.pv-summary2 {
    margin-top: 14px;
}

.pv-priceBar__neg {
    margin-left: 10px;
    font-size: 12px;
    font-weight: 800;
    font-style: italic;
    color: #6b7280;
    white-space: nowrap;
    vertical-align: middle;
}

.pv-summaryRow {
    margin-top: 34px;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 16px;
    align-items: stretch;
    /* make right side match left height */
}

@media (max-width: 992px) {
    .pv-summaryRow {
        grid-template-columns: 1fr;
    }
}

.pv-summary2 {
    margin-top: 0;
}

/* remove extra spacing inside row */

.pv-seller {
    height: 100%;
    border-radius: 18px;
    border: 1px solid rgba(0, 0, 0, .06);
    background: #fff;
    box-shadow: 0 10px 24px rgba(0, 0, 0, .05);
    padding: 16px;
    display: flex;
    flex-direction: column;
}

.pv-seller__title {
    margin: 0;
    font-weight: 950;
    font-size: 18px;
    color: #111827;
}

.pv-seller__body {
    margin-top: 12px;
    display: grid;
    gap: 10px;
}

.pv-seller__item .k {
    font-size: 12px;
    font-weight: 900;
    color: #6b7280;
}

.pv-seller__item .v {
    margin-top: 4px;
    font-size: 14px;
    font-weight: 950;
    color: #111827;
}

/* ===== Seller Ads Section ===== */

.pv-seller__ads {
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    display: grid;
    gap: 12px;
}

.pv-seller__adsTitle {
    font-size: 12px;
    font-weight: 900;
    color: #6b7280;
    letter-spacing: .4px;
}

.pv-adCard {
    display: block;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: #f9fafb;
    transition: transform .2s ease, box-shadow .2s ease;
}

.pv-adCard img {
    width: 100%;
    height: auto;
    display: block;
}

.pv-adCard:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
}

.pv-breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    font-weight: 800;
    color: #6b7280;
}

.pv-breadcrumb__link {
    color: #5c2d80;
    text-decoration: none;
    transition: color 0.18s ease;
}

.pv-breadcrumb__link:hover {
    color: #332e78;
    text-decoration: underline;
}

.pv-breadcrumb__muted {
    color: #6b7280;
    cursor: default;
    text-decoration: none;
}

.pv-breadcrumb__current {
    color: #111827;
    font-weight: 900;
}

.pv-breadcrumb__sep {
    color: #9ca3af;
    font-size: 10px;
    display: inline-flex;
    align-items: center;
}

@media (max-width: 576px) {
    .pv-breadcrumb {
        gap: 6px;
        font-size: 12px;
        margin-bottom: 14px;
    }

    .pv-breadcrumb__sep {
        font-size: 9px;
    }
}
</style>
