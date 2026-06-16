<script setup>
import { Head } from "@inertiajs/vue3"
import VendorRegister from "./partials/VendorRegister.vue"

defineProps({
    authUser: {
        type: Object,
        default: null,
    },
    existingRequest: {
        type: Object,
        default: null,
    },
    submittedRequest: {
        type: Object,
        default: null,
    },
    foodTypes: {
        type: Array,
        default: () => [],
    },
    restaurantPageImageDefaults: {
        type: Object,
        default: () => ({}),
    },
})
</script>

<template>
    <Head title="Register as Seller" />

    <VendorRegister
        :auth-user="authUser"
        :existing-request="existingRequest"
        :submitted-request="submittedRequest"
        :food-types="foodTypes"
        :restaurant-page-image-defaults="restaurantPageImageDefaults"
    />
</template>
