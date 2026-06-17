<template>
    <div class="kitchen-page">
        <Head title="Kitchen Viewer" />

        <TransitionGroup name="kitchen-alert" tag="div" class="kitchen-alert-stack">
            <div
                v-for="alert in newOrderAlerts"
                :key="alert.id"
                class="kitchen-order-alert"
            >
                <div class="kitchen-order-alert__pulse">
                    <i class="bi bi-bell"></i>
                </div>

                <div class="kitchen-order-alert__content">
                    <div class="kitchen-order-alert__eyebrow">
                        New Order Placed
                    </div>
                    <strong>Order #{{ alert.orderNumber }}</strong>
                    <span>{{ alert.itemCount }} item{{ alert.itemCount === 1 ? "" : "s" }} sent to kitchen</span>

                    <div class="kitchen-order-alert__meta">
                        <em>{{ prettyLabel(alert.channel) }}</em>
                        <small v-if="alert.branchName">{{ alert.branchName }}</small>
                        <small v-if="alert.tableName">{{ alert.tableName }}</small>
                    </div>
                </div>

                <button
                    type="button"
                    class="kitchen-order-alert__close"
                    title="Dismiss"
                    @click="dismissNewOrderAlert(alert.id)"
                >
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </TransitionGroup>

        <TransitionGroup name="kitchen-alert" tag="div" class="kitchen-undo-stack">
            <div
                v-for="action in statusUndoActions"
                :key="action.id"
                class="kitchen-undo-alert"
            >
                <div class="kitchen-undo-alert__content">
                    <strong>{{ action.title }}</strong>
                    <span>{{ action.message }}</span>
                </div>

                <button
                    v-if="can('pos-kitchen.edit')"
                    type="button"
                    class="kitchen-undo-alert__button"
                    @click="undoStatusChange(action)"
                >
                    Undo
                </button>

                <button
                    type="button"
                    class="kitchen-undo-alert__close"
                    title="Dismiss"
                    @click="dismissStatusUndoAction(action.id)"
                >
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </TransitionGroup>

        <div class="kitchen-shell">
            <div class="kitchen-topbar">
                <div class="kitchen-topbar__left">
                    <button type="button" class="top-icon-btn" @click="refreshPage" title="Refresh">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>

                    <div class="toolbar-search">
                        <i class="bi bi-search"></i>
                        <input
                            v-model="search"
                            type="text"
                            class="toolbar-search__input"
                            placeholder="Search by order number, register, item..."
                        />
                    </div>

                    <div class="toolbar-filter">
                        <SelectInput
                            v-model="selectedBranch"
                            :options="branchOptions"
                            labelKey="label"
                            valueKey="value"
                            placeholder="All Branches"
                        />
                    </div>

                    <div class="toolbar-filter">
                        <SelectInput
                            v-model="selectedChannel"
                            :options="channelOptions"
                            labelKey="label"
                            valueKey="value"
                            placeholder="All Order Types"
                        />
                    </div>
                </div>

                <div class="kitchen-topbar__right">
                    <div class="status-summary">
                        <button
                            v-for="status in statusTabs"
                            :key="status.value"
                            type="button"
                            class="status-pill"
                            :class="{ 'status-pill--active': selectedStatus === status.value }"
                            @click="selectedStatus = status.value"
                        >
                            <span>{{ status.label }}</span>
                            <strong>{{ countByStatus(status.value) }}</strong>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="filteredOrders.length" class="kitchen-board">
                <section
                    v-for="group in groupedFilteredOrders"
                    :key="group.label"
                    class="kitchen-time-section"
                >
                    <div class="kitchen-time-section__header">
                        <h2>{{ group.label }}</h2>
                        <span>{{ group.orders.length }}</span>
                    </div>

                    <div class="kitchen-grid">
                        <article
                            v-for="order in group.orders"
                            :key="order.id"
                            class="order-card"
                            :class="statusCardClass(order.status)"
                        >
                    <div class="order-card__header">
                        <div class="order-card__header-left">
                            <h3 class="order-card__title">
                                Order #{{ displayOrderNumber(order) }}
                            </h3>

                            <div class="order-card__time-row">
                                <span class="time-chip">
                                    <i class="bi bi-clock"></i>
                                    {{ formatTime(order.sent_to_kitchen_at || order.created_at) }}
                                </span>

                                <span
                                    v-if="order.table?.name"
                                    class="table-chip"
                                >
                                    {{ order.table.name }}
                                </span>
                            </div>
                        </div>

                        <div class="order-card__header-right">
                            <span
                                class="status-badge"
                                :class="statusBadgeClass(order.status)"
                            >
                                {{ statusLabel(order.status) }}
                            </span>
                        </div>
                    </div>

                    <div class="order-card__items">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="order-item"
                        >
                            <div class="order-item__top">
                                <div class="order-item__title">
                                    <strong>{{ trimQty(item.qty) }}x {{ item.product_name }}</strong>
                                </div>

                                <button
                                    v-if="can('pos-kitchen.edit') && itemStatus(item, order) === 'pending'"
                                    type="button"
                                    class="mini-action mini-action--prepare"
                                    :disabled="isItemActionLoading(item, 'prepare')"
                                    @click="startItemPreparing(item)"
                                >
                                    <i
                                        class="bi"
                                        :class="isItemActionLoading(item, 'prepare') ? 'bi-arrow-repeat mini-action__spinner' : 'bi-box-seam'"
                                    ></i>
                                    <span>{{ isItemActionLoading(item, 'prepare') ? 'Starting...' : 'Start Preparing' }}</span>
                                </button>

                                <button
                                    v-else-if="can('pos-kitchen.edit') && itemStatus(item, order) === 'preparing'"
                                    type="button"
                                    class="mini-action mini-action--ready"
                                    :disabled="isItemActionLoading(item, 'ready')"
                                    @click="markItemReady(item)"
                                >
                                    <i
                                        class="bi"
                                        :class="isItemActionLoading(item, 'ready') ? 'bi-arrow-repeat mini-action__spinner' : 'bi-check2-circle'"
                                    ></i>
                                    <span>{{ isItemActionLoading(item, 'ready') ? 'Marking...' : 'Mark As Ready' }}</span>
                                </button>

                                <span
                                    v-else-if="itemStatus(item, order) === 'ready'"
                                    class="mini-action mini-action--item-ready"
                                >
                                    <i class="bi bi-check2-all"></i>
                                    <span>Ready</span>
                                </span>
                            </div>

                            <div
                                v-if="item.options && item.options.length"
                                class="order-item__options"
                            >
                                <div
                                    v-for="option in item.options"
                                    :key="option.id"
                                    class="order-item__option"
                                >
                                    <span class="dot"></span>
                                    <span>
                                        {{ option.option_name }}:
                                        {{ option.value_label || option.value_input || "-" }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="item.notes"
                                class="order-item__notes"
                            >
                                <span class="dot"></span>
                                <span>{{ item.notes }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="order-card__footer">
                        <div class="footer-tags">
                            <span
                                class="footer-tag footer-tag--confirmed"
                                v-if="order.status === 'pending'"
                            >
                                <i class="bi bi-check-lg"></i>
                                Confirmed
                            </span>

                            <span
                                class="footer-tag footer-tag--preparing"
                                v-if="order.status === 'preparing'"
                            >
                                <i class="bi bi-box-seam"></i>
                                Preparing
                            </span>

                            <span
                                class="footer-tag footer-tag--ready"
                                v-if="order.status === 'ready'"
                            >
                                <i class="bi bi-check2-all"></i>
                                Ready
                            </span>

                            <span
                                class="footer-tag"
                                :class="channelFooterClass(order.channel)"
                            >
                                <i :class="channelIcon(order.channel)"></i>
                                {{ prettyLabel(order.channel || 'takeaway') }}
                            </span>
                        </div>
                    </div>
                        </article>
                    </div>
                </section>
            </div>

            <div v-else class="empty-board">
                <div class="empty-board__icon">
                    <i class="bi bi-clipboard2-x"></i>
                </div>
                <h3>No kitchen orders found</h3>
                <p>There are no kitchen tickets matching the selected filters.</p>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, router } from "@inertiajs/vue3";
import VendorAdminLayout from "@/Layouts/VendorAdminLayout.vue";
import SelectInput from "@/Components/SelectInput.vue";
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import Pusher from "pusher-js";
import { usePermission } from "@/composables/usePermission";

export default {
    name: "KitchenViewerIndex",
    layout: VendorAdminLayout,
    components: {
        Head,
        SelectInput,
    },
    setup() {
        const { can } = usePermission();
        return { can };
    },
    props: {
        orders: {
            type: Array,
            default: () => [],
        },
        tenantId: {
            type: [Number, String],
            default: null,
        },
        kitchenAlertSoundEnabled: {
            type: Boolean,
            default: true,
        },
        kitchenAlertSound: {
            type: String,
            default: "bell",
        },
    },
    data() {
        return {
            hiddenReadyOrderIds: [],
readyRemovalTimers: {},
            localOrders: [],
            search: "",
            selectedStatus: "all",
            selectedBranch: "",
            selectedChannel: "",
            itemActionLoading: {},
            kitchenPusher: null,
            kitchenChannel: null,
            currentTimeTick: Date.now(),
            elapsedTimeTimer: null,
            audioContext: null,
            soundUnlocked: false,
            newOrderAlerts: [],
            newOrderAlertTimers: {},
            statusUndoActions: [],
            statusUndoTimers: {},
            statusTabs: [
                { label: "All", value: "all" },
                { label: "Confirmed", value: "pending" },
                { label: "Preparing", value: "preparing" },
                { label: "Ready", value: "ready" },
            ],
            channelOptions: [
                { label: "All Order Types", value: "" },
                { label: "Takeaway", value: "takeaway" },
                { label: "Dine-In", value: "dine_in" },
                { label: "Pick-Up", value: "pick_up" },
                { label: "Drive-Thru", value: "drive_thru" },
                { label: "Pre-Order", value: "pre_order" },
                { label: "Catering", value: "catering" },
            ],
        };
    },
    computed: {
        visibleOrders() {
    return (this.localOrders || []).filter((order) => {
        const status = String(order.status || "pending").toLowerCase();

        return (
            ["pending", "preparing", "ready"].includes(status) &&
            !this.hiddenReadyOrderIds.includes(Number(order.id))
        );
    });
},
        branchOptions() {
            const unique = new Map();

this.visibleOrders.forEach((order) => {
                    const id = order.register?.branch_id ?? order.branch_id ?? "";
                const label = order.register?.branch?.name || order.branch?.name || "";

                if (id && label && !unique.has(String(id))) {
                    unique.set(String(id), { label, value: String(id) });
                }
            });

            return [{ label: "All Branches", value: "" }, ...Array.from(unique.values())];
        },

        filteredOrders() {
return this.visibleOrders.filter((order) => {
                    const orderStatus = String(order.status || "pending");
                const orderBranchId = String(order.register?.branch_id ?? order.branch_id ?? "");
                const orderChannel = String(order.channel || "");

                const matchesStatus =
                    this.selectedStatus === "all" || orderStatus === this.selectedStatus;

                const matchesBranch =
                    !this.selectedBranch || orderBranchId === String(this.selectedBranch);

                const matchesChannel =
                    !this.selectedChannel || orderChannel === String(this.selectedChannel);

                const haystack = [
                    order.uuid,
                    order.id,
                    order.register?.name,
                    order.branch?.name,
                    order.table?.name,
                    order.customer_name,
                    order.waiter_name,
                    order.channel,
                    ...(order.items || []).map((item) => item.product_name),
                    ...(order.items || []).flatMap((item) =>
                        (item.options || []).map((option) => `${option.option_name} ${option.value_label || option.value_input || ""}`)
                    ),
                ]
                    .filter(Boolean)
                    .join(" ")
                    .toLowerCase();

                const matchesSearch =
                    !this.search || haystack.includes(String(this.search).toLowerCase());

                return matchesStatus && matchesBranch && matchesChannel && matchesSearch;
            });
        },

        groupedFilteredOrders() {
            const groups = new Map();

            this.filteredOrders.forEach((order) => {
                const label = this.formatElapsedTime(order.sent_to_kitchen_at || order.created_at);

                if (!groups.has(label)) {
                    groups.set(label, []);
                }

                groups.get(label).push(order);
            });

            return Array.from(groups, ([label, orders]) => ({ label, orders }));
        },
    },
    watch: {
    orders: {
        immediate: true,
        deep: true,
        handler(orders) {
            this.localOrders = this.cloneKitchenOrders(orders);
            this.queueReadyOrdersForRemoval();
        },
    },
},
    mounted() {
        const flashSuccess = this.$page?.props?.flash?.success;
        const flashError = this.$page?.props?.flash?.error;

        if (flashSuccess) this.showSuccessToast(flashSuccess);
        if (flashError) this.showErrorToast(flashError);

        this.restoreStatusUndoActions();
        this.subscribeToKitchenOrders();
        this.setupKitchenSoundUnlock();
        this.elapsedTimeTimer = window.setInterval(() => {
            this.currentTimeTick = Date.now();
        }, 30000);
    },
   beforeUnmount() {
    this.unsubscribeFromKitchenOrders();
    window.removeEventListener("pointerdown", this.unlockKitchenSound);
    window.removeEventListener("keydown", this.unlockKitchenSound);

    if (this.elapsedTimeTimer) {
        clearInterval(this.elapsedTimeTimer);
        this.elapsedTimeTimer = null;
    }

    Object.values(this.newOrderAlertTimers).forEach((timer) => clearTimeout(timer));
    this.newOrderAlertTimers = {};

    Object.values(this.statusUndoTimers).forEach((timer) => clearTimeout(timer));
    this.statusUndoTimers = {};

    Object.values(this.readyRemovalTimers).forEach((timer) => clearTimeout(timer));
    this.readyRemovalTimers = {};

    if (this.audioContext) {
        this.audioContext.close().catch(() => {});
        this.audioContext = null;
    }
},
    methods: {
        setupKitchenSoundUnlock() {
            if (!this.kitchenAlertSoundEnabled) return;

            window.addEventListener("pointerdown", this.unlockKitchenSound, { once: true });
            window.addEventListener("keydown", this.unlockKitchenSound, { once: true });
        },

        async unlockKitchenSound() {
            if (!this.kitchenAlertSoundEnabled) return;

            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                if (!this.audioContext) {
                    this.audioContext = new AudioContext();
                }

                if (this.audioContext.state === "suspended") {
                    await this.audioContext.resume();
                }

                this.soundUnlocked = this.audioContext.state === "running";
            } catch (error) {
                this.soundUnlocked = false;
            }
        },

        playKitchenAlertSound() {
            if (!this.kitchenAlertSoundEnabled) return;

            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                if (!this.audioContext) {
                    this.audioContext = new AudioContext();
                }

                if (this.audioContext.state === "suspended") {
                    this.audioContext.resume().catch(() => {});
                }

                const sound = this.kitchenAlertSound || "bell";
                const patterns = {
                    bell: [[880, 0, 0.13], [1175, 0.16, 0.18]],
                    chime: [[659, 0, 0.12], [880, 0.14, 0.12], [1318, 0.28, 0.2]],
                    ding: [[1046, 0, 0.16]],
                    pulse: [[523, 0, 0.1], [523, 0.14, 0.1], [784, 0.28, 0.16]],
                    classic: [[740, 0, 0.18], [740, 0.24, 0.18]],
                    double_chime: [[784, 0, 0.12], [1046, 0.12, 0.16], [784, 0.34, 0.12], [1318, 0.46, 0.2]],
                    urgent: [[988, 0, 0.08], [988, 0.12, 0.08], [988, 0.24, 0.08], [1318, 0.38, 0.18]],
                };

                (patterns[sound] || patterns.bell).forEach(([frequency, delay, duration]) => {
                    const oscillator = this.audioContext.createOscillator();
                    const gain = this.audioContext.createGain();
                    const startAt = this.audioContext.currentTime + delay;

                    oscillator.type = ["classic", "urgent"].includes(sound) ? "square" : "sine";
                    oscillator.frequency.setValueAtTime(frequency, startAt);
                    gain.gain.setValueAtTime(0.0001, startAt);
                    gain.gain.exponentialRampToValueAtTime(0.28, startAt + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);

                    oscillator.connect(gain);
                    gain.connect(this.audioContext.destination);
                    oscillator.start(startAt);
                    oscillator.stop(startAt + duration + 0.02);
                });

                this.soundUnlocked = this.audioContext.state === "running";
            } catch (error) {
                this.soundUnlocked = false;
            }
        },

        cloneKitchenOrders(orders) {
            return (orders || []).map((order) => ({
                ...order,
                items: (order.items || []).map((item) => ({
                    ...item,
                    options: [...(item.options || [])],
                })),
            }));
        },

        updateLocalOrder(orderId, updater) {
            this.localOrders = this.localOrders.map((order) => {
                if (Number(order.id) !== Number(orderId)) return order;

                return updater(order);
            });
        },

        updateLocalOrderStatus(orderId, status) {
            if (status !== "ready") {
                this.restoreHiddenOrder(orderId);
            }

            this.updateLocalOrder(orderId, (order) => {
                const items = (order.items || []).map((item) => {
                    if (status === "preparing" && String(item.status || "pending") === "pending") {
                        return { ...item, status: "preparing", started_at: item.started_at || new Date().toISOString() };
                    }

                    if (status === "ready" && ["pending", "preparing"].includes(String(item.status || "pending"))) {
                        return { ...item, status: "ready", ready_at: new Date().toISOString() };
                    }

                    if (status === "pending") {
                        return { ...item, status: "pending", started_at: null, ready_at: null };
                    }

                    return item;
                });

                return {
                    ...order,
                    status,
                    ready_at: status === "ready" ? order.ready_at || new Date().toISOString() : null,
                    served_at: status === "served" ? new Date().toISOString() : null,
                    items,
                };
            });

            this.queueReadyOrdersForRemoval();
        },

        updateLocalItemStatus(itemId, status) {
            this.localOrders = this.localOrders.map((order) => {
                const hasItem = (order.items || []).some((item) => Number(item.id) === Number(itemId));
                if (!hasItem) return order;

                const items = (order.items || []).map((item) => {
                    if (Number(item.id) !== Number(itemId)) return item;

                    return {
                        ...item,
                        status,
                        started_at: status === "pending" ? null : item.started_at || new Date().toISOString(),
                        ready_at: status === "ready" ? new Date().toISOString() : null,
                    };
                });

                const statuses = items.map((item) => String(item.status || "pending"));
                const orderStatus = statuses.every((itemStatus) => itemStatus === "ready")
                    ? "ready"
                    : statuses.some((itemStatus) => ["preparing", "ready"].includes(itemStatus))
                        ? "preparing"
                        : "pending";

                if (orderStatus !== "ready") {
                    this.restoreHiddenOrder(order.id);
                }

                return {
                    ...order,
                    status: orderStatus,
                    ready_at: orderStatus === "ready" ? order.ready_at || new Date().toISOString() : null,
                    items,
                };
            });

            this.queueReadyOrdersForRemoval();
        },

        restoreHiddenOrder(orderId) {
            const id = Number(orderId);

            if (this.hiddenReadyOrderIds.includes(id)) {
                this.hiddenReadyOrderIds = this.hiddenReadyOrderIds.filter((hiddenId) => hiddenId !== id);
            }

            if (this.readyRemovalTimers[id]) {
                clearTimeout(this.readyRemovalTimers[id]);
                delete this.readyRemovalTimers[id];
            }
        },

        queueReadyOrdersForRemoval() {
    (this.localOrders || []).forEach((order) => {
        const orderId = Number(order.id);
        const status = String(order.status || "").toLowerCase();

        if (status !== "ready") {
            return;
        }

        if (this.hiddenReadyOrderIds.includes(orderId)) {
            return;
        }

        if (this.readyRemovalTimers[orderId]) {
            return;
        }

        this.readyRemovalTimers[orderId] = setTimeout(() => {
            this.hideReadyOrder(orderId);
        }, 3000);
    });
},

hideReadyOrder(orderId) {
    const id = Number(orderId);

    if (!this.hiddenReadyOrderIds.includes(id)) {
        this.hiddenReadyOrderIds = [...this.hiddenReadyOrderIds, id];
    }

    if (this.readyRemovalTimers[id]) {
        clearTimeout(this.readyRemovalTimers[id]);
        delete this.readyRemovalTimers[id];
    }
},
        showSuccessToast(message) {
            alertSuccess(this.toastHtml(message || "Updated successfully"));
        },

        showErrorToast(message) {
            alertError(this.toastHtml(message || "Something went wrong"));
        },

        toastHtml(message) {
            if (String(message || "").startsWith("STOCK_SHORTAGE|")) {
                const [, ingredient = "-", available = "0.000", needed = "0.000"] = String(message).split("|");
                const escape = (value) => String(value)
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;");

                return `
                    <div class="stock-shortage-toast">
                        <strong>Not Enough Stock</strong>
                        <div><span>Ingredient</span><em>-></em><b>${escape(ingredient)}</b></div>
                        <div><span>Available</span><em>-></em><b>${escape(available)}</b></div>
                        <div><span>Needed</span><em>-></em><b>${escape(needed)}</b></div>
                    </div>
                `;
            }

            const lines = String(message || "").split(/\r?\n/).filter(Boolean);
            const escape = (value) => String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");

            if (lines.length <= 1) {
                return `<div class="kitchen-toast-message"><strong>${escape(lines[0] || message)}</strong></div>`;
            }

            const [title, ...details] = lines;

            return `
                <div class="kitchen-toast-message">
                    <strong>${escape(title)}</strong>
                    ${details.map((line) => `<span>${escape(line)}</span>`).join("")}
                </div>
            `;
        },

        refreshPage() {
            router.reload({
                preserveScroll: true,
                onSuccess: () => {
                    const message = this.$page?.props?.flash?.success;
                    if (message) this.showSuccessToast(message);
                },
                onError: () => {
                    this.showErrorToast("Unable to refresh kitchen board.");
                },
            });
        },

        subscribeToKitchenOrders() {
            const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
            const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || "ap2";

            if (!pusherKey || !this.tenantId || this.kitchenPusher) {
                return;
            }

            this.kitchenPusher = new Pusher(pusherKey, {
                cluster: pusherCluster,
                forceTLS: true,
            });

            this.kitchenChannel = this.kitchenPusher.subscribe(`tenant.${this.tenantId}.kitchen`);
            this.kitchenChannel.bind("kitchen.order.placed", (payload) => {
                const order = payload?.order || {};

                this.showNewOrderAlert(order);
                router.reload({
                    preserveScroll: true,
                    preserveState: true,
                    only: ["orders", "kitchenAlertSoundEnabled", "kitchenAlertSound"],
                    onSuccess: () => {
                        this.playKitchenAlertSound();
                    },
                });
            });
        },

        unsubscribeFromKitchenOrders() {
            if (this.kitchenChannel) {
                this.kitchenChannel.unbind("kitchen.order.placed");
            }

            if (this.kitchenPusher && this.tenantId) {
                this.kitchenPusher.unsubscribe(`tenant.${this.tenantId}.kitchen`);
                this.kitchenPusher.disconnect();
            }

            this.kitchenChannel = null;
            this.kitchenPusher = null;
        },

        showNewOrderAlert(order) {
            const itemCount = Array.isArray(order.items)
                ? order.items.reduce((total, item) => total + Number(item.qty || 0), 0)
                : 0;

            const id = `${order.id || Date.now()}-${Date.now()}`;
            const orderNumber = order.uuid
                ? String(order.uuid).slice(0, 3).toUpperCase()
                : order.id || "-";

            this.newOrderAlerts = [
                {
                    id,
                    orderNumber,
                    itemCount,
                    channel: order.channel || "takeaway",
                    branchName: order.register?.branch?.name || order.branch?.name || "",
                    tableName: order.table?.name || "",
                },
                ...this.newOrderAlerts,
            ].slice(0, 3);

            this.newOrderAlertTimers[id] = setTimeout(() => {
                this.dismissNewOrderAlert(id);
            }, 5600);
        },

        dismissNewOrderAlert(id) {
            if (this.newOrderAlertTimers[id]) {
                clearTimeout(this.newOrderAlertTimers[id]);
                delete this.newOrderAlertTimers[id];
            }

            this.newOrderAlerts = this.newOrderAlerts.filter((alert) => alert.id !== id);
        },

        restoreStatusUndoActions() {
            if (typeof window === "undefined") return;

            try {
                const actions = JSON.parse(
                    window.sessionStorage.getItem("kitchenStatusUndoActions") || "[]"
                );
                const now = Date.now();

                this.statusUndoActions = actions.filter((action) => {
                    return Number(action.expiresAt || 0) > now;
                });

                this.persistStatusUndoActions();
                this.statusUndoActions.forEach((action) => {
                    this.scheduleStatusUndoDismiss(action);
                });
            } catch (error) {
                this.statusUndoActions = [];
                this.persistStatusUndoActions();
            }
        },

        persistStatusUndoActions() {
            if (typeof window === "undefined") return;

            window.sessionStorage.setItem(
                "kitchenStatusUndoActions",
                JSON.stringify(this.statusUndoActions)
            );
        },

        queueStatusUndoAction(action) {
            const undoAction = {
                ...action,
                id: `${action.type}-${action.subjectId}-${Date.now()}`,
                expiresAt: Date.now() + 5000,
            };

            this.statusUndoActions = [
                undoAction,
                ...this.statusUndoActions.filter((existing) => {
                    return !(
                        existing.type === action.type &&
                        Number(existing.subjectId) === Number(action.subjectId)
                    );
                }),
            ].slice(0, 3);

            this.persistStatusUndoActions();
            this.scheduleStatusUndoDismiss(undoAction);

            return undoAction;
        },

        scheduleStatusUndoDismiss(action) {
            if (this.statusUndoTimers[action.id]) {
                clearTimeout(this.statusUndoTimers[action.id]);
            }

            const timeout = Math.max(0, Number(action.expiresAt || 0) - Date.now());

            this.statusUndoTimers[action.id] = setTimeout(() => {
                this.dismissStatusUndoAction(action.id);
            }, timeout);
        },

        dismissStatusUndoAction(id) {
            if (this.statusUndoTimers[id]) {
                clearTimeout(this.statusUndoTimers[id]);
                delete this.statusUndoTimers[id];
            }

            this.statusUndoActions = this.statusUndoActions.filter((action) => action.id !== id);
            this.persistStatusUndoActions();
        },

        undoStatusChange(action) {
            const routeName =
                action.type === "item"
                    ? "vendor.pos.kitchen.items.undo-status"
                    : "vendor.pos.kitchen.undo-status";

            if (action.type === "item") {
                this.updateLocalItemStatus(action.subjectId, action.previousStatus);
            } else {
                this.updateLocalOrderStatus(action.subjectId, action.previousStatus);
            }

            router.patch(
                route(routeName, action.subjectId),
                { status: action.previousStatus },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.dismissStatusUndoAction(action.id);
                        this.showSuccessToast(
                            this.$page?.props?.flash?.success || "Status change undone."
                        );
                    },
                    onError: (errors) => {
                        if (action.nextStatus) {
                            if (action.type === "item") {
                                this.updateLocalItemStatus(action.subjectId, action.nextStatus);
                            } else {
                                this.updateLocalOrderStatus(action.subjectId, action.nextStatus);
                            }
                        }

                        this.showErrorToast(this.errorMessage(errors, "Unable to undo status."));
                    },
                }
            );
        },

        countByStatus(status) {
         if (status === "all") return this.visibleOrders.length;

return this.visibleOrders.filter(
    (order) => String(order.status || "pending") === status
).length;
        },

        startPreparing(order) {
            const previousStatus = String(order.status || "pending");
            const undoAction = this.queueStatusUndoAction({
                type: "order",
                subjectId: order.id,
                previousStatus,
                nextStatus: "preparing",
                title: `Order #${this.displayOrderNumber(order)} moved to preparing`,
                message: "Undo available for 5 seconds.",
            });
            this.updateLocalOrderStatus(order.id, "preparing");

            router.patch(
                route("vendor.pos.kitchen.start-preparing", order.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.showSuccessToast(
                            this.$page?.props?.flash?.success || "Order moved to preparing."
                        );
                    },
                    onError: (errors) => {
                        this.updateLocalOrderStatus(order.id, previousStatus);
                        this.dismissStatusUndoAction(undoAction.id);
                        this.showErrorToast(this.errorMessage(errors, "Unable to update order."));
                    },
                }
            );
        },

        startItemPreparing(item) {
            const previousStatus = String(item.status || "pending");
            const undoAction = this.queueStatusUndoAction({
                type: "item",
                subjectId: item.id,
                previousStatus,
                nextStatus: "preparing",
                title: `${item.product_name} moved to preparing`,
                message: "Undo available for 5 seconds.",
            });
            this.updateLocalItemStatus(item.id, "preparing");

            this.setItemActionLoading(item, "prepare", true);

            router.patch(
                route("vendor.pos.kitchen.items.start-preparing", item.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.showSuccessToast(
                            this.$page?.props?.flash?.success || "Item moved to preparing."
                        );
                    },
                    onError: (errors) => {
                        this.updateLocalItemStatus(item.id, previousStatus);
                        this.dismissStatusUndoAction(undoAction.id);
                        this.showErrorToast(this.errorMessage(errors, "Unable to update item."));
                    },
                    onFinish: () => {
                        this.setItemActionLoading(item, "prepare", false);
                    },
                }
            );
        },

        markItemReady(item) {
            const previousStatus = String(item.status || "preparing");
            const undoAction = this.queueStatusUndoAction({
                type: "item",
                subjectId: item.id,
                previousStatus,
                nextStatus: "ready",
                title: `${item.product_name} marked ready`,
                message: "Undo available for 5 seconds.",
            });
            this.updateLocalItemStatus(item.id, "ready");

            this.setItemActionLoading(item, "ready", true);

            router.patch(
                route("vendor.pos.kitchen.items.mark-ready", item.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.showSuccessToast(
                            this.$page?.props?.flash?.success || "Item marked as ready."
                        );
                    },
                    onError: (errors) => {
                        this.updateLocalItemStatus(item.id, previousStatus);
                        this.dismissStatusUndoAction(undoAction.id);
                        this.showErrorToast(this.errorMessage(errors, "Unable to update item."));
                    },
                    onFinish: () => {
                        this.setItemActionLoading(item, "ready", false);
                    },
                }
            );
        },

        markReady(order) {
            const previousStatus = String(order.status || "preparing");
            const undoAction = this.queueStatusUndoAction({
                type: "order",
                subjectId: order.id,
                previousStatus,
                nextStatus: "ready",
                title: `Order #${this.displayOrderNumber(order)} marked ready`,
                message: "Undo available for 5 seconds.",
            });
            this.updateLocalOrderStatus(order.id, "ready");

            router.patch(
                route("vendor.pos.kitchen.mark-ready", order.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.showSuccessToast(
                            this.$page?.props?.flash?.success || "Order marked as ready."
                        );
                    },
                    onError: (errors) => {
                        this.updateLocalOrderStatus(order.id, previousStatus);
                        this.dismissStatusUndoAction(undoAction.id);
                        this.showErrorToast(this.errorMessage(errors, "Unable to update order."));
                    },
                }
            );
        },

        markServed(order) {
            const previousStatus = String(order.status || "ready");
            const undoAction = this.queueStatusUndoAction({
                type: "order",
                subjectId: order.id,
                previousStatus,
                nextStatus: "served",
                title: `Order #${this.displayOrderNumber(order)} marked served`,
                message: "Undo available for 5 seconds.",
            });
            this.updateLocalOrderStatus(order.id, "served");

            router.patch(
                route("vendor.pos.kitchen.mark-served", order.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.showSuccessToast(
                            this.$page?.props?.flash?.success || "Order marked as served."
                        );
                    },
                    onError: (errors) => {
                        this.updateLocalOrderStatus(order.id, previousStatus);
                        this.dismissStatusUndoAction(undoAction.id);
                        this.showErrorToast(this.errorMessage(errors, "Unable to update order."));
                    },
                }
            );
        },

        errorMessage(errors, fallback) {
            if (errors?.general) return errors.general;

            const first = Object.values(errors || {})[0];
            if (Array.isArray(first)) return first[0] || fallback;
            if (typeof first === "string") return first;

            return fallback;
        },

        displayOrderNumber(order) {
            if (order.uuid) {
                return String(order.uuid).slice(0, 3).toUpperCase();
            }

            return order.id;
        },

        formatTime(value) {
            if (!value) return "-";

            try {
                return new Date(value).toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                });
            } catch (e) {
                return value;
            }
        },

        formatElapsedTime(value) {
            if (!value) return "Unknown time";

            const date = new Date(value);
            const timestamp = date.getTime();

            if (Number.isNaN(timestamp)) return "Unknown time";

            const elapsedSeconds = Math.max(
                0,
                Math.floor((this.currentTimeTick - timestamp) / 1000)
            );
            const elapsedMinutes = Math.floor(elapsedSeconds / 60);
            const elapsedHours = Math.floor(elapsedMinutes / 60);
            const elapsedDays = Math.floor(elapsedHours / 24);

            if (elapsedSeconds < 60) return "just now";
            if (elapsedMinutes < 60) {
                return `${elapsedMinutes} min${elapsedMinutes === 1 ? "" : "s"} ago`;
            }
            if (elapsedHours < 24) {
                return `${elapsedHours} hr${elapsedHours === 1 ? "" : "s"} ago`;
            }

            return `${elapsedDays} day${elapsedDays === 1 ? "" : "s"} ago`;
        },

        trimQty(value) {
            const numeric = Number(value || 0);
            return Number.isInteger(numeric) ? numeric : numeric.toFixed(3);
        },

        itemStatus(item, order) {
            const status = String(item?.status || "").toLowerCase();

            if (["pending", "preparing", "ready"].includes(status)) {
                return status;
            }

            const orderStatus = String(order?.status || "pending").toLowerCase();
            return ["pending", "preparing", "ready"].includes(orderStatus) ? orderStatus : "pending";
        },

        itemActionKey(item, action) {
            return `${action}-${item?.id}`;
        },

        isItemActionLoading(item, action) {
            return Boolean(this.itemActionLoading[this.itemActionKey(item, action)]);
        },

        setItemActionLoading(item, action, value) {
            this.itemActionLoading = {
                ...this.itemActionLoading,
                [this.itemActionKey(item, action)]: value,
            };
        },

        prettyLabel(value) {
            return String(value || "")
                .replace(/_/g, " ")
                .replace(/\b\w/g, (char) => char.toUpperCase());
        },

        statusLabel(status) {
            const map = {
                pending: "Confirmed",
                preparing: "Preparing",
                ready: "Ready",
            };

            return map[status] || this.prettyLabel(status);
        },

        statusBadgeClass(status) {
            const map = {
                pending: "status-badge--pending",
                preparing: "status-badge--preparing",
                ready: "status-badge--ready",
            };

            return map[status] || "status-badge--pending";
        },

        statusCardClass(status) {
            const map = {
                pending: "order-card--pending",
                preparing: "order-card--preparing",
                ready: "order-card--ready",
            };

            return map[status] || "order-card--pending";
        },

        channelIcon(channel) {
            const map = {
                takeaway: "bi bi-bag",
                dine_in: "bi bi-cup-hot",
                pick_up: "bi bi-box-seam",
                drive_thru: "bi bi-car-front",
                pre_order: "bi bi-calendar-event",
                catering: "bi bi-people",
                pms: "bi bi-building-check",
            };

            return map[channel] || "bi bi-circle";
        },

        channelFooterClass(channel) {
            const map = {
                takeaway: "footer-tag--takeaway",
                dine_in: "footer-tag--dine-in",
                pick_up: "footer-tag--pick-up",
                drive_thru: "footer-tag--drive-thru",
                pre_order: "footer-tag--pre-order",
                catering: "footer-tag--catering",
                pms: "footer-tag--pms",
            };

            return map[channel] || "footer-tag--takeaway";
        },
    },
};
</script>
<style scoped>
.kitchen-page {
    min-height: calc(100vh - 110px);
    background: #f4f5fb;
}

.kitchen-alert-stack {
    position: fixed;
    top: 22px;
    right: 22px;
    z-index: 1100;
    display: grid;
    gap: 12px;
    width: min(380px, calc(100vw - 32px));
    pointer-events: none;
}

.kitchen-undo-stack {
    position: fixed;
    top: 22px;
    left: 50%;
    z-index: 1100;
    display: grid;
    gap: 10px;
    width: min(420px, calc(100vw - 32px));
    pointer-events: none;
    transform: translateX(-50%);
}

.kitchen-undo-alert {
    pointer-events: auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto 30px;
    gap: 10px;
    align-items: center;
    padding: 12px;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    background: rgba(255, 251, 235, 0.98);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
}

.kitchen-undo-alert__content {
    display: grid;
    gap: 2px;
    min-width: 0;
}

.kitchen-undo-alert__content strong {
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.kitchen-undo-alert__content span {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.35;
}

.kitchen-undo-alert__button {
    min-height: 32px;
    border: none;
    border-radius: 9px;
    background: #3b82f6;
    color: #fff;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 900;
    cursor: pointer;
}

.kitchen-undo-alert__button:hover {
    background: #d97706;
}

.kitchen-undo-alert__close {
    width: 30px;
    height: 30px;
    border: 1px solid #fde68a;
    border-radius: 9px;
    background: #eff6ff;
    color: #92400e;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.kitchen-order-alert {
    pointer-events: auto;
    position: relative;
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr) 30px;
    gap: 12px;
    align-items: flex-start;
    overflow: hidden;
    padding: 14px;
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    background:
        linear-gradient(135deg, rgba(240, 253, 244, 0.96), rgba(255, 255, 255, 0.98)),
        #fff;
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.18);
}

.kitchen-order-alert::before {
    content: "";
    position: absolute;
    inset: auto 0 0 0;
    height: 4px;
    background: linear-gradient(90deg, #22c55e, #3b82f6);
    animation: kitchenAlertTimer 5.6s linear forwards;
}

.kitchen-order-alert__pulse {
    position: relative;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #dcfce7;
    color: #16a34a;
    font-size: 20px;
}

.kitchen-order-alert__pulse::after {
    content: "";
    position: absolute;
    inset: -5px;
    border-radius: 18px;
    border: 2px solid rgba(34, 197, 94, 0.24);
    animation: kitchenAlertPulse 1.5s ease-out infinite;
}

.kitchen-order-alert__content {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.kitchen-order-alert__eyebrow {
    color: #16a34a;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0;
}

.kitchen-order-alert__content strong {
    color: #0f172a;
    font-size: 18px;
    font-weight: 900;
    line-height: 1.15;
}

.kitchen-order-alert__content span {
    color: #475569;
    font-size: 13px;
    font-weight: 700;
}

.kitchen-order-alert__meta {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
    margin-top: 4px;
}

.kitchen-order-alert__meta em,
.kitchen-order-alert__meta small {
    min-height: 24px;
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0 9px;
    font-size: 11px;
    font-weight: 900;
    font-style: normal;
}

.kitchen-order-alert__meta em {
    background: #eff6ff;
    color: #3b82f6;
}

.kitchen-order-alert__meta small {
    background: #eef2ff;
    color: #4f46e5;
}

.kitchen-order-alert__close {
    width: 30px;
    height: 30px;
    border: 1px solid #dbe2ea;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.78);
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.kitchen-order-alert__close:hover {
    border-color: #60a5fa;
    color: #3b82f6;
}

.kitchen-alert-enter-active,
.kitchen-alert-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
}

.kitchen-alert-enter-from,
.kitchen-alert-leave-to {
    opacity: 0;
    transform: translate3d(24px, -10px, 0) scale(0.96);
}

.kitchen-alert-move {
    transition: transform 0.22s ease;
}

@keyframes kitchenAlertPulse {
    0% {
        opacity: 0.8;
        transform: scale(0.88);
    }
    100% {
        opacity: 0;
        transform: scale(1.12);
    }
}

@keyframes kitchenAlertTimer {
    from {
        transform: scaleX(1);
        transform-origin: left;
    }
    to {
        transform: scaleX(0);
        transform-origin: left;
    }
}

.kitchen-shell {
    display: grid;
    gap: 14px;
}

.kitchen-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e8ebf2;
    border-radius: 14px;
    padding: 12px;
}

.kitchen-topbar__left,
.kitchen-topbar__right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.top-icon-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1px solid #dbe2ea;
    background: #fff;
    color: #5f6b7a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.16s ease;
}

.top-icon-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}

.toolbar-search {
    position: relative;
    width: 220px;
}

.toolbar-search i {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #9aa4b2;
}

.toolbar-search__input {
    width: 100%;
    height: 42px;
    border: 1px solid #dbe2ea;
    border-radius: 12px;
    padding: 0 14px 0 40px;
    font-size: 13px;
    background: #fff;
    outline: none;
}

.toolbar-filter {
    min-width: 180px;
}

.toolbar-filter :deep(.form-group) {
    margin-bottom: 0;
}

.toolbar-filter :deep(.form-label) {
    display: none;
}

.toolbar-filter :deep(.select-trigger),
.toolbar-filter :deep(.form-input) {
    min-height: 42px;
    border-radius: 12px;
}

.status-summary {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.status-pill {
    min-height: 40px;
    border: 1px solid #dde3ed;
    border-radius: 999px;
    background: #fff;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.status-pill strong {
    min-width: 20px;
    height: 20px;
    border-radius: 999px;
    background: #f1f5f9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.status-pill--active {
    border-color: #8b5cf6;
    background: #f5f3ff;
    color: #7c3aed;
}

.kitchen-board {
    display: grid;
    gap: 18px;
}

.kitchen-time-section {
    display: grid;
    gap: 10px;
}

.kitchen-time-section__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 36px;
}

.kitchen-time-section__header h2 {
    margin: 0;
    font-size: 15px;
    font-weight: 900;
    color: #334155;
}

.kitchen-time-section__header span {
    min-width: 28px;
    height: 24px;
    border-radius: 999px;
    background: #eef2ff;
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    font-size: 11px;
    font-weight: 900;
}

.kitchen-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
    align-items: start;
}

.order-card {
    background: #fff;
    border: 1px solid #e8ebf2;
    border-radius: 14px;
    padding: 12px;
    min-height: 250px;
}

.order-card--pending {
    border-top: 3px solid #3b82f6;
}

.order-card--preparing {
    border-top: 3px solid #8b5cf6;
}

.order-card--ready {
    border-top: 3px solid #10b981;
}

.order-card__header {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}

.order-card__title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #334155;
}

.order-card__time-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 6px;
}

.time-chip,
.table-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
}

.table-chip {
    padding: 3px 8px;
    background: #dbeafe;
    color: #c2410c;
    border-radius: 8px;
}

.status-badge {
    min-height: 26px;
    padding: 0 10px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
}

.status-badge--pending {
    background: #dbeafe;
    color: #2563eb;
}

.status-badge--preparing {
    background: #ede9fe;
    color: #7c3aed;
}

.status-badge--ready {
    background: #d1fae5;
    color: #047857;
}

.order-card__items {
    display: grid;
    gap: 10px;
}

.order-item {
    border: 1px dashed #dde3ed;
    border-radius: 12px;
    padding: 10px;
}

.order-item__top {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.order-item__title strong {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    line-height: 1.5;
}

.order-item__options {
    display: grid;
    gap: 4px;
    margin-top: 8px;
}

.order-item__option,
.order-item__notes {
    display: inline-flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12px;
    color: #9aa4b2;
    line-height: 1.45;
}

.dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #9aa4b2;
    margin-top: 6px;
    flex-shrink: 0;
}

.mini-action {
    min-height: 30px;
    border: none;
    border-radius: 8px;
    padding: 0 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    width: fit-content;
}

.mini-action:disabled {
    cursor: not-allowed;
    opacity: 0.78;
}

.mini-action__spinner {
    animation: kitchen-spin 0.8s linear infinite;
}

.mini-action--prepare {
    background: #f3e8ff;
    color: #8b5cf6;
}

.mini-action--ready {
    background: #d1fae5;
    color: #10b981;
}

.mini-action--served {
    background: #e2e8f0;
    color: #475569;
}

.mini-action--item-ready {
    background: #e2e8f0;
    color: #475569;
    cursor: default;
}

@keyframes kitchen-spin {
    to {
        transform: rotate(360deg);
    }
}

.order-card__footer {
    margin-top: 12px;
}

.footer-tags {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.footer-tag {
    min-height: 24px;
    padding: 0 8px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
}

.footer-tag--confirmed {
    background: #dbeafe;
    color: #2563eb;
}

.footer-tag--preparing {
    background: #f3e8ff;
    color: #8b5cf6;
}

.footer-tag--ready {
    background: #d1fae5;
    color: #10b981;
}

.footer-tag--takeaway {
    background: #fef3c7;
    color: #d97706;
}

.footer-tag--dine-in {
    background: #dbeafe;
    color: #2563eb;
}

.footer-tag--pick-up {
    background: #dcfce7;
    color: #16a34a;
}

.footer-tag--drive-thru {
    background: #fee2e2;
    color: #dc2626;
}

.footer-tag--pre-order {
    background: #ede9fe;
    color: #7c3aed;
}

.footer-tag--catering {
    background: #cffafe;
    color: #0f766e;
}

.footer-tag--pms {
    background: #ccfbf1;
    color: #0f766e;
}

.empty-board {
    min-height: 420px;
    display: grid;
    place-items: center;
    text-align: center;
    background: #fff;
    border: 1px solid #e8ebf2;
    border-radius: 14px;
    padding: 24px;
}

.empty-board__icon {
    width: 74px;
    height: 74px;
    border-radius: 22px;
    background: #f8fafc;
    display: grid;
    place-items: center;
    font-size: 30px;
    color: #94a3b8;
    margin: 0 auto 12px;
}

.empty-board h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 900;
    color: #334155;
}

.empty-board p {
    margin: 8px 0 0;
    color: #64748b;
    font-size: 14px;
}

@media (max-width: 1700px) {
    .kitchen-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
}

@media (max-width: 1450px) {
    .kitchen-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 1180px) {
    .kitchen-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .kitchen-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .kitchen-grid {
        grid-template-columns: 1fr;
    }

    .kitchen-topbar {
        padding: 10px;
    }

    .toolbar-search {
        width: 100%;
    }

    .toolbar-filter {
        min-width: 100%;
    }
}
:deep(.kitchen-swal-toast) {
    width: 380px !important;
    border: 1px solid rgba(226, 232, 240, 0.95) !important;
    border-radius: 18px !important;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14) !important;
    padding: 14px 16px !important;
    backdrop-filter: blur(10px);
}

:deep(.kitchen-swal-toast .swal2-html-container) {
    margin: 0 !important;
}

:deep(.kitchen-toast-message) {
    display: grid;
    gap: 4px;
    text-align: left;
}

:deep(.kitchen-toast-message strong) {
    font-size: 15px;
    font-weight: 900;
    color: #0f172a;
}

:deep(.kitchen-toast-message span) {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    line-height: 1.35;
}

:deep(.stock-shortage-toast) {
    display: grid;
    gap: 6px;
    min-width: 230px;
    text-align: left;
}

:deep(.stock-shortage-toast > strong) {
    font-size: 15px;
    font-weight: 900;
    color: #0f172a;
}

:deep(.stock-shortage-toast div) {
    display: grid;
    grid-template-columns: 82px 18px minmax(0, 1fr);
    gap: 4px;
    align-items: baseline;
}

:deep(.stock-shortage-toast span) {
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
}

:deep(.stock-shortage-toast em) {
    font-style: normal;
    font-size: 13px;
    font-weight: 900;
    color: #94a3b8;
}

:deep(.stock-shortage-toast b) {
    font-size: 13px;
    font-weight: 900;
    color: #334155;
    word-break: break-word;
}

:deep(.kitchen-swal-toast__title) {
    margin: 0 !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.45 !important;
}

:deep(.kitchen-swal-toast__icon) {
    margin: 0 10px 0 0 !important;
    width: 28px !important;
    height: 28px !important;
    min-width: 28px !important;
    min-height: 28px !important;
    border-width: 2px !important;
}

:deep(.kitchen-swal-toast .swal2-actions) {
    display: none !important;
}

:deep(.kitchen-swal-toast .swal2-timer-progress-bar) {
    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #10b981) !important;
    height: 4px !important;
}

:deep(.kitchen-swal-toast.swal2-show) {
    animation: kitchenToastIn 0.28s ease-out;
}

:deep(.kitchen-swal-toast.swal2-hide) {
    animation: kitchenToastOut 0.18s ease-in forwards;
}

@keyframes kitchenToastIn {
    0% {
        opacity: 0;
        transform: translate3d(24px, -10px, 0) scale(0.96);
    }
    100% {
        opacity: 1;
        transform: translate3d(0, 0, 0) scale(1);
    }
}

@keyframes kitchenToastOut {
    0% {
        opacity: 1;
        transform: translate3d(0, 0, 0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate3d(18px, -6px, 0) scale(0.98);
    }
}
</style>
