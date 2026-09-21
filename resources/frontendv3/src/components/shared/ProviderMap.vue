<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  // [{ id, lat, lng, title, subtitle, isYou }]
  markers: { type: Array, default: () => [] },
  center: { type: Array, default: () => [-1.286389, 36.817223] }, // Nairobi — sensible default when nothing else is known
  zoom: { type: Number, default: 12 },
  heightClass: { type: String, default: 'h-64' },
})

const emit = defineEmits(['marker-click'])

const mapEl = ref(null)
let map = null
let markerLayer = null

// Simple pin drawn in the app's own palette instead of Leaflet's default blue
// marker (which also has a well-known broken-image issue under bundlers).
function pinIcon(color) {
  return L.divIcon({
    className: '',
    html: `<svg width="28" height="36" viewBox="0 0 28 36" xmlns="http://www.w3.org/2000/svg">
      <path d="M14 0C6.3 0 0 6.3 0 14c0 10.5 14 22 14 22s14-11.5 14-22C28 6.3 21.7 0 14 0z" fill="${color}"/>
      <circle cx="14" cy="14" r="5.5" fill="white"/>
    </svg>`,
    iconSize: [28, 36],
    iconAnchor: [14, 36],
    popupAnchor: [0, -32],
  })
}

const providerIcon = pinIcon('#B54B2C') // clay-600
const youIcon = pinIcon('#7C9473') // sage-500

function renderMarkers() {
  if (!map) return
  markerLayer.clearLayers()

  const valid = props.markers.filter((m) => Number.isFinite(m.lat) && Number.isFinite(m.lng))

  valid.forEach((m) => {
    const marker = L.marker([m.lat, m.lng], { icon: m.isYou ? youIcon : providerIcon }).addTo(markerLayer)

    if (m.title) {
      marker.bindPopup(
        `<strong>${escapeHtml(m.title)}</strong>${m.subtitle ? `<br><span class="text-xs">${escapeHtml(m.subtitle)}</span>` : ''}`
      )
    }

    if (!m.isYou) {
      marker.on('click', () => emit('marker-click', m.id))
    }
  })

  // Fit bounds to whatever's showing, so a single provider zooms in close and
  // a full discovery list frames everything without manual pan/zoom.
  if (valid.length > 1) {
    map.fitBounds(L.latLngBounds(valid.map((m) => [m.lat, m.lng])), { padding: [40, 40] })
  } else if (valid.length === 1) {
    map.setView([valid[0].lat, valid[0].lng], 15)
  }
}

function escapeHtml(str) {
  const div = document.createElement('div')
  div.textContent = str
  return div.innerHTML
}

onMounted(() => {
  map = L.map(mapEl.value, { scrollWheelZoom: false }).setView(props.center, props.zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19,
  }).addTo(map)

  markerLayer = L.layerGroup().addTo(map)
  renderMarkers()

  // Re-enable scroll-zoom once the user actually interacts, so the page can
  // still scroll normally over the map until then.
  map.once('click', () => map.scrollWheelZoom.enable())
})

watch(() => props.markers, renderMarkers, { deep: true })

onBeforeUnmount(() => {
  map?.remove()
  map = null
})
</script>

<template>
  <div ref="mapEl" :class="[heightClass, 'w-full rounded-xl z-0']"></div>
</template>
