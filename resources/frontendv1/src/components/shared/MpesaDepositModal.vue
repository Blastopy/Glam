<script setup>
import { ref, onBeforeUnmount } from 'vue'
import paymentsApi from '@/api/payments'

const props = defineProps({
  appointmentId: { type: Number, required: true },
  amount: { type: [Number, String], required: true },
})
const emit = defineEmits(['paid', 'close'])

const phone = ref('')
const stage = ref('form') // form | waiting | success | failed
const errorMsg = ref('')
const paymentId = ref(null)
let pollTimer = null

async function submit() {
  errorMsg.value = ''
  try {
    const { data } = await paymentsApi.initiateDeposit(props.appointmentId, phone.value)
    paymentId.value = data.payment_id
    stage.value = 'waiting'
    pollStatus()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Could not start M-Pesa payment.'
  }
}

function pollStatus() {
  let attempts = 0
  pollTimer = setInterval(async () => {
    attempts++
    const { data } = await paymentsApi.status(paymentId.value)

    if (data.status === 'successful') {
      clearInterval(pollTimer)
      stage.value = 'success'
      emit('paid', data)
    } else if (data.status === 'failed' || data.status === 'cancelled') {
      clearInterval(pollTimer)
      stage.value = 'failed'
      errorMsg.value = data.result_desc || 'Payment was not completed.'
    } else if (attempts > 20) {
      // ~1 minute of polling at 3s intervals
      clearInterval(pollTimer)
      stage.value = 'failed'
      errorMsg.value = 'Payment timed out. Check your M-Pesa messages, or try again.'
    }
  }, 3000)
}

onBeforeUnmount(() => pollTimer && clearInterval(pollTimer))
</script>

<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-20 px-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm">
      <h3 class="font-semibold mb-1">Secure your booking</h3>
      <p class="text-sm text-gray-500 mb-4">A deposit of KES {{ Number(amount).toLocaleString() }} is required.</p>

      <template v-if="stage === 'form'">
        <label class="block text-sm mb-1 text-gray-600">M-Pesa phone number</label>
        <input v-model="phone" placeholder="07XXXXXXXX" class="w-full mb-4 px-3 py-2 border rounded-lg" />
        <p v-if="errorMsg" class="text-red-600 text-sm mb-3">{{ errorMsg }}</p>
        <div class="flex gap-2">
          <button @click="$emit('close')" class="flex-1 py-2 rounded-lg border text-gray-600">Cancel</button>
          <button @click="submit" class="flex-1 py-2 rounded-lg bg-clay-600 text-white">Pay deposit</button>
        </div>
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
          <p class="text-sm text-gray-700 mb-4">Deposit received — your booking is confirmed!</p>
          <button @click="$emit('close')" class="w-full py-2 rounded-lg bg-clay-600 text-white">Done</button>
        </div>
      </template>

      <template v-else-if="stage === 'failed'">
        <p class="text-red-600 text-sm mb-4">{{ errorMsg }}</p>
        <div class="flex gap-2">
          <button @click="$emit('close')" class="flex-1 py-2 rounded-lg border text-gray-600">Close</button>
          <button @click="stage = 'form'" class="flex-1 py-2 rounded-lg bg-clay-600 text-white">Try again</button>
        </div>
      </template>
    </div>
  </div>
</template>
