<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import providersApi from '@/api/providers'
import servicesApi from '@/api/services'

const auth = useAuthStore()
const services = ref([])
const form = ref({ name: '', description: '', price: '', duration_minutes: 30 })
const editingId = ref(null)
const saving = ref(false)

async function load() {
  const providerId = auth.user.providerProfile?.id || auth.user.provider_profile?.id
  const { data } = await providersApi.services(providerId)
  services.value = data
}

function resetForm() {
  form.value = { name: '', description: '', price: '', duration_minutes: 30 }
  editingId.value = null
}

function edit(service) {
  editingId.value = service.id
  form.value = { ...service }
}

async function submit() {
  saving.value = true
  try {
    if (editingId.value) {
      await servicesApi.update(editingId.value, form.value)
    } else {
      await servicesApi.create(form.value)
    }
    resetForm()
    await load()
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!confirm('Remove this style from your catalogue?')) return
  await servicesApi.remove(id)
  await load()
}

onMounted(load)
</script>

<template>
  <div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
      <h1 class="text-lg font-semibold mb-4">Your catalogue</h1>

      <div v-if="!services.length" class="text-gray-400 text-sm">No styles added yet.</div>

      <div v-for="s in services" :key="s.id" class="bg-white rounded-xl border border-gray-100 p-4 mb-3 flex justify-between items-start">
        <div>
          <p class="font-medium">{{ s.name }}</p>
          <p class="text-sm text-gray-500">{{ s.duration_minutes }} min · KES {{ Number(s.price).toLocaleString() }}</p>
          <p v-if="s.description" class="text-sm text-gray-500 mt-1">{{ s.description }}</p>
        </div>
        <div class="flex gap-2 text-sm">
          <button @click="edit(s)" class="text-brand-600">Edit</button>
          <button @click="remove(s.id)" class="text-red-500">Remove</button>
        </div>
      </div>
    </div>

    <div>
      <div class="bg-white rounded-xl border border-gray-100 p-4 sticky top-20">
        <h3 class="font-medium mb-3">{{ editingId ? 'Edit style' : 'Add a style' }}</h3>

        <label class="block text-sm mb-1 text-gray-600">Name</label>
        <input v-model="form.name" class="w-full mb-3 px-3 py-2 border rounded-lg text-sm" />

        <label class="block text-sm mb-1 text-gray-600">Description</label>
        <textarea v-model="form.description" rows="2" class="w-full mb-3 px-3 py-2 border rounded-lg text-sm"></textarea>

        <label class="block text-sm mb-1 text-gray-600">Price (KES)</label>
        <input v-model="form.price" type="number" min="0" class="w-full mb-3 px-3 py-2 border rounded-lg text-sm" />

        <label class="block text-sm mb-1 text-gray-600">Duration (minutes)</label>
        <input v-model="form.duration_minutes" type="number" min="5" class="w-full mb-4 px-3 py-2 border rounded-lg text-sm" />

        <div class="flex gap-2">
          <button v-if="editingId" @click="resetForm" class="flex-1 py-2 rounded-lg border text-sm text-gray-600">Cancel</button>
          <button :disabled="saving" @click="submit" class="flex-1 py-2 rounded-lg bg-brand-600 text-white text-sm disabled:opacity-50">
            {{ saving ? 'Saving…' : editingId ? 'Save' : 'Add' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
