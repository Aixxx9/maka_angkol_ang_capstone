<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
  name: '',
  slug: '',
  description: '',
  type: 'team', // team | individual
  icon: null,
  stat_fields: [],
})

const preview = ref(null)

// Dynamic stat fields (pointing system)
const newField = () => ({ key: '', label: '', type: 'number', agg: 'sum' })
const statFields = ref([
  { key: 'points', label: 'Points', type: 'number', agg: 'sum' },
  { key: 'rebounds', label: 'Rebounds', type: 'number', agg: 'sum' },
  { key: 'assists', label: 'Assists', type: 'number', agg: 'sum' },
  { key: 'fg_percent', label: 'FG %', type: 'percent', agg: 'avg' },
])

function handleIconUpload(e) {
  const f = e.target.files?.[0]
  form.icon = f || null
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = f ? URL.createObjectURL(f) : null
}
function clearIcon() {
  form.icon = null
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = null
}
function submitForm() {
  form.stat_fields = statFields.value
  form.post('/sports', {
    forceFormData: true,
    onSuccess: () => {
      alert('✅ Sport added successfully!');
      form.reset();
      clearIcon();
      statFields.value = [
        { key: 'points', label: 'Points', type: 'number', agg: 'sum' },
        { key: 'rebounds', label: 'Rebounds', type: 'number', agg: 'sum' },
        { key: 'assists', label: 'Assists', type: 'number', agg: 'sum' },
        { key: 'fg_percent', label: 'FG %', type: 'percent', agg: 'avg' },
      ]
    },
  });
}

</script>

<template>
  <AppLayout title="Add Sport">
    <div class="min-h-screen bg-white text-[#111827] flex flex-col font-inter">
      <div class="max-w-[800px] mx-auto w-full px-4 py-10">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-3xl font-bold text-[#1f2937]">Add New Sport</h1>
          <Link href="/sports" class="text-[#0b66ff] hover:underline font-semibold">← Back to Sports</Link>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6 bg-white rounded-xl shadow p-6 border border-gray-100">
          <div>
            <label class="block text-sm font-medium mb-1">Sport Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]"
              placeholder="e.g. Basketball"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Slug</label>
            <input
              v-model="form.slug"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]"
              placeholder="e.g. basketball"
              required
            />
            <p class="text-xs text-gray-500 mt-1">Used in the URL (e.g. /sports/basketball)</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <select
              v-model="form.type"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]"
            >
              <option value="team">Team</option>
              <option value="individual">Individual</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="4"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]"
              placeholder="Describe the sport briefly..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-semibold mb-2">Stat Fields (Pointing System)</label>
            <p class="text-xs text-gray-500 mb-3">Define the stats tracked for this sport. Example: Points, Rebounds, Assists, FG %.</p>
            <div class="space-y-3">
              <div v-for="(f, idx) in statFields" :key="idx" class="grid grid-cols-12 gap-2 items-center">
                <input v-model="f.label" placeholder="Label (e.g. Points)" class="col-span-4 border rounded px-3 py-2 text-sm" />
                <input v-model="f.key" placeholder="Key (e.g. points)" class="col-span-3 border rounded px-3 py-2 text-sm" />
                <select v-model="f.type" class="col-span-2 border rounded px-2 py-2 text-sm">
                  <option value="number">Number</option>
                  <option value="percent">Percent</option>
                </select>
                <select v-model="f.agg" class="col-span-2 border rounded px-2 py-2 text-sm">
                  <option value="sum">Sum</option>
                  <option value="avg">Average</option>
                </select>
                <button type="button" @click="statFields.splice(idx, 1)" class="col-span-1 text-red-600 text-sm">✕</button>
              </div>
              <button type="button" @click="statFields.push(newField())" class="text-sm text-[#0b66ff]">+ Add Field</button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Icon / Logo</label>
            <input type="file" accept="image/*" @change="handleIconUpload" />
            <div v-if="preview" class="relative mt-3">
              <img :src="preview" class="h-20 w-20 rounded-xl object-cover ring-1 ring-gray-200" alt="preview"/>
              <button
                type="button"
                class="absolute -top-2 -right-2 text-xs bg-black/60 text-white rounded-full px-2 py-0.5"
                @click="clearIcon"
              >
                ✕
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Recommended: 512×512 PNG or JPG</p>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4">
            <Link
              href="/sports"
              class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50 transition"
            >
              Cancel
            </Link>
            <button
              type="submit"
              class="px-6 py-2 rounded-lg bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold transition"
            >
              Save Sport
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
