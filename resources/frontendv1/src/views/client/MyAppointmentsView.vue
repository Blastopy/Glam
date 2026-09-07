<script setup>
import { onMounted } from 'vue'
import { useAppointmentsStore } from '@/stores/appointments'

const store = useAppointmentsStore()
onMounted(() => store.fetchList())

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
</script>

<template>
  <div>
    <h1 class="text-lg font-semibold mb-4">My appointments</h1>

    <div v-if="!store.list.length" class="text-gray-400 text-sm">No appointments yet — go book one!</div>

    <div v-for="appt in store.list" :key="appt.id"
      class="bg-white rounded-xl border border-gray-100 p-4 mb-3 flex justify-between items-center">
      <div>
        <p class="font-medium">{{ appt.provider_profile?.business_name }}</p>
        <p class="text-sm text-gray-500">{{ appt.service?.name }} · {{ new Date(appt.scheduled_at).toLocaleString() }}</p>
      </div>
      <div class="flex items-center gap-3">
        <span :class="['text-xs px-2 py-1 rounded-full', statusColor[appt.status]]">{{ appt.status.replace('_', ' ') }}</span>
        <button v-if="canCheckIn(appt)" @click="store.checkIn(appt.id)"
          class="text-sm px-3 py-1.5 rounded-lg bg-clay-600 text-white">
          I'm on my way
        </button>
      </div>
    </div>
  </div>
</template>
