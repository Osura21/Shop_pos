<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { Link, usePage } from "@inertiajs/vue3"
import { Menu, X } from "lucide-vue-next"

const page = usePage()
const isMobileMenuOpen = ref(false)
const isNavbarScrolled = ref(false)

const user = computed(() => page.props.auth?.user || null)
const isLoggedIn = computed(() => !!user.value)
const currentPath = computed(() => String(page.url || "/").split("?")[0])

const navLinks = [
    { name: "Home", href: "/" },
    { name: "Restaurants", href: "/resturents" },
    { name: "Features", href: "/features" },
    { name: "Pricing", href: "/pricing" },
    { name: "Why Us", href: "/why-us" },
]

const isActive = (href) => {
    if (href === "/resturents") {
        return currentPath.value === href || currentPath.value.startsWith(`${href}/`)
    }

    return !href.includes("#") && href === currentPath.value
}

const openAuthPopup = (redirectTo) => {
    window.dispatchEvent(new CustomEvent("auth:open", { detail: { redirect: redirectTo } }))
}

// Keep the visual state separate from layout so the page does not jump while scrolling.
const handleNavbarScroll = () => {
    isNavbarScrolled.value = window.scrollY > 20
}

onMounted(() => {
    handleNavbarScroll()
    window.addEventListener("scroll", handleNavbarScroll, { passive: true })
})

onBeforeUnmount(() => {
    window.removeEventListener("scroll", handleNavbarScroll)
})
</script>

<template>
    <header class="sappy-nav" :class="{ 'navbar-scrolled': isNavbarScrolled }">
        <nav class="sappy-nav__inner">
            <Link href="/" class="sappy-logo" aria-label="Sappy Eats home">
                Sappy <strong>Eats</strong>
            </Link>

            <div class="sappy-nav__links">
                <Link
                    v-for="link in navLinks"
                    :key="link.name"
                    :href="link.href"
                    :class="{ active: isActive(link.href) }"
                >
                    {{ link.name }}
                </Link>
            </div>

            <div class="sappy-nav__actions">
                <!-- <a v-if="!isLoggedIn" href="/admin/login" class="sappy-btn sappy-btn--light">Log In</a>
                <Link v-else :href="route('customer.account')" class="sappy-btn sappy-btn--light">My Account</Link> -->
                <Link :href="route('seller.register')" class="sappy-btn sappy-btn--dark">Become a Partner</Link>
            </div>

            <button
                type="button"
                class="sappy-menu"
                :aria-expanded="isMobileMenuOpen ? 'true' : 'false'"
                aria-controls="sappy-mobile-menu"
                aria-label="Toggle menu"
                @click="isMobileMenuOpen = !isMobileMenuOpen"
            >
                <X v-if="isMobileMenuOpen" size="24" />
                <Menu v-else size="24" />
            </button>
        </nav>

        <Transition name="mobile-menu">
            <div v-if="isMobileMenuOpen" id="sappy-mobile-menu" class="sappy-mobile">
                <Link
                    v-for="link in navLinks"
                    :key="link.name"
                    :href="link.href"
                    @click="isMobileMenuOpen = false"
                >
                    {{ link.name }}
                </Link>
                <a href="/admin/login" @click="isMobileMenuOpen = false">Log In</a>
                <Link :href="route('seller.register')" @click="isMobileMenuOpen = false">Become a Partner</Link>
            </div>
        </Transition>
    </header>
</template>

<style scoped>
.sappy-nav {
    position: fixed;
    inset: 0 0 auto;
    z-index: 1050;
    background: transparent;
    border-bottom: 1px solid transparent;
    overflow: hidden;
    transition:
        background 0.35s ease,
        border-color 0.35s ease,
        box-shadow 0.35s ease;
}

.sappy-nav::before,
.sappy-nav::after {
    content: "";
    position: absolute;
    inset: 0 -14%;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.35s ease;
}

.sappy-nav::before {
    background:
        radial-gradient(ellipse 38% 170% at 8% 118%, rgba(223, 31, 45, 0.16), transparent 58%),
        radial-gradient(ellipse 48% 180% at 38% -30%, rgba(255, 107, 116, 0.17), transparent 62%),
        radial-gradient(ellipse 40% 160% at 76% 122%, rgba(223, 31, 45, 0.13), transparent 58%),
        radial-gradient(ellipse 26% 120% at 102% 30%, rgba(255, 196, 200, 0.22), transparent 60%),
        linear-gradient(115deg, rgba(255, 255, 255, 0.78), rgba(255, 250, 250, 0.48));
    background-size: 150% 150%, 170% 170%, 150% 150%, 140% 140%, 100% 100%;
    filter: blur(12px);
    transform: translate3d(0, 0, 0);
}

.sappy-nav::after {
    background:
        linear-gradient(105deg, transparent 4%, rgba(255, 255, 255, 0.58) 16%, transparent 30%),
        radial-gradient(ellipse 26% 115% at 10% 48%, rgba(255, 255, 255, 0.62), transparent 64%),
        radial-gradient(ellipse 30% 125% at 40% 62%, rgba(255, 107, 116, 0.15), transparent 62%),
        radial-gradient(ellipse 34% 135% at 72% 42%, rgba(223, 31, 45, 0.12), transparent 64%),
        radial-gradient(ellipse 22% 100% at 94% 58%, rgba(255, 255, 255, 0.52), transparent 62%);
    background-size: 160% 100%, 190% 170%, 160% 160%, 180% 170%, 150% 150%;
    filter: blur(13px);
    mix-blend-mode: soft-light;
    transform: translate3d(0, 0, 0);
}

.sappy-nav.navbar-scrolled {
    background: rgba(255, 255, 255, 0.78);
    border-color: rgba(223, 31, 45, 0.11);
    box-shadow: 0 18px 50px rgba(17, 24, 39, 0.1);
    backdrop-filter: blur(22px) saturate(170%);
    -webkit-backdrop-filter: blur(22px) saturate(170%);
}

.sappy-nav.navbar-scrolled::before {
    opacity: 1;
    animation: sappy-liquid-glow 8s ease-in-out infinite alternate;
}

.sappy-nav.navbar-scrolled::after {
    opacity: 1;
    animation: sappy-liquid-shimmer 9s ease-in-out infinite alternate;
}

.sappy-nav__inner {
    width: min(100% - 48px, 1320px);
    height: 78px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 26px;
    position: relative;
    z-index: 1;
    transition: height 0.35s ease;
}

.sappy-nav.navbar-scrolled .sappy-nav__inner {
    height: 70px;
}

.sappy-logo {
    color: #050505;
    text-decoration: none;
    font-size: 28px;
    font-weight: 900;
    white-space: nowrap;
}

.sappy-logo strong {
    color: #df1f2d;
}

.sappy-nav__links,
.sappy-nav__actions {
    display: flex;
    align-items: center;
    gap: 28px;
}

.sappy-nav__links a {
    position: relative;
    min-height: 78px;
    display: inline-flex;
    align-items: center;
    color: #1f2937;
    text-decoration: none;
    font-size: 14px;
    font-weight: 900;
    white-space: nowrap;
    transition:
        color 0.2s ease,
        min-height 0.35s ease;
}

.sappy-nav.navbar-scrolled .sappy-nav__links a {
    min-height: 70px;
}

.sappy-nav__links a:hover {
    color: #df1f2d;
}

.sappy-nav__links a.active {
    color: #df1f2d;
}

.sappy-nav__links a.active::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 21px;
    height: 3px;
    border-radius: 999px;
    background: #df1f2d;
}

.sappy-btn {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 0 22px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 900;
    white-space: nowrap;
}

.sappy-btn--light {
    color: #050505;
    border: 1px solid #e3e6ec;
    background: #fff;
}

.sappy-btn--dark {
    color: #fff;
    background: #050505;
}

.sappy-menu {
    display: none;
    width: 44px;
    height: 44px;
    border: 1px solid #e3e6ec;
    border-radius: 14px;
    background: #fff;
    align-items: center;
    justify-content: center;
}

.sappy-mobile {
    display: none;
}

@keyframes sappy-liquid-glow {
    0% {
        background-position: 0% 72%, 34% 0%, 100% 82%, 88% 28%, 0 0;
        transform: translate3d(-2.4%, 0, 0) scale(1.04);
    }

    50% {
        background-position: 44% 96%, 68% 20%, 54% 66%, 42% 70%, 0 0;
        transform: translate3d(1.8%, 0, 0) scale(1.08);
    }

    100% {
        background-position: 90% 70%, 16% 34%, 8% 92%, 12% 48%, 0 0;
        transform: translate3d(3%, 0, 0) scale(1.04);
    }
}

@keyframes sappy-liquid-shimmer {
    0% {
        background-position: -20% 50%, 0% 48%, 26% 66%, 76% 38%, 100% 62%;
        transform: translate3d(-3%, 0, 0) scale(1.07);
    }

    45% {
        background-position: 60% 50%, 35% 56%, 70% 42%, 30% 70%, 66% 48%;
        transform: translate3d(2%, 0, 0) scale(1.1);
    }

    100% {
        background-position: 125% 50%, 78% 42%, 10% 58%, 92% 48%, 20% 66%;
        transform: translate3d(3.4%, 0, 0) scale(1.07);
    }
}

.mobile-menu-enter-active,
.mobile-menu-leave-active {
    transition:
        opacity 0.24s ease,
        transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.mobile-menu-enter-from,
.mobile-menu-leave-to {
    opacity: 0;
    transform: translateY(-14px);
}

@media (prefers-reduced-motion: reduce) {
    .sappy-nav,
    .sappy-nav::before,
    .sappy-nav::after,
    .sappy-nav__inner,
    .sappy-nav__links a,
    .mobile-menu-enter-active,
    .mobile-menu-leave-active {
        animation: none;
        transition: none;
    }
}

@media (max-width: 1080px) {
    .sappy-nav__links,
    .sappy-nav__actions {
        display: none;
    }

    .sappy-menu {
        display: inline-flex;
    }

    .sappy-mobile {
        width: min(100% - 32px, 520px);
        margin: 0 auto 16px;
        padding: 12px;
        display: grid;
        gap: 6px;
        position: relative;
        z-index: 1;
        border: 1px solid rgba(223, 31, 45, 0.12);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 24px 60px rgba(17, 24, 39, 0.12);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
    }

    .sappy-mobile a {
        color: #111827;
        text-decoration: none;
        font-weight: 900;
        padding: 12px 14px;
        border-radius: 12px;
    }

    .sappy-mobile a:hover {
        background: #fff0f1;
        color: #df1f2d;
    }
}

@media (max-width: 640px) {
    .sappy-nav__inner {
        width: min(100% - 32px, 1320px);
        height: 70px;
    }

    .sappy-logo {
        font-size: 24px;
    }
}
</style>
