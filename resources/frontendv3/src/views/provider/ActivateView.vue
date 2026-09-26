<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import subscriptionApi from '@/api/subscription'
import { extractErrorMessage } from '@/utils/errors'
import { getEcho } from '@/echo'

const auth = useAuthStore()
const router = useRouter()

const status = ref(null) // { is_active, expires_at, fee, days_left, payments_enabled }
const loading = ref(true)
const phone = ref('')
const paying = ref(false)
const stage = ref('form') // form | waiting | success | failed
const errorMsg = ref('')
const paymentId = ref(null)
const redirectIn = ref(3)
let pollTimer = null
let redirectTimer = null
let subscribedChannelName = null

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
  if (!phone.value.trim()) {
    errorMsg.value = 'Enter the M-Pesa number to pay from.'
    return
  }
  errorMsg.value = ''
  paying.value = true
  try {
    const { data } = await subscriptionApi.pay(phone.value)
    paymentId.value = data.payment_id
    stage.value = 'waiting'
    subscribeForInstantConfirmation()
    pollStatus() // fallback in case Reverb isn't running or the event is missed
  } catch (e) {
    errorMsg.value = extractErrorMessage(e, 'Could not start M-Pesa payment. Please try again.')
  } finally {
    paying.value = false
  }
}

function subscribeForInstantConfirmation() {
  const providerProfileId = auth.user?.providerProfile?.id || auth.user?.provider_profile?.id
  if (!providerProfileId) return

  subscribedChannelName = `provider.${providerProfileId}`
  getEcho()?.private(subscribedChannelName).listen('.subscription.confirmed', (e) => {
    if (stage.value !== 'waiting') return
    handleSuccess(e.expires_at)
  })
}

function unsubscribeFromChannel() {
  if (subscribedChannelName) getEcho()?.leave(subscribedChannelName)
  subscribedChannelName = null
}

async function handleSuccess(expiresAt) {
  pollTimer && clearInterval(pollTimer)
  unsubscribeFromChannel()
  stage.value = 'success'
  status.value = { ...status.value, is_active: true, expires_at: expiresAt, days_left: null }

  // Fixes "Go to dashboard" appearing to do nothing: the router guard on
  // /dashboard reads auth.user's cached providerProfile synchronously. If we
  // only relied on the network refetch below and it was slow or failed, the
  // guard would still see the pre-payment (unpaid) state and silently bounce
  // the click straight back to this page. Patch local state immediately so
  // the button works regardless of that refetch's timing or success.
  auth.setProviderSubscriptionActive(expiresAt)

  try {
    await auth.refreshUser() // picks up the authoritative days_left, verified/featured flags, etc.
    const { data } = await subscriptionApi.status()
    status.value = data
  } catch {
    // non-critical — the success screen and the dashboard redirect both still
    // work correctly off the optimistic update above even if this fails
  }

  startRedirectCountdown()
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
        handleSuccess(data.period_end)
      } else if (data.status === 'failed' || data.status === 'cancelled') {
        clearInterval(pollTimer)
        unsubscribeFromChannel()
        stage.value = 'failed'
        errorMsg.value = data.result_desc || 'Payment was not completed — you may have cancelled the M-Pesa prompt.'
      } else if (attempts > 20) {
        clearInterval(pollTimer)
        unsubscribeFromChannel()
        stage.value = 'failed'
        errorMsg.value = 'Payment timed out waiting for M-Pesa. Check your phone for a message, or try again.'
      }
    } catch (e) {
      consecutiveFailures++
      if (consecutiveFailures >= 5 || attempts > 20) {
        clearInterval(pollTimer)
        unsubscribeFromChannel()
        stage.value = 'failed'
        errorMsg.value = extractErrorMessage(e, 'Lost connection while checking payment status. Check your M-Pesa messages, or try again.')
      }
    }
  }, 3000)
}

function startRedirectCountdown() {
  redirectIn.value = 3
  redirectTimer = setInterval(() => {
    redirectIn.value--
    if (redirectIn.value <= 0) {
      clearInterval(redirectTimer)
      goToDashboard()
    }
  }, 1000)
}

onBeforeUnmount(() => {
  pollTimer && clearInterval(pollTimer)
  redirectTimer && clearInterval(redirectTimer)
  unsubscribeFromChannel()
})

function goToDashboard() {
  router.push({ name: 'provider-dashboard' })
}

function retry() {
  errorMsg.value = ''
  stage.value = 'form'
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
        <!-- Admin has switched payments off system-wide — nothing to pay, nothing to show a date for. -->
        <template v-if="status?.payments_enabled === false">
          <h1 class="text-xl font-semibold mb-2 text-center">You're active</h1>
          <p class="text-sm text-gray-500 text-center mb-6">
            Payments are currently disabled platform-wide — your account is fully active with no fee required.
          </p>
          <button @click="goToDashboard" class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700">
            Go to dashboard
          </button>
        </template>

        <!-- Already active with a real expiry: show status + optional early renewal -->
        <template v-else-if="status?.is_active && stage === 'form'">
          <h1 class="text-xl font-semibold mb-2 text-center">You're active</h1>
          <p class="text-sm text-gray-500 text-center mb-1">
            Your subscription is active until <strong>{{ formatDate(status.expires_at) }}</strong>.
          </p>
          <p class="text-xs text-sage-600 text-center mb-6">{{ status.days_left }} day{{ status.days_left === 1 ? '' : 's' }} left</p>
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
          <input v-model="phone" placeholder="07XXXXXXXX" :disabled="paying" class="w-full mb-4 px-3 py-2 border rounded-lg disabled:opacity-50" />

          <p v-if="errorMsg" class="text-red-600 text-sm mb-4">{{ errorMsg }}</p>

          <button :disabled="paying" @click="submit" class="w-full bg-clay-600 text-white py-2 rounded-lg hover:bg-clay-700 disabled:opacity-50">
            {{ paying ? 'Starting payment…' : `Pay KES ${status?.fee ?? 70}` }}
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
            <p class="text-xs text-gray-500 mb-4">
              Active until {{ formatDate(status.expires_at) }}<template v-if="status.days_left != null"> · {{ status.days_left }} days left</template>
            </p>
            <button @click="goToDashboard" class="w-full py-2 rounded-lg bg-clay-600 text-white mb-2">Go to dashboard now</button>
            <p class="text-xs text-gray-400">Redirecting in {{ redirectIn }}s…</p>
          </div>
        </template>

        <template v-else-if="stage === 'failed'">
          <div class="text-center py-2">
            <p class="text-red-600 text-2xl mb-2">✕</p>
            <p class="text-red-600 text-sm mb-4">{{ errorMsg }}</p>
            <button @click="retry" class="w-full py-2 rounded-lg bg-clay-600 text-white">Try again</button>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>
