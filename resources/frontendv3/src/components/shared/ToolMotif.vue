<script setup>
defineProps({
  icon: { type: String, default: 'scissors' }, // scissors | comb | bottle | polish
})
</script>

<template>
  <svg v-if="icon === 'scissors'" viewBox="0 0 64 64" fill="none" class="w-full h-full">
    <circle cx="18" cy="46" r="7" stroke="currentColor" stroke-width="2.5" />
    <circle cx="18" cy="18" r="7" stroke="currentColor" stroke-width="2.5" />
    <path d="M24 22L54 50M24 42L54 14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
  </svg>

  <svg v-else-if="icon === 'comb'" viewBox="0 0 64 64" fill="none" class="w-full h-full">
    <rect x="10" y="14" width="44" height="8" rx="2" stroke="currentColor" stroke-width="2.5" />
    <path d="M16 22V50M24 22V44M32 22V50M40 22V44M48 22V50" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
  </svg>

  <svg v-else-if="icon === 'bottle'" viewBox="0 0 64 64" fill="none" class="w-full h-full">
    <path d="M26 10H38V18C38 18 44 24 44 34V50C44 52.2 42.2 54 40 54H24C21.8 54 20 52.2 20 50V34C20 24 26 18 26 18V10Z"
      stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
    <path d="M24 30H40" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
  </svg>

  <svg v-else viewBox="0 0 64 64" fill="none" class="w-full h-full">
    <path d="M27 8H37V16H27V8Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
    <path d="M25 16H39L42 24V52C42 54.2 40.2 56 38 56H26C23.8 56 22 54.2 22 52V24L25 16Z"
      stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
  </svg>
</template>
