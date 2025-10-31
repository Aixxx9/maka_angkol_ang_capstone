<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  hero:   { type: Array, default: () => [] },
  latest: { type: Array, default: () => [] },
})

const page = usePage()
const user = page.props?.auth?.user ?? null

const srcOf     = (item) => item?.cover_image_path ?? item?.cover ?? null
const hasVideo  = (item) => !!(item?.embed_url || item?.video_url)
const sportName = (item) => item?.sport?.name ?? null

// ---------- HERO SLIDESHOW ----------
const slides = computed(() => props.hero ?? [])
const active = ref(0)
const intervalMs = 5000
let timer = null

function next() { if (!slides.value.length) return; active.value = (active.value + 1) % slides.value.length }
function prev() { if (!slides.value.length) return; active.value = (active.value - 1 + slides.value.length) % slides.value.length }
function goTo(i) { if (!slides.value.length) return; active.value = i % slides.value.length }
function start() { stop(); if (slides.value.length > 1) timer = setInterval(next, intervalMs) }
function stop() { if (timer) { clearInterval(timer); timer = null } }
onMounted(start)
onUnmounted(stop)

const right1 = computed(() => slides.value[(active.value + 1) % (slides.value.length || 1)])
const right2 = computed(() => slides.value[(active.value + 2) % (slides.value.length || 1)])

// ---------- Highlight Uploader ----------
const showUploader = ref(false)
const form = useForm({ title: '', caption: '', file: null, sport_id: '' })
function onPick(e){ const f = e.target.files?.[0]; if (f) form.file = f }
function submit(){
  form.post('/highlights', {
    forceFormData: true,
    onSuccess: () => {
      showUploader.value = false
      form.reset('title','caption','file','sport_id')
    }
  })
}

// ---------- NEWS COMPOSER (FB-like) ----------
const showComposer = ref(false)
const previewUrl = ref(null)
const newsForm = useForm({
  title: '',
  body: '',
  cover: null, // image file maps to NewsController 'cover'
})

function onCover(e){
  const f = e.target.files?.[0]
  newsForm.cover = f || null
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = f ? URL.createObjectURL(f) : null
}
function clearCover(){
  newsForm.cover = null
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = null
}
function postNews(){
  newsForm.post('/admin/news', {
    forceFormData: true,
    onSuccess: () => {
      showComposer.value = false
      clearCover()
      newsForm.reset('title','body','cover')
    }
  })
}
</script>

<template>
  <AppLayout>
    <!-- ===== HERO (AUTO SLIDESHOW) ===== -->
    <section class="max-w-[1200px] mx-auto px-4 mt-3">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
        <!-- Big left (slides) -->
        <article
          class="md:col-span-7 relative rounded-md overflow-hidden bg-gray-200"
          @mouseenter="stop"
          @mouseleave="start"
        >
          <div class="relative w-full h-[320px] md:h-[360px]">
            <template v-if="slides.length">
              <div
                v-for="(item, i) in slides"
                :key="i"
                class="absolute inset-0 transition-opacity duration-700"
                :class="i === active ? 'opacity-100' : 'opacity-0 pointer-events-none'"
              >
                <template v-if="hasVideo(item)">
                  <iframe
                    v-if="item.embed_url"
                    :src="item.embed_url"
                    class="w-full h-full"
                    frameborder="0"
                    allow="autoplay; encrypted-media; picture-in-picture"
                    allowfullscreen
                  />
                  <video
                    v-else
                    :src="item.video_url"
                    controls
                    class="w-full h-full object-cover"
                  />
                </template>
                <img
                  v-else
                  :src="srcOf(item)"
                  class="w-full h-full object-cover"
                  :alt="item.title"
                />

                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                <div class="absolute top-3 left-3 flex items-center gap-2">
                  <span class="text-xs font-semibold px-2 py-0.5 rounded bg-white/90 text-gray-900">
                    {{ hasVideo(item) ? 'Highlight' : 'Featured' }}
                  </span>
                  <span v-if="sportName(item)" class="text-xs font-semibold px-2 py-0.5 rounded bg-black/50 text-white backdrop-blur">
                    {{ sportName(item) }}
                  </span>
                  <span v-if="hasVideo(item) && item.duration" class="text-[11px] font-semibold px-1.5 py-0.5 rounded bg-black/60 text-white">
                    {{ item.duration }}
                  </span>
                </div>
                <div v-if="hasVideo(item)" class="absolute inset-0 grid place-items-center pointer-events-none">
                  <div class="h-14 w-14 rounded-full bg-white/90 grid place-items-center shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                  </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 p-4">
                  <a :href="`/news/${item.slug}`" class="block text-white text-xl md:text-2xl font-semibold">
                    {{ item.title }}
                  </a>
                  <p v-if="item.caption" class="text-white/90 text-sm mt-1">{{ item.caption }}</p>
                  <p v-else class="text-white/80 text-sm line-clamp-2">{{ item.excerpt }}</p>
                </div>
              </div>
            </template>
            <div v-else class="w-full h-full flex items-center justify-center text-gray-500">
              No featured post
            </div>
          </div>

          <!-- Controls -->
          <button
            type="button"
            class="absolute left-2 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full bg-black/40 text-white grid place-items-center hover:bg-black/60"
            @click="prev"
            aria-label="Previous"
          >‹</button>
          <button
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full bg-black/40 text-white grid place-items-center hover:bg-black/60"
            @click="next"
            aria-label="Next"
          >›</button>

          <!-- Dots -->
          <div class="absolute bottom-2 left-0 right-0 flex items-center justify-center gap-2">
            <button
              v-for="(s, i) in slides"
              :key="'dot-'+i"
              class="h-2.5 w-2.5 rounded-full transition"
              :class="i === active ? 'bg-white' : 'bg-white/50 hover:bg-white/70'"
              @click="goTo(i)"
              aria-label="Go to slide"
            />
          </div>
        </article>

        <!-- Two right stacked -->
        <div class="md:col-span-5 grid grid-rows-2 gap-3">
          <article class="relative rounded-md overflow-hidden bg-gray-200">
            <template v-if="right1">
              <template v-if="hasVideo(right1)">
                <iframe
                  v-if="right1.embed_url"
                  :src="right1.embed_url"
                  class="w-full h-[160px] md:h-[176px]"
                  frameborder="0"
                  allow="autoplay; encrypted-media; picture-in-picture"
                  allowfullscreen
                />
                <video
                  v-else
                  :src="right1.video_url"
                  controls
                  class="w-full h-[160px] md:h-[176px] object-cover"
                />
              </template>
              <img
                v-else
                :src="srcOf(right1)"
                class="w-full h-[160px] md:h-[176px] object-cover"
                :alt="right1.title"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
              <div class="absolute bottom-0 left-0 right-0 p-3">
                <a :href="`/news/${right1.slug}`" class="block text-white font-semibold">
                  {{ right1.title }}
                </a>
              </div>
            </template>
            <div v-else class="w-full h-[160px] md:h-[176px] flex items-center justify-center text-gray-500">Empty</div>
          </article>

          <article class="relative rounded-md overflow-hidden bg-gray-200">
            <template v-if="right2">
              <template v-if="hasVideo(right2)">
                <iframe
                  v-if="right2.embed_url"
                  :src="right2.embed_url"
                  class="w-full h-[160px] md:h-[176px]"
                  frameborder="0"
                  allow="autoplay; encrypted-media; picture-in-picture"
                  allowfullscreen
                />
                <video
                  v-else
                  :src="right2.video_url"
                  controls
                  class="w-full h-[160px] md:h-[176px] object-cover"
                />
              </template>
              <img
                v-else
                :src="srcOf(right2)"
                class="w-full h-[160px] md:h-[176px] object-cover"
                :alt="right2.title"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
              <div class="absolute bottom-0 left-0 right-0 p-3">
                <a :href="`/news/${right2.slug}`" class="block text-white font-semibold">
                  {{ right2.title }}
                </a>
              </div>
            </template>
            <div v-else class="w-full h-[160px] md:h-[176px] flex items-center justify-center text-gray-500">Empty</div>
          </article>
        </div>
      </div>

      <!-- Add Highlight button -->
      <div class="mt-4">
        <button
          type="button"
          class="bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold text-sm px-4 py-2 rounded-md shadow transition"
          @click="showUploader = true"
        >
          + Add Highlight (photo or video)
        </button>
      </div>

      <!-- Uploader Modal -->
      <div v-if="showUploader" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50">
        <div class="bg-white w-full max-w-md rounded-lg shadow-lg overflow-hidden">
          <div class="px-5 py-4 border-b"><div class="font-semibold">Add Highlight</div></div>
          <form @submit.prevent="submit" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Title</label>
              <input v-model="form.title" type="text" class="w-full border rounded px-3 py-2" placeholder="Highlight title" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Caption (optional)</label>
              <input v-model="form.caption" type="text" class="w-full border rounded px-3 py-2" placeholder="Short caption" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Sport (optional)</label>
              <input v-model="form.sport_id" type="text" class="w-full border rounded px-3 py-2" placeholder="Sport ID (if any)" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">File (image or video)</label>
              <input type="file" accept="image/*,video/*" @change="onPick" />
              <p class="text-xs text-gray-500 mt-1">Supported: JPG/PNG or MP4/WebM, stored locally.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
              <button type="button" class="px-3 py-2 rounded border" @click="showUploader=false">Cancel</button>
              <button type="submit" class="px-4 py-2 rounded bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold" :disabled="form.processing">
                {{ form.processing ? 'Uploading…' : 'Save' }}
              </button>
            </div>

            <div v-if="form.errors && Object.keys(form.errors).length" class="text-sm text-red-600">
              <div v-for="(msg, key) in form.errors" :key="key">{{ msg }}</div>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- ===== LATEST SPORTS NEWS (composer moved inside) ===== -->
    <section class="max-w-[1200px] mx-auto px-4 mt-6 mb-6">
      <div class="text-center">
        <h2 class="text-[22px] font-bold">Latest Sports News</h2>
        <p class="text-sm text-[#6b7280] mt-1">Stay updated with the latest developments in Philippine sports</p>
      </div>

      <!-- Composer inside Latest section, above the grid -->
      <div class="mt-5">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
          <div class="flex items-start gap-3">
            <img
              v-if="user"
              :src="user?.avatar_url ?? '/images/user.png'"
              class="h-10 w-10 rounded-full object-cover ring-1 ring-gray-200"
              alt="avatar"
            />
            <div class="flex-1">
              <button
                v-if="!showComposer"
                class="w-full text-left bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-full px-4 py-2"
                @click="showComposer = true"
              >
                {{ user ? `What's on your mind, ${user.name.split(' ')[0]}?` : "Share an update..." }}
              </button>

              <form v-else @submit.prevent="postNews" class="space-y-3 mt-2">
                <input
                  v-model="newsForm.title"
                  type="text"
                  class="w-full border rounded-lg px-3 py-2"
                  placeholder="Headline (e.g. Tigers clinch semis berth)"
                />
                <textarea
                  v-model="newsForm.body"
                  rows="4"
                  class="w-full border rounded-lg px-3 py-2"
                  placeholder="Write your story… (this becomes the article body)"
                />
                <div class="flex items-center justify-between">
                  <label class="inline-flex items-center gap-2 text-sm font-medium cursor-pointer">
                    <input type="file" accept="image/*" class="hidden" @change="onCover" />
                    <span class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 rounded px-3 py-1.5">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14m18 0H3m18 0-5.5-6.5-4 4.5-3-3L3 19"/></svg>
                      Add photo
                    </span>
                  </label>
                  <div class="flex items-center gap-2">
                    <button type="button" class="text-sm px-3 py-1.5 rounded border" @click="showComposer=false">Cancel</button>
                    <button
                      type="submit"
                      class="text-sm px-4 py-1.5 rounded bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold"
                      :disabled="newsForm.processing"
                    >
                      {{ newsForm.processing ? 'Posting…' : 'Post' }}
                    </button>
                  </div>
                </div>

                <!-- Image preview -->
                <div v-if="previewUrl" class="relative mt-2">
                  <img :src="previewUrl" class="max-h-64 rounded-lg object-cover border" alt="preview"/>
                  <button type="button" class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded" @click="clearCover">
                    Remove
                  </button>
                </div>

                <!-- Errors -->
                <div v-if="newsForm.errors && Object.keys(newsForm.errors).length" class="text-sm text-red-600">
                  <div v-for="(msg, key) in newsForm.errors" :key="key">{{ msg }}</div>
                </div>
              </form>

              <div v-if="!user && !showComposer" class="text-xs text-gray-500 mt-2">
                You may need to <Link href="/login" class="text-[#0b66ff] hover:underline">log in</Link> to post news.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Posts grid -->
      <div class="mt-5 grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-8">
          <div
            v-if="latest[0]"
            class="rounded-lg p-5 min-h-[180px] bg-[linear-gradient(90deg,#252b6a_0%,#c4242a_100%)] text-white"
          >
            <a :href="`/news/${latest[0].slug}`" class="block text-lg md:text-xl font-semibold">
              {{ latest[0].title }}
            </a>
            <p class="text-white/90 text-sm mt-1">{{ latest[0].excerpt }}</p>
            <div class="text-white/80 text-xs mt-3">{{ latest[0].published ?? '' }}</div>
          </div>
          <div v-else class="rounded-lg min-h-[180px] flex items-center justify-center border border-dashed border-gray-300 bg-white text-gray-500">
            No news yet
          </div>
        </div>

        <div class="md:col-span-4 space-y-3">
          <div
            v-for="(p, idx) in latest.slice(1, 4)"
            :key="p.id || idx"
            class="bg-white rounded-lg p-3 shadow-sm border border-gray-200"
          >
            <a :href="`/news/${p.slug}`" class="font-semibold text-[15px] leading-snug hover:text-[#0b66ff]">
              {{ p.title }}
            </a>
            <div class="text-xs text-[#6b7280] mt-1">{{ p.published ?? '' }}</div>
          </div>

          <!-- Fill placeholders to keep grid -->
          <div
            v-for="i in Math.max(0, 3 - (latest.length - 1))"
            :key="'ph-'+i"
            class="bg-white/60 rounded-lg p-3 border border-dashed border-gray-300 text-sm text-gray-500"
          >
            Empty
          </div>
        </div>
      </div>
    </section>
  </AppLayout>
</template>
