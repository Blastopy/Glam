<script setup>
import { onMounted } from 'vue'
import { useProvidersStore } from '@/stores/providers'
import ProviderCard from '@/components/shared/ProviderCard.vue'

const store = useProvidersStore()

async function search() {
  await store.fetchList()
}

async function findNearby() {
  try {
    await store.useMyLocation()
    await store.fetchList()
  } catch {
    alert('Could not get your location — check browser permissions.')
  }
}

onMounted(search)
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 items-center mb-6">
      <select v-model="store.filters.type" @change="search" class="px-3 py-2 border rounded-lg text-sm">
        <option value="">All types</option>
        <option value="barber">Barbers</option>
        <option value="salon">Salons</option>
        <option value="manicurist">Manicurists</option>
      </select>

      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" v-model="store.filters.open_only" @change="search" />
        Open now
      </label>

      <button @click="findNearby" class="text-sm px-3 py-2 border rounded-lg text-brand-600 border-brand-300 hover:bg-brand-50">
        📍 Near me
      </button>
    </div>

    <p v-if="store.loading" class="text-gray-400 text-sm">Loading providers…</p>
    <p v-else-if="!store.list.length" class="text-gray-400 text-sm">No providers found. Try widening your search.</p>

    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <ProviderCard v-for="p in store.list" :key="p.id" :provider="p" />
    </div>
  </div>
</template>
