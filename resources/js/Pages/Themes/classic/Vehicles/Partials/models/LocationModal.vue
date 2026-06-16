<template>
  <teleport to="body">
    <transition name="locFade">
      <div
        v-if="open"
        class="locModal"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        @keydown.esc.prevent="close"
        @keydown.tab="onTabKey"
      >
        <div class="locModal__backdrop" @click="close"></div>

        <div ref="panel" class="locModal__panel" tabindex="-1" @click.stop>
          <div class="locModal__header">
            <div class="locModal__title" :id="titleId">Select City or District</div>
            <button
              ref="closeBtn"
              class="locModal__close"
              type="button"
              @click="close"
              aria-label="Close"
            >
              ×
            </button>
          </div>

          <div class="locModal__body">
            <button type="button" class="locTop" @click="selectAllSriLanka">
              All of Sri Lanka
              <span class="locPill" v-if="totalPosts">
                {{ totalPosts.toLocaleString() }} ads
              </span>
            </button>

            <div class="locGrid">
              <section class="locCol" aria-label="Districts">
                <div class="locCol__head">
                  <div class="locTitle">Districts</div>
                  <div class="locSearch">
                    <input
                      v-model="qDistrict"
                      type="text"
                      placeholder="Search districts…"
                      autocomplete="off"
                    />
                  </div>
                </div>

                <div ref="districtList" class="locList">
                  <button
                    v-for="d in filteredDistricts"
                    :key="d.id"
                    type="button"
                    class="locRow"
                    :class="{ active: String(d.id) === String(draftDistrictId) }"
                    @click="pickDistrict(d.id)"
                  >
                    <span class="locRow__text">{{ d.name }}</span>
                    <span class="chev">›</span>
                  </button>

                  <div v-if="!filteredDistricts.length" class="locEmpty">
                    No districts found
                  </div>
                </div>
              </section>

              <section class="locCol" aria-label="Cities">
                <div class="locCol__head">
                  <div class="locTitle">
                    <template v-if="draftDistrictName">
                      {{ draftDistrictName }}
                      <button type="button" class="locSubBtn" @click="selectDistrictOnly">
                        All ads in {{ draftDistrictName }}
                      </button>
                    </template>

                    <template v-else>
                      Select a district
                    </template>
                  </div>

                  <div class="locSearch" v-if="draftDistrictId">
                    <input
                      v-model="qCity"
                      type="text"
                      placeholder="Search cities…"
                      autocomplete="off"
                    />
                  </div>
                </div>

                <div ref="cityList" class="locList">
                  <template v-if="draftDistrictId">
                    <button
                      v-for="c in filteredCities"
                      :key="c.id"
                      type="button"
                      class="locRow"
                      :class="{ active: String(c.id) === String(draftCityId) }"
                      @click="pickCity(c.id)"
                    >
                      <span class="locRow__text">{{ c.name }}</span>
                    </button>

                    <div v-if="!filteredCities.length" class="locEmpty">
                      No cities found
                    </div>
                  </template>

                  <div v-else class="locHint">
                    Choose a district to see cities.
                  </div>
                </div>
              </section>
            </div>
          </div>

          <div class="locModal__footer">
            <button class="locReset" type="button" @click="resetDraft">Reset</button>

            <button
              class="locApply"
              type="button"
              @click="applyDraft"
              :disabled="applyDisabled"
            >
              <template v-if="variant === 'select'">
                Select
              </template>
              <template v-else>
                <span v-if="!countLoading">Show {{ displayCount }} posts</span>
                <span v-else>Show …</span>
              </template>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script>
export default {
  name: "LocationModal",

  props: {
    open: { type: Boolean, default: false },
    districts: { type: Array, default: () => [] },
    citiesAll: { type: Array, default: () => [] },
    selectedDistrictId: { type: [String, Number, null], default: null },
    selectedCityId: { type: [String, Number, null], default: null },
    totalPosts: { type: Number, default: 0 },
    activeBrandId: { type: [String, Number, null], default: null },
    activeModelId: { type: [String, Number, null], default: null },
    variant: { type: String, default: "filter" },
  },

  emits: ["close", "apply"],

  data() {
    return {
      titleId: "loc-title-" + Math.random().toString(16).slice(2),
      draftDistrictId: null,
      draftCityId: null,
      qDistrict: "",
      qCity: "",
      lastFocused: null,
      countLoading: false,
      draftCount: null,
      _countTimer: null,
    };
  },

  watch: {
    open(value) {
      if (value) this.onOpen();
      else this.onCloseCleanup();
    },
    draftDistrictId() {
      this.scheduleCount();
    },
    draftCityId() {
      this.scheduleCount();
    },
    activeBrandId() {
      if (this.open) this.scheduleCount();
    },
    activeModelId() {
      if (this.open) this.scheduleCount();
    },
  },

  computed: {
    applyDisabled() {
      if (this.variant === "select") {
        return !this.draftDistrictId && !this.draftCityId;
      }
      return this.countLoading;
    },

    displayCount() {
      const value = this.draftCount ?? this.totalPosts ?? 0;
      return Number(value).toLocaleString();
    },

    draftDistrictName() {
      const district = this.districts.find(
        (x) => String(x.id) === String(this.draftDistrictId)
      );
      return district?.name || "";
    },

    filteredDistricts() {
      const search = this.qDistrict.trim().toLowerCase();
      if (!search) return this.districts;

      return this.districts.filter((d) =>
        String(d.name).toLowerCase().includes(search)
      );
    },

    citiesForDistrict() {
      if (!this.draftDistrictId) return [];

      return this.citiesAll.filter(
        (c) => String(c.district_id) === String(this.draftDistrictId)
      );
    },

    filteredCities() {
      const search = this.qCity.trim().toLowerCase();
      if (!search) return this.citiesForDistrict;

      return this.citiesForDistrict.filter((c) =>
        String(c.name).toLowerCase().includes(search)
      );
    },
  },

  methods: {
    close() {
      this.$emit("close");
    },

    onOpen() {
      this.lastFocused = document.activeElement;

      const city = this.citiesAll.find(
        (c) => String(c.id) === String(this.selectedCityId)
      );

      this.draftCityId = city ? city.id : (this.selectedCityId || null);
      this.draftDistrictId = city
        ? city.district_id
        : (this.selectedDistrictId || null);

      this.qDistrict = "";
      this.qCity = "";

      document.body.style.overflow = "hidden";

      this.$nextTick(() => {
        this.$refs.closeBtn?.focus?.();
        if (this.$refs.cityList) this.$refs.cityList.scrollTop = 0;
      });

      this.fetchCount();
    },

    onCloseCleanup() {
      document.body.style.overflow = "";
      clearTimeout(this._countTimer);
      this._countTimer = null;
      this.countLoading = false;

      this.$nextTick(() => {
        this.lastFocused?.focus?.();
      });
    },

    pickDistrict(id) {
      this.draftDistrictId = id;
      this.draftCityId = null;
      this.qCity = "";

      this.$nextTick(() => {
        if (this.$refs.cityList) this.$refs.cityList.scrollTop = 0;
      });
    },

    pickCity(id) {
      this.draftCityId = id;
    },

    selectAllSriLanka() {
      this.draftDistrictId = null;
      this.draftCityId = null;
      this.qDistrict = "";
      this.qCity = "";

      this.$nextTick(() => {
        if (this.$refs.districtList) this.$refs.districtList.scrollTop = 0;
        if (this.$refs.cityList) this.$refs.cityList.scrollTop = 0;
      });

      this.scheduleCount();
    },

    selectDistrictOnly() {
      if (!this.draftDistrictId) return;
      this.draftCityId = null;
    },

    resetDraft() {
      this.selectAllSriLanka();

      const label =
        this.variant === "select" ? "Select location" : "All of Sri Lanka";

      this.$emit("apply", {
        district_id: null,
        city_id: null,
        locationSlug: null,
        label,
      });

      this.close();
    },

    applyDraft() {
      const district_id = this.draftDistrictId || null;
      const city_id = this.draftCityId || null;

      let locationSlug = null;
      let label = "All of Sri Lanka";

      if (city_id) {
        const city = this.citiesAll.find(
          (c) => String(c.id) === String(city_id)
        );
        const district = this.districts.find(
          (d) => String(d.id) === String(city?.district_id)
        );

        locationSlug = city?.slug || null;
        label = district?.name
          ? `${city?.name}, ${district?.name}`
          : (city?.name || label);
      } else if (district_id) {
        const district = this.districts.find(
          (d) => String(d.id) === String(district_id)
        );
        locationSlug = district?.slug || null;
        label = district?.name || label;
      }

      this.$emit("apply", { district_id, city_id, locationSlug, label });
      this.close();
    },

    scheduleCount() {
      if (!this.open) return;
      if (this.variant === "select") return;

      clearTimeout(this._countTimer);
      this._countTimer = setTimeout(() => this.fetchCount(), 180);
    },

    async fetchCount() {
      if (!this.open) return;

      this.countLoading = true;

      if (!this.draftDistrictId && !this.draftCityId) {
        this.draftCount = this.totalPosts || 0;
        this.countLoading = false;
        return;
      }

      try {
        const params = new URLSearchParams();

        if (this.draftDistrictId) params.set("district_id", this.draftDistrictId);
        if (this.draftCityId) params.set("city_id", this.draftCityId);
        if (this.activeBrandId) params.set("brand_id", this.activeBrandId);
        if (this.activeModelId) params.set("model_id", this.activeModelId);

        const url = route("vendorsite.vehicles.count") + "?" + params.toString();
        const response = await fetch(url, {
          headers: { Accept: "application/json" },
        });

        const json = await response.json();
        this.draftCount = Number(json?.count ?? 0);
      } catch (e) {
        this.draftCount = this.totalPosts || 0;
      } finally {
        this.countLoading = false;
      }
    },

    onTabKey(e) {
      const panel = this.$refs.panel;
      if (!panel) return;

      const focusables = panel.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );

      if (!focusables.length) return;

      const first = focusables[0];
      const last = focusables[focusables.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    },
  },
};
</script>

<style scoped>
.locFade-enter-active, .locFade-leave-active { transition: opacity .18s ease; }
.locFade-enter-from, .locFade-leave-to { opacity: 0; }

.locModal { position: fixed; inset: 0; z-index: 3000; }
.locModal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.40); backdrop-filter: blur(2px); }

.locModal__panel {
  position: relative;
  width: min(980px, calc(100vw - 32px));
  height: min(720px, calc(100vh - 32px));
  margin: 16px auto;
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.18);
  outline: none;
}

.locModal__header {
  position: sticky;
  top: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(8px);
}

.locModal__title { font-weight: 900; color: #111827; }

.locModal__close {
  width: 40px;
  height: 40px;
  border: 0;
  background: #f3f4f6;
  border-radius: 999px;
  font-size: 26px;
  line-height: 1;
  cursor: pointer;
}

.locModal__body { padding: 12px 16px; flex: 1; overflow: hidden; }

.locTop {
  width: 100%;
  text-align: left;
  border: 1px solid rgba(0,0,0,0.10);
  background: #fbfbfd;
  border-radius: 12px;
  padding: 12px 12px;
  font-weight: 900;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.locPill {
  font-size: 12px;
  font-weight: 900;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(51,46,120,0.10);
  color: #332e78;
}

.locGrid {
  height: calc(100% - 56px);
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  overflow: hidden;
}

.locCol {
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.locCol__head {
  padding: 12px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  background: #fff;
}

.locTitle { font-weight: 900; margin-bottom: 8px; color: #111827; }

.locSubBtn {
  display: block;
  margin-top: 6px;
  border: 0;
  background: transparent;
  padding: 0;
  text-align: left;
  font-size: 12px;
  font-weight: 900;
  color: #6b7280;
  cursor: pointer;
}

.locSearch input {
  width: 100%;
  border: 1px solid rgba(0,0,0,0.12);
  border-radius: 10px;
  padding: 10px 12px;
  outline: none;
}

.locList { flex: 1; overflow: auto; padding: 6px; scroll-behavior: smooth; }

.locRow {
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  padding: 10px;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.locRow:hover { background: rgba(0,0,0,0.05); }
.locRow.active { background: rgba(51,46,120,0.12); font-weight: 900; }

.locRow__text {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chev { opacity: .7; }

.locHint,
.locEmpty {
  padding: 14px 10px;
  color: #6b7280;
  font-weight: 800;
  font-size: 13px;
}

.locModal__footer {
  position: sticky;
  bottom: 0;
  z-index: 2;
  padding: 12px 16px;
  border-top: 1px solid rgba(0,0,0,0.08);
  display: flex;
  justify-content: space-between;
  gap: 12px;
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(8px);
}

.locReset {
  border: 1px solid rgba(0,0,0,0.12);
  background: #fff;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 900;
}

.locApply {
  border: 0;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
  border-radius: 6px;
  padding: 10px 18px;
  font-weight: 900;
}

.locApply:disabled { opacity: .75; cursor: not-allowed; }

@media (max-width: 760px) {
  .locModal__panel {
    width: calc(100vw - 16px);
    height: calc(100vh - 16px);
    margin: 8px auto;
    border-radius: 12px;
  }

  .locGrid {
    grid-template-columns: 1fr;
    height: calc(100% - 56px);
  }
}


</style>