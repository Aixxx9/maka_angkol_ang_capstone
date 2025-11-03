<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
})

const delForm = useForm({})

// role helpers
const page = usePage()
const isAdmin = computed(() => {
  const roles = page.props.auth?.user?.roles || []
  return Array.isArray(roles) ? roles.includes('admin') || roles.includes('super-admin') : false
})

function destroyPost() {
  if (confirm('Delete this news article? This cannot be undone.')) {
    delForm.delete(route('news.destroy', props.post.slug))
  }
}
</script>

<template>
  <AppLayout :title="props.post.title || 'News Details'">
    <div class="min-h-screen bg-white text-[#111827]">
      <div class="max-w-[900px] mx-auto px-4 py-10">
        <img :src="props.post.cover" alt="news" class="rounded-xl w-full h-[400px] object-cover shadow-lg mb-6" />
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-4xl font-bold mb-2">{{ props.post.title }}</h1>
            <p class="text-gray-500 text-sm mb-4">{{ props.post.published }}</p>
          </div>
          <div class="flex items-center gap-2" v-if="isAdmin">
            <Link :href="route('news.edit', props.post.slug)" class="px-3 py-1.5 rounded-md bg-blue-600 text-white hover:bg-blue-700">Edit</Link>
            <button @click="destroyPost" class="px-3 py-1.5 rounded-md bg-red-600 text-white hover:bg-red-700">Delete</button>
          </div>
        </div>
        <div class="prose max-w-none" v-html="props.post.body"></div>
      </div>
    </div>
  </AppLayout>
</template>
