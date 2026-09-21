<script setup>
import { ref } from 'vue'
import appointmentsApi from '@/api/appointments'
import { extractErrorMessage } from '@/utils/errors'

const props = defineProps({
  appointmentId: { type: Number, required: true },
  providerName: { type: String, default: '' },
})
const emit = defineEmits(['submitted', 'close'])

const rating = ref(0)
const hoverRating = ref(0)
const comment = ref('')
const submitting = ref(false)
const error = ref('')

async function submit() {
  if (!rating.value) {
    error.value = 'Please choose a star rating.'
    return
  }
  error.value = ''
  submitting.value = true
  try {
    const { data } = await appointmentsApi.review(props.appointmentId, {
      rating: rating.value,
      comment: comment.value || null,
    })
    emit('submitted', data)
  } catch (e) {
    error.value = extractErrorMessage(e, 'Could not submit your review. Please try again.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-40 px-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm">
      <h3 class="font-heading text-lg text-ink mb-1">Rate your visit</h3>
      <p class="text-sm text-ink-soft mb-4">{{ providerName || 'How was your appointment?' }}</p>

      <div class="flex items-center justify-center gap-1 mb-4">
        <button
          v-for="star in 5"
          :key="star"
          type="button"
          @click="rating = star"
          @mouseenter="hoverRating = star"
          @mouseleave="hoverRating = 0"
          class="text-3xl leading-none transition-colors"
          :class="(hoverRating || rating) >= star ? 'text-amber-500' : 'text-ink/15'"
          :aria-label="`${star} star${star === 1 ? '' : 's'}`"
        >
          ★
        </button>
      </div>

      <textarea v-model="comment" rows="3" placeholder="Anything you'd like to share (optional)"
        class="w-full mb-3 px-3 py-2 border rounded-lg text-sm"></textarea>

      <p v-if="error" class="text-red-600 text-sm mb-3">{{ error }}</p>

      <div class="flex gap-2">
        <button @click="$emit('close')" class="flex-1 py-2 rounded-lg border text-ink-soft text-sm">Cancel</button>
        <button :disabled="submitting" @click="submit" class="flex-1 py-2 rounded-lg bg-clay-600 text-white text-sm disabled:opacity-50">
          {{ submitting ? 'Submitting…' : 'Submit review' }}
        </button>
      </div>
    </div>
  </div>
</template>
