<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import subscriptionApi from '@/api/subscription'
import { extractErrorMessage } from '@/utils/errors'

const auth = useAuthStore()
const router = useRouter()

const status = ref(null) // { is_active, expires_at, fee }
const loading = ref(true)
const phone = ref('')
const stage = ref('form') // form | waiting | success | failed
const errorMsg = ref('')
const paymentId = ref(null)
let pollTimer = null

async function loadStatus() {
  loading.value = true
  try {
    const { data } = await subscriptionApi.status()
    status.value = data
  } catch (e) {
    errorMsg.value = extractErrorMessage(e, 'Could not load your subscription status.')
  } finally {
    loading.value = false
  }
}

onMounted(loadStatus)

async function submit() {
  errorMsg.value = ''
  try {
    const { data } = await subscriptionApi.pay(phone.value)
    paymentId.value = data.payment_id
    stage.value = 'waiting'
    pollStatus()
  } catch (e) {
    errorMsg.value = extractErrorMessage(e, 'Could not start M-Pesa payment.')
  }
}

function pollStatus() {
  let attempts = 0
  let consecutiveFailures = 0

  pollTimer = setInterval(async () => {
    attempts++
    try {
      const { data } = await subscriptionApi.poll(paymentId.value)
      consecutiveFailures = 0

      if (data.status === 'successful') {
        clearInterval(pollTimer)
        stage.value = 'success'
        status.value = {
          is_active: true,
          expires_at: data.period_end,
          fee: status.value?.fee,
        }
        await auth.refreshUser() // so router guards see the account as active immediately
      } else if (data.status === 'failed' || data.status === 'cancelled') {
        clearInterval(pollTimer)
        stage.value = 'failed'
        errorMsg.value = data.result_desc || 'Payment was not completed.'
      } else if (attempts > 20) {
        clearInterval(pollTimer)
        stage.value = 'failed'
        errorMsg.value = 'Payment timed out. Check your M-Pesa messages, or try again.'
      }
    } catch (e) {
      consecutiveFailures++
      if (consecutiveFailures >= 5 || attempts > 20) {
        clearInterval(pollTimer)
        stage.value = 'failed'
        errorMsg.value = extractErrorMessage(e, 'Lost connection while checking payment status. Check your M-Pesa messages, or try again.')
      }
    }
  }, 3000)
}

onBeforeUnmount(() => pollTimer && clearInterval(pollTimer))

function goToDashboard() {
  router.push({ name: 'provider-dashboard' })
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('en-KE', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
  <div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white p-8 rounded-xl shadow-sm border border-gray-100">
      <img src="/logo.jpg" alt="SalonBook" class="w-14 h-14 rounded-full object-cover mx-auto mb-4" />

      <p v-if="loading" class="text-center text-sm text-gray-500">Loading…</p>

      <template v-else>
        <!-- Already active: show status + optional early renewal -->
        <template v-if="status?.is_active && stage === 'form'">
          <h1 class="text-xl font-semibold mb-2 text-center">You're active</h1>
          <p class="text-sm text-gray-500 text-center mb-6">
            Your subscription is active until <strong>{{ formatDate(status.expires_at) }}</strong>.
          </p>
          <button @click="goToDashboard" class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700 mb-3">
            Go to dashboard
          </button>
          <p class="text-xs text-center text-gray-400">
            Want to renew early? Paying now adds another 30 days on top of your current expiry.
          </p>
        </template>

        <!-- Inactive/expired: payment form -->
        <template v-else-if="stage === 'form'">
          <h1 class="text-xl font-semibold mb-2 text-center">
            {{ status?.expires_at ? 'Your subscription has expired' : 'Activate your account' }}
          </h1>
          <p class="text-sm text-gray-500 text-center mb-6">
            Pay KES {{ status?.fee ?? 70 }} via M-Pesa to be listed and start accepting bookings for the next 30 days.
          </p>

          <label class="block text-sm mb-1 text-gray-600">M-Pesa phone number</label>
          <input v-model="phone" placeholder="07XXXXXXXX" class="w-full mb-4 px-3 py-2 border rounded-lg" />

          <p v-if="errorMsg" class="text-red-600 text-sm mb-4">{{ errorMsg }}</p>

          <button @click="submit" class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700">
            Pay KES {{ status?.fee ?? 70 }}
          </button>
        </template>

        <template v-else-if="stage === 'waiting'">
          <div class="text-center py-4">
            <div class="animate-spin h-8 w-8 border-2 border-clay-500 border-t-transparent rounded-full mx-auto mb-3"></div>
            <p class="text-sm text-gray-600">Check your phone and enter your M-Pesa PIN to approve the prompt…</p>
          </div>
        </template>

        <template v-else-if="stage === 'success'">
          <div class="text-center py-4">
            <p class="text-green-600 text-2xl mb-2">✓</p>
            <p class="text-sm text-gray-700 mb-1">You're activated!</p>
            <p class="text-xs text-gray-500 mb-4">Active until {{ formatDate(status.expires_at) }}</p>
            <button @click="goToDashboard" class="w-full py-2 rounded-lg bg-clay-600 text-white">Go to dashboard</button>
          </div>
        </template>

        <template v-else-if="stage === 'failed'">
          <p class="text-red-600 text-sm mb-4 text-center">{{ errorMsg }}</p>
          <button @click="stage = 'form'" class="w-full py-2 rounded-lg bg-clay-600 text-white">Try again</button>
        </template>
      </template>
    </div>
  </div>
</template>
