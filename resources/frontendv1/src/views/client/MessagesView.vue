<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import messagesApi from '@/api/messages'
import { getEcho } from '@/echo'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const conversations = ref([])
const thread = ref([])
const draft = ref('')
const scrollBox = ref(null)
let currentChannelName = null

async function loadConversations() {
  const { data } = await messagesApi.conversations()
  conversations.value = data
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
  const { data } = await messagesApi.thread(route.params.userId)
  thread.value = data.data
  await scrollToBottom()
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
  if (!draft.value.trim()) return
  const { data } = await messagesApi.send({ receiver_id: route.params.userId, body: draft.value })
  thread.value.push(data) // optimistic append — the .toOthers() on the backend means we won't get our own echo
  draft.value = ''
  await scrollToBottom()
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
  <div class="grid md:grid-cols-3 gap-4 h-[75vh]">
    <div class="bg-white rounded-xl border border-gray-100 overflow-y-auto">
      <button v-for="c in conversations" :key="c.other_user_id" @click="openConversation(c.other_user_id)"
        class="w-full text-left px-4 py-3 border-b hover:bg-gray-50 text-sm">
        Conversation #{{ c.other_user_id }}
      </button>
      <p v-if="!conversations.length" class="p-4 text-sm text-gray-400">No conversations yet.</p>
    </div>

    <div class="md:col-span-2 bg-white rounded-xl border border-gray-100 flex flex-col">
      <div ref="scrollBox" class="flex-1 overflow-y-auto p-4 space-y-2">
        <div v-for="m in thread" :key="m.id"
          :class="['max-w-[75%] px-3 py-2 rounded-xl text-sm', m.sender_id === auth.user.id ? 'ml-auto bg-clay-600 text-white' : 'bg-gray-100']">
          {{ m.body }}
        </div>
        <p v-if="!route.params.userId" class="text-gray-400 text-sm">Select a conversation to start chatting.</p>
      </div>

      <form v-if="route.params.userId" @submit.prevent="send" class="p-3 border-t flex gap-2">
        <input v-model="draft" placeholder="Type a message…" class="flex-1 px-3 py-2 border rounded-lg text-sm" />
        <button class="px-4 py-2 bg-clay-600 text-white rounded-lg text-sm">Send</button>
      </form>
    </div>
  </div>
</template>
