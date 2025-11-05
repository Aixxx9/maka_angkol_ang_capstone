<script setup>
import { ref } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// ✅ Props from controller
const props = defineProps({
  sports: { type: Array, default: () => [] },
  schoolOptions: { type: Array, default: () => [] },
})

// ✅ form state
const form = useForm({
  first_name: '',
  last_name: '',
  number: '',
  position: '',
  team_id: '',
  sport_id: '',
  school_id: '',
  avatar: null,
})

const avatarPreview = ref(null)

// ✅ handle image preview
function onPickAvatar(e) {
  const file = e.target.files?.[0]
  form.avatar = file || null
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarPreview.value = file ? URL.createObjectURL(file) : null
}

function clearAvatar() {
  form.avatar = null
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarPreview.value = null
}

// ✅ submit
function savePlayer() {
  form.post('/athletes', {
    forceFormData: true,
    onSuccess: () => {
      form.reset()
      clearAvatar()
      router.visit('/athletes')
    },
  })
}
</script>

<template>
  <AppLayout>
    <section class="max-w-[800px] mx-auto px-4 py-8">
      <!-- HEADER -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-neutral-900">Add Player</h1>
        <Link
          href="/athletes"
          class="text-sm text-[#0b66ff] hover:underline font-medium"
        >
          ← Back to Athletes
        </Link>
      </div>

      <!-- FORM -->
      <form @submit.prevent="savePlayer" class="space-y-6 bg-white rounded-2xl shadow p-6 border border-neutral-200">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">First Name</label>
            <input
              v-model="form.first_name"
              type="text"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Last Name</label>
            <input
              v-model="form.last_name"
              type="text"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
              required
            />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Number</label>
            <input
              v-model="form.number"
              type="text"
              placeholder="#"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Position</label>
            <input
              v-model="form.position"
              type="text"
              placeholder="PG / LW / etc."
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Team ID (optional)</label>
            <input
              v-model="form.team_id"
              type="number"
              placeholder="Team ID"
              class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
            />
          </div>
        </div>

        <!-- SPORT -->
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
        </div>

        <!-- SCHOOL -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-1">School</label>
          <select
            v-model="form.school_id"
            required
            class="w-full rounded-lg border border-neutral-300 px-3 py-2 shadow-sm bg-white focus:border-[#0b66ff] focus:ring-2 focus:ring-[#0b66ff]/30"
          >
            <option value="" disabled>Select a school…</option>
            <option v-for="sch in props.schoolOptions" :key="sch.id" :value="sch.id">{{ sch.name }}</option>
          </select>
        </div>

        <!-- AVATAR -->
        <div>
          <label class="block text-sm font-medium text-neutral-700 mb-1">Avatar (optional)</label>
          <input
            type="file"
            accept="image/*"
            @change="onPickAvatar"
            class="block w-full text-sm text-neutral-700 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-neutral-100 file:text-neutral-700 hover:file:bg-neutral-200"
          />
          <div v-if="avatarPreview" class="relative mt-3">
            <img :src="avatarPreview" alt="preview" class="h-24 w-24 rounded-full object-cover ring-2 ring-white shadow-sm" />
            <button
              type="button"
              class="absolute top-1 left-28 text-xs px-2 py-1 rounded bg-black/60 text-white hover:bg-black/70"
              @click="clearAvatar"
            >
              Remove
            </button>
          </div>
        </div>

        <!-- ACTIONS -->
        <div class="flex justify-end gap-3 pt-2">
          <Link
            href="/athletes"
            class="px-3 py-2 rounded-lg border border-neutral-300 bg-white hover:bg-neutral-50"
          >
            Cancel
          </Link>
          <button
            type="submit"
            class="px-4 py-2 rounded-lg bg-[#0b66ff] text-white font-semibold shadow-sm hover:bg-[#0856d6] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0b66ff]/60"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Saving…' : 'Save Player' }}
          </button>
        </div>

        <!-- ERRORS -->
        <div v-if="form.errors && Object.keys(form.errors).length" class="text-sm text-red-600 mt-2">
          <div v-for="(msg, key) in form.errors" :key="key">{{ msg }}</div>
        </div>
      </form>
    </section>
  </AppLayout>
</template>
