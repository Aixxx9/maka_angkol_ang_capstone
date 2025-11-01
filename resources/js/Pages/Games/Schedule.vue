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

const placeholders = computed(() => Math.max(0, 4 - (props.games?.length ?? 0)))

const selectedGame = ref(null)

function openModal(game) {
  selectedGame.value = game
}
function closeModal() {
  selectedGame.value = null
}

// ✅ Public version (no /admin/)
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

          <!-- Teams -->
          <div class="flex">
            <div class="flex-1 p-4">
              <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                <div class="flex items-center justify-start gap-2">
                  <span class="text-sm font-bold text-neutral-800">{{ g.home_team?.school?.name }}</span>
                  <img v-if="g.home_team?.school?.logo_path" :src="logoPath(g.home_team.school.logo_path)" class="h-6 w-6 rounded-full object-contain" />
                </div>
                <div class="text-center">
                  <span class="text-sm font-medium text-neutral-500">vs</span>
                </div>
                <div class="flex items-center justify-end gap-2">
                  <img v-if="g.away_team?.school?.logo_path" :src="logoPath(g.away_team.school.logo_path)" class="h-6 w-6 rounded-full object-contain" />
                  <span class="text-sm font-bold text-neutral-800">{{ g.away_team?.school?.name }}</span>
                </div>
              </div>
              <div class="mt-4 text-[15px] font-semibold text-[#6f39ff]">
                # {{ g.sport?.name || 'Unknown Sport' }}
              </div>
            </div>

            <!-- Info section -->
            <div class="w-px bg-neutral-200"></div>
            <div class="w-40 p-4 flex flex-col justify-center space-y-3 text-center">
              <div class="flex items-center justify-center gap-1 text-[13px] font-bold text-neutral-800">
                <span class="truncate max-w-[70px]">{{ g.home_team?.school?.name }}</span>
                <span class="text-[#0b66ff] font-extrabold">VS</span>
                <span class="truncate max-w-[70px]">{{ g.away_team?.school?.name }}</span>
              </div>
              <div class="text-[13px] text-neutral-600">📍 {{ g.venue || 'TBA' }}</div>
              <div class="text-[13px] text-neutral-700">🏅 {{ g.sport?.name || 'Unknown Sport' }}</div>
            </div>
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
        <!-- Split Backgrounds -->
        <div class="grid grid-cols-2 h-80 relative">
          <!-- Left -->
          <div
            class="relative bg-cover bg-center"
            :style="{ backgroundImage: `url('${logoPath(selectedGame.home_team?.school?.logo_path)}')` }"
          >
            <div class="absolute inset-0 bg-black/45 flex items-center justify-center">
              <h2 class="text-2xl font-extrabold text-white text-center drop-shadow-lg px-4">
                {{ selectedGame.home_team?.school?.name }}
              </h2>
            </div>
          </div>

          <!-- Right -->
          <div
            class="relative bg-cover bg-center"
            :style="{ backgroundImage: `url('${logoPath(selectedGame.away_team?.school?.logo_path)}')` }"
          >
            <div class="absolute inset-0 bg-black/45 flex items-center justify-center">
              <h2 class="text-2xl font-extrabold text-white text-center drop-shadow-lg px-4">
                {{ selectedGame.away_team?.school?.name }}
              </h2>
            </div>
          </div>

          <!-- VS -->
          <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
            <span class="text-6xl font-extrabold text-white drop-shadow-[0_2px_10px_rgba(0,0,0,0.8)]">
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
          <div class="mt-1 text-white/90">
            📍 {{ selectedGame.venue || 'Venue not set' }}
          </div>

          <!-- Buttons -->
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
