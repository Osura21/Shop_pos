<template>
    <section id="filter" class="filter-overlap-section">
        <div class="container">
            <div class="filter-shell step" ref="filterWrap">
                <div class="row g-3 align-items-stretch compact-grid">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <button type="button" class="mv-fieldBtn" @click="openLocationModal">
                            <span class="mv-fieldText" :class="{ 'mv-ph': !locationSlug }">
                                {{ locationLabel }}
                            </span>
                            <i class="fa-solid fa-chevron-down mv-chev"></i>
                        </button>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <CustomSelectForMultiVendor
                            v-model="selectedTypeId"
                            :options="typeOptions"
                            placeholder="Vehicle Type"
                        />
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <CustomSelectForMultiVendor
                            v-model="vehicleBrandId"
                            :options="brands"
                            placeholder="Brand"
                        />
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <CustomSelectForMultiVendor
                            v-model="vehicleModelId"
                            :options="models"
                            placeholder="Model"
                            :disabled="!vehicleBrandId"
                        />
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <CustomSelectForMultiVendor
                            v-model="conditionSlug"
                            :options="conditionOptions"
                            placeholder="All Conditions"
                        />
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <button type="button" class="mv-fieldBtn" @click="openYearModal">
                            <span class="mv-fieldText" :class="{ 'mv-ph': !yearRangeLabelActive }">
                                {{ yearRangeLabel }}
                            </span>
                            <i class="fa-solid fa-chevron-down mv-chev"></i>
                        </button>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="mv-dd" :class="{ open: openPanel === 'price' }">
                            <button class="mv-fieldBtn" type="button" @click.stop="togglePanel('price')">
                                <span class="mv-fieldText" :class="{ 'mv-ph': isPricePlaceholder }">
                                    {{ priceRangeLabel }}
                                </span>
                                <i class="fa-solid fa-chevron-down mv-chev"></i>
                            </button>

                            <div v-show="openPanel === 'price'" class="mv-ddPanel" @click.stop>
                                <div class="mv-ddBody mv-pricePanel">
                                    <div class="mv-priceTitle">PRICE RANGE</div>

                                    <div class="mv-priceInputs">
                                        <div class="mv-priceField">
                                            <label>Min</label>
                                            <div class="mv-priceInput">
                                                <span class="mv-priceCur">Rs</span>
                                                <input type="number" v-model.number="priceMin" @change="clampPrice()" />
                                            </div>
                                        </div>

                                        <div class="mv-priceDash">—</div>

                                        <div class="mv-priceField">
                                            <label>Max</label>
                                            <div class="mv-priceInput">
                                                <span class="mv-priceCur">Rs</span>
                                                <input type="number" v-model.number="priceMax" @change="clampPrice()" />
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
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3 d-flex">
                        <button class="mv-searchBtn w-100" type="button" @click="searchVehicles">
                            Search Vehicles
                        </button>
                    </div>
                </div>
            </div>

            <LocationModal
                :open="isLocationModalOpen"
                :districts="districts"
                :cities-all="citiesAll"
                :selected-district-id="selectedDistrictId"
                :selected-city-id="selectedCityId"
                :total-posts="totalVehicleCount"
                :active-brand-id="vehicleBrandId"
                :active-model-id="vehicleModelId"
                @close="isLocationModalOpen = false"
                @apply="applyLocation"
            />

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
                            <CustomSelectForMultiVendor
                                v-model="yearFromDraft"
                                :options="yearOptionsFrom"
                                placeholder="From Year"
                            />
                        </div>

                        <div class="col-12 col-md-6">
                            <CustomSelectForMultiVendor
                                v-model="yearToDraft"
                                :options="yearOptionsTo"
                                placeholder="To Year"
                            />
                        </div>
                    </div>

                    <div class="year-actions">
                        <button type="button" class="year-clear-btn" @click="clearYearRange">
                            Clear
                        </button>

                        <button type="button" class="year-show-btn" @click="applyYearRange">
                            Show Posts ({{ yearRangePostCount }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import CustomSelectForMultiVendor from "@/Components/CustomSelectForMultiVendor.vue"
import LocationModal from "../../Vehicles/Partials/models/LocationModal.vue"
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import axios from "axios"
import { router, usePage } from "@inertiajs/vue3"

const page = usePage()

const pageFilters = computed(() => page.props.filters || {})
const districts = computed(() => page.props.districts || [])
const citiesAll = computed(() => page.props.citiesAll || [])
const lowestVehicleYear = computed(() => Number(page.props.lowestVehicleYear || new Date().getFullYear()))
const currentYear = computed(() => Number(page.props.currentYear || new Date().getFullYear()))
const totalVehicleCount = computed(() => Number(page.props.totalVehicleCount || 0))
const conditionsFromProps = computed(() => page.props.conditions || [
    { slug: "brand-new", name: "Brand New" },
    { slug: "used", name: "Used" },
    { slug: "reconditioned", name: "Reconditioned" },
])

const types = ref([])
const vehicleBrandId = ref(pageFilters.value.brand_id ?? null)
const vehicleModelId = ref(pageFilters.value.model_id ?? null)
const brands = ref([])
const models = ref([])

const isLocationModalOpen = ref(false)
const locationSlug = ref(null)
const selectedDistrictId = ref(pageFilters.value.district_id ?? null)
const selectedCityId = ref(pageFilters.value.city_id ?? null)

const conditionSlug = ref(pageFilters.value.condition_slug ?? null)
const selectedTypeId = ref(pageFilters.value.type_id ?? null)

const selectedYearFrom = ref(pageFilters.value.year_from ?? null)
const selectedYearTo = ref(pageFilters.value.year_to ?? null)

const yearFromDraft = ref(pageFilters.value.year_from ?? null)
const yearToDraft = ref(pageFilters.value.year_to ?? null)

const isYearModalOpen = ref(false)
const yearRangePostCount = ref(0)
const yearCountLoading = ref(false)
const booting = ref(true)

const conditionOptions = computed(() => {
    const base = conditionsFromProps.value.map(c => ({ value: c.slug, label: c.name }))
    return [{ value: null, label: "All Conditions" }, ...base]
})

const typeOptions = computed(() => {
    return [{ value: null, label: "Vehicle Type" }, ...types.value]
})

const fullYearList = computed(() => {
    const start = Math.max(1900, lowestVehicleYear.value)
    const end = Math.max(start, currentYear.value)
    const list = []

    for (let year = start; year <= end; year++) {
        list.push({
            value: year,
            label: String(year),
        })
    }

    return list
})

const yearOptionsFrom = computed(() => {
    return [{ value: null, label: "From Year" }, ...fullYearList.value]
})

const yearOptionsTo = computed(() => {
    return [{ value: null, label: "To Year" }, ...fullYearList.value]
})

const yearRangeLabelActive = computed(() => {
    return !!(selectedYearFrom.value || selectedYearTo.value)
})

const yearRangeLabel = computed(() => {
    if (!selectedYearFrom.value && !selectedYearTo.value) return "Year Range"
    if (selectedYearFrom.value && selectedYearTo.value) return `${selectedYearFrom.value} - ${selectedYearTo.value}`
    if (selectedYearFrom.value) return `From ${selectedYearFrom.value}`
    return `To ${selectedYearTo.value}`
})

const openPanel = ref(null)
const priceFloor = 0
const priceCeil = 20000000
const priceStep = 1000

const priceMin = ref(
    pageFilters.value.min_price !== null && pageFilters.value.min_price !== undefined
        ? Number(pageFilters.value.min_price)
        : priceFloor
)

const priceMax = ref(
    pageFilters.value.max_price !== null && pageFilters.value.max_price !== undefined
        ? Number(pageFilters.value.max_price)
        : priceCeil
)

const isPricePlaceholder = computed(() => priceMin.value === priceFloor && priceMax.value === priceCeil)

const priceRangeLabel = computed(() => {
    if (isPricePlaceholder.value) return "Price Range"
    return `Rs ${fmtNum(priceMin.value)} - Rs ${fmtNum(priceMax.value)}`
})

const rangeFillStyle = computed(() => {
    const min = priceFloor
    const max = priceCeil
    const a = ((priceMin.value - min) / (max - min)) * 100
    const b = ((priceMax.value - min) / (max - min)) * 100
    const left = Math.max(0, Math.min(100, a))
    const right = Math.max(0, Math.min(100, b))
    return { left: `${left}%`, width: `${Math.max(0, right - left)}%` }
})

function fmtNum(n) {
    return Number(n || 0).toLocaleString("en-US")
}

function clampPrice() {
    let min = Number(priceMin.value)
    let max = Number(priceMax.value)

    if (!Number.isFinite(min)) min = priceFloor
    if (!Number.isFinite(max)) max = priceCeil

    min = Math.max(priceFloor, Math.min(min, priceCeil))
    max = Math.max(priceFloor, Math.min(max, priceCeil))

    if (min > max) [min, max] = [max, min]

    priceMin.value = min
    priceMax.value = max
}

function onMinRange() {
    if (priceMin.value > priceMax.value) priceMin.value = priceMax.value
}

function onMaxRange() {
    if (priceMax.value < priceMin.value) priceMax.value = priceMin.value
}

function clearPrice() {
    priceMin.value = priceFloor
    priceMax.value = priceCeil
    openPanel.value = null
}

function togglePanel(panel) {
    openPanel.value = openPanel.value === panel ? null : panel
}

function syncLocationSlug() {
    if (selectedCityId.value) {
        const city = citiesAll.value.find(x => String(x.id) === String(selectedCityId.value))
        locationSlug.value = city?.slug || null
        return
    }

    if (selectedDistrictId.value) {
        const district = districts.value.find(x => String(x.id) === String(selectedDistrictId.value))
        locationSlug.value = district?.slug || null
        return
    }

    locationSlug.value = null
}

const locationLabel = computed(() => {
    if (!locationSlug.value) return "All of Sri Lanka"

    const district = districts.value.find(x => x.slug === locationSlug.value)
    if (district) return district.name

    const city = citiesAll.value.find(x => x.slug === locationSlug.value)
    return city ? city.name : "All of Sri Lanka"
})

function openLocationModal() {
    openPanel.value = null
    isLocationModalOpen.value = true
}

function applyLocation(payload) {
    locationSlug.value = payload.locationSlug || null
    selectedDistrictId.value = payload.district_id || payload.districtId || null
    selectedCityId.value = payload.city_id || payload.cityId || null
    isLocationModalOpen.value = false
}

function openYearModal() {
    openPanel.value = null
    yearFromDraft.value = selectedYearFrom.value
    yearToDraft.value = selectedYearTo.value
    isYearModalOpen.value = true
    fetchYearRangeCount()
}

function closeYearModal() {
    isYearModalOpen.value = false
}

function clearYearRange() {
    yearFromDraft.value = null
    yearToDraft.value = null
    selectedYearFrom.value = null
    selectedYearTo.value = null
    fetchYearRangeCount()
}

function applyYearRange() {
    if (yearFromDraft.value && yearToDraft.value && Number(yearFromDraft.value) > Number(yearToDraft.value)) {
        const temp = yearFromDraft.value
        yearFromDraft.value = yearToDraft.value
        yearToDraft.value = temp
    }

    selectedYearFrom.value = yearFromDraft.value
    selectedYearTo.value = yearToDraft.value
    isYearModalOpen.value = false
}

async function fetchYearRangeCount() {
    yearCountLoading.value = true

    try {
        const response = await axios.get(route("vendorsite.vehicles.count"), {
            params: {
                district_id: selectedDistrictId.value || null,
                city_id: selectedCityId.value || null,
                brand_id: vehicleBrandId.value || null,
                model_id: vehicleModelId.value || null,
                type_id: selectedTypeId.value || null,
                condition: conditionSlug.value || null,
                year_from: yearFromDraft.value || null,
                year_to: yearToDraft.value || null,
                min_price: priceMin.value > priceFloor ? priceMin.value : null,
                max_price: priceMax.value < priceCeil ? priceMax.value : null,
            }
        })

        yearRangePostCount.value = Number(response?.data?.count || 0)
    } catch (error) {
        yearRangePostCount.value = 0
    } finally {
        yearCountLoading.value = false
    }
}

async function fetchTypes() {
    try {
        const res = await axios.get(route("vendorsite.types"))
        types.value = (res.data || []).map(t => ({
            value: t.id,
            label: t.title,
        }))
    } catch (e) {
        types.value = []
    }
}

async function fetchBrands() {
    try {
        const res = await axios.get(route("vendorsite.brands"))
        brands.value = (res.data || []).map(b => ({
            value: b.id,
            label: b.title,
        }))
    } catch (e) {
        brands.value = []
    }
}

async function fetchModels(brandId) {
    if (!brandId) {
        models.value = []
        return
    }

    try {
        const res = await axios.get(route("vendorsite.models"), {
            params: { brand_id: brandId }
        })

        models.value = (res.data || []).map(m => ({
            value: m.id,
            label: m.title,
        }))
    } catch (e) {
        models.value = []
    }
}

watch(vehicleBrandId, async (id) => {
    const currentModelId = vehicleModelId.value

    if (!booting.value) {
        vehicleModelId.value = null
    }

    await fetchModels(id)

    if (booting.value && currentModelId) {
        const exists = models.value.some(m => String(m.value) === String(currentModelId))
        vehicleModelId.value = exists ? currentModelId : null
    }
})

watch([yearFromDraft, yearToDraft], () => {
    if (isYearModalOpen.value) {
        fetchYearRangeCount()
    }
})

watch([selectedDistrictId, selectedCityId], () => {
    syncLocationSlug()

    if (isYearModalOpen.value) {
        fetchYearRangeCount()
    }
})

watch([vehicleBrandId, vehicleModelId, selectedTypeId, conditionSlug], () => {
    if (isYearModalOpen.value) {
        fetchYearRangeCount()
    }
})

onMounted(async () => {
    syncLocationSlug()

    await Promise.all([
        fetchTypes(),
        fetchBrands(),
    ])

    if (vehicleBrandId.value) {
        await fetchModels(vehicleBrandId.value)
    }

    booting.value = false

    document.addEventListener("click", onOutsideClick)
    document.addEventListener("keydown", onEsc)
})

onBeforeUnmount(() => {
    document.removeEventListener("click", onOutsideClick)
    document.removeEventListener("keydown", onEsc)
})

function onOutsideClick() {
    if (isLocationModalOpen.value || isYearModalOpen.value) return
    openPanel.value = null
}

function onEsc(e) {
    if (e.key === "Escape") {
        openPanel.value = null
        isYearModalOpen.value = false
    }
}

function cleanQuery(q) {
    const out = { ...q }

    Object.keys(out).forEach(key => {
        const value = out[key]
        if (value === null || value === undefined || value === "") {
            delete out[key]
        }
    })

    return out
}

function searchVehicles() {
    clampPrice()

    const query = cleanQuery({
        district_id: selectedDistrictId.value || null,
        city_id: selectedCityId.value || null,
        type_id: selectedTypeId.value || null,
        brand_id: vehicleBrandId.value || null,
        model_id: vehicleModelId.value || null,
        condition: conditionSlug.value || null,
        year_from: selectedYearFrom.value || null,
        year_to: selectedYearTo.value || null,
        min_price: priceMin.value > priceFloor ? priceMin.value : null,
        max_price: priceMax.value < priceCeil ? priceMax.value : null,
    })

    router.get(route("vendorsite.vehicles.index"), query, {
        preserveScroll: true,
        preserveState: true,
        replace: false,
    })
}
</script>

<style scoped>
.filter-overlap-section {
    position: relative;
    z-index: 5;
    padding-bottom: 24px;
}

.filter-shell {
    background: #fff;
    border-radius: 28px;
    box-shadow: 0 18px 45px rgba(18, 18, 30, .10);
    padding: 28px 24px 24px;
    border: 1px solid rgba(0, 0, 0, .05);
}

.compact-grid {
    --bs-gutter-x: 12px;
    --bs-gutter-y: 12px;
}

.mv-fieldBtn {
    width: 100%;
    height: 50px;
    border-radius: 999px;
    border: 1px solid rgba(0, 0, 0, 0.18);
    background: #fff;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.mv-fieldText {
    font-weight: 800;
    font-size: 13px;
    color: #111827;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.mv-ph {
    color: #9ca3af;
    font-weight: 800;
}

.mv-chev {
    transition: transform .18s ease;
    opacity: .75;
    font-size: 12px;
}

.mv-dd.open .mv-chev {
    transform: rotate(180deg);
}

.mv-searchBtn {
    height: 50px;
    border-radius: 999px;
    border: 0;
    font-weight: 900;
    font-size: 15px;
    color: #fff;
    background: linear-gradient(135deg, #332e78, #5c2d80);
    box-shadow: 0 10px 22px rgba(51, 46, 120, .18);
}

.mv-searchBtn:active {
    transform: scale(.99);
}

.mv-dd {
    position: relative;
}

.mv-ddPanel {
    position: absolute;
    top: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%);
    width: min(520px, 92vw);
    z-index: 50;
}

.mv-ddBody {
    background: #fff;
    border-radius: 18px;
    border: 1px solid rgba(0, 0, 0, .10);
    box-shadow: 0 18px 40px rgba(0, 0, 0, .10);
    padding: 14px;
}

.mv-priceTitle {
    text-align: center;
    font-weight: 900;
    font-size: 12px;
    letter-spacing: .10em;
    margin-bottom: 14px;
    color: #111827;
}

.mv-priceInputs {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 12px;
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
    margin: 0 0 8px;
}

.mv-priceInput {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f3f4f6;
    border-radius: 14px;
    padding: 12px 14px;
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
    height: 36px;
    margin-top: 16px;
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
    height: 36px;
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
    border: 1px solid rgba(0, 0, 0, .12);
    box-shadow: 0 8px 18px rgba(0, 0, 0, .12);
    cursor: pointer;
    margin-top: -8px;
}

.mv-range::-moz-range-thumb {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, .12);
    box-shadow: 0 8px 18px rgba(0, 0, 0, .12);
    cursor: pointer;
}

.mv-priceActions {
    margin-top: 14px;
    display: flex;
    justify-content: flex-end;
}

.mv-priceClear {
    border: 1px solid rgba(0, 0, 0, .10);
    background: #fff;
    border-radius: 999px;
    padding: 10px 18px;
    font-weight: 900;
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
    box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
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
    border: 1px solid rgba(0, 0, 0, .12);
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

@media (max-width: 992px) {
    .mv-ddPanel {
        left: 0;
        transform: none;
        width: 100%;
    }

    .filter-shell {
        border-radius: 24px;
        padding: 22px 16px 18px;
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
</style>