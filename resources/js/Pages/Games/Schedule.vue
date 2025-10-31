<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  games: { type: Array, default: () => [] }
})

// ---------- helpers ----------
const wd = ['SUN','MON','TUE','WED','THU','FRI','SAT']

function fmtDate(iso) {
  const d = new Date(iso)
  const day = wd[d.getDay()]
  const dd  = String(d.getDate()).padStart(2,'0')
  const mm  = String(d.getMonth()+1).padStart(2,'0')
  const yyyy= d.getFullYear()
  return `${day} ${dd}.${mm}.${yyyy}`
}
function fmtTime(iso) {
  const d = new Date(iso)
  const hh = String(d.getHours()).padStart(2,'0')
  const mm = String(d.getMinutes()).padStart(2,'0')
  return `${hh}:${mm}`
}
function abbr(name) {
  if (!name) return ''
  const cleaned = name.replace(/\./g,'').trim()
  const words = cleaned.split(/\s+/)
  if (words.length === 1 && words[0].length <= 4) return words[0].toUpperCase()
  const caps = words.map(w => w[0]).join('')
  if (caps.length >= 3) return caps.slice(0,3).toUpperCase()
  return cleaned.slice(0,3).toUpperCase()
}
function logoPath(p) {
  if (!p) return null
  return p.startsWith('/storage') ? p : `/storage/${p}`
}
function statusLabel(g) {
  if (g.status === 'final') return 'Result'
  if (g.status === 'live')  return 'Live'
  return 'Previous'
}

// how many placeholders to render to complete a row of 4
const placeholders = computed(() => Math.max(0, 4 - (props.games?.length ?? 0)))
</script>

<template>
  <AppLayout>
    <div class="max-w-[1200px] mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-5">
        <h1 class="text-3xl font-extrabold tracking-tight">NEXT MATCHES</h1>

        <!-- Add Schedule button -->
<Link
  href="/games/create"
  class="bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold text-sm px-4 py-2 rounded-md shadow transition"
>
  + Add Schedule
</Link>



      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- REAL GAME CARDS -->
        <div
          v-for="g in games"
          :key="g.id"
          class="bg-white rounded-md shadow-sm border border-neutral-200 overflow-hidden"
        >
          <!-- top strip -->
          <div class="flex items-center justify-between text-[13px] font-semibold tracking-wide px-4 py-2 bg-neutral-100">
            <div class="text-neutral-800">{{ fmtDate(g.starts_at) }}</div>
            <div class="text-neutral-600">{{ fmtTime(g.starts_at) }}</div>
          </div>

          <div class="flex">
            <!-- left: teams/score -->
            <div class="flex-1 p-4">
              <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                <!-- Home -->
                <div class="flex items-center justify-start gap-2">
                  <span class="text-sm font-bold text-neutral-800">{{ abbr(g.home_team?.school?.name) }}</span>
                  <img
                    v-if="g.home_team?.school?.logo_path"
                    :src="logoPath(g.home_team.school.logo_path)"
                    class="h-6 w-6 rounded-full object-contain"
                  />
                </div>

                <!-- Middle score -->
                <div class="text-center">
                  <template v-if="g.status === 'final' || g.status === 'live'">
                    <div class="flex items-center justify-center gap-2">
                      <span class="text-3xl font-extrabold">{{ g.home_score ?? 0 }}</span>
                      <span class="text-xl font-bold">-</span>
                      <span class="text-3xl font-extrabold">{{ g.away_score ?? 0 }}</span>
                    </div>
                  </template>
                  <template v-else>
                    <span class="text-sm font-medium text-neutral-500">vs</span>
                  </template>
                </div>

                <!-- Away -->
                <div class="flex items-center justify-end gap-2">
                  <img
                    v-if="g.away_team?.school?.logo_path"
                    :src="logoPath(g.away_team.school.logo_path)"
                    class="h-6 w-6 rounded-full object-contain"
                  />
                  <span class="text-sm font-bold text-neutral-800">{{ abbr(g.away_team?.school?.name) }}</span>
                </div>
              </div>

              <!-- hashtag -->
              <div class="mt-4">
                <a :href="`/games/${g.id}`" class="text-[15px] font-semibold text-[#6f39ff] hover:underline">
                  # {{ abbr(g.home_team?.school?.name) }}{{ abbr(g.away_team?.school?.name) }}
                </a>
              </div>
            </div>

            <!-- right: actions column -->
            <div class="w-px bg-neutral-200"></div>
            <div class="w-40 p-4 space-y-3">
              <div class="flex items-center gap-2 text-sm cursor-pointer">
                <span class="font-semibold">Compare</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <span class="font-semibold">{{ statusLabel(g) }}</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <span class="font-semibold">TV</span>
              </div>
            </div>
          </div>

          <!-- bottom ribbon -->
          <a :href="`/games/${g.id}`"
             class="block text-center text-white text-[15px] font-bold tracking-wide bg-[#f2473f] hover:bg-[#e23a32] py-3">
            Where to watch PRISAA
          </a>
        </div>

        <!-- PLACEHOLDER CARDS -->
        <div
          v-for="i in placeholders"
          :key="'ph-'+i"
          class="bg-white rounded-md shadow-sm border border-neutral-200 overflow-hidden"
        >
          <div class="flex items-center justify-between px-4 py-2 bg-neutral-100">
            <div class="h-4 w-28 bg-neutral-200 animate-pulse rounded"></div>
            <div class="h-4 w-10 bg-neutral-200 animate-pulse rounded"></div>
          </div>
          <div class="flex">
            <div class="flex-1 p-4">
              <div class="h-6 w-20 bg-neutral-200 animate-pulse rounded mb-2"></div>
              <div class="h-6 w-28 bg-neutral-200 animate-pulse rounded mb-2"></div>
            </div>
          </div>
          <div class="py-3 bg-[#f2473f]/70 text-transparent">.</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
