<template>
    <aside v-if="visible && isVisible" class="settings-mini-nav" :class="`settings-mini-nav--${sizeMode}`">

        <!-- Desktop & Tab -->
        <nav v-if="sizeMode !== 'mobile'" class="settings-mini-nav__list">
            <button v-for="item in visibleItems" :key="item.id" type="button" class="settings-mini-nav__item"
                :class="{ active: activeItem === item.id }" @click="handleClick(item)">
                <i :class="item.icon"></i>
                <span v-if="sizeMode === 'desktop'" class="settings-mini-nav__label">
                    {{ item.label }}
                </span>
            </button>
        </nav>

        <!-- Mobile -->
        <template v-if="sizeMode === 'mobile'">
            <div class="mobile-nav-inner">
                <button class="mobile-nav-arrow" :disabled="mobilePage === 0" aria-label="Previous"
                    @click="mobilePage--">
                    <i class="bi bi-chevron-left"></i>
                </button>

                <div class="mobile-nav-viewport">
                    <div class="mobile-nav-track"
                        :style="{ transform: `translateX(-${mobilePage * mobilePageWidth}px)` }">
                        <button v-for="item in visibleItems" :key="item.id" type="button"
                            class="settings-mini-nav__item--mobile" :class="{ active: activeItem === item.id }"
                            @click="handleClick(item)">
                            <i :class="item.icon"></i>
                            <span class="nav-icon-label">{{ item.label }}</span>
                        </button>
                    </div>
                </div>

                <button class="mobile-nav-arrow" :disabled="mobilePage >= mobileMaxPage" aria-label="Next"
                    @click="mobilePage++">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <!-- <div class="mobile-nav-dots">
                <span v-for="p in mobileMaxPage + 1" :key="p" class="mobile-nav-dot"
                    :class="{ active: mobilePage === p - 1 }" />
            </div> -->
        </template>

    </aside>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from "vue"
import { usePermission } from "@/composables/usePermission"
import { router } from "@inertiajs/vue3"

const { can } = usePermission()

const props = defineProps({
    visible: { type: Boolean, default: true },
    activeItem: { type: String, default: "currency" },
    size: { type: String, default: "auto" },
    hideOnMobile: { type: Boolean, default: false },
    hideOnDesktop: { type: Boolean, default: false },
})

const emit = defineEmits(["update:activeItem", "navigate"])

const items = [
    { id: "currency", permission: "settings-currency.view", label: "Currency", icon: "bi bi-cash-stack", link: route("vendor.settings.currency") },
    { id: "logo", permission: "settings.logo.view", label: "Logo", icon: "bi bi-image", link: route("vendor.settings.logo") },
    { id: "pms", permission: "pms.view", label: "PMS", icon: "bi bi-receipt", link: route("vendor.settings.pms") },
    { id: "mail", permission: "settings-mail.view", label: "Mail", icon: "bi bi-envelope", link: route("vendor.settings.mail") },
    { id: "mail-recipients", permission: "settings-mail.view", label: "Mail To", icon: "bi bi-envelope-paper", link: route("vendor.settings.mail.recipients") },
    { id: "kitchen-alert", permission: "settings-kitchen-alert.view", label: "Kitchen Alert", icon: "bi bi-bell", link: route("vendor.settings.kitchen-alert") },
]

const activeItem = ref(props.activeItem)
const visibleItems = computed(() => items.filter(item => can(item.permission)))

watch(() => props.activeItem, (val) => (activeItem.value = val))

const windowMode = ref("desktop")

const updateMode = () => {
    const w = window.innerWidth
    if (w <= 768) windowMode.value = "mobile"
    else if (w <= 1231) windowMode.value = "tab"
    else windowMode.value = "desktop"
}

const sizeMode = computed(() =>
    props.size === "auto" ? windowMode.value : props.size
)

const isVisible = computed(() => {
    if (!props.visible) return false
    if (props.hideOnMobile && sizeMode.value === "mobile") return false
    if (props.hideOnDesktop && (sizeMode.value === "desktop" || sizeMode.value === "tab")) return false
    return true
})

const MOBILE_VISIBLE = 3
const MOBILE_ITEM_W = 72   

const mobilePage = ref(0)
const mobilePageWidth = computed(() => MOBILE_VISIBLE * MOBILE_ITEM_W)
const mobileMaxPage = computed(() =>
    Math.max(0, Math.ceil(visibleItems.value.length / MOBILE_VISIBLE) - 1)
)

watch(visibleItems, () => { mobilePage.value = 0 })

const handleClick = (item) => {
    activeItem.value = item.id
    emit("update:activeItem", item.id)
    emit("navigate", item)
    router.visit(item.link)
}

onMounted(() => {
    updateMode()
    window.addEventListener("resize", updateMode)
})

onBeforeUnmount(() => {
    window.removeEventListener("resize", updateMode)
})
</script>

<style scoped>
.settings-mini-nav {
    background: #fff;
    border-radius: 24px;
    border: 1px solid #fde8cc;
    box-shadow: 0 10px 30px rgba(251, 146, 60, 0.06),
        0 2px 8px rgba(251, 146, 60, 0.04);
    height: fit-content;
    margin-left: auto;
    margin-right: auto;
    transition: all 0.25s ease;
}

.settings-mini-nav__list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.settings-mini-nav__item {
    width: 100%;
    border: 1.5px solid transparent;
    background: transparent;
    border-radius: 14px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    color: #c07830;
    transition: 0.2s ease;
}

.settings-mini-nav__item i {
    font-size: 1.1rem;
    color: #e8953a;
    flex-shrink: 0;
}

.settings-mini-nav__item:hover {
    background: #fff7ed;
    color: #e8853a;
    transform: translateX(2px);
}

.settings-mini-nav__item:hover i {
    color: #f97316;
}

.settings-mini-nav__item.active {
    background: #fff4e6;
    color: #c2620a;
    border-color: #fcd09a;
    box-shadow: inset 0 1px 3px rgba(251, 146, 60, 0.10);
}

.settings-mini-nav__item.active i {
    color: #f97316;
}

.settings-mini-nav--desktop {
    width: 200px;
    padding: 18px;
}

.settings-mini-nav--tab {
    width: 88px;
    padding: 14px 10px;
}

.settings-mini-nav--tab .settings-mini-nav__list {
    align-items: center;
}

.settings-mini-nav--tab .settings-mini-nav__item {
    justify-content: center;
}

.settings-mini-nav--mobile {
    width: 290px;
    padding: 10px 8px 8px;
}

.mobile-nav-inner {
    display: flex;
    align-items: center;
    gap: 3px;
}

.mobile-nav-arrow {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 12px;
    border: 1.5px solid #fcd09a;
    background: #fff7ed;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #d97c2a;
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}

.mobile-nav-arrow i {
    font-size: 11px;
}

.mobile-nav-arrow:hover:not(:disabled) {
    background: #fde8cc;
    border-color: #f5a94a;
    color: #c2620a;
}

.mobile-nav-arrow:disabled {
    opacity: 0.3;
    cursor: default;
}

.mobile-nav-viewport {
    flex: 1;
    overflow: hidden;
}

.mobile-nav-track {
    display: flex;
    gap: 4px;
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Mobile nav items */
.settings-mini-nav__item--mobile {
    flex-shrink: 0;
    width: 68px;
    border: 1.5px solid transparent;
    background: transparent;
    border-radius: 14px;
    padding: 8px 4px 7px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease;
}

.settings-mini-nav__item--mobile i {
    font-size: 1.1rem;
    color: #e8953a;
}

.settings-mini-nav__item--mobile .nav-icon-label {
    font-size: 9.5px;
    font-weight: 600;
    color: #c07830;
    letter-spacing: 0.01em;
    white-space: nowrap;
}

.settings-mini-nav__item--mobile:hover {
    background: #fff7ed;
}

.settings-mini-nav__item--mobile:hover i {
    color: #f97316;
}

.settings-mini-nav__item--mobile.active {
    background: #fff4e6;
    border-color: #fcd09a;
    box-shadow: inset 0 1px 3px rgba(251, 146, 60, 0.10);
}

.settings-mini-nav__item--mobile.active i {
    color: #f97316;
}

.settings-mini-nav__item--mobile.active .nav-icon-label {
    color: #c2620a;
}

.mobile-nav-dots {
    display: flex;
    justify-content: center;
    gap: 5px;
    padding-top: 7px;
}

.mobile-nav-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #fcd09a;
    transition: background 0.2s ease, width 0.2s ease;
}

.mobile-nav-dot.active {
    background: #f97316;
    width: 14px;
    border-radius: 3px;
}
</style>