<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProvidersStore } from '@/stores/providers'
import { useAppointmentsStore } from '@/stores/appointments'
import MpesaDepositModal from '@/components/shared/MpesaDepositModal.vue'

const route = useRoute()
const providers = useProvidersStore()
const appointments = useAppointmentsStore()

const selectedService = ref(null)
const scheduledAt = ref('')
const notes = ref('')
const booking = ref(false)
const bookedAppointment = ref(null)
const showDeposit = ref(false)
const bookError = ref('')

onMounted(() => providers.fetchOne(route.params.id))

function pickService(service) {
  selectedService.value = service
  bookedAppointment.value = null
}

async function submitBooking() {
  bookError.value = ''
  booking.value = true
  try {
    const appt = await appointments.book({
      service_id: selectedService.value.id,
      scheduled_at: scheduledAt.value,
      client_notes: notes.value,
    })
    bookedAppointment.value = appt
    showDeposit.value = true
  } catch (e) {
    bookError.value = e.response?.data?.message || 'Could not book this slot.'
  } finally {
    booking.value = false
  }
}
</script>

<template>
  <div v-if="providers.current">
    <div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-lg font-semibold">{{ providers.current.business_name }}</h1>
          <p class="text-sm text-gray-500 capitalize">{{ providers.current.provider_type }} · {{ providers.current.address }}</p>
        </div>
        <span :class="['text-xs px-2 py-1 rounded-full', providers.current.is_open ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
          {{ providers.current.is_open ? 'Open' : providers.current.closed_reason || 'Closed' }}
        </span>
      </div>
      <p class="text-sm text-gray-600 mt-2">{{ providers.current.bio }}</p>
      <RouterLink :to="`/messages/${providers.current.user.id}`" class="inline-block mt-3 text-sm text-brand-600">
        💬 Message this provider
      </RouterLink>
    </div>

    <h2 class="font-medium mb-3">Styles &amp; pricing</h2>
    <div class="grid sm:grid-cols-2 gap-3 mb-6">
      <button v-for="s in providers.current.services" :key="s.id" @click="pickService(s)"
        :class="['text-left p-4 rounded-xl border transition', selectedService?.id === s.id ? 'border-brand-500 bg-brand-50' : 'border-gray-100 bg-white hover:border-gray-300']">
        <div class="flex justify-between">
          <span class="font-medium">{{ s.name }}</span>
          <span class="text-brand-600 font-medium">KES {{ Number(s.price).toLocaleString() }}</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">{{ s.duration_minutes }} min</p>
        <p v-if="s.description" class="text-sm text-gray-500 mt-1">{{ s.description }}</p>
      </button>
    </div>

    <div v-if="selectedService" class="bg-white rounded-xl border border-gray-100 p-5">
      <h3 class="font-medium mb-3">Book {{ selectedService.name }}</h3>

      <label class="block text-sm mb-1 text-gray-600">Date &amp; time</label>
      <input v-model="scheduledAt" type="datetime-local" class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-gray-600">Notes for the provider (optional)</label>
      <textarea v-model="notes" rows="2" class="w-full mb-3 px-3 py-2 border rounded-lg"></textarea>

      <p v-if="bookError" class="text-red-600 text-sm mb-3">{{ bookError }}</p>

      <button :disabled="booking || !scheduledAt" @click="submitBooking"
        class="w-full bg-brand-600 text-white py-2 rounded-lg disabled:opacity-50">
        {{ booking ? 'Booking…' : 'Book appointment' }}
      </button>
    </div>

    <MpesaDepositModal
      v-if="showDeposit && bookedAppointment"
      :appointment-id="bookedAppointment.id"
      :amount="bookedAppointment.price_at_booking * 0.3"
      @close="showDeposit = false"
      @paid="showDeposit = false"
    />
  </div>
</template>
