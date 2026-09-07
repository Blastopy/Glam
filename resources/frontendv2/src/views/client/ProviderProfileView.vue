<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProvidersStore } from '@/stores/providers'
import { useAppointmentsStore } from '@/stores/appointments'
import { extractErrorMessage } from '@/utils/errors'
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
const loadError = ref('')

async function load() {
  loadError.value = ''
  try {
    await providers.fetchOne(route.params.id)
  } catch (e) {
    loadError.value = extractErrorMessage(e, 'Could not load this provider. Please go back and try again.')
  }
}

onMounted(load)

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
    bookError.value = extractErrorMessage(e, 'Could not book this slot. Please try a different time.')
  } finally {
    booking.value = false
  }
}
</script>

<template>
  <div v-if="loadError" class="text-center py-16">
    <p class="text-ink-soft">{{ loadError }}</p>
    <button @click="load" class="mt-3 text-sm text-clay-600 underline">Try again</button>
  </div>

  <p v-else-if="!providers.current" class="text-ink-soft text-sm py-16 text-center">Loading…</p>

  <div v-else>
    <div class="bg-white rounded-xl border border-ink/10 p-5 mb-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="font-heading text-xl font-semibold text-ink">{{ providers.current.business_name }}</h1>
          <p class="text-sm text-ink-soft capitalize">{{ providers.current.provider_type }} · {{ providers.current.address }}</p>
        </div>
        <span :class="['text-xs px-2 py-1 rounded-full font-medium', providers.current.is_open ? 'bg-sage-500 text-white' : 'bg-ink/70 text-white']">
          {{ providers.current.is_open ? 'Open' : providers.current.closed_reason || 'Closed' }}
        </span>
      </div>
      <p class="text-sm text-ink-soft mt-2">{{ providers.current.bio }}</p>
      <RouterLink :to="`/messages/${providers.current.user.id}`" class="inline-block mt-3 text-sm text-clay-600">
        💬 Message this provider
      </RouterLink>
    </div>

    <h2 class="font-heading text-lg text-ink mb-3">Styles &amp; pricing</h2>
    <div class="grid sm:grid-cols-2 gap-3 mb-6">
      <button v-for="s in providers.current.services" :key="s.id" @click="pickService(s)"
        :class="['text-left rounded-xl border overflow-hidden transition', selectedService?.id === s.id ? 'border-clay-500 ring-2 ring-clay-100' : 'border-ink/10 bg-white hover:border-ink/20']">
        <div v-if="s.image_url" class="h-32 w-full">
          <img :src="s.image_url" :alt="s.name" class="w-full h-full object-cover" />
        </div>
        <div class="p-4">
          <div class="flex justify-between">
            <span class="font-medium text-ink">{{ s.name }}</span>
            <span class="text-clay-600 font-medium">KES {{ Number(s.price).toLocaleString() }}</span>
          </div>
          <p class="text-xs text-ink-soft mt-1">{{ s.duration_minutes }} min</p>
          <p v-if="s.description" class="text-sm text-ink-soft mt-1">{{ s.description }}</p>
        </div>
      </button>
    </div>

    <div v-if="selectedService" class="bg-white rounded-xl border border-ink/10 p-5">
      <h3 class="font-medium text-ink mb-3">Book {{ selectedService.name }}</h3>

      <label class="block text-sm mb-1 text-ink-soft">Date &amp; time</label>
      <input v-model="scheduledAt" type="datetime-local" class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-ink-soft">Notes for the provider (optional)</label>
      <textarea v-model="notes" rows="2" class="w-full mb-3 px-3 py-2 border rounded-lg"></textarea>

      <p v-if="bookError" class="text-red-600 text-sm mb-3">{{ bookError }}</p>

      <button :disabled="booking || !scheduledAt" @click="submitBooking"
        class="w-full bg-clay-600 text-white py-2 rounded-lg disabled:opacity-50">
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
