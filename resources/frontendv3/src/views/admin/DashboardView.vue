<script setup>
import { ref, onMounted } from 'vue'
import adminApi from '@/api/admin'
import { extractErrorMessage } from '@/utils/errors'
import RevenueLineChart from '@/components/charts/RevenueLineChart.vue'
import BookingsBarChart from '@/components/charts/BookingsBarChart.vue'
import UserGrowthChart from '@/components/charts/UserGrowthChart.vue'
import StatusDoughnutChart from '@/components/charts/StatusDoughnutChart.vue'

const overview = ref(null)
const revenue = ref({ labels: [], values: [] })
const bookings = ref({ labels: [], series: {} })
const growth = ref({ labels: [], clients: [], providers: [] })
const topProviders = ref([])
const rangeDays = ref(30)
const loading = ref(true)
const loadError = ref('')


async function loadAll() {
  loading.value = true
  loadError.value = ''
  try {
    const [ov, rev, book, grow, top] = await Promise.all([
      adminApi.overview(),
      adminApi.revenueSeries(rangeDays.value),
      adminApi.bookingsSeries(rangeDays.value),
      adminApi.userGrowthSeries(rangeDays.value),
      adminApi.topProviders(),
    ])
    overview.value = ov.data
    revenue.value = rev.data
    bookings.value = book.data
    growth.value = grow.data
    topProviders.value = top.data
  } catch (e) {
    loadError.value = extractErrorMessage(e, 'Could not load analytics. Please try again.')
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

function fmtCurrency(n) {
  return `KES ${Number(n || 0).toLocaleString()}`
}
</script>

<template>
  <div>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
      <h1 class="font-heading text-2xl text-ink">Admin overview</h1>
      <select v-model.number="rangeDays" @change="loadAll" class="text-sm px-3 py-2 border rounded-lg bg-white">
        <option :value="7">Last 7 days</option>
        <option :value="30">Last 30 days</option>
        <option :value="90">Last 90 days</option>
      </select>
    </div>

    <p v-if="loading" class="text-ink-soft text-sm">Loading analytics…</p>

    <div v-else-if="loadError" class="text-center py-16">
      <p class="text-ink-soft">{{ loadError }}</p>
      <button @click="loadAll" class="mt-3 text-sm text-clay-600 underline">Try again</button>
    </div>

    <template v-else-if="overview">
      <!-- Headline counters -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-ink/10 p-4">
          <p class="text-xs text-ink-soft">Total revenue</p>
          <p class="font-heading text-2xl text-ink mt-1">{{ fmtCurrency(overview.total_revenue) }}</p>
          <p class="text-xs text-sage-600 mt-1">{{ fmtCurrency(overview.revenue_this_month) }} this month</p>
        </div>
        <div class="bg-white rounded-xl border border-ink/10 p-4">
          <p class="text-xs text-ink-soft">Appointments</p>
          <p class="font-heading text-2xl text-ink mt-1">{{ overview.appointments_total }}</p>
          <p class="text-xs text-ink-soft mt-1">{{ overview.appointments_this_week }} this week</p>
        </div>
        <div class="bg-white rounded-xl border border-ink/10 p-4">
          <p class="text-xs text-ink-soft">Providers</p>
          <p class="font-heading text-2xl text-ink mt-1">{{ overview.providers }}</p>
          <p class="text-xs text-ink-soft mt-1">{{ overview.verified_providers }} verified · {{ overview.open_providers }} open now</p>
        </div>
        <div class="bg-white rounded-xl border border-ink/10 p-4">
          <p class="text-xs text-ink-soft">Clients</p>
          <p class="font-heading text-2xl text-ink mt-1">{{ overview.clients }}</p>
          <p class="text-xs text-ink-soft mt-1">{{ overview.pending_payments }} pending payments</p>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-4 mb-8">
        <div class="lg:col-span-2 bg-white rounded-xl border border-ink/10 p-5">
          <h2 class="font-heading text-base text-ink mb-3">Revenue</h2>
          <RevenueLineChart :labels="revenue.labels" :values="revenue.values" />
        </div>
        <div class="bg-white rounded-xl border border-ink/10 p-5">
          <h2 class="font-heading text-base text-ink mb-3">Appointment mix</h2>
          <StatusDoughnutChart :breakdown="overview.appointment_status_breakdown" />
        </div>
      </div>

      <div class="grid lg:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-ink/10 p-5">
          <h2 class="font-heading text-base text-ink mb-3">Bookings by status</h2>
          <BookingsBarChart :labels="bookings.labels" :series="bookings.series" />
        </div>
        <div class="bg-white rounded-xl border border-ink/10 p-5">
          <h2 class="font-heading text-base text-ink mb-3">Signups</h2>
          <UserGrowthChart :labels="growth.labels" :clients="growth.clients" :providers="growth.providers" />
        </div>
      </div>

      <div class="bg-white rounded-xl border border-ink/10 p-5">
        <h2 class="font-heading text-base text-ink mb-3">Top providers by completed bookings</h2>
        <div v-if="!topProviders.length" class="text-ink-soft text-sm">No completed bookings yet.</div>
        <div v-for="(p, i) in topProviders" :key="p.id" class="flex items-center gap-3 py-2 border-b border-ink/5 last:border-0">
          <span class="text-ink-soft text-sm w-5 shrink-0">{{ i + 1 }}</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-ink truncate">{{ p.business_name }}</p>
            <p class="text-xs text-ink-soft capitalize">{{ p.provider_type }} · ⭐ {{ Number(p.average_rating).toFixed(1) }}</p>
          </div>
          <span class="text-sm font-medium text-clay-600 shrink-0">{{ p.completed_count }} bookings</span>
        </div>
      </div>
    </template>
  </div>
</template>
