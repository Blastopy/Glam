import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

// Shared visual defaults so every chart matches the app's palette without
// each component having to repeat font/color config.
ChartJS.defaults.font.family = '"Work Sans", sans-serif'
ChartJS.defaults.color = '#4B5A50' // ink-soft
ChartJS.defaults.plugins.legend.labels.usePointStyle = true
