<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { extractErrorMessage } from '@/utils/errors'
import messagesApi from '@/api/messages'
import { getEcho } from '@/echo'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const notifications = useNotificationsStore()

const conversations = ref([])
const thread = ref([])
const draft = ref('')
const sending = ref(false)
const scrollBox = ref(null)
let currentChannelName = null

async function loadConversations() {
  try {
    const { data } = await messagesApi.conversations()
    conversations.value = data
  } catch {
    // toasted globally by the axios interceptor
  }
}

async function scrollToBottom() {
  await nextTick()
  scrollBox.value?.scrollTo({ top: scrollBox.value.scrollHeight })
}

async function loadThread() {
  if (!route.params.userId) {
    thread.value = []
    return
  }
  try {
    const { data } = await messagesApi.thread(route.params.userId)
    thread.value = data.data
    await scrollToBottom()
  } catch {
    notifications.error('Could not load this conversation.')
  }
}

/** Both users compute the same sorted channel name — see routes/channels.php. */
function conversationChannelName(otherUserId) {
  const ids = [auth.user.id, Number(otherUserId)].sort((a, b) => a - b)
  return `conversation.${ids[0]}.${ids[1]}`
}

function subscribeToThread(otherUserId) {
  unsubscribeFromThread()
  if (!otherUserId) return

  currentChannelName = conversationChannelName(otherUserId)
  getEcho()
    ?.private(currentChannelName)
    .listen('.message.sent', (e) => {
      thread.value.push(e)
      scrollToBottom()
      loadConversations() // refresh last-message previews
    })
}

function unsubscribeFromThread() {
  if (currentChannelName) getEcho()?.leave(currentChannelName)
  currentChannelName = null
}

async function send() {
  if (!draft.value.trim() || sending.value) return
  sending.value = true
  const outgoing = draft.value
  try {
    const { data } = await messagesApi.send({ receiver_id: route.params.userId, body: outgoing })
    thread.value.push(data) // optimistic append — the .toOthers() on the backend means we won't get our own echo
    draft.value = ''
    await scrollToBottom()
  } catch (e) {
    // deliberately don't clear draft.value here — the person shouldn't have to retype a failed message
    notifications.error(extractErrorMessage(e, 'Message failed to send. Please try again.'))
  } finally {
    sending.value = false
  }
}

function openConversation(userId) {
  router.push(`/messages/${userId}`)
}

onMounted(() => {
  loadConversations()
  loadThread()
  subscribeToThread(route.params.userId)
})

watch(
  () => route.params.userId,
  (userId) => {
    loadThread()
    subscribeToThread(userId)
  }
)

onBeforeUnmount(unsubscribeFromThread)
</script>

<template>
  <!-- Mobile: list and thread are separate full-width screens, swapped by
       whether a conversation is selected. Desktop (md+): both panes side by side. -->
  <div class="grid md:grid-cols-3 gap-4 h-[calc(100vh-8rem)] md:h-[75vh]">
    <div :class="['bg-white rounded-xl border border-gray-100 overflow-y-auto', route.params.userId ? 'hidden md:block' : 'block']">
      <button v-for="c in conversations" :key="c.other_user_id" @click="openConversation(c.other_user_id)"
        class="w-full text-left px-4 py-3 border-b hover:bg-gray-50 text-sm">
        Conversation #{{ c.other_user_id }}
      </button>
      <p v-if="!conversations.length" class="p-4 text-sm text-gray-400">No conversations yet.</p>
    </div>

    <div :class="['md:col-span-2 bg-white rounded-xl border border-gray-100 flex-col', route.params.userId ? 'flex' : 'hidden md:flex']">
      <div class="md:hidden px-3 py-2 border-b">
        <RouterLink to="/messages" class="text-sm text-clay-600">← Back to conversations</RouterLink>
      </div>

      <div ref="scrollBox" class="flex-1 overflow-y-auto p-4 space-y-2">
        <div v-for="m in thread" :key="m.id"
          :class="['max-w-[85%] sm:max-w-[75%] px-3 py-2 rounded-xl text-sm', m.sender_id === auth.user.id ? 'ml-auto bg-clay-600 text-white' : 'bg-gray-100']">
          {{ m.body }}
        </div>
        <p v-if="!route.params.userId" class="text-gray-400 text-sm hidden md:block">Select a conversation to start chatting.</p>
      </div>

      <form v-if="route.params.userId" @submit.prevent="send" class="p-3 border-t flex gap-2">
        <input v-model="draft" :disabled="sending" placeholder="Type a message…" class="flex-1 px-3 py-2 border rounded-lg text-sm disabled:opacity-50" />
        <button :disabled="sending" class="px-4 py-2 bg-clay-600 text-white rounded-lg text-sm disabled:opacity-50 shrink-0">
          {{ sending ? 'Sending…' : 'Send' }}
        </button>
      </form>
    </div>
  </div>
</template>
