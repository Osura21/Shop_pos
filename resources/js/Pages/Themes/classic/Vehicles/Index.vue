<template>
  <MultiVendorLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Vehicle Stock
      </h2>
    </template>

    <StockFilter />
  </MultiVendorLayout>
</template>

<script>
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue";
import StockFilter from "./Partials/StockFilter.vue";

export default {
  name: "ThemeVehicleIndex",
  components: {
    MultiVendorLayout,
    StockFilter,
  },
};
</script>