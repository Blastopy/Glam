<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'
import clientsApi from '@/api/clients'

const notifications = useNotificationsStore()
const clients = ref([])
const newClientId = ref('')
const notes = ref('')
const loading = ref(true)
const addError = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await clientsApi.list()
    clients.value = data.data
  } catch {
    // network/server failures are already toasted globally by the axios interceptor
  } finally {
    loading.value = false
  }
}

async function addClient() {
  if (!newClientId.value) return
  addError.value = ''
  try {
    await clientsApi.add({ client_id: newClientId.value, notes: notes.value, source: 'manual_add' })
    newClientId.value = ''
    notes.value = ''
    await load()
  } catch (e) {
    addError.value = extractErrorMessage(e, 'Could not add that client — check the ID and try again.')
  }
}

async function removeClient(id) {
  if (!confirm('Remove this client from your book?')) return
  try {
    await clientsApi.remove(id)
    await load()
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not remove this client.'))
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="text-lg font-semibold mb-4">Your clients</h1>

    <div class="bg-white rounded-xl border border-gray-100 p-4 mb-6">
      <h3 class="font-medium mb-3 text-sm">Add a client manually</h3>
      <div class="flex flex-col sm:flex-row gap-2">
        <input v-model="newClientId" placeholder="Client user ID" class="flex-1 min-w-0 px-3 py-2 border rounded-lg text-sm" />
        <input v-model="notes" placeholder="Notes (optional)" class="flex-1 min-w-0 px-3 py-2 border rounded-lg text-sm" />
        <button @click="addClient" class="px-4 py-2 bg-clay-600 text-white rounded-lg text-sm shrink-0">Add</button>
      </div>
      <p v-if="addError" class="text-red-600 text-xs mt-2">{{ addError }}</p>
      <p class="text-xs text-gray-400 mt-2">
        Clients are also added automatically once you mark their appointment as completed.
      </p>
    </div>

    <p v-if="loading" class="text-gray-400 text-sm">Loading…</p>
    <div v-else-if="!clients.length" class="text-gray-400 text-sm">No clients in your book yet.</div>

    <div v-for="c in clients" :key="c.id" class="bg-white rounded-xl border border-gray-100 p-4 mb-2 flex justify-between items-center">
      <div>
        <p class="font-medium">{{ c.name }}</p>
        <p class="text-sm text-gray-500">{{ c.phone }}</p>
      </div>
      <button @click="removeClient(c.id)" class="text-sm text-red-500">Remove</button>
    </div>
  </div>
</template>
