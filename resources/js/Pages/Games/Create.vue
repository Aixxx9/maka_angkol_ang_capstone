<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  teams:  { type: Array, default: () => [] },   // from GameController@create
  sports: { type: Array, default: () => [] }
})

const form = useForm({
  sport_id: '',
  home_team_id: '',
  away_team_id: '',
  starts_at: '',
  venue: ''
})

function submit() {
  form.post(route('games.store'))
}
</script>

<template>
  <AppLayout>
    <div class="max-w-[900px] mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-extrabold tracking-tight">Create Game Schedule</h1>
        <Link href="/schedule" class="text-sm text-[#0b66ff] hover:underline">← Back to Schedule</Link>
      </div>

      <form @submit.prevent="submit" class="bg-white border border-neutral-200 rounded-md p-5 space-y-5">
        <!-- Sport -->
        <div>
          <label class="block text-sm font-medium mb-1">Sport</label>
          <select v-model="form.sport_id" class="w-full border rounded-md p-2">
            <option value="" disabled>Select sport…</option>
            <option v-for="s in sports" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <div v-if="form.errors.sport_id" class="text-red-600 text-sm mt-1">{{ form.errors.sport_id }}</div>
        </div>

        <!-- Teams -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Home Team</label>
            <select v-model="form.home_team_id" class="w-full border rounded-md p-2">
              <option value="" disabled>Select home team…</option>
              <option v-for="t in props.teams" :key="t.id" :value="t.id">
                {{ t.school?.name }} — {{ t.sport?.name }} {{ t.season ? `(${t.season})` : '' }}
              </option>
            </select>
            <div v-if="form.errors.home_team_id" class="text-red-600 text-sm mt-1">{{ form.errors.home_team_id }}</div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Away Team</label>
            <select v-model="form.away_team_id" class="w-full border rounded-md p-2">
              <option value="" disabled>Select away team…</option>
              <option v-for="t in props.teams" :key="t.id" :value="t.id">
                {{ t.school?.name }} — {{ t.sport?.name }} {{ t.season ? `(${t.season})` : '' }}
              </option>
            </select>
            <div v-if="form.errors.away_team_id" class="text-red-600 text-sm mt-1">{{ form.errors.away_team_id }}</div>
          </div>
        </div>

        <!-- Date/Time & Venue -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Starts At</label>
            <input
              type="datetime-local"
              v-model="form.starts_at"
              class="w-full border rounded-md p-2"
            />
            <div v-if="form.errors.starts_at" class="text-red-600 text-sm mt-1">{{ form.errors.starts_at }}</div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Venue (optional)</label>
            <input
              type="text"
              v-model="form.venue"
              placeholder="e.g., PRISAA Dome"
              class="w-full border rounded-md p-2"
            />
            <div v-if="form.errors.venue" class="text-red-600 text-sm mt-1">{{ form.errors.venue }}</div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-[#0b66ff] hover:bg-[#084dcc] disabled:opacity-60 text-white font-semibold text-sm px-4 py-2 rounded-md shadow transition"
          >
            Save Schedule
          </button>
          <Link href="/schedule" class="text-sm text-neutral-600 hover:underline">Cancel</Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
