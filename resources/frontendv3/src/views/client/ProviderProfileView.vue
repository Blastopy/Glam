<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProvidersStore } from '@/stores/providers'
import { useAppointmentsStore } from '@/stores/appointments'
import { useAuthStore } from '@/stores/auth'
import { extractErrorMessage } from '@/utils/errors'
import ProviderMap from '@/components/shared/ProviderMap.vue'

const route = useRoute()
const router = useRouter()
const providers = useProvidersStore()
const appointments = useAppointmentsStore()
const auth = useAuthStore()

const selectedService = ref(null)
const scheduledAt = ref('')
const notes = ref('')
const wantsHomeService = ref(false)
const homeAddress = ref('')
const homeLat = ref(null)
const homeLng = ref(null)
const capturingLocation = ref(false)
const locationCaptured = ref(false)
const booking = ref(false)
const bookedAppointment = ref(null)
const bookError = ref('')
const loadError = ref('')
const brokenUrls = ref(new Set())

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
  if (!auth.isAuthenticated) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  selectedService.value = service
  bookedAppointment.value = null
}

function bookAnother() {
  bookedAppointment.value = null
  selectedService.value = null
  scheduledAt.value = ''
  notes.value = ''
  wantsHomeService.value = false
  homeAddress.value = ''
  homeLat.value = null
  homeLng.value = null
  locationCaptured.value = false
}

/**
 * Best-effort — lets the provider get real turn-by-turn directions instead of
 * just a typed address. Entirely optional: declining the browser permission
 * just means the provider works from the typed address alone, same as before.
 */
function captureLocation() {
  if (!navigator.geolocation) return
  capturingLocation.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      homeLat.value = pos.coords.latitude
      homeLng.value = pos.coords.longitude
      locationCaptured.value = true
      capturingLocation.value = false
    },
    () => {
      capturingLocation.value = false
      // Silent failure — the typed address still works fine without this
    }
  )
}

const minScheduledAt = computed(() => {
  const now = new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
  return now.toISOString().slice(0, 16)
})

const homeServiceFee = computed(() => parseFloat(providers.current?.home_service_fee) || 0)

const estimatedTotal = computed(() => {
  if (!selectedService.value) return 0
  const base = Number(selectedService.value.price)
  return wantsHomeService.value ? base + homeServiceFee.value : base
})

async function submitBooking() {
  bookError.value = ''

  if (new Date(scheduledAt.value) <= new Date()) {
    bookError.value = 'Please choose a time in the future.'
    return
  }

  if (wantsHomeService.value && !homeAddress.value.trim()) {
    bookError.value = 'Please add the address for your home visit.'
    return
  }

  booking.value = true
  try {
    const appt = await appointments.book({
      service_id: selectedService.value.id,
      scheduled_at: scheduledAt.value,
      client_notes: notes.value,
      is_home_service: wantsHomeService.value,
      home_address: wantsHomeService.value ? homeAddress.value : null,
      home_latitude: wantsHomeService.value ? homeLat.value : null,
      home_longitude: wantsHomeService.value ? homeLng.value : null,
    })
    bookedAppointment.value = appt
  } catch (e) {
    bookError.value = extractErrorMessage(e, 'Could not book this slot. Please try a different time.')
  } finally {
    booking.value = false
  }
}

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

function stars(rating) {
  const n = Math.round(rating)
  return '★'.repeat(n) + '☆'.repeat(5 - n)
}

function formatReviewDate(d) {
  return new Date(d).toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' })
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
      <div class="flex justify-between items-start gap-3">
        <div class="min-w-0">
          <h1 class="font-heading text-xl font-semibold text-ink flex items-center gap-2 flex-wrap">
            {{ providers.current.business_name }}
            <span v-if="providers.current.is_featured" class="text-xs px-2 py-0.5 rounded-full bg-amber-500 text-white">★ Featured</span>
            <span v-if="providers.current.is_verified" class="text-xs px-2 py-0.5 rounded-full bg-sage-500 text-white">Verified</span>
            <span v-if="providers.current.offers_home_service" class="text-xs px-2 py-0.5 rounded-full bg-clay-100 text-clay-700 border border-clay-200">
              🏠 Home visits{{ homeServiceFee ? ` (+KES ${homeServiceFee.toLocaleString()})` : '' }}
            </span>
          </h1>
          <p class="text-sm text-ink-soft capitalize">{{ providers.current.provider_type }} · {{ providers.current.address }}</p>
          <p class="text-sm text-ink-soft mt-1">
            ⭐ {{ Number(providers.current.average_rating || 0).toFixed(1) }} ({{ providers.current.reviews_count || 0 }} review{{ providers.current.reviews_count === 1 ? '' : 's' }})
          </p>
        </div>
        <span :class="['text-xs px-2 py-1 rounded-full font-medium shrink-0', providers.current.is_open ? 'bg-sage-500 text-white' : 'bg-ink/70 text-white']">
          {{ providers.current.is_open ? 'Open' : providers.current.closed_reason || 'Closed' }}
        </span>
      </div>
      <p class="text-sm text-ink-soft mt-2">{{ providers.current.bio }}</p>

      <div class="flex flex-wrap gap-4 mt-3 text-sm">
        <a v-if="providers.current.user?.phone" :href="`tel:${providers.current.user.phone}`" class="text-clay-600">
          📞 {{ providers.current.user.phone }}
        </a>
        <RouterLink :to="`/messages/${providers.current.user.id}`" class="text-clay-600">
          💬 Message this provider
        </RouterLink>
      </div>
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
      <p v-if="bookedAppointment.is_home_service" class="text-sm text-ink-soft mb-1">🏠 Home visit at {{ bookedAppointment.home_address }}</p>
      <p class="text-xs text-ink-soft mb-4">No payment needed — {{ providers.current.business_name }} will confirm shortly.</p>
      <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <RouterLink to="/my-appointments" class="px-4 py-2 rounded-lg bg-clay-600 text-white text-sm">View my appointments</RouterLink>
        <button @click="bookAnother" class="px-4 py-2 rounded-lg border text-sm text-ink-soft">Book another style</button>
      </div>
    </div>

    <div v-else-if="selectedService" class="bg-white rounded-xl border border-ink/10 p-5">
      <h3 class="font-medium text-ink mb-3">Book {{ selectedService.name }}</h3>

      <label class="block text-sm mb-1 text-ink-soft">Date &amp; time</label>
      <input v-model="scheduledAt" type="datetime-local" :min="minScheduledAt" class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <template v-if="providers.current.offers_home_service">
        <label class="flex items-center gap-2 text-sm text-ink mb-3 cursor-pointer">
          <input type="checkbox" v-model="wantsHomeService" />
          Book as a home visit
          <span v-if="homeServiceFee" class="text-ink-soft">(+KES {{ homeServiceFee.toLocaleString() }})</span>
        </label>

        <template v-if="wantsHomeService">
          <label class="block text-sm mb-1 text-ink-soft">Your address</label>
          <input v-model="homeAddress" placeholder="e.g. Apartment name, street, area" class="w-full mb-2 px-3 py-2 border rounded-lg" />

          <button type="button" @click="captureLocation" :disabled="capturingLocation"
            class="text-xs text-clay-600 mb-3 disabled:opacity-50">
            {{ locationCaptured ? '📍 Location shared with provider' : capturingLocation ? 'Getting your location…' : '📍 Share my exact location (optional, for directions)' }}
          </button>
        </template>
      </template>

      <label class="block text-sm mb-1 text-ink-soft">Notes for the provider (optional)</label>
      <textarea v-model="notes" rows="2" class="w-full mb-3 px-3 py-2 border rounded-lg"></textarea>

      <p v-if="selectedService" class="text-sm text-ink-soft mb-3">
        Estimated total: <span class="text-ink font-medium">KES {{ estimatedTotal.toLocaleString() }}</span>
      </p>

      <p v-if="bookError" class="text-red-600 text-sm mb-3">{{ bookError }}</p>

      <button :disabled="booking || !scheduledAt" @click="submitBooking"
        class="w-full bg-clay-600 text-white py-2 rounded-lg disabled:opacity-50">
        {{ booking ? 'Booking…' : 'Book appointment' }}
      </button>
    </div>

    <div class="mt-8">
      <h2 class="font-heading text-lg text-ink mb-3">
        Reviews
        <span class="text-sm font-normal text-ink-soft">({{ providers.current.reviews_count || 0 }})</span>
      </h2>

      <p v-if="!providers.current.reviews?.length" class="text-ink-soft text-sm">No reviews yet.</p>

      <div v-else class="space-y-3">
        <div v-for="r in providers.current.reviews" :key="r.id" class="bg-white rounded-xl border border-ink/10 p-4">
          <div class="flex justify-between items-start gap-2">
            <p class="font-medium text-ink text-sm">{{ r.client?.name || 'Anonymous' }}</p>
            <span class="text-amber-500 text-sm shrink-0">{{ stars(r.rating) }}</span>
          </div>
          <p v-if="r.comment" class="text-sm text-ink-soft mt-1">{{ r.comment }}</p>
          <p class="text-xs text-ink-soft/70 mt-1">{{ formatReviewDate(r.created_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
