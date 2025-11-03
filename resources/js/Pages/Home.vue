<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  featured: Array,         // 4 top stories (first is main)
  playersBySport: Array,   // [{ sport, players: [] }]
  gamesBySport: Array,     // [{ sport, games: [] }]
  news: Array              // general news list
})

// Modal states
const showPlayerModal = ref(false)
const selectedPlayer = ref(null)
const showGameModal = ref(false)
const selectedGame = ref(null)

// Modal functions
const openPlayerModal = (player) => {
  selectedPlayer.value = player
  showPlayerModal.value = true
}
const openGameModal = (game) => {
  selectedGame.value = game
  showGameModal.value = true
}
const closePlayerModal = () => { showPlayerModal.value = false; selectedPlayer.value = null }
const closeGameModal = () => { showGameModal.value = false; selectedGame.value = null }

// Top players dropdown
const sportOptions = computed(() => (props.playersBySport || []).map(s => s.sport))
const selectedSportSlug = ref('')

function pickDefaultSlug(opts) {
  if (!opts || !opts.length) return ''
  const b = opts.find(o => (o?.slug || '').toLowerCase() === 'basketball')
  return (b && b.slug) || opts[0].slug
}

// Initialize default sport
selectedSportSlug.value = pickDefaultSlug(sportOptions.value)

// Keep selection valid when options change
watch(sportOptions, (opts) => {
  if (!opts.length) { selectedSportSlug.value = ''; return }
  if (!opts.some(o => o.slug === selectedSportSlug.value)) {
    selectedSportSlug.value = pickDefaultSlug(opts)
  }
})

const selectedPlayersSection = computed(() => {
  const slug = selectedSportSlug.value
  return (props.playersBySport || []).find(sec => sec?.sport?.slug === slug)
})

// Games dropdown
const gameSportOptions = computed(() => (props.gamesBySport || []).map(s => s.sport))
const selectedGamesSportSlug = ref('')
selectedGamesSportSlug.value = pickDefaultSlug(gameSportOptions.value)
watch(gameSportOptions, (opts) => {
  if (!opts.length) { selectedGamesSportSlug.value = ''; return }
  if (!opts.some(o => o.slug === selectedGamesSportSlug.value)) {
    selectedGamesSportSlug.value = pickDefaultSlug(opts)
  }
})
const selectedGamesSection = computed(() => {
  const slug = selectedGamesSportSlug.value
  return (props.gamesBySport || []).find(sec => sec?.sport?.slug === slug)
})
</script>

<template>
  <AppLayout title="Home">
    <div class="home-page min-h-screen bg-[#f9fafc] text-[#111827] font-inter">
      <div class="max-w-[1200px] mx-auto w-full px-4 py-8">

        <!-- 🏆 HERO SECTION -->
        <section class="mb-12">
          <h2 class="text-2xl font-extrabold mb-6 tracking-tight">LATEST HIGHLIGHTS</h2>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main big article -->
            <div
              v-if="featured[0]"
              class="relative col-span-2 cursor-pointer rounded-xl overflow-hidden shadow-lg group"
              @click="$inertia.visit(route('news.show', featured[0].slug))"
            >
              <img
                :src="featured[0].cover"
                alt="main news"
                class="h-[450px] w-full object-cover group-hover:scale-105 transition-transform duration-700"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 flex flex-col justify-end">
                <h2 class="text-3xl font-bold text-white mb-2 group-hover:text-[#0b66ff] transition">
                  {{ featured[0].title }}
                </h2>
                <p class="text-white/80 line-clamp-2">{{ featured[0].excerpt }}</p>
                <span class="text-xs text-white/70 mt-2">{{ featured[0].published }}</span>
              </div>
            </div>

            <!-- Side articles -->
            <div class="flex flex-col gap-5">
              <div
                v-for="(item, idx) in featured.slice(1, 4)"
                :key="idx"
                class="flex items-center gap-4 bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-3 cursor-pointer transition"
                @click="$inertia.visit(route('news.show', item.slug))"
              >
                <img :src="item.cover" alt="thumb" class="h-20 w-28 object-cover rounded-md" />
                <div class="flex flex-col">
                  <h3 class="font-semibold text-sm text-[#111827] line-clamp-2 hover:text-[#0b66ff] transition">
                    {{ item.title }}
                  </h3>
                  <p class="text-xs text-[#4b5563] line-clamp-2 mt-1">{{ item.excerpt }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>


        <!-- ⚽ TOP PLAYERS -->
        <section v-if="sportOptions && sportOptions.length" class="mb-12">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-2xl font-extrabold tracking-tight">Top Players of the Month</h2>
            <div class="flex items-center gap-2">
              <label for="player-sport" class="text-sm text-[#6b7280]">Sport</label>
              <select id="player-sport" v-model="selectedSportSlug" class="text-sm border border-gray-300 rounded-md px-2 py-1 bg-white">
                <option v-for="s in sportOptions" :key="s.id" :value="s.slug">{{ s.name }}</option>
              </select>
              <Link :href="route('athletes.index', { sport: selectedSportSlug })" class="text-sm text-[#0b66ff] hover:underline">See all</Link>
            </div>
          </div>

          <div v-if="selectedPlayersSection" class="flex gap-4 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory pb-2">
            <div
              v-for="p in selectedPlayersSection.players"
              :key="p.id"
              class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition p-5 text-center cursor-pointer shrink-0 w-[220px] snap-start"
              @click="openPlayerModal(p)"
            >
              <img
                :src="p.photo"
                alt="player"
                class="w-20 h-20 mx-auto rounded-full object-cover border-4 border-[#0b66ff]/10 mb-3"
                @error="(e) => (e.target.src = '/images/default-logo.png')"
              />
              <h4 class="font-semibold">{{ p.name }}</h4>
              <p class="text-xs text-[#6b7280]">{{ p.team || p.sport }}</p>
              <div class="mt-2 text-[11px] text-[#0b66ff]">{{ p.month_points }} pts • {{ p.month_rebounds }} reb • {{ p.month_assists }} ast</div>
            </div>
          </div>
        </section>

        <!-- 🏀 UPCOMING GAMES -->
        <section v-if="gamesBySport && gamesBySport.length" class="mb-12">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-2xl font-extrabold tracking-tight">Upcoming Games</h2>
            <div class="flex items-center gap-2">
              <label for="game-sport" class="text-sm text-[#6b7280]">Sport</label>
              <select id="game-sport" v-model="selectedGamesSportSlug" class="text-sm border border-gray-300 rounded-md px-2 py-1 bg-white">
                <option v-for="s in gameSportOptions" :key="s.id" :value="s.slug">{{ s.name }}</option>
              </select>
              <Link href="/schedule" class="text-sm text-[#0b66ff] hover:underline">Full schedule</Link>
            </div>
          </div>

          <div v-if="selectedGamesSection" class="flex gap-4 overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory pb-2">
            <div
              v-for="g in selectedGamesSection.games"
              :key="g.id"
              class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition text-center cursor-pointer shrink-0 w-[260px] snap-start"
              @click="openGameModal(g)"
            >
              <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-semibold text-[#0b66ff]">{{ g.sport }}</span>
                <span class="text-xs text-[#6b7280]">{{ g.date }}</span>
              </div>

              <div v-if="g.schools && g.schools.length > 2" class="flex flex-wrap items-center justify-center gap-3">
                <div v-for="s in g.schools" :key="s.id" class="flex items-center gap-2">
                  <img :src="s.logo" class="w-8 h-8 object-contain" @error="(e) => (e.target.src = '/images/default-logo.png')" />
                  <span class="text-xs font-medium">{{ s.name }}</span>
                </div>
              </div>
              <div v-else class="flex items-center justify-between">
                <div class="flex-1">
                  <img :src="g.team_a_logo" class="w-10 h-10 mx-auto object-contain" />
                  <p class="text-sm mt-1 font-semibold">{{ g.team_a }}</p>
                </div>
                <span class="mx-2 font-bold text-[#0b66ff]">VS</span>
                <div class="flex-1">
                  <img :src="g.team_b_logo" class="w-10 h-10 mx-auto object-contain" />
                  <p class="text-sm mt-1 font-semibold">{{ g.team_b }}</p>
                </div>
              </div>
              <p class="text-xs mt-3 text-[#6b7280]">{{ g.location }}</p>
            </div>
          </div>
        </section>

        <!-- 📰 MORE NEWS -->
        <section>
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-extrabold tracking-tight">MORE NEWS</h2>
            <Link href="/news" class="text-sm text-[#0b66ff] hover:underline">View all</Link>
          </div>

          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div
              v-for="n in news"
              :key="n.slug"
              @click="$inertia.visit(route('news.show', n.slug))"
              class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition cursor-pointer"
            >
              <img
                :src="n.cover"
                alt="news"
                class="h-48 w-full object-cover hover:scale-105 transition-transform duration-700"
              />
              <div class="p-4">
                <h3 class="font-semibold text-lg text-[#111827] line-clamp-2 hover:text-[#0b66ff] transition-colors">
                  {{ n.title }}
                </h3>
                <p class="text-sm text-[#4b5563] mt-1 line-clamp-3">{{ n.excerpt }}</p>
                <div class="text-xs text-[#9ca3af] mt-3">{{ n.published }}</div>
              </div>
            </div>
          </div>
        </section>

      </div>

      <!-- Player Modal -->
      <div v-if="showPlayerModal" class="modal-overlay" @click.self="closePlayerModal">
        <div class="modal-card">
          <div class="modal-header">
            <h3 class="text-lg font-semibold">Player Summary</h3>
            <button @click="closePlayerModal" class="text-gray-500 hover:text-gray-700">✕</button>
          </div>
          <div class="modal-body" v-if="selectedPlayer">
            <div class="flex items-center gap-4 mb-4">
              <img :src="selectedPlayer.photo" class="w-16 h-16 rounded-full object-cover" />
              <div>
                <div class="text-xl font-bold">{{ selectedPlayer.name }}</div>
                <div class="text-sm text-gray-600">{{ selectedPlayer.team || selectedPlayer.sport }}</div>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center">
              <div class="bg-[#0b66ff]/10 rounded-md p-3">
                <div class="text-xs text-gray-600">Points</div>
                <div class="text-lg font-semibold text-[#0b66ff]">{{ selectedPlayer.month_points }}</div>
              </div>
              <div class="bg-[#0b66ff]/10 rounded-md p-3">
                <div class="text-xs text-gray-600">Rebounds</div>
                <div class="text-lg font-semibold text-[#0b66ff]">{{ selectedPlayer.month_rebounds }}</div>
              </div>
              <div class="bg-[#0b66ff]/10 rounded-md p-3">
                <div class="text-xs text-gray-600">Assists</div>
                <div class="text-lg font-semibold text-[#0b66ff]">{{ selectedPlayer.month_assists }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Game Modal -->
      <div v-if="showGameModal" class="modal-overlay" @click.self="closeGameModal">
        <div class="modal-card">
          <div class="modal-header">
            <h3 class="text-lg font-semibold">Game Summary</h3>
            <button @click="closeGameModal" class="text-gray-500 hover:text-gray-700">✕</button>
          </div>
          <div class="modal-body" v-if="selectedGame">
            <div class="text-sm text-gray-600 mb-2">{{ selectedGame.sport }} · {{ selectedGame.date }}</div>
            <div class="text-sm text-gray-600 mb-4">Venue: {{ selectedGame.location }}</div>
            <div v-if="selectedGame.schools && selectedGame.schools.length" class="space-y-2">
              <div class="text-xs font-semibold text-gray-700">Participating Schools</div>
              <div class="flex flex-wrap gap-3 items-center">
                <div v-for="s in selectedGame.schools" :key="s.id" class="flex items-center gap-2 bg-gray-50 rounded-md px-2 py-1">
                  <img :src="s.logo" class="w-6 h-6 object-contain" />
                  <span class="text-xs">{{ s.name }}</span>
                </div>
              </div>
            </div>
            <div v-else class="flex items-center justify-between mt-4">
              <div class="flex-1 text-center">
                <img :src="selectedGame.team_a_logo" class="w-12 h-12 mx-auto object-contain" />
                <div class="font-medium mt-1">{{ selectedGame.team_a }}</div>
              </div>
              <div class="mx-2 font-bold text-[#0b66ff]">VS</div>
              <div class="flex-1 text-center">
                <img :src="selectedGame.team_b_logo" class="w-12 h-12 mx-auto object-contain" />
                <div class="font-medium mt-1">{{ selectedGame.team_b }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<style scoped>
.home-page {
  background-color: #f9fafc;
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Simple modal styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal-card {
  background: #fff;
  border-radius: 0.75rem;
  width: 100%;
  max-width: 560px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  animation: fadeIn 0.3s ease-in-out;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-body {
  padding: 1rem 1.25rem;
}

/* Hide horizontal scrollbar (WebKit + Firefox) */
.no-scrollbar {
  scrollbar-width: none; /* Firefox */
}

.no-scrollbar::-webkit-scrollbar {
  display: none; /* Chrome, Safari */
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.97); }
  to { opacity: 1; transform: scale(1); }
}
</style>
