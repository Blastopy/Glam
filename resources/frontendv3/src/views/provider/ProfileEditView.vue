<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'
import providersApi from '@/api/providers'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const form = ref({
  business_name: '', provider_type: 'barber', bio: '', address: '',
  latitude: null, longitude: null,
  offers_home_service: false, home_service_fee: '',
})
const closedReason = ref('')
const saving = ref(false)
const savedMsg = ref('')
const saveError = ref('')
const togglingOpen = ref(false)

onMounted(async () => {
  const profile = auth.user.providerProfile || auth.user.provider_profile
  if (profile) form.value = { ...form.value, ...profile }
})

async function save() {
  saving.value = true
  savedMsg.value = ''
  saveError.value = ''
  try {
    await providersApi.updateProfile(form.value)
    savedMsg.value = 'Profile updated.'
  } catch (e) {
    saveError.value = extractErrorMessage(e, 'Could not save your profile. Please try again.')
  } finally {
    saving.value = false
  }
}

async function useCurrentLocation() {
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.value.latitude = pos.coords.latitude
      form.value.longitude = pos.coords.longitude
    },
    () => {
      notifications.error('Could not get your location. Check your browser permissions and try again.')
    }
  )
}

async function toggleOpen(isOpen) {
  togglingOpen.value = true
  try {
    await providersApi.toggleOpen({ is_open: isOpen, closed_reason: isOpen ? null : closedReason.value })
    savedMsg.value = isOpen ? "You're now shown as open." : "You're now shown as closed."
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not update your open/closed status.'))
  } finally {
    togglingOpen.value = false
  }
}
</script>

<template>
  <div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
      <h1 class="text-lg font-semibold mb-4">Business profile</h1>

      <label class="block text-sm mb-1 text-gray-600">Business name</label>
      <input v-model="form.business_name" class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <label class="block text-sm mb-1 text-gray-600">Type</label>
      <select v-model="form.provider_type" class="w-full mb-3 px-3 py-2 border rounded-lg">
        <option value="barber">Barber</option>
        <option value="salon">Salon</option>
        <option value="manicurist">Manicurist</option>
        <option value="other">Other</option>
      </select>

      <label class="block text-sm mb-1 text-gray-600">Bio</label>
      <textarea v-model="form.bio" rows="3" class="w-full mb-3 px-3 py-2 border rounded-lg"></textarea>

      <label class="block text-sm mb-1 text-gray-600">Address</label>
      <input v-model="form.address" class="w-full mb-3 px-3 py-2 border rounded-lg" />

      <button type="button" @click="useCurrentLocation" class="text-sm text-clay-600 mb-4">
        📍 Use my current location for map search
      </button>

      <div class="border border-gray-200 rounded-lg p-4 mb-4">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
          <input type="checkbox" v-model="form.offers_home_service" />
          I can travel to a client's home
        </label>
        <p class="text-xs text-gray-500 mt-1">Clients will see this and can request a home visit when booking.</p>

        <template v-if="form.offers_home_service">
          <label class="block text-sm mb-1 text-gray-600 mt-3">Home visit fee (KES, optional)</label>
          <input v-model="form.home_service_fee" type="number" min="0" placeholder="e.g. 300"
            class="w-full px-3 py-2 border rounded-lg" />
          <p class="text-xs text-gray-500 mt-1">Added on top of the service price for home bookings. Leave blank for no extra charge.</p>
        </template>
      </div>

      <p v-if="savedMsg" class="text-green-600 text-sm mb-3">{{ savedMsg }}</p>
      <p v-if="saveError" class="text-red-600 text-sm mb-3">{{ saveError }}</p>

      <button :disabled="saving" @click="save" class="px-5 py-2 bg-clay-600 text-white rounded-lg disabled:opacity-50">
        {{ saving ? 'Saving…' : 'Save profile' }}
      </button>
    </div>

    <div>
      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <h3 class="font-medium mb-3">Open / Closed</h3>
        <button :disabled="togglingOpen" @click="toggleOpen(true)"
          class="w-full mb-2 py-2 rounded-lg bg-green-600 text-white text-sm disabled:opacity-50">Mark as open</button>

        <input v-model="closedReason" placeholder="Reason (e.g. Public holiday)" class="w-full mb-2 px-3 py-2 border rounded-lg text-sm" />
        <button :disabled="togglingOpen" @click="toggleOpen(false)"
          class="w-full py-2 rounded-lg bg-gray-600 text-white text-sm disabled:opacity-50">Mark as closed</button>
      </div>
    </div>
  </div>
</template>
