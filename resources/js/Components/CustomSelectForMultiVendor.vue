<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue"

const props = defineProps({
    modelValue: [String, Number, Object, null],
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: "" },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },

})


const emit = defineEmits(["update:modelValue"])

const open = ref(false)
const search = ref("")
const wrapper = ref(null)
const searchInput = ref(null)
const openUpward = ref(false)

const filteredOptions = computed(() => {
    const q = search.value.toLowerCase().trim()
    if (!q) return normalizedOptions.value

    return normalizedOptions.value.filter((opt) =>
        String(opt.label).toLowerCase().includes(q)
    )
})
const selectedLabel = computed(() => {
    const found = normalizedOptions.value.find((o) => o.value == props.modelValue)
    return found?.label ?? ""
})


const normalizedOptions = computed(() => {
    return (props.options || []).map((opt) => {
        if (typeof opt === "string" || typeof opt === "number") {
            return { value: opt, label: String(opt) }
        }

        if (opt && typeof opt === "object") {
            const value = opt.value ?? opt.id ?? opt.key
            const label = opt.label ?? opt.title ?? String(value ?? "")
            return { value, label }
        }

        return { value: "", label: "" }
    })
})

const toggleDropdown = async () => {
    if (props.disabled) return

    open.value = !open.value

    if (open.value) {
        await nextTick()
        detectDirection()
        searchInput.value?.focus()
    }
}

const detectDirection = () => {
    const rect = wrapper.value.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    const spaceAbove = rect.top
    openUpward.value = spaceBelow < 260 && spaceAbove > spaceBelow
}

const selectOption = (value) => {
  if (props.clearable && value == props.modelValue) {
    clearSelection()
    return
  }

  emit("update:modelValue", value)
  open.value = false
  search.value = ""
}

const clearSelection = () => {
  emit("update:modelValue", null)
  open.value = false
  search.value = ""
}

const clickOutside = (e) => {
    if (wrapper.value && !wrapper.value.contains(e.target)) {
        open.value = false
    }
}

onMounted(() => document.addEventListener("click", clickOutside))
onBeforeUnmount(() => document.removeEventListener("click", clickOutside))
</script>

<template>
    <div ref="wrapper" class="custom-select" :class="{ disabled }">

        <!-- DISPLAY -->
        <div class="select-display" @click="toggleDropdown">
            <span :class="{ placeholder: !modelValue }">
                {{ selectedLabel || placeholder }}
            </span>

            <i class="bi bi-chevron-down"></i>
        </div>

        <!-- DROPDOWN -->
        <transition name="fade-slide">
            <div v-if="open" class="dropdown-box" :class="{ upward: openUpward }">
                <!-- SEARCH -->
                <input ref="searchInput" v-model="search" type="text" class="search-input" placeholder="Search..." />

                <!-- OPTIONS -->
               <ul class="options">
  <li
    v-if="clearable && modelValue"
    class="clear-option"
    @click="clearSelection"
  >
    ✕ Clear
  </li>

  <li
    v-for="option in filteredOptions"
    :key="option.value"
    @click="selectOption(option.value)"
  >
    {{ option.label }}
  </li>

  <li v-if="!filteredOptions.length" class="no-results">
    No results found
  </li>
</ul>

            </div>
        </transition>

    </div>
</template>

<style scoped>
.custom-select {
    position: relative;
    width: 100%;
}

.custom-select.disabled {
    opacity: 0.6;
    pointer-events: none;
}

/* DISPLAY */
.select-display {
    height: 52px;
    padding: 0 18px;
    border-radius: 999px;
    border: 1.5px solid #ababab;

    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;

}

.select-display:hover {
    border-color: #5c2d80;
    background: #f8fafc;
}

.placeholder {
    color: #9ca3af;
    background-color: transparent;
    cursor: pointer;
}

/* DROPDOWN */
.dropdown-box {
    position: absolute;
    left: 0;
    width: 100%;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    z-index: 50;
    overflow: hidden;
    top: calc(100% + 8px);
}

/* OPEN UPWARD */
.dropdown-box.upward {
    top: auto;
    bottom: calc(100% + 8px);
}

/* SEARCH INPUT */
.search-input {
    width: 100%;
    padding: 12px 14px;
    border: none;
    border-bottom: 1px solid #e5e7eb;
    outline: none;

    font-size: 14px;
    background: #ffffff;
    color: #111827;
    /* FIX TEXT COLOR */
    caret-color: #2563eb;
    /* FIX CURSOR */
}

.search-input::placeholder {
    color: #9ca3af;
}

/* OPTIONS */
.options {
    max-height: 220px;
    overflow-y: auto;
    list-style: none;
    margin: 0;
    padding: 6px;
}

.options li {
    padding: 10px 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
    color: #111827;
}

.options li:hover {
    background: #f1f5f9;
}

.no-results {
    text-align: center;
    color: #9ca3af;
    padding: 12px;
}

/* ANIMATION */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.2s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(-6px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

/* Focus / Open state */
.custom-select.open .select-display,
.select-display:focus-within {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    background: #ffffff;
}

.options li.clear-option {
  padding: 6px 10px !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  line-height: 1.2 !important;
  color: #dc2626 !important;
}

.options li.clear-option:hover {
  background: rgba(220, 38, 38, 0.08) !important;
}

</style>
