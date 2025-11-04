<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  schools: { type: Array, default: () => [] },
  sports: { type: Array, default: () => [] },
  scoreboard: { type: Object, default: null },
})

const form = useForm({
  left_school_id: props.scoreboard?.left_school?.id || (props.schools[0]?.id || ''),
  right_school_id: props.scoreboard?.right_school?.id || (props.schools[1]?.id || ''),
  sport_id: props.scoreboard?.sport?.id || (props.sports[0]?.id || ''),
  match_label: props.scoreboard?.match_label || 'MATCH',
})

const score = ref({
  left: props.scoreboard?.left_score ?? 0,
  right: props.scoreboard?.right_score ?? 0,
  active: props.scoreboard?.is_active ?? false,
})

function start() {
  form.post('/live-scoring/start', {
    onSuccess: () => {
      score.value.left = 0
      score.value.right = 0
      score.value.active = true
    },
  })
}

async function bump(side, delta) {
  try {
    const { data } = await window.axios.post('/live-scoring/score', { side, delta })
    if (data && data.scoreboard) {
      score.value.left = data.scoreboard.left_score
      score.value.right = data.scoreboard.right_score
    }
  } catch (e) {}
}

function resetScores() {
  window.axios.post('/live-scoring/reset').then(() => { score.value.left = 0; score.value.right = 0 })
}
async function hideBoard() {
  try {
    const { data } = await window.axios.post('/live-scoring/hide')
    if (data && typeof data.active !== 'undefined') {
      score.value.active = !!data.active
      if (data.scoreboard) {
        score.value.left = data.scoreboard.left_score
        score.value.right = data.scoreboard.right_score
      }
    }
  } catch (e) {}
}
</script>

<template>
  <AppLayout title="Live Scoring">
    <div class="max-w-4xl mx-auto w-full px-4 py-8">
      <h1 class="text-2xl font-bold mb-6">Manual Live Scoring</h1>

      <!-- Setup Row -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium mb-1">Left School</label>
          <select v-model="form.left_school_id" class="w-full border rounded px-3 py-2">
            <option v-for="s in props.schools" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Sport</label>
          <select v-model="form.sport_id" class="w-full border rounded px-3 py-2">
            <option v-for="sp in props.sports" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Right School</label>
          <select v-model="form.right_school_id" class="w-full border rounded px-3 py-2">
            <option v-for="s in props.schools" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium mb-1">Match Label</label>
        <input v-model="form.match_label" class="w-full border rounded px-3 py-2" placeholder="e.g. MATCH 5" />
      </div>

      <div class="flex items-center gap-3 mb-6">
        <button @click="start" class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold">Start / Update</button>
        <button @click="resetScores" class="px-4 py-2 rounded bg-gray-100 hover:bg-gray-200">Reset</button>
        <button @click="hideBoard" class="px-4 py-2 rounded bg-red-50 hover:bg-red-100 text-red-700">{{ score.active ? 'Hide' : 'Show' }}</button>
        <div class="text-sm" :class="score.active ? 'text-green-700' : 'text-gray-500'">
          {{ score.active ? 'Active' : 'Hidden' }}
        </div>
      </div>

      <!-- Scoring Panel (reference-style) -->
      <div class="bg-white rounded-lg border shadow-sm p-6">
        <div class="grid grid-cols-3 items-center">
          <div class="flex flex-col items-center">
            <button @click="bump('left', +1)" class="p-2 hover:bg-gray-100 rounded">
              ▲
            </button>
            <div class="text-4xl font-bold bg-gray-50 rounded-lg w-20 h-20 grid place-items-center my-2">{{ score.left }}</div>
            <button @click="bump('left', -1)" class="p-2 hover:bg-gray-100 rounded">▼</button>
          </div>
          <div class="text-center">
            <div class="text-sm bg-gray-100 rounded px-2 py-1 inline-block">{{ (props.sports.find(s=>s.id===form.sport_id)?.name) || 'Sport' }}</div>
            <div class="text-3xl font-extrabold mt-2">{{ form.match_label || 'MATCH' }}</div>
          </div>
          <div class="flex flex-col items-center">
            <button @click="bump('right', +1)" class="p-2 hover:bg-gray-100 rounded">▲</button>
            <div class="text-4xl font-bold bg-gray-50 rounded-lg w-20 h-20 grid place-items-center my-2">{{ score.right }}</div>
            <button @click="bump('right', -1)" class="p-2 hover:bg-gray-100 rounded">▼</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
