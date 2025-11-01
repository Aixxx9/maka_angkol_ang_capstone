<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Chart from 'chart.js/auto'

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

// School filter helpers
const schoolFilter = ref('')
const availableSchools = computed(() => {
  const seen = new Map()
  for (const p of props.players || []) {
    const id = p.school_id ?? p.school?.id
    const name = p.school_name ?? p.school?.name
    if (id && name && !seen.has(id)) {
      seen.set(id, { id: String(id), name })
    }
  }
  return Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name))
})

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
watch(() => props.sport, () => { schoolFilter.value = '' })

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
  let list = props.players || []

  if (schoolFilter.value) {
    list = list.filter(p => String(p.school_id ?? p.school?.id) === String(schoolFilter.value))
  }

  if (!term) return list
  return list.filter(p => {
    const name = fullName(p).toLowerCase()
    const team = (p.team_name ?? '').toLowerCase()
    const num = String(p.number ?? '')
    const school = (p.school_name ?? p.school?.name ?? '').toLowerCase()
    return name.includes(term) || team.includes(term) || num.includes(term) || school.includes(term)
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

// Chart grouping selector and helpers
const timeRange = ref('all') // all | weekly | monthly | yearly
function pad2(n) { return String(n).padStart(2, '0') }
function startOfISOWeek(date) {
  const d = new Date(date)
  const day = d.getDay() || 7
  if (day !== 1) d.setDate(d.getDate() - (day - 1))
  d.setHours(0, 0, 0, 0)
  return d
}
function getPeriodMeta(date, mode) {
  const d = new Date(date)
  if (mode === 'weekly') {
    const start = startOfISOWeek(d)
    // ISO week number approximation
    const tmp = new Date(start)
    tmp.setDate(tmp.getDate() + 3) // Thursday
    const yearStart = new Date(tmp.getFullYear(), 0, 4)
    const week = 1 + Math.round(((tmp - yearStart) / 86400000 - 3 + ((yearStart.getDay() + 6) % 7)) / 7)
    return { key: `${start.getFullYear()}-W${pad2(week)}`, label: `${start.getFullYear()}-W${pad2(week)}`, sortDate: start }
  }
  if (mode === 'monthly') {
    const first = new Date(d.getFullYear(), d.getMonth(), 1)
    return { key: `${first.getFullYear()}-${pad2(first.getMonth()+1)}`, label: `${first.getFullYear()}-${pad2(first.getMonth()+1)}`, sortDate: first }
  }
  if (mode === 'yearly') {
    const first = new Date(d.getFullYear(), 0, 1)
    return { key: String(first.getFullYear()), label: String(first.getFullYear()), sortDate: first }
  }
  const day = new Date(d.getFullYear(), d.getMonth(), d.getDate())
  const label = `${day.getFullYear()}-${pad2(day.getMonth()+1)}-${pad2(day.getDate())}`
  return { key: label, label, sortDate: day }
}

function openRecordModal(player) {
  selectedPlayer.value = player
  showRecordModal.value = true
  nextTick(renderChart)
}

function renderChart() {
  if (!chartCanvas.value || !selectedPlayer.value?.game_stats) return

  const stats = [...selectedPlayer.value.game_stats]
    .filter(s => !!s.game_date)
    .sort((a, b) => new Date(a.game_date) - new Date(b.game_date))

  const grouped = new Map()
  for (const s of stats) {
    const { key, label, sortDate } = getPeriodMeta(s.game_date, timeRange.value)
    if (!grouped.has(key)) grouped.set(key, { label, sortDate, points: 0, rebounds: 0, assists: 0, fgSum: 0, count: 0 })
    const g = grouped.get(key)
    g.points += Number(s.points || 0)
    g.rebounds += Number(s.rebounds || 0)
    g.assists += Number(s.assists || 0)
    if (s.fg_percent != null) g.fgSum += Number(s.fg_percent)
    g.count += 1
  }

  const rows = [...grouped.values()].sort((a, b) => a.sortDate - b.sortDate)
  const labels = rows.map(r => r.label)
  const points = rows.map(r => r.points)
  const rebounds = rows.map(r => r.rebounds)
  const assists = rows.map(r => r.assists)

  if (chartInstance) chartInstance.destroy()

  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Points', data: points, borderColor: '#0b66ff', backgroundColor: '#0b66ff22', tension: 0.3 },
        { label: 'Rebounds', data: rebounds, borderColor: '#10b981', backgroundColor: '#10b98122', tension: 0.3 },
        { label: 'Assists', data: assists, borderColor: '#f59e0b', backgroundColor: '#f59e0b22', tension: 0.3 },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
      interaction: { mode: 'nearest', intersect: false },
      scales: {
        x: { title: { display: true, text: timeRange.value === 'all' ? 'Game Date' : 'Period' }, ticks: { autoSkip: true, maxRotation: 0 } },
        y: { beginAtZero: true, title: { display: true, text: 'Stat Value' } },
      },
    },
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

function clearRecordForm() {
  recordForm.reset()
}

const recentRecords = computed(() => {
  const stats = selectedPlayer.value?.game_stats || []
  return [...stats]
    .sort((a, b) => new Date(b.game_date) - new Date(a.game_date))
    .slice(0, 8)
})

function clearMostRecentRecord() {
  const r = recentRecords.value[0]
  if (!r) return
  router.delete(`/athletes/${selectedPlayer.value.id}/game-stats/${r.id}`, {
    onSuccess: () => {
      router.reload({ only: ['players'] })
    },
    onError: () => {
      alert('Failed to clear the recent record.')
    }
  })
}

watch(() => props.players, async (list) => {
  if (!showRecordModal.value || !selectedPlayer.value) return
  const updated = list.find(p => p.id === selectedPlayer.value.id)
  if (updated) {
    selectedPlayer.value = updated
    await nextTick()
    renderChart()
  }
})

// Update chart when grouping changes
watch(timeRange, async () => {
  await nextTick()
  renderChart()
})
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

          <div v-if="sport" class="md:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-medium text-neutral-600">Search players</label>
              <input v-model="playerQuery" type="text" placeholder="Search by name, team, or number"
                class="mt-1 w-full rounded-lg border border-neutral-300 bg-white/90 px-3 py-2 shadow-sm focus:border-[#0b66ff]">
            </div>
            <div>
              <label class="text-xs font-medium text-neutral-600">Filter by school</label>
              <select v-model="schoolFilter" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 shadow-sm focus:border-[#0b66ff]">
                <option value="">All schools</option>
                <option v-for="s in availableSchools" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
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
              <button @click.stop="openEditModal(p)" class="text-xs text-blue-600 hover:underline opacity-80 hover:opacity-100">Edit</button>
              <button @click.stop="openDeleteModal(p)" class="text-xs text-red-600 hover:underline opacity-80 hover:opacity-100">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ✅ UPDATED RECORD MODAL -->
    <div v-if="showRecordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden animate-fade-in">
        <div class="bg-[#0b66ff] text-white p-5">
          <h2 class="text-xl font-bold">{{ fullName(selectedPlayer) }} — {{ selectedPlayer.team_name || 'Team' }}</h2>
          <p class="text-sm opacity-90">School: {{ selectedPlayer.school_name || selectedPlayer.school?.name || '—' }}</p>
        </div>

        <div class="p-5 space-y-5 overflow-y-auto max-h-[75vh]">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-xl bg-neutral-50 border p-3 text-center shadow-sm">
              <div class="text-xs text-neutral-500">PPG</div>
              <div class="text-2xl font-extrabold text-neutral-900">{{ selectedPlayer.ppg ?? '—' }}</div>
            </div>
            <div class="rounded-xl bg-neutral-50 border p-3 text-center shadow-sm">
              <div class="text-xs text-neutral-500">RPG</div>
              <div class="text-2xl font-extrabold text-neutral-900">{{ selectedPlayer.rpg ?? '—' }}</div>
            </div>
            <div class="rounded-xl bg-neutral-50 border p-3 text-center shadow-sm">
              <div class="text-xs text-neutral-500">APG</div>
              <div class="text-2xl font-extrabold text-neutral-900">{{ selectedPlayer.apg ?? '—' }}</div>
            </div>
            <div class="rounded-xl bg-neutral-50 border p-3 text-center shadow-sm">
              <div class="text-xs text-neutral-500">FG%</div>
              <div class="text-2xl font-extrabold text-neutral-900">{{ selectedPlayer.fg ?? '—' }}</div>
            </div>
          </div>

          <div class="bg-neutral-50 rounded-xl border p-5 shadow-inner">
            <h3 class="font-semibold text-neutral-800 mb-3">Add / Update Game Record</h3>
            <form @submit.prevent="saveGameRecord" class="grid grid-cols-2 sm:grid-cols-5 gap-3">
              <input v-model="recordForm.points" type="number" placeholder="Points" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0b66ff]" />
              <input v-model="recordForm.rebounds" type="number" placeholder="Rebounds" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0b66ff]" />
              <input v-model="recordForm.assists" type="number" placeholder="Assists" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0b66ff]" />
              <input v-model="recordForm.fg_percent" type="number" step="0.1" placeholder="FG%" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0b66ff]" />
              <input v-model="recordForm.game_date" type="date" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0b66ff]" />
              <div class="col-span-2 sm:col-span-5 flex justify-end gap-3 mt-2">
                <button type="submit" class="bg-[#0b66ff] hover:bg-[#084dcc] text-white rounded-lg px-4 py-2 text-sm font-semibold shadow">
                  Save Record
                </button>
                <button type="button" @click="clearRecordForm" class="border border-neutral-300 hover:bg-neutral-100 rounded-lg px-4 py-2 text-sm">
                  Clear
                </button>
              </div>
            </form>
          </div>

          <div class="bg-white border rounded-xl shadow p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold text-neutral-800">Performance Trend</h3>
              <div class="flex items-center gap-2">
                <label class="text-xs text-neutral-500">Group by</label>
                <select v-model="timeRange" class="border rounded px-2 py-1 text-xs bg-white">
                  <option value="all">All</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </div>
            </div>
            <canvas ref="chartCanvas" class="h-56 w-full"></canvas>
          </div>

          <div class="bg-neutral-50 border rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-neutral-800">Recent Game Records</h3>
              <button
                type="button"
                class="text-xs px-3 py-1 rounded border border-neutral-300 bg-white hover:bg-neutral-100 disabled:opacity-50"
                :disabled="recentRecords.length === 0"
                @click="clearMostRecentRecord"
              >
                Clear Recent
              </button>
            </div>
            <div v-if="recentRecords.length === 0" class="text-sm text-neutral-500 text-center py-4">
              No game records yet.
            </div>
            <ul v-else class="divide-y divide-neutral-200 rounded-md overflow-hidden border">
              <li v-for="r in recentRecords" :key="r.id" class="px-4 py-3 text-sm flex justify-between items-center hover:bg-neutral-100 transition">
                <div>
                  <div class="font-medium text-neutral-800">{{ r.game_date }}</div>
                  <div class="text-xs text-neutral-600">
                    Pts: {{ r.points }} | Reb: {{ r.rebounds }} | Ast: {{ r.assists }} | FG%: {{ r.fg_percent }}
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-neutral-500">#{{ selectedPlayer.number || '—' }}</span>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <div class="bg-neutral-100 p-4 flex justify-end">
          <button @click="showRecordModal = false"
            class="px-4 py-2 text-sm font-semibold bg-white border border-neutral-300 rounded-lg hover:bg-neutral-200 transition">
            Close
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: scale(0.97);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
.animate-fade-in {
  animation: fade-in 0.25s ease-in-out;
}
</style>
