<script setup>
import { ArrowRight, DollarSign, Clock, Shield } from "lucide-vue-next"
import { Link } from "@inertiajs/vue3"

/* ================= DATA ================= */
const benefits = [
  {
    icon: DollarSign,
    title: "Best Value",
    description: "Get competitive offers from verified buyers",
  },
  {
    icon: Clock,
    title: "Quick Process",
    description: "Receive offers within 24 hours",
  },
  {
    icon: Shield,
    title: "Safe & Secure",
    description: "Protected transactions every step",
  },
]
</script>

<template>
  <section id="sell" class="sell-cta position-relative overflow-hidden py-5">
    <!-- BACKGROUND -->
    <div class="position-absolute top-0 start-0 w-100 h-100">
      <img
        src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?q=80&w=2583&auto=format&fit=crop"
        alt="Luxury car"
        class="w-100 h-100 object-fit-cover"
      />
      <div class="cta-overlay"></div>
    </div>

    <!-- CONTENT -->
    <div class="container position-relative z-2 py-5">
      <div class="row align-items-center g-5">
        <!-- LEFT CONTENT -->
        <div class="col-lg-6 text-white">
          <span class="badge rounded-pill custom-bg-primary bg-opacity-25 custom-text-primary px-4 py-2 mb-4">
            Sell Your Car
          </span>

          <h2 class="fw-bold display-5 display-lg-4 mb-4">
            Get the Best Price <br />
            <span class="custom-text-hero">For Your Vehicle</span>
          </h2>

          <p class="text-white-50 fs-5 mb-4" style="max-width: 520px">
            Join thousands of satisfied sellers. Our platform connects you with
            serious buyers and ensures you get top dollar for your car.
          </p>

          <!-- CTA BUTTONS -->
          <div class="d-flex flex-column flex-sm-row gap-3 mb-5">
            <Link
              href="/sell"
              class="custom-btn custom-btn-primary btn-lg rounded-pill px-5 fw-semibold d-inline-flex align-items-center gap-2"
            >
              Start Selling
              <ArrowRight size="20" class="arrow-hover" />
            </Link>

            <!-- <Link
              href="/sell/quote"
              class="btn btn-outline-light btn-lg rounded-pill px-5 fw-semibold"
            >
              Get Instant Quote
            </Link> -->
          </div>
        </div>

        <!-- RIGHT BENEFITS -->
        <div class="col-lg-6">
          <div class="row g-3">
            <div
              v-for="(benefit, index) in benefits"
              :key="benefit.title"
              class="col-12"
            >
              <div
                class="benefit-card d-flex gap-4"
                :style="{ animationDelay: `${index * 120}ms` }"
              >
                <div class="benefit-icon">
                  <component :is="benefit.icon" size="24" />
                </div>
                <div>
                  <h5 class="fw-bold text-white mb-1">
                    {{ benefit.title }}
                  </h5>
                  <p class="text-white-50 mb-0">
                    {{ benefit.description }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<style scoped>
/* ================= SECTION ================= */
.sell-cta {
  min-height: 600px;
}

/* ================= OVERLAY ================= */
.cta-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to right,
    rgba(0,0,0,.9),
    rgba(0,0,0,.8),
    rgba(0,0,0,.7)
  );
}

/* ================= BENEFIT CARD ================= */
.benefit-card {
  background: rgba(255,255,255,.1);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  padding: 24px;
  border: 1px solid rgba(255,255,255,.12);
  transition: all .3s ease;
  animation: fadeUp .6s ease forwards;
  opacity: 0;
}

.benefit-card:hover {
  background: rgba(255,255,255,.15);
  border-color: rgba(255,255,255,.2);
}

/* ================= ICON ================= */
.benefit-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #5c2d80;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .3s ease;
}

.benefit-card:hover .benefit-icon {
  background: #5c2d80;
  color: #fff;
}

/* ================= BUTTON HOVER ================= */
.arrow-hover {
  transition: transform .3s ease;
}

.btn:hover .arrow-hover {
  transform: translateX(4px);
}

/* ================= ANIMATION ================= */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
