
<script setup>
import { Link, useForm } from '@inertiajs/vue3'
const props = defineProps({ post: Object })

const form = useForm({
  title: props.post.title,
  body: props.post.body,
  cover: null,
  category: props.post.category ?? '',
  is_featured: !!props.post.is_featured,
  published_at: props.post.published_at
    ? new Date(props.post.published_at).toISOString().slice(0,16)
    : new Date().toISOString().slice(0,16),
})

function submit() {
  form.post(`/admin/news/${props.post.id}?_method=PUT`, { forceFormData: true })
}
</script>

<template>
  <div class="max-w-3xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Edit Post</h1>
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
        <input v-model="form.category" class="w-full border rounded px-3 py-2" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Body</label>
        <textarea v-model="form.body" rows="10" class="w-full border rounded px-3 py-2" required></textarea>
        <div v-if="form.errors.body" class="text-red-600 text-sm mt-1">{{ form.errors.body }}</div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Replace cover (optional)</label>
        <input type="file" @change="e => form.cover = e.target.files[0]" accept="image/*" />
        <div v-if="form.errors.cover" class="text-red-600 text-sm mt-1">{{ form.errors.cover }}</div>
        <div v-if="post.cover_image_path" class="mt-2">
          <img :src="`/storage/${post.cover_image_path}`" class="h-24 rounded" />
        </div>
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
          {{ form.processing ? 'Saving…' : 'Update' }}
        </button>
      </div>
    </form>
  </div>
</template>
