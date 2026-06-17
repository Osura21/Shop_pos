<script setup>
import { ref, watchEffect } from "vue";
import { useForm, usePage, Head } from "@inertiajs/vue3";



defineOptions({ layout: null });

const page = usePage();
const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

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

const submit = () => {
    form.post(route('vendor.login.submit'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="login-page">

        <Head :title="page.props.appTitle
                ? `${page.props.appTitle} - Restaurant POS Admin`
                : 'Restaurant POS Admin Login'
            " />

        <main class="login-shell">
            <!-- LEFT: LOGIN -->
            <section class="login-panel" aria-label="Vendor admin login">
                <div class="login-panel__inner">
                    <div class="brand-row">
                        <div class="brand-icon" aria-hidden="true">
                            <template v-if="page.props.tenant?.logo_url">
                                <img :src="page.props.tenant.logo_url" alt="Logo" class="brand-logo" />
                            </template>

                            <template v-else>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M8 3V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M12 3V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M10 11V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M17 3C18.6569 3 20 4.34315 20 6V10C20 11.6569 18.6569 13 17 13V21"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M6 3V7C6 9.20914 7.79086 11 10 11C12.2091 11 14 9.20914 14 7V3"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </template>
                        </div>

                        <div class="brand-text">
                            <h2>{{ page.props.appTitle || "Restaurant POS" }}</h2>
                            <p>Vendor Admin Login</p>
                        </div>
                    </div>

                    <div class="login-card">
                        <div class="card-top-line"></div>

                        <div class="login-header">
                            <span class="login-badge">Vendor Portal</span>
                            <h1>Welcome back</h1>
                            <p>Sign in to manage your POS system.</p>
                        </div>

                        <form @submit.prevent="submit" class="login-form" novalidate>
                            <div class="form-group">
                                <label for="email">Email Address</label>

                                <div class="input-wrap" :class="{ 'has-error': form.errors.email }">
                                    <span class="input-icon" aria-hidden="true">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
                                            <path d="M4 6H20V18H4V6Z" stroke="currentColor" stroke-width="2"
                                                stroke-linejoin="round" />
                                            <path d="M4 8L12 13L20 8" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>

                                    <input id="email" v-model="form.email" type="email"
                                        placeholder="admin@restaurant.com" autocomplete="username" inputmode="email" />
                                </div>

                                <span v-if="form.errors.email" class="error-text">
                                    {{ form.errors.email }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>

                                <div class="input-wrap" :class="{ 'has-error': form.errors.password }">
                                    <span class="input-icon" aria-hidden="true">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
                                            <rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor"
                                                stroke-width="2" />
                                            <path d="M8 10V7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7V10"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                    </span>

                                    <input id="password" v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'" placeholder="Enter your password"
                                        autocomplete="current-password" />

                                    <button type="button" class="toggle-password" @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'">
                                        {{ showPassword ? "Hide" : "Show" }}
                                    </button>
                                </div>

                                <span v-if="form.errors.password" class="error-text">
                                    {{ form.errors.password }}
                                </span>
                            </div>

                            <div class="form-options">
                                <label class="remember-me">
                                    <input type="checkbox" v-model="form.remember" />
                                    <span>Remember me</span>
                                </label>
                            </div>

                            <button type="submit" class="submit-btn" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status"
                                    aria-hidden="true"></span>
                                {{ form.processing ? "Signing in..." : "Sign In" }}
                            </button>
                        </form>

                        <div class="login-footer">
                            Protected area â€¢ Authorized vendors only
                        </div>
                    </div>
                </div>
            </section>

            <!-- RIGHT: VISUAL -->
            <section class="visual-panel" aria-hidden="true">
                <div class="visual-stage">
                    <div class="bg-circle bg-circle-1"></div>
                    <div class="bg-circle bg-circle-2"></div>
                    <div class="bg-circle bg-circle-3"></div>

                    <div class="visual-stack">
                        <div class="floating-order-card">
                            <div class="mini-line w-70"></div>
                            <div class="mini-line w-50"></div>

                            <div class="mini-items">
                                <span>Burger Combo</span>
                                <strong>$18</strong>
                            </div>

                            <div class="mini-items">
                                <span>Pasta</span>
                                <strong>$14</strong>
                            </div>

                            <div class="mini-items">
                                <span>Fresh Juice</span>
                                <strong>$6</strong>
                            </div>
                        </div>

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
                                            <small>Orders</small>
                                            <strong>124</strong>
                                        </div>

                                        <div class="stat-card stat-green">
                                            <small>Revenue</small>
                                            <strong>$2.8K</strong>
                                        </div>

                                        <div class="stat-card stat-blue">
                                            <small>Tables</small>
                                            <strong>16</strong>
                                        </div>

                                        <div class="stat-card stat-pink">
                                            <small>Kitchen</small>
                                            <strong>Live</strong>
                                        </div>
                                    </div>

                                    <div class="screen-chart">
                                        <span style="height: 42%"></span>
                                        <span style="height: 65%"></span>
                                        <span style="height: 52%"></span>
                                        <span style="height: 78%"></span>
                                        <span style="height: 90%"></span>
                                        <span style="height: 60%"></span>
                                    </div>

                                    <div class="screen-table">
                                        <div class="table-row">
                                            <span>Table 01</span>
                                            <span>Served</span>
                                        </div>

                                        <div class="table-row">
                                            <span>Table 03</span>
                                            <span>Cooking</span>
                                        </div>

                                        <div class="table-row">
                                            <span>Table 07</span>
                                            <span>Pending</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pos-stand"></div>
                            <div class="pos-base"></div>
                        </div>

                        <div class="receipt-card">
                            <div class="receipt-top">
                                <div class="receipt-logo"></div>
                                <div class="receipt-title">Receipt</div>
                            </div>

                            <div class="receipt-line w-80"></div>
                            <div class="receipt-line w-60"></div>
                            <div class="receipt-line w-90"></div>
                            <div class="receipt-line w-55"></div>

                            <div class="receipt-total">
                                <span>Total</span>
                                <strong>$38.00</strong>
                            </div>
                        </div>

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
            </section>
        </main>
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
    min-height: 100dvh;
    width: 100%;
    background:
        radial-gradient(circle at 12% 10%, rgba(249, 115, 22, 0.08), transparent 30%),
        radial-gradient(circle at 86% 24%, rgba(251, 146, 60, 0.08), transparent 28%),
        #ffffff;
    color: #111827;
}

.login-shell {
    width: 100%;
    min-height: 100vh;
    min-height: 100dvh;
    display: grid;
    grid-template-columns: minmax(420px, 46%) minmax(0, 54%);
    gap: clamp(14px, 2vw, 24px);
    padding: clamp(12px, 2vw, 20px);
}

.login-panel,
.visual-panel {
    min-width: 0;
}

.login-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(10px, 3vw, 34px);
}

.login-panel__inner {
    width: min(100%, 440px);
}

.brand-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: clamp(18px, 3vw, 24px);
}

.brand-icon {
    width: clamp(48px, 5vw, 56px);
    height: clamp(48px, 5vw, 56px);
    border-radius: 18px;
    background: linear-gradient(135deg, #fff1e8, #ffe3d1);
    color: #ef6b2e;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 26px rgba(239, 107, 46, 0.14);
    flex: 0 0 auto;
}

.brand-logo {
    width: clamp(48px, 5vw, 56px);
    height: clamp(48px, 5vw, 56px);
    object-fit: contain;
}

.brand-text {
    min-width: 0;
}

.brand-text h2 {
    margin: 0;
    font-size: clamp(20px, 2vw, 25px);
    line-height: 1.12;
    font-weight: 850;
    color: #111827;
    word-break: break-word;
}

.brand-text p {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: clamp(13px, 1.3vw, 14px);
    font-weight: 600;
}

.login-card {
    position: relative;
    width: 100%;
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid #ececec;
    border-radius: clamp(22px, 3vw, 30px);
    padding: clamp(24px, 4vw, 36px) clamp(18px, 4vw, 32px) clamp(22px, 3vw, 28px);
    box-shadow: 0 24px 60px rgba(17, 24, 39, 0.08);
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.card-top-line {
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #60a5fa, #fdba74);
}

.login-header {
    margin-bottom: clamp(20px, 3vw, 25px);
}

.login-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: 7px 14px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    line-height: 1;
    font-weight: 800;
    margin-bottom: 14px;
    border: 1px solid #bfdbfe;
}

.login-header h1 {
    margin: 0 0 9px;
    font-size: clamp(28px, 4vw, 34px);
    line-height: 1.08;
    color: #111827;
    font-weight: 850;
    letter-spacing: -0.04em;
}

.login-header p {
    margin: 0;
    color: #6b7280;
    font-size: clamp(14px, 1.4vw, 15px);
    line-height: 1.55;
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
    font-weight: 800;
}

.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    min-height: 54px;
    border: 1px solid #d9dde3;
    border-radius: 16px;
    background: #ffffff;
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        transform 0.2s ease;
}

.input-wrap:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12);
}

.input-wrap.has-error {
    border-color: #dc2626;
}

.input-icon {
    width: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3b82f6;
    flex: 0 0 auto;
}

.input-wrap input {
    flex: 1 1 auto;
    min-width: 0;
    height: 52px;
    border: 0;
    outline: 0;
    background: transparent;
    color: #111827;
    font-size: 15px;
    padding: 0 12px 0 0;
}

.input-wrap input::placeholder {
    color: #9ca3af;
}

.toggle-password {
    flex: 0 0 auto;
    border: 0;
    background: transparent;
    color: #1d4ed8;
    font-size: 13px;
    font-weight: 850;
    padding: 0 14px;
    cursor: pointer;
    height: 52px;
    border-radius: 12px;
}

.toggle-password:hover {
    color: #c2410c;
}

.error-text {
    display: block;
    margin-top: 7px;
    color: #dc2626;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 600;
}

.form-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1px;
}

.remember-me {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #4b5563;
    font-size: 14px;
    font-weight: 650;
    cursor: pointer;
    user-select: none;
}

.remember-me input {
    width: 16px;
    height: 16px;
    accent-color: #3b82f6;
}

.submit-btn {
    width: 100%;
    min-height: 56px;
    border: 0;
    border-radius: 17px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
    font-size: 15px;
    font-weight: 850;
    cursor: pointer;
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        opacity 0.2s ease;
    box-shadow: 0 16px 30px rgba(249, 115, 22, 0.24);
    margin-top: 6px;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 20px 36px rgba(249, 115, 22, 0.3);
}

.submit-btn:active:not(:disabled) {
    transform: translateY(0);
}

.submit-btn:disabled {
    opacity: 0.72;
    cursor: not-allowed;
}

.login-footer {
    margin-top: 17px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.45;
}

/* RIGHT VISUAL */
.visual-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.visual-stage {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 520px;
    border-radius: clamp(26px, 4vw, 38px);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
        linear-gradient(180deg, rgba(255, 247, 237, 0.62), rgba(255, 255, 255, 0.9)),
        #fffaf7;
    border: 1px solid rgba(249, 115, 22, 0.08);
}

.bg-circle {
    position: absolute;
    border-radius: 999px;
    filter: blur(10px);
    pointer-events: none;
}

.bg-circle-1 {
    width: 280px;
    height: 280px;
    top: 6%;
    left: 7%;
    background: radial-gradient(circle, rgba(249, 115, 22, 0.15), transparent 70%);
}

.bg-circle-2 {
    width: 370px;
    height: 370px;
    right: 3%;
    top: 8%;
    background: radial-gradient(circle, rgba(251, 146, 60, 0.13), transparent 70%);
}

.bg-circle-3 {
    width: 330px;
    height: 330px;
    bottom: 2%;
    left: 18%;
    background: radial-gradient(circle, rgba(253, 186, 116, 0.14), transparent 70%);
}

.visual-stack {
    position: relative;
    width: min(100%, 760px);
    height: min(100%, 620px);
    min-height: 520px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pos-3d {
    position: relative;
    width: clamp(360px, 45vw, 470px);
    transform: perspective(1400px) rotateY(-14deg) rotateX(7deg);
    z-index: 2;
}

.pos-screen {
    background: linear-gradient(180deg, #20242b 0%, #111827 100%);
    border: clamp(9px, 1.2vw, 12px) solid #111827;
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
    border-radius: 999px;
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
    padding: clamp(14px, 2vw, 18px);
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
    background: linear-gradient(135deg, #bfdbfe, #fdba74);
}

.screen-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}

.stat-card {
    border-radius: 16px;
    padding: clamp(11px, 1.5vw, 14px);
    color: #111827;
}

.stat-card small {
    display: block;
    font-size: 12px;
    font-weight: 650;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-card strong {
    font-size: clamp(20px, 2.4vw, 24px);
    line-height: 1;
    font-weight: 850;
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
    height: clamp(78px, 10vw, 98px);
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
    background: linear-gradient(180deg, #fdba74 0%, #3b82f6 100%);
}

.screen-table {
    background: #ffffff;
    border-radius: 18px;
    padding: 8px 14px;
    box-shadow: inset 0 0 0 1px #eef2f7;
}

.table-row {
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: #374151;
    font-size: 13px;
    font-weight: 650;
    border-bottom: 1px solid #f1f5f9;
}

.table-row:last-child {
    border-bottom: 0;
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

.floating-order-card,
.receipt-card,
.phone-card {
    position: absolute;
    z-index: 3;
}

.floating-order-card {
    left: 30px;
    top: 84px;
    width: 210px;
    padding: 18px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(255, 255, 255, 0.86);
    box-shadow: 0 22px 40px rgba(249, 115, 22, 0.12);
    backdrop-filter: blur(12px);
    transform: rotate(-8deg);
}

.mini-line,
.receipt-line,
.phone-line {
    border-radius: 999px;
    background: #e8edf4;
}

.mini-line {
    height: 10px;
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
    gap: 10px;
    margin-top: 10px;
    color: #374151;
    font-size: 13px;
    font-weight: 650;
}

.receipt-card {
    right: 46px;
    top: 90px;
    width: 190px;
    padding: 18px 16px;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 22px 40px rgba(17, 24, 39, 0.08);
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
    background: linear-gradient(135deg, #bfdbfe, #60a5fa);
}

.receipt-title {
    font-size: 14px;
    font-weight: 850;
    color: #111827;
}

.receipt-line {
    height: 9px;
    margin-bottom: 9px;
}

.receipt-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px dashed #e5e7eb;
    font-size: 13px;
    color: #374151;
    font-weight: 750;
}

.receipt-total strong {
    color: #111827;
    font-size: 15px;
}

.phone-card {
    right: 90px;
    bottom: 48px;
    width: 136px;
    height: 250px;
    padding: 12px;
    border-radius: 30px;
    background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
    box-shadow: 0 28px 50px rgba(17, 24, 39, 0.16);
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
    background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
    padding: 14px 12px;
}

.phone-line {
    height: 8px;
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
    background: linear-gradient(135deg, #dbeafe, #ffe4d6);
}

.phone-button {
    width: 100%;
    height: 38px;
    border-radius: 14px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    margin-top: auto;
}

/* Large tablets / small laptops */
@media (max-width: 1280px) {
    .login-shell {
        grid-template-columns: minmax(390px, 48%) minmax(0, 52%);
    }

    .floating-order-card {
        left: 16px;
        top: 76px;
        width: 185px;
    }

    .receipt-card {
        right: 22px;
        width: 170px;
    }

    .phone-card {
        right: 52px;
        bottom: 36px;
        width: 120px;
        height: 224px;
    }
}

/* Tablet */
@media (max-width: 991px) {
    .login-page {
        background:
            radial-gradient(circle at 18% 0%, rgba(249, 115, 22, 0.1), transparent 34%),
            #ffffff;
    }

    .login-shell {
        min-height: 100dvh;
        grid-template-columns: 1fr;
        gap: 14px;
        padding: 14px;
    }

    .login-panel {
        order: 1;
        padding: 14px 8px 20px;
        align-items: flex-start;
    }

    .login-panel__inner {
        width: min(100%, 500px);
    }

    .visual-panel {
        order: 2;
        min-height: 330px;
        display: flex;
    }

    .visual-stage {
        min-height: 330px;
        height: 330px;
        border-radius: 28px;
    }

    .visual-stack {
        min-height: 300px;
        height: 300px;
    }

    .pos-3d {
        width: 310px;
        transform: perspective(1100px) rotateY(-10deg) rotateX(6deg);
    }

    .floating-order-card {
        left: 18px;
        top: 40px;
        width: 150px;
        padding: 14px;
        border-radius: 18px;
    }

    .receipt-card {
        right: 18px;
        top: 48px;
        width: 138px;
        padding: 14px 12px;
        border-radius: 18px;
    }

    .phone-card {
        width: 94px;
        height: 176px;
        right: 38px;
        bottom: 16px;
        border-radius: 24px;
    }

    .phone-stat-grid span {
        height: 34px;
    }

    .screen-chart {
        height: 76px;
    }
}

/* Mobile */
@media (max-width: 575px) {
    .login-shell {
        padding: 10px;
        gap: 12px;
    }

    .login-panel {
        padding: 8px 0 14px;
    }

    .brand-row {
        gap: 12px;
        margin-bottom: 16px;
    }

    .brand-icon {
        width: 46px;
        height: 46px;
        border-radius: 15px;
    }

    .brand-text h2 {
        font-size: 20px;
    }

    .brand-text p {
        font-size: 13px;
    }

    .login-card {
        padding: 25px 18px 22px;
        border-radius: 22px;
    }

    .login-header h1 {
        font-size: 28px;
    }

    .login-header p {
        font-size: 14px;
    }

    .input-wrap {
        min-height: 52px;
        border-radius: 15px;
    }

    .input-icon {
        width: 42px;
    }

    .input-wrap input,
    .toggle-password {
        height: 50px;
    }

    .toggle-password {
        padding: 0 12px;
        font-size: 12px;
    }

    .submit-btn {
        min-height: 54px;
        border-radius: 15px;
    }

    .visual-panel {
        min-height: 250px;
    }

    .visual-stage {
        min-height: 250px;
        height: 250px;
        border-radius: 22px;
    }

    .visual-stack {
        min-height: 230px;
        height: 230px;
    }

    .pos-3d {
        width: 230px;
        transform: perspective(900px) rotateY(-8deg) rotateX(5deg);
    }

    .screen-top {
        height: 28px;
        padding: 0 12px;
    }

    .screen-dot {
        width: 7px;
        height: 7px;
    }

    .screen-grid {
        gap: 8px;
        margin-bottom: 10px;
    }

    .stat-card {
        border-radius: 12px;
        padding: 9px;
    }

    .stat-card small {
        font-size: 10px;
        margin-bottom: 5px;
    }

    .stat-card strong {
        font-size: 17px;
    }

    .screen-chart {
        height: 54px;
        padding: 10px;
        gap: 6px;
        margin-bottom: 10px;
        border-radius: 14px;
    }

    .screen-table {
        display: none;
    }

    .pos-stand {
        width: 48px;
        height: 56px;
    }

    .pos-base {
        width: 142px;
        height: 18px;
    }

    .floating-order-card,
    .receipt-card,
    .phone-card {
        display: none;
    }
}

/* Extra small phones */
@media (max-width: 380px) {
    .login-shell {
        padding: 8px;
    }

    .login-card {
        padding: 23px 15px 20px;
    }

    .brand-text h2 {
        font-size: 18px;
    }

    .login-header h1 {
        font-size: 25px;
    }

    .remember-me {
        font-size: 13px;
    }

    .visual-panel {
        min-height: 220px;
    }

    .visual-stage {
        height: 220px;
        min-height: 220px;
    }

    .pos-3d {
        width: 205px;
    }
}

/* Short-height screens */
@media (min-width: 992px) and (max-height: 760px) {
    .login-shell {
        min-height: 100vh;
    }

    .login-panel {
        padding-block: 12px;
    }

    .brand-row {
        margin-bottom: 14px;
    }

    .login-card {
        padding-top: 26px;
        padding-bottom: 20px;
    }

    .login-header {
        margin-bottom: 18px;
    }

    .login-form {
        gap: 13px;
    }

    .visual-stage {
        min-height: 0;
    }

    .visual-stack {
        transform: scale(0.88);
    }
}

/* Hide POS visual image on mobile */
@media (max-width: 575px) {
    .visual-panel {
        display: none !important;
    }

    .login-shell {
        grid-template-columns: 1fr;
        min-height: 100vh;
        min-height: 100dvh;
        padding: 14px;
    }

    .login-panel {
        min-height: 100vh;
        min-height: 100dvh;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .login-panel__inner {
        width: 100%;
        max-width: 430px;
    }
}
</style>
