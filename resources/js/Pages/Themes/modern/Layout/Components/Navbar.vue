<template>
  <header
    class="navbar-wrapper"
    :class="{ 'navbar-scrolled': isScrolled }"
  >
    <nav class="navbar-container">

      <Link href="/" class="navbar-logo">
        <img
          :src="isScrolled ? '/assets/images/logo-1.png' : '/assets/images/logo-2.png'"
          alt="Autosale.lk Logo"
          class="logo-image"
        />
      </Link>

      <div class="navbar-desktop">
        <Link
          v-for="link in navLinks"
          :key="link.name"
          :href="link.href"
          class="nav-link"
          :class="{
            'nav-link-active': isActive(link),
            'nav-link-light': !isScrolled
          }"
        >
          <span class="nav-link-text">{{ link.name }}</span>
          <span class="nav-link-underline"></span>
        </Link>
      </div>

      <div class="navbar-actions">

        <a
          href="tel:+18888888888"
          class="phone-link"
          :class="{ 'phone-link-dark': isScrolled }"
        >
          <PhoneIcon size="18" />
          <span>(888) 888-8888</span>
        </a>

        <Link
          :href="('/vehicles')"
          class="btn-primary"
        >
          Buy Your Car
        </Link>
      </div>

      <button
        class="mobile-toggle"
        @click="isMobileMenuOpen = !isMobileMenuOpen"
        :class="{ 'toggle-scrolled': isScrolled }"
        aria-label="Toggle mobile menu"
      >
        <X v-if="isMobileMenuOpen" size="24" :color="isScrolled ? '#183B4E' : '#FFFFFF'" />
        <Menu v-else size="24" :color="isScrolled ? '#183B4E' : '#FFFFFF'" />
      </button>
    </nav>

    <div class="d-lg-none mobile-offcanvas bg-white shadow-lg"
      :class="{ 'mobile-offcanvas--open': isMobileMenuOpen }">

      <!-- Offcanvas Header -->
      <div class="mobile-offcanvas__header">
        <h6 class="mobile-offcanvas__title">Menu</h6>
        <button type="button" class="mobile-offcanvas__close" @click="isMobileMenuOpen = false"
          aria-label="Close menu">
          <X size="20" />
        </button>
      </div>

      <!-- Offcanvas Body -->
      <div class="mobile-offcanvas__body">
        <div class="container py-4 d-flex flex-column gap-3">
          <Link
            v-for="link in navLinks"
            :key="link.name"
            :href="link.href"
            class="mobile-offcanvas__link fw-medium text-decoration-none"
            :class="{ 'mobile-offcanvas__link--active': isActive(link) }"
            @click="isMobileMenuOpen = false"
          >
            {{ link.name }}
          </Link>
          <a
            href="tel:+18888888888"
            class="mobile-offcanvas__phone-link"
          >
            <PhoneIcon size="20" />
            <span class="mobile-phone-number">(888) 888-8888</span>
          </a>
          <Link
            :href="('/vehicles')"
            class="mobile-offcanvas__btn"
            @click="isMobileMenuOpen = false"
          >
            Buy Your Car
          </Link>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue"
import { Link, usePage } from "@inertiajs/vue3"
import { X, Menu, PhoneIcon } from "lucide-vue-next"

const page = usePage()
const isScrolled = ref(false)
const isMobileMenuOpen = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
}

onMounted(() => window.addEventListener("scroll", handleScroll))
onUnmounted(() => window.removeEventListener("scroll", handleScroll))

const navLinks = [
  { name: "Home", href: "/" },
  { name: "Browse Cars", href: "/vehicles" },
  { name: "About", href: route("multivendor.about") },
  { name: "Contact", href: route("multivendor.contact") },
]

const isActive = (link) => {
  const currentUrl = page.url

  if (link.href === "/") {
    return currentUrl === "/" || currentUrl === ""
  }

  const urlWithoutQuery = currentUrl.split("?")[0]
  return urlWithoutQuery === link.href
}
</script>

<style scoped>

.navbar-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 1rem 0;
}

.navbar-scrolled {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  box-shadow: 0 4px 30px rgba(24, 59, 78, 0.1);
  padding: 0.5rem 0;
  border-bottom: 1px solid rgba(221, 168, 83, 0.2);
}

.navbar-container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Logo */
.navbar-logo {
  display: flex;
  align-items: center;
  z-index: 1001;
  transition: all 0.3s ease;
}

.logo-image {
  height: 40px;
  width: auto;
  transition: height 0.3s ease;
}

.navbar-scrolled .logo-image {
  height: 36px;
}

.navbar-desktop {
  display: flex;
  gap: 3rem;
  align-items: center;
}

.nav-link {
  position: relative;
  text-decoration: none;
  font-weight: 500;
  font-size: 1.2rem;
  color: #1f2937;
  padding: 0.5rem 0;
  transition: color 0.3s ease;
  white-space: nowrap;
}

.nav-link-light {
  color: #FFFFFF;
}

.nav-link:hover {
  color: #DDA853;
}

.nav-link-light:hover {
  color: #DDA853;
}

.nav-link-underline {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, #DDA853, #C19A4F);
  border-radius: 1px;
  transition: width 0.3s ease;
}

.nav-link:hover .nav-link-underline,
.nav-link-active .nav-link-underline {
  width: 100%;
}

.nav-link-active {
  color: #DDA853;
  font-weight: 600;
}

.nav-link-light.nav-link-active {
  color: #DDA853;
}

.navbar-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  justify-self: end;
}

.phone-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  border-radius: 50px;
  text-decoration: none;
  color: #FFFFFF;
  font-weight: 500;
  font-size: 1rem;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(4px);
}

.phone-link:hover {
  background: rgba(221, 168, 83, 0.2);
  border-color: #DDA853;
  color: #DDA853;
  transform: translateY(-1px);
}

.phone-link-dark {
  color: #183B4E;
  border: 1px solid rgba(24, 59, 78, 0.15);
  background: rgba(255, 255, 255, 0.9);
}

.phone-link-dark:hover {
  background: rgba(221, 168, 83, 0.1);
  border-color: #DDA853;
  color: #DDA853;
}

.phone-link svg {
  transition: transform 0.3s ease;
}

.phone-link:hover svg {
  transform: scale(1.1);
}


.btn-primary {
  padding: 0.6rem 1.8rem;
  border-radius: 50px;
  font-weight: 600;
  text-decoration: none;
  background: #DDA853;
  color: #183B4E;
  border: none;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(221, 168, 83, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  background: #C19A4F;
  box-shadow: 0 10px 25px rgba(221, 168, 83, 0.4);
  color: #FFFFFF;
}

/* Mobile Toggle */
.mobile-toggle {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  z-index: 1001;
  align-items: center;
  justify-content: center;
}

/* ========= MOBILE MENU ========== */
@media (max-width: 999px) {
  .navbar-desktop,
  .navbar-actions {
    display: none;
  }

  .mobile-toggle {
    display: flex;
  }

  .navbar-container {
    padding: 0 1.5rem;
  }
}

.mobile-offcanvas {
  position: fixed;
  top: 0;
  right: -320px;
  width: 320px;
  max-width: 85vw;
  height: 100vh;
  z-index: 1050;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  transform: translateX(0);
}

.mobile-offcanvas--open {
  transform: translateX(-320px);
}

.mobile-offcanvas__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  color: #183B4E;
}

.mobile-offcanvas__title {
  margin: 0;
  font-weight: 800;
  font-size: 18px;
  letter-spacing: 0.5px;
  color: #183B4E;
}

.mobile-offcanvas__close {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: 1px solid rgba(24, 59, 78, 0.2);
  background: rgba(255, 255, 255, 0.9);
  color: #183B4E;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s ease;
}

.mobile-offcanvas__close:hover {
  background: #FFFFFF;
  transform: scale(1.05);
}

.mobile-offcanvas__body {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}

.mobile-offcanvas__body::-webkit-scrollbar {
  width: 6px;
}

.mobile-offcanvas__body::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.03);
}

.mobile-offcanvas__body::-webkit-scrollbar-thumb {
  background: rgba(221, 168, 83, 0.3);
  border-radius: 3px;
}

.mobile-offcanvas__body::-webkit-scrollbar-thumb:hover {
  background: rgba(221, 168, 83, 0.5);
}

.mobile-offcanvas__link {
  padding: 14px 20px;
  border-radius: 12px;
  margin: 0 12px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid transparent;
  color: #183B4E;
}

.mobile-offcanvas__link:before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #DDA853;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.mobile-offcanvas__link:hover {
  background: rgba(221, 168, 83, 0.06);
  border-color: rgba(221, 168, 83, 0.12);
  transform: translateX(4px);
}

.mobile-offcanvas__link:hover:before {
  opacity: 1;
}

.mobile-offcanvas__link--active {
  background: rgba(221, 168, 83, 0.1);
  color: #DDA853 !important;
  border-color: rgba(221, 168, 83, 0.2);
  font-weight: 800;
  box-shadow: 0 4px 12px rgba(221, 168, 83, 0.1);
}

.mobile-offcanvas__phone-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 12px;
  text-decoration: none;
  background: linear-gradient(135deg, #183B4E, #27548A);
  color: #FFFFFF;
  font-weight: 500;
  transition: all 0.3s ease;
  margin: 0 12px;
}

.mobile-offcanvas__phone-link:hover {
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(221, 168, 83, 0.3);
}

.mobile-offcanvas__phone-link svg {
  color: #DDA853;
  width: 20px;
  height: 20px;
}

.mobile-offcanvas__phone-link:hover svg {
  color: #183B4E;
}

.mobile-offcanvas__phone-link .mobile-phone-number {
  font-size: 1rem;
  font-weight: 700;
}

.mobile-offcanvas__btn {
  padding: 12px 20px;
  background: linear-gradient(135deg, #DDA853, #C19A4F);
  color: #183B4E !important;
  border-radius: 12px;
  text-align: center;
  font-weight: 700;
  text-decoration: none;
  border: none;
  transition: all 0.2s ease;
  margin: 0 12px;
}

.mobile-offcanvas__btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(221, 168, 83, 0.3);
  color: #FFFFFF !important;
}

.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>
