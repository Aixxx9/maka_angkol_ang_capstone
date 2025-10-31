<script setup>
import { Link, usePage } from '@inertiajs/vue3'
const props = defineProps({ posts: Object })
const { flash } = usePage().props
</script>

<template>
  <div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">News Desk</h1>
      <Link href="/admin/news/create" class="px-3 py-2 rounded bg-blue-600 text-white">New Post</Link>
    </div>

    <p v-if="flash?.success" class="mb-4 text-green-600">{{ flash.success }}</p>

    <div class="bg-white rounded-lg border">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="p-3 text-left">Title</th>
            <th class="p-3">Category</th>
            <th class="p-3">Featured</th>
            <th class="p-3">Published</th>
            <th class="p-3">Views</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in posts.data" :key="p.id" class="border-b">
            <td class="p-3 text-left">
              <Link :href="`/news/${p.slug}`" class="font-medium hover:underline">{{ p.title }}</Link>
            </td>
            <td class="p-3 text-center">{{ p.category ?? '—' }}</td>
            <td class="p-3 text-center">
              <span :class="p.is_featured ? 'text-green-700' : 'text-gray-400'">
                {{ p.is_featured ? 'Yes' : 'No' }}
              </span>
            </td>
            <td class="p-3 text-center">{{ new Date(p.published_at).toLocaleString() }}</td>
            <td class="p-3 text-center">{{ p.views_count?.toLocaleString?.() ?? p.views_count }}</td>
            <td class="p-3 text-right">
              <Link :href="`/admin/news/${p.id}/edit`" class="px-2 py-1 rounded bg-gray-200 hover:bg-gray-300 mr-2">Edit</Link>
              <Link :href="`/admin/news/${p.id}`" method="delete" as="button"
                    class="px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700"
                    confirm="Delete this post?">Delete</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination (simple) -->
    <div class="mt-4 flex gap-2">
      <Link v-for="link in posts.links" :key="link.url || link.label"
            :href="link.url || '#'" v-html="link.label"
            class="px-2 py-1 rounded border"
            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white'"/>
    </div>
  </div>
</template>
