<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Chart from 'chart.js/auto' // ✅ simpler import, no vue-chartjs

const props = defineProps({
  sports: { type: Array, default: () => [] },
  sport: { type: String, default: null },
  players: { type: Array, default: () => [] },
  query: { type: String, default: '' },
})

// ==========================
// SEARCH + FILTER
// ==========================
const sportSearch = ref('')
const playerQuery = ref(props.query || '')

const filteredSports = computed(() => {
  const term = sportSearch.value.trim().toLowerCase()
  if (!term) return props.sports
  return props.sports.filter(s => (s.name || '').toLowerCase().includes(term))
})

// ==========================
// HELPERS
// ==========================
const imgSrc = (p) => {
  const path = p?.avatar_path
  if (!path) return '/images/user.png'
  return path.startsWith('/storage') ? path : `/storage/${path}`
}
const fullName = (p) => [p?.first_name, p?.last_name].filter(Boolean).join(' ')
const n1 = (n) => (typeof n === 'number' ? n.toFixed(1) : '—')
const pct = (n) => (typeof n === 'number' ? `${n.toFixed(1)}%` : '—')

// ==========================
// SCROLL + ROUTE
// ==========================
const playersSectionEl = ref(null)
function scrollToPlayers() {
  if (playersSectionEl.value) {
    playersSectionEl.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}
watch(() => props.sport, (val) => { if (val) nextTick(scrollToPlayers) })
onMounted(() => { if (props.sport) nextTick(scrollToPlayers) })

function chooseSport(slug) {
  router.get('/athletes', { sport: slug }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// ==========================
// FILTERED PLAYERS + METRICS
// ==========================
const filteredPlayers = computed(() => {
  const term = playerQuery.value.trim().toLowerCase()
  if (!term) return props.players
  return props.players.filter(p => {
    const name = fullName(p).toLowerCase()
    const team = (p.team_name ?? '').toLowerCase()
    const num = String(p.number ?? '')
    return name.includes(term) || team.includes(term) || num.includes(term)
  })
})

const totalPlayers = computed(() => filteredPlayers.value.length)
const avgPoints = computed(() => {
  const vals = filteredPlayers.value.map(p => Number(p.ppg)).filter(v => !isNaN(v))
  return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0
})
const topScorer = computed(() =>
  [...filteredPlayers.value].sort((a, b) => (b.ppg ?? 0) - (a.ppg ?? 0))[0] || null
)

// ==========================
// EDIT + DELETE MODALS
// ==========================
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedPlayer = ref(null)
const preview = ref(null)

const editForm = useForm({
  first_name: '',
  last_name: '',
  number: '',
  position: '',
  team_id: '',
  avatar: null,
})

function openEditModal(p) {
  selectedPlayer.value = p
  editForm.first_name = p.first_name
  editForm.last_name = p.last_name
  editForm.number = p.number
  editForm.position = p.position
  editForm.team_id = p.team_id
  preview.value = p.avatar_path ? imgSrc(p) : null
  showEditModal.value = true
}

function saveEdit() {
  editForm.post(`/athletes/${selectedPlayer.value.id}?_method=PUT`, {
    forceFormData: true,
    onSuccess: () => {
      showEditModal.value = false
      router.reload({ only: ['players'] })
    },
  })
}

function onPickAvatar(e) {
  const f = e.target.files?.[0]
  if (f) {
    editForm.avatar = f
    preview.value = URL.createObjectURL(f)
  }
}

function openDeleteModal(p) {
  selectedPlayer.value = p
  showDeleteModal.value = true
}
function confirmDelete() {
  router.delete(`/athletes/${selectedPlayer.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
      router.reload({ only: ['players'] })
    },
  })
}

// ==========================
// PLAYER RECORD MODAL + CHART
// ==========================
const showRecordModal = ref(false)
const recordForm = useForm({
  points: '',
  rebounds: '',
  assists: '',
  fg_percent: '',
  game_date: '',
})

const chartCanvas = ref(null)
let chartInstance = null

function openRecordModal(player) {
  selectedPlayer.value = player
  showRecordModal.value = true
  nextTick(renderChart)
}

function renderChart() {
  if (!chartCanvas.value || !selectedPlayer.value?.game_stats) return

  const stats = selectedPlayer.value.game_stats
  const labels = stats.map(g => g.game_date)
  const dataSets = [
    { label: 'Points', data: stats.map(g => g.points), borderColor: '#0b66ff', tension: 0.3 },
    { label: 'Rebounds', data: stats.map(g => g.rebounds), borderColor: '#10b981', tension: 0.3 },
    { label: 'Assists', data: stats.map(g => g.assists), borderColor: '#f59e0b', tension: 0.3 }
  ]

  if (chartInstance) chartInstance.destroy()

  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: { labels, datasets: dataSets },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  })
}

function saveGameRecord() {
  recordForm.post(`/athletes/${selectedPlayer.value.id}/game-stats`, {
    onSuccess: () => {
      recordForm.reset()
      router.reload({ only: ['players'] })
    },
  })
}
</script>

<template>
  <AppLayout>
    <section class="max-w-[1200px] mx-auto px-4 py-6">
      <!-- HEADER -->
      <div class="flex items-center gap-3">
        <h1 class="text-3xl font-bold tracking-tight text-neutral-900">Athletes</h1>
        <div class="ml-auto" />
        <Link
          href="/athletes/create"
          class="inline-flex items-center gap-2 rounded-lg bg-[#0b66ff] px-4 py-2 text-white text-sm font-semibold shadow-sm hover:bg-[#0856d6]">
          <span class="text-base leading-none">＋</span>
          Add Player
        </Link>
      </div>

      <!-- SPORTS FILTER -->
      <div class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div class="md:col-span-6">
            <label class="text-xs font-medium text-neutral-600">Search sports</label>
            <div class="relative mt-1">
              <input v-model="sportSearch" type="text" placeholder="Find a sport"
                class="w-full rounded-lg border border-neutral-300 bg-white/90 px-3 py-2 pl-10 shadow-sm focus:border-[#0b66ff]">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" viewBox="0 0 24 24"
                fill="currentColor">
                <path
                  d="M10 18a8 8 0 1 1 6.32-3.09l4.39 4.39-1.41 1.41-4.39-4.39A7.98 7.98 0 0 1 10 18Z" />
              </svg>
            </div>
          </div>

          <div v-if="sport" class="md:col-span-6">
            <label class="text-xs font-medium text-neutral-600">Search players</label>
            <input v-model="playerQuery" type="text" placeholder="Search by name or team"
              class="mt-1 w-full rounded-lg border border-neutral-300 bg-white/90 px-3 py-2 shadow-sm focus:border-[#0b66ff]">
          </div>
        </div>

        <!-- SPORTS GRID -->
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <button v-for="s in filteredSports" :key="s.slug" @click="chooseSport(s.slug)"
            class="group rounded-xl border border-neutral-200 bg-white/95 p-4 text-left shadow-sm hover:shadow-md transition"
            :class="sport === s.slug ? 'ring-2 ring-[#0b66ff]' : ''">
            <div
              class="h-12 w-12 rounded-full bg-neutral-100 grid place-items-center overflow-hidden shadow-sm ring-1 ring-white">
              <img v-if="s.icon_path" :src="s.icon_path.startsWith('/storage') ? s.icon_path : `/storage/${s.icon_path}`"
                class="h-full w-full object-cover" />
              <span v-else class="text-sky-700 font-bold">{{ s.name[0].toUpperCase() }}</span>
            </div>
            <div class="mt-3 text-[13px] font-semibold text-neutral-800 group-hover:text-[#0b66ff]">{{ s.name }}</div>
          </button>
        </div>
      </div>

      <!-- PLAYERS GRID -->
      <div v-if="sport" ref="playersSectionEl" class="mt-8 scroll-mt-24">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Selected Sport</div>
            <div class="mt-1 text-base font-semibold capitalize text-neutral-900">{{ sport.replaceAll('-', ' ') }}</div>
          </div>
          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Total Players</div>
            <div class="mt-1 text-2xl font-extrabold">{{ totalPlayers }}</div>
          </div>
          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Avg Points</div>
            <div class="mt-1 text-2xl font-extrabold">{{ n1(avgPoints) }}</div>
          </div>
          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Top Scorer</div>
            <div class="mt-1 font-semibold truncate text-neutral-900">{{ topScorer ? fullName(topScorer) : '—' }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          <div v-for="p in filteredPlayers" :key="p.id"
            class="rounded-xl border border-neutral-200 bg-white shadow-sm hover:-translate-y-0.5 hover:shadow-md transition cursor-pointer"
            @click="openRecordModal(p)">
            <div class="flex items-start gap-3 p-4">
              <img :src="imgSrc(p)" :alt="fullName(p)"
                class="h-12 w-12 rounded-full object-cover ring-2 ring-white shadow-sm" />
              <div>
                <div class="font-semibold text-neutral-900 truncate">{{ fullName(p) }}</div>
                <div class="text-xs text-neutral-500">{{ p.team_name || '—' }}</div>
              </div>
            </div>

            <div class="px-4 pb-2 grid grid-cols-4 gap-2 text-center">
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">PPG</div>
                <div class="text-lg font-extrabold">{{ n1(p.ppg) }}</div>
              </div>
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">RPG</div>
                <div class="text-lg font-extrabold">{{ n1(p.rpg) }}</div>
              </div>
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">APG</div>
                <div class="text-lg font-extrabold">{{ n1(p.apg) }}</div>
              </div>
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">FG%</div>
                <div class="text-lg font-extrabold">{{ pct(p.fg) }}</div>
              </div>
            </div>

            <div class="flex justify-center gap-4 pb-4">
              <button @click.stop="openEditModal(p)" class="text-xs text-blue-600 hover:underline">Edit</button>
              <button @click.stop="openDeleteModal(p)" class="text-xs text-red-600 hover:underline">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- RECORD MODAL -->
    <div v-if="showRecordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl overflow-y-auto max-h-[90vh]">
        <h2 class="text-lg font-semibold mb-3">{{ fullName(selectedPlayer) }} – Full Record</h2>

        <div class="grid grid-cols-4 gap-4 text-center mb-4">
          <div class="bg-neutral-50 p-3 rounded"><div class="text-xs text-neutral-500">PPG</div><div class="text-xl font-bold">{{ selectedPlayer.ppg ?? '—' }}</div></div>
          <div class="bg-neutral-50 p-3 rounded"><div class="text-xs text-neutral-500">RPG</div><div class="text-xl font-bold">{{ selectedPlayer.rpg ?? '—' }}</div></div>
          <div class="bg-neutral-50 p-3 rounded"><div class="text-xs text-neutral-500">APG</div><div class="text-xl font-bold">{{ selectedPlayer.apg ?? '—' }}</div></div>
          <div class="bg-neutral-50 p-3 rounded"><div class="text-xs text-neutral-500">FG%</div><div class="text-xl font-bold">{{ selectedPlayer.fg ?? '—' }}</div></div>
        </div>

        <form @submit.prevent="saveGameRecord" class="grid grid-cols-5 gap-2 mb-6">
          <input v-model="recordForm.points" type="number" placeholder="Points" class="border rounded px-2 py-1 text-sm" />
          <input v-model="recordForm.rebounds" type="number" placeholder="Rebounds" class="border rounded px-2 py-1 text-sm" />
          <input v-model="recordForm.assists" type="number" placeholder="Assists" class="border rounded px-2 py-1 text-sm" />
          <input v-model="recordForm.fg_percent" type="number" placeholder="FG%" class="border rounded px-2 py-1 text-sm" />
          <input v-model="recordForm.game_date" type="date" class="border rounded px-2 py-1 text-sm" />
          <button type="submit" class="col-span-5 bg-[#0b66ff] text-white rounded py-1 mt-2 text-sm">Add Record</button>
        </form>

        <!-- ✅ Chart.js Canvas -->
        <canvas ref="chartCanvas" class="h-60 w-full"></canvas>

        <div class="flex justify-end mt-4">
          <button @click="showRecordModal=false" class="px-3 py-1 border rounded">Close</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
