<template>
  <MultiVendorLayout>
    <Banner
      title="About Autosale.lk"
      subtitle="A premium marketplace experience built for trust, clarity, and speed."
      image="/multivendor/black-luxury.webp"
    />

    <section class="vy-account-page py-5">
      <div class="container">
        <div class="row g-5 align-items-start">
          <!-- SIDEBAR -->
          <div class="col-12 col-lg-3">
            <aside class="vy-card p-4 sticky-lg-top">
              <h6 class="fw-bold mb-4 text-purple">Account</h6>

              <button
                class="btn w-100 text-start mb-3 vy-tab-btn"
                :class="activeTab === 'profile' ? 'btn-purple' : 'btn-outline-purple'"
                @click="activeTab = 'profile'"
              >
                <i class="bi bi-person me-2"></i>My Profile
              </button>

                                            <button
                class="btn w-100 text-start mb-3 vy-tab-btn"
                :class="activeTab === 'ads' ? 'btn-purple' : 'btn-outline-purple'"
                @click="activeTab = 'ads'"
              >
                <i class="bi bi-megaphone me-2"></i>My Ads
              </button>

              <button
                class="btn w-100 text-start mb-3 vy-tab-btn"
                :class="activeTab === 'membership' ? 'btn-purple' : 'btn-outline-purple'"
                @click="activeTab = 'membership'"
              >
                <i class="bi bi-gem me-2"></i>My Membership
              </button>

              <button
                class="btn w-100 text-start vy-tab-btn"
                :class="activeTab === 'settings' ? 'btn-purple' : 'btn-outline-purple'"
                @click="activeTab = 'settings'"
              >
                <i class="bi bi-gear me-2"></i>Settings
              </button>


            </aside>
          </div>

          <!-- CONTENT -->
          <div class="col-12 col-lg-9">
            <section class="vy-card p-4 p-xl-5">
<component
  :is="tabs[activeTab]"
  :customer="customer"
  :districts="districts"
  :cities-all="citiesAll"
  :vehicles="vehicles"
  :membership-plan="membershipPlan"
/>
            </section>
          </div>
        </div>
      </div>
    </section>
  </MultiVendorLayout>
</template>

<script>
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue"
import { Link } from "@inertiajs/vue3"
import Banner from "./partials/Banner.vue"
import ProfileTab from "./Tabs/Profile.vue"
import MembershipTab from "./Tabs/MembershipTab.vue"
import SettingsTab from "./Tabs/Settings.vue"

export default {
  name: "AboutUs",
  props: {
  customer: Object,
  districts: Array,
  citiesAll: Array,
  vehicles: Object,
  membershipPlan: Object,
},
  components: { MultiVendorLayout, Link, Banner },
  data() {
    return {
      activeTab: "profile",
      tabs: {
        profile: ProfileTab,
        membership: MembershipTab,
        settings: SettingsTab,
        ads: AdsTab,
      },
    }
  },
}
</script>

<style scoped>
.vy-account-page {
  background: transparent;
  --purple: #5c2d80;
  --purple-light: #ede9fe;
  --purple-dark: #332e78;
}

.vy-card {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 20px;
  box-shadow: 0 20px 40px -15px rgba(121, 72, 235, 0.15);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.vy-card:hover {
  box-shadow: 0 25px 50px -15px rgba(121, 72, 235, 0.15);
}

.btn-purple {
  background-color: var(--purple);
  border-color: var(--purple);
  color: white;
  border-radius: 14px;
  padding: 12px 16px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-purple:hover {
  background-color: var(--purple-dark);
  border-color: var(--purple-dark);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px -5px rgba(139, 92, 246, 0.4);
}

.btn-outline-purple {
  background-color: transparent;
  border: 1.5px solid var(--purple);
  color: var(--purple);
  border-radius: 14px;
  padding: 12px 16px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-outline-purple:hover {
  background-color: var(--purple-light);
  border-color: var(--purple-dark);
  color: var(--purple-dark);
  transform: translateY(-2px);
}

.vy-tab-btn {
  border-radius: 14px;
  padding: 12px 16px;
  font-weight: 600;
  text-align: left;
  display: flex;
  align-items: center;
}

.text-purple {
  color: var(--purple);
}

.sticky-lg-top {
  top: 100px;
  z-index: 100;
}
</style>
