<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
})

const form = useForm({
  title: props.post.title || '',
  body: props.post.body || '',
  cover: null,
  category: props.post.category || '',
  is_featured: !!props.post.is_featured,
  published_at: props.post.published_at || '',
})

const preview = ref(props.post.cover || null)

function handleImage(e) {
  const f = e.target.files?.[0]
  form.cover = f || null
  if (preview.value && preview.value.startsWith('blob:')) URL.revokeObjectURL(preview.value)
  preview.value = f ? URL.createObjectURL(f) : props.post.cover || null
}

function submitForm() {
  form.put(route('news.update', props.post.slug), {
    forceFormData: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayout :title="`Edit: ${props.post.title}`">
    <div class="min-h-screen bg-white text-[#111827] flex flex-col font-inter">
      <div class="max-w-[900px] mx-auto w-full px-4 py-10">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-3xl font-bold text-[#1f2937]">Edit News</h1>
          <div class="flex items-center gap-3">
            <Link :href="route('news.show', props.post.slug)" class="text-[#0b66ff] hover:underline font-semibold">View</Link>
            <Link href="/news" class="text-gray-600 hover:underline">Back to list</Link>
          </div>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6 bg-white rounded-xl shadow p-6 border border-gray-100">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]"
              placeholder="Enter headline"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Category (optional)</label>
            <input
              v-model="form.category"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]"
              placeholder="e.g. Basketball"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Full Article Content</label>
            <textarea
              v-model="form.body"
              rows="8"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]"
              placeholder="Write the full article here..."
              required
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Cover Image</label>
            <input type="file" accept="image/*" @change="handleImage" />
            <div v-if="preview" class="mt-3">
              <img :src="preview" class="h-40 w-full object-cover rounded-lg shadow-sm" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Publish At (optional)</label>
              <input type="datetime-local" v-model="form.published_at" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0b66ff]/40 focus:border-[#0b66ff]" />
            </div>
            <div class="flex items-center gap-2 pt-6">
              <input id="is_featured" type="checkbox" v-model="form.is_featured" class="h-4 w-4" />
              <label for="is_featured" class="text-sm">Feature on homepage</label>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4">
            <Link href="/news" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50 transition">
              Cancel
            </Link>
            <button
              type="submit"
              class="px-6 py-2 rounded-lg bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold transition"
            >
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
  
</template>
