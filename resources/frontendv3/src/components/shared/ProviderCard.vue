<script setup>
import { ref } from 'vue'
import ToolMotif from './ToolMotif.vue'

const props = defineProps({ provider: { type: Object, required: true } })

const motifs = ['scissors', 'comb', 'bottle', 'polish']

// Tracks cover images that failed to actually load (404, missing storage
// symlink, deleted file) so we fall back to the icon tile instead of
// silently showing nothing where a photo was expected.
const brokenUrls = ref(new Set())

function cheapestService(services) {
  if (!services?.length) return null
  return services.reduce((min, s) => (Number(s.price) < Number(min.price) ? s : min), services[0])
}

function coverImage(provider) {
  const url = provider.services?.find((s) => s.image_url)?.image_url || null
  return url && !brokenUrls.value.has(url) ? url : null
}

function markBroken(url) {
  brokenUrls.value.add(url)
}

// Deterministic-ish pick so the same card doesn't flicker between icons on re-render
function fallbackMotif(provider) {
  return motifs[provider.id % motifs.length]
}
</script>

<template>
  <RouterLink :to="`/providers/${provider.id}`"
    class="block bg-white rounded-2xl border border-ink/5 overflow-hidden hover:shadow-lg hover:shadow-ink/5 transition-shadow">
    <div class="relative h-36 bg-sage-50">
      <img v-if="coverImage(provider)" :src="coverImage(provider)" :alt="provider.business_name"
        class="w-full h-full object-cover" @error="markBroken(coverImage(provider))" />
      <div v-else class="w-full h-full flex items-center justify-center text-sage-400">
        <div class="w-10 h-10">
          <ToolMotif :icon="fallbackMotif(provider)" />
        </div>
      </div>

      <span :class="[
        'absolute top-2 right-2 text-xs px-2 py-1 rounded-full font-medium',
        provider.is_open ? 'bg-sage-500 text-white' : 'bg-ink/70 text-white',
      ]">
        {{ provider.is_open ? 'Open' : 'Closed' }}
      </span>

      <span v-if="provider.is_featured" class="absolute top-2 left-2 text-xs px-2 py-1 rounded-full font-medium bg-amber-500 text-white">
        ★ Featured
      </span>
    </div>

    <div class="p-4">
      <h3 class="font-heading font-semibold text-ink leading-tight">{{ provider.business_name }}</h3>
      <p class="text-sm text-ink-soft capitalize">{{ provider.provider_type }}</p>

      <p v-if="provider.distance_km" class="text-xs text-ink-soft/70 mt-1">
        {{ Number(provider.distance_km).toFixed(1) }} km away
      </p>

      <p v-if="provider.offers_home_service" class="text-xs text-clay-600 mt-1">🏠 Home visits available</p>

      <div class="flex justify-between items-center mt-3 text-sm">
        <span class="text-ink-soft">
          ⭐ {{ Number(provider.average_rating || 0).toFixed(1) }} ({{ provider.reviews_count || 0 }})
        </span>
        <span v-if="cheapestService(provider.services)" class="text-clay-600 font-medium">
          From KES {{ Number(cheapestService(provider.services).price).toLocaleString() }}
        </span>
      </div>
    </div>
  </RouterLink>
</template>
