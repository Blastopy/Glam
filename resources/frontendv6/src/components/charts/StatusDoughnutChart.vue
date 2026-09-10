<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'

const props = defineProps({
  breakdown: { type: Object, required: true }, // { pending: 3, confirmed: 5, ... }
})

const statusColors = {
  pending: '#E8A33D',
  confirmed: '#7C9473',
  in_progress: '#9DB295',
  completed: '#16241D',
  cancelled: '#C97246',
  no_show: '#B54B2C',
}

const chartData = computed(() => {
  const entries = Object.entries(props.breakdown)
  return {
    labels: entries.map(([status]) => status.replace('_', ' ')),
    datasets: [
      {
        data: entries.map(([, count]) => count),
        backgroundColor: entries.map(([status]) => statusColors[status] || '#9DB295'),
        borderWidth: 2,
        borderColor: '#FBF6EC', // paper — gives each slice a visible seam
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: { legend: { position: 'right', labels: { boxWidth: 8, padding: 10 } } },
}
</script>

<template>
  <div class="h-48">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>
