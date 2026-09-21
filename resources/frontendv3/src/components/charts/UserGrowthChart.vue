<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'

const props = defineProps({
  labels: { type: Array, required: true },
  clients: { type: Array, required: true },
  providers: { type: Array, required: true },
})

function formatLabel(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-KE', { day: 'numeric', month: 'short' })
}

const chartData = computed(() => ({
  labels: props.labels.map(formatLabel),
  datasets: [
    {
      label: 'Clients',
      data: props.clients,
      borderColor: '#7C9473', // sage-500
      backgroundColor: 'transparent',
      tension: 0.35,
      pointRadius: 0,
      pointHoverRadius: 4,
      borderWidth: 2,
    },
    {
      label: 'Providers',
      data: props.providers,
      borderColor: '#E8A33D', // amber-500
      backgroundColor: 'transparent',
      tension: 0.35,
      pointRadius: 0,
      pointHoverRadius: 4,
      borderWidth: 2,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 12 } } },
  scales: {
    x: { grid: { display: false } },
    y: { beginAtZero: true, grid: { color: 'rgba(22,36,29,0.06)' }, ticks: { precision: 0 } },
  },
}
</script>

<template>
  <div class="h-56">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
