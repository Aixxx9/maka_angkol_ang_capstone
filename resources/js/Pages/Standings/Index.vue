<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  sports: { type: Array, default: () => [] },
  sportId: { type: [Number, String, null], default: null },
  standings: { type: Array, default: () => [] },
})

function logoPath(p){ if(!p) return '/images/default-logo.png'; return p.startsWith('/storage')?p:`/storage/${p}` }

function onChange(e){
  const sport_id = e.target.value
  router.get('/standings', { sport_id: sport_id || undefined }, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
  <AppLayout>
    <div class="max-w-[1000px] mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-5">
        <h1 class="text-3xl font-extrabold tracking-tight">STANDINGS</h1>
        <select :value="sportId || ''" @change="onChange" class="border rounded px-2 py-2">
          <option value="">Select Sport</option>
          <option v-for="s in sports" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>

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
              <td colspan="6" class="px-4 py-6 text-center text-neutral-500">No finalized games yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

