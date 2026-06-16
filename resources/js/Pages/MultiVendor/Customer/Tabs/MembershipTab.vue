<script setup>
defineProps({
  customer: Object,
  membershipPlan: Object,
})
</script>

<template>
  <div>
    <h2 class="tab-title" :style="{ color: 'var(--purple)' }">My Membership</h2>
    <hr class="purple-hr" />

    <div v-if="membershipPlan" class="membership-card" :style="{ borderColor: membershipPlan.plan_card_color || '#5c2d80' }">
      <div class="membership-card__top">
        <div>
          <h3 class="membership-title">{{ membershipPlan.subscription_name }}</h3>
          <p v-if="membershipPlan.short_description" class="membership-desc">
            {{ membershipPlan.short_description }}
          </p>
        </div>

        <div
          v-if="membershipPlan.badge"
          class="membership-badge"
        >
          {{ membershipPlan.badge.replace('_', ' ') }}
        </div>
      </div>

      <div class="membership-price">
        Rs. {{ membershipPlan.price }}
        <span>/ {{ membershipPlan.billing_interval }}</span>
      </div>

     <!-- <div class="membership-meta">
        <div><strong>Plan Code:</strong> {{ membershipPlan.subscription_plan_code }}</div>
        <div><strong>Auto Renew:</strong> {{ Number(membershipPlan.auto_renew) === 1 ? 'Enabled' : 'Disabled' }}</div>
      </div>-->

      <div v-if="membershipPlan.features?.length" class="membership-features">
        <h5>Features</h5>
        <ul>
          <li v-for="feature in membershipPlan.features" :key="feature.feature_key">
            {{ feature.feature_name }}
            <span v-if="feature.limit_type === 'limited' && feature.limit_value !== null">
              - {{ feature.limit_value }}
            </span>
            <span v-else>
              - Unlimited
            </span>
          </li>
        </ul>
      </div>
    </div>

    <div v-else class="empty-state">
      <img src="/assets/images/no-phone.png" alt="" />
      <h4>No membership plan available right now.</h4>
      <p>
        There is currently no active default membership plan assigned for display.
      </p>
    </div>
  </div>
</template>

<style scoped>
.tab-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 12px;
}

.purple-hr {
  border: 0;
  height: 2px;
  background: linear-gradient(90deg, #5c2d80, transparent);
  margin: 1rem 0 1.5rem 0;
  opacity: 0.3;
}

.membership-card {
  background: #fff;
  border: 2px solid #5c2d80;
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 18px 40px -18px rgba(92, 45, 128, 0.22);
}

.membership-card__top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 16px;
}

.membership-title {
  font-size: 24px;
  font-weight: 800;
  color: #332e78;
  margin-bottom: 6px;
}

.membership-desc {
  color: #6b7280;
  margin-bottom: 0;
  line-height: 1.6;
}

.membership-badge {
  background: rgba(92, 45, 128, 0.1);
  color: #5c2d80;
  border: 1px solid rgba(92, 45, 128, 0.18);
  border-radius: 999px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 700;
  text-transform: capitalize;
  white-space: nowrap;
}

.membership-price {
  font-size: 28px;
  font-weight: 800;
  color: #5c2d80;
  margin-bottom: 16px;
}

.membership-price span {
  font-size: 15px;
  font-weight: 600;
  color: #6b7280;
}

.membership-meta {
  display: grid;
  gap: 10px;
  margin-bottom: 18px;
  color: #374151;
}

.membership-features h5 {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 10px;
  color: #332e78;
}

.membership-features ul {
  padding-left: 18px;
  margin-bottom: 0;
}

.membership-features li {
  margin-bottom: 8px;
  color: #4b5563;
}

.empty-state {
  margin-top: 40px;
  text-align: center;
  max-width: 420px;
  margin-left: auto;
  margin-right: auto;
}

.empty-state img {
  width: 120px;
  margin-bottom: 20px;
  opacity: 0.6;
  filter: drop-shadow(0 10px 15px rgba(92, 45, 128, 0.1));
}

.empty-state h4 {
  font-size: 16px;
  margin-bottom: 10px;
  color: #4a5568;
}

.empty-state p {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.6;
}
</style>