<script setup>
import { ref, onMounted } from 'vue'
import adminApi from '@/api/admin'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'

const notifications = useNotificationsStore()
const appointments = ref([])
const statusFilter = ref('')
const loading = ref(true)

const statusColor = {
  pending: 'bg-amber-500',
  confirmed: 'bg-sage-500',
  in_progress: 'bg-sage-400',
  completed: 'bg-ink',
  cancelled: 'bg-clay-400',
  no_show: 'bg-clay-600',
}

async function load() {
  loading.value = true
  try {
    const { data } = await adminApi.appointments({ status: statusFilter.value || undefined })
    appointments.value = data.data
  } catch {
    // network/server failures are already toasted globally by the axios interceptor
  } finally {
    loading.value = false
  }
}

async function cancel(appt) {
  const reason = prompt(`Reason for cancelling ${appt.client?.name}'s appointment? (optional)`)
  if (reason === null) return // user hit cancel on the prompt itself
  try {
    await adminApi.cancelAppointment(appt.id, reason)
    await load()
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not cancel this appointment.'))
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="font-heading text-2xl text-ink mb-4">Appointments</h1>

    <select v-model="statusFilter" @change="load" class="mb-5 px-3 py-2 border rounded-lg text-sm bg-white">
      <option value="">All statuses</option>
      <option value="pending">Pending</option>
      <option value="confirmed">Confirmed</option>
      <option value="in_progress">In progress</option>
      <option value="completed">Completed</option>
      <option value="cancelled">Cancelled</option>
      <option value="no_show">No-show</option>
    </select>

    <p v-if="loading" class="text-ink-soft text-sm">Loading…</p>

    <div v-else class="bg-white rounded-xl border border-ink/10 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-sage-50 text-ink-soft text-left">
          <tr>
            <th class="px-4 py-2.5 font-medium">Client</th>
            <th class="px-4 py-2.5 font-medium">Provider</th>
            <th class="px-4 py-2.5 font-medium">Service</th>
            <th class="px-4 py-2.5 font-medium">When</th>
            <th class="px-4 py-2.5 font-medium">Status</th>
            <th class="px-4 py-2.5 font-medium"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in appointments" :key="a.id" class="border-t border-ink/5">
            <td class="px-4 py-2.5 text-ink">{{ a.client?.name }}</td>
            <td class="px-4 py-2.5 text-ink-soft">{{ a.provider_profile?.business_name }}</td>
            <td class="px-4 py-2.5 text-ink-soft">{{ a.service?.name }}</td>
            <td class="px-4 py-2.5 text-ink-soft">{{ new Date(a.scheduled_at).toLocaleString() }}</td>
            <td class="px-4 py-2.5">
              <span :class="['text-xs px-2 py-1 rounded-full text-white', statusColor[a.status]]">{{ a.status.replace('_', ' ') }}</span>
            </td>
            <td class="px-4 py-2.5 text-right">
              <button v-if="!['completed', 'cancelled'].includes(a.status)" @click="cancel(a)"
                class="text-xs px-3 py-1.5 rounded-lg border text-red-600 border-red-200">
                Force cancel
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!appointments.length" class="p-4 text-sm text-ink-soft">No appointments match this filter.</p>
    </div>
  </div>
</template>
