<template>
  <AppLayout title="Video Highlights">
    <div class="min-h-screen bg-white text-[#111827] font-inter flex flex-col">
      <div class="max-w-[1300px] mx-auto w-full px-4 py-10">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-10">
          <div>
            <h1 class="text-4xl font-extrabold text-[#1f2937] tracking-tight">Video Highlights</h1>
            <p class="text-gray-500 text-sm mt-2">
              Relive the action. Watch the best plays, moments, and highlights.
            </p>
          </div>

          <button
            @click="showAddModal = true"
            class="mt-4 sm:mt-0 bg-[#0b66ff] hover:bg-[#084dcc] px-5 py-2.5 rounded-lg text-white font-semibold tracking-wide transition-all duration-200"
          >
            + Add Highlight
          </button>
        </div>

        <!-- Video Grid -->
        <transition-group
          name="fade"
          tag="div"
          class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
          v-if="videos.length"
        >
          <div
            v-for="v in videos"
            :key="v.id"
            class="group bg-white border border-gray-200 rounded-xl overflow-hidden shadow hover:shadow-lg hover:scale-[1.02] transition-all duration-300 cursor-pointer"
          >
            <!-- Video Preview -->
            <div class="relative aspect-video overflow-hidden bg-gray-100">
              <video
                v-if="v.video"
                :src="v.video"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                muted
                loop
                @mouseenter="$event.target.play()"
                @mouseleave="$event.target.pause()"
              ></video>

              <img
                v-else-if="v.thumbnail"
                :src="v.thumbnail"
                alt="thumbnail"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />

              <div
                v-else
                class="absolute inset-0 bg-gray-200 flex items-center justify-center text-gray-500"
              >
                No Preview
              </div>

              <!-- Play Icon Overlay -->
              <div
                class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-black/40 transition-all duration-300"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-12 w-12 text-white"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path d="M8 5v14l11-7z" />
                </svg>
              </div>
            </div>

            <!-- Details -->
            <div class="p-4 space-y-1">
              <h2
                class="text-lg font-semibold line-clamp-1 text-[#111827] group-hover:text-[#0b66ff] transition"
              >
                {{ v.title }}
              </h2>
              <p class="text-sm text-gray-600 line-clamp-2">{{ v.description }}</p>
              <span class="block text-xs text-gray-400 mt-2">{{ v.date }}</span>
            </div>
          </div>
        </transition-group>

        <!-- Empty State -->
        <div
          v-else
          class="flex flex-col items-center justify-center py-24 text-center text-gray-500"
        >
          <img
            src="https://cdn-icons-png.flaticon.com/512/3449/3449573.png"
            alt="no videos"
            class="w-24 h-24 mb-4 opacity-60"
          />
          <p class="text-lg font-medium mb-2">No highlights yet</p>
          <p class="text-sm text-gray-400 mb-6">
            Add your first sports highlight below.
          </p>
          <button
            @click="showAddModal = true"
            class="bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold px-5 py-2.5 rounded-lg transition-all duration-200"
          >
            + Add Highlight
          </button>
        </div>
      </div>
    </div>

    <!-- ================= ADD VIDEO MODAL ================= -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 backdrop-blur-sm"
    >
      <div class="bg-white text-[#111827] rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
          <h3 class="font-semibold text-lg">Add Video Highlight</h3>
          <button class="text-gray-500 hover:text-gray-700" @click="closeModal">✕</button>
        </div>

        <form @submit.prevent="saveVideo" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:border-[#0b66ff] outline-none"
              placeholder="e.g. Basketball Finals Highlights"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:border-[#0b66ff] outline-none"
              placeholder="Brief description of the video"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Upload Video</label>
            <input type="file" accept="video/*" @change="onVideoSelect" class="text-sm" />
            <div v-if="videoPreview" class="mt-3">
              <video
                :src="videoPreview"
                controls
                class="rounded-lg w-full max-h-60 object-cover"
              ></video>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Thumbnail (optional)</label>
            <input type="file" accept="image/*" @change="onThumbnailSelect" class="text-sm" />
            <div v-if="thumbnailPreview" class="mt-3">
              <img
                :src="thumbnailPreview"
                alt="preview"
                class="rounded-lg w-full h-40 object-cover"
              />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-3">
            <button
              type="button"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition"
              @click="closeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold rounded transition"
            >
              Save
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const videos = ref([])
const showAddModal = ref(false)
const form = ref({
  title: '',
  description: '',
  video: null,
  thumbnail: null,
  date: ''
})
const videoPreview = ref(null)
const thumbnailPreview = ref(null)

onMounted(() => {
  const saved = localStorage.getItem('videos')
  videos.value = saved ? JSON.parse(saved) : []
})

function onVideoSelect(e) {
  const f = e.target.files[0]
  form.value.video = f ? URL.createObjectURL(f) : null
  videoPreview.value = form.value.video
}

function onThumbnailSelect(e) {
  const f = e.target.files[0]
  form.value.thumbnail = f ? URL.createObjectURL(f) : null
  thumbnailPreview.value = form.value.thumbnail
}

function saveVideo() {
  const newVideo = {
    id: Date.now(),
    title: form.value.title,
    description: form.value.description,
    video: form.value.video,
    thumbnail: form.value.thumbnail,
    date: new Date().toLocaleDateString()
  }
  videos.value.unshift(newVideo)
  localStorage.setItem('videos', JSON.stringify(videos.value))
  closeModal()
}

function closeModal() {
  showAddModal.value = false
  form.value = { title: '', description: '', video: null, thumbnail: null, date: '' }
  videoPreview.value = null
  thumbnailPreview.value = null
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
