<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  name: '', email: '', phone: '', password: '', password_confirmation: '',
  role: 'client', provider_type: 'barber',
})
const error = ref('')

async function submit() {
  error.value = ''
  try {
    const { user } = await auth.register(form.value)
    router.push(user.role === 'provider' ? { name: 'provider-dashboard' } : { name: 'discover' })
  } catch (e) {
    error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ')
      || e.response?.data?.message || 'Registration failed.'
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-10">
    <form @submit.prevent="submit" class="w-full max-w-sm bg-white p-8 rounded-xl shadow-sm border border-gray-100">
      <h1 class="text-xl font-semibold mb-6 text-center">Create your account</h1>

      <div class="grid grid-cols-2 gap-2 mb-4">
        <button type="button" @click="form.role = 'client'"
          :class="['py-2 rounded-lg border text-sm', form.role === 'client' ? 'bg-brand-600 text-white border-brand-600' : 'text-gray-600']">
          I'm a client
        </button>
        <button type="button" @click="form.role = 'provider'"
          :class="['py-2 rounded-lg border text-sm', form.role === 'provider' ? 'bg-brand-600 text-white border-brand-600' : 'text-gray-600']">
          I'm a provider
        </button>
      </div>

      <label class="block text-sm mb-1 text-gray-600">Full name</label>
      <input v-model="form.name" required class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-gray-600">Email</label>
      <input v-model="form.email" type="email" required class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-gray-600">Phone (M-Pesa number)</label>
      <input v-model="form.phone" placeholder="07XXXXXXXX" required class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <template v-if="form.role === 'provider'">
        <label class="block text-sm mb-1 text-gray-600">Business type</label>
        <select v-model="form.provider_type" class="w-full mb-3 px-3 py-2 border rounded-lg">
          <option value="barber">Barber</option>
          <option value="salon">Salon</option>
          <option value="manicurist">Manicurist</option>
          <option value="other">Other</option>
        </select>
      </template>

      <label class="block text-sm mb-1 text-gray-600">Password</label>
      <input v-model="form.password" type="password" required class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-gray-600">Confirm password</label>
      <input v-model="form.password_confirmation" type="password" required class="w-full mb-4 px-3 py-2 border rounded-lg" />

      <p v-if="error" class="text-red-600 text-sm mb-4">{{ error }}</p>

      <button :disabled="auth.loading" type="submit"
        class="w-full bg-brand-600 text-white py-2 rounded-lg hover:bg-brand-700 disabled:opacity-50">
        {{ auth.loading ? 'Creating account…' : 'Create account' }}
      </button>

      <p class="text-sm text-center mt-4 text-gray-500">
        Already have an account? <RouterLink to="/login" class="text-brand-600">Sign in</RouterLink>
      </p>
    </form>
  </div>
</template>
