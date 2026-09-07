<script setup>
import { ref, onMounted } from 'vue'
import adminApi from '@/api/admin'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'

const notifications = useNotificationsStore()
const users = ref([])
const roleFilter = ref('')
const search = ref('')
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const { data } = await adminApi.users({
      role: roleFilter.value || undefined,
      search: search.value || undefined,
    })
    users.value = data.data
  } catch {
    // network/server failures are already toasted globally by the axios interceptor
  } finally {
    loading.value = false
  }
}

async function toggleSuspend(user) {
  try {
    if (user.deleted_at) {
      await adminApi.reactivateUser(user.id)
    } else {
      if (!confirm(`Suspend ${user.name}'s account?`)) return
      await adminApi.suspendUser(user.id)
    }
    await load()
  } catch (e) {
    notifications.error(extractErrorMessage(e, 'Could not update this account.'))
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="font-heading text-2xl text-ink mb-4">Users</h1>

    <div class="flex flex-wrap gap-3 mb-5">
      <select v-model="roleFilter" @change="load" class="px-3 py-2 border rounded-lg text-sm bg-white">
        <option value="">All roles</option>
        <option value="client">Clients</option>
        <option value="provider">Providers</option>
        <option value="admin">Admins</option>
      </select>
      <input v-model="search" @keyup.enter="load" placeholder="Search name, email, or phone…"
        class="px-3 py-2 border rounded-lg text-sm flex-1 min-w-[200px]" />
      <button @click="load" class="px-4 py-2 bg-clay-600 text-white rounded-lg text-sm">Search</button>
    </div>

    <p v-if="loading" class="text-ink-soft text-sm">Loading…</p>

    <div v-else class="bg-white rounded-xl border border-ink/10 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-sage-50 text-ink-soft text-left">
          <tr>
            <th class="px-4 py-2.5 font-medium">Name</th>
            <th class="px-4 py-2.5 font-medium">Contact</th>
            <th class="px-4 py-2.5 font-medium">Role</th>
            <th class="px-4 py-2.5 font-medium">Status</th>
            <th class="px-4 py-2.5 font-medium"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="border-t border-ink/5">
            <td class="px-4 py-2.5 text-ink">{{ u.name }}</td>
            <td class="px-4 py-2.5 text-ink-soft">{{ u.email }}<br /><span class="text-xs">{{ u.phone }}</span></td>
            <td class="px-4 py-2.5 capitalize text-ink-soft">{{ u.role }}</td>
            <td class="px-4 py-2.5">
              <span :class="['text-xs px-2 py-1 rounded-full', u.deleted_at ? 'bg-ink/70 text-white' : 'bg-sage-500 text-white']">
                {{ u.deleted_at ? 'Suspended' : 'Active' }}
              </span>
            </td>
            <td class="px-4 py-2.5 text-right">
              <button v-if="u.role !== 'admin'" @click="toggleSuspend(u)"
                :class="['text-xs px-3 py-1.5 rounded-lg border', u.deleted_at ? 'text-sage-600 border-sage-300' : 'text-red-600 border-red-200']">
                {{ u.deleted_at ? 'Reactivate' : 'Suspend' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!users.length" class="p-4 text-sm text-ink-soft">No users match this filter.</p>
    </div>
  </div>
</template>
