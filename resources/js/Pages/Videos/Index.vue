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
            @click="openWatch(v)"
          >
            <!-- Video Preview -->
            <div class="relative aspect-video overflow-hidden bg-gray-100">
              <video
                v-if="v.video"
                :src="v.video"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                muted
                loop
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
                class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-black/40 transition-all duration-300 pointer-events-none"
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

              <!-- Card actions removed (moved to watch modal footer) -->
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

    <!-- ================= EDIT VIDEO MODAL ================= -->
    <div
      v-if="showEditModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white text-[#111827] rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
          <h3 class="font-semibold text-lg">Edit Video Highlight</h3>
          <button class="text-gray-500 hover:text-gray-700" @click="closeEdit">×</button>
        </div>

        <form @submit.prevent="saveEdit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input
              v-model="editForm.title"
              type="text"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:border-[#0b66ff] outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="editForm.description"
              rows="3"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:border-[#0b66ff] outline-none"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Replace Video (optional)</label>
            <input type="file" accept="video/*" @change="onEditVideoSelect" class="text-sm" />
            <div v-if="editVideoPreview" class="mt-3">
              <video :src="editVideoPreview" controls class="rounded-lg w-full max-h-60 object-cover"></video>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Replace Thumbnail (optional)</label>
            <input type="file" accept="image/*" @change="onEditThumbnailSelect" class="text-sm" />
            <div v-if="editThumbPreview" class="mt-3">
              <img :src="editThumbPreview" alt="preview" class="rounded-lg w-full h-40 object-cover" />
            </div>
          </div>

          <div class="flex justify-between items-center pt-3">
            <button type="button" @click="confirmDeleteFromEdit" class="text-sm text-red-600 hover:text-red-700">Delete</button>
            <div class="flex gap-3">
              <button type="button" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition" @click="closeEdit">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold rounded">Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div
      v-if="showAddModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white text-[#111827] rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
          <h3 class="font-semibold text-lg">Add Video Highlight</h3>
          <button class="text-gray-500 hover:text-gray-700" @click="closeModal">Ã—</button>
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

    <!-- Watch Modal (NBA-like layout) -->
    <div v-if="showWatchModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm md:backdrop-blur flex items-center justify-center p-3 md:p-4">
      <div class="bg-white/95 text-[#111827] rounded-xl md:rounded-2xl shadow-xl ring-1 ring-black/5 w-full max-w-[960px] overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
          <h3 class="font-semibold text-lg line-clamp-1">{{ currentVideo?.title || 'Highlight' }}</h3>
          <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none" @click="closeWatch" aria-label="Close">×</button>
        </div>
        <!-- Body: player + up next -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-0">
          <!-- Left: Player + meta -->
          <div class="lg:col-span-2 bg-black">
            <video v-if="currentVideo?.video" :src="currentVideo.video" :autoplay="autoplay" controls class="w-full max-h-[60vh] md:max-h-[65vh]"></video>
            <div v-else class="text-white p-6">No video available for this item.</div>
            <div class="bg-white text-[#111827] p-5 space-y-2">
              <div class="text-xl font-bold">{{ currentVideo?.title }}</div>
              <div class="text-xs text-gray-500">{{ currentVideo?.date }}</div>
              <p class="text-sm text-gray-700" v-if="currentVideo?.description">{{ currentVideo.description }}</p>
            </div>
            <!-- Footer actions (icon-only) -->
            <div class="bg-white border-t border-gray-200 px-5 py-3 flex items-center gap-3">
              <!-- Download -->
              <button
                v-if="currentVideo"
                @click="downloadVideo(currentVideo)"
                class="p-2 rounded hover:bg-gray-100"
                title="Download"
                aria-label="Download"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 text-gray-700" fill="currentColor"><path d="M12 3a1 1 0 0 1 1 1v9.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 1 1 1.414-1.414L11 13.586V4a1 1 0 0 1 1-1z"/><path d="M5 20a1 1 0 0 1 0-2h14a1 1 0 1 1 0 2H5z"/></svg>
              </button>
              <!-- Edit -->
              <button
                v-if="currentVideo"
                @click="openEdit(currentVideo)"
                class="p-2 rounded hover:bg-gray-100"
                title="Edit"
                aria-label="Edit"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 text-gray-700" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              </button>
              <!-- Delete -->
              <button
                v-if="currentVideo"
                @click="destroyVideo(currentVideo.id)"
                class="p-2 rounded hover:bg-red-50"
                title="Delete"
                aria-label="Delete"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 text-red-600" fill="currentColor"><path d="M9 3a1 1 0 0 0-1 1v1H5a1 1 0 0 0 0 2h14a1 1 0 1 0 0-2h-3V4a1 1 0 0 0-1-1H9zm-2 6a1 1 0 0 1 1 1v8a1 1 0 1 1-2 0v-8a1 1 0 0 1 1-1zm5 0a1 1 0 0 1 1 1v8a1 1 0 1 1-2 0v-8a1 1 0 0 1 1-1zm6 1v8a1 1 0 1 1-2 0v-8a1 1 0 1 1 2 0z"/></svg>
              </button>
            </div>
          </div>
          <!-- Right: Up Next -->
          <div class="border-l border-gray-200 bg-white/95 p-4 flex flex-col gap-3 max-h-[60vh] md:max-h-[65vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-2">
              <div class="text-sm font-semibold">Up Next</div>
              <label class="flex items-center gap-2 text-xs text-gray-600 select-none">
                <input type="checkbox" v-model="autoplay" class="rounded border-gray-300" /> Autoplay
              </label>
            </div>
            <button
              v-for="v in upNext"
              :key="v.id"
              class="flex gap-3 items-center text-left hover:bg-gray-50 rounded-lg p-2 transition"
              @click="playVideo(v)"
            >
              <div class="relative w-32 h-20 shrink-0">
                <img :src="v.thumbnail || '/images/default-logo.png'" class="w-full h-full object-cover rounded" />
                <div class="absolute inset-0 flex items-center justify-center bg-black/30 text-white opacity-0 hover:opacity-100 transition">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-[13px] font-semibold line-clamp-2">{{ v.title }}</div>
                <div class="text-[11px] text-gray-500 mt-1">{{ v.date }}</div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showWatchModal" class="fixed inset-0 z-40" @click="closeWatch"></div>

  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
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

// Watch modal state
const showWatchModal = ref(false)
const currentVideo = ref(null)
const autoplay = ref(true)

const upNext = computed(() => {
  const list = Array.isArray(videos.value) ? videos.value : []
  const cur = currentVideo.value?.id
  return list.filter(v => v.id !== cur)
})

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

function openWatch(v) {
  currentVideo.value = v
  showWatchModal.value = true
}

function closeWatch() {
  showWatchModal.value = false
  currentVideo.value = null
}

function playVideo(v) {
  currentVideo.value = v
}

// Edit state
const showEditModal = ref(false)
const editingId = ref(null)
const editForm = ref({ title: '', description: '', video: null, thumbnail: null })
const editVideoPreview = ref(null)
const editThumbPreview = ref(null)

function openEdit(v) {
  editingId.value = v?.id ?? null
  editForm.value = {
    title: v?.title || '',
    description: v?.description || '',
    video: null,
    thumbnail: null,
  }
  editVideoPreview.value = v?.video || null
  editThumbPreview.value = v?.thumbnail || null
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  editingId.value = null
  editForm.value = { title: '', description: '', video: null, thumbnail: null }
  editVideoPreview.value = null
  editThumbPreview.value = null
}

function onEditVideoSelect(e) {
  const f = e.target.files?.[0]
  editForm.value.video = f ? URL.createObjectURL(f) : null
  editVideoPreview.value = editForm.value.video
}

function onEditThumbnailSelect(e) {
  const f = e.target.files?.[0]
  editForm.value.thumbnail = f ? URL.createObjectURL(f) : null
  editThumbPreview.value = editForm.value.thumbnail
}

function saveEdit() {
  if (!editingId.value) { closeEdit(); return }
  const idx = (videos.value || []).findIndex(x => x.id === editingId.value)
  if (idx >= 0) {
    const updated = { ...videos.value[idx] }
    updated.title = editForm.value.title
    updated.description = editForm.value.description
    if (editForm.value.video) updated.video = editForm.value.video
    if (editForm.value.thumbnail) updated.thumbnail = editForm.value.thumbnail
    videos.value.splice(idx, 1, updated)
    localStorage.setItem('videos', JSON.stringify(videos.value))
    if (currentVideo.value && currentVideo.value.id === updated.id) {
      currentVideo.value = updated
    }
  }
  closeEdit()
}

function confirmDeleteFromEdit() {
  if (!editingId.value) return
  destroyVideo(editingId.value)
  closeEdit()
}

function destroyVideo(id) {
  if (!confirm('Delete this video? This action cannot be undone.')) return
  const list = videos.value || []
  const idx = list.findIndex(v => v.id === id)
  if (idx >= 0) {
    list.splice(idx, 1)
    videos.value = [...list]
    localStorage.setItem('videos', JSON.stringify(videos.value))
  }
  if (currentVideo.value && currentVideo.value.id === id) {
    // Auto-advance if autoplay and another item exists
    const next = upNext.value?.[0]
    if (autoplay.value && next) {
      currentVideo.value = next
    } else {
      closeWatch()
    }
  }
}
function safeFileName(name) {
  const base = (name || 'video').toString().trim().replace(/[^\w\-\s\.]+/g, '').replace(/\s+/g, '-').slice(0, 60)
  return base || 'video'
}

function downloadVideo(item) {
  const href = item?.video
  if (!href) { alert('No video available to download.'); return }
  const a = document.createElement('a')
  a.href = href
  a.download = safeFileName(item?.title) + '.mp4'
  a.rel = 'noopener'
  a.style.display = 'none'
  document.body.appendChild(a)
  a.click()
  setTimeout(() => { document.body.removeChild(a) }, 0)
}</script>

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

.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
</style>









