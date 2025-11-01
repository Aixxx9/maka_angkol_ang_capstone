<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

// ✅ Props from GameController@create
const props = defineProps({
  teams: { type: Array, default: () => [] },
  sports: { type: Array, default: () => [] },
})

// ✅ Form state
const form = useForm({
  sport_id: '',
  home_team_id: '',
  away_team_id: '',
  starts_at: '',
  venue: '',
  participants: [],
})

// ✅ Deduplicate teams by school name
const uniqueSchools = computed(() => {
  const seen = new Set()
  const filtered = []
  for (const t of props.teams) {
    if (t.school && !seen.has(t.school.name)) {
      seen.add(t.school.name)
      filtered.push(t)
    }
  }
  return filtered
})

const selectedIds = computed(() => {
  const ids = new Set()
  if (form.home_team_id) ids.add(Number(form.home_team_id))
  if (form.away_team_id) ids.add(Number(form.away_team_id))
  for (const v of form.participants) if (v) ids.add(Number(v))
  return ids
})

const availableSchools = computed(() => {
  // prevent duplicate selection
  return uniqueSchools.value.filter(t => !selectedIds.value.has(Number(t.id)))
})

// ✅ Submit
function submit() {
  // ensure participants excludes blanks and duplicates and does not include home/away
  const extras = Array.from(new Set((form.participants || []).filter(Boolean).map(Number)))
    .filter(id => id !== Number(form.home_team_id) && id !== Number(form.away_team_id))
  form.participants = extras
  form.post(route('games.store'), {
    onSuccess: () => form.reset(),
  })
}
</script>

<template>
  <AppLayout>
    <section class="max-w-[900px] mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-neutral-900">Create Game Schedule</h1>
        <Link
          href="/schedule"
          class="text-sm text-[#0b66ff] hover:underline font-medium"
        >
          ← Back to Schedule
        </Link>
      </div>

      <!-- Form -->
      <form
        @submit.prevent="submit"
        class="bg-white rounded-2xl shadow p-6 border border-neutral-200 space-y-6"
      >
        <!-- Sport -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-1">Sport</label>
          <select
            v-model="form.sport_id"
            required
            class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm bg-white focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
          >
            <option value="" disabled>Select a sport…</option>
            <option v-for="s in props.sports" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <p v-if="form.errors.sport_id" class="text-red-600 text-sm mt-1">{{ form.errors.sport_id }}</p>
        </div>

      <!-- Teams -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Home Team -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Home Team</label>
            <select
              v-model="form.home_team_id"
              required
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm bg-white focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            >
              <option value="" disabled>Select home team…</option>
              <!-- ✅ Show unique school names only -->
              <option
                v-for="t in uniqueSchools"
                :key="t.school.id"
                :value="t.id"
              >
                {{ t.school?.name }}
              </option>
            </select>
            <p v-if="form.errors.home_team_id" class="text-red-600 text-sm mt-1">{{ form.errors.home_team_id }}</p>
      </div>

      <!-- Additional Schools (optional, multi) -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-medium text-neutral-700">Additional Schools</label>
          <button type="button" @click="form.participants.push('')" class="text-sm text-[#0b66ff] hover:underline">+ Add School</button>
        </div>
        <div v-if="!form.participants.length" class="text-xs text-neutral-500">No additional schools. Click “+ Add School” to include more participants.</div>
        <div v-for="(p, idx) in form.participants" :key="idx" class="flex items-center gap-2">
          <select v-model="form.participants[idx]" class="flex-1 rounded-lg border border-neutral-300 px-3 py-2 shadow-sm bg-white focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30">
            <option value="" disabled>Select school…</option>
            <option
              v-for="t in uniqueSchools"
              :key="t.id + '-' + idx"
              :value="t.id"
              :disabled="selectedIds.has(Number(t.id)) && Number(form.participants[idx]) !== Number(t.id)"
            >
              {{ t.school?.name }}
            </option>
          </select>
          <button type="button" @click="form.participants.splice(idx,1)" class="text-xs text-red-600">Remove</button>
        </div>
        <p v-if="form.errors['participants'] || form.errors['participants.*']" class="text-red-600 text-sm">
          {{ form.errors['participants'] || form.errors['participants.*'] }}
        </p>
      </div>

          <!-- Away Team -->
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Away Team</label>
            <select
              v-model="form.away_team_id"
              required
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm bg-white focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            >
              <option value="" disabled>Select away team…</option>
              <!-- ✅ Show unique school names only -->
              <option
                v-for="t in uniqueSchools"
                :key="t.school.id + '-away'"
                :value="t.id"
              >
                {{ t.school?.name }}
              </option>
            </select>
            <p v-if="form.errors.away_team_id" class="text-red-600 text-sm mt-1">{{ form.errors.away_team_id }}</p>
          </div>
        </div>

        <!-- Starts At + Venue -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Start Date & Time</label>
            <input
              type="datetime-local"
              v-model="form.starts_at"
              required
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            />
            <p v-if="form.errors.starts_at" class="text-red-600 text-sm mt-1">{{ form.errors.starts_at }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Venue (optional)</label>
            <input
              type="text"
              v-model="form.venue"
              placeholder="e.g. PRISAA Dome"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            />
            <p v-if="form.errors.venue" class="text-red-600 text-sm mt-1">{{ form.errors.venue }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <Link
            href="/schedule"
            class="px-3 py-2 rounded-lg border border-neutral-300 bg-white hover:bg-neutral-50"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 rounded-lg bg-[#0b66ff] text-white font-semibold shadow-sm hover:bg-[#0856d6] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0b66ff]/60 disabled:opacity-60"
          >
            {{ form.processing ? 'Saving…' : 'Save Schedule' }}
          </button>
        </div>
      </form>
    </section>
  </AppLayout>
</template>
