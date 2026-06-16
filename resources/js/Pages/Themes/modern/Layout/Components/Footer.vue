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
    <div class="newsletter-section">
      <div class="container py-4 py-md-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-6">
            <h3 class="fw-bold mb-2 fs-4 fs-md-3">Stay Updated</h3>
            <p class="text-white-50 mb-0 small">
              Get the latest listings and automotive news delivered to your inbox.
            </p>
          </div>

          <div class="col-lg-6">
            <form class="newsletter-form">
              <div class="d-flex flex-column flex-sm-row gap-3">
                <input
                  type="email"
                  class="newsletter-input rounded-pill px-4 flex-grow-1"
                  placeholder="Enter your email"
                />
                <button
                  type="button"
                  class="btn btn-accent rounded-pill px-4 d-inline-flex align-items-center gap-2 fw-semibold"
                >
                  Subscribe
                  <ArrowRight size="16" />
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN FOOTER -->
    <div class="main-footer">
      <div class="container py-5">
        <div class="row g-4 g-md-5">

          <!-- LOGO & INFO -->
          <div class="col-12 col-md-5 col-lg-4">
            <Link href="/" class="fw-bold fs-3 text-white text-decoration-none d-inline-block mb-3">
              <img src="/assets/images/cropped-Veyogo-white.png" alt="Autosale.lk Logo" class="logo-img" />
            </Link>

            <p class="footer-text mb-4">
              Your trusted destination for buying and selling premium vehicles.
              Experience automotive excellence.
            </p>

            <ul class="list-unstyled footer-contact small">
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
            class="col-6 col-md-3 col-lg-2"
          >
            <h6 class="footer-heading fw-semibold mb-3">{{ category }}</h6>
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
    </div>

    <!-- BOTTOM BAR -->
    <div class="footer-bottom">
      <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
          <p class="small footer-copyright mb-0 text-center text-md-start">
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
/* ================= FOOTER BACKGROUND ================= */
.footer-bg {
  background: linear-gradient(135deg, #183B4E 0%, #27548A 100%);
}

/* ================= NEWSLETTER SECTION ================= */
.newsletter-section {
  background: rgba(221, 168, 83, 0.08);
  border-bottom: 1px solid rgba(221, 168, 83, 0.15);
}

.newsletter-input {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(221, 168, 83, 0.3);
  color: #FFFFFF;
  transition: all 0.3s ease;
}

.newsletter-input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.newsletter-input:focus {
  background: rgba(255, 255, 255, 0.15);
  border-color: #DDA853;
  box-shadow: 0 0 0 3px rgba(221, 168, 83, 0.2);
  outline: none;
}

.btn-accent {
  background: linear-gradient(90deg, #DDA853, #C19A4F);
  color: #183B4E;
  border: none;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-accent:hover {
  background: linear-gradient(90deg, #C19A4F, #DDA853);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(221, 168, 83, 0.3);
  color: #183B4E;
}

/* ================= MAIN FOOTER ================= */
.main-footer {
  background: #183B4E;
}

.footer-text {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
}

.footer-contact {
  color: rgba(255, 255, 255, 0.7);
}

.footer-contact svg {
  color: #DDA853;
}

.footer-heading {
  color: #DDA853;
  font-size: 1rem;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

/* ================= FOOTER LINKS ================= */
.footer-link {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: all 0.3s ease;
  position: relative;
  padding-left: 0;
  display: inline-block;
}

.footer-link:hover {
  color: #DDA853;
  padding-left: 8px;
}

.footer-link::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 2px;
  background: #DDA853;
  transition: width 0.3s ease;
}

.footer-link:hover::before {
  width: 6px;
}

/* ================= BOTTOM BAR ================= */
.footer-bottom {
  background: #0F2A3A;
  border-top: 1px solid rgba(221, 168, 83, 0.15);
}

.footer-copyright {
  color: rgba(255, 255, 255, 0.6);
}

/* ================= SOCIAL ICONS ================= */
.social-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.3s ease;
  text-decoration: none;
  border: 1px solid transparent;
}

.social-icon:hover {
  background: #DDA853;
  color: #183B4E;
  border-color: #DDA853;
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(221, 168, 83, 0.2);
}

/* ================= LOGO ================= */
.logo-img {
  height: 40px;
  filter: brightness(0) invert(1);
  transition: transform 0.3s ease;
}

.logo-img:hover {
  transform: scale(1.05);
}

/* ================= RESPONSIVE ================= */
@media (max-width: 768px) {
  .newsletter-section .container {
    padding-top: 3rem !important;
    padding-bottom: 3rem !important;
  }

  .main-footer .container {
    padding-top: 3rem !important;
  }

  .footer-heading {
    margin-top: 1.5rem;
  }
}
</style>
