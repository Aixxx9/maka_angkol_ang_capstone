<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const scoreboard = ref(null)
let stopPoll = null

function setupEcho() {
  try {
    if (window.Echo) {
      window.Echo.channel('scoreboard').listen('.ScoreboardUpdated', (e) => {
        scoreboard.value = e.scoreboard
      })
    }
  } catch (e) {}
}

async function fetchCurrent() {
  try {
    const res = await fetch('/api/scoreboard', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    if (res.ok) scoreboard.value = await res.json()
  } catch (e) {}
}

onMounted(() => {
  setupEcho()
  fetchCurrent()
  // Polling fallback every 2s
  stopPoll = setInterval(fetchCurrent, 2000)
})
onBeforeUnmount(() => { if (stopPoll) clearInterval(stopPoll) })
</script>

<template>
  <div v-if="scoreboard && scoreboard.is_active" class="bg-white border rounded-xl shadow-sm p-4 mb-6">
    <div class="text-center text-sm">
      <span class="bg-gray-100 rounded px-2 py-0.5">{{ scoreboard.sport?.name }}</span>
    </div>
    <div class="text-center font-extrabold text-2xl mt-1">{{ scoreboard.match_label || 'MATCH' }}</div>
    <div class="grid grid-cols-3 items-center mt-2">
      <div class="text-center">
        <div class="text-xs font-semibold">{{ scoreboard.left_school?.name }}</div>
        <div class="mt-1 text-4xl font-bold bg-gray-50 rounded-lg inline-block px-4 py-2">{{ scoreboard.left_score }}</div>
      </div>
      <div class="text-center text-gray-500 font-bold">VS</div>
      <div class="text-center">
        <div class="text-xs font-semibold">{{ scoreboard.right_school?.name }}</div>
        <div class="mt-1 text-4xl font-bold bg-gray-50 rounded-lg inline-block px-4 py-2">{{ scoreboard.right_score }}</div>
      </div>
    </div>
  </div>
</template>

