<template>
  <div class="mv-page">
    <Banner
      :total="vehicles?.total || 0"
      :typesCount="(vehicleTypes || []).length"
      :makesCount="(manufacturers || []).length"
    />

    <div
      v-if="isMobile"
      class="mv-mobile-filter-btn"
      :class="{ 'mv-mobile-filter-btn--sticky': scrollY > 100 && isMobile }"
      @click="toggleStickyBar"
    >
      <i class="fa-solid fa-filter"></i>
      <span class="mv-filter-count" v-if="filterCount > 0">{{ filterCount }}</span>
    </div>

    <div class="container">
      <div class="mv-layout">
        <main class="mv-main">
          <div class="mv-stickyBar" ref="stickyBar" :class="{ 'mobile-open': isStickyBarOpen && isMobile }">
            <button class="mv-mobile-close" @click="toggleStickyBar" v-if="isMobile">×</button>

            <div class="mv-stickyRow">
              <div class="mv-stickyFilters">
                <!-- Location -->
                <div class="mv-dd">
                  <button class="mv-ddBtn" type="button" @click.stop="openLocationModal">
                    <span class="mv-ddText" :class="{ 'mv-ph': isLocationPlaceholder }">
                      {{ selectedLocationLabel }}
                    </span>
                    <i class="fa-solid fa-chevron-down mv-chev"></i>
                  </button>
                </div>

                <!-- Type -->
                <div class="mv-dd" :class="{ open: openPanel === 'type' }">
                  <button class="mv-ddBtn" type="button" @click.stop="togglePanel('type')">
                    <span class="mv-ddText" :class="{ 'mv-ph': isTypePlaceholder }">
                      {{ selectedTypeLabel }}
                    </span>
                    <i class="fa-solid fa-chevron-down mv-chev"></i>
                  </button>

                  <div v-show="openPanel === 'type'" class="mv-ddPanel">
                    <div class="mv-ddBody">
                      <div class="mv-optionList">
                        <button
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: !active.type }"
                          @click="applyType(null)"
                        >
                          All Vehicle Types
                        </button>

                        <button
                          v-for="type in vehicleTypes"
                          :key="type.id"
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: active.type?.id === type.id }"
                          @click="applyType(type.id)"
                        >
                          {{ type.title }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Make -->
                <div class="mv-dd" :class="{ open: openPanel === 'make' }">
                  <button class="mv-ddBtn" type="button" @click.stop="togglePanel('make')">
                    <span class="mv-ddText" :class="{ 'mv-ph': isMakePlaceholder }">
                      {{ selectedMakesLabel }}
                    </span>
                    <i class="fa-solid fa-chevron-down mv-chev"></i>
                  </button>

                  <div v-show="openPanel === 'make'" class="mv-ddPanel">
                    <div class="mv-ddBody">
                      <div class="mv-search">
                        <input v-model="makeQ" type="text" placeholder="Search make..." />
                      </div>

                      <div class="mv-optionList">
                        <button
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: !active.brand }"
                          @click="applyBrand(null)"
                        >
                          All Brands
                        </button>

                        <button
                          v-for="brand in filteredManufacturers"
                          :key="brand.id"
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: active.brand?.id === brand.id }"
                          @click="applyBrand(brand.id)"
                        >
                          {{ brand.title }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Model -->
                <div class="mv-dd" :class="{ open: openPanel === 'model' }">
                  <button class="mv-ddBtn" type="button" @click.stop="togglePanel('model')" :disabled="!active.brand">
                    <span class="mv-ddText" :class="{ 'mv-ph': isModelPlaceholder }">
                      {{ selectedModelsLabel }}
                    </span>
                    <i class="fa-solid fa-chevron-down mv-chev"></i>
                  </button>

                  <div v-show="openPanel === 'model'" class="mv-ddPanel">
                    <div class="mv-ddBody">
                      <div class="mv-search">
                        <input v-model="modelQ" type="text" placeholder="Search model..." />
                      </div>

                      <div class="mv-optionList">
                        <button
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: !active.model }"
                          @click="applyModel(null)"
                        >
                          All Models
                        </button>

                        <button
                          v-for="model in filteredModels"
                          :key="model.id"
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: active.model?.id === model.id }"
                          @click="applyModel(model.id)"
                        >
                          {{ model.title }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Condition -->
                <div class="mv-dd" :class="{ open: openPanel === 'condition' }">
                  <button class="mv-ddBtn" type="button" @click.stop="togglePanel('condition')">
                    <span class="mv-ddText" :class="{ 'mv-ph': isConditionPlaceholder }">
                      {{ selectedConditionLabel }}
                    </span>
                    <i class="fa-solid fa-chevron-down mv-chev"></i>
                  </button>

                  <div v-show="openPanel === 'condition'" class="mv-ddPanel">
                    <div class="mv-ddBody">
                      <div class="mv-optionList">
                        <button
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: !active.condition }"
                          @click="applyCondition(null)"
                        >
                          All Conditions
                        </button>

                        <button
                          v-for="condition in conditionsList"
                          :key="condition.slug"
                          type="button"
                          class="mv-filterOpt"
                          :class="{ active: active.condition?.slug === condition.slug }"
                          @click="applyCondition(condition.slug)"
                        >
                          {{ condition.name }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Year -->
                <div class="mv-dd">
                  <button class="mv-ddBtn" type="button" @click.stop="openYearModal">
                    <span class="mv-ddText" :class="{ 'mv-ph': isYearPlaceholder }">
                      {{ yearRangeLabel }}
                    </span>
                    <i class="fa-solid fa-chevron-down mv-chev"></i>
                  </button>
                </div>

                <!-- Price -->
                <div class="mv-dd mv-dd--price" :class="{ open: openPanel === 'price' }">
                  <button class="mv-ddBtn" type="button" @click.stop="togglePanel('price')">
                    <span class="mv-ddText" :class="{ 'mv-ph': isPricePlaceholder }">
                      {{ priceRangeLabel }}
                    </span>
                    <i class="fa-solid fa-chevron-down mv-chev"></i>
                  </button>

                  <div v-show="openPanel === 'price'" class="mv-ddPanel">
                    <div class="mv-ddBody mv-pricePanel">
                      <div class="mv-priceTitle">PRICE RANGE</div>

                      <div class="mv-priceInputs">
                        <div class="mv-priceField">
                          <label>Min</label>
                          <div class="mv-priceInput">
                            <span class="mv-priceCur">Rs</span>
                            <input type="number" v-model.number="priceMin" @change="clampPrice(); queueAutoApply()" />
                          </div>
                        </div>

                        <div class="mv-priceDash">—</div>

                        <div class="mv-priceField">
                          <label>Max</label>
                          <div class="mv-priceInput">
                            <span class="mv-priceCur">Rs</span>
                            <input type="number" v-model.number="priceMax" @change="clampPrice(); queueAutoApply()" />
                          </div>
                        </div>
                      </div>

                      <div class="mv-rangeWrap">
                        <div class="mv-rangeTrack">
                          <div class="mv-rangeFill" :style="rangeFillStyle"></div>
                        </div>

                        <input
                          class="mv-range mv-range--min"
                          type="range"
                          :min="priceFloor"
                          :max="priceCeil"
                          :step="priceStep"
                          v-model.number="priceMin"
                          @input="onMinRange"
                        />
                        <input
                          class="mv-range mv-range--max"
                          type="range"
                          :min="priceFloor"
                          :max="priceCeil"
                          :step="priceStep"
                          v-model.number="priceMax"
                          @input="onMaxRange"
                        />
                      </div>

                      <div class="mv-priceActions">
                        <button class="mv-priceClear" type="button" @click="clearPrice">Clear</button>
                      </div>
                    </div>
                  </div>
                </div>

                <LocationModal
                  :open="isLocationModalOpen"
                  :districts="$page.props.districts || []"
                  :cities-all="$page.props.citiesAll || []"
                  :selected-district-id="active.district?.id || null"
                  :selected-city-id="active.city?.id || null"
                  :total-posts="vehicles?.total || 0"
                  :active-brand-id="active.brand?.id || null"
                  :active-model-id="active.model?.id || null"
                  @close="isLocationModalOpen = false"
                  @apply="applyLocation"
                />
              </div>
            </div>
          </div>

          <div class="mv-topBar">
            <div>
              <div class="mv-topTitle">Results</div>
              <div class="mv-topSub">{{ vehicles?.total || 0 }} vehicles found</div>
            </div>

            <div class="mv-topActions">
              <select class="mv-sort" v-model="sortBy" @change="applySort">
                <option value="">Sort: Default</option>
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
              </select>

              <button type="button" class="mv-resetBtn" @click="resetAll" v-if="filterCount > 0">
                Reset Filters
              </button>
            </div>
          </div>

          <div v-if="isLoading" class="mv-loading">Loading vehicles…</div>

          <template v-else>
            <div v-if="vehicles?.data?.length" class="mv-grid">
              <Link v-for="v in vehicles.data" :key="v.id" class="stock-card" :href="detailUrl(v)">
                <div class="stock-card__media">
                  <div v-if="!imgReady[v.id]" class="stock-skel"></div>

                  <img
                    class="stock-card__img"
                    :class="{ ready: imgReady[v.id] }"
                    :src="cardImage(v)"
                    alt="vehicle"
                    @load="imgReady[v.id] = true"
                  />

                  <div class="stock-badge" :class="availabilityBadgeClass(v.availability)">
                    {{ availabilityLabel(v.availability) }}
                  </div>

                  <div class="stock-photos" v-if="v.media?.length">
                    <i class="fa-regular fa-images stock-photos__ic"></i>
                    {{ v.media.length }}
                  </div>
                </div>

                <div class="stock-card__body">
                  <div class="stock-head">
                    <div class="stock-head__left">
                      <div class="stock-name">
                        {{ (v.manufacture?.title || "—") }} {{ (v.vehicle_model?.title || "") }}
                      </div>
                      <div class="stock-year">{{ v.year || "—" }}</div>
                    </div>

                    <div class="stock-head__right">
                      <div class="stock-cc">{{ v.engine_capacity || "—" }}cc</div>
                      <div class="stock-cond" :class="badgeClass(v.condition)">
                        {{ (v.condition || "—").toUpperCase() }}
                      </div>
                    </div>
                  </div>

                  <div class="stock-divider"></div>

                  <div class="stock-icons">
                    <div class="sitem">
                      <i class="fa-solid fa-gas-pump sico"></i>
                      <span class="stxt">{{ fuelLabel(v.fuel_type) }}</span>
                    </div>

                    <div class="sitem">
                      <i class="fa-solid fa-gears sico"></i>
                      <span class="stxt">{{ transmissionLabel(v.transmission) }}</span>
                    </div>

                    <div class="sitem">
                      <i class="fa-solid fa-road sico"></i>
                      <span class="stxt stxt--nowrap">
                        {{ v.mileage ? `${v.mileage} Miles` : "N/A Miles" }}
                      </span>
                    </div>
                  </div>

                  <div class="stock-footer">
                    <div class="stock-footer__price">
                      {{ money(v.price || 0, v.price_currency || "LKR") }}
                    </div>

                    <span class="stock-footer__btn">
                      <i class="fa-solid fa-arrow-right"></i>
                    </span>
                  </div>
                </div>
              </Link>
            </div>

            <div v-else class="mv-empty">
              <div class="mv-emptyTitle">No vehicles found</div>
              <div class="mv-emptySub">Try removing filters or searching again.</div>
              <button type="button" class="mv-emptyBtn" @click="resetAll">Reset Filters</button>
            </div>

            <div v-if="vehicles?.last_page > 1" class="mv-pager">
              <button class="pageBtn" :disabled="!vehicles.prev_page_url" @click="changePage(vehicles.current_page - 1)">
                Prev
              </button>

              <button
                v-for="p in visiblePages"
                :key="p"
                class="pageBtn"
                :class="{ active: vehicles.current_page === p }"
                @click="changePage(p)"
              >
                {{ p }}
              </button>

              <button class="pageBtn" :disabled="!vehicles.next_page_url" @click="changePage(vehicles.current_page + 1)">
                Next
              </button>
            </div>
          </template>
        </main>
      </div>
    </div>

    <div v-if="isYearModalOpen" class="year-modal-backdrop" @click.self="closeYearModal">
      <div class="year-modal-card">
        <div class="year-modal-head">
          <div>
            <h5 class="year-modal-title mb-1">Select Year Range</h5>
            <p class="year-modal-sub mb-0">Choose a from year and to year.</p>
          </div>

          <button type="button" class="year-close-btn" @click="closeYearModal">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12 col-md-6">
            <select class="mv-nativeSelect" v-model="yearFromDraft">
              <option :value="null">From Year</option>
              <option v-for="year in fullYearList" :key="`from-${year}`" :value="year">{{ year }}</option>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <select class="mv-nativeSelect" v-model="yearToDraft">
              <option :value="null">To Year</option>
              <option v-for="year in fullYearList" :key="`to-${year}`" :value="year">{{ year }}</option>
            </select>
          </div>
        </div>

        <div class="year-actions">
          <button type="button" class="year-clear-btn" @click="clearYearRange">Clear</button>
          <button type="button" class="year-show-btn" @click="applyYearRange">Apply Year Filter</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { router, Link } from "@inertiajs/vue3";
import Banner from "./Banner.vue";
import LocationModal from "./models/LocationModal.vue";

export default {
  name: "ThemeStockFilter",
  components: { Banner, LocationModal, Link },

  data() {
    const filters = this.$page.props.filters || {};

    return {
      makeQ: "",
      modelQ: "",
      isLoading: false,
      imgReady: {},
      sortBy: this.$page.props.requestQuery?.sortBy || filters.sortBy || "",
      openPanel: null,
      isMobile: false,
      isStickyBarOpen: false,
      isLocationModalOpen: false,
      isYearModalOpen: false,
      scrollY: 0,

      yearFromDraft: filters.year_from ?? null,
      yearToDraft: filters.year_to ?? null,

      priceFloor: 0,
      priceCeil: 20000000,
      priceStep: 1000,
      priceMin: filters.min_price !== null && filters.min_price !== undefined ? Number(filters.min_price) : 0,
      priceMax: filters.max_price !== null && filters.max_price !== undefined ? Number(filters.max_price) : 20000000,
      autoApplyTimer: null,
      autoApplyDelay: 450,
    };
  },

  mounted() {
    this.initPriceFromQuery();

    document.addEventListener("click", this.onOutsideClick);
    document.addEventListener("keydown", this.onEsc);
    window.addEventListener("resize", this.checkMobile);
    window.addEventListener("scroll", this.handleScroll);

    this.checkMobile();
  },

  beforeUnmount() {
    document.removeEventListener("click", this.onOutsideClick);
    document.removeEventListener("keydown", this.onEsc);
    window.removeEventListener("resize", this.checkMobile);
    window.removeEventListener("scroll", this.handleScroll);

    clearTimeout(this.autoApplyTimer);
  },

  computed: {
    pageFilters() {
      return this.$page.props.filters || {};
    },

    active() {
      return this.$page.props.active || {};
    },

    vehicles() {
      return this.$page.props.vehicles || null;
    },

    vehicleTypes() {
      return this.$page.props.vehicleTypes || [];
    },

    manufacturers() {
      return this.$page.props.manufacturers || [];
    },

    models() {
      return this.$page.props.models || [];
    },

    conditionsList() {
      return this.$page.props.conditions || [
        { slug: "brand-new", name: "Brand New" },
        { slug: "used", name: "Used" },
        { slug: "reconditioned", name: "Reconditioned" },
      ];
    },

    lowestVehicleYear() {
      return Number(this.$page.props.lowestVehicleYear || new Date().getFullYear());
    },

    currentYear() {
      return Number(this.$page.props.currentYear || new Date().getFullYear());
    },

    fullYearList() {
      const list = [];
      const start = Math.max(1900, this.lowestVehicleYear);
      const end = Math.max(start, this.currentYear);

      for (let year = end; year >= start; year--) {
        list.push(year);
      }

      return list;
    },

    isTypePlaceholder() {
      return !this.active.type;
    },

    isMakePlaceholder() {
      return !this.active.brand;
    },

    isModelPlaceholder() {
      return !this.active.model;
    },

    isLocationPlaceholder() {
      return !(this.active.city || this.active.district);
    },

    isConditionPlaceholder() {
      return !this.active.condition;
    },

    isYearPlaceholder() {
      return !(this.pageFilters.year_from || this.pageFilters.year_to);
    },

    isPricePlaceholder() {
      return !(this.pageFilters.min_price || this.pageFilters.max_price);
    },

    selectedTypeLabel() {
      return this.active.type?.title || "Vehicle Type";
    },

    selectedMakesLabel() {
      return this.active.brand?.title || "Brand";
    },

    selectedModelsLabel() {
      return this.active.model?.title || "Model";
    },

    selectedConditionLabel() {
      return this.active.condition?.name || "All Conditions";
    },

    selectedLocationLabel() {
      if (this.active.city) return this.active.city.name;
      if (this.active.district) return this.active.district.name;
      return "All of Sri Lanka";
    },

    yearRangeLabel() {
      const from = this.pageFilters.year_from;
      const to = this.pageFilters.year_to;

      if (!from && !to) return "Year Range";
      if (from && to) return `${from} - ${to}`;
      if (from) return `From ${from}`;
      return `To ${to}`;
    },

    priceRangeLabel() {
      if (this.priceMin === this.priceFloor && this.priceMax === this.priceCeil) {
        return "Price Range";
      }

      return `Rs ${this.fmtNum(this.priceMin)} - Rs ${this.fmtNum(this.priceMax)}`;
    },

    rangeFillStyle() {
      const min = this.priceFloor;
      const max = this.priceCeil;
      const a = ((this.priceMin - min) / (max - min)) * 100;
      const b = ((this.priceMax - min) / (max - min)) * 100;
      const left = Math.max(0, Math.min(100, a));
      const right = Math.max(0, Math.min(100, b));

      return {
        left: `${left}%`,
        width: `${Math.max(0, right - left)}%`,
      };
    },

    filteredManufacturers() {
      const q = this.makeQ.trim().toLowerCase();
      if (!q) return this.manufacturers;

      return this.manufacturers.filter((item) =>
        String(item.title || "").toLowerCase().includes(q)
      );
    },

    filteredModels() {
      const q = this.modelQ.trim().toLowerCase();
      if (!q) return this.models;

      return this.models.filter((item) =>
        String(item.title || "").toLowerCase().includes(q)
      );
    },

    visiblePages() {
      const vp = this.vehicles;
      if (!vp) return [];

      const last = vp.last_page;
      const cur = vp.current_page;
      let start = cur - 1;
      let end = cur + 1;

      if (start < 1) {
        start = 1;
        end = Math.min(3, last);
      }

      if (end > last) {
        end = last;
        start = Math.max(last - 2, 1);
      }

      const pages = [];
      for (let p = start; p <= end; p++) pages.push(p);

      return pages;
    },

    filterCount() {
      let count = 0;

      if (this.active.city || this.active.district) count += 1;
      if (this.active.type) count += 1;
      if (this.active.brand) count += 1;
      if (this.active.model) count += 1;
      if (this.active.condition) count += 1;
      if (this.pageFilters.year_from || this.pageFilters.year_to) count += 1;
      if (this.pageFilters.min_price || this.pageFilters.max_price) count += 1;

      return count;
    },
  },

  watch: {
    openPanel(panel) {
      if (panel !== "make") this.makeQ = "";
      if (panel !== "model") this.modelQ = "";
    },

    "$page.props.filters": {
      deep: true,
      handler(newFilters) {
        this.yearFromDraft = newFilters?.year_from ?? null;
        this.yearToDraft = newFilters?.year_to ?? null;
        this.sortBy = this.$page.props.requestQuery?.sortBy || newFilters?.sortBy || "";
        this.initPriceFromQuery();
      },
    },
  },

  methods: {
    fmtNum(n) {
      return Number(n || 0).toLocaleString("en-US");
    },

    initPriceFromQuery() {
      const q = this.$page.props.requestQuery || this.$page.props.filters || {};

      const min = q.min_price !== undefined && q.min_price !== null && q.min_price !== ""
        ? Number(q.min_price)
        : this.priceFloor;

      const max = q.max_price !== undefined && q.max_price !== null && q.max_price !== ""
        ? Number(q.max_price)
        : this.priceCeil;

      this.priceMin = Number.isFinite(min) ? min : this.priceFloor;
      this.priceMax = Number.isFinite(max) ? max : this.priceCeil;

      this.clampPrice();
    },

    clampPrice() {
      let min = Number(this.priceMin);
      let max = Number(this.priceMax);

      if (!Number.isFinite(min)) min = this.priceFloor;
      if (!Number.isFinite(max)) max = this.priceCeil;

      min = Math.max(this.priceFloor, Math.min(min, this.priceCeil));
      max = Math.max(this.priceFloor, Math.min(max, this.priceCeil));

      if (min > max) [min, max] = [max, min];

      this.priceMin = min;
      this.priceMax = max;
    },

    queueAutoApply() {
      if (this.openPanel !== "price") return;

      clearTimeout(this.autoApplyTimer);
      this.autoApplyTimer = setTimeout(() => {
        this.applyPrice();
      }, this.autoApplyDelay);
    },

    onMinRange() {
      if (this.priceMin > this.priceMax) this.priceMin = this.priceMax;
      this.queueAutoApply();
    },

    onMaxRange() {
      if (this.priceMax < this.priceMin) this.priceMax = this.priceMin;
      this.queueAutoApply();
    },

    cleanQuery(query) {
      const out = { ...query };

      delete out.condition_slug;

      Object.keys(out).forEach((key) => {
        const value = out[key];

        if (value === null || value === undefined || value === "") {
          delete out[key];
        }

        if (Array.isArray(value) && value.length === 0) {
          delete out[key];
        }
      });

      if (!out.page || Number(out.page) === 1) {
        delete out.page;
      }

      return out;
    },

    goWithQuery(next) {
      const current = this.$page.props.requestQuery || this.$page.props.filters || {};
      const query = this.cleanQuery({ ...current, ...next });

      this.isLoading = true;

      router.get(route("vendorsite.vehicles.index"), query, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
          this.isLoading = false;
          if (this.isMobile) this.isStickyBarOpen = false;
        },
      });
    },

    applyType(typeId) {
      this.openPanel = null;
      this.goWithQuery({
        type_id: typeId || null,
        page: null,
      });
    },

    applyBrand(brandId) {
      this.makeQ = "";
      this.modelQ = "";
      this.openPanel = null;

      this.goWithQuery({
        brand_id: brandId || null,
        model_id: null,
        page: null,
      });
    },

    applyModel(modelId) {
      if (!this.active.brand && modelId) return;

      this.modelQ = "";
      this.openPanel = null;

      this.goWithQuery({
        model_id: modelId || null,
        page: null,
      });
    },

    applyCondition(conditionSlug) {
      this.openPanel = null;

      this.goWithQuery({
        condition: conditionSlug || null,
        page: null,
      });
    },

    openLocationModal() {
      this.openPanel = null;
      this.isLocationModalOpen = true;
    },

    applyLocation(payload) {
      this.isLocationModalOpen = false;

      this.goWithQuery({
        district_id: payload.district_id || payload.districtId || null,
        city_id: payload.city_id || payload.cityId || null,
        page: null,
      });
    },

    openYearModal() {
      this.openPanel = null;
      this.yearFromDraft = this.pageFilters.year_from ?? null;
      this.yearToDraft = this.pageFilters.year_to ?? null;
      this.isYearModalOpen = true;
    },

    closeYearModal() {
      this.isYearModalOpen = false;
    },

    clearYearRange() {
      this.yearFromDraft = null;
      this.yearToDraft = null;
      this.isYearModalOpen = false;

      this.goWithQuery({
        year_from: null,
        year_to: null,
        page: null,
      });
    },

    applyYearRange() {
      let from = this.yearFromDraft;
      let to = this.yearToDraft;

      if (from && to && Number(from) > Number(to)) {
        [from, to] = [to, from];
      }

      this.isYearModalOpen = false;

      this.goWithQuery({
        year_from: from || null,
        year_to: to || null,
        page: null,
      });
    },

    applyPrice() {
      this.clampPrice();

      this.openPanel = null;

      this.goWithQuery({
        min_price: this.priceMin > this.priceFloor ? this.priceMin : null,
        max_price: this.priceMax < this.priceCeil ? this.priceMax : null,
        page: null,
      });
    },

    clearPrice() {
      this.priceMin = this.priceFloor;
      this.priceMax = this.priceCeil;
      this.openPanel = null;

      this.goWithQuery({
        min_price: null,
        max_price: null,
        page: null,
      });
    },

    resetAll() {
      this.sortBy = "";
      this.makeQ = "";
      this.modelQ = "";
      this.openPanel = null;
      this.isYearModalOpen = false;

      this.goWithQuery({
        district_id: null,
        city_id: null,
        type_id: null,
        brand_id: null,
        model_id: null,
        condition: null,
        year_from: null,
        year_to: null,
        min_price: null,
        max_price: null,
        sortBy: null,
        page: null,
      });
    },

    applySort() {
      this.goWithQuery({
        sortBy: this.sortBy || null,
        page: null,
      });
    },

    changePage(page) {
      const vp = this.vehicles;
      if (!vp) return;
      if (page < 1 || page > vp.last_page) return;

      window.scrollTo({ top: 0, behavior: "smooth" });

      this.goWithQuery({
        page: page <= 1 ? null : page,
      });
    },

    onEsc(e) {
      if (e.key === "Escape") {
        this.openPanel = null;
        this.isLocationModalOpen = false;
        this.isYearModalOpen = false;

        if (this.isMobile) {
          this.isStickyBarOpen = false;
        }
      }
    },

    togglePanel(panel) {
      this.openPanel = this.openPanel === panel ? null : panel;
    },

    onOutsideClick(e) {
      if (this.isLocationModalOpen || this.isYearModalOpen) return;

      const el = this.$refs.stickyBar;
      const mobileBtn = document.querySelector(".mv-mobile-filter-btn");

      if ((!el || !el.contains(e.target)) && (!mobileBtn || !mobileBtn.contains(e.target))) {
        this.openPanel = null;

        if (this.isMobile) {
          this.isStickyBarOpen = false;
        }
      }
    },

    availabilityBadgeClass(val) {
      const n = Number(val);
      if (n === 0) return "stock-badge--green";
      if (n === 1) return "stock-badge--amber";
      if (n === 2) return "stock-badge--red";
      return "stock-badge--muted";
    },

    availabilityLabel(val) {
      const n = Number(val);
      if (n === 0) return "Available";
      if (n === 1) return "Arriving";
      if (n === 2) return "Sold";
      return "—";
    },

    money(amount, currency = "LKR") {
      const n = Number(amount || 0);
      const code = String(currency || "LKR").toUpperCase();

      if (code === "LKR") {
        return "Rs " + n.toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        });
      }

      return `${code} ${n.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`;
    },

    badgeClass(condition) {
      const s = String(condition || "").toLowerCase();
      if (s.includes("new")) return "badge-green";
      return "badge-gray";
    },

    cardImage(v) {
      const main = (v.media || []).find(
        (m) => m.collection_name === "vehicle_main" || m.custom_properties?.type === "vehicle_main"
      );
      const any = (v.media || [])[0];

      return main?.original_url || any?.original_url || "/images/placeholder-car.png";
    },

    detailUrl(v) {
      if (v?.slug) {
        return route("vendorsite.product", { slug: v.slug });
      }

      return route("vendorsite.product", { slug: "ad" }) + "?id=" + v.id;
    },

    transmissionLabel(val) {
      const n = Number(val);
      if (n === 0) return "Auto";
      if (n === 1) return "Manual";
      if (n === 2) return "Triptonic";
      return "—";
    },

    fuelLabel(val) {
      const n = Number(val);
      if (n === 0) return "Diesel";
      if (n === 1) return "Petrol";
      if (n === 2) return "Hybrid";
      if (n === 3) return "Electric";
      return "—";
    },

    checkMobile() {
      this.isMobile = window.innerWidth <= 576;
    },

    toggleStickyBar() {
      this.isStickyBarOpen = !this.isStickyBarOpen;
      this.openPanel = null;
    },

    handleScroll() {
      this.scrollY = window.scrollY;
    },
  },
};
</script>

<style scoped>
.mv-page {
  width: 100%;
  padding: 0 0 40px;
}

.mv-layout {
  display: block;
  margin-top: 16px;
}

.mv-main {
  min-width: 0;
}

.mv-stickyBar {
  position: sticky;
  top: 24px;
  z-index: 1051;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.06);
  border-radius: 30px;
  padding: 14px;
  margin-bottom: 14px;
}

.mv-stickyRow {
  display: flex;
  justify-content: center;
}

.mv-stickyFilters {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  width: 100%;
}

.mv-dd {
  position: relative;
}

.mv-ddBtn {
  width: 100%;
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: #fbfbfd;
  border-radius: 22px;
  padding: 10px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 46px;
}

.mv-ddBtn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.mv-ddText {
  font-weight: 900;
  font-size: 13px;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mv-ddText.mv-ph {
  color: #9ca3af;
  font-weight: 800;
}

.mv-chev {
  transition: transform 0.18s ease;
}

.mv-dd.open .mv-chev {
  transform: rotate(180deg);
}

.mv-ddPanel {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  z-index: 2000;
}

.mv-ddBody {
  background: #fff;
  border-radius: 14px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.1);
  padding: 10px 12px;
  max-height: min(320px, calc(100vh - 180px));
  overflow: auto;
  overscroll-behavior: contain;
}

.mv-optionList {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mv-filterOpt {
  width: 100%;
  text-align: left;
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: #fff;
  border-radius: 12px;
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
  transition: all 0.18s ease;
}

.mv-filterOpt.active {
  color: #fff;
  border-color: transparent;
  background: linear-gradient(135deg, #332e78, #5c2d80);
}

.mv-search input,
.mv-nativeSelect {
  width: 100%;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  padding: 10px 12px;
  font-weight: 800;
  margin-bottom: 10px;
  outline: none;
  background: #fff;
}

.mv-dd--price .mv-ddPanel {
  left: 50%;
  right: auto;
  width: 380px;
  transform: translateX(-50%);
}

.mv-dd--price .mv-ddBody {
  width: 100%;
}

.mv-pricePanel {
  padding: 14px;
}

.mv-priceTitle {
  text-align: center;
  font-weight: 900;
  font-size: 12px;
  letter-spacing: 0.08em;
  color: #111827;
  margin-bottom: 10px;
}

.mv-priceInputs {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 10px;
  align-items: end;
}

.mv-priceDash {
  color: #c1c7d0;
  font-weight: 900;
  padding-bottom: 10px;
  text-align: center;
}

.mv-priceField label {
  display: block;
  font-size: 11px;
  font-weight: 800;
  color: #9ca3af;
  margin: 0 0 6px;
}

.mv-priceInput {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f3f4f6;
  border-radius: 12px;
  padding: 10px 12px;
}

.mv-priceCur {
  font-weight: 900;
  font-size: 13px;
  color: #111827;
}

.mv-priceInput input {
  width: 100%;
  border: 0;
  background: transparent;
  outline: none;
  font-weight: 900;
  font-size: 13px;
  color: #111827;
}

.mv-rangeWrap {
  position: relative;
  height: 34px;
  margin-top: 12px;
}

.mv-rangeTrack {
  position: absolute;
  left: 0;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  height: 6px;
  border-radius: 999px;
  background: rgba(118, 34, 197, 0.18);
}

.mv-rangeFill {
  position: absolute;
  top: 0;
  height: 100%;
  border-radius: 999px;
  background: #7b0dd6dc;
}

.mv-range {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 34px;
  background: transparent;
  -webkit-appearance: none;
  appearance: none;
}

.mv-range--min {
  z-index: 2;
}

.mv-range--max {
  z-index: 3;
}

.mv-range::-webkit-slider-runnable-track {
  height: 6px;
  background: transparent;
}

.mv-range::-moz-range-track {
  height: 6px;
  background: transparent;
}

.mv-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.12);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
  cursor: pointer;
  margin-top: -8px;
}

.mv-range::-moz-range-thumb {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.12);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
  cursor: pointer;
}

.mv-priceActions {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.mv-priceClear {
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: #fff;
  border-radius: 999px;
  padding: 10px 16px;
  font-weight: 900;
}

.mv-topBar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
  margin-bottom: 12px;
}

.mv-topActions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.mv-topTitle {
  font-size: 18px;
  font-weight: 900;
  color: #111827;
}

.mv-topSub {
  font-size: 12px;
  font-weight: 800;
  color: #6b7280;
}

.mv-sort {
  border-radius: 14px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  padding: 10px 12px;
  font-weight: 900;
  font-size: 13px;
  outline: none;
}

.mv-resetBtn {
  border: 0;
  border-radius: 14px;
  padding: 10px 14px;
  font-weight: 900;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
}

.mv-loading {
  padding: 18px;
  border-radius: 16px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  font-weight: 900;
  color: #6b7280;
}

.mv-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
}

.stock-card {
  display: flex;
  flex-direction: column;
  border-radius: 15px;
  overflow: hidden;
  background: #fff;
  text-decoration: none;
  color: inherit;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.stock-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 28px rgba(0, 0, 0, 0.12);
}

.stock-card__media {
  position: relative;
  height: 200px;
  background: #eef2f7;
}

.stock-card__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0;
  transition: opacity 0.35s ease;
}

.stock-card__img.ready {
  opacity: 1;
}

.stock-skel {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, #e5e7eb 0%, #f3f4f6 50%, #e5e7eb 100%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }

  100% {
    background-position: 200% 0;
  }
}

.stock-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 5;
  padding: 6px 12px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  color: #fff;
  backdrop-filter: blur(6px);
}

.stock-badge--green {
  background: rgba(34, 197, 94, 0.7);
}

.stock-badge--amber {
  background: rgba(245, 158, 11, 0.75);
}

.stock-badge--red {
  background: rgba(239, 68, 68, 0.75);
}

.stock-badge--muted {
  background: rgba(107, 114, 128, 0.65);
}

.stock-photos {
  position: absolute;
  right: 12px;
  bottom: 12px;
  background: rgba(0, 0, 0, 0.65);
  color: #fff;
  font-weight: 800;
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.stock-photos__ic {
  font-size: 12px;
  opacity: 0.95;
}

.stock-card__body {
  padding: 14px 14px 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.stock-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.stock-name {
  font-weight: 950;
  font-size: 15px;
  color: #111827;
  line-height: 1.2;
}

.stock-year {
  margin-top: 6px;
  font-weight: 800;
  font-size: 12px;
  color: #6b7280;
}

.stock-head__right {
  text-align: right;
  display: grid;
  gap: 6px;
  justify-items: end;
}

.stock-cc {
  font-weight: 950;
  font-size: 13px;
  color: #111827;
}

.stock-cond {
  font-weight: 950;
  font-size: 12px;
}

.badge-green {
  color: #0f7a32;
  font-weight: 900;
}

.badge-gray {
  color: #6b7280;
  font-weight: 900;
}

.stock-divider {
  height: 1px;
  background: rgba(0, 0, 0, 0.08);
  margin: 2px 0;
}

.stock-icons {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px 12px;
  padding-top: 6px;
}

.sitem {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #111827;
  min-width: 0;
}

.sico {
  width: 18px;
  display: inline-flex;
  justify-content: center;
  font-size: 14px;
  color: #111827;
}

.stxt {
  font-size: 11px;
  font-weight: 800;
  color: #111827;
}

.stxt--nowrap {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stock-footer {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding-top: 10px;
}

.stock-footer__price {
  font-weight: 950;
  font-size: 20px;
  color: #111827;
  line-height: 1;
}

.stock-footer__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
  flex: 0 0 auto;
}

.mv-empty {
  border-radius: 18px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: #fff;
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.05);
  padding: 18px;
  text-align: center;
}

.mv-emptyTitle {
  font-weight: 900;
  font-size: 16px;
}

.mv-emptySub {
  margin-top: 6px;
  font-weight: 800;
  color: #6b7280;
}

.mv-emptyBtn {
  margin-top: 12px;
  border: 0;
  border-radius: 14px;
  padding: 10px 14px;
  font-weight: 900;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
}

.mv-pager {
  margin-top: 14px;
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.pageBtn {
  border: 1px solid rgba(0, 0, 0, 0.1);
  background: #fff;
  border-radius: 12px;
  padding: 8px 12px;
  font-weight: 900;
  font-size: 13px;
}

.pageBtn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pageBtn.active {
  border: 0;
  color: #fff;
  background: linear-gradient(135deg, #332e78, #5c2d80);
}

.mv-mobile-filter-btn {
  display: none;
}

.year-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.year-modal-card {
  width: min(680px, 100%);
  background: #fff;
  border-radius: 24px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.24);
  padding: 22px;
}

.year-modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.year-modal-title {
  font-size: 20px;
  font-weight: 900;
  color: #111827;
}

.year-modal-sub {
  color: #6b7280;
  font-size: 14px;
}

.year-close-btn {
  border: 0;
  background: #f3f4f6;
  width: 38px;
  height: 38px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #111827;
}

.year-actions {
  margin-top: 18px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.year-clear-btn {
  border: 1px solid rgba(0, 0, 0, 0.12);
  background: #fff;
  border-radius: 999px;
  padding: 12px 18px;
  font-weight: 800;
}

.year-show-btn {
  border: 0;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  color: #fff;
  border-radius: 999px;
  padding: 12px 20px;
  font-weight: 900;
}

@media (max-width: 1400px) {
  .mv-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 1200px) {
  .mv-stickyFilters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .mv-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 992px) {
  .mv-stickyBar {
    border-radius: 18px;
    z-index: 0;
    top: 14px;
  }

  .mv-stickyFilters {
    grid-template-columns: 1fr;
  }

  .mv-ddPanel {
    position: static;
    margin-top: 10px;
  }

  .mv-dd--price .mv-ddPanel {
    left: 0;
    width: auto;
    transform: none;
  }

  .year-modal-card {
    padding: 18px;
    border-radius: 20px;
  }

  .year-actions {
    flex-direction: column;
  }

  .year-clear-btn,
  .year-show-btn {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .mv-grid {
    grid-template-columns: 1fr;
  }

  .mv-mobile-filter-btn {
    display: flex;
    position: fixed;
    top: 210px;
    right: 20px;
    background: linear-gradient(135deg, #332e78, #5c2d80);
    color: white;
    border: none;
    border-radius: 50%;
    width: 46px;
    height: 46px;
    font-weight: 900;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(51, 46, 120, 0.3);
    cursor: pointer;
    transition: all 0.4s ease;
    flex-direction: column;
    padding: 0;
    margin: 0;
    z-index: 100;
  }

  .mv-mobile-filter-btn--sticky {
    position: fixed !important;
    top: 90px !important;
    right: 20px;
    z-index: 100;
    background: linear-gradient(135deg, #332e78, #5c2d80);
    box-shadow: 0 4px 12px rgba(51, 46, 120, 0.4);
    animation: slideDown 0.4s ease;
  }

  .mv-mobile-filter-btn i {
    font-size: 18px;
  }

  .mv-mobile-filter-btn:active {
    transform: scale(0.92);
    box-shadow: 0 2px 8px rgba(51, 46, 120, 0.4);
  }

  .mv-filter-count {
    position: absolute;
    top: -4px;
    right: -4px;
    background: white;
    color: #332e78;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    border: 2px solid white;
  }

  .mv-stickyBar {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1060;
    background: white;
    border-radius: 0;
    padding: 20px;
    margin: 0;
    overflow-y: auto;
    backdrop-filter: none;
  }

  .mv-stickyBar.mobile-open {
    display: block;
  }

  .mv-stickyFilters {
    grid-template-columns: 1fr;
    gap: 15px;
    width: 100%;
    margin-top: 40px;
  }

  .mv-ddBtn {
    border-radius: 16px;
    padding: 14px 16px;
  }

  .mv-ddBody {
    max-height: 60vh;
  }

  .mv-mobile-close {
    position: fixed;
    top: 10px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 300;
    color: #111827;
    z-index: 1061;
    cursor: pointer;
    border: none;
  }

  .mv-topBar {
    flex-direction: column;
    align-items: stretch;
  }

  .mv-topActions {
    justify-content: space-between;
  }
}

@media (min-width: 577px) and (max-width: 760px) {
  .mv-mobile-filter-btn {
    display: none !important;
  }

  .mv-stickyBar {
    display: block !important;
    position: sticky;
    top: 14px;
    z-index: 1051;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.06);
    border-radius: 18px;
    padding: 14px;
    margin-bottom: 14px;
  }
}

@media (min-width: 761px) {
  .mv-mobile-filter-btn {
    display: none !important;
  }
}

@keyframes slideDown {
  from {
    transform: translateY(-10px);
    opacity: 0;
  }

  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>