<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue"
import { Link, usePage } from "@inertiajs/vue3"
import { Menu, X } from "lucide-vue-next"

const page = usePage()

const isScrolled = ref(false)
const isMobileMenuOpen = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

const normalizePath = (path) => {
  const value = String(path || "/").split("?")[0].trim()
  if (!value) return "/"
  const normalized = value.replace(/\/+$/, "")
  return normalized || "/"
}

const currentPath = computed(() => normalizePath(page.url || "/"))

const navLinks = [
  { name: "Home", href: "/" },
  { name: "Cars", href: "/vehicles" },
  { name: "About Us", href: "/about" },
  { name: "Contact Us", href: "/contact" },
]

const hrefPath = (href) => {
  if (!href || String(href).startsWith("#")) return ""

  try {
    return normalizePath(new URL(String(href), window.location.origin).pathname)
  } catch {
    return normalizePath(String(href))
  }
}

const isActive = (link) => {
  if (!link?.href) return false
  if (String(link.href).startsWith("#")) return false

  const linkPath = hrefPath(link.href)

  if (linkPath === "/") {
    return currentPath.value === "/"
  }

  return currentPath.value === linkPath
}

const loginHref = computed(() => {
  try {
    return route("multivendor.login")
  } catch {
    return "/login"
  }
})

watch(isMobileMenuOpen, (open) => {
  document.body.style.overflow = open ? "hidden" : ""
})

watch(
  () => page.url,
  () => {
    isMobileMenuOpen.value = false
  }
)

onMounted(() => {
  handleScroll()
  window.addEventListener("scroll", handleScroll)
})

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll)
  document.body.style.overflow = ""
})
</script>

<template>
  <header
    class="fixed-top transition-all py-3 py-lg-4 container"
    :class="isScrolled ? '' : 'navbar-transparent'"
    style="z-index: 1050;"
  >
    <nav
      class="navbar-inner d-flex align-items-center justify-content-between mx-auto"
      :class="isScrolled ? 'navbar-pill' : 'navbar-full'"
    >
      <!-- LOGO -->
      <Link
        href="/"
        class="fw-bold text-decoration-none d-flex align-items-center"
        :class="isScrolled ? 'text-dark' : 'text-white'"
      >
        <img
          v-if="isScrolled"
          src="/assets/images/logo-1.png"
          alt="Autosale.lk Logo"
          class="logo-img"
        />
        <img
          v-else
          src="/assets/images/logo-2.png"
          alt="Autosale.lk Logo"
          class="logo-img"
        />
      </Link>

      <!-- DESKTOP NAV -->
      <ul class="d-none d-lg-flex gap-4 gap-xl-5 list-unstyled mb-0">
        <li v-for="link in navLinks" :key="link.name">
          <Link
            :href="link.href"
            class="text-decoration-none fw-medium position-relative nav-link-custom"
            :class="[
              isScrolled ? 'text-dark' : 'text-white',
              { 'nav-link-active': isActive(link) }
            ]"
            :aria-current="isActive(link) ? 'page' : null"
          >
            {{ link.name }}
          </Link>
        </li>
      </ul>

      <!-- CTA -->
      <div class="d-none d-lg-flex gap-2 gap-xl-3">
        <a
          :href="loginHref"
          class="custom-btn rounded-pill px-4 text-decoration-none"
          :class="isScrolled ? 'custom-btn-primary' : 'custom-btn-secondary'"
        >
          Sign In
        </a>
      </div>

      <!-- MOBILE TOGGLE -->
      <button
        class="btn d-lg-none p-1 mobile-menu-btn"
        @click="isMobileMenuOpen = !isMobileMenuOpen"
        :class="isScrolled ? 'text-dark' : 'text-white'"
        aria-label="Toggle mobile menu"
        type="button"
      >
        <X v-if="isMobileMenuOpen" size="26" />
        <Menu v-else size="26" />
      </button>
    </nav>
  </header>

  <!-- Offcanvas Backdrop -->
  <div
    v-if="isMobileMenuOpen"
    class="mobile-offcanvas-backdrop"
    @click="isMobileMenuOpen = false"
  ></div>

  <!-- Offcanvas Menu -->
  <div
    class="d-lg-none mobile-offcanvas bg-white shadow-lg"
    :class="{ 'mobile-offcanvas--open': isMobileMenuOpen }"
  >
    <!-- Offcanvas Header -->
    <div class="mobile-offcanvas__header">
      <h6 class="mobile-offcanvas__title">Menu</h6>
      <button
        type="button"
        class="mobile-offcanvas__close"
        @click="isMobileMenuOpen = false"
        aria-label="Close menu"
      >
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
          :class="isActive(link) ? 'mobile-offcanvas__link--active' : 'text-dark'"
          @click="isMobileMenuOpen = false"
        >
          {{ link.name }}
        </Link>

        <a
          :href="loginHref"
          class="mobile-offcanvas__btn mt-3"
          @click="isMobileMenuOpen = false"
        >
          Sign In
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.nav-link-custom {
  padding-bottom: 8px;
}

.nav-link-custom::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: 2px;
  width: 100%;
  height: 2px;
  border-radius: 999px;
  background: currentColor;
  opacity: 0;
  transform: scaleX(0);
  transform-origin: center;
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.nav-link-custom:hover::after {
  opacity: 0.65;
  transform: scaleX(1);
}

.nav-link-active::after {
  opacity: 1;
  transform: scaleX(1);
}

.nav-mobile-active {
  color: #111827;
  font-weight: 800;
}

.mobile-menu-btn {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.mobile-menu-btn:hover {
  background: #ffffff;
  border-color: rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

.mobile-menu-btn:active {
  transform: translateY(0);
}

/* Offcanvas Backdrop */
.mobile-offcanvas-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1049;
  opacity: 0;
  animation: fadeIn 0.2s ease forwards;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}

/* Offcanvas Menu */
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

/* Offcanvas Header */
.mobile-offcanvas__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: white;
}

.mobile-offcanvas__title {
  margin: 0;
  font-weight: 800;
  font-size: 18px;
  letter-spacing: 0.5px;
}

.mobile-offcanvas__close {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.12);
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s ease;
}

.mobile-offcanvas__close:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

/* Offcanvas Body */
.mobile-offcanvas__body {
  flex: 1;
  overflow-y: auto;
  padding: 0;
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
}

.mobile-offcanvas__link:before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #5c2d80;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.mobile-offcanvas__link:hover {
  background: rgba(92, 45, 128, 0.06);
  border-color: rgba(92, 45, 128, 0.12);
  transform: translateX(4px);
}

.mobile-offcanvas__link:hover:before {
  opacity: 1;
}

.mobile-offcanvas__link--active {
  background: rgba(92, 45, 128, 0.1);
  color: #5c2d80 !important;
  border-color: rgba(92, 45, 128, 0.2);
  font-weight: 800;
  box-shadow: 0 4px 12px rgba(92, 45, 128, 0.1);
}

/* Mobile Sign In Button */
.mobile-offcanvas__btn {
  padding: 12px 20px;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: white !important;
  border-radius: 12px;
  text-align: center;
  font-weight: 700;
  text-decoration: none;
  border: none;
  transition: all 0.2s ease;
  margin: 0 12px;
  display: block;
}

.mobile-offcanvas__btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(92, 45, 128, 0.3);
}

/* Smooth scroll */
.mobile-offcanvas__body::-webkit-scrollbar {
  width: 6px;
}

.mobile-offcanvas__body::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.03);
}

.mobile-offcanvas__body::-webkit-scrollbar-thumb {
  background: rgba(92, 45, 128, 0.3);
  border-radius: 3px;
}

.mobile-offcanvas__body::-webkit-scrollbar-thumb:hover {
  background: rgba(92, 45, 128, 0.5);
}
</style>