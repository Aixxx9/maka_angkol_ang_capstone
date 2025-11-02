<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
  title: '',
  body: '',
  cover: null,
  category: '',
  is_featured: false,
  published_at: '',
})

const preview = ref(null)

function handleImage(e) {
  const f = e.target.files?.[0]
  form.cover = f || null
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = f ? URL.createObjectURL(f) : null
}

function submitForm() {
  form.post(route('news.store'), {
    forceFormData: true,
  })
}
</script>

<template>
  <AppLayout title="Create News">
    <div class="min-h-screen bg-white text-[#111827] flex flex-col font-inter">
      <div class="max-w-[900px] mx-auto w-full px-4 py-10">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-3xl font-bold text-[#1f2937]">Create News</h1>
          <Link href="/news" class="text-[#0b66ff] hover:underline font-semibold">← Back</Link>
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
            <label class="block text-sm font-medium mb-1">Upload Image</label>
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
              Publish
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
