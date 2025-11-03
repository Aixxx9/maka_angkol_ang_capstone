<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  hero: Array,
  latest: Array
})

const destroyForm = useForm({})

// Auth/role helpers
const page = usePage()
const isAdmin = computed(() => {
  const roles = page.props.auth?.user?.roles || []
  return Array.isArray(roles) ? roles.includes('admin') || roles.includes('super-admin') : false
})

function destroyPost(slug) {
  if (confirm('Delete this news? This action cannot be undone.')) {
    destroyForm.delete(route('news.destroy', slug))
  }
}
</script>

<template>
  <AppLayout title="News">
    <div class="news-page min-h-screen bg-white text-[#111827] flex flex-col font-inter">
      <div class="max-w-[1200px] mx-auto w-full px-4 py-10">
        <!-- HEADER -->
        <div class="flex items-center justify-between mb-8">
          <h1 class="text-4xl font-extrabold tracking-tight">Latest News</h1>
          <Link
            v-if="isAdmin"
            :href="route('news.create')"
            class="bg-[#0b66ff] text-white px-5 py-2.5 rounded-lg shadow hover:bg-[#084dcc] transition"
          >
            + Create News
          </Link>
        </div>

        <!-- FEATURED LAYOUT (2-column NBA style) -->
        <div
          v-if="latest.length"
          class="news-featured grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12"
        >
          <!-- LEFT: MAIN FEATURED NEWS -->
          <div
            v-if="latest[0]"
            @click="$inertia.visit(route('news.show', latest[0].slug))"
            class="news-main relative cursor-pointer rounded-xl overflow-hidden shadow-lg group col-span-2"
          >
            <img
              :src="latest[0].cover"
              alt="featured"
              class="h-[450px] w-full object-cover group-hover:scale-105 transition-transform duration-700"
            />
            <!-- Edit/Delete overlay -->
            <div v-if="isAdmin" class="absolute top-3 right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition">
              <Link
                :href="route('news.edit', latest[0].slug)"
                @click.stop
                class="px-3 py-1.5 text-sm rounded-md bg-white/90 hover:bg-white text-[#111827] shadow"
              >Edit</Link>
              <button
                @click.stop="destroyPost(latest[0].slug)"
                class="px-3 py-1.5 text-sm rounded-md bg-red-600/90 hover:bg-red-600 text-white shadow"
              >Delete</button>
            </div>
            <div
              class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/80 via-black/50 to-transparent p-8"
            >
              <span class="text-sm text-white/80 uppercase">{{ latest[0].category }}</span>
              <h2
                class="text-3xl md:text-4xl font-bold text-white mb-2 group-hover:text-[#0b66ff] transition"
              >
                {{ latest[0].title }}
              </h2>
              <p class="text-white/80 text-base line-clamp-2">{{ latest[0].excerpt }}</p>
              <span class="text-xs text-white/60 mt-2">{{ latest[0].published }}</span>
            </div>
          </div>

          <!-- RIGHT: SMALL SIDE ARTICLES -->
          <div class="news-side flex flex-col gap-5">
            <div
              v-for="n in latest.slice(1, 4)"
              :key="n.slug"
              @click="$inertia.visit(route('news.show', n.slug))"
              class="news-card relative flex items-center gap-4 cursor-pointer group border-b border-gray-200 pb-3 hover:bg-gray-50 p-2 rounded-md transition"
            >
              <img
                :src="n.cover"
                alt="thumb"
                class="h-20 w-28 object-cover rounded-md flex-shrink-0 group-hover:opacity-90 transition"
              />
              <div class="flex flex-col justify-between">
                <h3
                  class="font-semibold text-[15px] text-[#111827] group-hover:text-[#0b66ff] leading-snug"
                >
                  {{ n.title }}
                </h3>
                <p class="text-xs text-[#4b5563] line-clamp-2">{{ n.excerpt }}</p>
                <span class="text-[11px] text-[#9ca3af] mt-1">{{ n.published }}</span>
              </div>
              <!-- Inline actions -->
              <div v-if="isAdmin" class="absolute top-2 right-2 hidden group-hover:flex gap-2">
                <Link :href="route('news.edit', n.slug)" @click.stop class="px-2 py-1 text-xs rounded bg-white border">Edit</Link>
                <button @click.stop="destroyPost(n.slug)" class="px-2 py-1 text-xs rounded bg-red-600 text-white">Delete</button>
              </div>
            </div>
          </div>
        </div>

        <!-- REST OF ARTICLES -->
        <div
          v-if="latest.length > 4"
          class="news-grid grid gap-8 sm:grid-cols-2 lg:grid-cols-3"
        >
          <div
            v-for="n in latest.slice(4)"
            :key="n.slug"
            @click="$inertia.visit(route('news.show', n.slug))"
            class="relative bg-white border border-neutral-200 hover:border-[#0b66ff]/40 rounded-lg shadow-sm hover:shadow-lg transition overflow-hidden cursor-pointer"
          >
            <img
              :src="n.cover"
              alt="news"
              class="h-48 w-full object-cover hover:scale-110 transition-transform duration-700"
            />
            <!-- Card actions -->
            <div v-if="isAdmin" class="absolute top-3 right-3 flex gap-2 opacity-0 hover:opacity-100">
              <Link :href="route('news.edit', n.slug)" @click.stop class="px-2 py-1 text-xs rounded bg-white border">Edit</Link>
              <button @click.stop="destroyPost(n.slug)" class="px-2 py-1 text-xs rounded bg-red-600 text-white">Delete</button>
            </div>
            <div class="p-4">
              <h2
                class="font-semibold text-lg mb-1 text-[#111827] line-clamp-2 hover:text-[#0b66ff] transition-colors"
              >
                {{ n.title }}
              </h2>
              <p class="text-sm text-[#4b5563] mb-3 line-clamp-3 leading-snug">{{ n.excerpt }}</p>
              <div class="flex items-center justify-between text-xs text-[#6b7280]">
                <span>{{ n.published }}</span>
                <span>👁️ {{ n.views }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-else class="text-center text-gray-500 py-20">
          No news yet. Click
          <span class="text-[#0b66ff] font-semibold">"Create News"</span> to add one.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.news-page {
  background-color: #fff;
}

.news-featured {
  margin-bottom: 3rem;
}

.news-card:hover h3 {
  color: #0b66ff;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
