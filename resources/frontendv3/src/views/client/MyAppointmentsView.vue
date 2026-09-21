<script setup>
import { ref, onMounted } from 'vue'
import { useAppointmentsStore } from '@/stores/appointments'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'
import ReviewModal from '@/components/shared/ReviewModal.vue'

const store = useAppointmentsStore()
const notifications = useNotificationsStore()
const loading = ref(true)
const reviewingAppointment = ref(null) // the appointment currently open in the review modal, or null

async function load() {
  loading.value = true
  try {
    await store.fetchList()
  } catch {
    // network/server failures are already toasted globally by the axios interceptor
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function checkIn(appointmentId) {
  try {
    await store.checkIn(appointmentId)
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not check in right now.'))
  }
}

async function cancel(appt) {
  if (!confirm('Cancel this appointment?')) return
  try {
    await store.updateStatus(appt.id, 'cancelled')
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not cancel this appointment.'))
  }
}

function openReview(appt) {
  reviewingAppointment.value = appt
}

function handleReviewSubmitted(review) {
  // Attach the new review straight onto the local list — no need to refetch
  // the whole page just to flip one appointment's card into "reviewed" state.
  const appt = store.list.find((a) => a.id === reviewingAppointment.value.id)
  if (appt) appt.review = review
  reviewingAppointment.value = null
  notifications.success('Thanks for your review!')
}

const statusColor = {
  pending: 'bg-yellow-100 text-yellow-700',
  confirmed: 'bg-blue-100 text-blue-700',
  in_progress: 'bg-purple-100 text-purple-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-gray-100 text-gray-500',
  no_show: 'bg-red-100 text-red-700',
}

function canCheckIn(appt) {
  return appt.status === 'confirmed' && !appt.checked_in_at
}

function canCancel(appt) {
  return ['pending', 'confirmed'].includes(appt.status)
}

function canReview(appt) {
  return appt.status === 'completed' && !appt.review
}

function stars(rating) {
  const n = Math.round(rating)
  return '★'.repeat(n) + '☆'.repeat(5 - n)
}
</script>

<template>
  <div>
    <h1 class="text-lg font-semibold mb-4">My appointments</h1>

    <p v-if="loading" class="text-gray-400 text-sm">Loading…</p>
    <div v-else-if="!store.list.length" class="text-gray-400 text-sm">No appointments yet — go book one!</div>

    <div v-for="appt in store.list" :key="appt.id"
      class="bg-white rounded-xl border border-gray-100 p-4 mb-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
      <div class="min-w-0">
        <p class="font-medium truncate">{{ appt.provider_profile?.business_name }}</p>
        <p class="text-sm text-gray-500">{{ appt.service?.name }} · {{ new Date(appt.scheduled_at).toLocaleString() }}</p>
        <p v-if="appt.review" class="text-xs text-amber-500 mt-1">{{ stars(appt.review.rating) }} <span class="text-gray-400">— you reviewed this</span></p>
      </div>
      <div class="flex items-center gap-3 shrink-0 flex-wrap">
        <span :class="['text-xs px-2 py-1 rounded-full', statusColor[appt.status]]">{{ appt.status.replace('_', ' ') }}</span>
        <button v-if="canCheckIn(appt)" @click="checkIn(appt.id)"
          class="text-sm px-3 py-1.5 rounded-lg bg-clay-600 text-white">
          I'm on my way
        </button>
        <button v-if="canReview(appt)" @click="openReview(appt)"
          class="text-sm px-3 py-1.5 rounded-lg border text-amber-600 border-amber-200">
          Leave a review
        </button>
        <button v-if="canCancel(appt)" @click="cancel(appt)"
          class="text-sm px-3 py-1.5 rounded-lg border text-red-600 border-red-200">
          Cancel
        </button>
      </div>
    </div>

    <ReviewModal
      v-if="reviewingAppointment"
      :appointment-id="reviewingAppointment.id"
      :provider-name="reviewingAppointment.provider_profile?.business_name"
      @submitted="handleReviewSubmitted"
      @close="reviewingAppointment = null"
    />
  </div>
</template>
