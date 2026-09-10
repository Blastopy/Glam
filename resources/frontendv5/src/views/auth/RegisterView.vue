<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { extractErrorMessage, extractFieldErrors } from '@/utils/errors'
import GoogleSignInButton from '@/components/shared/GoogleSignInButton.vue'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  name: '', email: '', phone: '', password: '', password_confirmation: '',
  role: 'client', provider_type: 'barber',
})
const error = ref('')
const fieldErrors = ref({})

async function submit() {
  error.value = ''
  fieldErrors.value = {}
  try {
    const { user } = await auth.register(form.value)
    // Providers aren't bookable until they pay the monthly fee — send them
    // straight to activation instead of a dashboard they can't do much with yet.
    router.push(user.role === 'provider' ? { name: 'provider-activate' } : { name: 'discover' })
  } catch (e) {
    fieldErrors.value = extractFieldErrors(e)
    error.value = extractErrorMessage(e, 'Registration failed. Please check your details and try again.')
  }
}

async function handleGoogle(credential) {
  error.value = ''
  try {
    const { user } = await auth.loginWithGoogle(credential)
    router.push(user.role === 'provider' ? { name: 'provider-activate' } : { name: 'discover' })
  } catch (e) {
    error.value = extractErrorMessage(e, 'Google sign-in failed. Please try again.')
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-10">
    <form @submit.prevent="submit" class="w-full max-w-sm bg-white p-8 rounded-xl shadow-sm border border-gray-100">
      <img src="/logo.jpg" alt="SalonBook" class="w-14 h-14 rounded-full object-cover mx-auto mb-4" />
      <h1 class="text-xl font-semibold mb-6 text-center">Create your account</h1>

      <div class="grid grid-cols-2 gap-2 mb-4">
        <button type="button" @click="form.role = 'client'"
          :class="['py-2 rounded-lg border text-sm', form.role === 'client' ? 'bg-clay-600 text-white border-clay-600' : 'text-gray-600']">
          I'm a client
        </button>
        <button type="button" @click="form.role = 'provider'"
          :class="['py-2 rounded-lg border text-sm', form.role === 'provider' ? 'bg-clay-600 text-white border-clay-600' : 'text-gray-600']">
          I'm a provider
        </button>
      </div>

      <label class="block text-sm mb-1 text-gray-600">Full name</label>
      <input v-model="form.name" required class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-gray-600">Email</label>
      <input v-model="form.email" type="email" required class="w-full mb-1 px-3 py-2 border rounded-lg" />
      <p v-if="fieldErrors.email" class="text-red-600 text-xs mb-2">{{ fieldErrors.email[0] }}</p>
      <div v-else class="mb-3"></div>

      <label class="block text-sm mb-1 text-gray-600">Phone (M-Pesa number)</label>
      <input v-model="form.phone" placeholder="07XXXXXXXX" required class="w-full mb-1 px-3 py-2 border rounded-lg" />
      <p v-if="fieldErrors.phone" class="text-red-600 text-xs mb-2">{{ fieldErrors.phone[0] }}</p>
      <div v-else class="mb-3"></div>

      <template v-if="form.role === 'provider'">
        <label class="block text-sm mb-1 text-gray-600">Business type</label>
        <select v-model="form.provider_type" class="w-full mb-3 px-3 py-2 border rounded-lg">
          <option value="barber">Barber</option>
          <option value="salon">Salon</option>
          <option value="manicurist">Manicurist</option>
          <option value="other">Other</option>
        </select>
        <p class="text-xs text-gray-500 mb-3 -mt-1">
          A KES 70/month fee applies to be listed and accept bookings — you'll pay this right after creating your account.
        </p>
      </template>

      <label class="block text-sm mb-1 text-gray-600">Password</label>
      <input v-model="form.password" type="password" required class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-gray-600">Confirm password</label>
      <input v-model="form.password_confirmation" type="password" required class="w-full mb-4 px-3 py-2 border rounded-lg" />

      <p v-if="error" class="text-red-600 text-sm mb-4">{{ error }}</p>

      <button :disabled="auth.loading" type="submit"
        class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700 disabled:opacity-50">
        {{ auth.loading ? 'Creating account…' : 'Create account' }}
      </button>

      <p class="text-sm text-center mt-4 text-gray-500">
        Already have an account? <RouterLink to="/login" class="text-clay-600">Sign in</RouterLink>
      </p>

      <template v-if="form.role === 'client'">
        <div class="flex items-center gap-3 my-5">
          <div class="flex-1 border-t border-gray-200"></div>
          <span class="text-xs text-gray-400">OR</span>
          <div class="flex-1 border-t border-gray-200"></div>
        </div>
        <GoogleSignInButton @credential="handleGoogle" />
      </template>
    </form>
  </div>
</template>
