<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import authApi from '@/api/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'

const route = useRoute()
const router = useRouter()
const notifications = useNotificationsStore()

const form = ref({ token: '', email: '', password: '', password_confirmation: '' })
const submitting = ref(false)
const error = ref('')
const linkInvalid = ref(false)

onMounted(() => {
  form.value.token = route.query.token || ''
  form.value.email = route.query.email || ''

  if (!form.value.token || !form.value.email) {
    linkInvalid.value = true
  }
})

async function submit() {
  error.value = ''
  submitting.value = true
  try {
    await authApi.resetPassword(form.value)
    notifications.success('Password reset — you can now sign in.')
    router.push({ name: 'login' })
  } catch (e) {
    // Common case: an expired or already-used token — the backend's message
    // (via Laravel's Password::reset status strings) already explains why.
    error.value = extractErrorMessage(e, 'Could not reset your password. The link may have expired — request a new one.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white p-8 rounded-xl shadow-sm border border-gray-100">
      <img src="/logo.jpg" alt="SalonBook" class="w-14 h-14 rounded-full object-cover mx-auto mb-4" />

      <template v-if="linkInvalid">
        <h1 class="text-xl font-semibold mb-2 text-center">Invalid reset link</h1>
        <p class="text-sm text-gray-500 text-center mb-6">This link is missing some information. Request a new one below.</p>
        <RouterLink to="/forgot-password" class="block w-full text-center bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700">
          Request a new link
        </RouterLink>
      </template>

      <form v-else @submit.prevent="submit">
        <h1 class="text-xl font-semibold mb-2 text-center">Choose a new password</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Resetting for {{ form.email }}</p>

        <label class="block text-sm mb-1 text-gray-600">New password</label>
        <input v-model="form.password" type="password" required minlength="8"
          class="w-full mb-4 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-clay-500" />

        <label class="block text-sm mb-1 text-gray-600">Confirm new password</label>
        <input v-model="form.password_confirmation" type="password" required minlength="8"
          class="w-full mb-4 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-clay-500" />

        <p v-if="error" class="text-red-600 text-sm mb-4">{{ error }}</p>

        <button :disabled="submitting" type="submit"
          class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700 disabled:opacity-50">
          {{ submitting ? 'Resetting…' : 'Reset password' }}
        </button>
      </form>
    </div>
  </div>
</template>
