<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue"
import { router, usePage } from "@inertiajs/vue3"
import LoginPopup from "./LoginPopup.vue"
import RegisterPopup from "./RegisterPopup.vue"

const page = usePage()

const isOpen = ref(false)
const mode = ref("login") // login | register
const redirectTo = ref(route(""))
const registerPhone = ref("")

const close = () => {
  isOpen.value = false
  mode.value = "login"
  registerPhone.value = ""
}

const open = (payload = {}) => {
  redirectTo.value = payload.redirect || route("")
  mode.value = "login"
  registerPhone.value = ""
  isOpen.value = true
}

const onNeedRegister = (phone) => {
  registerPhone.value = phone
  mode.value = "register"
}

const onSuccess = () => {
  close()
  router.visit(redirectTo.value)
}

const onKeydown = (e) => {
  if (e.key === "Escape" && isOpen.value) close()
}

const onOpenEvent = (e) => open(e.detail || {})

onMounted(() => {
  window.addEventListener("auth:open", onOpenEvent)
  window.addEventListener("keydown", onKeydown)
})
onBeforeUnmount(() => {
  window.removeEventListener("auth:open", onOpenEvent)
  window.removeEventListener("keydown", onKeydown)
})
</script>

<template>
  <div v-if="isOpen" class="auth-backdrop" @click.self="close">
    <div class="auth-modal">
      <button class="auth-close" type="button" @click="close">✕</button>

      <LoginPopup
        v-if="mode === 'login'"
        :redirect-to="redirectTo"
        @need-register="onNeedRegister"
        @success="onSuccess"
      />

      <RegisterPopup
        v-else
        :phone="registerPhone"
        :redirect-to="redirectTo"
        @back="mode = 'login'"
        @success="onSuccess"
      />
    </div>
  </div>
</template>

<style scoped>
.auth-backdrop{
  position: fixed; inset: 0;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(6px);
  z-index: 2000;
  display: grid;
  place-items: center;
  padding: 18px;
}
.auth-modal{
  width: min(980px, 100%);
  background: #fff;
  border-radius: 22px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 24px 70px rgba(0,0,0,.22);
}
.auth-close{
  position: absolute;
  top: 12px; right: 12px;
  width: 40px; height: 40px;
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,.12);
  background: #fff;
  font-weight: 900;
  z-index: 5;
}
</style>
