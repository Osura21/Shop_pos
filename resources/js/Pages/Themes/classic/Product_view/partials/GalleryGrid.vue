<!--Themes/classic/Product_view/partials/GalleryGrid.vue-->

<template>
 <transition name="gg-fade">
  <div v-if="visible" class="gg gg--white" @click.self="close">

      <!-- top bar -->
      <div class="gg-top">
        <div class="gg-tabs" v-if="cats.length">
          <button
            v-for="c in cats"
            :key="c"
            type="button"
            class="gg-tab"
            :class="{ active: c === active }"
            @click="active = c"
          >
            {{ c }}
          </button>
        </div>

        <button class="gg-close" type="button" @click="close" aria-label="Close">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- grid -->
      <div class="gg-body">
        <div class="gg-grid">
          <button
            v-for="img in filtered"
            :key="img._idx"
            type="button"
            class="gg-item"
            @click="select(img._idx)"
          >
            <img :src="img.original_url" class="gg-img" alt="gallery" />
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: "GalleryGrid",
  props: {
    visible: { type: Boolean, default: false },
    images: { type: Array, default: () => [] }, // expects { original_url, custom_properties? }
    categories: { type: Array, default: () => [] }, // optional. if empty -> auto
  },
  emits: ["close", "select"],

  data() {
    return {
      active: "All",
      prevOverflow: "",
    };
  },

  computed: {
    cats() {
      // If categories prop is passed, use it; else auto-build
      const base = (this.categories || []).filter(Boolean);
      if (base.length) return base;

      const set = new Set();
      (this.images || []).forEach((i) => {
        const c = i?.custom_properties?.category;
        if (c && String(c).trim()) set.add(String(c).trim());
      });

      return ["All", ...Array.from(set)];
    },

    filtered() {
      const imgs = (this.images || []).map((img, idx) => ({ ...img, _idx: idx }));
      if (this.active === "All") return imgs;

      return imgs.filter((img) => String(img?.custom_properties?.category || "").trim() === this.active);
    },
  },

  watch: {
    visible(v) {
      // lock scroll when open
      if (v) {
        this.prevOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";
        this.active = "All";
        window.addEventListener("keydown", this.onKey);
      } else {
        document.body.style.overflow = this.prevOverflow || "";
        window.removeEventListener("keydown", this.onKey);
      }
    },
  },

  beforeUnmount() {
    window.removeEventListener("keydown", this.onKey);
    document.body.style.overflow = this.prevOverflow || "";
  },

  methods: {
    close() {
      this.$emit("close");
    },
    select(idx) {
      this.$emit("select", idx); // pass ORIGINAL index in allImages
    },
    onKey(e) {
      if (e.key === "Escape") this.close();
    },
  },
};
</script>

<style scoped>
   
.gg {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  flex-direction: column;
}

.gg--white {
  background: #fff;
}

.gg-top {
  position: sticky;
  top: 0;
  z-index: 2;
  padding: 14px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.gg-tab {
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 999px;
  padding: 10px 14px;
  font-weight: 900;
  font-size: 12px;
  color: #231127;
  background: rgba(17,24,39,0.04);
  white-space: nowrap;
}

.gg-tab.active {
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
  border-color: #111827;
}

.gg-close {
  border: 1px solid rgba(0, 0, 0, 0.1);
  width: 44px;
  height: 44px;
  border-radius: 999px;
  background: #fff;
  color: #261127;
  display: grid;
  place-items: center;
}

.gg-body {
  flex: 1;
  overflow: auto;
  padding: 10px 14px 18px;
}

.gg-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

@media (max-width: 1200px) {
  .gg-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 576px) {
  .gg-grid { grid-template-columns: 1fr; }
}

.gg-item {
  border: 0;
  padding: 0;
  background: transparent;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
}

.gg-img {
  width: 100%;
  height: 280px;
  object-fit: cover;
  display: block;
  border-radius: 14px;
}

.gg-fade-enter-active, .gg-fade-leave-active { transition: opacity .18s ease; }
.gg-fade-enter-from, .gg-fade-leave-to { opacity: 0; }
</style>
