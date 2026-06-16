<script setup>
import Footer from "./Components/Footer.vue";
import Navbar from "./Components/Navbar.vue"
import AuthModal from "../Auth/AuthModal.vue"
import '@@/css/multivendor.css'
</script>

<template>
  <div class="relative min-h-screen bg-background">
    <!-- TOP NAVBAR -->
    <Navbar />
    <AuthModal />

    <!-- PAGE CONTENT -->
    <!-- Padding-top added because navbar is fixed -->
    <main class="">
      <slot />
    </main>

    <Footer />
  </div>
</template>
