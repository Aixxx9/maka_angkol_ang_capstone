<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue'
const props = defineProps({ game: Object })
const page = usePage()
const user = page.props.auth.user
const state = ref(props.game)
const isAdmin = computed(() => {
  const roles = page.props.auth?.user?.roles || []
  return Array.isArray(roles) ? roles.includes('admin') || roles.includes('super-admin') : false
})


async function refresh(){
const res = await fetch(`/games/${state.value.id}`)
const html = await res.text()
// quick-and-dirty: re-fetch via JSON endpoint would be cleaner; for brevity we reload via inertia router
router.reload({ only: ['game'] })
}


async function score(team, value=1){
await fetch(`/games/${state.value.id}/events`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}, body: JSON.stringify({team, type:'score', value}) })
refresh()
}


onMounted(()=>{ setInterval(refresh, 5000) })

const originalSourceUrl = computed(() => {
  const u = state.value?.live_embed_url || ''
  try {
    if (u.includes('facebook.com/plugins/')) {
      const url = new URL(u)
      const href = url.searchParams.get('href')
      return href ? decodeURIComponent(href) : null
    }
  } catch (e) {}
  return null
})
function stopLive() {
  if (!isAdmin.value) return
  if (!confirm('End live now?')) return
  router.put(`/live/${state.value.id}/stop`)
}

// Fixed-size, ratio-selectable container that stays within viewport
const ratio = ref('16:9') // '16:9' | '9:16' | '1:1'
const ratioStyle = computed(() => {
  let w = 16, h = 9
  if (ratio.value === '9:16') { w = 9; h = 16 }
  if (ratio.value === '1:1') { w = 1; h = 1 }
  return {
    width: `min(100%, calc(70vh * ${w} / ${h}))`,
    height: `min(calc(100vw * ${h} / ${w}), 70vh)`,
  }
})
const homeName = computed(() => {
  return (
    state.value?.home_team?.school?.name ||
    state.value?.home_team?.name ||
    state.value?.homeTeam?.school?.name ||
    state.value?.homeTeam?.name ||
    'Home'
  )
})
const awayName = computed(() => {
  return (
    state.value?.away_team?.school?.name ||
    state.value?.away_team?.name ||
    state.value?.awayTeam?.school?.name ||
    state.value?.awayTeam?.name ||
    'Away'
  )
})
</script>
<template>
<AppLayout :title="game.live_title || 'Live'">
<div class="max-w-[1200px] mx-auto w-full px-4 py-6 grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-sm">
<div class="px-4 py-2 bg-red-600 text-white text-sm font-semibold flex items-center gap-2">
  <span class="relative flex h-2.5 w-2.5">
    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-60"></span>
    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
  </span>
  <span>{{ game.live_title || 'Live Now' }}</span>
  <div class="ml-auto flex items-center gap-2">
    <label class="text-xs text-white/80">Ratio</label>
    <select v-model="ratio" class="text-xs bg-white/10 hover:bg-white/20 border border-white/20 rounded px-2 py-1">
      <option value="16:9">16:9</option>
      <option value="9:16">9:16</option>
      <option value="1:1">1:1</option>
    </select>
    <button
      v-if="isAdmin && game.status === 'live'"
      @click="stopLive"
      class="inline-flex items-center gap-1 bg-white/15 hover:bg-white/25 text-white px-2 py-1 rounded"
      title="End Live"
    >
      End Live
    </button>
  </div>
</div>
<div class="relative mx-auto bg-black overflow-hidden" :style="ratioStyle">
  <iframe v-if="game.live_embed_url" :src="game.live_embed_url" class="absolute inset-0 w-full h-full" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
  <div v-else class="absolute inset-0 w-full h-full flex items-center justify-center text-sm opacity-70">No live stream</div>
  </div>
<div v-if="originalSourceUrl" class="bg-neutral-50 border-t border-neutral-200 text-xs px-4 py-2 text-neutral-600">If the video doesn’t load, <a :href="originalSourceUrl" target="_blank" class="text-[#0b66ff] underline">open on Facebook</a>.</div>
</div>
<div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-sm">
<div class="flex items-center justify-between">
<div class="text-center flex-1">
<div class="text-xs text-neutral-500 tracking-wide">HOME</div>
<div class="text-5xl font-extrabold text-neutral-900 leading-tight">{{ game.home_score }}</div>
<div class="text-sm text-neutral-600 mt-1">{{ homeName }}</div>
</div>
<div class="px-4 text-2xl font-bold text-neutral-700">vs</div>
<div class="text-center flex-1">
<div class="text-xs text-neutral-500 tracking-wide">AWAY</div>
<div class="text-5xl font-extrabold text-neutral-900 leading-tight">{{ game.away_score }}</div>
<div class="text-sm text-neutral-600 mt-1">{{ awayName }}</div>
</div>
</div>
<div v-if="user && (user.roles.includes('super-admin') || user.roles.includes('mod'))" class="mt-5 grid grid-cols-2 gap-3">
<button @click="score('home',1)" class="bg-amber-500 hover:bg-amber-400 text-black rounded-lg py-2 font-semibold transition">+ Home</button>
<button @click="score('away',1)" class="bg-amber-500 hover:bg-amber-400 text-black rounded-lg py-2 font-semibold transition">+ Away</button>
</div>
</div>
</div>
</AppLayout>
</template>
