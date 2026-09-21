<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { homeRouteFor } from '@/router'
import { extractErrorMessage } from '@/utils/errors'
import GoogleSignInButton from '@/components/shared/GoogleSignInButton.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = ref({ email: '', password: '' })
const error = ref('')

function redirectAfterLogin(role) {
  // If they were bounced here from a login-required page (e.g. booking as a
  // guest), send them back there instead of their generic role home.
  const redirect = route.query.redirect
  if (typeof redirect === 'string' && redirect.startsWith('/')) {
    router.push(redirect)
  } else {
    router.push(homeRouteFor(role))
  }
}

async function submit() {
  error.value = ''
  try {
    const { user } = await auth.login(form.value)
    redirectAfterLogin(user.role)
  } catch (e) {
    error.value = extractErrorMessage(e, 'Login failed. Check your details.')
  }
}

async function handleGoogle(credential) {
  error.value = ''
  try {
    const { user } = await auth.loginWithGoogle(credential)
    redirectAfterLogin(user.role)
  } catch (e) {
    error.value = extractErrorMessage(e, 'Google sign-in failed. Please try again.')
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <form @submit.prevent="submit" class="w-full max-w-sm bg-white p-8 rounded-xl shadow-sm border border-gray-100">
      <img src="/logo.jpg" alt="SalonBook" class="w-14 h-14 rounded-full object-cover mx-auto mb-4" />
      <h1 class="text-xl font-semibold mb-6 text-center">Welcome back</h1>

      <label class="block text-sm mb-1 text-gray-600">Email</label>
      <input v-model="form.email" type="email" required
        class="w-full mb-4 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-clay-500" />

      <div class="flex justify-between items-center mb-1">
        <label class="block text-sm text-gray-600">Password</label>
        <RouterLink to="/forgot-password" class="text-xs text-clay-600">Forgot password?</RouterLink>
      </div>
      <input v-model="form.password" type="password" required
        class="w-full mb-4 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-clay-500" />

      <p v-if="error" class="text-red-600 text-sm mb-4">{{ error }}</p>

      <button :disabled="auth.loading" type="submit"
        class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700 disabled:opacity-50">
        {{ auth.loading ? 'Signing in…' : 'Sign in' }}
      </button>

      <div class="flex items-center gap-3 my-5">
        <div class="flex-1 border-t border-gray-200"></div>
        <span class="text-xs text-gray-400">OR</span>
        <div class="flex-1 border-t border-gray-200"></div>
      </div>

      <GoogleSignInButton @credential="handleGoogle" />

      <p class="text-sm text-center mt-5 text-gray-500">
        No account? <RouterLink :to="{ path: '/register', query: route.query }" class="text-clay-600">Register</RouterLink>
      </p>
    </form>
  </div>
</template>
