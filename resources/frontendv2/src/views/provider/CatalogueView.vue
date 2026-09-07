<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'
import providersApi from '@/api/providers'
import servicesApi from '@/api/services'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const services = ref([])
const form = ref({ name: '', description: '', price: '', duration_minutes: 30 })
const imageFile = ref(null)
const imagePreview = ref(null)
const editingId = ref(null)
const saving = ref(false)
const formError = ref('')
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const providerId = auth.user.providerProfile?.id || auth.user.provider_profile?.id
    const { data } = await providersApi.services(providerId)
    services.value = data
  } catch {
    // network/server failures are already toasted globally by the axios interceptor
  } finally {
    loading.value = false
  }
}

function onImageChange(e) {
  const file = e.target.files[0] || null
  if (file && file.size > 4 * 1024 * 1024) {
    formError.value = 'That photo is too large — please choose one under 4MB.'
    e.target.value = ''
    return
  }
  imageFile.value = file
  imagePreview.value = file ? URL.createObjectURL(file) : null
}

function resetForm() {
  form.value = { name: '', description: '', price: '', duration_minutes: 30 }
  editingId.value = null
  imageFile.value = null
  imagePreview.value = null
  formError.value = ''
}

function edit(service) {
  editingId.value = service.id
  form.value = {
    name: service.name,
    description: service.description,
    price: service.price,
    duration_minutes: service.duration_minutes,
  }
  imageFile.value = null
  imagePreview.value = service.image_url // show the existing photo until a new one is picked
  formError.value = ''
}

async function submit() {
  formError.value = ''
  saving.value = true
  try {
    const payload = { ...form.value }
    if (imageFile.value) payload.image = imageFile.value

    if (editingId.value) {
      await servicesApi.update(editingId.value, payload)
    } else {
      await servicesApi.create(payload)
    }
    resetForm()
    await load()
  } catch (e) {
    formError.value = extractErrorMessage(e, 'Could not save this style. Please check the details and try again.')
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!confirm('Remove this style from your catalogue?')) return
  try {
    await servicesApi.remove(id)
    await load()
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not remove this style.'))
  }
}

onMounted(load)
</script>

<template>
  <div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
      <h1 class="font-heading text-2xl text-ink mb-4">Your catalogue</h1>

      <p v-if="loading" class="text-ink-soft text-sm">Loading…</p>
      <div v-else-if="!services.length" class="text-ink-soft text-sm">No styles added yet — add your first one to the right.</div>

      <div v-for="s in services" :key="s.id"
        class="bg-white rounded-xl border border-black/5 p-4 mb-3 flex gap-4 items-start">
        <img v-if="s.image_url" :src="s.image_url" :alt="s.name" class="w-20 h-20 rounded-lg object-cover shrink-0" />
        <div v-else class="w-20 h-20 rounded-lg bg-sage-50 flex items-center justify-center shrink-0 text-sage-500 text-xs">
          No photo
        </div>

        <div class="flex-1">
          <p class="font-medium text-ink">{{ s.name }}</p>
          <p class="text-sm text-ink-soft">{{ s.duration_minutes }} min · KES {{ Number(s.price).toLocaleString() }}</p>
          <p v-if="s.description" class="text-sm text-ink-soft mt-1">{{ s.description }}</p>
        </div>

        <div class="flex gap-3 text-sm shrink-0">
          <button @click="edit(s)" class="text-clay-600">Edit</button>
          <button @click="remove(s.id)" class="text-red-500">Remove</button>
        </div>
      </div>
    </div>

    <div>
      <div class="bg-white rounded-xl border border-black/5 p-4 sticky top-20">
        <h3 class="font-medium text-ink mb-3">{{ editingId ? 'Edit style' : 'Add a style' }}</h3>

        <label class="block text-sm mb-1 text-ink-soft">Style photo</label>
        <label class="block mb-3 cursor-pointer">
          <div v-if="imagePreview" class="w-full h-32 rounded-lg overflow-hidden mb-2">
            <img :src="imagePreview" class="w-full h-full object-cover" />
          </div>
          <div v-else class="w-full h-32 rounded-lg border-2 border-dashed border-sage-300 flex items-center justify-center text-sage-500 text-sm mb-2">
            Tap to add a photo
          </div>
          <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
          <span class="text-xs text-clay-600">{{ imagePreview ? 'Change photo' : 'Choose photo' }}</span>
        </label>

        <label class="block text-sm mb-1 text-ink-soft">Name</label>
        <input v-model="form.name" class="w-full mb-3 px-3 py-2 border rounded-lg text-sm" />

        <label class="block text-sm mb-1 text-ink-soft">Description</label>
        <textarea v-model="form.description" rows="2" class="w-full mb-3 px-3 py-2 border rounded-lg text-sm"></textarea>

        <label class="block text-sm mb-1 text-ink-soft">Price (KES)</label>
        <input v-model="form.price" type="number" min="0" class="w-full mb-3 px-3 py-2 border rounded-lg text-sm" />

        <label class="block text-sm mb-1 text-ink-soft">Duration (minutes)</label>
        <input v-model="form.duration_minutes" type="number" min="5" class="w-full mb-4 px-3 py-2 border rounded-lg text-sm" />

        <p v-if="formError" class="text-red-600 text-sm mb-3">{{ formError }}</p>

        <div class="flex gap-2">
          <button v-if="editingId" @click="resetForm" class="flex-1 py-2 rounded-lg border text-sm text-ink-soft">Cancel</button>
          <button :disabled="saving" @click="submit" class="flex-1 py-2 rounded-lg bg-clay-600 text-white text-sm disabled:opacity-50">
            {{ saving ? 'Saving…' : editingId ? 'Save' : 'Add' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
