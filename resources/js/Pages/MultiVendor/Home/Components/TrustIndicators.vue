<script setup>
import { CheckCircle, Shield, Award, Headphones, Star } from "lucide-vue-next"

/* ================= DATA ================= */

const indicators = [
  {
    icon: Shield,
    title: "Vehicle Inspection",
    description:
      "Every car undergoes a comprehensive 150-point inspection by certified mechanics.",
  },
  {
    icon: CheckCircle,
    title: "Verified Sellers",
    description:
      "All sellers are verified with ID checks and background verification.",
  },
  {
    icon: Award,
    title: "Quality Guarantee",
    description:
      "7-day money-back guarantee on all purchases. Your satisfaction is our priority.",
  },
  {
    icon: Headphones,
    title: "24/7 Support",
    description:
      "Our expert team is available around the clock to assist you.",
  },
]

const testimonials = [
  {
    name: "Michael Chen",
    role: "BMW M4 Buyer",
    content:
      "The buying process was seamless. Found my dream car and the inspection report gave me complete confidence.",
    rating: 5,
    image:
      "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop",
  },
  {
    name: "Sarah Williams",
    role: "Porsche 911 Seller",
    content:
      "Sold my car in just 3 days at a great price. The platform made everything incredibly easy.",
    rating: 5,
    image:
      "https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=200&auto=format&fit=crop",
  },
  {
    name: "David Rodriguez",
    role: "Tesla Model S Buyer",
    content:
      "Outstanding customer service. They answered all my questions and handled everything professionally.",
    rating: 5,
    image:
      "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=200&auto=format&fit=crop",
  },
]
</script>

<template>
  <section id="trust" class="py-5 bg-light bg-opacity-50">
    <div class="container py-5">

      <!-- HEADER -->
      <div class="text-center mb-5">
        <span class="badge rounded-pill custom-bg-primary bg-opacity-10 custom-text-primary px-4 py-2 mb-3">
          Why Choose Us
        </span>
        <h2 class="fw-bold display-7 mb-3">Trusted by Thousands</h2>
        <p class="text-muted display-8 mx-auto" style="max-width: 720px">
          We've built our reputation on transparency, quality, and exceptional service.
        </p>
      </div>

      <!-- TRUST INDICATORS -->
      <div class="row g-4 mb-5">
        <div
          v-for="indicator in indicators"
          :key="indicator.title"
          class="col-12 col-md-6 col-lg-3"
        >
          <div class="indicator-card h-100">
            <div class="indicator-icon">
              <component :is="indicator.icon" size="28" />
            </div>
            <h5 class="fw-bold mb-2">{{ indicator.title }}</h5>
            <p class="text-muted mb-0">
              {{ indicator.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- TESTIMONIALS -->
      <!-- <div class="mb-5">
        <h3 class="fw-bold text-center mb-4">
          What Our Customers Say
        </h3>

        <div class="row g-4">
          <div
            v-for="testimonial in testimonials"
            :key="testimonial.name"
            class="col-12 col-md-4"
          >
            <div class="testimonial-card h-100">
              <div class="d-flex gap-1 mb-3">
                <Star
                  v-for="i in testimonial.rating"
                  :key="i"
                  size="18"
                  class="star"
                />
              </div>
              <p class="mb-4 text-dark">
                “{{ testimonial.content }}”
              </p>
              <div class="d-flex align-items-center gap-3">
                <img
                  :src="testimonial.image"
                  :alt="testimonial.name"
                  class="testimonial-avatar"
                />
                <div>
                  <div class="fw-semibold">
                    {{ testimonial.name }}
                  </div>
                  <small class="text-muted">
                    {{ testimonial.role }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div> -->

      <!-- PARTNERS -->
      <!-- <div class="pt-5 border-top">
        <p class="text-center text-muted fw-semibold small mb-4">
          TRUSTED FINANCING PARTNERS
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-4 gap-md-5 opacity-75">
          <span
            v-for="partner in ['Capital One', 'Chase', 'Bank of America', 'Wells Fargo', 'USAA']"
            :key="partner"
            class="partner-name"
          >
            {{ partner }}
          </span>
        </div>
      </div> -->

    </div>
  </section>
</template>

<style scoped>
/* ================= INDICATORS ================= */
.indicator-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid rgba(0,0,0,.05);
  transition: all .3s ease;
}

.indicator-card:hover {
  box-shadow: 0 14px 40px rgba(0,0,0,.08);
  transform: translateY(-4px);
}

.indicator-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: rgba(13,110,253,.1);
  color: #5c2d80;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  transition: all .3s ease;
}

.indicator-card:hover .indicator-icon {
  background: #5c2d80;
  color: #fff;
}

/* ================= TESTIMONIALS ================= */
.testimonial-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid rgba(0,0,0,.05);
  transition: all .3s ease;
}

.testimonial-card:hover {
  box-shadow: 0 14px 40px rgba(0,0,0,.08);
}

.star {
  color: var(--bs-primary);
  fill: var(--bs-primary);
}

.testimonial-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

/* ================= PARTNERS ================= */
.partner-name {
  font-size: 1.125rem;
  font-weight: 700;
  color: #6c757d;
  cursor: pointer;
  transition: color .3s ease;
}

.partner-name:hover {
  color: #212529;
}
</style>
