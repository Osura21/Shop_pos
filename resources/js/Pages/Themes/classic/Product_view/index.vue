<!--Themes/classic/Product_view/index.vue-->

<template>
  <MultiVendorLayout>
    <Banner :vehicle="vehicle" />
    <ProductDetails :vehicle="vehicle" :seller="seller" :randomVehicles="randomVehicles" />
  </MultiVendorLayout>
</template>

<script>
import MultiVendorLayout from "../Layout/MultiVendorLayout.vue";
import Banner from "./Partials/Banner.vue";
import ProductDetails from "./Partials/product.vue";

export default {
  name: "ThemeProductViewIndex",
  components: {
    MultiVendorLayout,
    Banner,
    ProductDetails,
  },
  props: {
    vehicle: Object,
    seller: Object,
    randomVehicles: Array,
  },
  mounted() {
    window.scrollTo({ top: 0, behavior: "auto" });
  },
};
</script>