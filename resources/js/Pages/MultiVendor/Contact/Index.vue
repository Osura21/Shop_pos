<template>
  <MultiVendorLayout>
    <ContactBanner
      title="Contact Autosale.lk"
      subtitle="Questions, feedback, or partnership requests — send a message and we’ll respond quickly."
      image="/multivendor/contact.webp"
    />

    <section class="cu">
      <div class="container px-4">
        <div class="cu__grid">
          <!-- FORM -->
          <div class="cu-card cu-card--form cu-reveal">
            <div class="cu-card__head">
              <div class="cu-kicker">
                <i class="bi bi-send"></i>
                Send a message
              </div>
              <h2 class="cu-h2">We’re here to help.</h2>
              <p class="cu-p">
                Fill the form and we’ll get back to you as soon as possible.
              </p>
            </div>

            <form class="cu-form" @submit.prevent="submit">
              <div class="cu-row">
                <div class="cu-field">
                  <label class="cu-label">Full Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="cu-input"
                    placeholder="Your name"
                  />
                  <div v-if="form.errors.name" class="cu-err">{{ form.errors.name }}</div>
                </div>

                <div class="cu-field">
                  <label class="cu-label">Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="cu-input"
                    placeholder="you@example.com"
                  />
                  <div v-if="form.errors.email" class="cu-err">{{ form.errors.email }}</div>
                </div>
              </div>

              <div class="cu-row">
                <div class="cu-field">
                  <label class="cu-label">Phone (optional)</label>
                  <input
                    v-model="form.phone"
                    type="tel"
                    class="cu-input"
                    placeholder="+94 …"
                  />
                  <div v-if="form.errors.phone" class="cu-err">{{ form.errors.phone }}</div>
                </div>

                <div class="cu-field">
                  <label class="cu-label">Subject</label>
                  <input
                    v-model="form.subject"
                    type="text"
                    class="cu-input"
                    placeholder="How can we help?"
                  />
                  <div v-if="form.errors.subject" class="cu-err">{{ form.errors.subject }}</div>
                </div>
              </div>

              <div class="cu-field">
                <label class="cu-label">Message</label>
                <textarea
                  v-model="form.message"
                  class="cu-textarea"
                  rows="6"
                  placeholder="Write your message..."
                ></textarea>
                <div v-if="form.errors.message" class="cu-err">{{ form.errors.message }}</div>
              </div>

              <div class="cu-actions">
                <button class="cu-btn cu-btn--primary" type="submit" :disabled="form.processing">
                  <span v-if="!form.processing">Send Message</span>
                  <span v-else>Sending…</span>
                  <i class="bi bi-arrow-right"></i>
                </button>

                <div v-if="form.recentlySuccessful" class="cu-ok">
                  <i class="bi bi-check2-circle"></i>
                  Message sent successfully.
                </div>
              </div>
            </form>
          </div>

          <!-- INFO -->
          <div class="cu-right">
            <div class="cu-card cu-card--info cu-reveal" style="transition-delay:.08s">
              <div class="cu-kicker">
                <i class="bi bi-info-circle"></i>
                Contact details
              </div>

              <div class="cu-infoGrid">
                <a class="cu-info" :href="`mailto:${email}`">
                  <div class="cu-info__ic"><i class="bi bi-envelope"></i></div>
                  <div>
                    <div class="cu-info__t">Email</div>
                    <div class="cu-info__v">{{ email }}</div>
                  </div>
                </a>

                <a class="cu-info" :href="phoneHref">
                  <div class="cu-info__ic"><i class="bi bi-telephone"></i></div>
                  <div>
                    <div class="cu-info__t">Phone</div>
                    <div class="cu-info__v">{{ phone }}</div>
                  </div>
                </a>

                <div class="cu-info">
                  <div class="cu-info__ic"><i class="bi bi-geo-alt"></i></div>
                  <div>
                    <div class="cu-info__t">Address</div>
                    <div class="cu-info__v">{{ address }}</div>
                  </div>
                </div>

                <div class="cu-info">
                  <div class="cu-info__ic"><i class="bi bi-clock"></i></div>
                  <div>
                    <div class="cu-info__t">Hours</div>
                    <div class="cu-info__v">{{ hours }}</div>
                  </div>
                </div>
              </div>

              <div class="cu-mini">
                <div class="cu-mini__t">Support promise</div>
                <div class="cu-mini__d">
                  We usually respond within 24 hours (often faster during business hours).
                </div>
              </div>
            </div>

            <!-- MAP -->
            <div class="cu-card cu-card--map cu-reveal" style="transition-delay:.14s">
              <div class="cu-mapHead">
                <div class="cu-kicker">
                  <i class="bi bi-map"></i>
                  Location
                </div>
                <div class="cu-mapHint">Optional (you can change the embed url)</div>
              </div>

              <div class="cu-map">
                <iframe
                  :src="mapEmbedUrl"
                  width="100%"
                  height="100%"
                  style="border:0;"
                  allowfullscreen
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
            </div>

            <!-- CTA -->
            <div class="cu-cta cu-reveal" style="transition-delay:.2s">
              <div class="cu-cta__left">
                <div class="cu-cta__k">Prefer browsing?</div>
                <div class="cu-cta__t">Explore listings first.</div>
                <div class="cu-cta__d">Use filters to find the right vehicle in seconds.</div>
              </div>

              <Link class="cu-btn cu-btn--ghost" :href="route('multivendor.vehicles')">
                Browse Cars <i class="bi bi-arrow-right"></i>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <MarketplaceFooterSections />
  </MultiVendorLayout>
</template>

<script setup>
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue";
import ContactBanner from "./partials/Banner.vue";
import MarketplaceFooterSections from "../Components/MarketplaceFooterSections.vue";
import { Link, useForm } from "@inertiajs/vue3";

/**
 * Change these values to match your business info.
 */
const email = "info@autosale.lk";
const phone = "+94 77 755 5333";
const address = "207/23 Srimath Anagarika Dharmapala Mawatha, Colombo";
const hours = "Mon–Sun • Open 24 Hours";

/**
 * Update this Google Maps embed url to your location.
 * (This one is a safe placeholder)
 */
const mapEmbedUrl =
  "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126743.63259758735!2d79.786164!3d6.921838!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2593b0d0b7fcd%3A0x6c9f1a8f6a86e2dd!2sColombo!5e0!3m2!1sen!2slk!4v1700000000000";

const phoneHref = `tel:${phone.replace(/\s+/g, "")}`;

/**
 * IMPORTANT: set this to your backend route.
 * If you already have a POST endpoint, replace this with the correct route name or url.
 */
const submitUrl = (() => {
  try {
    // preferred if Ziggy route exists
    return route("contact.send");
  } catch (e) {
    // fallback
    return "/contact-us";
  }
})();

const form = useForm({
  name: "",
  email: "",
  phone: "",
  subject: "",
  message: "",
});

function submit() {
  form.post(submitUrl, {
    preserveScroll: true,
    onSuccess: () => form.reset("name", "email", "phone", "subject", "message"),
  });
}
</script>

<style scoped>
/* ===== Base ===== */
.cu{
  padding: 56px 0 70px;
  background:
    radial-gradient(900px 380px at 15% 20%, rgba(92,45,128,.10) 0%, rgba(0,0,0,0) 55%),
    radial-gradient(900px 380px at 85% 10%, rgba(51,46,120,.08) 0%, rgba(0,0,0,0) 55%),
    linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
}

.cu__grid{
  display: grid;
  grid-template-columns: 1.05fr .95fr;
  gap: 18px;
  align-items: start;
}

.cu-card{
  border-radius: 22px;
  border: 1px solid rgba(0,0,0,.08);
  background: rgba(255,255,255,.85);
  box-shadow: 0 18px 45px rgba(0,0,0,.06);
  overflow: hidden;
}

.cu-card--form{ padding: 18px; }
.cu-card--info{ padding: 18px; }
.cu-card--map{ padding: 16px; }
.cu-right{ display: grid; gap: 14px; }

.cu-kicker{
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(92,45,128,.10);
  color: #5c2d80;
  font-weight: 950;
  font-size: 12px;
  border: 1px solid rgba(92,45,128,.14);
}

.cu-h2{
  margin: 12px 0 8px;
  font-weight: 950;
  font-size: 28px;
  color: #111827;
}

.cu-p{
  margin: 0;
  font-weight: 700;
  color: #4b5563;
  line-height: 1.8;
  font-size: 14px;
}

/* ===== Form ===== */
.cu-form{ margin-top: 14px; display: grid; gap: 12px; }
.cu-row{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.cu-field{ min-width: 0; }
.cu-label{
  display: block;
  font-weight: 900;
  font-size: 12px;
  color: #111827;
  margin-bottom: 6px;
}
.cu-input, .cu-textarea{
  width: 100%;
  border-radius: 14px;
  border: 1px solid rgba(0,0,0,.12);
  background: #fff;
  padding: 12px 12px;
  font-weight: 800;
  font-size: 13px;
  outline: none;
  transition: box-shadow .18s ease, border-color .18s ease, transform .18s ease;
}
.cu-input:focus, .cu-textarea:focus{
  border-color: rgba(92,45,128,.35);
  box-shadow: 0 0 0 4px rgba(92,45,128,.12);
}
.cu-err{
  margin-top: 6px;
  font-weight: 800;
  color: #b91c1c;
  font-size: 12px;
}

.cu-actions{
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 4px;
}

/* buttons */
.cu-btn{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border-radius: 999px;
  font-weight: 950;
  padding: 12px 16px;
  text-decoration: none;
  border: 1px solid rgba(0,0,0,.10);
  color: #111827;
  background: #fff;
  transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
  white-space: nowrap;
}
.cu-btn:hover{
  transform: translateY(-1px);
  box-shadow: 0 14px 28px rgba(0,0,0,.10);
}
.cu-btn:disabled{ opacity: .65; cursor: not-allowed; }

.cu-btn--primary{
  border: 0;
  color: #fff;
  background: linear-gradient(135deg, #332e78, #5c2d80);
}
.cu-btn--ghost{
  background: rgba(255,255,255,.75);
}

.cu-ok{
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 900;
  color: #0f7a32;
  background: rgba(34,197,94,.10);
  border: 1px solid rgba(34,197,94,.18);
  padding: 10px 12px;
  border-radius: 14px;
}

/* ===== Info cards ===== */
.cu-infoGrid{
  margin-top: 14px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.cu-info{
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px;
  border-radius: 18px;
  border: 1px solid rgba(0,0,0,.08);
  background: #fff;
  text-decoration: none;
  color: inherit;
  transition: transform .18s ease, box-shadow .18s ease;
}
.cu-info:hover{
  transform: translateY(-1px);
  box-shadow: 0 14px 28px rgba(0,0,0,.08);
}

.cu-info__ic{
  width: 44px;
  height: 44px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: rgba(92,45,128,.10);
  color: #5c2d80;
  font-size: 18px;
  flex: 0 0 auto;
}
.cu-info__t{
  font-weight: 950;
  color: #111827;
}
.cu-info__v{
  margin-top: 4px;
  font-weight: 800;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.5;
}

/* mini */
.cu-mini{
  margin-top: 12px;
  border-radius: 18px;
  border: 1px solid rgba(0,0,0,.08);
  background:
    radial-gradient(420px 180px at 15% 25%, rgba(92,45,128,.12) 0%, rgba(0,0,0,0) 55%),
    #fff;
  padding: 14px;
}
.cu-mini__t{ font-weight: 950; color: #111827; }
.cu-mini__d{ margin-top: 6px; font-weight: 700; color: #6b7280; line-height: 1.7; font-size: 13px; }

/* ===== Map ===== */
.cu-mapHead{
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.cu-mapHint{
  font-weight: 800;
  font-size: 12px;
  color: #6b7280;
}
.cu-map{
  margin-top: 12px;
  height: 290px;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,.08);
  background: #eef2f7;
}

/* ===== CTA ===== */
.cu-cta{
  border-radius: 22px;
  border: 1px solid rgba(0,0,0,.08);
  background:
    radial-gradient(700px 220px at 15% 25%, rgba(92,45,128,.12) 0%, rgba(0,0,0,0) 55%),
    #fff;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.cu-cta__k{ font-weight: 950; color: #5c2d80; }
.cu-cta__t{ margin-top: 2px; font-weight: 950; font-size: 18px; color: #111827; }
.cu-cta__d{ margin-top: 4px; font-weight: 700; font-size: 13px; color: #6b7280; line-height: 1.6; }

/* ===== Animations (simple + safe) ===== */
.cu-reveal{
  opacity: 0;
  transform: translateY(10px);
  animation: cuFadeUp .55s ease forwards;
}
@keyframes cuFadeUp{
  to { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce){
  .cu-reveal{ animation: none; opacity: 1; transform: none; }
}

/* ===== Responsive ===== */
@media (max-width: 992px){
  .cu__grid{ grid-template-columns: 1fr; }
  .cu-row{ grid-template-columns: 1fr; }
  .cu-map{ height: 240px; }
}
</style>
