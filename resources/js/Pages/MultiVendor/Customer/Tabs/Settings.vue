<template>
    <div>
        <h2 class="tab-title" :style="{ color: 'var(--purple)' }">Settings</h2>
        <p class="text-muted mb-4">More options coming soon.</p>

        <button class="logout-btn" @click="logout">
            <i class="bi bi-box-arrow-right me-2"></i>
            Logout
        </button>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3"

const logout = () => {
    router.post(route('customer.logout'))
}
</script>

<style scoped>
.tab-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.logout-btn {
    background: linear-gradient(135deg, #5c2d80, #332e78);
    color: white;
    padding: 12px 24px;
    border-radius: 16px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow: 0 10px 20px -8px rgba(92, 45, 128, 0.3);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #332e78, #5c2d80);
    transform: translateY(-2px);
    box-shadow: 0 15px 25px -10px rgba(92, 45, 128, 0.4);
}

.logout-btn:active {
    transform: translateY(0);
}

.logout-btn i {
    font-size: 1.2rem;
}

.settings-option {
    background: #f8fafc;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.settings-option:hover {
    border-color: #5c2d80;
    box-shadow: 0 5px 15px -8px rgba(92, 45, 128, 0.2);
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .tab-title {
        font-size: 1.5rem;
    }
    
    .logout-btn {
        width: 100%;
        padding: 14px 20px;
    }
}
</style>