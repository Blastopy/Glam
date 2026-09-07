<script setup>
import { computed, onMounted } from 'vue'
import { useProvidersStore } from '@/stores/providers'
import { useAuthStore } from '@/stores/auth'
import ProviderCard from '@/components/shared/ProviderCard.vue'
import ToolMotif from '@/components/shared/ToolMotif.vue'

const store = useProvidersStore()
const auth = useAuthStore()

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

// Real style photos pulled from whatever's loaded — the hero and trending
// strip are photo-led, so they only show real uploads, never stock imagery.
const allPhotos = computed(() =>
  store.list.flatMap((p) => (p.services || []).filter((s) => s.image_url).map((s) => ({ ...s, provider: p.business_name })))
)
const heroPhotos = computed(() => allPhotos.value.slice(0, 4))
const trendingPhotos = computed(() => allPhotos.value.slice(0, 12))

const motifs = ['scissors', 'comb', 'bottle', 'polish']
const firstName = computed(() => auth.user?.name?.split(' ')[0] || '')
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="grid md:grid-cols-2 gap-10 items-center py-8 md:py-12">
      <div>
        <h1 class="font-heading text-4xl md:text-5xl font-semibold text-ink leading-[1.05] tracking-tight">
          Find your next look{{ firstName ? `, ${firstName}` : '' }}
        </h1>
        <p class="text-ink-soft mt-4 text-base max-w-md">
          Barbers, salons and nail artists near you — browse real work, see honest prices, and book straight from your phone.
        </p>

        <div class="flex flex-wrap gap-3 items-center mt-6">
          <select v-model="store.filters.type" @change="search"
            class="px-4 py-2.5 rounded-full border border-ink/15 text-sm bg-white">
            <option value="">All types</option>
            <option value="barber">Barbers</option>
            <option value="salon">Salons</option>
            <option value="manicurist">Manicurists</option>
          </select>

          <label class="flex items-center gap-2 text-sm text-ink-soft px-4 py-2.5 rounded-full border border-ink/15 bg-white cursor-pointer">
            <input type="checkbox" v-model="store.filters.open_only" @change="search" />
            Open now
          </label>

          <button @click="findNearby"
            class="text-sm px-4 py-2.5 rounded-full bg-clay-600 text-white hover:bg-clay-700 transition-colors">
            Use my location
          </button>
        </div>
      </div>

      <!-- Photo collage: real style photos when available, hand-drawn tool
           motifs otherwise — never a generic stock/gradient placeholder. -->
      <div class="relative h-64 md:h-80 hidden sm:block">
        <template v-if="heroPhotos.length">
          <div v-for="(photo, i) in heroPhotos" :key="photo.id"
            class="absolute w-32 h-40 md:w-36 md:h-44 rounded-2xl overflow-hidden shadow-xl shadow-ink/10 border-4 border-white"
            :style="{
              top: `${[10, 0, 45, 35][i]}%`,
              left: `${[5, 45, 60, 20][i]}%`,
              transform: `rotate(${[-6, 4, -3, 7][i]}deg)`,
              zIndex: i,
            }">
            <img :src="photo.image_url" :alt="photo.name" class="w-full h-full object-cover" />
          </div>
        </template>
        <template v-else>
          <div v-for="(m, i) in motifs" :key="m"
            class="absolute w-28 h-28 md:w-32 md:h-32 rounded-2xl flex items-center justify-center border-4 border-white shadow-lg shadow-ink/10"
            :class="[i % 2 === 0 ? 'bg-amber-50 text-amber-600' : 'bg-sage-50 text-sage-600']"
            :style="{
              top: `${[10, 0, 48, 38][i]}%`,
              left: `${[8, 48, 62, 22][i]}%`,
              transform: `rotate(${[-6, 4, -3, 7][i]}deg)`,
              zIndex: i,
            }">
            <div class="w-10 h-10 p-2">
              <ToolMotif :icon="m" />
            </div>
          </div>
        </template>
      </div>
    </section>

    <!-- Trending styles -->
    <section v-if="trendingPhotos.length" class="mb-10">
      <h2 class="font-heading text-lg text-ink mb-3">Trending styles</h2>
      <div class="flex gap-3 overflow-x-auto scroll-strip pb-2">
        <div v-for="photo in trendingPhotos" :key="photo.id"
          class="relative shrink-0 w-36 h-44 rounded-xl overflow-hidden group">
          <img :src="photo.image_url" :alt="photo.name" class="w-full h-full object-cover" />
          <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink/80 to-transparent p-2.5">
            <p class="text-white text-xs font-medium leading-tight">{{ photo.name }}</p>
            <p class="text-white/70 text-[11px]">{{ photo.provider }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Provider grid -->
    <section>
      <h2 class="font-heading text-lg text-ink mb-3">Near you</h2>

      <p v-if="store.loading" class="text-ink-soft text-sm">Loading providers…</p>
      <p v-else-if="!store.list.length" class="text-ink-soft text-sm">No providers found. Try widening your search.</p>

      <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <ProviderCard v-for="p in store.list" :key="p.id" :provider="p" />
      </div>
    </section>
  </div>
</template>
