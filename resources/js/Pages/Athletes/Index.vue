<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// ✅ props now include sports from backend
const props = defineProps({
  sports:  { type: Array, default: () => [] },
  sport:   { type: String, default: null },
  players: { type: Array,  default: () => [] },
  query:   { type: String, default: '' },
})

// ✅ reactive references
const sportSearch = ref('')
const playerQuery = ref(props.query || '')

// ✅ filtered sports (live search)
const filteredSports = computed(() => {
  const term = sportSearch.value.trim().toLowerCase()
  if (!term) return props.sports
  return props.sports.filter(s => (s.name || '').toLowerCase().includes(term))
})

// ✅ player helpers
const imgSrc = (p) => {
  const path = p?.avatar_path
  if (!path) return '/images/user.png'
  return path.startsWith('/storage') ? path : `/storage/${path}`
}
const fullName = (p) => [p?.first_name, p?.last_name].filter(Boolean).join(' ')
const initials = (p) => (p?.first_name?.[0] ?? '') + (p?.last_name?.[0] ?? '')
const n1  = (n) => (typeof n === 'number' ? n.toFixed(1) : '—')
const pct = (n) => (typeof n === 'number' ? `${n.toFixed(1)}%` : '—')

// ✅ smooth scroll
const playersSectionEl = ref(null)
function scrollToPlayers() {
  if (playersSectionEl.value) {
    playersSectionEl.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}
watch(() => props.sport, (val) => { if (val) nextTick(scrollToPlayers) })
onMounted(() => { if (props.sport) nextTick(scrollToPlayers) })

// ✅ navigate between sports
function chooseSport(slug) {
  router.get('/athletes', { sport: slug }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// ✅ player filtering + metrics
const filteredPlayers = computed(() => {
  const term = playerQuery.value.trim().toLowerCase()
  if (!term) return props.players
  return props.players.filter(p => {
    const name = fullName(p).toLowerCase()
    const team = (p.team_name ?? '').toLowerCase()
    const num  = String(p.number ?? '')
    return name.includes(term) || team.includes(term) || num.includes(term)
  })
})

const totalPlayers = computed(() => filteredPlayers.value.length)
const avgPoints = computed(() => {
  const vals = filteredPlayers.value.map(p => Number(p.ppg)).filter(v => !isNaN(v))
  return vals.length ? vals.reduce((a,b)=>a+b,0)/vals.length : 0
})
const topScorer = computed(() =>
  [...filteredPlayers.value].sort((a,b)=>(b.ppg ?? 0) - (a.ppg ?? 0))[0] || null
)

// ✅ Add Player modal
const showAddPlayer = ref(false)
const avatarPreview = ref(null)
const addForm = useForm({
  first_name: '',
  last_name: '',
  number: '',
  position: '',
  team_id: '',
  sport_slug: props.sport || '',
  avatar: null,
})

function openAddModal() {
  addForm.sport_slug = props.sport || ''
  showAddPlayer.value = true
}

function onPickAvatar(e) {
  const f = e.target.files?.[0]
  addForm.avatar = f || null
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarPreview.value = f ? URL.createObjectURL(f) : null
}

function clearAvatar() {
  addForm.avatar = null
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarPreview.value = null
}

function savePlayer() {
  addForm.post('/athletes', {
    forceFormData: true,
    onSuccess: () => {
      showAddPlayer.value = false
      clearAvatar()
      addForm.reset('first_name','last_name','number','position','team_id','sport_slug','avatar')
      if (addForm.recentlySuccessful && addForm.sport_slug && addForm.sport_slug !== props.sport) {
        chooseSport(addForm.sport_slug)
      } else {
        router.reload({ only: ['players'] })
        nextTick(scrollToPlayers)
      }
    }
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
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-[#0b66ff] px-4 py-2 text-white text-sm font-semibold shadow-sm hover:bg-[#0856d6] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0b66ff]/60"
          @click="openAddModal"
        >
          <span class="text-base leading-none">＋</span>
          Add Player
        </button>
      </div>

      <!-- STEP 1: PICK A SPORT -->
      <div class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div class="md:col-span-6">
            <label class="text-xs font-medium text-neutral-600">Search sports</label>
            <div class="relative mt-1">
              <input
                v-model="sportSearch"
                type="text"
                placeholder="Find a sport (e.g., Basketball)"
                class="w-full rounded-lg border border-neutral-300 bg-white/90 px-3 py-2 pl-10 shadow-sm placeholder:text-neutral-400 focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
              />
              <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 18a8 8 0 1 1 6.32-3.09l4.39 4.39-1.41 1.41-4.39-4.39A7.98 7.98 0 0 1 10 18Zm0-2a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"/>
              </svg>
            </div>
          </div>

          <div v-if="sport" class="md:col-span-6">
            <label class="text-xs font-medium text-neutral-600">Search players ({{ sport }})</label>
            <input
              v-model="playerQuery"
              type="text"
              placeholder="Search by player or team"
              class="mt-1 w-full rounded-lg border border-neutral-300 bg-white/90 px-3 py-2 shadow-sm placeholder:text-neutral-400 focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            />
          </div>
        </div>

        <!-- Sports grid -->
        <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <button
            v-for="s in filteredSports"
            :key="s.slug"
            type="button"
            @click="chooseSport(s.slug)"
            class="group rounded-xl border border-neutral-200 bg-white/95 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#0b66ff]/50 hover:shadow-md"
            :class="sport === s.slug ? 'ring-2 ring-[#0b66ff]' : ''"
          >
            <!-- sport icon or initials -->
            <div class="h-12 w-12 rounded-full bg-neutral-100 grid place-items-center overflow-hidden shadow-sm ring-1 ring-white">
              <img
                v-if="s.icon_path"
                :src="s.icon_path.startsWith('/storage') ? s.icon_path : `/storage/${s.icon_path}`"
                class="h-full w-full object-cover"
              />
              <span v-else class="text-sky-700 font-bold">
                {{ s.name.split(' ').map(w => w[0]).join('').slice(0,3).toUpperCase() }}
              </span>
            </div>
            <div class="mt-3 text-[13px] font-semibold leading-snug text-neutral-800 group-hover:text-[#0b66ff]">
              {{ s.name }}
            </div>
            <div class="text-[11px] text-neutral-500">Tap to view players</div>
          </button>
        </div>
      </div>

      <!-- STEP 2: SHOW PLAYERS + STATS -->
      <div v-if="sport" ref="playersSectionEl" id="players" class="mt-8 scroll-mt-24">
        <!-- KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Selected Sport</div>
            <div class="mt-1 text-base font-semibold capitalize text-neutral-900">{{ sport.replaceAll('-', ' ') }}</div>
          </div>
          <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Total Players</div>
            <div class="mt-1 text-2xl font-extrabold text-neutral-900">{{ totalPlayers }}</div>
          </div>
          <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Avg Points</div>
            <div class="mt-1 text-2xl font-extrabold text-neutral-900">{{ n1(avgPoints) }}</div>
          </div>
          <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-neutral-500">Top Scorer</div>
            <div class="mt-1 font-semibold truncate text-neutral-900">{{ topScorer ? fullName(topScorer) : '—' }}</div>
          </div>
        </div>

        <!-- Player cards -->
        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          <div
            v-for="p in filteredPlayers"
            :key="p.id"
            class="rounded-xl border border-neutral-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <div class="flex items-start gap-3 p-4">
              <div v-if="p.avatar_path">
                <img :src="imgSrc(p)" :alt="fullName(p)" class="h-12 w-12 rounded-full object-cover ring-2 ring-white shadow-sm" />
              </div>
              <div v-else class="h-12 w-12 rounded-full bg-sky-100 text-sky-700 grid place-items-center font-bold ring-2 ring-white shadow-sm">
                {{ initials(p).toUpperCase() }}
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <div class="font-semibold leading-tight truncate text-neutral-900">{{ fullName(p) }}</div>
                  <span class="text-[11px] inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-700 ring-1 ring-neutral-200">
                    {{ p.position || '—' }}
                  </span>
                </div>
                <div class="text-xs text-neutral-500">{{ p.team_name || '—' }}</div>
              </div>
            </div>
            <div class="px-4 pb-4 grid grid-cols-4 gap-2 text-center">
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">PPG</div>
                <div class="text-lg font-extrabold text-neutral-900">{{ n1(p.ppg) }}</div>
              </div>
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">RPG</div>
                <div class="text-lg font-extrabold text-neutral-900">{{ n1(p.rpg) }}</div>
              </div>
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">APG</div>
                <div class="text-lg font-extrabold text-neutral-900">{{ n1(p.apg) }}</div>
              </div>
              <div class="rounded-md bg-neutral-50 p-2">
                <div class="text-[11px] text-neutral-500">FG%</div>
                <div class="text-lg font-extrabold text-neutral-900">{{ pct(p.fg) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="mt-10 text-center text-neutral-500">
        Pick a sport above to view its athletes and statistics.
      </div>
    </section>

    <!-- ADD PLAYER MODAL -->
    <div
      v-if="showAddPlayer"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between bg-neutral-50">
          <div class="font-semibold text-neutral-900">Add Player</div>
          <button class="text-neutral-500 hover:text-neutral-800 transition" @click="showAddPlayer=false">✕</button>
        </div>

        <form @submit.prevent="savePlayer" class="p-6 grid grid-cols-1 gap-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">First name</label>
              <input v-model="addForm.first_name" type="text" class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Last name</label>
              <input v-model="addForm.last_name" type="text" class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30" required />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Number</label>
              <input v-model="addForm.number" type="text" class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30" placeholder="#" />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Position</label>
              <input v-model="addForm.position" type="text" class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30" placeholder="PG / LW / etc." />
            </div>
            <div>
              <label class="block text-sm font-medium text-neutral-700 mb-1">Team ID (optional)</label>
              <input v-model="addForm.team_id" type="number" class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30" placeholder="Team ID" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Sport</label>
            <select v-model="addForm.sport_slug" class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30" required>
              <option value="" disabled>Select a sport…</option>
              <option v-for="s in props.sports" :key="s.slug" :value="s.slug">{{ s.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Avatar (optional)</label>
            <input type="file" accept="image/*" @change="onPickAvatar" class="block w-full text-sm text-neutral-700 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-neutral-100 file:text-neutral-700 hover:file:bg-neutral-200" />
            <div v-if="avatarPreview" class="relative mt-3">
              <img :src="avatarPreview" alt="preview" class="h-24 w-24 rounded-full object-cover ring-2 ring-white shadow-sm" />
              <button type="button" class="absolute top-1 left-28 text-xs px-2 py-1 rounded bg-black/60 text-white hover:bg-black/70" @click="clearAvatar">Remove</button>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" class="px-3 py-2 rounded-lg border border-neutral-300 bg-white hover:bg-neutral-50">Cancel</button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg bg-[#0b66ff] text-white font-semibold shadow-sm hover:bg-[#0856d6] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0b66ff]/60"
              :disabled="addForm.processing"
            >
              {{ addForm.processing ? 'Saving…' : 'Save Player' }}
            </button>
          </div>

          <div v-if="addForm.errors && Object.keys(addForm.errors).length" class="text-sm text-red-600">
            <div v-for="(msg, key) in addForm.errors" :key="key">{{ msg }}</div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
