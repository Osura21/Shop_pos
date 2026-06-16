<template>
  <MultiVendorLayout>

     <Banner
      title="About Autosale.lk"
  subtitle="A premium marketplace experience built for trust, clarity, and speed."
  image="/multivendor/black-luxury.webp"
    />
    <section class="au-hero">
      <div class="container px-4">
        <div class="au-hero__wrap">
          <div class="au-hero__left">
            <div class="au-pill">
              <i class="bi bi-info-circle"></i>
              About Us
            </div>

            <h1 class="au-h1">
              A better way to discover
              <span class="au-grad">trusted</span> vehicles.
            </h1>

            <p class="au-sub">
              We built Autosale.lk to make buying and selling vehicles feel simple, transparent, and premium.
              Clean listings, strong filters, and a marketplace that respects your time.
            </p>

            <div class="au-hero__cta">
              <Link class="au-btn au-btn--primary" :href="route('multivendor.vehicles.index')">
                Browse Cars
                <i class="bi bi-arrow-right"></i>
              </Link>

              <Link class="au-btn au-btn--ghost" href="/contact-us">
                Contact Us
              </Link>
            </div>

            <div class="au-metrics">
              <div class="au-metric">
                <div class="au-metric__v">Verified</div>
                <div class="au-metric__k">Seller-first quality</div>
              </div>
              <div class="au-metric">
                <div class="au-metric__v">Premium UI</div>
                <div class="au-metric__k">Modern experience</div>
              </div>
              <div class="au-metric">
                <div class="au-metric__v">Support</div>
                <div class="au-metric__k">Fast responses</div>
              </div>
            </div>
          </div>

          <div class="au-hero__right">
           <div class="au-collage au-reveal au-reveal--scale" style="transition-delay:.12s">
  <!-- BIG CARD -->
  <div class="au-collage__card au-collage__card--big au-float1 au-hoverlift">
    <div class="au-collage__badge">
      <i class="bi bi-shield-check"></i> Trusted Marketplace
    </div>

    <div class="au-big">
      <div class="au-big__t">Built for confident decisions</div>
      <div class="au-big__d">
        Verified listings, clean details, and a premium experience designed for speed.
      </div>

      <div class="au-big__chips">
        <span class="au-chip"><i class="bi bi-patch-check-fill"></i> Verified</span>
        <span class="au-chip"><i class="bi bi-funnel-fill"></i> Fast Filters</span>
        <span class="au-chip"><i class="bi bi-stars"></i> Premium UI</span>
      </div>
    </div>

    <div class="au-watermark">
      <i class="bi bi-car-front-fill"></i>
    </div>
  </div>

  <!-- ROW -->
  <div class="au-collage__row">
    <!-- MID LEFT CARD -->
    <div class="au-collage__card au-collage__card--mid au-float2 au-hoverlift">
      <div class="au-mid">
        <div class="au-mid__k">Speed</div>
        <div class="au-mid__v">Quick search</div>
        <div class="au-mid__d">Less scrolling, more finding.</div>
      </div>

      <div class="au-midIcon">
        <i class="bi bi-lightning-charge-fill"></i>
      </div>
    </div>

    <!-- MID RIGHT CARD -->
    <div class="au-collage__card au-collage__card--mid2 au-float3 au-hoverlift">
      <div class="au-collage__mini">
        <div class="au-mini__k">Focus</div>
        <div class="au-mini__v">Clear listings</div>
      </div>

      <div class="au-collage__mini">
        <div class="au-mini__k">Goal</div>
        <div class="au-mini__v">Confident buyers</div>
      </div>

      <div class="au-collage__mini">
        <div class="au-mini__k">Standard</div>
        <div class="au-mini__v">Quality first</div>
      </div>
    </div>
  </div>

  <div class="au-collage__glow"></div>
</div>

          </div>
        </div>
      </div>
    </section>

    <!-- TRUST STRIP -->
    <section class="au-strip">
      <div class="container px-4">
        <div class="au-strip__grid">
          <div class="au-strip__item">
            <div class="au-strip__ic"><i class="bi bi-ui-checks-grid"></i></div>
            <div>
              <div class="au-strip__t">Consistent Listings</div>
              <div class="au-strip__d">Easy to scan, compare, and decide.</div>
            </div>
          </div>

          <div class="au-strip__item">
            <div class="au-strip__ic"><i class="bi bi-funnel"></i></div>
            <div>
              <div class="au-strip__t">Smart Filters</div>
              <div class="au-strip__d">Find what matters in seconds.</div>
            </div>
          </div>

          <div class="au-strip__item">
            <div class="au-strip__ic"><i class="bi bi-stars"></i></div>
            <div>
              <div class="au-strip__t">Premium Experience</div>
              <div class="au-strip__d">Modern design, fast interaction.</div>
            </div>
          </div>

          <div class="au-strip__item">
            <div class="au-strip__ic"><i class="bi bi-headset"></i></div>
            <div>
              <div class="au-strip__t">Real Support</div>
              <div class="au-strip__d">We help you move forward.</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- STORY + VALUES -->
    <section class="au-section">
      <div class="container px-4">
        <div class="row gy-5 align-items-start">
          <div class="col-lg-6">
            <div class="au-kicker">Our story</div>
            <h2 class="au-h2">Built to reduce confusion and increase trust.</h2>
            <p class="au-p">
              Most marketplaces overwhelm users with inconsistent data and cluttered layouts.
              Autosale.lk was created to raise the standard: clean cards, meaningful filters,
              and a buyer-friendly experience.
            </p>

            <div class="au-checks">
              <div class="au-check">
                <i class="bi bi-check2-circle"></i>
                Clear details and a consistent layout
              </div>
              <div class="au-check">
                <i class="bi bi-check2-circle"></i>
                Better discovery with fast filtering
              </div>
              <div class="au-check">
                <i class="bi bi-check2-circle"></i>
                Design that feels modern and premium
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="au-kicker">Our values</div>

            <div class="au-values">
              <div class="au-value">
                <div class="au-value__ic"><i class="bi bi-shield-lock"></i></div>
                <div>
                  <div class="au-value__t">Trust</div>
                  <div class="au-value__d">A marketplace where users feel confident.</div>
                </div>
              </div>

              <div class="au-value">
                <div class="au-value__ic"><i class="bi bi-eye"></i></div>
                <div>
                  <div class="au-value__t">Transparency</div>
                  <div class="au-value__d">Information should be clear and consistent.</div>
                </div>
              </div>

              <div class="au-value">
                <div class="au-value__ic"><i class="bi bi-lightning-charge"></i></div>
                <div>
                  <div class="au-value__t">Speed</div>
                  <div class="au-value__d">Less scrolling, faster decisions.</div>
                </div>
              </div>

              <div class="au-value">
                <div class="au-value__ic"><i class="bi bi-people"></i></div>
                <div>
                  <div class="au-value__t">Service</div>
                  <div class="au-value__d">Support that is quick and helpful.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- HOW IT WORKS -->
        <div class="au-how">
          <div class="au-how__head">
            <div class="au-kicker">How it works</div>
            <h2 class="au-h2">Simple steps. Better outcomes.</h2>
          </div>

          <div class="au-steps">
            <div class="au-step">
              <div class="au-step__n">1</div>
              <div class="au-step__t">Discover</div>
              <div class="au-step__d">Browse listings built for quick scanning.</div>
            </div>

            <div class="au-step">
              <div class="au-step__n">2</div>
              <div class="au-step__t">Filter</div>
              <div class="au-step__d">Narrow down with type, make, model and more.</div>
            </div>

            <div class="au-step">
              <div class="au-step__n">3</div>
              <div class="au-step__t">Compare</div>
              <div class="au-step__d">See consistent specs and pricing presentation.</div>
            </div>

            <div class="au-step">
              <div class="au-step__n">4</div>
              <div class="au-step__t">Contact</div>
              <div class="au-step__d">Reach out and move forward with confidence.</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="au-section au-section--muted">
      <div class="container px-4">
        <div class="row mb-4">
          <div class="col-lg-8">
            <div class="au-kicker">FAQ</div>
            <h2 class="au-h2">Common questions</h2>
            <p class="au-p">
              Clear answers to help you understand how Autosale.lk works.
            </p>
          </div>
        </div>

        <div class="au-faq">
          <details class="au-faq__item">
            <summary class="au-faq__q">
              What makes Autosale.lk different?
              <i class="bi bi-chevron-down"></i>
            </summary>
            <div class="au-faq__a">
              A cleaner experience: consistent listing layout, premium UI, and a strong focus on clarity.
            </div>
          </details>

          <details class="au-faq__item">
            <summary class="au-faq__q">
              Can I browse without signing in?
              <i class="bi bi-chevron-down"></i>
            </summary>
            <div class="au-faq__a">
              Yes — you can browse listings and view details. Sign-in helps with saving favorites and seller actions.
            </div>
          </details>

          <details class="au-faq__item">
            <summary class="au-faq__q">
              How do I contact a seller?
              <i class="bi bi-chevron-down"></i>
            </summary>
            <div class="au-faq__a">
              Open a vehicle page and use the available contact actions. If you need help, contact our support team.
            </div>
          </details>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="au-section">
      <div class="container px-4">
        <div class="au-cta">
          <div class="au-cta__left">
            <div class="au-kicker">Ready to explore?</div>
            <div class="au-cta__t">Find your next car with confidence.</div>
            <div class="au-cta__d">Browse curated listings and compare quickly using clean cards and filters.</div>
          </div>

          <div class="au-cta__right">
            <Link class="au-btn au-btn--primary" :href="route('multivendor.vehicles.index')">
              Browse Cars <i class="bi bi-arrow-right"></i>
            </Link>
            <Link class="au-btn au-btn--ghost" href="/contact-us">
              Contact Us
            </Link>
          </div>
        </div>
      </div>
    </section>

    <MarketplaceFooterSections />
  </MultiVendorLayout>
</template>

<script>
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue";
import { Link } from "@inertiajs/vue3";
import Banner from "./partials/Banner.vue";
import MarketplaceFooterSections from "../Components/MarketplaceFooterSections.vue";

export default {
  name: "AboutUs",
  components: { MultiVendorLayout, Link, Banner, MarketplaceFooterSections },
};
</script>

<style scoped>
/* ===== Collage (FIXED + Filled) ===== */
.au-collage{
  position: relative;
  width: 100%;
  height: 420px;
}

.au-collage__glow{
  position: absolute;
  inset: -60px;
  background:
    radial-gradient(280px 220px at 30% 25%, rgba(92,45,128,.22) 0%, rgba(0,0,0,0) 70%),
    radial-gradient(280px 220px at 80% 45%, rgba(51,46,120,.18) 0%, rgba(0,0,0,0) 70%);
  pointer-events: none;
  filter: blur(2px);
}

/* base card */
.au-collage__card{
  border-radius: 22px;
  border: 1px solid rgba(0,0,0,.08);
  box-shadow: 0 18px 45px rgba(0,0,0,.08);
  position: relative;
  overflow: hidden;
}

/* animated highlight (subtle) */
.au-collage__card::before{
  content: "";
  position: absolute;
  inset: -2px;
  background: linear-gradient(120deg,
    rgba(255,255,255,.10) 0%,
    rgba(255,255,255,0) 35%,
    rgba(255,255,255,.08) 55%,
    rgba(255,255,255,0) 75%);
  transform: translateX(-40%);
  animation: auSheen 6.5s ease-in-out infinite;
  pointer-events: none;
  opacity: .8;
}

@keyframes auSheen{
  0% { transform: translateX(-45%); opacity: .45; }
  50% { transform: translateX(25%); opacity: .75; }
  100% { transform: translateX(-45%); opacity: .45; }
}

@media (prefers-reduced-motion: reduce){
  .au-collage__card::before{ animation: none; }
}

/* BIG */
.au-collage__card--big{
  height: 240px;
  padding: 18px;
  background:
    radial-gradient(420px 260px at 15% 30%, rgba(255,255,255,.18) 0%, rgba(0,0,0,0) 60%),
    linear-gradient(135deg, #332e78, #5c2d80);
}

.au-collage__badge{
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 999px;
  font-weight: 950;
  font-size: 12px;
  color: #fff;
  background: rgba(0,0,0,.25);
  border: 1px solid rgba(255,255,255,.16);
  backdrop-filter: blur(6px);
  position: relative;
  z-index: 2;
}

.au-big{
  margin-top: 16px;
  position: relative;
  z-index: 2;
  color: #fff;
}

.au-big__t{
  font-weight: 950;
  font-size: 20px;
  line-height: 1.2;
}

.au-big__d{
  margin-top: 8px;
  font-weight: 800;
  font-size: 13px;
  line-height: 1.7;
  opacity: .9;
  max-width: 46ch;
}

.au-big__chips{
  margin-top: 12px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.au-chip{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 11px;
  color: #fff;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.16);
}

/* watermark icon */
.au-watermark{
  position: absolute;
  right: 16px;
  bottom: 12px;
  font-size: 86px;
  color: rgba(255,255,255,.14);
  transform: rotate(-6deg);
  pointer-events: none;
}

/* ROW */
.au-collage__row{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-top: 14px;
}

/* MID LEFT */
.au-collage__card--mid{
  height: 160px;
  padding: 16px;
  background:
    radial-gradient(260px 160px at 25% 30%, rgba(255,255,255,.14) 0%, rgba(0,0,0,0) 65%),
    linear-gradient(135deg, #111827 0%, #332e78 100%);
  color: #fff;
}

.au-mid{
  position: relative;
  z-index: 2;
  max-width: 22ch;
}

.au-mid__k{
  font-weight: 900;
  font-size: 12px;
  opacity: .85;
}

.au-mid__v{
  margin-top: 4px;
  font-weight: 950;
  font-size: 18px;
}

.au-mid__d{
  margin-top: 6px;
  font-weight: 800;
  font-size: 12px;
  opacity: .85;
  line-height: 1.6;
}

.au-midIcon{
  position: absolute;
  right: 14px;
  bottom: 12px;
  font-size: 34px;
  color: rgba(255,255,255,.20);
}

/* MID RIGHT (IMPORTANT FIX: make 3 minis always fit) */
.au-collage__card--mid2{
  height: 160px;
  padding: 12px;
  background:
    radial-gradient(220px 140px at 20% 20%, rgba(255,255,255,.16) 0%, rgba(0,0,0,0) 65%),
    linear-gradient(135deg, #5c2d80 0%, #332e78 100%);
  display: flex;             /* <--- changed from grid to flex */
  flex-direction: column;
  gap: 10px;
}

.au-collage__mini{
  border-radius: 16px;
  padding: 10px 12px;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.14);
  color: #fff;
  flex: 1;                   /* <--- makes all 3 fit evenly */
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.au-mini__k{
  font-size: 11px;
  font-weight: 900;
  opacity: .80;
}
.au-mini__v{
  font-weight: 950;
  margin-top: 2px;
}

/* responsive */
@media (max-width: 992px){
  .au-collage{ height: 360px; }
}
@media (max-width: 576px){
  .au-collage__row{ grid-template-columns: 1fr; }
  .au-collage{ height: auto; }
  .au-collage__card--big{ height: 230px; }
  .au-collage__card--mid,
  .au-collage__card--mid2{ height: 160px; }
}
.au-hero {
  position: relative;
  padding: 86px 0 52px;
  background:
    radial-gradient(900px 380px at 15% 20%, rgba(92,45,128,.16) 0%, rgba(0,0,0,0) 55%),
    radial-gradient(900px 380px at 85% 10%, rgba(51,46,120,.14) 0%, rgba(0,0,0,0) 55%),
    linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
  overflow: hidden;
}

.au-hero__wrap {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 26px;
  align-items: center;
}

.au-pill {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 999px;
  font-weight: 950;
  font-size: 12px;
  color: #5c2d80;
  background: rgba(92,45,128,.10);
  border: 1px solid rgba(92,45,128,.14);
}

.au-h1 {
  margin-top: 14px;
  font-weight: 950;
  font-size: 54px;
  line-height: 1.05;
  color: #111827;
}

.au-grad {
  background: linear-gradient(135deg, #332e78, #5c2d80);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.au-sub {
  margin-top: 16px;
  font-weight: 700;
  color: #4b5563;
  line-height: 1.8;
  font-size: 15px;
  max-width: 56ch;
}

.au-hero__cta {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 18px;
}

.au-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border-radius: 999px;
  font-weight: 950;
  padding: 12px 16px;
  text-decoration: none;
  border: 1px solid rgba(0,0,0,.10);
  color: #111827;
  background: #fff;
}

.au-btn--primary {
  border: 0;
  color: #fff;
  background: linear-gradient(135deg, #332e78, #5c2d80);
}

.au-btn--ghost {
  background: rgba(255,255,255,.75);
}

.au-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 20px;
}

.au-metric {
  border-radius: 16px;
  border: 1px solid rgba(0,0,0,.08);
  background: rgba(255,255,255,.75);
  padding: 12px 14px;
}

.au-metric__v {
  font-weight: 950;
  color: #111827;
}

.au-metric__k {
  margin-top: 3px;
  font-weight: 800;
  font-size: 12px;
  color: #6b7280;
}

/* right collage */
.au-collage {
  position: relative;
  width: 100%;
  height: 420px;
}

.au-collage__glow {
  position: absolute;
  inset: -60px;
  background:
    radial-gradient(280px 220px at 30% 25%, rgba(92,45,128,.22) 0%, rgba(0,0,0,0) 70%),
    radial-gradient(280px 220px at 80% 45%, rgba(51,46,120,.18) 0%, rgba(0,0,0,0) 70%);
  pointer-events: none;
}

.au-collage__card {
  border-radius: 22px;
  border: 1px solid rgba(0,0,0,.08);
  box-shadow: 0 18px 45px rgba(0,0,0,.08);
  background:
    linear-gradient(135deg, rgba(51,46,120,.95), rgba(92,45,128,.95));
  position: relative;
  overflow: hidden;
}

.au-collage__card--big {
  height: 240px;
  padding: 18px;
  background:
    radial-gradient(400px 240px at 15% 25%, rgba(255,255,255,.20) 0%, rgba(0,0,0,0) 60%),
    linear-gradient(135deg, #332e78, #5c2d80);
}

.au-collage__badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 999px;
  font-weight: 950;
  font-size: 12px;
  color: #fff;
  background: rgba(0,0,0,.25);
  border: 1px solid rgba(255,255,255,.16);
}

.au-collage__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-top: 14px;
}

.au-collage__card--mid {
  height: 160px;
  background:
    radial-gradient(220px 140px at 25% 25%, rgba(255,255,255,.20) 0%, rgba(0,0,0,0) 65%),
    linear-gradient(135deg, #111827 0%, #332e78 100%);
}

.au-collage__card--mid2 {
  height: 160px;
  padding: 14px;
  background:
    radial-gradient(220px 140px at 20% 20%, rgba(255,255,255,.18) 0%, rgba(0,0,0,0) 65%),
    linear-gradient(135deg, #5c2d80 0%, #332e78 100%);
  display: grid;
  gap: 10px;
}

.au-collage__mini {
  border-radius: 16px;
  padding: 10px 12px;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.14);
  color: #fff;
}

.au-mini__k {
  font-size: 11px;
  font-weight: 900;
  opacity: .80;
}
.au-mini__v {
  font-weight: 950;
  margin-top: 2px;
}

/* ===== STRIP ===== */
.au-strip {
  padding: 18px 0;
  background: #ffffff;
}

.au-strip__grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.au-strip__item {
  border-radius: 18px;
  border: 1px solid rgba(0,0,0,.08);
  background: #fff;
  padding: 14px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  box-shadow: 0 10px 24px rgba(0,0,0,.05);
}

.au-strip__ic {
  width: 42px;
  height: 42px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: rgba(92,45,128,.10);
  color: #5c2d80;
  font-size: 16px;
  flex: 0 0 auto;
}

.au-strip__t {
  font-weight: 950;
  color: #111827;
}

.au-strip__d {
  margin-top: 3px;
  font-weight: 700;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.6;
}

/* ===== SECTION ===== */
.au-section {
  padding: 70px 0;
  background: #fff;
}
.au-section--muted {
  background: #f7f7fb;
}

.au-kicker {
  display: inline-flex;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(92,45,128,.10);
  color: #5c2d80;
  font-weight: 950;
  font-size: 12px;
  border: 1px solid rgba(92,45,128,.14);
  margin-bottom: 12px;
}

.au-h2 {
  font-weight: 950;
  font-size: 38px;
  color: #111827;
  margin: 0 0 12px;
}

.au-p {
  font-weight: 700;
  color: #4b5563;
  font-size: 15px;
  line-height: 1.85;
  margin: 0;
}

/* checks */
.au-checks {
  margin-top: 14px;
  display: grid;
  gap: 10px;
}
.au-check {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  color: #111827;
}
.au-check i {
  color: #16a34a;
}

/* values list */
.au-values {
  display: grid;
  gap: 12px;
}
.au-value {
  border-radius: 18px;
  border: 1px solid rgba(0,0,0,.08);
  background: #fff;
  padding: 16px;
  box-shadow: 0 10px 24px rgba(0,0,0,.05);
  display: flex;
  gap: 12px;
}
.au-value__ic {
  width: 44px;
  height: 44px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: rgba(92,45,128,.10);
  color: #5c2d80;
  font-size: 18px;
  flex: 0 0 auto;
}
.au-value__t {
  font-weight: 950;
  color: #111827;
}
.au-value__d {
  margin-top: 3px;
  font-weight: 700;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.6;
}

/* how it works */
.au-how {
  margin-top: 48px;
}
.au-how__head {
  margin-bottom: 14px;
}
.au-steps {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}
.au-step {
  border-radius: 18px;
  border: 1px solid rgba(0,0,0,.08);
  background: #fff;
  box-shadow: 0 10px 24px rgba(0,0,0,.05);
  padding: 16px;
}
.au-step__n {
  width: 38px;
  height: 38px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
  font-weight: 950;
}
.au-step__t {
  margin-top: 10px;
  font-weight: 950;
  color: #111827;
}
.au-step__d {
  margin-top: 6px;
  font-weight: 700;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.65;
}

/* FAQ */
.au-faq {
  display: grid;
  gap: 12px;
}
.au-faq__item {
  border-radius: 18px;
  border: 1px solid rgba(0,0,0,.08);
  background: #fff;
  padding: 14px 16px;
  box-shadow: 0 10px 24px rgba(0,0,0,.05);
}
.au-faq__q {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  cursor: pointer;
  font-weight: 950;
  color: #111827;
  list-style: none;
}
.au-faq__q::-webkit-details-marker { display: none; }
.au-faq__q i {
  transition: transform .18s ease;
}
.au-faq__item[open] .au-faq__q i {
  transform: rotate(180deg);
}
.au-faq__a {
  margin-top: 10px;
  font-weight: 700;
  color: #6b7280;
  line-height: 1.75;
  font-size: 14px;
}

/* CTA */
.au-cta {
  border-radius: 26px;
  border: 1px solid rgba(0,0,0,.08);
  background:
    radial-gradient(900px 380px at 15% 20%, rgba(92,45,128,.16) 0%, rgba(0,0,0,0) 55%),
    linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
  box-shadow: 0 18px 45px rgba(0,0,0,.06);
  padding: 22px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
}
.au-cta__t {
  font-weight: 950;
  font-size: 22px;
  color: #111827;
}
.au-cta__d {
  margin-top: 6px;
  font-weight: 700;
  color: #6b7280;
}
.au-cta__right {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .au-hero__wrap {
    grid-template-columns: 1fr;
  }
  .au-h1 {
    font-size: 40px;
  }
  .au-metrics {
    grid-template-columns: 1fr;
  }
  .au-collage {
    height: 360px;
  }
  .au-strip__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .au-steps {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .au-h2 {
    font-size: 30px;
  }
  .au-cta {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 576px) {
  .au-strip__grid {
    grid-template-columns: 1fr;
  }
  .au-steps {
    grid-template-columns: 1fr;
  }
}
</style>
