<script setup>
import { ref, onMounted } from 'vue'
import adminApi from '@/api/admin'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'

const notifications = useNotificationsStore()
const settings = ref(null)
const loading = ref(true)
const saving = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await adminApi.settings()
    settings.value = data
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not load settings.'))
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function togglePayments() {
  const next = !settings.value.payments_enabled
  saving.value = true
  try {
    const { data } = await adminApi.updatePaymentsEnabled(next)
    settings.value = data
    notifications.success(next ? 'Payments are now enabled.' : 'Payments are now disabled — every provider is bookable regardless of subscription.')
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not update this setting.'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-xl">
    <h1 class="font-heading text-2xl text-ink mb-4">Settings</h1>

    <p v-if="loading" class="text-ink-soft text-sm">Loading…</p>

    <div v-else class="bg-white rounded-xl border border-ink/10 p-5">
      <div class="flex justify-between items-start gap-4">
        <div>
          <h2 class="font-medium text-ink mb-1">M-Pesa payments</h2>
          <p class="text-sm text-ink-soft">
            When <strong>enabled</strong>, providers must pay the KES {{ ' ' }}
            <span class="text-ink">monthly subscription fee</span> to be listed and accept bookings.
          </p>
          <p class="text-sm text-ink-soft mt-2">
            When <strong>disabled</strong>, every provider is treated as active regardless of their
            subscription — use this before Daraja is configured, or to pause billing platform-wide.
          </p>
        </div>

        <button
          @click="togglePayments"
          :disabled="saving"
          :class="[
            'shrink-0 relative inline-flex h-7 w-12 items-center rounded-full transition-colors disabled:opacity-50',
            settings.payments_enabled ? 'bg-sage-500' : 'bg-ink/20',
          ]"
          :aria-pressed="settings.payments_enabled"
          aria-label="Toggle payments enabled"
        >
          <span :class="['inline-block h-5 w-5 transform rounded-full bg-white transition-transform', settings.payments_enabled ? 'translate-x-6' : 'translate-x-1']" />
        </button>
      </div>

      <p class="text-sm mt-4" :class="settings.payments_enabled ? 'text-sage-600' : 'text-amber-600'">
        Currently <strong>{{ settings.payments_enabled ? 'enabled' : 'disabled' }}</strong>.
      </p>
    </div>
  </div>
</template>
