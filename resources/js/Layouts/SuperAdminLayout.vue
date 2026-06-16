<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from "vue";
import { Link, usePage, Head, router, useForm } from "@inertiajs/vue3";
import "@@/css/superadmin.css";
import { usePermission } from "@/composables/usePermission";
import ctd from "country-telephone-data";
import "flag-icons/css/flag-icons.min.css";

const page = usePage();
const user = computed(() => page.props.auth?.user);
const { can } = usePermission();

/*  DESKTOP STATE  */

const collapsed = ref(
    localStorage.getItem("admin_sidebar_collapsed") === "true",
);
const openMenu = ref(null);

// Desktop flyout
const flyoutKey = ref(null);
const flyoutTop = ref(0);
const flyoutLeft = ref(0);
const isReadonly = ref(true);

const sidebarRef = ref(null);
const flyoutRef = ref(null);

const showCountry = ref(false);

const countries = ctd.allCountries.map((c) => ({
    name: c.name,
    code: `+${c.dialCode}`,
    flagClass: `fi fi-${c.iso2.toLowerCase()}`,
}));

const phoneCountryCode = ref("+94");
const phoneNumber = ref("");

// run once when loading user
const initPhone = () => {
    const fullPhone = user.value?.phone || "";

    const found = countries.find((c) => fullPhone.startsWith(c.code));

    if (found) {
        phoneCountryCode.value = found.code;
        selectedCountry.value = found;
        phoneNumber.value = fullPhone.slice(found.code.length);
    } else {
        // fallback
        phoneCountryCode.value = "+94";
        selectedCountry.value = countries[0];
        phoneNumber.value = fullPhone;
    }
};

const selectedCountry = ref(
    countries.find((c) => c.code === phoneCountryCode.value) || countries[0],
);

const toggleCountry = () => {
    showCountry.value = !showCountry.value;
};

const selectCountry = (c) => {
    selectedCountry.value = c;
    phoneCountryCode.value = c.code;
    showCountry.value = false;
};

initPhone();

/*  MOBILE STATE  */

const mobileOpen = ref(false);
const mobileVendorsOpen = ref(false);
const mobileSettingsOpen = ref(false);

const profileMenuOpen = ref(false);
const accountModalOpen = ref(false);
const passwordModalOpen = ref(false);
const showCurrent = ref(false);
const showPassword = ref(false);
const showConfirm = ref(false);

/*  ACTIVE STATES  */
const currentRoute = (...names) => {
    page.url;

    try {
        return names.some((name) => route().current(name));
    } catch {
        return false;
    }
};

const currentRouteGroup = (prefix) => currentRoute(`${prefix}.*`);

const isDashboard = computed(() => currentRoute("superadmin.dashboard"));
const isFoodCategories = computed(() => currentRouteGroup("food-categories"));
const isSeoFooterLinks = computed(() => currentRouteGroup("seo-footer-links"));
const isRequestedVendors = computed(() => currentRouteGroup("requested-vendors"));
const isVendors = computed(() => currentRouteGroup("vendors") || isRequestedVendors.value);
const isSettings = computed(() => currentRouteGroup("settings"));
const isSubscriptions = computed(() => currentRouteGroup("vendor-subscriptions"));

watch([isVendors, isSettings], ([vendorsActive, settingsActive]) => {
    if (!collapsed.value) {
        openMenu.value = vendorsActive ? "vendors" : settingsActive ? "settings" : null;
    }

    mobileVendorsOpen.value = vendorsActive;
    mobileSettingsOpen.value = settingsActive;
});

/*  METHODS  */

const toggleSidebar = () => {
    collapsed.value = !collapsed.value;
    localStorage.setItem("admin_sidebar_collapsed", String(collapsed.value));
    openMenu.value = null;
    flyoutKey.value = null;
};

const toggleMenu = (key) => {
    openMenu.value = openMenu.value === key ? null : key;
};

const openFlyout = (key, event) => {
    if (!collapsed.value) return;

    if (flyoutKey.value === key) {
        flyoutKey.value = null;
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    flyoutKey.value = key;
    flyoutTop.value = rect.top;
    flyoutLeft.value = rect.right + 12;
};

const closeMobile = () => {
    mobileOpen.value = false;
    mobileVendorsOpen.value = false;
    mobileSettingsOpen.value = false;
};

/* Close flyout + mobile menu AFTER navigation */
router.on("finish", () => {
    flyoutKey.value = null;
    mobileVendorsOpen.value = false;
    mobileOpen.value = false;
    mobileSettingsOpen.value = false;
});

const showToast = ref(false);
const toastMessage = ref("");
const toastType = ref("success");

const toastIcon = computed(() => {
    return toastType.value === "success"
        ? "bi bi-check-circle-fill admin-toast__icon"
        : "bi bi-x-circle-fill admin-toast__icon";
});

const showToastMessage = (message, type = "success") => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;

    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

const accountForm = useForm({
    name: user.value?.name || "",
    email: user.value?.email || "",
    phone: "",
    gender: user.value?.gender || "",
});

const passwordForm = useForm({
    currentPassword: "",
    password: "",
    confirmPassword: "",
    logoutOthers: false,
});

const accountErrors = ref({
    name: "",
    email: "",
    phone: "",
    gender: "",
});

const validateAccount = () => {
    accountErrors.value = { name: "", email: "", phone: "", gender: "" };
    let valid = true;

    if (!accountForm.name?.trim()) {
        accountErrors.value.name = "Name is required";
        valid = false;
    }
    if (!accountForm.email?.trim()) {
        accountErrors.value.email = "Email is required";
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(accountForm.email)) {
        accountErrors.value.email = "Please enter a valid email";
        valid = false;
    }
    if (!accountForm.phone?.trim()) {
        accountErrors.value.phone = "Phone is required";
        valid = false;
    }

    return valid;
};

const formatPhone = () => {
    let value = phoneNumber.value.replace(/\D/g, "");

    if (value.startsWith("0")) {
        value = value.slice(1);
    }

    phoneNumber.value = value;
};

const updateAccount = () => {
    if (!validateAccount()) return;

    accountForm.patch(route("profile.update"), {
        preserveScroll: true,
        onSuccess: () => {
            closeAccountModal();
            router.reload({ only: ["auth"] });
            showToastMessage("Profile updated successfully!", "success");
        },
        onError: () => {
            showToastMessage("Failed to update profile.", "error");
        },
    });
};

const passwordErrors = ref({
    currentPassword: "",
    password: "",
    confirmPassword: "",
});

const validatePassword = () => {
    passwordErrors.value = {
        currentPassword: "",
        password: "",
        confirmPassword: "",
    };
    let valid = true;

    if (!passwordForm.currentPassword) {
        passwordErrors.value.currentPassword = "Current password is required";
        valid = false;
    }
    if (!passwordForm.password) {
        passwordErrors.value.password = "New password is required";
        valid = false;
    } else if (passwordForm.password.length < 6) {
        passwordErrors.value.password =
            "Password must be at least 6 characters";
        valid = false;
    }
    if (!passwordForm.confirmPassword) {
        passwordErrors.value.confirmPassword = "Please confirm password";
        valid = false;
    } else if (passwordForm.password !== passwordForm.confirmPassword) {
        passwordErrors.value.confirmPassword = "Passwords do not match";
        valid = false;
    }

    return valid;
};

const updatePassword = () => {
    if (!validatePassword()) return;

    passwordForm.put(route("password.update"), {
        preserveScroll: true,
        onSuccess: () => {
            closePasswordModal();
            router.reload({ only: ["auth"] });
            showToastMessage("Password updated successfully!", "success");
        },
        onError: () => {
            showToastMessage("Failed to update password.", "error");
        },
    });
};

const handleLogout = () => {
    router.post(route("superadmin.logout"));
};

const toggleProfileMenu = () => {
    profileMenuOpen.value = !profileMenuOpen.value;
};

const openAccountModal = () => {
    accountModalOpen.value = true;
    profileMenuOpen.value = false;
};

const closeAccountModal = () => {
    accountModalOpen.value = false;
};

const openPasswordModal = () => {
    passwordModalOpen.value = true;
    profileMenuOpen.value = false;
};

const closePasswordModal = () => {
    passwordModalOpen.value = false;
};

const getInitials = (name) => {
    if (!name) return "A";
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
};

/* Close desktop flyout on outside click */
const onDocClick = (e) => {
    if (!flyoutKey.value) return;
    const insideSidebar = sidebarRef.value?.contains(e.target);
    const insideFlyout = flyoutRef.value?.contains(e.target);
    if (insideSidebar || insideFlyout) return;
    flyoutKey.value = null;
    if (
        profileMenuOpen.value &&
        !e.target.closest(".admin-topbar__profile-menu")
    ) {
        profileMenuOpen.value = false;
    }
};

onMounted(() => document.addEventListener("click", onDocClick));
onBeforeUnmount(() => document.removeEventListener("click", onDocClick));

/* Prevent body scroll when mobile menu is open */
watch(
    mobileOpen,
    (v) => {
        document.body.style.overflow = v ? "hidden" : "";
    },
    { immediate: true },
);
watch(
    [phoneNumber, phoneCountryCode],
    () => {
        accountForm.phone = `${phoneCountryCode.value}${phoneNumber.value}`;
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    document.body.style.overflow = "";
});

const flyoutMenus = {
    vendors: {
        title: "Vendors",
        items: [
            {
                label: "All Vendors",
                href: () => route("vendors.index"),
                active: () => currentRoute("vendors.index"),
            },
            {
                label: "Add Vendor",
                href: () => route("vendors.create"),
                active: () => currentRoute("vendors.create"),
            },
            {
                label: "Requested Vendors",
                href: () => route("requested-vendors.index"),
                active: () => currentRouteGroup("requested-vendors"),
            },
        ],
    },

    settings: {
        title: "Settings",
        items: [
            {
                label: "Roles & Permissions",
                href: () => route("settings.roles.index"),
                active: () => currentRouteGroup("settings.roles"),
            },
            {
                label: "Staff Members",
                href: () => route("settings.staff.index"),
                active: () => currentRouteGroup("settings.staff"),
            },
            {
                label: "Mail Settings",
                href: () => route("settings.mail.edit"),
                active: () => currentRouteGroup("settings.mail"),
            },
        ],
    },
};
</script>

<template>
    <div>

        <Head :title="page.props.appTitle" />

        <!-- MOBILE TOP BAR -->
        <div class="admin-mobile-header">
            <button class="admin-mobile-toggle" type="button" @click="mobileOpen = true" aria-label="Open menu">
                <i class="bi bi-list" />
            </button>

            <div class="admin-mobile-brand">Autosale.lk</div>

            <div class="admin-mobile-spacer" />
        </div>

        <div class="admin-root superadmin">
            <div class="admin-layout">
                <!-- DESKTOP SIDEBAR -->
                <aside ref="sidebarRef" class="admin-sidebar" :class="{ 'admin-sidebar--collapsed': collapsed }">
                    <!-- Header -->
                    <div class="admin-sidebar__header">
                        <Link :href="route('superadmin.dashboard')" class="admin-sidebar__brand" :class="{
                            'admin-sidebar__brand--hidden': collapsed,
                        }" style="text-decoration: none;">
                            <!-- <img src="/superadmin/images/cropped-Veyogo.png" alt="Autosale.lk"
                                class="login-logo img-fluid mb-0" /> -->

                            <small class="admin-sidebar__brand-role">Saasbeds POS</small>
                        </Link>

                        <button class="admin-sidebar__toggle" type="button" @click="toggleSidebar" :aria-label="collapsed
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                            ">
                            <i :class="collapsed
                                    ? 'bi bi-chevron-right'
                                    : 'bi bi-chevron-left'
                                " />
                        </button>
                    </div>

                    <!-- Nav -->
                    <nav class="admin-sidebar__nav">
                        <!-- Dashboard -->
                        <Link :href="route('superadmin.dashboard')" class="admin-sidebar__item" :class="{
                            'admin-sidebar__item--active': isDashboard,
                        }">
                            <i class="bi bi-speedometer2 admin-sidebar__icon" />
                            <span class="admin-sidebar__label" :class="{
                                'admin-sidebar__label--hidden': collapsed,
                            }">
                                Dashboard
                            </span>
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Dashboard</span>
                        </Link>

                        <!-- Vendors -->
                        <button class="admin-sidebar__item admin-sidebar__item--button" :class="{
                            'admin-sidebar__item--active': isVendors,
                        }" type="button" v-if="can('vendor.view')" @click="
                                collapsed
                                    ? openFlyout('vendors', $event)
                                    : toggleMenu('vendors')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-people admin-sidebar__icon" />
                                <span class="admin-sidebar__label" :class="{
                                    'admin-sidebar__label--hidden':
                                        collapsed,
                                }">
                                    Vendors
                                </span>
                            </div>

                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron" :class="{
                                'admin-sidebar__chevron--open':
                                    openMenu === 'vendors',
                            }" />

                            <span v-if="collapsed" class="admin-sidebar__tooltip">Vendors</span>
                        </button>

                        <!-- Expanded submenu -->
                        <div class="admin-sidebar__submenu" :class="{
                            'admin-sidebar__submenu--open':
                                !collapsed && openMenu === 'vendors',
                        }">
                            <Link :href="route('vendors.index')" class="admin-sidebar__subitem" v-if="can('vendor.view')" :class="{
                                'admin-sidebar__subitem--active':
                                    currentRoute('vendors.index'),
                            }">
                                All Vendors
                            </Link>

                            <Link :href="route('vendors.create')" class="admin-sidebar__subitem"
                                v-if="can('vendor.create')" :class="{
                                    'admin-sidebar__subitem--active':
                                        currentRoute('vendors.create'),
                                }">
                                Add Vendor
                            </Link>

                            <Link :href="route('requested-vendors.index')" class="admin-sidebar__subitem"
                                v-if="can('vendor.view')" :class="{
                                    'admin-sidebar__subitem--active':
                                        currentRouteGroup('requested-vendors'),
                                }">
                                Requested Vendors
                            </Link>
                        </div>

                        <Link :href="route('food-categories.index')" class="admin-sidebar__item"  :class="{
                            'admin-sidebar__item--active': isFoodCategories,
                        }">
                            <i class="bi bi-tags admin-sidebar__icon" />
                            <span class="admin-sidebar__label" :class="{
                                'admin-sidebar__label--hidden': collapsed,
                            }">
                                Food Types
                            </span>
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Food Types</span>
                        </Link>

                        <Link v-if="can('seo-footer-links.view')" :href="route('seo-footer-links.index')" class="admin-sidebar__item" :class="{
                            'admin-sidebar__item--active': isSeoFooterLinks,
                        }">
                            <i class="bi bi-search-heart admin-sidebar__icon" />
                            <span class="admin-sidebar__label" :class="{
                                'admin-sidebar__label--hidden': collapsed,
                            }">
                                SEO Footer
                            </span>
                            <span v-if="collapsed" class="admin-sidebar__tooltip">SEO Footer</span>
                        </Link>

                        <!-- settings -->
                        <button class="admin-sidebar__item admin-sidebar__item--button" :class="{
                            'admin-sidebar__item--active': isSettings,
                        }" type="button" @click="
                                collapsed
                                    ? openFlyout('settings', $event)
                                    : toggleMenu('settings')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-gear admin-sidebar__icon" />
                                <span class="admin-sidebar__label" :class="{
                                    'admin-sidebar__label--hidden':
                                        collapsed,
                                }">
                                    Settings
                                </span>
                            </div>

                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron" :class="{
                                'admin-sidebar__chevron--open':
                                    openMenu === 'settings',
                            }" />

                            <span v-if="collapsed" class="admin-sidebar__tooltip">Settings</span>
                        </button>

                        <!-- Expanded submenu -->
                        <div class="admin-sidebar__submenu" :class="{
                            'admin-sidebar__submenu--open':
                                !collapsed && openMenu === 'settings',
                        }">
                            <Link :href="route('settings.roles.index')" class="admin-sidebar__subitem"
                                v-if="can('roles-permissions.view')" :class="{
                                    'admin-sidebar__subitem--active':
                                        currentRouteGroup('settings.roles'),
                                }">
                                Roles & Permissions
                            </Link>
                            <Link :href="route('settings.staff.index')" class="admin-sidebar__subitem"
                                v-if="can('staff-member.view')" :class="{
                                    'admin-sidebar__subitem--active':
                                        currentRouteGroup('settings.staff'),
                                }">
                                Staff Members
                            </Link>
                            <Link :href="route('settings.mail.edit')" class="admin-sidebar__subitem"
                                v-if="can('mail-settings.view')" :class="{
                                    'admin-sidebar__subitem--active':
                                        currentRouteGroup('settings.mail'),
                                }">
                                Mail Settings
                            </Link>
                        </div>

                        <Link
                            :href="route('vendor-subscriptions.index')"
                            class="admin-sidebar__item"
                            :class="{ 'admin-sidebar__item--active': isSubscriptions }"
                        >
                            <i class="bi bi-diagram-3 admin-sidebar__icon" />
                            <span class="admin-sidebar__label" :class="{ 'admin-sidebar__label--hidden': collapsed }">
                                Vendor Subscriptions
                            </span>
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Vendor Subscriptions</span>
                        </Link>
                    </nav>

                    <!-- Footer -->
                    <!-- <div class="admin-sidebar__footer">
                        <div class="admin-sidebar__user" :class="{ 'admin-sidebar__user--hidden': collapsed }">
                            <div class="admin-sidebar__user-label">Logged in as</div>
                            <div class="admin-sidebar__user-email">{{ user?.email }}</div>
                        </div>
                    </div> -->
                </aside>

                <!--  DESKTOP FLYOUT  -->
                <div v-if="collapsed && flyoutKey" ref="flyoutRef" class="admin-flyout"
                    :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }">
                    <!-- Header -->
                    <!-- <div class="admin-flyout__header">
                        {{ flyoutMenus[flyoutKey]?.title }}
                    </div> -->

                    <!-- Items -->
                    <div class="admin-flyout__list">
                        <Link v-for="(item, index) in flyoutMenus[flyoutKey]
                            ?.items" :key="index" :href="typeof item.href === 'function'
                                        ? item.href()
                                        : item.href
                                    " class="admin-flyout__item" :class="{
                                'admin-flyout__item--active': item.active(),
                            }">
                            {{ item.label }}
                        </Link>
                    </div>
                </div>

                <!--  MOBILE OFFCANVAS (CUSTOM)  -->
                <div class="admin-offcanvas-backdrop" :class="{ 'admin-offcanvas-backdrop--visible': mobileOpen }"
                    @click="closeMobile" />

                <section class="admin-offcanvas" :class="{ 'admin-offcanvas--open': mobileOpen }"
                    aria-label="Mobile menu">
                    <div class="admin-offcanvas__header">
                        <Link :href="route('superadmin.dashboard')" style="text-decoration: none;">
                            <img src="/superadmin/images/cropped-Veyogo.png" alt="Autosale.lk"
                                class="login-logo img-fluid mb-0" />
                        </Link>

                        <button class="admin-offcanvas__close" type="button" @click="closeMobile"
                            aria-label="Close menu">
                            <i class="bi bi-x-lg" />
                        </button>
                    </div>

                    <div class="admin-offcanvas__body">
                        <Link :href="route('superadmin.dashboard')" class="admin-offcanvas__item" :class="{
                            'admin-offcanvas__item--active': isDashboard,
                        }">
                            <i class="bi bi-speedometer2" />
                            <span>Dashboard</span>
                        </Link>

                        <!-- Mobile Vendors -->
                        <button class="admin-offcanvas__accordion-btn" type="button"
                            :class="{ 'admin-offcanvas__accordion-btn--active': isVendors }"
                            @click="mobileVendorsOpen = !mobileVendorsOpen" v-if="can('vendor.view')">
                            <i class="bi bi-people" />
                            <span>Vendors</span>
                            <i class="bi bi-chevron-down admin-offcanvas__chevron" :class="{
                                'admin-offcanvas__chevron--open':
                                    mobileVendorsOpen,
                            }" />
                        </button>

                        <div class="admin-offcanvas__submenu" :class="{
                            'admin-offcanvas__submenu--open':
                                mobileVendorsOpen,
                        }">
                            <Link :href="route('vendors.index')" class="admin-offcanvas__subitem" v-if="can('vendor.view')"
                                :class="{
                                    'admin-offcanvas__subitem--active':
                                        currentRoute('vendors.index'),
                                }">
                                All Vendors
                            </Link>

                            <Link :href="route('vendors.create')" class="admin-offcanvas__subitem"
                                v-if="can('vendor.create')" :class="{
                                    'admin-offcanvas__subitem--active':
                                        currentRoute('vendors.create'),
                                }">
                                Add Vendor
                            </Link>

                            <Link :href="route('requested-vendors.index')" class="admin-offcanvas__subitem"
                                v-if="can('vendor.view')" :class="{
                                    'admin-offcanvas__subitem--active': isRequestedVendors,
                                }">
                                Requested Vendors
                            </Link>
                        </div>

                        <Link :href="route('food-categories.index')" class="admin-offcanvas__item"
                            v-if="can('food-types.view')" :class="{
                                'admin-offcanvas__item--active': isFoodCategories,
                            }" @click="closeMobile">
                            <i class="bi bi-tags" />
                            <span>Food Types</span>
                        </Link>

                        <Link :href="route('seo-footer-links.index')" class="admin-offcanvas__item"
                            v-if="can('seo-footer-links.view')" :class="{
                                'admin-offcanvas__item--active': isSeoFooterLinks,
                            }" @click="closeMobile">
                            <i class="bi bi-search-heart" />
                            <span>SEO Footer</span>
                        </Link>

                        <!-- Mobile Settings -->
                        <button class="admin-offcanvas__accordion-btn" type="button"
                            :class="{ 'admin-offcanvas__accordion-btn--active': isSettings }"
                            @click="mobileSettingsOpen = !mobileSettingsOpen">
                            <i class="bi bi-gear" />
                            <span>Settings</span>
                            <i class="bi bi-chevron-down admin-offcanvas__chevron" :class="{
                                'admin-offcanvas__chevron--open':
                                    mobileSettingsOpen,
                            }" />
                        </button>

                        <div class="admin-offcanvas__submenu" :class="{
                            'admin-offcanvas__submenu--open':
                                mobileSettingsOpen,
                        }">
                            <Link :href="route('settings.roles.index')" class="admin-offcanvas__subitem"
                                v-if="can('roles-permissions.view')" :class="{
                                    'admin-offcanvas__subitem--active':
                                        currentRouteGroup('settings.roles'),
                                }">
                                Roles & Permissions
                            </Link>
                            <Link :href="route('settings.staff.index')" class="admin-offcanvas__subitem"
                                v-if="can('staff-member.view')" :class="{
                                    'admin-offcanvas__subitem--active':
                                        currentRouteGroup('settings.staff'),
                                }">
                                Staff Members
                            </Link>
                            <Link :href="route('settings.mail.edit')" class="admin-offcanvas__subitem"
                                v-if="can('mail-settings.view')" :class="{
                                    'admin-offcanvas__subitem--active':
                                        currentRouteGroup('settings.mail'),
                                }">
                                Mail Settings
                            </Link>
                        </div>

                        <Link
                            :href="route('vendor-subscriptions.index')"
                            class="admin-offcanvas__item"
                            :class="{ 'admin-offcanvas__item--active': isSubscriptions }"
                            @click="closeMobile"
                        >
                            <i class="bi bi-diagram-3" />
                            <span>Vendor Subscriptions</span>
                        </Link>

                    </div>

                    <div class="admin-offcanvas__footer">
                        <span class="admin-offcanvas__email">{{
                            user?.email
                            }}</span>
                    </div>
                </section>

                <!-- RIGHT SIDE -->
                <div class="admin-right">
                    <div class="admin-content-wrapper">
                        <header class="admin-topbar">
                            <div class="admin-topbar__left">
                                <!-- <button class="admin-topbar__icon-btn" aria-label="Notifications">
                                <i class="bi bi-bell" />
                            </button>
                            <button class="admin-topbar__icon-btn" aria-label="Messages">
                                <i class="bi bi-chat-left" />
                            </button>
                            <button class="admin-topbar__icon-btn" aria-label="Fullscreen">
                                <i class="bi bi-fullscreen" />
                            </button> -->
                            </div>

                            <div class="admin-topbar__right">
                                <!-- <button class="admin-topbar__icon-btn" aria-label="Theme toggle">
                                <i class="bi bi-brightness-high" />
                            </button>
                            <div class="admin-topbar__divider" />
                            <button class="admin-topbar__language-btn">
                                <span class="admin-topbar__language-flag">🇬🇧</span>
                                <span>EN</span>
                                <i class="bi bi-chevron-down" />
                            </button> -->
                                <div class="admin-topbar__profile-menu">
                                    <button class="admin-topbar__profile-btn" @click="toggleProfileMenu" type="button">
                                        <div class="admin-topbar__avatar">
                                            {{ getInitials(user?.name) }}
                                        </div>
                                    </button>

                                    <div v-if="profileMenuOpen" class="admin-topbar__dropdown" @click.stop>
                                        <!-- Header -->
                                        <div class="admin-topbar__dropdown-header">
                                            <div class="admin-topbar__dropdown-avatar">
                                                {{ getInitials(user?.name) }}
                                            </div>
                                            <div class="admin-topbar__dropdown-info">
                                                <div class="admin-topbar__dropdown-name">
                                                    {{ user?.name || "Admin" }}
                                                    <span class="admin-topbar__dropdown-role-badge">{{
                                                        user.roles[0].name
                                                    }}</span>
                                                </div>
                                                <div class="admin-topbar__dropdown-email">
                                                    {{
                                                        user?.email ||
                                                        "admin@forkiva.app"
                                                    }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- First Card: My Account + Update Password -->
                                        <div class="admin-topbar__dropdown-card">
                                            <button class="admin-topbar__dropdown-item" @click="openAccountModal"
                                                type="button">
                                                <i class="bi bi-person"></i>
                                                <span>My Account</span>
                                            </button>
                                            <div class="admin-topbar__dropdown-divider"></div>
                                            <button class="admin-topbar__dropdown-item" @click="openPasswordModal"
                                                type="button">
                                                <i class="bi bi-lock"></i>
                                                <span>Update Password</span>
                                            </button>
                                        </div>

                                        <!-- Second Card: Accounts Section + Logout -->
                                        <div class="admin-topbar__dropdown-card">
                                            <!-- Current Account (highlighted) -->
                                            <!-- <div class="admin-topbar__account-item admin-topbar__account-item--active">
                                            <div class="admin-topbar__account-avatar">A</div>
                                            <div class="admin-topbar__account-info">
                                                <div class="admin-topbar__account-name">
                                                    Admin
                                                    <span class="admin-topbar__dropdown-role-badge">Admin</span>
                                                </div>
                                                <div class="admin-topbar__account-email">admin@forkiva.app</div>
                                            </div>
                                        </div>

                                        <button class="admin-topbar__dropdown-item admin-topbar__add-account"
                                            @click="addAnotherAccount" type="button">
                                            <i class="bi bi-plus-circle"></i>
                                            <span>Add another account</span>
                                        </button>

                                        <div class="admin-topbar__dropdown-divider"></div> -->

                                            <button
                                                class="admin-topbar__dropdown-item admin-topbar__dropdown-item--logout"
                                                @click="handleLogout" type="button">
                                                <i class="bi bi-box-arrow-left"></i>
                                                <span>Logout</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </header>

                        <main class="admin-main">
                            <slot />
                        </main>
                    </div>

                    <!-- ACCOUNT MODAL  -->
                    <div v-if="accountModalOpen" class="admin-modal-backdrop" @click.stop="closeAccountModal">
                        <div class="admin-modal" @click.stop>
                            <div class="admin-modal__header">
                                <div class="admin-modal__title">
                                    <i class="bi bi-person-circle admin-modal__icon" />
                                    <span>My Account</span>
                                </div>
                                <button class="admin-modal__close" @click="closeAccountModal" type="button">
                                    <i class="bi bi-x-lg" />
                                </button>
                            </div>

                            <div class="admin-modal__body">
                                <div class="admin-modal__two-column">
                                    <div class="admin-modal__form-group floating">
                                        <input v-model="accountForm.name" class="admin-modal__input" type="text"
                                            placeholder=" " :class="{
                                                error: accountErrors.name,
                                            }" :disabled="isReadonly" />
                                        <label :class="[
                                            'admin-modal__label',
                                            {
                                                'admin-modal__error':
                                                    accountErrors.name,
                                            },
                                        ]">Name</label>
                                        <span v-if="accountErrors.name" class="admin-modal__error">{{ accountErrors.name
                                            }}</span>
                                    </div>

                                    <div class="admin-modal__form-group floating">
                                        <input v-model="accountForm.email" class="admin-modal__input" type="email"
                                            placeholder="" :class="{
                                                error: accountErrors.email,
                                            }" :disabled="isReadonly" />
                                        <label :class="[
                                            'admin-modal__label',
                                            {
                                                'admin-modal__error':
                                                    accountErrors.email,
                                            },
                                        ]">E-mail</label>
                                        <span v-if="accountErrors.email" class="admin-modal__error">{{
                                            accountErrors.email }}</span>
                                    </div>
                                </div>

                                <div class="center-box">
                                    <div class="phone-group">
                                        <div class="phone-row">
                                            <!-- COUNTRY DROPDOWN -->
                                            <div class="country-dropdown" @click="toggleCountry">
                                                <span :class="selectedCountry.flagClass
                                                    "></span>
                                                {{ selectedCountry.code }}
                                                <i class="bi bi-chevron-down" style="margin-left: 5px"></i>

                                                <!-- <label class="admin-modal__label fixed">Code</label> -->

                                                <div v-if="showCountry" class="country-list">
                                                    <div v-for="c in countries" :key="c.code" class="country-item"
                                                        @click.stop="
                                                            selectCountry(c)
                                                            ">
                                                        <span :class="c.flagClass"></span>
                                                        {{ c.code }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="admin-modal__form-group floating phone-input">
                                                <input v-model="phoneNumber" class="admin-modal__input" type="tel"
                                                    @input="formatPhone" placeholder="771234567" />
                                                <label class="admin-modal__label">Phone</label>
                                            </div>
                                        </div>
                                    </div>

                                    <span v-if="accountErrors.phone" class="admin-modal__error">
                                        {{ accountErrors.phone }}
                                    </span>
                                </div>
                            </div>

                            <div class="admin-modal__footer">
                                <button class="admin-modal__btn admin-modal__btn--secondary" @click="closeAccountModal"
                                    type="button">
                                    Cancel
                                </button>
                                <button class="admin-modal__btn admin-modal__btn--primary" @click="updateAccount"
                                    type="button" :disabled="accountForm.processing">
                                    {{
                                        accountForm.processing
                                            ? "Updating..."
                                            : "Update"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!--  PASSWORD MODAL  -->
                    <div v-if="passwordModalOpen" class="admin-modal-backdrop" @click.stop="closePasswordModal">
                        <div class="admin-modal" @click.stop>
                            <div class="admin-modal__header">
                                <div class="admin-modal__title">
                                    <i class="bi bi-person-circle admin-modal__icon" />
                                    <span>Update Password</span>
                                </div>
                                <button class="admin-modal__close" @click="closePasswordModal" type="button">
                                    <i class="bi bi-x-lg" />
                                </button>
                            </div>

                            <div class="admin-modal__body">
                                <div class="admin-modal__form-group floating password-field">
                                    <input v-model="passwordForm.currentPassword" :type="showCurrent ? 'text' : 'password'
                                        " class="admin-modal__input" placeholder="" :class="{
                                            error: passwordErrors.currentPassword,
                                        }" />

                                    <label :class="[
                                        'admin-modal__label',
                                        {
                                            'admin-modal__error':
                                                passwordErrors.currentPassword,
                                        },
                                    ]">Current Password</label>

                                    <i class="bi" :class="[
                                        showCurrent
                                            ? 'bi-eye-slash'
                                            : 'bi-eye',
                                        {
                                            'admin-modal__error':
                                                passwordErrors.currentPassword,
                                        },
                                    ]" @click="showCurrent = !showCurrent"></i>

                                    <span v-if="passwordErrors.currentPassword" class="admin-modal__error">
                                        {{ passwordErrors.currentPassword }}
                                    </span>
                                </div>

                                <div class="admin-modal__two-column">
                                    <div class="admin-modal__form-group floating password-field">
                                        <input v-model="passwordForm.password" :type="showPassword
                                                ? 'text'
                                                : 'password'
                                            " class="admin-modal__input" placeholder="" :class="{
                                                error: passwordErrors.password,
                                            }" />

                                        <label :class="[
                                            'admin-modal__label',
                                            {
                                                'admin-modal__error':
                                                    passwordErrors.password,
                                            },
                                        ]">Password</label>

                                        <i class="bi" :class="[
                                            showPassword
                                                ? 'bi-eye-slash'
                                                : 'bi-eye',
                                            {
                                                'admin-modal__error':
                                                    passwordErrors.password,
                                            },
                                        ]" @click="
                                                showPassword = !showPassword
                                                "></i>

                                        <span v-if="passwordErrors.password" class="admin-modal__error">
                                            {{ passwordErrors.password }}
                                        </span>
                                    </div>

                                    <div class="admin-modal__form-group floating password-field">
                                        <input v-model="passwordForm.confirmPassword
                                            " :type="showConfirm
                                                    ? 'text'
                                                    : 'password'
                                                " class="admin-modal__input" placeholder="" :class="{
                                                error: passwordErrors.confirmPassword,
                                            }" />

                                        <label :class="[
                                            'admin-modal__label',
                                            {
                                                'admin-modal__error':
                                                    passwordErrors.confirmPassword,
                                            },
                                        ]">Confirm Password</label>

                                        <i class="bi" :class="[
                                            showConfirm
                                                ? 'bi-eye-slash'
                                                : 'bi-eye',
                                            {
                                                'admin-modal__error':
                                                    passwordErrors.confirmPassword,
                                            },
                                        ]" @click="showConfirm = !showConfirm"></i>

                                        <span v-if="
                                            passwordErrors.confirmPassword
                                        " class="admin-modal__error">
                                            {{ passwordErrors.confirmPassword }}
                                        </span>
                                    </div>
                                </div>

                                <!-- <div class="admin-modal__checkbox">
                                <input 
                                    v-model="passwordForm.logoutOthers" 
                                    class="admin-modal__checkbox-input" 
                                    type="checkbox" 
                                    id="logoutOthers" 
                                />
                                <label class="admin-modal__checkbox-label" for="logoutOthers">
                                    Logout from other devices
                                </label>
                            </div> -->
                            </div>

                            <div class="admin-modal__footer">
                                <button class="admin-modal__btn admin-modal__btn--secondary" @click="closePasswordModal"
                                    type="button">
                                    Cancel
                                </button>
                                <button class="admin-modal__btn admin-modal__btn--primary" @click="updatePassword"
                                    type="button" :disabled="passwordForm.processing">
                                    {{
                                        passwordForm.processing
                                            ? "Updating..."
                                            : "Update"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showToast" class="admin-toast">
            <div :class="[
                'admin-toast__content',
                `admin-toast__content--${toastType}`,
            ]">
                <i :class="toastIcon"></i>

                <span class="admin-toast__text">
                    {{ toastMessage }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
/*  TOKENS  */
.admin-layout {
    min-height: 100vh;
    background: #f6f7fb;
    display: flex;
}

.admin-main {
    flex: 1;
    padding: 24px;
    overflow: auto;
}

/*  MOBILE HEADER  */
.admin-mobile-header {
    display: none;
    height: 52px;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    background: #ffffff;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    position: sticky;
    top: 0;
    z-index: 30;
}

.admin-mobile-toggle {
    width: 40px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.admin-mobile-toggle i {
    font-size: 18px;
    color: rgba(15, 23, 42, 0.75);
}

.admin-mobile-brand {
    font-weight: 700;
    color: rgba(15, 23, 42, 0.9);
}

.admin-mobile-spacer {
    width: 40px;
}

.admin-content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.admin-topbar {
    height: 60px;
    margin: 1.5rem;
    margin-bottom: 0;
    background: #ffffff;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    z-index: 20;
}

.admin-topbar__left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.admin-topbar__right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-topbar__icon-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(15, 23, 42, 0.65);
    cursor: pointer;
    transition: all 0.15s ease;
}

.admin-topbar__icon-btn:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #f57c00;
    border-color: rgba(255, 149, 0, 0.12);
}

.admin-topbar__icon-btn i {
    font-size: 16px;
}

.admin-topbar__divider {
    width: 1px;
    height: 20px;
    background: rgba(15, 23, 42, 0.08);
}

.admin-topbar__language-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: transparent;
    color: rgba(15, 23, 42, 0.7);
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.admin-topbar__language-btn:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #f57c00;
    border-color: rgba(255, 149, 0, 0.12);
}

.admin-topbar__language-flag {
    font-size: 14px;
}

.admin-topbar__language-btn i {
    font-size: 12px;
}

.admin-topbar__profile-menu {
    position: relative;
}

.admin-topbar__profile-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
}

.admin-topbar__avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f57c00, #ffb340);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 700;
    font-size: 14px;

    box-shadow:
        0 0 0 2px #ffffff,
        0 0 0 3px #d4d4d4,
        0 2px 6px rgba(0, 0, 0, 0.034);
}

/* Dropdown */
.admin-topbar__dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
    min-width: 280px;
    overflow: hidden;
    z-index: 1000;
    padding: 8px 0;
}

/* Header */
.admin-topbar__dropdown-header {
    padding: 16px 16px 12px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-topbar__dropdown-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f57c00, #ffb340);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
}

.admin-topbar__dropdown-info {
    flex: 1;
    min-width: 0;
}

.admin-topbar__dropdown-name {
    font-weight: 700;
    color: rgba(15, 23, 42, 0.9);
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.admin-topbar__dropdown-email {
    font-size: 13px;
    color: rgba(15, 23, 42, 0.55);
    margin-top: 2px;
}

.admin-topbar__dropdown-role-badge {
    font-size: 11px;
    font-weight: 600;
    background: rgba(255, 149, 0, 0.1);
    color: #f57c00;
    padding: 1px 6px;
    border-radius: 4px;
}

/* Card Containers */
.admin-topbar__dropdown-card {
    background: #ffffff;
    margin: 8px 8px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

/* Menu Items */
.admin-topbar__dropdown-item {
    width: 100%;
    padding: 12px 16px;
    border: none;
    background: transparent;
    color: rgba(15, 23, 42, 0.75);
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.15s ease;
}

.admin-topbar__dropdown-item:hover {
    background: rgba(255, 149, 0, 0.05);
    color: #f57c00;
}

.admin-topbar__dropdown-item i {
    font-size: 15px;
    width: 18px;
}

/* Logout styling */
.admin-topbar__dropdown-item--logout {
    color: #dc2626;
}

.admin-topbar__dropdown-item--logout:hover {
    background: rgba(220, 38, 38, 0.05);
    color: #dc2626;
}

/* Account Item */
.admin-topbar__account-item {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff7ed;
    /* light orange background like in image */
}

.admin-topbar__account-item--active {
    background: #fff7ed;
}

.admin-topbar__account-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f57c00;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.admin-topbar__account-info {
    flex: 1;
}

.admin-topbar__account-name {
    font-weight: 600;
    color: rgba(15, 23, 42, 0.9);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.admin-topbar__account-email {
    font-size: 12.5px;
    color: rgba(15, 23, 42, 0.55);
}

/* Add another account */
.admin-topbar__add-account {
    color: #f57c00;
}

.admin-topbar__add-account:hover {
    background: rgba(255, 149, 0, 0.08);
    color: #f57c00;
}

.admin-modal__form-group.floating {
    position: relative;
}

.admin-modal__label {
    position: absolute;
    top: 50%;
    left: 10px;
    transform: translateY(-50%);
    padding: 0 6px;
    font-size: 13px;
    color: #94a3b8;
    transition: 0.2s ease;
    pointer-events: none;
}

.admin-modal__label.fixed {
    top: -0.8px;
    font-size: 12px;
    left: 14px;
    font-weight: 600;
    background: linear-gradient(to bottom, #ffffff 50%, #f0f0f0 50%);
    color: #f57c00;
}

.admin-modal__label.admin-modal__error {
    top: 25%;
    left: 10px;
    color: #fa7e7eda;
}

i.admin-modal__error {
    top: 26% !important;
    color: #fa7e7eda !important;
}

/* move label up */
.admin-modal__input:focus+.admin-modal__label,
.admin-modal__input:not(:placeholder-shown)+.admin-modal__label {
    top: -2px;
    font-size: 12px;
    left: 14px;
    font-weight: 600;
    background: #fff;
    color: #f57c00;
}

.admin-modal__input:focus+.admin-modal__label.admin-modal__error,
.admin-modal__input:not(:placeholder-shown)+.admin-modal__label.admin-modal__error {
    color: #dc2626;
    top: -8px;
    left: 14px;
    background: linear-gradient(to bottom, #ffffff 50%, #fef2f2 50%);
}

.admin-modal__input::placeholder {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.admin-modal__input:focus:placeholder-shown::placeholder {
    opacity: 1;
    color: #94a3b8;
}

.password-field {
    position: relative;
}

.password-field i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 16px;
    color: #94a3b8;
    cursor: pointer;
    transition: 0.2s;
}

/* Phone input takes remaining space */
.phone-input {
    flex: 1;
}

.password-field i:hover {
    color: #f57c00;
}

/* add padding so text doesn't overlap icon */
.password-field input {
    padding-right: 36px;
}

/* Override autofill background */
input:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #0f172a !important;
    /* your text color */
    transition: background-color 5000s ease-in-out 0s;
}

/* On focus */
input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #0f172a !important;
}

/* Divider */
.admin-topbar__dropdown-divider {
    height: 1px;
    background: rgba(15, 23, 42, 0.08);
}

/* Remove default ugly disabled look */
.admin-modal__input:disabled {
    background: #f8fafc;
    /* soft background */
    color: #0f172a;
    /* keep text visible */
    opacity: 1;
    /* remove fade */
    cursor: not-allowed;
    border-color: #e2e8f0;
}

/* Make it look like display field */
.admin-modal__form-group.floating input:disabled+label {
    color: #64748b;
}

/* Optional: subtle dashed style */
.admin-modal__input:disabled {
    border-style: dashed;
}

/* wrapper */
.phone-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.phone-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* COUNTRY DROPDOWN */
.country-dropdown {
    position: relative;
    min-width: 80px !important;
    height: 47px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    background: #f0f0f0;
    font-size: 14px;
}

.country-dropdown i {
    font-size: 12px;
}

/* dropdown list */
.country-list {
    position: absolute;
    top: 110%;
    left: 0;
    font-size: 16px !important;
    width: 100px;
    background: #f0f0f0;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);

    max-height: 200px;
    overflow-y: auto;
    z-index: 9999;
}

.country-list::-webkit-scrollbar {
    width: 3px;
}

.country-list::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 1px;
}

.country-list::-webkit-scrollbar-track {
    background-color: transparent;
}

.country-item span,
.country-dropdown span {
    width: 22px;
    height: 16px;
    display: inline-block;
    margin-right: 5px;
}

.country-item,
.country-dropdown {
    font-family: "DM Sans", sans-serif;
    font-weight: 500;
    letter-spacing: 0.3px;
}

.country-item:hover {
    font-weight: 600;
    color: #0f172a;
}

/* items */
.country-item {
    padding: 10px;
    cursor: pointer;
    font-size: 13px;
}

.country-item:hover {
    background: #f1f5f9;
}

/* phone input adjust */
.phone-input {
    flex: 1;
}

/*toast message */
/* Wrapper */
.admin-toast {
    position: fixed;
    top: 18px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;

    animation:
        toast-slide-down 0.3s ease,
        toast-fade-out 0.4s ease 3.6s forwards;
}

/* Base capsule */
.admin-toast__content {
    display: flex;
    align-items: center;
    gap: 8px;

    padding: 8px 16px;
    border-radius: 999px;

    font-size: 13px;
    font-weight: 600;
    color: #fff;

    backdrop-filter: blur(6px);
}

.admin-toast__content--success {
    background: linear-gradient(135deg, #f57c00, #ff9a2f);

    box-shadow:
        0 6px 18px rgba(245, 124, 0, 0.35),
        0 0 0 1px rgba(255, 255, 255, 0.08);
}

.admin-toast__content--error {
    background: linear-gradient(135deg, #d32f2f, #ff5252);

    box-shadow:
        0 6px 18px rgba(211, 47, 47, 0.35),
        0 0 0 1px rgba(255, 255, 255, 0.08);
}

/* Icon */
.admin-toast__icon {
    font-size: 16px;
}

/* Animations */
@keyframes toast-slide-down {
    from {
        opacity: 0;
        transform: translate(-50%, -15px);
    }

    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}

@keyframes toast-fade-out {
    to {
        opacity: 0;
        transform: translate(-50%, -10px);
    }
}

@keyframes slide-up {
    from {
        transform: translateY(20px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-slide-up {
    animation: slide-up 0.3s ease forwards;
}

.admin-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.admin-modal {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
    width: 90%;
    max-width: 520px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: visible;
}

.admin-modal__header {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.admin-modal__title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    color: rgba(15, 23, 42, 0.9);
    font-size: 16px;
}

.admin-modal__icon {
    font-size: 26px;
    color: #f57c00;
}

.admin-modal__close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(15, 23, 42, 0.7);
    cursor: pointer;
}

.admin-modal__close:hover {
    background: rgba(15, 23, 42, 0.04);
    color: rgba(15, 23, 42, 0.9);
}

/* Body */
.admin-modal__body {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
    /* overflow-y: auto; */
}

/* Two Column Layout */
.admin-modal__two-column {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items: flex-start;
}

.admin-modal__form-group {
    display: flex;
    flex-direction: column;
}

.admin-modal__input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    color: #1e2937;
    font-size: 14px;
    transition: all 0.2s ease;
}

.admin-modal__input:focus {
    outline: none;
    border-color: #f57c00;
    box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.1);
}

.admin-modal__input::placeholder {
    color: #94a3b8;
}

/* Error Styles */
.admin-modal__input.error {
    border-color: #ef4444 !important;
    background-color: #fef2f2;
}

.admin-modal__error {
    color: #ef4444;
    font-size: 12.5px;
    margin-top: 5px;
    font-weight: 500;
}

/* Checkbox */
.admin-modal__checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: rgba(245, 124, 0, 0.04);
    border-radius: 8px;
    margin-top: 4px;
}

.center-box {
    width: 300px;
    margin: auto;
}

.admin-modal__checkbox-input {
    width: 18px;
    height: 18px;
    accent-color: #f57c00;
    cursor: pointer;
}

.admin-modal__checkbox-label {
    font-size: 14px;
    color: rgba(15, 23, 42, 0.75);
    cursor: pointer;
    margin: 0;
}

/* Footer */
.admin-modal__footer {
    padding: 16px 24px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.admin-modal__btn {
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.admin-modal__btn--secondary {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.admin-modal__btn--primary {
    background: #f57c00;
    color: #ffffff;
    border: 1px solid #f57c00;
}

.admin-modal__btn--primary:disabled {
    background: #f59e0b;
    cursor: not-allowed;
    opacity: 0.8;
}

/*  SIDEBAR  */
.admin-sidebar {
    width: 220px;
    background: #ffffff;
    border-right: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    transition: width 0.25s ease;
    position: sticky;
    top: 0;
    height: 100vh;
    z-index: 10;
}

.admin-sidebar--collapsed {
    width: 72px;
}

.admin-sidebar__header {
    display: flex;
    align-items: center;
    padding: 18px 14px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    position: relative;
}

.admin-sidebar__header {
    height: 68px;
    /* pick the height you want */
    min-height: 68px;
    max-height: 68px;
}

.admin-sidebar__brand {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.admin-sidebar__brand--hidden {
    opacity: 0;
    pointer-events: none;
    visibility: hidden;
}

.admin-sidebar__brand-name {
    font-weight: 800;
    color: rgba(15, 23, 42, 0.9);
    line-height: 1.1;
}

.admin-sidebar__brand-role {
    color: rgba(15, 23, 42, 0.55);
    font-weight: 600;
    margin-left: 10px;
    font-size: 20px;
}

.admin-sidebar__toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.admin-sidebar__toggle i {
    font-size: 14px;
    color: rgba(15, 23, 42, 0.7);
}

.admin-sidebar__nav {
    padding: 14px 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.admin-sidebar__item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 12px;
    border-radius: 12px;
    color: rgba(15, 23, 42, 0.65);
    text-decoration: none;
    border: 1px solid transparent;
    background: transparent;
    transition:
        background 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease;
}

.admin-sidebar__item:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #ff9500;
}

.admin-sidebar__item--active {
    background: rgba(255, 149, 0, 0.08);
    color: #ff9500;
    border-color: rgba(255, 149, 0, 0.14);
    font-weight: 700;
}

.admin-sidebar__item--button {
    cursor: pointer;
    text-align: left;
}

.admin-sidebar__icon {
    font-size: 18px;
    line-height: 1;
}

.admin-sidebar__label {
    font-size: 14px;
    font-weight: 600;
}

.admin-sidebar__label--hidden {
    display: none;
}

.admin-sidebar__tooltip {
    position: absolute;
    left: calc(100% + 10px);
    top: 50%;
    transform: translateY(-50%);
    background: rgba(15, 23, 42, 0.92);
    color: #ffffff;
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 10px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18);
}

.admin-sidebar__item:hover .admin-sidebar__tooltip {
    opacity: 1;
}

.admin-sidebar__item-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-sidebar__chevron {
    margin-left: auto;
    font-size: 14px;
    color: rgba(15, 23, 42, 0.55);
    transition: transform 0.2s ease;
}

.admin-sidebar__chevron--open {
    transform: rotate(180deg);
}

.admin-sidebar__submenu {
    margin-left: 34px;
    border-left: 1px solid rgba(255, 149, 0, 0.13);
    padding-left: 10px;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition:
        max-height 0.22s ease,
        opacity 0.18s ease,
        visibility 0s linear 0.18s;
    pointer-events: none;
}

.admin-sidebar__submenu--open {
    max-height: 2000px;
    opacity: 1;
    pointer-events: auto;
    visibility: visible;
}

.admin-sidebar__subitem {
    display: block;
    padding: 8px 6px 8px 15px;
    font-size: 13px;
    color: rgba(15, 23, 42, 0.58);
    text-decoration: none;
    border-radius: 10px;
    margin-bottom: 2px;
}

.admin-sidebar__subitem:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #ff9500;
}

.admin-sidebar__subitem--active {
    background: rgba(255, 149, 0, 0.08);
    color: #ff9500;
    font-weight: 700;
}

.admin-sidebar__footer {
    margin-top: auto;
    padding: 14px 14px 16px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
}

.admin-sidebar__user {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.admin-sidebar__user--hidden {
    display: none;
}

.admin-sidebar__user-label {
    font-size: 12px;
    color: rgba(15, 23, 42, 0.55);
}

.admin-sidebar__user-email {
    font-weight: 700;
    font-size: 13px;
    color: rgba(15, 23, 42, 0.82);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/*  FLYOUT  */
.admin-flyout {
    position: fixed;
    z-index: 40;
    width: 220px;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 14px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16);
    overflow: hidden;
}

.admin-flyout__header {
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 800;
    background: rgba(15, 23, 42, 0.04);
    color: rgba(15, 23, 42, 0.82);
}

.admin-flyout__list {
    display: flex;
    flex-direction: column;
}

.admin-flyout__item {
    padding: 10px 12px;
    text-decoration: none;
    color: rgba(15, 23, 42, 0.7);
    font-size: 13px;
    font-weight: 600;
}

.admin-flyout__item:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #ff9500;
}

.admin-flyout__item--active {
    background: rgba(255, 149, 0, 0.08);
    color: #ff9500;
    font-weight: 800;
}

/*  MOBILE OFFCANVAS  */
.admin-offcanvas-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.18s ease;
    z-index: 50;
}

.admin-offcanvas-backdrop--visible {
    opacity: 1;
    pointer-events: auto;
}

.admin-offcanvas {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 300px;
    max-width: 86vw;
    background: #ffffff;
    transform: translateX(-104%);
    transition: transform 0.22s ease;
    z-index: 60;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
}

.admin-offcanvas--open {
    transform: translateX(0);
}

.admin-offcanvas__header {
    padding: 16px 14px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.admin-offcanvas__brand {
    margin: 0;
    font-weight: 800;
    color: rgba(15, 23, 42, 0.9);
}

.admin-offcanvas__role {
    color: rgba(15, 23, 42, 0.55);
    font-size: 12px;
}

.admin-offcanvas__close {
    width: 40px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.admin-offcanvas__close i {
    font-size: 16px;
    color: rgba(15, 23, 42, 0.7);
}

.admin-offcanvas__body {
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    overflow: auto;
}

.admin-offcanvas__item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 12px;
    border-radius: 12px;
    text-decoration: none;
    color: rgba(15, 23, 42, 0.7);
    font-weight: 700;
    border: 1px solid transparent;
}

.admin-offcanvas__item i {
    font-size: 18px;
}

.admin-offcanvas__item:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #ff9500;
}

.admin-offcanvas__item--active {
    background: rgba(255, 149, 0, 0.08);
    color: #ff9500;
    border-color: rgba(255, 149, 0, 0.14);
}

.admin-offcanvas__accordion-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 12px;
    border-radius: 12px;
    border: 1px solid transparent;
    background: #ffffff;
    color: rgba(15, 23, 42, 0.7);
    font-weight: 800;
    cursor: pointer;
}

.admin-offcanvas__accordion-btn:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #ff9500;
}

.admin-offcanvas__accordion-btn--active {
    background: rgba(255, 149, 0, 0.08);
    color: #ff9500;
    border-color: rgba(255, 149, 0, 0.14);
}

.admin-offcanvas__accordion-btn i {
    font-size: 18px;
}

.admin-offcanvas__chevron {
    margin-left: auto;
    transition: transform 0.2s ease;
    color: rgba(15, 23, 42, 0.55);
}

.admin-offcanvas__chevron--open {
    transform: rotate(180deg);
}

.admin-offcanvas__submenu {
    margin-left: 34px;
    border-left: 1px solid rgba(15, 23, 42, 0.08);
    padding-left: 12px;
    max-height: 0;
    opacity: 0;

    overflow: hidden;
    transition:
        max-height 0.22s ease,
        opacity 0.18s ease;
}

.admin-offcanvas__submenu--open {
    max-height: 5000px;
    opacity: 1;
}

.admin-offcanvas__subitem {
    display: block;
    padding: 10px 6px;
    border-radius: 10px;
    text-decoration: none;
    color: rgba(15, 23, 42, 0.62);
    font-weight: 700;
}

.admin-offcanvas__subitem:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #ff9500;
}

.admin-offcanvas__subitem--active {
    background: rgba(255, 149, 0, 0.08);
    color: #ff9500;
}

.admin-offcanvas__footer {
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    padding: 12px 14px;
}

.admin-offcanvas__email {
    display: block;
    font-weight: 800;
    font-size: 13px;
    color: rgba(15, 23, 42, 0.78);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 767.98px) {
    .admin-mobile-header {
        display: flex;
    }

    .center-box {
        width: 100%;
        margin: auto;
    }

    .admin-sidebar {
        display: none;
    }

    .admin-main {
        padding: 0;
    }

    .admin-layout {
        flex-direction: column;
    }
}

/*  COLLAPSED ICON CENTER FIX  */

.admin-sidebar--collapsed .admin-sidebar__item,
.admin-sidebar--collapsed .admin-sidebar__item--button {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
}

/* Ensure icon is perfectly centered */
.admin-sidebar--collapsed .admin-sidebar__icon,
.admin-sidebar--collapsed .admin-sidebar__item i {
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

/*  DESKTOP TOPNAV  */
.admin-topnav {
    position: sticky;
    top: 0;
    z-index: 20;
    height: 56px;

    /* Transparent white */
    background: rgba(255, 255, 255, 0.342);

    /* Blur effect */
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);

    border: 1px solid rgba(255, 255, 255, 0.35);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 18px;
    margin: 15px;
    border-radius: 12px;
}

.admin-topnav__left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-topnav__title {
    font-weight: 800;
    color: rgba(15, 23, 42, 0.9);
    letter-spacing: 0.2px;
}

.admin-topnav__right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-topnav__btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.admin-topnav__btn i {
    font-size: 18px;
    color: rgba(15, 23, 42, 0.7);
}

.admin-topnav__btn:hover {
    background: rgba(15, 23, 42, 0.04);
}

.admin-topnav__user {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 12px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: rgba(15, 23, 42, 0.02);
}

.admin-topnav__user i {
    font-size: 20px;
    color: rgba(15, 23, 42, 0.75);
}

.admin-topnav__email {
    font-size: 13px;
    font-weight: 700;
    color: rgba(15, 23, 42, 0.75);
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Hide desktop topnav on mobile*/
@media (max-width: 767.98px) {
    .admin-topnav {
        display: none;
    }
}

.admin-right {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

/*  RESPONSIVE MODAL IMPROVEMENTS  */

.admin-modal {
    width: 90%;
    max-width: 520px;
    margin: 20px auto;
}

.admin-modal__form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Mobile adjustments */
@media (max-width: 640px) {
    .admin-modal {
        width: 95%;
        max-width: none;
        margin: 12px auto;
        border-radius: 14px;
    }

    .admin-modal__header {
        padding: 18px 20px;
    }

    .admin-modal__body {
        padding: 20px;
        gap: 20px;
    }

    .admin-modal__footer {
        padding: 16px 20px;
    }

    .admin-modal__form-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .phone-group {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }

    .country-dropdown {
        min-width: auto;
        justify-content: space-between;
    }

    .country-list {
        width: 100%;
        left: 0;
    }

    .admin-modal__two-column {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .admin-modal__input {
        padding: 14px 16px;
        font-size: 16px;
    }

    .admin-modal__label {
        font-size: 14px;
    }
}

/* Extra small screens */
@media (max-width: 480px) {
    .admin-modal__body {
        padding: 18px 16px;
    }

    .admin-modal__header {
        padding: 16px 18px;
    }
}
</style>
