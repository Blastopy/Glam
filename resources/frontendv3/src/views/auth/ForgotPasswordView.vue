<script setup>
import { ref } from 'vue'
import authApi from '@/api/auth'
import { extractErrorMessage } from '@/utils/errors'

const email = ref('')
const sending = ref(false)
const sent = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  sending.value = true
  try {
    await authApi.forgotPassword(email.value)
    // Always shown regardless of whether the email exists — the backend
    // deliberately doesn't reveal that, so neither does this screen.
    sent.value = true
  } catch (e) {
    error.value = extractErrorMessage(e, 'Could not send the reset link. Please try again.')
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white p-8 rounded-xl shadow-sm border border-gray-100">
      <img src="/logo.jpg" alt="SalonBook" class="w-14 h-14 rounded-full object-cover mx-auto mb-4" />

      <template v-if="sent">
        <h1 class="text-xl font-semibold mb-2 text-center">Check your email</h1>
        <p class="text-sm text-gray-500 text-center mb-6">
          If an account exists for <strong>{{ email }}</strong>, we've sent a link to reset your password.
        </p>
        <RouterLink to="/login" class="block w-full text-center bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700">
          Back to sign in
        </RouterLink>
      </template>

      <form v-else @submit.prevent="submit">
        <h1 class="text-xl font-semibold mb-2 text-center">Forgot your password?</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Enter your email and we'll send you a reset link.</p>

        <label class="block text-sm mb-1 text-gray-600">Email</label>
        <input v-model="email" type="email" required
          class="w-full mb-4 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-clay-500" />

        <p v-if="error" class="text-red-600 text-sm mb-4">{{ error }}</p>

        <button :disabled="sending" type="submit"
          class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700 disabled:opacity-50">
          {{ sending ? 'Sending…' : 'Send reset link' }}
        </button>

        <p class="text-sm text-center mt-4 text-gray-500">
          <RouterLink to="/login" class="text-clay-600">Back to sign in</RouterLink>
        </p>
      </form>
    </div>
  </div>
</template>
