<template>
    <div>

        <Head :title="page.props.appTitle" />

        <div class="admin-mobile-header">
            <button class="admin-mobile-toggle" type="button" @click="mobileOpen = true" aria-label="Open menu">
                <i class="bi bi-list" />
            </button>

            <div class="admin-mobile-brand">{{ page.props.tenant?.name }}</div>
            <div class="admin-mobile-spacer" />
        </div>

        <div class="admin-root vendoradmin" :class="{ 'admin-root--pos': isPosViewer }">
            <div class="admin-layout">
                <button ref="toggleRef" class="admin-sidebar__toggle" type="button" @click="toggleSidebar"
                    :class="{ 'collapsed': collapsed }" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                    <i :class="collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left'" />
                </button>

                <aside ref="sidebarRef" class="admin-sidebar" :class="{ 'admin-sidebar--collapsed': collapsed }">
                    <div class="admin-sidebar__header">
                        <div class="admin-sidebar__brand" :class="{ 'collapsed': collapsed }">

                            <Link :href="route('vendor.dashboard')" class="admin-sidebar__brand-content" :class="{ 'collapsed': collapsed }" style="text-decoration: none;">

                                <!-- Logo ALWAYS visible -->
                                <img v-if="page.props.tenant?.logo_url" :src="page.props.tenant.logo_url"
                                    class="admin-sidebar__brand-logo" alt="Tenant Logo" />

                                <!-- Name hides when collapsed -->
                                <div class="admin-sidebar__brand-name" :class="{ 'is-hidden': collapsed }">
                                    {{ page.props.tenant?.name }}
                                </div>

                            </Link>

                        </div>

                        <!-- <button class="admin-sidebar__toggle" type="button" @click="toggleSidebar"
                            :class="{ 'collapsed': collapsed }"
                            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                            <i :class="collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left'" />
                        </button> -->
                    </div>
                    <nav class="admin-sidebar__nav">
                        <Link v-if="can('dashboard.view')" :href="route('vendor.dashboard')" class="admin-sidebar__item"
                            :class="{
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



                        <Link v-if="can('branches.view')" :href="route('vendor.branches.index')" class="admin-sidebar__item"
                            :class="{
                                'admin-sidebar__item--active': isBranches,
                            }">
                            <i class="bi bi-diagram-3 admin-sidebar__icon" />
                            <span class="admin-sidebar__label" :class="{
                                'admin-sidebar__label--hidden': collapsed,
                            }">
                                Branches
                            </span>
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Branches</span>
                        </Link>

                        <Link v-if="can('customers.view')" :href="route('vendor.customers.index')" class="admin-sidebar__item"
                            :class="{
                                'admin-sidebar__item--active': isCustomers,
                            }">
                            <i class="bi bi-person-lines-fill admin-sidebar__icon" />
                            <span class="admin-sidebar__label" :class="{
                                'admin-sidebar__label--hidden': collapsed,
                            }">
                                Customers
                            </span>
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Customers</span>
                        </Link>
                        <button v-if="
                            can('sales-orders.view') ||
                            can('zosales-invoicesnes.view') ||
                            can('sales-payments.view') ||
                            can('sales-reasons.view')
                        " class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isSalesMenu }" type="button" @click="
                                collapsed
                                    ? openFlyout('sales', $event)
                                    : toggleMenu('sales')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-basket admin-sidebar__icon" />
                                <span class="admin-sidebar__label"
                                    :class="{ 'admin-sidebar__label--hidden': collapsed }">
                                    Sales
                                </span>
                            </div>

                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'sales' }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Sales</span>
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': !collapsed && openMenu === 'sales' }">
                            <Link v-if="can('sales-orders.view')" :href="route('vendor.sales.orders.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesOrders }">
                                Orders
                            </Link>

                            <Link v-if="can('sales-invoices.view')" :href="route('vendor.sales.invoices.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesInvoices }">
                                Invoices
                            </Link>

                            <Link v-if="can('sales-payments.view')" :href="route('vendor.sales.payments.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesPayments }">
                                Payments
                            </Link>

                            <Link v-if="can('sales-reasons.view')" :href="route('vendor.sales.reasons.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesReasons }">
                                Reasons
                            </Link>
                        </div>
                        <!-- <button v-if="
                            can('floors.view') ||
                            can('zones.view') ||
                            can('tables.view') ||
                            can('table-merges.view')
                        " class="admin-sidebar__item admin-sidebar__item--button" :class="{
                            'admin-sidebar__item--active':
                                isSeatingPlanMenu,
                        }" type="button" @click="
                            collapsed
                                ? openFlyout('seating-plan', $event)
                                : toggleMenu('seating-plan')
                            ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-building admin-sidebar__icon" />
                                <span class="admin-sidebar__label" :class="{
                                    'admin-sidebar__label--hidden':
                                        collapsed,
                                }">
                                    Seating Plan
                                </span>
                            </div>

                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron" :class="{
                                'admin-sidebar__chevron--open':
                                    openMenu === 'seating-plan',
                            }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Seating Plan</span>
                        </button> -->
<!-- 
                        <div class="admin-sidebar__submenu" :class="{
                            'admin-sidebar__submenu--open':
                                !collapsed && openMenu === 'seating-plan',
                        }">
                            <Link v-if="can('floors.view')" :href="route('vendor.floors.index')" class="admin-sidebar__subitem"
                                :class="{
                                    'admin-sidebar__subitem--active': isFloors,
                                }">
                                Floors
                            </Link>

                            <Link v-if="can('zones.view')" :href="route('vendor.zones.index')" class="admin-sidebar__subitem"
                                :class="{
                                    'admin-sidebar__subitem--active': isZones,
                                }">
                                Zones
                            </Link>

                            <Link v-if="can('tables.view')" :href="route('vendor.tables.index')" class="admin-sidebar__subitem"
                                :class="{
                                    'admin-sidebar__subitem--active': isTables,
                                }">
                                Tables
                            </Link>

                            <Link v-if="can('table-merges.view')" :href="route('vendor.table-merges.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isTableMerges,
                                }">
                                Table Merges
                            </Link>
                        </div> -->

                        <button v-if="
                            can('menus.view') ||
                            can('online-menus.view') ||
                            can('categories.view') ||
                            can('options.view') ||
                            can('products.view')
                        " class="admin-sidebar__item admin-sidebar__item--button" :class="{
                            'admin-sidebar__item--active': isMenusMenu,
                        }" type="button" @click="
                            collapsed
                                ? openFlyout('menus', $event)
                                : toggleMenu('menus')
                            ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-list-ul admin-sidebar__icon" />
                                <span class="admin-sidebar__label" :class="{
                                    'admin-sidebar__label--hidden':
                                        collapsed,
                                }">
                                    Menus
                                </span>
                            </div>

                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron" :class="{
                                'admin-sidebar__chevron--open':
                                    openMenu === 'menus',
                            }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Menus</span>
                        </button>

                        <div class="admin-sidebar__submenu" :class="{
                            'admin-sidebar__submenu--open':
                                !collapsed && openMenu === 'menus',
                        }">
                            <!-- <Link v-if="can('online-menus.view')" :href="route('vendor.online-menus.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isOnlineMenus,
                                }">
                                Online menus
                            </Link> -->

                            <Link v-if="can('categories.view')" :href="route('vendor.categories.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isCategories,
                                }">
                                Categories
                            </Link>
                            <Link v-if="can('products.view')" :href="route('vendor.brands.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isBrands,
                                }">
                                Brands
                            </Link>
                            <!-- <Link v-if="can('options.view')" :href="route('vendor.options.index')" class="admin-sidebar__subitem"
                                :class="{
                                    'admin-sidebar__subitem--active': isOptions,
                                }">
                                Options
                            </Link> -->
                            <Link v-if="can('products.view')" :href="route('vendor.products.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isProducts,
                                }">
                                Products
                            </Link>
                        </div>

                        <button
                            v-if="can('pos.view') || can('pos-registers.view') || can('pos-sessions.view') || can('pos-cash-movements.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isPosMenu }" type="button" @click="
                                collapsed
                                    ? openFlyout('pos', $event)
                                    : toggleMenu('pos')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-receipt-cutoff admin-sidebar__icon" />
                                <span class="admin-sidebar__label"
                                    :class="{ 'admin-sidebar__label--hidden': collapsed }">POS</span>
                            </div>
                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'pos' }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">POS</span>
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': !collapsed && openMenu === 'pos' }">
                            <Link v-if="can('pos-registers.view')" :href="route('vendor.pos.registers.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPosRegisters }">
                                Registers
                            </Link>
                            <Link v-if="can('pos-sessions.view')" :href="route('vendor.pos.sessions.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPosSessions }">
                                Sessions
                            </Link>
                            <Link v-if="can('pos-cash-movements.view')" :href="route('vendor.pos.cash-movements.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPosCashMovements }">
                                Cash Movements
                            </Link>
                        </div>
                        <button v-if="
                            can('inventory.view') ||
                            can('products.view') ||
                            can('units.view') ||
                            can('suppliers.view')
                        " class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{
                                'admin-sidebar__item--active': isInventoryMenu,
                            }" type="button" @click="
                                collapsed
                                    ? openFlyout('inventory', $event)
                                    : toggleMenu('inventory')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-box-seam admin-sidebar__icon" />
                                <span class="admin-sidebar__label" :class="{
                                    'admin-sidebar__label--hidden':
                                        collapsed,
                                }">
                                    Inventory
                                </span>
                            </div>

                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron" :class="{
                                'admin-sidebar__chevron--open':
                                    openMenu === 'inventory',
                            }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Inventory</span>
                        </button>

                        <div class="admin-sidebar__submenu" :class="{
                            'admin-sidebar__submenu--open':
                                !collapsed && openMenu === 'inventory',
                        }">
                            <Link v-if="can('inventory.view')" :href="route('vendor.inventory-analytics.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isInventoryAnalytics,
                                }">
                                Analytics
                            </Link>

                            <Link v-if="can('products.view')" :href="route('vendor.stock-management.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isStockManagement,
                                }">
                                Stock Management
                            </Link>

                            <Link v-if="can('units.view')" :href="route('vendor.units.index')" class="admin-sidebar__subitem"
                                :class="{
                                    'admin-sidebar__subitem--active': isUnits,
                                }">
                                Units
                            </Link>

                            <Link v-if="can('suppliers.view')" :href="route('vendor.suppliers.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        isSuppliers,
                                }">
                                Suppliers
                            </Link>

                        </div>
                        <button
                            v-if="can('promotions.view') || can('promotion-discounts.view') || can('promotion-vouchers.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isPromotionsMenu }" type="button" @click="
                                collapsed
                                    ? openFlyout('promotions', $event)
                                    : toggleMenu('promotions')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-bullseye admin-sidebar__icon" />
                                <span class="admin-sidebar__label"
                                    :class="{ 'admin-sidebar__label--hidden': collapsed }">Promotions</span>
                            </div>
                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'promotions' }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Promotions</span>
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': !collapsed && openMenu === 'promotions' }">
                            <Link v-if="can('promotion-discounts.view')"
                                :href="route('vendor.promotions.discounts.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPromotionDiscounts }">
                                Discounts
                            </Link>
                            <Link v-if="can('promotion-vouchers.view')"
                                :href="route('vendor.promotions.vouchers.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPromotionVouchers }">
                                Vouchers
                            </Link>
                        </div>



                        <button
                            v-if="can('loyalty.view') || can('loyalty-programs.view') || can('loyalty-tiers.view') || can('loyalty-rewards.view') || can('loyalty-promotions.view') || can('loyalty-customers.view') || can('loyalty-gifts.view') || can('loyalty-transactions.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isLoyaltyMenu }" type="button" @click="
                                collapsed
                                    ? openFlyout('loyalty', $event)
                                    : toggleMenu('loyalty')
                                ">
                            <div class="admin-sidebar__item-content">
<i class="bi bi-stars admin-sidebar__icon" />
                                <span class="admin-sidebar__label"
                                    :class="{ 'admin-sidebar__label--hidden': collapsed }">Loyalty</span>
                            </div>
                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'loyalty' }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Loyalty</span>
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': !collapsed && openMenu === 'loyalty' }">
                            <Link v-if="can('loyalty-programs.view')" :href="route('vendor.loyalty.programs.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isLoyaltyPrograms }">Programs</Link>
                            <Link v-if="can('loyalty-tiers.view')" :href="route('vendor.loyalty.tiers.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isLoyaltyTiers }">Tiers</Link>
                            <Link v-if="can('loyalty-rewards.view')" :href="route('vendor.loyalty.rewards.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isLoyaltyRewards }">Rewards</Link>
                            <Link v-if="can('loyalty-promotions.view')" :href="route('vendor.loyalty.promotions.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isLoyaltyPromotions }">Promotions</Link>
                            <Link v-if="can('loyalty-customers.view')" :href="route('vendor.loyalty.customers.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isLoyaltyCustomers }">Customers</Link>
                            <Link v-if="can('loyalty-gifts.view')" :href="route('vendor.loyalty.gifts.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isLoyaltyGifts }">Gifts</Link>
                            <Link v-if="can('loyalty-transactions.view')"
                                :href="route('vendor.loyalty.transactions.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isLoyaltyTransactions }">Transactions</Link>
                        </div>

                        <button
                            v-if="can('gift-cards.view') || can('gift-card-analytics.view') || can('gift-card-transactions.view') || can('gift-card-batches.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isGiftCardsMenu }" type="button" @click="
                                collapsed
                                    ? openFlyout('gift-cards', $event)
                                    : toggleMenu('gift-cards')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-gift admin-sidebar__icon" />
                                <span class="admin-sidebar__label"
                                    :class="{ 'admin-sidebar__label--hidden': collapsed }">Gift Cards</span>
                            </div>
                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'gift-cards' }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Gift Cards</span>
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': !collapsed && openMenu === 'gift-cards' }">
                            <Link v-if="can('gift-card-analytics.view')" :href="route('vendor.gift-cards.analytics')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCardAnalytics }">
                                Analytics
                            </Link>
                            <Link v-if="can('gift-cards.view')" :href="route('vendor.gift-cards.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCards }">
                                Cards
                            </Link>
                            <Link v-if="can('gift-card-transactions.view')"
                                :href="route('vendor.gift-cards.transactions.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCardTransactions }">
                                Transactions
                            </Link>
                            <Link v-if="can('gift-card-batches.view')" :href="route('vendor.gift-cards.batches.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCardBatches }">
                                Batches
                            </Link>
                        </div>

                        <div
                            v-if="!collapsed && (
                                can('users.view') ||
                                can('roles-permissions.view') ||
                                can('activities.view') ||
                                can('activity-logs.view') ||
                                can('authentication-logs.view') ||
                                can('reports.view') ||
                                can('settings.view')
                            )"
                            class="admin-sidebar__section-label"
                        >
                            System
                        </div>

                        <button v-if="
                            can('users.view') ||
                            can('roles-permissions.view')" class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{
                                'admin-sidebar__item--active': isUsersMenu,
                            }" type="button" @click="
                                collapsed
                                    ? openFlyout('users', $event)
                                    : toggleMenu('users')
                                ">
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-people admin-sidebar__icon" />
                                <span class="admin-sidebar__label" :class="{
                                    'admin-sidebar__label--hidden':
                                        collapsed,
                                }">
                                    Users
                                </span>
                            </div>

                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron" :class="{
                                'admin-sidebar__chevron--open':
                                    openMenu === 'users',
                            }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Users</span>
                        </button>

                        <div class="admin-sidebar__submenu" :class="{
                            'admin-sidebar__submenu--open':
                                !collapsed && openMenu === 'users',
                        }">
                            <Link v-if="can('users.view')" :href="route('vendor.users.index')" class="admin-sidebar__subitem"
                                :class="{
                                    'admin-sidebar__subitem--active': isUsers,
                                }">
                                Users
                            </Link>

                            <Link v-if="can('roles-permissions.view')" :href="route('vendor.roles.index')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active': isRoles,
                                }">
                                Roles
                            </Link>
                        </div>
                        <button
                            v-if="can('activities.view') || can('activity-logs.view') || can('authentication-logs.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isActivitiesMenu }"
                            type="button"
                            @click="collapsed ? openFlyout('activities', $event) : toggleMenu('activities')"
                        >
                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-clock-history admin-sidebar__icon" />
                                <span class="admin-sidebar__label" :class="{ 'admin-sidebar__label--hidden': collapsed }">
                                    Activities
                                </span>
                            </div>
                            <i v-if="!collapsed" class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'activities' }" />
                            <span v-if="collapsed" class="admin-sidebar__tooltip">Activities</span>
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': !collapsed && openMenu === 'activities' }">
                            <Link v-if="can('activity-logs.view')" :href="route('vendor.activities.activity-logs.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isActivityLogs }">
                                Activity Logs
                            </Link>
                            <Link v-if="can('authentication-logs.view')" :href="route('vendor.activities.authentication-logs.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isAuthenticationLogs }">
                                Authentication Logs
                            </Link>
                        </div>

                        <Link
    v-if="can('reports.view')"
    :href="route('vendor.reports.index')"
    class="admin-sidebar__item"
    :class="{ 'admin-sidebar__item--active': isReports }"
>
    <i class="bi bi-graph-up-arrow admin-sidebar__icon" />
    <span
        class="admin-sidebar__label"
        :class="{ 'admin-sidebar__label--hidden': collapsed }"
    >
        Reports
    </span>
    <span v-if="collapsed" class="admin-sidebar__tooltip">Reports</span>
</Link>
                        <button v-if="can('settings.view')" class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{
                                'admin-sidebar__item--active': isSettingsMenu,
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

                        <div class="admin-sidebar__submenu" :class="{
                            'admin-sidebar__submenu--open':
                                !collapsed && openMenu === 'settings',
                        }">
                            <Link v-if="can('settings-currency.view')" :href="route('vendor.settings.currency')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        pageStartsWithRoute('vendor.settings.currency'),
                                }">
                                General
                            </Link>

                            <!-- <Link v-if="can('settings-kitchen-alert.view')" :href="route('vendor.settings.kitchen-alert')"
                                class="admin-sidebar__subitem" :class="{
                                    'admin-sidebar__subitem--active':
                                        pageStartsWithRoute('vendor.settings.kitchen-alert'),
                                }">
                                Kitchen Alert
                            </Link> -->

                            <!-- <Link v-if="can('pms.view')" :href="route('vendor.settings.pms')" class="admin-sidebar__subitem" :class="{
                                'admin-sidebar__subitem--active': isSettingsPms,
                            }">
                                PMS Integration
                            </Link> -->

                            <Link v-if="can('taxes.view')" :href="route('vendor.taxes.index')" class="admin-sidebar__subitem"
                                :class="{
                                    'admin-sidebar__subitem--active': isTaxes,
                                }">
                                Taxes
                            </Link>
                        </div>
                    </nav>

                    <div class="admin-sidebar__footer">
                        <div class="admin-sidebar__user" :class="{
                            'admin-sidebar__user--hidden': collapsed,
                        }">
                            <div class="admin-sidebar__user-label">
                                Logged in as
                            </div>
                            <div class="admin-sidebar__user-email">
                                {{ user?.email }}
                            </div>
                        </div>
                    </div>
                </aside>

                <!--  DESKTOP FLYOUT  -->
                <div v-if="collapsed && flyoutKey" ref="flyoutRef" class="admin-flyout"
                    :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }">
                    <div class="admin-flyout__list">
                        <template v-for="(item, index) in flyoutMenus[flyoutKey]?.items" :key="index">
                            <Link v-if="
                                (!item.view && !item.views) ||
                                (item.view && can(item.view)) ||
                                (item.views && item.views.some(view => can(view)))
                            " :href="typeof item.href === 'function'
                                ? item.href()
                                : item.href" class="admin-flyout__item" :class="{
                                    'admin-flyout__item--active': item.active(),
                                }">
                                {{ item.label }}
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="admin-offcanvas-backdrop" :class="{ 'admin-offcanvas-backdrop--visible': mobileOpen }"
                    @click="closeMobile" />

                <section class="admin-offcanvas" :class="{ 'admin-offcanvas--open': mobileOpen }"
                    aria-label="Mobile menu">
                    <div class="admin-offcanvas__header">
                        <Link :href="route('vendor.dashboard')" class="admin-offcanvas__brand-wrapper" style="text-decoration: none;">

                            <img v-if="page.props.tenant?.logo_url" :src="page.props.tenant.logo_url"
                                class="admin-offcanvas__brand-logo" alt="Tenant Logo" />

                            <h6 class="admin-offcanvas__brand">
                                {{ page.props.tenant?.name }}
                            </h6>

                        </Link>

                        <button class="admin-offcanvas__close" type="button" @click="closeMobile"
                            aria-label="Close menu">
                            <i class="bi bi-x-lg" />
                        </button>
                    </div>

                    <div class="admin-offcanvas__body">

                        <!-- DASHBOARD -->
                        <Link v-if="can('dashboard.view')" :href="route('vendor.dashboard')" class="admin-sidebar__item"
                            :class="{ 'admin-sidebar__item--active': isDashboard }">
                            <i class="bi bi-speedometer2 admin-sidebar__icon" />
                            <span class="admin-sidebar__label">Dashboard</span>
                        </Link>

                        <!-- BRANCHES -->
                        <Link v-if="can('branches.view')" :href="route('vendor.branches.index')" class="admin-sidebar__item"
                            :class="{ 'admin-sidebar__item--active': isBranches }">
                            <i class="bi bi-diagram-3 admin-sidebar__icon" />
                            <span class="admin-sidebar__label">Branches</span>
                        </Link>

                        <Link v-if="can('customers.view')" :href="route('vendor.customers.index')" class="admin-sidebar__item"
                            :class="{ 'admin-sidebar__item--active': isCustomers }">
                            <i class="bi bi-person-lines-fill admin-sidebar__icon" />
                            <span class="admin-sidebar__label">Customers</span>
                        </Link>

                        <!-- SALES -->
                        <button v-if="can('sales.view')" class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isSalesMenu }" type="button"
                            @click="toggleMenu('sales')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-basket admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Sales</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'sales' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'sales' }">

                            <Link v-if="can('sales-orders.view')" :href="route('vendor.sales.orders.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesOrders }">
                                Orders
                            </Link>

                            <Link v-if="can('sales-invoices.view')" :href="route('vendor.sales.invoices.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesInvoices }">
                                Invoices
                            </Link>

                            <Link v-if="can('sales-payments.view')" :href="route('vendor.sales.payments.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesPayments }">
                                Payments
                            </Link>

                            <Link v-if="can('sales-reasons.view')" :href="route('vendor.sales.reasons.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSalesReasons }">
                                Reasons
                            </Link>
                        </div>

                        <!-- SEATING PLAN -->
                        <!-- <button
                            v-if="can('floors.view') || can('zones.view') || can('tables.view') || can('table-merges.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isSeatingPlanMenu }" type="button"
                            @click="toggleMenu('seating-plan')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-building admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Seating Plan</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'seating-plan' }" />
                        </button> -->

                        <!-- <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'seating-plan' }">

                            <Link v-if="can('floors.view')" :href="route('vendor.floors.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isFloors }">
                                Floors
                            </Link>

                            <Link v-if="can('zones.view')" :href="route('vendor.zones.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isZones }">
                                Zones
                            </Link>

                            <Link v-if="can('tables.view')" :href="route('vendor.tables.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isTables }">
                                Tables
                            </Link>

                            <Link v-if="can('table-merges.view')" :href="route('vendor.table-merges.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isTableMerges }">
                                Table Merges
                            </Link>
                        </div> -->

                        <!-- MENUS -->
                        <button
                            v-if="can('menus.view') || can('online-menus.view') || can('categories.view') || can('options.view') || can('products.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isMenusMenu }" type="button"
                            @click="toggleMenu('menus')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-list-ul admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Menus</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'menus' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'menus' }">

                            <Link v-if="can('online-menus.view')" :href="route('vendor.online-menus.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isOnlineMenus }">
                                Online menus
                            </Link>

                            <Link v-if="can('categories.view')" :href="route('vendor.categories.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isCategories }">
                                Categories
                            </Link>

                            <Link v-if="can('products.view')" :href="route('vendor.brands.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isBrands }">
                                Brands
                            </Link>

                            <Link v-if="can('options.view')" :href="route('vendor.options.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isOptions }">
                                Options
                            </Link>

                            <Link v-if="can('products.view')" :href="route('vendor.products.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isProducts }">
                                Products
                            </Link>
                        </div>

                        <!-- POS -->
                        <button
                            v-if="can('pos.view') || can('pos-registers.view') || can('pos-sessions.view') || can('pos-cash-movements.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isPosMenu }" type="button"
                            @click="toggleMenu('pos')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-receipt-cutoff admin-sidebar__icon" />
                                <span class="admin-sidebar__label">POS</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'pos' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'pos' }">

                            <Link v-if="can('pos-registers.view')" :href="route('vendor.pos.registers.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPosRegisters }">
                                Registers
                            </Link>

                            <Link v-if="can('pos-sessions.view')" :href="route('vendor.pos.sessions.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPosSessions }">
                                Sessions
                            </Link>

                            <Link v-if="can('pos-cash-movements.view')" :href="route('vendor.pos.cash-movements.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isPosCashMovements }">
                                Cash Movements
                            </Link>
                        </div>

                        <!-- INVENTORY -->
                        <button v-if="
                            can('inventory.view') ||
                            can('products.view') ||
                            can('units.view') ||
                            can('suppliers.view')
                        " class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isInventoryMenu }" type="button"
                            @click="toggleMenu('inventory')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-box-seam admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Inventory</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'inventory' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'inventory' }">
                            <Link v-if="can('inventory.view')" :href="route('vendor.inventory-analytics.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isInventoryAnalytics }">
                                Analytics
                            </Link>

                            <Link v-if="can('products.view')" :href="route('vendor.stock-management.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isStockManagement }">
                                Stock Management
                            </Link>

                            <Link v-if="can('units.view')" :href="route('vendor.units.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isUnits }">
                                Units
                            </Link>

                            <Link v-if="can('suppliers.view')" :href="route('vendor.suppliers.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isSuppliers }">
                                Suppliers
                            </Link>

                        </div>

                        <!-- GIFT CARDS -->
                        <button
                            v-if="can('gift-cards.view') || can('gift-card-analytics.view') || can('gift-card-transactions.view') || can('gift-card-batches.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isGiftCardsMenu }" type="button"
                            @click="toggleMenu('gift-cards')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-gift admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Gift Cards</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'gift-cards' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'gift-cards' }">

                            <Link v-if="can('gift-card-analytics.view')" :href="route('vendor.gift-cards.analytics')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCardAnalytics }">
                                Analytics
                            </Link>

                            <Link v-if="can('gift-cards.view')" :href="route('vendor.gift-cards.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCards }">
                                Cards
                            </Link>

                            <Link v-if="can('gift-card-transactions.view')"
                                :href="route('vendor.gift-cards.transactions.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCardTransactions }">
                                Transactions
                            </Link>

                            <Link v-if="can('gift-card-batches.view')" :href="route('vendor.gift-cards.batches.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isGiftCardBatches }">
                                Batches
                            </Link>
                        </div>

                        <div
                            v-if="
                                can('users.view') ||
                                can('roles-permissions.view') ||
                                can('activities.view') ||
                                can('activity-logs.view') ||
                                can('authentication-logs.view') ||
                                can('reports.view') ||
                                can('settings.view')
                            "
                            class="admin-sidebar__section-label"
                        >
                            System
                        </div>

                        <!-- USERS -->
                        <button v-if="can('users.view') || can('roles-permissions.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isUsersMenu }" type="button"
                            @click="toggleMenu('users')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-people admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Users</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'users' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'users' }">

                            <Link v-if="can('users.view')" :href="route('vendor.users.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isUsers }">
                                Users
                            </Link>

                            <Link v-if="can('roles-permissions.view')" :href="route('vendor.roles.index')"
                                class="admin-sidebar__subitem" :class="{ 'admin-sidebar__subitem--active': isRoles }">
                                Roles
                            </Link>
                        </div>

                        <!-- ACTIVITIES -->
                        <button
                            v-if="can('activities.view') || can('activity-logs.view') || can('authentication-logs.view')"
                            class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isActivitiesMenu }" type="button"
                            @click="toggleMenu('activities')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-clock-history admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Activities</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'activities' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'activities' }">

                            <Link v-if="can('activity-logs.view')"
                                :href="route('vendor.activities.activity-logs.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isActivityLogs }">
                                Activity Logs
                            </Link>

                            <Link v-if="can('authentication-logs.view')"
                                :href="route('vendor.activities.authentication-logs.index')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isAuthenticationLogs }">
                                Authentication Logs
                            </Link>
                        </div>

                        <Link
                            v-if="can('reports.view')"
                            :href="route('vendor.reports.index')"
                            class="admin-sidebar__item"
                            :class="{ 'admin-sidebar__item--active': isReports }"
                        >
                            <i class="bi bi-graph-up-arrow admin-sidebar__icon" />
                            <span class="admin-sidebar__label">Reports</span>
                        </Link>

                        <!-- SETTINGS -->
                        <button v-if="can('settings.view')" class="admin-sidebar__item admin-sidebar__item--button"
                            :class="{ 'admin-sidebar__item--active': isSettingsMenu }" type="button"
                            @click="toggleMenu('settings')">

                            <div class="admin-sidebar__item-content">
                                <i class="bi bi-gear admin-sidebar__icon" />
                                <span class="admin-sidebar__label">Settings</span>
                            </div>

                            <i class="bi bi-chevron-down admin-sidebar__chevron"
                                :class="{ 'admin-sidebar__chevron--open': openMenu === 'settings' }" />
                        </button>

                        <div class="admin-sidebar__submenu"
                            :class="{ 'admin-sidebar__submenu--open': openMenu === 'settings' }">

                            <Link v-if="can('settings-currency.view')" :href="route('vendor.settings.currency')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': pageStartsWithRoute('vendor.settings.currency') }">
                                General
                            </Link>

                            <Link v-if="can('settings-kitchen-alert.view')" :href="route('vendor.settings.kitchen-alert')"
                                class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': pageStartsWithRoute('vendor.settings.kitchen-alert') }">
                                Kitchen Alert
                            </Link>

                            <Link v-if="can('taxes.view')" :href="route('vendor.taxes.index')" class="admin-sidebar__subitem"
                                :class="{ 'admin-sidebar__subitem--active': isTaxes }">
                                Taxes
                            </Link>
                        </div>

                    </div>

                    <div class="admin-offcanvas__footer">
                        <span class="admin-offcanvas__email">
                            {{ user?.email }}
                        </span>
                    </div>
                </section>

                <div class="admin-content-wrapper">
                    <header class="admin-topbar" :class="{
                        'admin-topbar--pos': isPosViewer,
                        'admin-topbar--stuck': isTopbarStuck && !isPosViewer,
                    }">
                        <div class="admin-topbar__left">
                            <template v-if="false && isPosMenu">
                                <!-- Shop POS: restaurant branch/menu/register/currency filters are hidden from the viewer header. -->
                                <div class="admin-posbar admin-posbar--desktop">
                                    <div class="admin-posbar__item">
                                        <span class="admin-posbar__label">Branch</span>
                                        <div class="admin-posbar__select">
                                            <SelectInput secondary-label="Branch" v-model="posMetaForm.branch_id"
                                                :options="posBranchOptions" labelKey="label" valueKey="value"
                                                placeholder="Select Branch" @update:modelValue="onPosBranchChange" />
                                        </div>
                                    </div>

                                    <div class="admin-posbar__item">
                                        <span class="admin-posbar__label">Menu</span>
                                        <div class="admin-posbar__select">
                                            <SelectInput secondary-label="Menu" v-model="posMetaForm.menu_id"
                                                :options="posMenuOptions" labelKey="label" valueKey="value"
                                                placeholder="Select Menu" @update:modelValue="onPosMenuChange" />
                                        </div>
                                    </div>

                                    <div class="admin-posbar__item">
                                        <span class="admin-posbar__label">Register</span>
                                        <div class="admin-posbar__select">
                                            <SelectInput secondary-label="Register" v-model="posMetaForm.register_id"
                                                :options="posRegisterOptions" labelKey="label" valueKey="value"
                                                placeholder="Select Register"
                                                @update:modelValue="onPosRegisterChange" />
                                        </div>
                                    </div>

                                    <div v-if="posHasSecondaryCurrency && isPosViewer" class="admin-posbar__item">
                                        <span class="admin-posbar__label">Currency</span>
                                        <div class="admin-posbar__select">
                                            <SelectInput secondary-label="Currency" v-model="posMetaForm.currency_mode"
                                                :options="posCurrencyOptions" labelKey="label" valueKey="value"
                                                placeholder="Currency" :disabled="posCurrencyLocked"
                                                @update:modelValue="onPosCurrencyChange" />
                                        </div>
                                    </div>
                                </div>
                                <button class="pos-filter-toggle" :class="{ 'has-selection': posActiveFilterCount > 0 }"
                                    @click="openPosSheet">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <path d="M2 4h12M5 8h6M7 12h2" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" />
                                    </svg>
                                    Filters
                                    <span v-if="posActiveFilterCount > 0" class="pos-filter-toggle__badge">
                                        {{ posActiveFilterCount }}
                                    </span>
                                </button>
                            </template>
                        </div>

                        <div class="admin-topbar__right">
                            <!-- <Link v-if="can('pos-kitchen.view')" :href="route('vendor.pos.kitchen.index')"
                                class="admin-topbar__icon-btn admin-topbar__icon-btn--pos"
                                aria-label="Kitchen Viewer"
                                @mouseenter="showTopbarTooltip('kitchen')"
                                @mouseleave="hideTopbarTooltip"
                                @focus="showTopbarTooltip('kitchen')"
                                @blur="hideTopbarTooltip">
                                <ChefHat :size="18" :stroke-width="1.8" />
                                <span class="admin-topbar__hover-label"
                                    :class="{ 'admin-topbar__hover-label--visible': topbarTooltip === 'kitchen' }">
                                    Kitchen Viewer
                                </span>
                            </Link> -->
                            <Link v-if="can('pos.view')" :href="route('vendor.pos.open')"
                                class="admin-topbar__icon-btn admin-topbar__icon-btn--pos" aria-label="POS Viewer"
                                @mouseenter="showTopbarTooltip('pos')" @mouseleave="hideTopbarTooltip"
                                @focus="showTopbarTooltip('pos')" @blur="hideTopbarTooltip">
                                <span class="admin-topbar__register-icon">
                                    <i class="bi bi-shop"></i>
                                    <small>$</small>
                                </span>
                                <span class="admin-topbar__hover-label"
                                    :class="{ 'admin-topbar__hover-label--visible': topbarTooltip === 'pos' }">
                                    POS Viewer
                                </span>
                            </Link>
                            <button type="button" class="admin-topbar__icon-btn admin-topbar__icon-btn--pos"
                                :aria-label="isFullscreen ? 'Exit Full Screen' : 'Full Screen'"
                                @mouseenter="showTopbarTooltip('fullscreen')"
                                @mouseleave="hideTopbarTooltip"
                                @focus="showTopbarTooltip('fullscreen')"
                                @blur="hideTopbarTooltip"
                                @click="toggleFullscreen">
                                <Minimize v-if="isFullscreen" :size="18" :stroke-width="1.9" />
                                <Maximize v-else :size="18" :stroke-width="1.9" />
                                <span class="admin-topbar__hover-label"
                                    :class="{ 'admin-topbar__hover-label--visible': topbarTooltip === 'fullscreen' }">
                                    {{ isFullscreen ? 'Exit Full Screen' : 'Full Screen' }}
                                </span>
                            </button>
                            <div class="admin-topbar__divider" />

                            <div class="admin-topbar__profile-menu">
                                <button class="admin-topbar__profile-btn" @click="toggleProfileMenu" type="button">
                                    <div class="admin-topbar__avatar">
                                        {{ getInitials(user?.name) }}
                                    </div>
                                </button>

                                <div v-if="profileMenuOpen" class="admin-topbar__dropdown" @click.stop>
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

                                    <div class="admin-topbar__dropdown-card">
                                        <button class="admin-topbar__dropdown-item admin-topbar__dropdown-item--logout"
                                            @click="handleLogout" type="button">
                                            <i class="bi bi-box-arrow-left"></i>
                                            <span>Logout</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <main class="admin-main" :class="{ 'admin-main--pos': isPosViewer }">
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
                                        placeholder=" " :class="{ error: accountErrors.name }" />
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
                                        placeholder="" :class="{ error: accountErrors.email }" />
                                    <label :class="[
                                        'admin-modal__label',
                                        {
                                            'admin-modal__error':
                                                accountErrors.email,
                                        },
                                    ]">E-mail</label>
                                    <span v-if="accountErrors.email" class="admin-modal__error">{{ accountErrors.email
                                    }}</span>
                                </div>
                            </div>

                            <div class="center-box">
                                <div class="phone-group">
                                    <div class="phone-row">
                                        <div class="country-dropdown" @click="toggleCountry">
                                            <span :class="selectedCountry.flagClass
                                                "></span>
                                            {{ selectedCountry.code }}
                                            <i class="bi bi-chevron-down" style="margin-left: 5px"></i>

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
                                            <input v-model="phoneNumber" class="admin-modal__input" type="text"
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
                                <input v-model="passwordForm.currentPassword" :type="showCurrent ? 'text' : 'password'"
                                    class="admin-modal__input" placeholder="" :class="{
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
                                    showCurrent ? 'bi-eye-slash' : 'bi-eye',
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
                                    <input v-model="passwordForm.password" :type="showPassword ? 'text' : 'password'
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
                                    ]" @click="showPassword = !showPassword"></i>

                                    <span v-if="passwordErrors.password" class="admin-modal__error">
                                        {{ passwordErrors.password }}
                                    </span>
                                </div>

                                <div class="admin-modal__form-group floating password-field">
                                    <input v-model="passwordForm.confirmPassword" :type="showConfirm ? 'text' : 'password'
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

                                    <span v-if="passwordErrors.confirmPassword" class="admin-modal__error">
                                        {{ passwordErrors.confirmPassword }}
                                    </span>
                                </div>
                            </div>
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

        <Teleport to="body">
            <Transition name="sheet">
                <div v-if="isPosSheetOpen" class="pos-sheet-overlay" @click.self="isPosSheetOpen = false">
                    <div class="pos-sheet">
                        <div class="pos-sheet__handle"></div>

                        <div class="pos-sheet__header">
                            <span class="pos-sheet__title">POS settings</span>
                            <button class="pos-sheet__close" @click="isPosSheetOpen = false">✕</button>
                        </div>

                        <div class="pos-sheet__body">
                            <div class="pos-sheet__field">
                                <label class="pos-sheet__label">Branch</label>
                                <SelectInput v-model="posSheetForm.branch_id" :options="posBranchOptions"
                                    :change="onPosSheetBranchChange" labelKey="label" valueKey="value"
                                    placeholder="Select Branch" />
                            </div>
                            <div class="pos-sheet__field">
                                <label class="pos-sheet__label">Menu</label>
                                <SelectInput v-model="posSheetForm.menu_id" :options="posSheetMenuOptions"
                                    labelKey="label" valueKey="value" placeholder="Select Menu" />
                            </div>
                            <div class="pos-sheet__field">
                                <label class="pos-sheet__label">Register</label>

                                <SelectInput v-model="posSheetForm.register_id" :options="posSheetRegisterOptions"
                                    labelKey="label" valueKey="value" placeholder="Select Register" />

                                <small v-if="posSheetErrors.register_id" class="text-danger ms-2">
                                    {{ posSheetErrors.register_id }}
                                </small>
                            </div>
                            <div v-if="posHasSecondaryCurrency && isPosViewer" class="pos-sheet__field">
                                <label class="pos-sheet__label">Currency</label>
                                <SelectInput v-model="posSheetForm.currency_mode" :options="posCurrencyOptions"
                                    labelKey="label" valueKey="value" placeholder="Currency"
                                    :disabled="posCurrencyLocked" />
                            </div>
                        </div>

                        <div class="pos-sheet__footer">
                            <button class="pos-sheet__reset" @click="resetPosSheet">Reset</button>
                            <button class="pos-sheet__apply" @click="applyPosSheet">Apply</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
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
<script setup>
import { ref, computed, onMounted, watchEffect, reactive, onBeforeUnmount, watch } from "vue";
import { Link, usePage, Head, router, useForm } from "@inertiajs/vue3";
import { usePermission } from "@/composables/usePermission";
import { ChefHat, Maximize, Minimize } from "lucide-vue-next";
import ctd from "country-telephone-data";
import "flag-icons/css/flag-icons.min.css";
import SelectInput from "@/Components/SelectInput.vue";

const page = usePage();

watchEffect(() => {
    const logo = page.props.tenant?.favicon_url
    if (logo) {
        let link = document.querySelector("link[rel~='icon']")
        if (!link) {
            link = document.createElement('link')
            link.rel = 'icon'
            document.head.appendChild(link)
        }
        link.href = logo
    }
})

const user = computed(() => page.props.auth?.user);
const { can } = usePermission();

const isPosViewer = computed(
    () => page.component === "VendorAdmin/POS/Viewer"
);
const isKitchenViewer = computed(
    () => page.component === "VendorAdmin/POS/Kitchen/Index"
);
const shouldAutoCollapseSidebar = computed(
    () => isPosViewer.value || isKitchenViewer.value
);

const posSession = computed(() => page.props.session ?? null);
const posBranches = computed(() => page.props.branches ?? []);
const posMenus = computed(() => page.props.menus ?? []);

const posMetaForm = useForm({
    branch_id: '',
    menu_id: '',
    register_id: '',
    currency_mode: 'base',
})
const posFilteredMenus = computed(() => {
    return posMenus.value.filter(menu => {
        return (
            !posMetaForm.branch_id ||
            !Array.isArray(menu.branch_ids) ||
            !menu.branch_ids.length ||
            menu.branch_ids.map(Number).includes(Number(posMetaForm.branch_id))
        );
    });
});
const posFilteredRegisters = computed(() => {
    return posRegisters.value.filter((register) => {
        return !posMetaForm.branch_id || Number(register.branch_id) === Number(posMetaForm.branch_id)
    })
})
const posHasSecondaryCurrency = computed(
    () => !!page.props.hasSecondaryCurrency
);

const posBaseCurrencyCode = computed(
    () => page.props.baseCurrencyCode || "LKR"
);

const posSecondaryCurrencyCode = computed(
    () => page.props.secondaryCurrencyCode || ""
);

const posCurrencyLocked = computed(
    () => (posSession.value?.items || []).length > 0
);

const posRegisterOptions = computed(() =>
    (posFilteredRegisters.value || []).map((register) => ({
        label: `${register.name} (${register.code})`,
        value: register.id,
    }))
)

const goToSelectedRegister = () => {
    if (!posMetaForm.register_id) return;

    router.get(
        route("vendor.pos.viewer", posMetaForm.register_id),
        {
            branch_id: posMetaForm.branch_id || undefined,
            menu_id: posMetaForm.menu_id || undefined,
            currency_mode: posMetaForm.currency_mode || "base",
        },
        {
            preserveScroll: true,
            preserveState: false,
        }
    );
};
const savePosMeta = () => {
    if (!posSession.value?.id) {
        goToSelectedRegister()
        return
    }

    posMetaForm.patch(route('vendor.pos.meta', posSession.value.id), {
        preserveScroll: true,
        preserveState: false,
    })
}
const onPosBranchChange = () => {
    const firstMenu = posFilteredMenus.value[0] ?? null;
    const firstRegister = posFilteredRegisters.value[0] ?? null;

    posMetaForm.menu_id = firstMenu?.id ?? "";
    posMetaForm.register_id = firstRegister?.id ?? "";

    if (!posMetaForm.register_id) {
        return;
    }

    goToSelectedRegister();
};

const posBranchOptions = computed(() =>
    (posBranches.value || []).map((branch) => ({
        label: branch.name,
        value: branch.id,
    }))
);
const onPosMenuChange = () => {
    if (posSession.value?.id) {
        savePosMeta()
        return
    }

    goToSelectedRegister()
}
const onPosRegisterChange = () => {
    goToSelectedRegister()
}

const posMenuOptions = computed(() =>
    (posFilteredMenus.value || []).map((menu) => ({
        label: menu.name,
        value: menu.id,
    }))
);

const posCurrencyOptions = computed(() => {
    const options = [
        {
            label: posBaseCurrencyCode.value,
            value: "base",
        },
    ];

    if (posHasSecondaryCurrency.value && posSecondaryCurrencyCode.value) {
        options.push({
            label: posSecondaryCurrencyCode.value,
            value: "secondary",
        });
    }

    return options;
});
const collapsed = ref(
    localStorage.getItem("admin_sidebar_collapsed") === "true",
);

const setSidebarCollapsed = (value, persist = true) => {
    collapsed.value = !!value;

    if (persist) {
        localStorage.setItem(
            "admin_sidebar_collapsed",
            String(collapsed.value),
        );
    }

    flyoutKey.value = null;
    openMenu.value = null;
};

const toggleSidebar = () => {
    setSidebarCollapsed(!collapsed.value, true);
};

const handleSidebarEvent = (event) => {
    setSidebarCollapsed(event.detail?.collapsed ?? true, false);
};
const sidebarRef = ref(null);
const flyoutKey = ref(null);
const flyoutTop = ref(0);
const flyoutLeft = ref(0);
const flyoutRef = ref(null);
const mobileOpen = ref(false);
const profileMenuOpen = ref(false);
const isFullscreen = ref(false);
const topbarTooltip = ref("");
const isTopbarStuck = ref(false);

const showTopbarTooltip = (key) => {
    topbarTooltip.value = key;
};

const hideTopbarTooltip = () => {
    topbarTooltip.value = "";
};

let topbarScrollFrame = null;

const syncTopbarStickState = () => {
    if (topbarScrollFrame) return;

    topbarScrollFrame = window.requestAnimationFrame(() => {
        isTopbarStuck.value = window.scrollY > 8;
        topbarScrollFrame = null;
    });
};
const accountModalOpen = ref(false);
const passwordModalOpen = ref(false);

const showCountry = ref(false);

const countries = ctd.allCountries.map((c) => ({
    name: c.name,
    code: `+${c.dialCode}`,
    flagClass: `fi fi-${c.iso2.toLowerCase()}`,
}));

const phoneCountryCode = ref("+94");
const phoneNumber = ref("");

const initPhone = () => {
    const fullPhone = user.value?.phone || "";

    const found = countries.find((c) => fullPhone.startsWith(c.code));

    if (found) {
        phoneCountryCode.value = found.code;
        selectedCountry.value = found;
        phoneNumber.value = fullPhone.slice(found.code.length);
    } else {
        phoneCountryCode.value = "+94";
        selectedCountry.value = countries[0];
        phoneNumber.value = fullPhone;
    }
};

const flyoutMenus = {
    sales: {
        items: [
            {
                label: "Orders", view: "sales-orders.view",
                href: () => route('vendor.sales.orders.index'),
                active: () => isSalesOrders.value,
            },
            {
                label: "Invoices", view: "sales-invoices.view",
                href: () => route('vendor.sales.invoices.index'),
                active: () => isSalesInvoices.value,
            },
            {
                label: "Payments", view: "sales-payments.view",
                href: () => route('vendor.sales.payments.index'),
                active: () => isSalesPayments.value,
            },
            {
                label: "Reasons", view: "sales-reasons.view",
                href: () => route('vendor.sales.reasons.index'),
                active: () => isSalesReasons.value,
            },
        ],
    },

    users: {
        items: [
            {
                label: "Users", view: "users.view",
                href: route('vendor.users.index'),
                active: () => isUsers.value,
            },
            {
                label: "Customers", view: "customers.view",
                href: route('vendor.customers.index'),
                active: () => isCustomers.value,
            },
            {
                label: "Roles", view: "roles-permissions.view",
                href: route('vendor.roles.index'),
                active: () => isRoles.value,
            },
        ],
    },

    // "seating-plan": {
    //     items: [
    //         { label: "Floors", view: "floors.view", href: route('vendor.floors.index'), active: () => isFloors.value },
    //         { label: "Zones", view: "zones.view", href: route('vendor.zones.index'), active: () => isZones.value },
    //         { label: "Tables", view: "tables.view", href: route('vendor.tables.index'), active: () => isTables.value },
    //         { label: "Table Merges", view: "table-merges.view", href: route('vendor.table-merges.index'), active: () => isTableMerges.value },
    //     ],
    // },

    menus: {
        items: [
            { label: "Online Menus", view: "online-menus.view", href: route('vendor.online-menus.index'), active: () => isOnlineMenus.value },
            { label: "Categories", view: "categories.view", href: route('vendor.categories.index'), active: () => isCategories.value },
            { label: "Brands", view: "products.view", href: route('vendor.brands.index'), active: () => isBrands.value },
            { label: "Options", view: "options.view", href: route('vendor.options.index'), active: () => isOptions.value },
            { label: "Products", view: "products.view", href: route('vendor.products.index'), active: () => isProducts.value },
        ],
    },

    pos: {
        items: [
            { label: "Registers", view: "pos-registers.view", href: () => route('vendor.pos.registers.index'), active: () => isPosRegisters.value },
            { label: "Sessions", view: "pos-sessions.view", href: () => route('vendor.pos.sessions.index'), active: () => isPosSessions.value },
            { label: "Cash Movements", view: "pos-cash-movements.view", href: () => route('vendor.pos.cash-movements.index'), active: () => isPosCashMovements.value },
        ],
    },

    inventory: {
        items: [
            { label: "Analytics", view: "inventory.view", href: route('vendor.inventory-analytics.index'), active: () => isInventoryAnalytics.value },
            { label: "Stock Management", view: "products.view", href: route('vendor.stock-management.index'), active: () => isStockManagement.value },
            { label: "Units", view: "units.view", href: route('vendor.units.index'), active: () => isUnits.value },
            { label: "Suppliers", view: "suppliers.view", href: route('vendor.suppliers.index'), active: () => isSuppliers.value },
        ],
    },

    "gift-cards": {
        items: [
            { label: "Analytics", view: "gift-card-analytics.view", href: () => route('vendor.gift-cards.analytics'), active: () => isGiftCardAnalytics.value },
            { label: "Cards", view: "gift-cards.view", href: () => route('vendor.gift-cards.index'), active: () => isGiftCards.value },
            { label: "Transactions", view: "gift-card-transactions.view", href: () => route('vendor.gift-cards.transactions.index'), active: () => isGiftCardTransactions.value },
            { label: "Batches", view: "gift-card-batches.view", href: () => route('vendor.gift-cards.batches.index'), active: () => isGiftCardBatches.value },
        ],
    },

    activities: {
        items: [
            { label: "Activity Logs", view: "activity-logs.view", href: () => route('vendor.activities.activity-logs.index'), active: () => isActivityLogs.value },
            { label: "Authentication Logs", view: "authentication-logs.view", href: () => route('vendor.activities.authentication-logs.index'), active: () => isAuthenticationLogs.value },
        ],
    },

    loyalty: {
        items: [
            { label: "Programs", view: "loyalty-programs.view", href: () => route('vendor.loyalty.programs.index'), active: () => isLoyaltyPrograms.value },
            { label: "Tiers", view: "loyalty-tiers.view", href: () => route('vendor.loyalty.tiers.index'), active: () => isLoyaltyTiers.value },
            { label: "Rewards", view: "loyalty-rewards.view", href: () => route('vendor.loyalty.rewards.index'), active: () => isLoyaltyRewards.value },
            { label: "Promotions", view: "loyalty-promotions.view", href: () => route('vendor.loyalty.promotions.index'), active: () => isLoyaltyPromotions.value },
            { label: "Customers", view: "loyalty-customers.view", href: () => route('vendor.loyalty.customers.index'), active: () => isLoyaltyCustomers.value },
            { label: "Gifts", view: "loyalty-gifts.view", href: () => route('vendor.loyalty.gifts.index'), active: () => isLoyaltyGifts.value },
            { label: "Transactions", view: "loyalty-transactions.view", href: () => route('vendor.loyalty.transactions.index'), active: () => isLoyaltyTransactions.value },
        ],
    },

    promotions: {
        items: [
            { label: "Discounts", view: "promotion-discounts.view", href: () => route('vendor.promotions.discounts.index'), active: () => isPromotionDiscounts.value },
            { label: "Vouchers", view: "promotion-vouchers.view", href: () => route('vendor.promotions.vouchers.index'), active: () => isPromotionVouchers.value },
        ],
    },

    settings: {
        items: [
            {
                label: "General",
                views: [
                    'settings-kitchen-alert.view',
                    'settings-currency.view',
                    'pms.view',
                    'settings-mail.view',
                    'settings.logo.view'
                ],
                href: () => {
                    const routes = [
                        { permission: 'settings-currency.view', url: route('vendor.settings.currency') },
                        { permission: 'settings.logo.view', url: route('vendor.settings.logo') },
                        { permission: 'pms.view', url: route('vendor.settings.pms') },
                        { permission: 'settings-mail.view', url: route('vendor.settings.mail') },
                        { permission: 'settings-mail.view', url: route('vendor.settings.mail.recipients') },
                        { permission: 'settings-kitchen-alert.view', url: route('vendor.settings.kitchen-alert') },
                    ]

                    return routes.find(r => can(r.permission))?.url || route('vendor.settings.currency')
                },
                active: () => isSettingsGeneral.value
            },
            // { label: "Kitchen Alert", view: "settings-kitchen-alert.view", href: route('vendor.settings.kitchen-alert'), active: () => isSettingsGeneral.value },
            // { label: "PMS Integration", view: "pms.view", href: route('vendor.settings.pms'), active: () => isSettingsGeneral.value },
            { label: "Taxes", view: "taxes.view", href: route('vendor.taxes.index'), active: () => isTaxes.value },
        ],
    },
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

const isPosSheetOpen = ref(false)

const posSheetErrors = reactive({
    register_id: "",
});

const posSheetForm = reactive({
    branch_id: null,
    menu_id: null,
    register_id: null,
    currency_mode: null,
})

const openPosSheet = () => {
    posSheetForm.branch_id = posMetaForm.branch_id
    posSheetForm.menu_id = posMetaForm.menu_id
    posSheetForm.register_id = posMetaForm.register_id
    posSheetForm.currency_mode = posMetaForm.currency_mode
    isPosSheetOpen.value = true
}

const posSheetFilteredMenus = computed(() => {
    return posMenus.value.filter((menu) => {
        return (
            !posSheetForm.branch_id ||
            !Array.isArray(menu.branch_ids) ||
            !menu.branch_ids.length ||
            menu.branch_ids.map(Number).includes(Number(posSheetForm.branch_id))
        );
    })
})

const posSheetFilteredRegisters = computed(() => {
    return posRegisters.value.filter((register) => {
        return !posSheetForm.branch_id || Number(register.branch_id) === Number(posSheetForm.branch_id)
    })
})

const posSheetMenuOptions = computed(() =>
    (posSheetFilteredMenus.value || []).map((menu) => ({
        label: menu.name,
        value: menu.id,
    }))
)

const posSheetRegisterOptions = computed(() =>
    (posSheetFilteredRegisters.value || []).map((register) => ({
        label: `${register.name} (${register.code})`,
        value: register.id,
    }))
)

const posActiveFilterCount = computed(() => {
    return [
        posMetaForm.branch_id,
        posMetaForm.menu_id,
        posMetaForm.register_id,
        posMetaForm.currency_mode,
    ].filter(Boolean).length
})

const applyPosSheet = () => {

    posSheetErrors.register_id = "";

    if (!posSheetForm.register_id) {
        posSheetErrors.register_id = "Please select a register.";
        return;
    }

    posMetaForm.branch_id = posSheetForm.branch_id;
    posMetaForm.menu_id = posSheetForm.menu_id;
    posMetaForm.register_id = posSheetForm.register_id;
    posMetaForm.currency_mode = posSheetForm.currency_mode ?? "base";

    isPosSheetOpen.value = false;

    if (posMetaForm.register_id) {
        router.get(
            route("vendor.pos.viewer", posMetaForm.register_id),
            {
                branch_id: posMetaForm.branch_id || undefined,
                menu_id: posMetaForm.menu_id || undefined,
                currency_mode: posMetaForm.currency_mode || "base",
            },
            { preserveScroll: true, preserveState: false }
        );
    } else if (posSession.value?.id) {
        posMetaForm.patch(route("vendor.pos.meta", posSession.value.id), {
            preserveScroll: true,
            preserveState: false,
        });
    }
}

const resetPosSheet = () => {
    posSheetForm.branch_id = posMetaForm.branch_id
    posSheetForm.menu_id = posMetaForm.menu_id
    posSheetForm.register_id = posMetaForm.register_id
    posSheetForm.currency_mode = posMetaForm.currency_mode
}

const openMenu = ref(null);
const previousSidebarCollapsed = ref(null);
const mobileUsersOpen = ref(false);
const mobileSalesOpen = ref(false);
const showCurrent = ref(false);
const showPassword = ref(false);
const showConfirm = ref(false);

watch(
    shouldAutoCollapseSidebar,
    (shouldCollapse) => {
        if (shouldCollapse) {
            if (previousSidebarCollapsed.value === null) {
                previousSidebarCollapsed.value = collapsed.value;
            }

            setSidebarCollapsed(true, false);
            return;
        }

        if (previousSidebarCollapsed.value !== null) {
            setSidebarCollapsed(previousSidebarCollapsed.value, false);
            previousSidebarCollapsed.value = null;
        }
    },
    { immediate: true },
);

const routePath = (name, params) => new URL(route(name, params), window.location.origin).pathname;
const pageStartsWithRoute = (name, params) => page.url.startsWith(routePath(name, params));

const isDashboard = computed(() => pageStartsWithRoute('vendor.dashboard'));
const isReports = computed(() => pageStartsWithRoute('vendor.reports.index'));
const isBranches = computed(() => pageStartsWithRoute('vendor.branches.index'));
const isRoles = computed(() => pageStartsWithRoute('vendor.roles.index'));

const isUsers = computed(() => pageStartsWithRoute('vendor.users.index'));
const isCustomers = computed(() => pageStartsWithRoute('vendor.customers.index'));

const isMenus = computed(() => pageStartsWithRoute('vendor.menus.index'));
const isOnlineMenus = computed(() =>
    pageStartsWithRoute('vendor.online-menus.index'),
);
const isCategories = computed(() => pageStartsWithRoute('vendor.categories.index'));
const isBrands = computed(() => pageStartsWithRoute('vendor.brands.index'));
const isProducts = computed(() => pageStartsWithRoute('vendor.products.index'));
const isOptions = computed(() => pageStartsWithRoute('vendor.options.index'));
const isUnits = computed(() => pageStartsWithRoute('vendor.units.index'));
const isSuppliers = computed(() => pageStartsWithRoute('vendor.suppliers.index'));
const isIngredients = computed(() => pageStartsWithRoute('vendor.ingredients.index'));

const isPosRegisters = computed(() => page.component === 'VendorAdmin/POS/Register/Index' || page.component === 'VendorAdmin/POS/Register/CreateUpdate')
const isPosSessions = computed(() => page.component === 'VendorAdmin/POS/Session/Index')
const isPosCashMovements = computed(() => page.component === 'VendorAdmin/POS/CashMovement/Index')
const isPosMenu = computed(() => isPosViewer.value)

const isSalesOrders = computed(() => pageStartsWithRoute('vendor.sales.orders.index'));

const isSalesInvoices = computed(() => pageStartsWithRoute('vendor.sales.invoices.index'));

const isSalesPayments = computed(() => pageStartsWithRoute('vendor.sales.payments.index'));

const isSalesReasons = computed(() => pageStartsWithRoute('vendor.sales.reasons.index'));

const openFlyout = (key, event) => {
    if (!collapsed.value) return;

    if (flyoutKey.value === key) {
        flyoutKey.value = null;
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();

    flyoutKey.value = key;

    flyoutTop.value = Math.min(
        rect.top,
        window.innerHeight - 220
    );

    flyoutLeft.value = rect.right + 12;
};

const isSalesMenu = computed(() =>
    isSalesOrders.value ||
    isSalesInvoices.value ||
    isSalesPayments.value ||
    isSalesReasons.value
);
const posRegisters = computed(() => {
    const value = page.props.registers ?? [];

    if (Array.isArray(value)) {
        return value;
    }

    if (value && Array.isArray(value.data)) {
        return value.data;
    }

    return [];
});
const posSelectedRegister = computed(() => page.props.selectedRegister ?? null)
const posSelectedBranchId = computed(() => page.props.selectedBranchId ?? '')
const posSelectedMenuId = computed(() => page.props.selectedMenuId ?? '')

const isStockMovements = computed(() =>
    pageStartsWithRoute('vendor.stock-movements.index'),
);
const isStockManagement = computed(() =>
    pageStartsWithRoute('vendor.stock-management.index'),
);
const isPurchases = computed(() => pageStartsWithRoute('vendor.purchases.index'));
const isInventoryAnalytics = computed(() =>
    pageStartsWithRoute('vendor.inventory-analytics.index'),
);
const isGiftCardAnalytics = computed(() =>
    pageStartsWithRoute('vendor.gift-cards.analytics'),
);
const isGiftCardTransactions = computed(() =>
    pageStartsWithRoute('vendor.gift-cards.transactions.index'),
);
const isGiftCardBatches = computed(() =>
    pageStartsWithRoute('vendor.gift-cards.batches.index'),
);
const isGiftCards = computed(() =>
    pageStartsWithRoute('vendor.gift-cards.index') &&
    !isGiftCardAnalytics.value &&
    !isGiftCardTransactions.value &&
    !isGiftCardBatches.value,
);
const isActivityLogs = computed(() =>
    pageStartsWithRoute('vendor.activities.activity-logs.index'),
);
const isAuthenticationLogs = computed(() =>
    pageStartsWithRoute('vendor.activities.authentication-logs.index'),
);
const isActivitiesMenu = computed(() =>
    isActivityLogs.value ||
    isAuthenticationLogs.value,
);
const isPromotionDiscounts = computed(() =>
    pageStartsWithRoute('vendor.promotions.discounts.index'),
);
const isPromotionVouchers = computed(() =>
    pageStartsWithRoute('vendor.promotions.vouchers.index'),
);
const isLoyaltyPrograms = computed(() =>
    pageStartsWithRoute('vendor.loyalty.programs.index'),
);
const isLoyaltyTiers = computed(() =>
    pageStartsWithRoute('vendor.loyalty.tiers.index'),
);
const isLoyaltyRewards = computed(() =>
    pageStartsWithRoute('vendor.loyalty.rewards.index'),
);
const isLoyaltyPromotions = computed(() =>
    pageStartsWithRoute('vendor.loyalty.promotions.index'),
);
const isLoyaltyCustomers = computed(() =>
    pageStartsWithRoute('vendor.loyalty.customers.index'),
);
const isLoyaltyGifts = computed(() =>
    pageStartsWithRoute('vendor.loyalty.gifts.index'),
);
const isLoyaltyTransactions = computed(() =>
    pageStartsWithRoute('vendor.loyalty.transactions.index'),
);
const isSettingsGeneral = computed(() =>
    pageStartsWithRoute('vendor.settings.currency') ||
    pageStartsWithRoute('vendor.settings.kitchen-alert') ||
    pageStartsWithRoute('vendor.settings.pms') ||
    pageStartsWithRoute('vendor.settings.mail') ||
    pageStartsWithRoute('vendor.settings.mail.recipients') ||
    pageStartsWithRoute('vendor.settings.logo')
);
const isTaxes = computed(() => pageStartsWithRoute('vendor.taxes.index'));
const isFloors = computed(() => pageStartsWithRoute('vendor.floors.index'));
const isZones = computed(() => pageStartsWithRoute('vendor.zones.index'));
const isTables = computed(() => pageStartsWithRoute('vendor.tables.index'));
const isTableMerges = computed(() =>
    pageStartsWithRoute('vendor.table-merges.index'),
);
watch(
    () => [
        page.props.session,
        page.props.selectedRegister,
        page.props.selectedBranchId,
        page.props.selectedMenuId
    ],
    () => {
        posMetaForm.branch_id =
            posSession.value?.branch_id ??
            posSelectedBranchId.value ??
            ''

        posMetaForm.menu_id =
            posSession.value?.menu_id ??
            posSelectedMenuId.value ??
            ''

        posMetaForm.register_id =
            posSelectedRegister.value?.id ??
            ''

        posMetaForm.currency_mode =
            posSession.value?.currency_mode ??
            'base'
    },
    { immediate: true }
)

watch(
    () => posSheetForm.branch_id,
    (newBranchId, oldBranchId) => {
        if (newBranchId === oldBranchId) return

        const firstMenu = posSheetFilteredMenus.value[0] ?? null
        const firstRegister = posSheetFilteredRegisters.value[0] ?? null

        posSheetForm.menu_id = firstMenu?.id ?? ""
        posSheetForm.register_id = firstRegister?.id ?? ""
    }
)

watch(
    () => posSheetForm.register_id,
    () => {
        posSheetErrors.register_id = "";
    }
);

const isSeatingPlanMenu = computed(
    () =>
        isFloors.value ||
        isZones.value ||
        isTables.value ||
        isTableMerges.value,
);

const mobileSeatingPlanOpen = ref(false);

const isSettingsMenu = computed(() => isSettingsGeneral.value);
const mobileSettingsOpen = ref(false);

const isInventoryMenu = computed(
    () =>
        isStockManagement.value ||
        isUnits.value ||
        isSuppliers.value,
);

const mobileInventoryOpen = ref(false);

const isGiftCardsMenu = computed(
    () =>
        isGiftCardAnalytics.value ||
        isGiftCards.value ||
        isGiftCardTransactions.value ||
        isGiftCardBatches.value,
);

const mobileGiftCardsOpen = ref(false);

const isPromotionsMenu = computed(
    () =>
        isPromotionDiscounts.value ||
        isPromotionVouchers.value,
);

const mobilePromotionsOpen = ref(false);

const isLoyaltyMenu = computed(
    () =>
        isLoyaltyPrograms.value ||
        isLoyaltyTiers.value ||
        isLoyaltyRewards.value ||
        isLoyaltyPromotions.value ||
        isLoyaltyCustomers.value ||
        isLoyaltyGifts.value ||
        isLoyaltyTransactions.value,
);

const onPosCurrencyChange = () => {
    if (posSession.value?.id) {
        savePosMeta();
        return;
    }

    goToSelectedRegister();
};
const isMenusMenu = computed(
    () =>
        isMenus.value ||
        isOnlineMenus.value ||
        isCategories.value ||
        isBrands.value ||
        isProducts.value ||
        isOptions.value ||
        isTaxes.value,
);

const mobileMenusOpen = ref(false);

const isUsersMenu = computed(
    () => isUsers.value || isCustomers.value || isRoles.value,
);

const toggleMenu = (key) => {
    openMenu.value = openMenu.value === key ? null : key;
};

const closeMobile = () => {
    mobileSettingsOpen.value = false;
    mobileOpen.value = false;
    mobileUsersOpen.value = false;
    mobileMenusOpen.value = false;
    mobileInventoryOpen.value = false;
    mobileGiftCardsOpen.value = false;
    mobilePromotionsOpen.value = false;
    mobileSeatingPlanOpen.value = false;
    mobileSalesOpen.value = false;
};

router.on("finish", () => {
    flyoutKey.value = null;
    mobileSettingsOpen.value = false;
    mobileOpen.value = false;
    mobileUsersOpen.value = false;
    mobileMenusOpen.value = false;
    mobileInventoryOpen.value = false;
    mobileGiftCardsOpen.value = false;
    mobilePromotionsOpen.value = false;
    mobileSeatingPlanOpen.value = false;
    mobileSalesOpen.value = false;
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

    accountForm.patch(route("vendor.profile.update"), {
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

    passwordForm.put(route("vendor.password.update"), {
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
    router.post(route("vendor.logout"));
};

const toggleProfileMenu = () => {
    profileMenuOpen.value = !profileMenuOpen.value;
};

const syncFullscreenState = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

const toggleFullscreen = async () => {
    try {
        if (document.fullscreenElement) {
            await document.exitFullscreen();
        } else {
            await document.documentElement.requestFullscreen();
        }

        syncFullscreenState();
    } catch (error) {
        showToastMessage("Unable to change full screen mode.", "error");
    }
};

const openAccountModal = () => {
    router.visit(route("vendor.profile"));
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

const onDocClick = (e) => {
    const insideSidebar = sidebarRef.value?.contains(e.target);
    const insideFlyout = flyoutRef.value?.contains(e.target);

    if (!insideSidebar && !insideFlyout) {
        flyoutKey.value = null;
    }

    if (
        profileMenuOpen.value &&
        !e.target.closest(".admin-topbar__profile-menu")
    ) {
        profileMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", onDocClick);
    document.addEventListener("fullscreenchange", syncFullscreenState);
    window.addEventListener("vendor-admin:sidebar", handleSidebarEvent);
    window.addEventListener("scroll", syncTopbarStickState, { passive: true });
    syncFullscreenState();
    syncTopbarStickState();

    posMetaForm.branch_id = posSession.value?.branch_id ?? ''
    posMetaForm.menu_id = posSession.value?.menu_id ?? ''
    posMetaForm.register_id = posSelectedRegister.value?.id ?? ''
    posMetaForm.currency_mode = posSession.value?.currency_mode ?? 'base'

    posSheetForm.branch_id = posSession.value?.branch_id ?? ''
    posSheetForm.menu_id = posSession.value?.menu_id ?? ''
    posSheetForm.register_id = posSelectedRegister.value?.id ?? ''
    posSheetForm.currency_mode = posSession.value?.currency_mode ?? 'base'


    if (isSalesMenu.value) {
        openMenu.value = "sales";
        mobileSalesOpen.value = true;
    }

    if (isSeatingPlanMenu.value) {
        openMenu.value = "seating-plan";
        mobileSeatingPlanOpen.value = true;
    }

    if (isSettingsMenu.value) {
        openMenu.value = "settings";
        mobileSettingsOpen.value = true;
    }

    if (isInventoryMenu.value) {
        openMenu.value = "inventory";
        mobileInventoryOpen.value = true;
    }

    if (isGiftCardsMenu.value) {
        openMenu.value = "gift-cards";
        mobileGiftCardsOpen.value = true;
    }

    if (isActivitiesMenu.value) {
        openMenu.value = "activities";
    }

    if (isPromotionsMenu.value) {
        openMenu.value = "promotions";
        mobilePromotionsOpen.value = true;
    }

    if (isLoyaltyMenu.value) {
        openMenu.value = "loyalty";
    }

    if (isUsersMenu.value) {
        openMenu.value = "users";
        mobileUsersOpen.value = true;
    }

    if (isMenusMenu.value) {
        openMenu.value = "menus";
        mobileMenusOpen.value = true;
    }
});

onBeforeUnmount(() => {
    document.removeEventListener("click", onDocClick);
    document.removeEventListener("fullscreenchange", syncFullscreenState);
    window.removeEventListener("vendor-admin:sidebar", handleSidebarEvent);
    window.removeEventListener("scroll", syncTopbarStickState);
    if (topbarScrollFrame) {
        window.cancelAnimationFrame(topbarScrollFrame);
    }
    document.body.style.overflow = "";
});
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

watch(collapsed, (value) => {
    document.body.classList.toggle('sidebar-collapsed', value)
}, { immediate: true });

</script>


<style>
.table-container-modern {
    padding: 0 0.5rem 1rem;
    max-width: calc(100vw - 300px);
    transition: max-width 0.25s ease;
}

body.sidebar-collapsed .table-container-modern {
    max-width: calc(100vw - 120px) !important;
}
</style>

<style scoped>
.admin-layout {
    min-height: 100vh;
    position: relative;
    background: #f6f7fb;
    display: flex;
}

.admin-content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.admin-topbar {
    height: 60px;
    margin: 1.5rem;
    position: sticky;
    top: 14px;
    z-index: 50;
    background: #ffffff;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    transform: translateY(0);
    transform-origin: top center;
    will-change: box-shadow, background-color, border-color, backdrop-filter;
    transition:
        border-color 0.32s ease,
        background-color 0.32s ease,
        box-shadow 0.34s cubic-bezier(0.16, 1, 0.3, 1),
        backdrop-filter 0.32s ease;
}

.admin-topbar--stuck {
    background: rgba(255, 255, 255, 0.92);
    border-color: rgba(15, 23, 42, 0.1);
    box-shadow:
        0 14px 38px rgba(15, 23, 42, 0.11),
        0 1px 0 rgba(255, 255, 255, 0.72) inset;
    backdrop-filter: blur(16px);
}

.admin-topbar__left {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 8px;
    flex-wrap: wrap;
}

.admin-topbar__right {
    display: flex;
    align-items: center;
    gap: 12px;
    overflow: visible;
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
    position: relative;
    overflow: visible;
    isolation: isolate;
    transition:
        color 0.18s ease,
        border-color 0.18s ease,
        background 0.18s ease,
        box-shadow 0.18s ease,
        transform 0.18s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.admin-topbar__icon-btn:hover {
    background: rgba(255, 149, 0, 0.04);
    color: #f57c00;
    border-color: rgba(255, 149, 0, 0.12);
    transform: translateY(-2px);
}

.admin-topbar__icon-btn i,
.admin-topbar__icon-btn svg {
    font-size: 16px;
    transition: transform 0.18s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.admin-topbar__icon-btn:hover i,
.admin-topbar__icon-btn:hover svg {
    transform: scale(1.08);
}

.admin-topbar__hover-label {
    position: absolute;
    left: 50%;
    top: calc(100% + 8px);
    z-index: 30;
    min-width: max-content;
    max-width: 160px;
    padding: 7px 10px;
    border-radius: 4px;
    background: #1f2a37;
    color: #ffffff;
    font-size: 12px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0;
    pointer-events: none;
    opacity: 0;
    transform: translate(-50%, -4px) scale(0.96);
    transition:
        opacity 0.16s ease,
        transform 0.18s cubic-bezier(0.2, 0.8, 0.2, 1);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.2);
}

.admin-topbar__hover-label::before {
    content: "";
    position: absolute;
    left: 50%;
    top: -4px;
    width: 8px;
    height: 8px;
    background: #1f2a37;
    transform: translateX(-50%) rotate(45deg);
}

.admin-topbar__hover-label--visible {
    opacity: 1;
    transform: translate(-50%, 0) scale(1);
}

.admin-topbar__register-icon {
    position: relative;
    display: inline-grid;
    place-items: center;
}

.admin-topbar__register-icon i {
    font-size: 17px;
}

.admin-topbar__register-icon small {
    position: absolute;
    right: -7px;
    bottom: -6px;
    display: grid;
    place-items: center;
    width: 14px;
    height: 14px;
    border-radius: 999px;
    background: #ffffff;
    color: currentColor;
    font-size: 9px;
    font-weight: 900;
    line-height: 1;
}

.admin-topbar__divider {
    width: 1px;
    height: 20px;
    background: rgba(15, 23, 42, 0.08);
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

.admin-topbar__dropdown-card {
    background: #ffffff;
    margin: 8px 8px;
    border-radius: 10px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.center-box {
    width: 300px;
    margin: auto;
}

.phone-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

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
    background: #f3f3f3;
    font-size: 14px;
}

.country-dropdown i {
    font-size: 12px;
}

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

.country-item {
    padding: 10px;
    cursor: pointer;
    font-size: 13px;
}

.country-item:hover {
    background: #f1f5f9;
}

.phone-input {
    flex: 1;
}

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

.admin-topbar__dropdown-item--logout {
    color: #dc2626;
}

.admin-topbar__dropdown-item--logout:hover {
    background: rgba(220, 38, 38, 0.05);
    color: #dc2626;
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

.admin-topbar__dropdown-divider {
    height: 1px;
    background: rgba(15, 23, 42, 0.08);
}

.admin-main {
    flex: 1;
    padding-top: 0 !important;
    padding: 24px;
    background: #f6f7fb;
}

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

.admin-toast__icon {
    font-size: 16px;
}

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


.admin-sidebar {
    width: 220px;
    background: #ffffff;
    border-right: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    transition: width 0.25s ease;
    position: sticky;
    overflow-y: scroll;
    overflow-x: visible;
    top: 0;
    height: 100vh;
    z-index: 10;
}

.admin-sidebar::-webkit-scrollbar {
    display: none;
}

.admin-sidebar--collapsed {
    width: 70px;
}

.admin-sidebar__header {
    position: sticky;
    top: 0;
    z-index: 5;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px 14px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    background: #ffffff;
}

.admin-sidebar__brand {
    flex: 1;
    display: flex;
    justify-content: center;
    min-width: 0;
    padding-right: 10px;
}

.admin-sidebar__brand.collapsed {
    padding-right: 0px;
}

.admin-sidebar__brand--hidden {
    opacity: 0;
    pointer-events: none;
    visibility: hidden;
}

.admin-sidebar__brand-content {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    justify-content: center;
}

.admin-sidebar__brand-content.collapsed {
    gap: 0;
}


.admin-sidebar__brand-logo {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.admin-sidebar__brand-name {
    font-weight: 600;
    color: rgba(15, 23, 42, 0.9);
    line-height: 1.1;
    font-size: 20px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.admin-sidebar__brand-name.is-hidden {
    opacity: 0;
    width: 0;
    overflow: hidden;
    white-space: nowrap;
    pointer-events: none;
    transition: all 0.2s ease;
}

.admin-sidebar__toggle {
    position: fixed;
    left: 208px;
    top: 18px;

    width: 22px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: #fff;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    transition: all 0.25s ease;
    z-index: 999;
}

.admin-sidebar__toggle.collapsed {
    left: 58px;
}



.admin-sidebar__toggle i {
    font-size: 14px;
    color: rgba(15, 23, 42, 0.7);
}

.admin-sidebar__nav {
    padding: 5px 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
    scrollbar-width: none;
    position: relative;
}

.admin-sidebar__nav::-webkit-scrollbar {
    display: none;
}

.admin-sidebar__nav::after {
    content: '';
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(to top, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0.8) 100%);
    pointer-events: none;
}

.admin-sidebar__section-label {
    margin: 18px 2px 7px;
    padding: 0 8px;
    color: rgba(100, 116, 139, 0.55);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    line-height: 1;
    text-transform: uppercase;
}

@keyframes scrollBounce {
    0% {
        transform: translateY(0);
        opacity: 0.4;
    }

    50% {
        transform: translateY(6px);
        opacity: 1;
    }

    100% {
        transform: translateY(0);
        opacity: 0.4;
    }
}

@keyframes scrollPulse {

    0%,
    100% {
        opacity: 0.4;
    }

    50% {
        opacity: 0.8;
    }
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
    color: #f57c00;
}

.admin-sidebar__item--active {
    background: rgba(255, 149, 0, 0.08);
    color: #f57c00;
    padding: 6px 12px;
    border-color: rgba(255, 149, 0, 0.14);
    font-weight: 700;
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
    position: fixed;
    background: rgba(15, 23, 42, 0.92);
    left: 75px;
    color: #ffffff;
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 10px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18);
    z-index: 9999;
    transition: opacity 0.15s ease;
}

.admin-sidebar__item:hover .admin-sidebar__tooltip {
    opacity: 1;
}

.admin-sidebar__footer {
    position: sticky;
    bottom: 0;
    z-index: 5;
    padding: 14px 14px 16px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    background: #ffffff;
    margin-top: 0;
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

.admin-flyout {
    position: fixed;
    z-index: 40;
    width: 200px;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 14px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16);
    overflow: hidden;
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

.admin-offcanvas-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.18s ease;
    z-index:60;
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
    overflow-y: scroll;
    flex-direction: column;
    border-right: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
}

.admin-offcanvas::-webkit-scrollbar {
    display: none;
}

.admin-offcanvas--open {
    transform: translateX(0);
}

.admin-offcanvas__header {
    position: sticky;
    top: 0;
    z-index: 5;
    padding: 16px 14px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
}

.admin-offcanvas__brand-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.admin-offcanvas__brand-logo {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.admin-offcanvas__brand {
    margin: 0;
    font-weight: 800;
    color: rgba(15, 23, 42, 0.9);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
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
    overflow: visible;
    position: relative;
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
    color: #f57c00;
}

.admin-offcanvas__item--active {
    background: rgba(255, 149, 0, 0.08);
    color: #f57c00;
    border-color: rgba(255, 149, 0, 0.14);
    font-weight: 700;
}

.admin-offcanvas__footer {
    position: sticky;
    bottom: 0;
    z-index: 5;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    padding: 12px 14px;
    background: #ffffff;
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

.password-field i:hover {
    color: #f57c00;
}

.password-field input {
    padding-right: 36px;
}

input:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #0f172a !important;
    transition: background-color 5000s ease-in-out 0s;
}

input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
    -webkit-text-fill-color: #0f172a !important;
}

.admin-sidebar--collapsed .admin-sidebar__item {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
}

.admin-sidebar--collapsed .admin-sidebar__icon,
.admin-sidebar--collapsed .admin-sidebar__item i {
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.admin-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
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
    margin: 20px auto;
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

.admin-modal__body {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
    overflow-y: visible;
}

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

.admin-sidebar__item--button {
    cursor: pointer;
    text-align: left;
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
    border-left: 1px solid rgba(119, 8, 119, 0.13);
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
    max-height: 300px;
    opacity: 1;
    pointer-events: auto;
    visibility: visible;
}

.admin-sidebar__subitem {
    display: block;
    position: relative;
    padding: 5px 6px 5px 14px;
    font-size: 13px;
    color: rgba(15, 23, 42, 0.58);
    text-decoration: none;
    border-radius: 10px;
    margin-bottom: 2px;
}

.admin-sidebar__subitem:hover {
    color: rgba(15, 23, 42, 0.85);
}

.admin-sidebar__subitem:hover::before {
    content: '';
    position: absolute;
    left: 0;
    top: 6px;
    bottom: 6px;
    width: 3px;
    border-radius: 2px;
    background: rgba(245, 124, 0, 0.4);
}

.admin-sidebar__subitem--active {
    color: #f57c00;
    font-weight: 700;
}

.admin-sidebar__subitem--active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 6px;
    bottom: 6px;
    width: 3px;
    border-radius: 2px;
    background: #f57c00;
}

.admin-offcanvas__accordion-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 12px;
    border-radius: 12px;
    border: 1px solid transparent;
    background: transparent;
    color: rgba(15, 23, 42, 0.7);
    font-weight: 700;
    text-align: left;
}

.admin-offcanvas__chevron {
    margin-left: auto;
    transition: transform 0.2s ease;
}

.admin-offcanvas__chevron--open {
    transform: rotate(180deg);
}

.admin-offcanvas__submenu {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition:
        max-height 0.22s ease,
        opacity 0.18s ease;
    padding-left: 16px;
}

.admin-offcanvas__submenu--open {
    max-height: 220px;
    opacity: 1;
    margin-bottom: 6px;
}

.admin-offcanvas__subitem {
    display: block;
    padding: 10px 12px;
    border-radius: 10px;
    text-decoration: none;
    color: rgba(15, 23, 42, 0.65);
    font-weight: 600;
    font-size: 14px;
}

.admin-offcanvas__subitem:hover {
    background: rgba(15, 23, 42, 0.04);
}

.admin-offcanvas__subitem--active {
    background: rgba(157, 13, 253, 0.08);
    color: #7837aa;
    border-color: rgba(109, 0, 252, 0.14);
    font-weight: 700;
}

.admin-sidebar__subitem--disabled,
.admin-offcanvas__subitem--disabled {
    opacity: 0.45;
    cursor: not-allowed;
    pointer-events: none;
}

.admin-topbar__icon-btn--pos {
    position: relative;
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}

.admin-topbar__icon-btn--pos:hover {
    background: #ffffff;
    color: #f57c00;
    border-color: rgba(245, 124, 0, 0.22);
    box-shadow: 0 14px 28px rgba(245, 124, 0, 0.13);
}

.admin-topbar--pos {
    position: static;
    top: auto;
    min-height: 58px;
    margin: 0.75rem 1.5rem 0.5rem;
    padding: 8px 18px;
    border: 1px solid rgba(15, 23, 42, 0.06);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}

.admin-root--pos .admin-topbar__left {
    min-width: 0;
}

.admin-topbar--pos.admin-topbar--stuck {
    border-color: rgba(245, 124, 0, 0.12);
    box-shadow:
        0 14px 40px rgba(15, 23, 42, 0.12),
        0 0 0 1px rgba(255, 255, 255, 0.64) inset;
}

.admin-main--pos {
    padding: 8px 24px 12px;
}

.admin-root--pos .admin-layout {
    height: 100dvh;
    min-height: 0;
    overflow: hidden;
}

.admin-root--pos .admin-content-wrapper {
    height: 100dvh;
    min-height: 0;
    overflow: hidden;
}

.admin-root--pos .admin-main--pos {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    box-sizing: border-box;
}

@media (max-width: 1200px) {
    .admin-root--pos .admin-layout,
    .admin-root--pos .admin-content-wrapper {
        height: auto;
        min-height: 100dvh;
        overflow: visible;
    }

    .admin-root--pos .admin-main--pos {
        overflow: visible;
        min-height: auto;
        padding: 12px 16px 24px;
    }
}

@media (max-width: 640px) {
    .admin-root--pos .admin-main--pos {
        padding: 10px 12px 18px;
    }
}

.admin-posbar {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
}

.admin-posbar__item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);

    flex-shrink: 1;
    min-width: 0;
}

.admin-posbar__label {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    white-space: nowrap;
    flex-shrink: 0;
}

.admin-posbar__select {
    flex: 1;
    max-width: 220px;
}

.admin-posbar__select :deep(.form-group) {
    margin-bottom: 0;
    width: 100%;
}

.admin-posbar__select :deep(.form-label) {
    display: none;
}

.admin-posbar__select :deep(.form-input),
.admin-posbar__select :deep(.select-trigger) {
    min-height: 40px;
    height: 40px;
    border-radius: 12px;
    border: 1px solid #dbe3ec;
    background: #ffffff;
    font-size: 14px;
    font-weight: 600;
    width: 100%;
    min-width: 0;
    color: #1f2937;
    box-shadow: none;
}

.admin-posbar__select :deep(.form-input:focus),
.admin-posbar__select :deep(.select-trigger:focus) {
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
}

.pos-filter-toggle {
    align-items: center;
    gap: 6px;
    height: 36px;
    padding: 0 14px;
    border-radius: 18px;
    border: 1px solid #dbe3ec;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    cursor: pointer;
    position: relative;
}

.pos-filter-toggle.has-selection {
    border-color: #f59e0b;
    background: #fffbeb;
}

.pos-filter-toggle__badge {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #f59e0b;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pos-sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 9999;
    display: flex;
    align-items: flex-end;
}

.pos-sheet {
    width: 100%;
    background: #fff;
    border-radius: 20px 20px 0 0;
    padding-bottom: env(safe-area-inset-bottom, 16px);
}

.pos-sheet__handle {
    width: 36px;
    height: 4px;
    border-radius: 2px;
    background: #e2e8f0;
    margin: 12px auto 4px;
}

.pos-sheet__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px 12px;
    border-bottom: 0.5px solid #e2e8f0;
}

.pos-sheet__title {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

.pos-sheet__close {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    cursor: pointer;
    font-size: 12px;
    color: #64748b;
}

.pos-sheet__body {
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.pos-sheet__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pos-sheet__label {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.pos-sheet__footer {
    display: flex;
    gap: 10px;
    padding: 12px 16px;
    border-top: 0.5px solid #e2e8f0;
}

.pos-sheet__reset {
    flex: 0 0 auto;
    height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
}

.pos-sheet__apply {
    flex: 1;
    height: 48px;
    border-radius: 14px;
    border: none;
    background: #f59e0b;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
}

.sheet-enter-active,
.sheet-leave-active {
    transition: opacity 0.25s ease;
}

.sheet-enter-active .pos-sheet,
.sheet-leave-active .pos-sheet {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}

.sheet-enter-from .pos-sheet,
.sheet-leave-to .pos-sheet {
    transform: translateY(100%);
}

.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
}

@media (max-width: 1377px) {
    .admin-topbar--pos {
        height: auto;
    }

    .admin-posbar__label {
        display: none;
    }

    .admin-topbar__left {
        width: 100%;
    }

    .admin-posbar {
        gap: 10px;
    }

    .admin-posbar__item {
        width: 100%;
        justify-content: space-between;
    }

    .admin-posbar__select {
        flex: 1;
        min-width: 0;
    }}

    @media (min-width: 768px) {
        .pos-filter-toggle {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .admin-mobile-header {
            display: flex;
        }

        .admin-topbar {
            position: relative;
            top: 0;
            z-index: 20;
        }

        .admin-posbar--desktop {
            display: none;
        }

        .pos-filter-toggle {
            display: flex;
        }

        .admin-sidebar,
        .admin-sidebar__toggle {
            display: none;
        }

        .admin-layout {
            flex-direction: column;
        }
    }

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
</style>
