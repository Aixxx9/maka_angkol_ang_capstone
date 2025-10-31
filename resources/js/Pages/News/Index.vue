<template>
  <AppLayout title="News">
    <div class="min-h-screen bg-[#f7f8fc] text-[#111827] flex flex-col font-inter">
      <div class="max-w-[1200px] mx-auto w-full px-4 py-8">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-3xl font-bold text-[#1f2937]">Latest News</h1>
          <Link
            href="/news/create"
            class="bg-[#0b66ff] text-white px-4 py-2 rounded hover:bg-[#084dcc] transition"
          >
            + Create News
          </Link>
        </div>

        <!-- News Grid -->
        <div v-if="newsList.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="n in newsList"
            :key="n.id"
            class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden cursor-pointer"
          >
            <img :src="n.image" alt="news" class="h-40 w-full object-cover" />
            <div class="p-4">
              <h2 class="font-semibold text-lg mb-1">{{ n.title }}</h2>
              <p class="text-sm text-[#4b5563] mb-2 line-clamp-2">{{ n.summary }}</p>
              <span class="text-xs text-[#6b7280]">{{ n.date }}</span>
            </div>
          </div>
        </div>

        <div v-else class="text-center text-gray-500 py-10">
          No news yet. Click <span class="text-[#0b66ff] font-semibold">"Create News"</span> to add one.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const newsList = ref([])

onMounted(() => {
  const saved = localStorage.getItem('newsList')
  newsList.value = saved ? JSON.parse(saved) : []
})
</script>
