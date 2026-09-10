<script setup>
import { ref, onMounted } from 'vue'
import adminApi from '@/api/admin'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'

const notifications = useNotificationsStore()
const providers = ref([])
const unverifiedOnly = ref(false)
const search = ref('')
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const { data } = await adminApi.providers({
      unverified_only: unverifiedOnly.value || undefined,
      search: search.value || undefined,
    })
    providers.value = data.data
  } catch {
    // network/server failures are already toasted globally by the axios interceptor
  } finally {
    loading.value = false
  }
}

async function toggleVerify(provider) {
  try {
    if (provider.is_verified) {
      await adminApi.unverifyProvider(provider.id)
    } else {
      await adminApi.verifyProvider(provider.id)
    }
    await load()
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not update this provider.'))
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="font-heading text-2xl text-ink mb-4">Providers</h1>

    <div class="flex flex-wrap gap-3 mb-5 items-center">
      <input v-model="search" @keyup.enter="load" placeholder="Search business name…"
        class="px-3 py-2 border rounded-lg text-sm flex-1 min-w-[200px]" />
      <label class="flex items-center gap-2 text-sm text-ink-soft px-3 py-2 border rounded-lg bg-white cursor-pointer">
        <input type="checkbox" v-model="unverifiedOnly" @change="load" />
        Unverified only
      </label>
      <button @click="load" class="px-4 py-2 bg-clay-600 text-white rounded-lg text-sm">Search</button>
    </div>

    <p v-if="loading" class="text-ink-soft text-sm">Loading…</p>

    <div v-else class="grid sm:grid-cols-2 gap-3">
      <div v-for="p in providers" :key="p.id" class="bg-white rounded-xl border border-ink/10 p-4">
        <div class="flex justify-between items-start gap-2">
          <div class="min-w-0">
            <p class="font-medium text-ink truncate">{{ p.business_name }}</p>
            <p class="text-xs text-ink-soft capitalize">{{ p.provider_type }} · {{ p.user?.phone }}</p>
          </div>
          <span :class="['text-xs px-2 py-1 rounded-full shrink-0', p.is_verified ? 'bg-sage-500 text-white' : 'bg-amber-500 text-white']">
            {{ p.is_verified ? 'Verified' : 'Unverified' }}
          </span>
        </div>
        <p class="text-xs text-ink-soft mt-2">⭐ {{ Number(p.average_rating).toFixed(1) }} ({{ p.reviews_count }} reviews)</p>
        <button @click="toggleVerify(p)"
          class="mt-3 text-xs px-3 py-1.5 rounded-lg border text-clay-600 border-clay-200 hover:bg-clay-50">
          {{ p.is_verified ? 'Remove verification' : 'Verify provider' }}
        </button>
      </div>
      <p v-if="!providers.length" class="text-sm text-ink-soft">No providers match this filter.</p>
    </div>
  </div>
</template>
