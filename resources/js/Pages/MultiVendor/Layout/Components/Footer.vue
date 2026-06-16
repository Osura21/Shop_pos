<script setup>
import { Link } from "@inertiajs/vue3"
import { Facebook, Instagram, Linkedin } from "lucide-vue-next"

const businessLinks = ["Features", "Pricing", "Franchise", "Delivery Network"]
const industryLinks = ["Restaurants", "Cloud Kitchens", "Cafes", "Bakeries"]
const socialLinks = [
    { icon: Instagram, label: "Instagram", href: "#" },
    { icon: Facebook, label: "Facebook", href: "#" },
    { icon: Linkedin, label: "LinkedIn", href: "#" },
]
</script>

<template>
    <footer class="sappy-footer">
        <div class="sappy-footer__inner">
            <section>
                <Link href="/" class="sappy-footer__logo">Sappy <strong>Eats</strong></Link>
                <p>We deliver smart food ordering and delivery tools to help food businesses grow and serve customers faster.</p>
            </section>

            <section>
                <h2>FOR BUSINESSES</h2>
                <a v-for="link in businessLinks" :key="link" href="/#features">{{ link }}</a>
            </section>

            <section>
                <h2>INDUSTRIES</h2>
                <a v-for="link in industryLinks" :key="link" href="/#restaurants">{{ link }}</a>
            </section>

            <section>
                <h2>FOLLOW US</h2>
                <div class="sappy-footer__socials">
                    <a v-for="social in socialLinks" :key="social.label" :href="social.href" :aria-label="social.label">
                        <component :is="social.icon" size="20" />
                    </a>
                </div>
            </section>
        </div>

        <div class="sappy-footer__bottom">
            <p>© Copyright Sappy Eats 2026</p>
            <div>
                <span>All Rights Reserved</span>
                <a href="#">Privacy Policy</a>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.sappy-footer {
    background: #f5f5f6;
    color: #111827;
    padding: 60px 0 28px;
}

.sappy-footer__inner,
.sappy-footer__bottom {
    width: min(100% - 48px, 1320px);
    margin: 0 auto;
}

.sappy-footer__inner {
    display: grid;
    grid-template-columns: 1.7fr 1fr 1fr 1fr;
    gap: 72px;
    padding-bottom: 54px;
    border-bottom: 1px solid #dfe3ea;
}

.sappy-footer__logo {
    display: inline-block;
    margin-bottom: 26px;
    color: #050505;
    text-decoration: none;
    font-size: 28px;
    font-weight: 900;
}

.sappy-footer__logo strong {
    color: #df1f2d;
}

.sappy-footer p,
.sappy-footer a,
.sappy-footer span {
    color: #657089;
}

.sappy-footer p {
    max-width: 320px;
    line-height: 1.7;
}

.sappy-footer h2 {
    margin: 0 0 18px;
    font-size: 14px;
    font-weight: 900;
}

.sappy-footer section > a {
    display: block;
    margin: 12px 0;
    text-decoration: none;
}

.sappy-footer__socials {
    display: flex;
    gap: 14px;
}

.sappy-footer__socials a {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: #fff;
    color: #050505;
    box-shadow: 0 12px 30px rgba(17, 24, 39, 0.06);
}

.sappy-footer__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding-top: 24px;
}

.sappy-footer__bottom p {
    margin: 0;
}

.sappy-footer__bottom div {
    display: flex;
    gap: 26px;
}

.sappy-footer__bottom a {
    text-decoration: none;
}

@media (max-width: 900px) {
    .sappy-footer__inner {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 36px;
    }
}

@media (max-width: 640px) {
    .sappy-footer__inner,
    .sappy-footer__bottom {
        width: min(100% - 32px, 1320px);
    }

    .sappy-footer__inner,
    .sappy-footer__bottom {
        grid-template-columns: 1fr;
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
