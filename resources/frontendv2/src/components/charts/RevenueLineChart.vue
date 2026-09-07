<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'

const props = defineProps({
  labels: { type: Array, required: true },
  values: { type: Array, required: true },
})

function formatLabel(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-KE', { day: 'numeric', month: 'short' })
}

const chartData = computed(() => ({
  labels: props.labels.map(formatLabel),
  datasets: [
    {
      label: 'Revenue (KES)',
      data: props.values,
      borderColor: '#B54B2C', // clay-500
      backgroundColor: 'rgba(181, 75, 44, 0.08)',
      tension: 0.35,
      fill: true,
      pointRadius: 0,
      pointHoverRadius: 4,
      borderWidth: 2,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false } },
    y: { beginAtZero: true, grid: { color: 'rgba(22,36,29,0.06)' } },
  },
}
</script>

<template>
  <div class="h-56">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
