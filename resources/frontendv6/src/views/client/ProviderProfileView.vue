<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useProvidersStore } from '@/stores/providers'
import { useAppointmentsStore } from '@/stores/appointments'
import { extractErrorMessage } from '@/utils/errors'
import ProviderMap from '@/components/shared/ProviderMap.vue'

const route = useRoute()
const providers = useProvidersStore()
const appointments = useAppointmentsStore()

const selectedService = ref(null)
const scheduledAt = ref('')
const notes = ref('')
const booking = ref(false)
const bookedAppointment = ref(null) // set once booking succeeds — shows the confirmation panel instead of the form
const bookError = ref('')
const loadError = ref('')
const brokenUrls = ref(new Set()) // image URLs that failed to load — fall back gracefully instead of a broken icon

function markBroken(url) {
  brokenUrls.value.add(url)
}

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

function bookAnother() {
  bookedAppointment.value = null
  selectedService.value = null
  scheduledAt.value = ''
  notes.value = ''
}

async function submitBooking() {
  bookError.value = ''
  booking.value = true
  try {
    // No payment step — booking is free; the provider confirms manually.
    const appt = await appointments.book({
      service_id: selectedService.value.id,
      scheduled_at: scheduledAt.value,
      client_notes: notes.value,
    })
    bookedAppointment.value = appt
  } catch (e) {
    bookError.value = extractErrorMessage(e, 'Could not book this slot. Please try a different time.')
  } finally {
    booking.value = false
  }
}

// Decimal-cast lat/lng come back from Laravel as strings — coerce before handing to Leaflet/URLs.
const providerLat = computed(() => parseFloat(providers.current?.latitude))
const providerLng = computed(() => parseFloat(providers.current?.longitude))
const hasLocation = computed(() => Number.isFinite(providerLat.value) && Number.isFinite(providerLng.value))

const mapMarkers = computed(() => hasLocation.value ? [{
  id: providers.current.id,
  lat: providerLat.value,
  lng: providerLng.value,
  title: providers.current.business_name,
  subtitle: providers.current.address,
}] : [])

const directionsUrl = computed(() =>
  hasLocation.value
    ? `https://www.google.com/maps/dir/?api=1&destination=${providerLat.value},${providerLng.value}`
    : null
)
</script>

<template>
  <div v-if="loadError" class="text-center py-16">
    <p class="text-ink-soft">{{ loadError }}</p>
    <button @click="load" class="mt-3 text-sm text-clay-600 underline">Try again</button>
  </div>

  <p v-else-if="!providers.current" class="text-ink-soft text-sm py-16 text-center">Loading…</p>

  <div v-else>
    <div class="bg-white rounded-xl border border-ink/10 p-5 mb-6">
      <div class="flex justify-between items-start gap-3">
        <div class="min-w-0">
          <h1 class="font-heading text-xl font-semibold text-ink">{{ providers.current.business_name }}</h1>
          <p class="text-sm text-ink-soft capitalize">{{ providers.current.provider_type }} · {{ providers.current.address }}</p>
        </div>
        <span :class="['text-xs px-2 py-1 rounded-full font-medium shrink-0', providers.current.is_open ? 'bg-sage-500 text-white' : 'bg-ink/70 text-white']">
          {{ providers.current.is_open ? 'Open' : providers.current.closed_reason || 'Closed' }}
        </span>
      </div>
      <p class="text-sm text-ink-soft mt-2">{{ providers.current.bio }}</p>
      <RouterLink :to="`/messages/${providers.current.user.id}`" class="inline-block mt-3 text-sm text-clay-600">
        💬 Message this provider
      </RouterLink>
    </div>

    <div v-if="hasLocation" class="bg-white rounded-xl border border-ink/10 p-5 mb-6">
      <div class="flex justify-between items-center mb-3">
        <h2 class="font-heading text-base text-ink">Location</h2>
        <a :href="directionsUrl" target="_blank" rel="noopener"
          class="text-sm px-3 py-1.5 rounded-lg bg-clay-600 text-white hover:bg-clay-700 shrink-0">
          Get directions
        </a>
      </div>
      <ProviderMap :markers="mapMarkers" height-class="h-56" />
      <p class="text-xs text-ink-soft mt-2">{{ providers.current.address }}</p>
    </div>

    <h2 class="font-heading text-lg text-ink mb-3">Styles &amp; pricing</h2>
    <div class="grid sm:grid-cols-2 gap-3 mb-6">
      <button v-for="s in providers.current.services" :key="s.id" @click="pickService(s)"
        :class="['text-left rounded-xl border overflow-hidden transition', selectedService?.id === s.id ? 'border-clay-500 ring-2 ring-clay-100' : 'border-ink/10 bg-white hover:border-ink/20']">
        <div v-if="s.image_url && !brokenUrls.has(s.image_url)" class="h-32 w-full">
          <img :src="s.image_url" :alt="s.name" class="w-full h-full object-cover" @error="markBroken(s.image_url)" />
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

    <div v-if="bookedAppointment" class="bg-white rounded-xl border border-sage-300 p-5 text-center">
      <p class="text-sage-600 text-2xl mb-2">✓</p>
      <h3 class="font-heading text-lg text-ink mb-1">Booked!</h3>
      <p class="text-sm text-ink-soft mb-1">{{ selectedService?.name }} · {{ new Date(bookedAppointment.scheduled_at).toLocaleString() }}</p>
      <p class="text-xs text-ink-soft mb-4">No payment needed — {{ providers.current.business_name }} will confirm shortly.</p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <RouterLink to="/my-appointments" class="px-4 py-2 rounded-lg bg-clay-600 text-white text-sm">View my appointments</RouterLink>
        <button @click="bookAnother" class="px-4 py-2 rounded-lg border text-sm text-ink-soft">Book another style</button>
      </div>
    </div>

    <div v-else-if="selectedService" class="bg-white rounded-xl border border-ink/10 p-5">
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
  </div>
</template>
