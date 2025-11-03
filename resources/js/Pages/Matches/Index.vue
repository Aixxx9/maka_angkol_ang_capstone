<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  upcoming: { type: Array, default: () => [] },
  finished: { type: Array, default: () => [] },
  sports: { type: Array, default: () => [] },
  schools: { type: Array, default: () => [] },
  standings: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

// Page-level tabs
const activeTab = ref('schedule')
// Schedule sub-tab
const schedTab = ref('upcoming')

const state = ref({
  sport_id: props.filters?.sport_id || '',
  school_id: props.filters?.school_id || '',
  winner_school_id: props.filters?.winner_school_id || '',
})

function applyFilters() {
  router.get('/matches', {
    sport_id: state.value.sport_id || undefined,
    school_id: state.value.school_id || undefined,
    winner_school_id: state.value.winner_school_id || undefined,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

function setSportFilter(val){
  state.value.sport_id = val
  applyFilters()
}

const wd = ['SUN','MON','TUE','WED','THU','FRI','SAT']
function fmtDate(iso){ const d=new Date(iso);return `${wd[d.getDay()]} ${String(d.getDate()).padStart(2,'0')}.${String(d.getMonth()+1).padStart(2,'0')}.${d.getFullYear()}` }
function fmtTime(iso){ const d=new Date(iso);return `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}` }
function logoPath(p){ if(!p) return '/images/default-logo.png'; return p?.startsWith?.('/storage')?p:`/storage/${p}` }

function getParticipants(g){
  if(Array.isArray(g.teams) && g.teams.length) return g.teams.map(t=>t?.school).filter(Boolean)
  const arr=[]; if(g.home_team?.school) arr.push(g.home_team.school); if(g.away_team?.school) arr.push(g.away_team.school); return arr
}

function scoreline(g){ if(g.status!=='final') return null; return `${g.home_score} - ${g.away_score}` }

// Finalize modal state
const showFinalize = ref(false)
const currentGame = ref(null)
const finalizeForm = ref({ home_score: 0, away_score: 0 })
const winnerTeamId = ref(null)
const participantScores = ref({})

function getParticipantTeams(g){
  const list = []
  if (Array.isArray(g.teams) && g.teams.length) {
    for (const t of g.teams) {
      if (!t) continue
      list.push({
        teamId: t.id,
        school: t.school,
        isHome: t.id === g.home_team_id || t.id === g.home_team?.id,
        isAway: t.id === g.away_team_id || t.id === g.away_team?.id,
      })
    }
    return list
  }
  if (g.home_team) list.push({ teamId: g.home_team?.id ?? g.home_team_id, school: g.home_team?.school, isHome: true, isAway: false })
  if (g.away_team) list.push({ teamId: g.away_team?.id ?? g.away_team_id, school: g.away_team?.school, isHome: false, isAway: true })
  return list
}

function openFinalize(g){
  currentGame.value = g
  finalizeForm.value = {
    home_score: Number(g.home_score ?? 0),
    away_score: Number(g.away_score ?? 0),
  }
  // default winner to home team
  winnerTeamId.value = g?.home_team?.id ?? g?.home_team_id ?? null
  // seed participant scores (use pivot.score if present, fall back to home/away)
  participantScores.value = {}
  for (const p of getParticipantTeams(g)) {
    const tid = p.teamId
    let val = 0
    // if teams array includes pivot with score
    const teamObj = (Array.isArray(g.teams) ? g.teams.find(t => t?.id === tid) : null)
    if (teamObj && teamObj.pivot && typeof teamObj.pivot.score !== 'undefined') {
      val = Number(teamObj.pivot.score || 0)
    }
    if (p.isHome) val = Number(g.home_score || val || 0)
    if (p.isAway) val = Number(g.away_score || val || 0)
    participantScores.value[tid] = val
  }
  showFinalize.value = true
}

function submitFinalize(){
  if(!currentGame.value) return
  // Adjust scores based on selected winner if necessary
  const homeId = currentGame.value?.home_team?.id ?? currentGame.value?.home_team_id
  const awayId = currentGame.value?.away_team?.id ?? currentGame.value?.away_team_id
  // derive home/away from participantScores
  finalizeForm.value.home_score = Number(participantScores.value[homeId] || 0)
  finalizeForm.value.away_score = Number(participantScores.value[awayId] || 0)
  const hs = Number(finalizeForm.value.home_score || 0)
  const as = Number(finalizeForm.value.away_score || 0)
  if (winnerTeamId.value === homeId && hs <= as) finalizeForm.value.home_score = as + 1
  if (winnerTeamId.value === awayId && as <= hs) finalizeForm.value.away_score = hs + 1
  router.put(`/games/${currentGame.value.id}/finalize`, {
    home_score: finalizeForm.value.home_score,
    away_score: finalizeForm.value.away_score,
    participant_scores: participantScores.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showFinalize.value = false
      currentGame.value = null
    }
  })
}
</script>

<template>
  <AppLayout>
    <div class="max-w-[1200px] mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-5">
        <h1 class="text-3xl font-extrabold tracking-tight">MATCHES</h1>
        <div class="flex gap-2">
          <Link href="/games/create" class="bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-semibold text-sm px-4 py-2 rounded-md shadow">+ Add Schedule</Link>
        </div>
      </div>

      <!-- Filters -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-white rounded-md border p-3 mb-5">
        <div>
          <label class="text-xs text-neutral-500">Sport</label>
          <select v-model="state.sport_id" @change="applyFilters" class="w-full border rounded px-2 py-2">
            <option value="">All Sports</option>
            <option v-for="s in sports" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-neutral-500">Who Played (School)</label>
          <select v-model="state.school_id" @change="applyFilters" class="w-full border rounded px-2 py-2">
            <option value="">Any</option>
            <option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-neutral-500">Winner (School)</label>
          <select v-model="state.winner_school_id" @change="applyFilters" class="w-full border rounded px-2 py-2">
            <option value="">Any</option>
            <option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div class="flex items-end">
          <div class="inline-flex bg-neutral-100 rounded overflow-hidden border">
            <button class="px-3 py-2 text-sm" :class="activeTab==='schedule' ? 'bg-white font-semibold' : ''" @click="activeTab='schedule'">Schedule</button>
            <button class="px-3 py-2 text-sm" :class="activeTab==='standings' ? 'bg-white font-semibold' : ''" @click="activeTab='standings'">Standings</button>
          </div>
        </div>
      </div>

      <!-- Schedule tab -->
      <div v-if="activeTab==='schedule'">
        <div class="flex items-center justify-between mb-3">
          <div class="inline-flex bg-neutral-100 rounded overflow-hidden border">
            <button class="px-3 py-2 text-sm" :class="schedTab==='upcoming' ? 'bg-white font-semibold' : ''" @click="schedTab='upcoming'">Upcoming</button>
            <button class="px-3 py-2 text-sm" :class="schedTab==='finished' ? 'bg-white font-semibold' : ''" @click="schedTab='finished'">Finished</button>
          </div>
        </div>

        <!-- Compact sport dropdown for Schedule -->
        <div class="flex items-center gap-2 mb-4">
          <label class="text-xs text-neutral-500">Filter by Sport</label>
          <select :value="state.sport_id || ''" @change="(e) => setSportFilter(e.target.value)" class="border rounded px-2 py-1 text-sm">
            <option value="">All Sports</option>
            <option v-for="s in sports" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <div v-if="schedTab==='upcoming'">
          <div v-if="!upcoming.length" class="text-center text-sm text-neutral-500 py-10">No upcoming matches.</div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div v-for="g in upcoming" :key="g.id" class="bg-white rounded-md shadow-sm border overflow-hidden cursor-pointer" @click="openFinalize(g)">
              <div class="flex items-center justify-between text-[13px] font-semibold tracking-wide px-4 py-2 bg-neutral-100">
                <div class="text-neutral-800">{{ fmtDate(g.starts_at) }}</div>
                <div class="text-neutral-600">{{ fmtTime(g.starts_at) }}</div>
              </div>
              <div class="p-4 flex flex-col items-center text-center space-y-3">
                <div class="flex flex-wrap justify-center gap-3">
                  <template v-for="(sch,i) in getParticipants(g)" :key="i">
                    <div class="flex flex-col items-center">
                      <img :src="logoPath(sch?.logo_path)" class="h-10 w-10 rounded-full object-contain ring-1 ring-neutral-300 bg-white" />
                      <span class="text-[12px] font-semibold text-neutral-800 mt-1 max-w-[120px] truncate">{{ sch?.name }}</span>
                    </div>
                    <span v-if="getParticipants(g).length===2 && i===0" class="self-center text-[#0b66ff] font-bold text-sm mx-1">VS</span>
                  </template>
                </div>
                <div class="text-[15px] font-semibold text-[#6f39ff] mt-3"># {{ g.sport?.name || 'Unknown Sport' }}</div>
                <div class="text-xs text-neutral-600 mt-1">Venue: {{ g.venue || 'TBA' }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else>
          <div v-if="!finished.length" class="text-center text-sm text-neutral-500 py-10">No finished matches.</div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div v-for="g in finished" :key="g.id" class="bg-white rounded-md shadow-sm border overflow-hidden">
              <div class="flex items-center justify-between text-[13px] font-semibold tracking-wide px-4 py-2 bg-neutral-100">
                <div class="text-neutral-800">{{ fmtDate(g.starts_at) }}</div>
                <div class="text-neutral-600">{{ fmtTime(g.starts_at) }}</div>
              </div>
              <div class="p-4 flex flex-col items-center text-center space-y-3">
                <div class="flex flex-wrap justify-center gap-3">
                  <template v-for="(sch,i) in getParticipants(g)" :key="i">
                    <div class="flex flex-col items-center">
                      <img :src="logoPath(sch?.logo_path)" class="h-10 w-10 rounded-full object-contain ring-1 ring-neutral-300 bg-white" />
                      <span class="text-[12px] font-semibold text-neutral-800 mt-1 max-w-[120px] truncate">{{ sch?.name }}</span>
                    </div>
                    <span v-if="getParticipants(g).length===2 && i===0" class="self-center text-[#0b66ff] font-bold text-sm mx-1">VS</span>
                  </template>
                </div>
                <div class="text-[15px] font-semibold text-[#10b981] mt-3">Final: {{ scoreline(g) }}</div>
                <div class="text-xs text-neutral-600 mt-1"># {{ g.sport?.name || 'Unknown Sport' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Standings tab -->
      <div v-else-if="activeTab==='standings'">
        <div class="bg-white rounded-md border overflow-hidden">
          <table class="min-w-full text-sm">
            <thead class="bg-neutral-100 text-neutral-700">
              <tr>
                <th class="text-left px-4 py-2">#</th>
                <th class="text-left px-4 py-2">School</th>
                <th class="text-right px-4 py-2">W</th>
                <th class="text-right px-4 py-2">L</th>
                <th class="text-right px-4 py-2">GP</th>
                <th class="text-right px-4 py-2">Win%</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in standings" :key="row.school_id" class="border-t">
                <td class="px-4 py-2">{{ idx + 1 }}</td>
                <td class="px-4 py-2">
                  <div class="flex items-center gap-2">
                    <img :src="logoPath(row.logo_path)" class="h-7 w-7 rounded-full object-cover ring-1 ring-neutral-300"/>
                    <span class="font-semibold">{{ row.name }}</span>
                  </div>
                </td>
                <td class="px-4 py-2 text-right font-semibold">{{ row.wins }}</td>
                <td class="px-4 py-2 text-right">{{ row.losses }}</td>
                <td class="px-4 py-2 text-right">{{ row.gp }}</td>
                <td class="px-4 py-2 text-right">{{ row.win_pct }}%</td>
              </tr>
              <tr v-if="!standings.length">
                <td colspan="6" class="px-4 py-6 text-center text-neutral-500">No teams found for selected sport.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      

      <!-- Finalize Modal -->
      <div v-if="showFinalize" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-md shadow-lg w-full max-w-md p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="text-lg font-bold">Finalize Game</div>
            <button class="text-neutral-500 hover:text-neutral-800" @click="showFinalize=false">✕</button>
          </div>
          <div v-if="currentGame" class="space-y-3">
            <div class="text-sm text-neutral-600">
              {{ currentGame.sport?.name }} • {{ fmtDate(currentGame.starts_at) }} {{ fmtTime(currentGame.starts_at) }}
            </div>
            <div class="space-y-3">
              <div
                v-for="p in getParticipantTeams(currentGame)"
                :key="p.teamId"
                class="grid grid-cols-3 gap-3 items-center"
              >
                <div class="flex items-center gap-2 col-span-2">
                  <input type="radio" name="winnerTeam" :value="p.teamId" v-model="winnerTeamId" class="h-4 w-4 text-[#2563eb]" />
                  <img :src="logoPath(p.school?.logo_path)" class="h-7 w-7 rounded-full ring-1 ring-neutral-300"/>
                  <div class="font-semibold truncate">{{ p.school?.name }}</div>
                  <span v-if="p.isHome" class="ml-2 text-xs px-2 py-0.5 rounded bg-[#dbeafe] text-[#1d4ed8]">Home</span>
                  <span v-if="p.isAway" class="ml-2 text-xs px-2 py-0.5 rounded bg-[#fee2e2] text-[#b91c1c]">Away</span>
                  <span v-if="!p.isHome && !p.isAway" class="ml-2 text-xs text-neutral-500">(participant)</span>
                </div>
                <div class="flex justify-end">
                  <input type="number" v-model.number="participantScores[p.teamId]" min="0" class="border rounded px-2 py-2 text-right w-20" placeholder="Score" />
                </div>
              </div>
              <div class="text-xs text-neutral-500">Pick the winner with the radio. You can input points for all participants.</div>
            </div>
          </div>
          <div class="mt-4 flex justify-end gap-2">
            <button class="px-4 py-2 rounded border" @click="showFinalize=false">Cancel</button>
            <button class="px-4 py-2 rounded bg-[#10b981] hover:bg-[#0e9f76] text-white font-semibold" @click="submitFinalize">Save Result</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
