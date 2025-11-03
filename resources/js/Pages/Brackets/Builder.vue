<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  sports: { type: Array, default: () => [] },
  teams: { type: Array, default: () => [] },
  competition: { type: Object, default: null },
  rounds: { type: Array, default: () => [] },
})

const sportId = ref(props.competition?.sport_id || '')
const size = ref(8)
const selected = ref([])
const name = ref(props.competition?.name || '')

const filteredTeams = computed(() => {
  return props.teams.filter(t => !sportId.value || t.sport_id === Number(sportId.value))
})

function logoPath(p){ if(!p) return '/images/default-logo.png'; return p.startsWith('/storage')?p:`/storage/${p}` }

function toggleTeam(id){
  if(selected.value.includes(id)) selected.value = selected.value.filter(x=>x!==id)
  else if(selected.value.length < Number(size.value)) selected.value = [...selected.value, id]
}

function createBracket(){
  router.post('/brackets', {
    sport_id: sportId.value,
    size: Number(size.value),
    name: name.value || undefined,
    seed_team_ids: selected.value,
  }, { preserveScroll: true })
}

function fmtRoundName(r){ return `Round ${r}` }
</script>

<template>
  <AppLayout>
    <div class="max-w-[1200px] mx-auto px-4 py-6">
      <h1 class="text-3xl font-extrabold tracking-tight mb-5">BRACKET BUILDER</h1>

      <!-- Create Mode -->
      <div v-if="!competition" class="bg-white border rounded-md p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <div>
            <label class="text-xs text-neutral-500">Sport</label>
            <select v-model="sportId" class="w-full border rounded px-2 py-2">
              <option value="">Select sport</option>
              <option v-for="s in sports" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-neutral-500">Bracket Size</label>
            <select v-model="size" class="w-full border rounded px-2 py-2">
              <option :value="4">4</option>
              <option :value="8">8</option>
              <option :value="16">16</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="text-xs text-neutral-500">Name (optional)</label>
            <input v-model="name" type="text" class="w-full border rounded px-2 py-2" placeholder="e.g. Volleyball 2024 Bracket"/>
          </div>
        </div>

        <div class="mt-4">
          <div class="text-sm font-semibold mb-2">Pick {{ size }} teams ({{ selected.length }}/{{ size }})</div>
          <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
            <button
              v-for="t in filteredTeams"
              :key="t.id"
              type="button"
              @click="toggleTeam(t.id)"
              class="group border rounded p-3 flex flex-col items-center"
              :class="selected.includes(t.id) ? 'ring-2 ring-[#0b66ff]' : ''"
            >
              <img :src="logoPath(t.school?.logo_path)" class="h-10 w-10 rounded-full object-cover ring-1 ring-neutral-300"/>
              <div class="mt-2 text-xs text-center font-medium">
                <div class="truncate max-w-[120px]">{{ t.school?.name }}</div>
                <div class="text-neutral-500">{{ t.name }}</div>
              </div>
            </button>
          </div>
        </div>

        <div class="mt-4 flex justify-end">
          <button @click="createBracket" class="px-4 py-2 rounded bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold">Create Bracket</button>
        </div>
      </div>

      <!-- View/Manage Mode -->
      <div v-else>
        <div class="mb-4">
          <div class="text-xl font-bold">{{ competition.name }}</div>
          <div class="text-sm text-neutral-600">{{ competition.sport?.name }}</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-{{ Math.max(1, rounds.length) }} gap-4">
          <div v-for="(group, i) in rounds" :key="i" class="bg-white border rounded p-3">
            <div class="font-semibold mb-2">{{ fmtRoundName(i+1) }}</div>
            <div class="space-y-3">
              <div v-for="g in group" :key="g.id" class="border rounded p-3">
                <div class="flex items-center justify-between text-xs text-neutral-500 mb-2">
                  <div>{{ g.starts_at ? (new Date(g.starts_at)).toLocaleString() : 'TBA' }}</div>
                  <div>#{{ g.id }}</div>
                </div>
                <div class="flex items-center gap-3">
                  <img :src="logoPath(g.home_team?.school?.logo_path)" class="h-7 w-7 rounded-full ring-1 ring-neutral-300"/>
                  <div class="flex-1 font-medium truncate">{{ g.home_team?.school?.name || 'TBD' }}</div>
                  <div class="text-sm">{{ g.status==='final' ? g.home_score : '' }}</div>
                </div>
                <div class="text-center my-1 text-[11px] text-neutral-400">vs</div>
                <div class="flex items-center gap-3">
                  <img :src="logoPath(g.away_team?.school?.logo_path)" class="h-7 w-7 rounded-full ring-1 ring-neutral-300"/>
                  <div class="flex-1 font-medium truncate">{{ g.away_team?.school?.name || 'TBD' }}</div>
                  <div class="text-sm">{{ g.status==='final' ? g.away_score : '' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

