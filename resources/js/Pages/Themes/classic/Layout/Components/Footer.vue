<script setup>
import { Link } from "@inertiajs/vue3"
import {
  ArrowRight,
  Mail,
  Phone,
  MapPin,
  Facebook,
  Twitter,
  Instagram,
  Youtube,
} from "lucide-vue-next"

/* ================= DATA ================= */

const footerLinks = {
  "Buy Cars": [
    { name: "Browse All Cars", href: "#" },
    { name: "Certified Pre-Owned", href: "#" },
    { name: "New Arrivals", href: "#" },
    { name: "Electric Vehicles", href: "#" },
    { name: "Financing Options", href: "#" },
  ],
  "Sell Cars": [
    { name: "Get Instant Quote", href: "#" },
    { name: "How It Works", href: "#" },
    { name: "Dealer Partnerships", href: "#" },
    { name: "Trade-In Value", href: "#" },
  ],
  Company: [
    { name: "About Us", href: "#" },
    { name: "Careers", href: "#" },
    { name: "Press", href: "#" },
    { name: "Blog", href: "#" },
    { name: "Contact", href: "#" },
  ],
  Support: [
    { name: "Help Center", href: "#" },
    { name: "FAQ", href: "#" },
    { name: "Privacy Policy", href: "#" },
    { name: "Terms of Service", href: "#" },
  ],
}

const socialLinks = [
  { icon: Facebook, href: "#", label: "Facebook" },
  { icon: Twitter, href: "#", label: "Twitter" },
  { icon: Instagram, href: "#", label: "Instagram" },
  { icon: Youtube, href: "#", label: "YouTube" },
]
</script>

<template>
  <footer id="footer" class="footer-bg text-white">

    <!-- NEWSLETTER -->
    <div class="border-bottom border-light border-opacity-10">
      <div class="container py-5">
        <div class="row align-items-center g-4">
          <div class="col-md-6">
            <h3 class="fw-bold mb-2">Stay Updated</h3>
            <p class="text-white-50 mb-0">
              Get the latest listings and automotive news delivered to your inbox.
            </p>
          </div>

          <div class="col-md-6">
            <form class="d-flex flex-column flex-sm-row gap-3">
              <input
                type="email"
                class="form-control rounded-pill px-4 bg-white bg-opacity-10 border border-light border-opacity-25 text-white"
                placeholder="Enter your email"
              />
              <button
                type="button"
                class="btn btn-accent rounded-pill px-4 d-inline-flex align-items-center gap-2 fw-semibold"
              >
                Subscribe
                <ArrowRight size="16" />
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN FOOTER -->
    <div class="container py-5">
      <div class="row g-4">

        <!-- LOGO & INFO -->
        <div class="col-12 col-md-4">
          <Link href="/" class="fw-bold fs-3 text-white text-decoration-none d-inline-block mb-3">
           <img src="/assets/images/cropped-Veyogo-white.png" alt="Autosale.lk Logo" class="logo-img" />
          </Link>

          <p class="text-white-50 mb-4" style="max-width: 320px">
            Your trusted destination for buying and selling premium vehicles.
            Experience automotive excellence.
          </p>

          <ul class="list-unstyled text-white-50 small">
            <li class="d-flex align-items-center gap-2 mb-2">
              <Phone size="16" />
              <span>0777 555 333</span>
            </li>
            <li class="d-flex align-items-center gap-2 mb-2">
              <Mail size="16" />
              <span>info@autosale.lk</span>
            </li>
            <li class="d-flex align-items-center gap-2">
              <MapPin size="16" />
              <span>207/23 Srimath Anagarika Dharmapala Mawatha, Colombo</span>
            </li>
          </ul>
        </div>

        <!-- LINKS -->
        <div
          v-for="(links, category) in footerLinks"
          :key="category"
          class="col-6 col-md-2"
        >
          <h6 class="fw-semibold mb-3">{{ category }}</h6>
          <ul class="list-unstyled">
            <li
              v-for="link in links"
              :key="link.name"
              class="mb-2"
            >
              <a
                :href="link.href"
                class="footer-link"
              >
                {{ link.name }}
              </a>
            </li>
          </ul>
        </div>

      </div>
    </div>

    <!-- BOTTOM BAR -->
    <div class="border-top border-light border-opacity-10">
      <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
          <p class="small text-white-50 mb-0">
            © 2026 Autosale.lk. All rights reserved.
          </p>

          <div class="d-flex gap-3">
            <a
              v-for="social in socialLinks"
              :key="social.label"
              :href="social.href"
              :aria-label="social.label"
              class="social-icon"
            >
              <component :is="social.icon" size="18" />
            </a>
          </div>
        </div>
      </div>
    </div>

  </footer>
</template>

<style scoped>
/* ================= LINKS ================= */
.footer-link {
  font-size: 0.875rem;
  color: rgba(255,255,255,.65);
  text-decoration: none;
  transition: color .3s ease;
}

.footer-link:hover {
  color: #fff;
}

/* ================= SOCIAL ================= */
.social-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255,255,255,.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,.7);
  transition: all .3s ease;
  text-decoration: none;
}

.social-icon:hover {
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
}

.btn-accent {
  background: var(--bs-warning);
  color: #000;
  border: none;
}

.btn-accent:hover {
  background: #ffc107cc;
}
</style>
