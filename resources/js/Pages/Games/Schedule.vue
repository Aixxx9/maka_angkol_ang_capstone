<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  games: { type: Array, default: () => [] }
})

const wd = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']

function fmtDate(iso) {
  const d = new Date(iso)
  const day = wd[d.getDay()]
  const dd = String(d.getDate()).padStart(2, '0')
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const yyyy = d.getFullYear()
  return `${day} ${dd}.${mm}.${yyyy}`
}

function fmtTime(iso) {
  const d = new Date(iso)
  const hh = String(d.getHours()).padStart(2, '0')
  const mm = String(d.getMinutes()).padStart(2, '0')
  return `${hh}:${mm}`
}

function logoPath(p) {
  if (!p) return '/images/default-logo.png'
  return p.startsWith('/storage') ? p : `/storage/${p}`
}

const selectedGame = ref(null)

function openModal(game) {
  selectedGame.value = game
}
function closeModal() {
  selectedGame.value = null
}

function deleteGame(id) {
  if (confirm('Are you sure you want to delete this schedule?')) {
    router.delete(`/games/${id}`, {
      onSuccess: () => (selectedGame.value = null),
    })
  }
}
function editGame(id) {
  router.visit(`/games/${id}/edit`)
}

// ✅ Collect all teams dynamically
function getParticipants(g) {
  if (Array.isArray(g.teams) && g.teams.length) {
    return g.teams.map(t => t?.school).filter(Boolean)
  }
  const arr = []
  if (g.home_team?.school) arr.push(g.home_team.school)
  if (g.away_team?.school) arr.push(g.away_team.school)
  return arr
}

// ✅ For modal display
const participants = computed(() => {
  const g = selectedGame.value
  return g ? getParticipants(g) : []
})
</script>

<template>
  <AppLayout>
    <div class="max-w-[1200px] mx-auto px-4 py-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <h1 class="text-3xl font-extrabold tracking-tight">NEXT MATCHES</h1>
        <Link
          href="/games/create"
          class="bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold text-sm px-4 py-2 rounded-md shadow transition"
        >
          + Add Schedule
        </Link>
      </div>

      <!-- Match Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div
          v-for="g in games"
          :key="g.id"
          @click="openModal(g)"
          class="cursor-pointer bg-white rounded-md shadow-sm border border-neutral-200 overflow-hidden hover:shadow-md transition"
        >
          <!-- Header -->
          <div class="flex items-center justify-between text-[13px] font-semibold tracking-wide px-4 py-2 bg-neutral-100">
            <div class="text-neutral-800">{{ fmtDate(g.starts_at) }}</div>
            <div class="text-neutral-600">{{ fmtTime(g.starts_at) }}</div>
          </div>

          <!-- Dynamic Teams -->
          <div class="p-4 flex flex-col items-center text-center space-y-3">
            <div class="flex flex-wrap justify-center gap-3">
              <template v-for="(sch, i) in getParticipants(g)" :key="i">
                <div class="flex flex-col items-center">
                  <img
                    :src="logoPath(sch?.logo_path)"
                    class="h-10 w-10 rounded-full object-contain ring-1 ring-neutral-300 bg-white"
                  />
                  <span class="text-[12px] font-semibold text-neutral-800 mt-1 max-w-[100px] truncate">
                    {{ sch?.name }}
                  </span>
                </div>
                <!-- Show VS between 2 schools only -->
                <span
                  v-if="getParticipants(g).length === 2 && i === 0"
                  class="self-center text-[#0b66ff] font-bold text-sm mx-1"
                >
                  VS
                </span>
              </template>
            </div>

            <div class="text-[15px] font-semibold text-[#6f39ff] mt-3"># {{ g.sport?.name || 'Unknown Sport' }}</div>
            <div class="text-xs text-neutral-600 mt-1">📍 {{ g.venue || 'TBA' }}</div>
          </div>

          <!-- Footer -->
          <div class="block text-center text-white text-[15px] font-bold tracking-wide bg-[#f2473f] hover:bg-[#e23a32] py-3">
            Click to View Details
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL -->
    <div
      v-if="selectedGame"
      class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 px-4 backdrop-blur-md"
      @click.self="closeModal"
    >
      <div class="relative w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl animate-fade-in border border-white/20 bg-white/10 backdrop-blur-lg">
        <!-- Participants logos grid -->
        <div class="relative p-8 bg-gradient-to-br from-black/60 to-black/40">
          <div
            class="grid gap-6 place-items-center auto-cols-fr"
            :class="participants.length <= 2 ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5'"
          >
            <div
              v-for="(sch, i) in participants"
              :key="i"
              class="flex flex-col items-center justify-center"
            >
              <img
                :src="logoPath(sch?.logo_path)"
                class="h-20 w-20 sm:h-24 sm:w-24 rounded-full object-contain ring-2 ring-white bg-white shadow-lg"
              />
              <div class="mt-2 text-xs sm:text-sm text-white/90 font-medium text-center truncate max-w-[100px]">
                {{ sch?.name }}
              </div>
            </div>
          </div>

          <!-- VS only if exactly 2 teams -->
          <div
            v-if="participants.length === 2"
            class="absolute inset-0 flex items-center justify-center pointer-events-none"
          >
            <span class="text-6xl font-extrabold text-white drop-shadow-[0_3px_10px_rgba(0,0,0,0.9)]">
              VS
            </span>
          </div>
        </div>

        <!-- Details -->
        <div class="p-6 text-center text-white bg-gradient-to-t from-black/80 via-black/60 to-transparent">
          <div class="text-2xl font-bold mb-2">{{ selectedGame.sport?.name || 'Unknown Sport' }}</div>
          <div class="text-white/90">
            📅 {{ fmtDate(selectedGame.starts_at) }} — 🕒 {{ fmtTime(selectedGame.starts_at) }}
          </div>
          <div class="mt-1 text-white/90">📍 {{ selectedGame.venue || 'Venue not set' }}</div>

          <div class="mt-6 flex justify-center gap-4">
            <button
              @click="editGame(selectedGame.id)"
              class="px-4 py-2 rounded-md bg-[#0b66ff]/70 text-white font-semibold hover:bg-[#0b66ff]/100 transition shadow-sm hover:shadow-md"
            >
              Edit
            </button>
            <button
              @click="deleteGame(selectedGame.id)"
              class="px-4 py-2 rounded-md bg-red-600/70 text-white font-semibold hover:bg-red-600/100 transition shadow-sm hover:shadow-md"
            >
              Delete
            </button>
            <button
              @click="closeModal"
              class="px-4 py-2 rounded-md border border-white/20 bg-white/10 text-white hover:bg-white/20 transition"
            >
              Close
            </button>
          </div>
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
