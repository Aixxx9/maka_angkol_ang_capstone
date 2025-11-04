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
<div class="grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-black rounded-2xl overflow-hidden">
<div class="px-3 py-2 bg-red-600 text-white text-sm font-semibold flex items-center gap-2">
  <span class="relative flex h-2.5 w-2.5">
    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-60"></span>
    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
  </span>
  <span>{{ game.live_title || 'Live Now' }}</span>
  <button
    v-if="isAdmin && game.status === 'live'"
    @click="stopLive"
    class="ml-auto inline-flex items-center gap-1 bg-white/15 hover:bg-white/25 text-white px-2 py-1 rounded"
    title="End Live"
  >
    End Live
  </button>
</div>
<div class="aspect-video">
<iframe v-if="game.live_embed_url" :src="game.live_embed_url" class="w-full h-full" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
<div v-else class="w-full h-full flex items-center justify-center text-sm opacity-70">No live stream</div>
</div>
<div v-if="originalSourceUrl" class="bg-neutral-800 text-white text-xs px-3 py-2">If the video doesn’t load, <a :href="originalSourceUrl" target="_blank" class="underline">open on Facebook</a>.</div>
</div>
<div class="bg-neutral-900 rounded-2xl p-4">
<div class="flex items-center justify-between">
<div class="text-center">
<div class="text-xs opacity-60">HOME</div>
<div class="text-4xl font-bold">{{ game.home_score }}</div>
<div class="text-xs opacity-60">{{ homeName }}</div>
</div>
<div class="text-2xl">vs</div>
<div class="text-center">
<div class="text-xs opacity-60">AWAY</div>
<div class="text-4xl font-bold">{{ game.away_score }}</div>
<div class="text-xs opacity-60">{{ awayName }}</div>
</div>
</div>
<div v-if="user && (user.roles.includes('super-admin') || user.roles.includes('mod'))" class="mt-4 grid grid-cols-2 gap-2">
<button @click="score('home',1)" class="bg-amber-500 text-black rounded-lg py-2 font-semibold">+ Home</button>
<button @click="score('away',1)" class="bg-amber-500 text-black rounded-lg py-2 font-semibold">+ Away</button>
</div>
</div>
</div>
</AppLayout>
</template>
