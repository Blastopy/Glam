<script setup>
defineProps({ provider: { type: Object, required: true } })

function cheapestService(services) {
  if (!services?.length) return null
  return services.reduce((min, s) => (Number(s.price) < Number(min.price) ? s : min), services[0])
}
</script>

<template>
  <RouterLink :to="`/providers/${provider.id}`"
    class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition p-4">
    <div class="flex justify-between items-start">
      <div>
        <h3 class="font-semibold">{{ provider.business_name }}</h3>
        <p class="text-sm text-gray-500 capitalize">{{ provider.provider_type }}</p>
      </div>
      <span :class="['text-xs px-2 py-1 rounded-full', provider.is_open ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
        {{ provider.is_open ? 'Open' : 'Closed' }}
      </span>
    </div>

    <p v-if="provider.distance_km" class="text-xs text-gray-400 mt-1">
      {{ Number(provider.distance_km).toFixed(1) }} km away
    </p>

    <div class="flex justify-between items-center mt-3 text-sm">
      <span class="text-gray-500">
        ⭐ {{ Number(provider.average_rating || 0).toFixed(1) }} ({{ provider.reviews_count || 0 }})
      </span>
      <span v-if="cheapestService(provider.services)" class="text-brand-600 font-medium">
        From KES {{ Number(cheapestService(provider.services).price).toLocaleString() }}
      </span>
    </div>
  </RouterLink>
</template>
