<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  title: '',
  body: '',
  cover: null,
  category: '',
  is_featured: false,
  published_at: new Date().toISOString().slice(0,16), // HTML datetime-local
})

function submit() {
  form.post('/admin/news', { forceFormData: true })
}
</script>

<template>
  <div class="max-w-3xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Create Post</h1>
      <Link href="/admin/news" class="text-blue-600">Back to list</Link>
    </div>

    <form @submit.prevent="submit" class="space-y-5 bg-white p-5 rounded-lg border">
      <div>
        <label class="block text-sm font-medium mb-1">Title</label>
        <input v-model="form.title" class="w-full border rounded px-3 py-2" required />
        <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Category</label>
        <input v-model="form.category" class="w-full border rounded px-3 py-2" placeholder="Basketball, Boxing, ..." />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Body (HTML or long text)</label>
        <textarea v-model="form.body" rows="10" class="w-full border rounded px-3 py-2" required></textarea>
        <div v-if="form.errors.body" class="text-red-600 text-sm mt-1">{{ form.errors.body }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Cover image</label>
        <input type="file" @change="e => form.cover = e.target.files[0]" accept="image/*" />
        <div v-if="form.errors.cover" class="text-red-600 text-sm mt-1">{{ form.errors.cover }}</div>
      </div>

      <div class="flex items-center gap-6">
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.is_featured" />
          <span>Feature on homepage</span>
        </label>

        <div>
          <label class="block text-sm font-medium mb-1">Publish at</label>
          <input type="datetime-local" v-model="form.published_at" class="border rounded px-3 py-2" />
        </div>
      </div>

      <div class="pt-2">
        <button :disabled="form.processing" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
          {{ form.processing ? 'Saving…' : 'Publish' }}
        </button>
      </div>
    </form>
  </div>
</template>
