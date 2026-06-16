<script setup>
import { ref } from "vue";
import { useForm, usePage, Head } from "@inertiajs/vue3";

defineOptions({ layout: null });

const page = usePage();
const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("superadmin.login.submit"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div>
        <Head
            :title="
                page.props.appTitle
                    ? `${page.props.appTitle} - Super Admin`
                    : 'Super Admin Login'
            "
        />

        <div class="login-page">
            <div class="login-shell">
                <!-- LEFT -->
                <div class="left-panel">
                    <div class="left-inner">
                        <div class="brand-row">


                           
                        </div>

                        <div class="login-card">
                            <div class="card-top-line"></div>

                            <div class="login-header">
                                <span class="login-badge">Super Admin Access</span>
                                <h1>Welcome back</h1>
                                <p>
                                    Sign in to manage vendors, reports, users,
                                    and system operations.
                                </p>
                            </div>

                            <form
                                @submit.prevent="submit"
                                class="login-form"
                                novalidate
                            >
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <div
                                        class="input-wrap"
                                        :class="{
                                            'has-error': form.errors.email,
                                        }"
                                    >
                                        <span class="input-icon">
                                            <svg
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                            >
                                                <path
                                                    d="M4 6H20V18H4V6Z"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linejoin="round"
                                                />
                                                <path
                                                    d="M4 8L12 13L20 8"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </span>

                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="superadmin@example.com"
                                            autocomplete="username"
                                        />
                                    </div>

                                    <span
                                        v-if="form.errors.email"
                                        class="error-text"
                                    >
                                        {{ form.errors.email }}
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <div
                                        class="input-wrap"
                                        :class="{
                                            'has-error': form.errors.password,
                                        }"
                                    >
                                        <span class="input-icon">
                                            <svg
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                            >
                                                <rect
                                                    x="4"
                                                    y="10"
                                                    width="16"
                                                    height="10"
                                                    rx="2"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                />
                                                <path
                                                    d="M8 10V7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7V10"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                />
                                            </svg>
                                        </span>

                                        <input
                                            id="password"
                                            v-model="form.password"
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            placeholder="Enter your password"
                                            autocomplete="current-password"
                                        />

                                        <button
                                            type="button"
                                            class="toggle-password"
                                            @click="
                                                showPassword = !showPassword
                                            "
                                        >
                                            {{ showPassword ? "Hide" : "Show" }}
                                        </button>
                                    </div>

                                    <span
                                        v-if="form.errors.password"
                                        class="error-text"
                                    >
                                        {{ form.errors.password }}
                                    </span>
                                </div>

                                <div class="form-options">
                                    <label class="remember-me">
                                        <input
                                            type="checkbox"
                                            v-model="form.remember"
                                        />
                                        <span>Remember me</span>
                                    </label>
                                </div>

                                <button
                                    type="submit"
                                    class="submit-btn"
                                    :disabled="form.processing"
                                >
                                    <span
                                        v-if="form.processing"
                                        class="spinner-border spinner-border-sm me-2"
                                        role="status"
                                        aria-hidden="true"
                                    ></span>
                                    {{
                                        form.processing
                                            ? "Signing in..."
                                            : "Sign In"
                                    }}
                                </button>
                            </form>

                            <div class="login-footer">
                                Secure system access • Authorized personnel only
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="right-panel">
                    <div class="visual-stage">
                        <div class="bg-circle bg-circle-1"></div>
                        <div class="bg-circle bg-circle-2"></div>
                        <div class="bg-circle bg-circle-3"></div>

                        <div class="visual-stack">
                            <!-- floating card -->
                            <div class="floating-order-card">
                                <div class="mini-line w-70"></div>
                                <div class="mini-line w-50"></div>

                                <div class="mini-items">
                                    <span>Vendors</span>
                                    <strong>248</strong>
                                </div>

                                <div class="mini-items">
                                    <span>Active Plans</span>
                                    <strong>192</strong>
                                </div>

                                <div class="mini-items">
                                    <span>Support Tickets</span>
                                    <strong>14</strong>
                                </div>
                            </div>

                            <!-- 3D admin monitor -->
                            <div class="pos-3d">
                                <div class="pos-screen">
                                    <div class="screen-top">
                                        <span class="screen-dot dot-red"></span>
                                        <span class="screen-dot dot-yellow"></span>
                                        <span class="screen-dot dot-green"></span>
                                    </div>

                                    <div class="screen-body">
                                        <div class="screen-header">
                                            <div class="screen-title"></div>
                                            <div class="screen-pill"></div>
                                        </div>

                                        <div class="screen-grid">
                                            <div class="stat-card stat-orange">
                                                <small>Vendors</small>
                                                <strong>248</strong>
                                            </div>

                                            <div class="stat-card stat-green">
                                                <small>Revenue</small>
                                                <strong>$18.4K</strong>
                                            </div>

                                            <div class="stat-card stat-blue">
                                                <small>Users</small>
                                                <strong>1,284</strong>
                                            </div>

                                            <div class="stat-card stat-pink">
                                                <small>Status</small>
                                                <strong>Live</strong>
                                            </div>
                                        </div>

                                        <div class="screen-chart">
                                            <span style="height: 38%"></span>
                                            <span style="height: 54%"></span>
                                            <span style="height: 49%"></span>
                                            <span style="height: 72%"></span>
                                            <span style="height: 88%"></span>
                                            <span style="height: 64%"></span>
                                        </div>

                                        <div class="screen-table">
                                            <div class="table-row">
                                                <span>Analytics</span>
                                                <span>Updated</span>
                                            </div>
                                            <div class="table-row">
                                                <span>Subscriptions</span>
                                                <span>Healthy</span>
                                            </div>
                                            <div class="table-row">
                                                <span>System Logs</span>
                                                <span>Synced</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pos-stand"></div>
                                <div class="pos-base"></div>
                            </div>

                            <!-- report card -->
                            <div class="receipt-card">
                                <div class="receipt-top">
                                    <div class="receipt-logo"></div>
                                    <div class="receipt-title">Overview</div>
                                </div>

                                <div class="receipt-line w-80"></div>
                                <div class="receipt-line w-60"></div>
                                <div class="receipt-line w-90"></div>
                                <div class="receipt-line w-55"></div>

                                <div class="receipt-total">
                                    <span>Today</span>
                                    <strong>+12.8%</strong>
                                </div>
                            </div>

                            <!-- mobile card -->
                            <div class="phone-card">
                                <div class="phone-notch"></div>
                                <div class="phone-screen">
                                    <div class="phone-line w-70"></div>
                                    <div class="phone-line w-50"></div>

                                    <div class="phone-stat-grid">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>

                                    <div class="phone-button"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END RIGHT -->
            </div>
        </div>
    </div>
</template>

<style scoped>
*,
*::before,
*::after {
    box-sizing: border-box;
}

.login-page {
    min-height: 100vh;
    background: #ffffff;
}

.login-shell {
    width: 100%;
    height: 100vh;
    padding: 16px;
    overflow: hidden;
    display: grid;
    grid-template-columns: 46% 54%;
    gap: 20px;
}

.login-shell,
.left-panel,
.right-panel {
    min-height: 0;
}

.left-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    overflow: hidden;
}

.left-inner {
    width: 100%;
    max-width: 430px;
}

.brand-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
}

.brand-logo-wrap {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    background: linear-gradient(135deg, #fff1e8, #ffe3d1);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 24px rgba(239, 107, 46, 0.12);
    flex-shrink: 0;
    overflow: hidden;
}

.brand-logo {
    max-width: 34px;
    max-height: 34px;
    object-fit: contain;
}

.brand-text h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 800;
    color: #111827;
    line-height: 1.1;
}

.brand-text p {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.login-card {
    position: relative;
    background: #ffffff;
    border: 1px solid #ececec;
    border-radius: 28px;
    padding: 34px 30px 26px;
    box-shadow: 0 24px 60px rgba(17, 24, 39, 0.08);
    overflow: hidden;
}

.card-top-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #f97316, #fb923c, #fdba74);
}

.login-header {
    margin-bottom: 24px;
}

.login-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 32px;
    padding: 0 14px;
    border-radius: 999px;
    background: #fff7ed;
    color: #ea580c;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 14px;
    border: 1px solid #fed7aa;
}

.login-header h1 {
    margin: 0 0 8px;
    font-size: 32px;
    line-height: 1.1;
    color: #111827;
    font-weight: 800;
}

.login-header p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #374151;
    font-size: 14px;
    font-weight: 700;
}

.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    height: 54px;
    border: 1px solid #d9dde3;
    border-radius: 15px;
    background: #ffffff;
    transition: all 0.2s ease;
}

.input-wrap:focus-within {
    border-color: #f97316;
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12);
}

.input-wrap.has-error {
    border-color: #dc2626;
}

.input-icon {
    width: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f97316;
    flex-shrink: 0;
}

.input-wrap input {
    flex: 1;
    height: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: #111827;
    font-size: 15px;
    padding: 0 14px 0 0;
}

.input-wrap input::placeholder {
    color: #9ca3af;
}

.toggle-password {
    border: none;
    background: transparent;
    color: #ea580c;
    font-size: 13px;
    font-weight: 700;
    padding: 0 14px;
    cursor: pointer;
}

.error-text {
    display: block;
    margin-top: 7px;
    color: #dc2626;
    font-size: 13px;
}

.form-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 2px;
}

.remember-me {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #4b5563;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.remember-me input {
    width: 16px;
    height: 16px;
    accent-color: #f97316;
}

.submit-btn {
    width: 100%;
    height: 56px;
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    transition: all 0.2s ease;
    box-shadow: 0 16px 28px rgba(249, 115, 22, 0.22);
    margin-top: 6px;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.login-footer {
    margin-top: 16px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
}

/* RIGHT */
.right-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.visual-stage {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 0;
    border-radius: 34px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-circle {
    position: absolute;
    border-radius: 50%;
    filter: blur(10px);
}

.bg-circle-1 {
    width: 280px;
    height: 280px;
    top: 6%;
    left: 7%;
    background: radial-gradient(
        circle,
        rgba(249, 115, 22, 0.14),
        transparent 70%
    );
}

.bg-circle-2 {
    width: 360px;
    height: 360px;
    right: 4%;
    top: 8%;
    background: radial-gradient(
        circle,
        rgba(251, 146, 60, 0.12),
        transparent 70%
    );
}

.bg-circle-3 {
    width: 320px;
    height: 320px;
    bottom: 2%;
    left: 18%;
    background: radial-gradient(
        circle,
        rgba(253, 186, 116, 0.12),
        transparent 70%
    );
}

.visual-stack {
    position: relative;
    width: min(100%, 760px);
    height: min(100%, 620px);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 3D MONITOR */
.pos-3d {
    position: relative;
    width: 470px;
    transform: perspective(1400px) rotateY(-14deg) rotateX(7deg);
    z-index: 2;
}

.pos-screen {
    background: linear-gradient(180deg, #20242b 0%, #111827 100%);
    border: 12px solid #111827;
    border-radius: 28px;
    box-shadow:
        0 34px 70px rgba(17, 24, 39, 0.22),
        0 12px 24px rgba(17, 24, 39, 0.12);
    overflow: hidden;
}

.screen-top {
    height: 36px;
    background: #0f172a;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 0 16px;
}

.screen-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
}

.dot-red {
    background: #fb7185;
}

.dot-yellow {
    background: #fbbf24;
}

.dot-green {
    background: #4ade80;
}

.screen-body {
    background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    padding: 18px;
}

.screen-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.screen-title {
    width: 120px;
    height: 14px;
    border-radius: 999px;
    background: #dbe4ef;
}

.screen-pill {
    width: 72px;
    height: 26px;
    border-radius: 999px;
    background: linear-gradient(135deg, #fed7aa, #fdba74);
}

.screen-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}

.stat-card {
    border-radius: 16px;
    padding: 14px;
    color: #111827;
}

.stat-card small {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-card strong {
    font-size: 24px;
    line-height: 1;
    font-weight: 800;
}

.stat-orange {
    background: #fff1e8;
}

.stat-green {
    background: #ecfdf3;
}

.stat-blue {
    background: #eff6ff;
}

.stat-pink {
    background: #fff1f2;
}

.screen-chart {
    height: 98px;
    border-radius: 18px;
    background: #ffffff;
    padding: 14px;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    margin-bottom: 18px;
    box-shadow: inset 0 0 0 1px #eef2f7;
}

.screen-chart span {
    flex: 1;
    border-radius: 10px 10px 4px 4px;
    background: linear-gradient(180deg, #fdba74 0%, #f97316 100%);
}

.screen-table {
    background: #ffffff;
    border-radius: 18px;
    padding: 8px 14px;
    box-shadow: inset 0 0 0 1px #eef2f7;
}

.table-row {
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
    border-bottom: 1px solid #f1f5f9;
}

.table-row:last-child {
    border-bottom: none;
}

.pos-stand {
    width: 70px;
    height: 86px;
    margin: -2px auto 0;
    background: linear-gradient(180deg, #cfd6df 0%, #9aa7b6 100%);
    clip-path: polygon(34% 0%, 66% 0%, 78% 100%, 22% 100%);
}

.pos-base {
    width: 200px;
    height: 24px;
    margin: -2px auto 0;
    border-radius: 999px;
    background: linear-gradient(180deg, #dce4ed 0%, #b8c3d1 100%);
    box-shadow: 0 10px 24px rgba(148, 163, 184, 0.25);
}

/* floating cards */
.floating-order-card {
    position: absolute;
    left: 30px;
    top: 84px;
    width: 210px;
    padding: 18px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(255, 255, 255, 0.8);
    box-shadow: 0 22px 40px rgba(249, 115, 22, 0.12);
    backdrop-filter: blur(12px);
    z-index: 3;
    transform: rotate(-8deg);
}

.mini-line {
    height: 10px;
    border-radius: 999px;
    background: #e8edf4;
    margin-bottom: 10px;
}

.w-70 {
    width: 70%;
}

.w-50 {
    width: 50%;
}

.w-80 {
    width: 80%;
}

.w-60 {
    width: 60%;
}

.w-90 {
    width: 90%;
}

.w-55 {
    width: 55%;
}

.mini-items {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
    color: #374151;
    font-size: 13px;
    font-weight: 600;
}

.receipt-card {
    position: absolute;
    right: 46px;
    top: 90px;
    width: 190px;
    padding: 18px 16px;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 22px 40px rgba(17, 24, 39, 0.08);
    z-index: 3;
    transform: rotate(10deg);
}

.receipt-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.receipt-logo {
    width: 28px;
    height: 28px;
    border-radius: 10px;
    background: linear-gradient(135deg, #fed7aa, #fb923c);
}

.receipt-title {
    font-size: 14px;
    font-weight: 800;
    color: #111827;
}

.receipt-line {
    height: 9px;
    border-radius: 999px;
    background: #edf2f7;
    margin-bottom: 9px;
}

.receipt-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px dashed #e5e7eb;
    font-size: 13px;
    color: #374151;
    font-weight: 700;
}

.receipt-total strong {
    color: #111827;
    font-size: 15px;
}

.phone-card {
    position: absolute;
    right: 90px;
    bottom: 48px;
    width: 136px;
    height: 250px;
    padding: 12px;
    border-radius: 30px;
    background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
    box-shadow: 0 28px 50px rgba(17, 24, 39, 0.16);
    z-index: 3;
    transform: rotate(12deg);
}

.phone-notch {
    width: 64px;
    height: 14px;
    border-radius: 0 0 12px 12px;
    background: #0f172a;
    margin: 0 auto 10px;
}

.phone-screen {
    height: calc(100% - 24px);
    border-radius: 20px;
    background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    padding: 14px 12px;
    display: flex;
    flex-direction: column;
}

.phone-line {
    height: 8px;
    border-radius: 999px;
    background: #e9edf3;
    margin-bottom: 10px;
}

.phone-stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin: 14px 0;
}

.phone-stat-grid span {
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffedd5, #ffe4d6);
}

.phone-button {
    width: 100%;
    height: 38px;
    border-radius: 14px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    margin-top: auto;
}

/* desktop no scroll */
@media (min-width: 992px) {
    .login-page,
    .login-shell {
        height: 100vh;
        overflow: hidden;
    }
}

/* medium */
@media (max-width: 1280px) {
    .pos-3d {
        width: 410px;
    }

    .floating-order-card {
        left: 18px;
        top: 78px;
        width: 190px;
    }

    .receipt-card {
        right: 28px;
        width: 172px;
    }

    .phone-card {
        right: 54px;
        bottom: 36px;
        width: 122px;
        height: 226px;
    }
}

@media (max-width: 991px) {
    .login-shell {
        height: auto;
        min-height: 100vh;
        overflow: visible;
        grid-template-columns: 1fr;
        gap: 16px;
        padding: 14px;
    }

    .left-panel,
    .right-panel {
        overflow: visible;
    }

    .left-panel {
        order: 2;
        padding-bottom: 10px;
    }

    .right-panel {
        order: 1;
        min-height: 360px;
    }

    .visual-stage {
        min-height: 360px;
        border-radius: 26px;
    }

    .visual-stack {
        width: 100%;
        height: 320px;
    }

    .pos-3d {
        width: 310px;
    }

    .floating-order-card {
        left: 10px;
        top: 46px;
        width: 150px;
        padding: 14px;
    }

    .receipt-card {
        right: 10px;
        top: 56px;
        width: 138px;
        padding: 14px 12px;
    }

    .phone-card {
        width: 96px;
        height: 180px;
        right: 32px;
        bottom: 18px;
    }

    .brand-text h2 {
        font-size: 22px;
    }

    .login-header h1 {
        font-size: 28px;
    }
}

@media (max-width: 575px) {
    .login-shell {
        padding: 12px;
    }

    .login-card {
        padding: 28px 20px 22px;
        border-radius: 22px;
    }

    .visual-stage {
        min-height: 300px;
    }

    .visual-stack {
        height: 270px;
    }

    .pos-3d {
        width: 248px;
    }

    .floating-order-card,
    .receipt-card,
    .phone-card {
        display: none;
    }

    .brand-logo-wrap {
        width: 48px;
        height: 48px;
    }

    .brand-logo {
        max-width: 28px;
        max-height: 28px;
    }

    .brand-text h2 {
        font-size: 20px;
    }

    .brand-text p {
        font-size: 13px;
    }
}
</style>
