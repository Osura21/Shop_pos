<template>
  <section class="about-hero">
    <div class="about-hero__bg" :style="{ backgroundImage: `url('${image}')` }"></div>
    <div class="about-hero__overlay"></div>

    <div class="about-hero__content">
      <div class="container">
        <div class="about-hero__titleRow">
          <h1 class="about-hero__title">Account</h1>
          <div class="about-hero__line"></div>
        </div>

        <p class="about-hero__sub">
          {{ subtitle }}
        </p>


      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "AboutBanner",
  props: {
    title: { type: String, default: "Account" },
    subtitle: {
      type: String,
      default: "We build a marketplace that makes buying and selling vehicles simple, modern, and trusted.",
    },

    image: { type: String, default: "/multivendor/black-luxury.webp" },

    showStats: { type: Boolean, default: true },
    statsOneValue: { type: String, default: "Trusted" },
    statsOneLabel: { type: String, default: "Marketplace" },
    statsTwoValue: { type: String, default: "Modern" },
    statsTwoLabel: { type: String, default: "Experience" },
    statsThreeValue: { type: String, default: "Fast" },
    statsThreeLabel: { type: String, default: "Support" },
  },
};
</script>

<style scoped>
.about-hero{
  position: relative;
  width: 100%;
  height: clamp(240px, 38vw, 500px);
  overflow: hidden;
}

/* background image */
.about-hero__bg{
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  transform: scale(1.02);
}

/* overlay (premium purple tone) */
.about-hero__overlay{
  position: absolute;
  inset: 0;
  background:
    radial-gradient(900px 380px at 15% 20%, rgba(92,45,128,.55) 0%, rgba(0,0,0,0) 60%),
    linear-gradient(
      90deg,
      rgba(0,0,0,.70) 0%,
      rgba(0,0,0,.45) 35%,
      rgba(0,0,0,.15) 70%,
      rgba(0,0,0,0) 100%
    );
}

/* content */
.about-hero__content{
  position: absolute;
  left: 0;
  right: 0;
  bottom: 70px;
  z-index: 2;
  color: #fff;
}

.pv-container{
  max-width: 1320px;
  margin: 0 auto;
  padding: 0 24px;
}

@media (max-width: 992px){
  .about-hero__content{ bottom: 28px; }
  .pv-container{ padding: 0 16px; }
}

.about-hero__titleRow{
  display: inline-flex;
  flex-direction: column;
  gap: 10px;
}

.about-hero__title{
  margin: 0;
  font-weight: 950;
  letter-spacing: .2px;
  font-size: clamp(26px, 3.2vw, 46px);
  text-shadow: 0 6px 18px rgba(0,0,0,.35);
}

.about-hero__line{
  width: 260px;
  max-width: 10vw;
  height: 3px;
  border-radius: 999px;
  background: linear-gradient(135deg, #332e78, #da0cda);
  opacity: .95;
}

.about-hero__sub{
  margin: 10px 0 0;
  font-size: 14px;
  opacity: .92;
  max-width: 560px;
  line-height: 1.7;
}

/* stats */
.about-hero__stats{
  margin-top: 14px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.stat{
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 14px;
  padding: 10px 12px;
  min-width: 120px;
  backdrop-filter: blur(6px);
}

.stat__num{
  font-weight: 950;
  font-size: 14px;
}

.stat__lbl{
  font-size: 12px;
  opacity: .86;
  font-weight: 800;
}

@media (max-width: 576px){
  .about-hero__line{ width: 180px; }
}
</style>
