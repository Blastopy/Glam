<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const emit = defineEmits(['credential'])
const buttonEl = ref(null)
let pollTimer = null

function render() {
  if (!window.google?.accounts?.id) return false

  window.google.accounts.id.initialize({
    client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
    callback: (response) => emit('credential', response.credential),
  })

  window.google.accounts.id.renderButton(buttonEl.value, {
    type: 'standard',
    theme: 'outline',
    size: 'large',
    width: 320,
    text: 'continue_with',
  })

  return true
}

onMounted(() => {
  // The GSI script loads async — it may not be ready the instant this component
  // mounts, so poll briefly rather than assuming a fixed load order.
  if (render()) return
  pollTimer = setInterval(() => {
    if (render()) clearInterval(pollTimer)
  }, 200)
})

onBeforeUnmount(() => pollTimer && clearInterval(pollTimer))
</script>

<template>
  <div class="flex justify-center">
    <div ref="buttonEl"></div>
  </div>
</template>
