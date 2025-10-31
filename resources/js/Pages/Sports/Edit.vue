<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  sport: Object,
})

const form = useForm({
  name: props.sport.name || '',
  slug: props.sport.slug || '',
  type: props.sport.type || 'team',
  description: props.sport.description || '',
  icon: null,
})

const preview = ref(props.sport.icon_path ? `/storage/${props.sport.icon_path}` : null)

function handleIconUpload(e) {
  const file = e.target.files?.[0]
  if (file) {
    form.icon = file
    preview.value = URL.createObjectURL(file)
  }
}

function submitForm() {
  form.post(`/sports/${props.sport.id}?_method=PUT`, {
    forceFormData: true,
  })
}
</script>

<template>
  <AppLayout title="Edit Sport">
    <div class="min-h-screen bg-white text-[#111827] flex flex-col font-inter">
      <div class="max-w-[800px] mx-auto w-full px-4 py-10">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-3xl font-bold text-[#1f2937]">Edit Sport</h1>
          <Link href="/sports" class="text-[#0b66ff] hover:underline font-semibold">← Back</Link>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6 bg-white rounded-xl shadow p-6 border border-gray-100">
          <div>
            <label class="block text-sm font-medium mb-1">Sport Name</label>
            <input v-model="form.name" type="text" class="w-full border rounded-lg px-4 py-2" required />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Slug</label>
            <input v-model="form.slug" type="text" class="w-full border rounded-lg px-4 py-2" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <select v-model="form.type" class="w-full border rounded-lg px-4 py-2">
              <option value="team">Team</option>
              <option value="individual">Individual</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full border rounded-lg px-4 py-2"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Icon / Logo</label>
            <input type="file" accept="image/*" @change="handleIconUpload" />
            <div v-if="preview" class="mt-3">
              <img :src="preview" class="h-20 w-20 rounded-xl object-cover border" alt="Preview" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3">
            <Link href="/sports" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50 transition">
              Cancel
            </Link>
            <button type="submit" class="px-6 py-2 bg-[#0b66ff] hover:bg-[#084dcc] text-white rounded-lg font-semibold">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
