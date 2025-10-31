<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
const props = defineProps({ game: Object })
const page = usePage()
const user = page.props.auth.user
const state = ref(props.game)


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
</script>
<template>
<AppLayout>
<div class="grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 bg-black rounded-2xl overflow-hidden aspect-video">
<iframe v-if="game.live_embed_url" :src="game.live_embed_url" class="w-full h-full" allowfullscreen></iframe>
<div v-else class="w-full h-full flex items-center justify-center text-sm opacity-70">No live stream</div>
</div>
<div class="bg-neutral-900 rounded-2xl p-4">
<div class="flex items-center justify-between">
<div class="text-center">
<div class="text-xs opacity-60">HOME</div>
<div class="text-4xl font-bold">{{ game.home_score }}</div>
<div class="text-xs opacity-60">{{ game.home_team.school.name }}</div>
</div>
<div class="text-2xl">vs</div>
<div class="text-center">
<div class="text-xs opacity-60">AWAY</div>
<div class="text-4xl font-bold">{{ game.away_score }}</div>
<div class="text-xs opacity-60">{{ game.away_team.school.name }}</div>
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