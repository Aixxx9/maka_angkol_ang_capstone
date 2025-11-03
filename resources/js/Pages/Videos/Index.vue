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
            <div class="relative aspect-video overflow-hidden bg-gray-100">
              <video
                v-if="v.video"
                :src="v.video"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                muted
                loop
                playsinline
                webkit-playsinline
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

              <!-- Overlay Play Icon -->
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

              <!-- Action Buttons -->
              <div class="absolute top-2 right-2 hidden group-hover:flex gap-2 z-10">
                <button @click.stop="downloadVideo(v)" class="px-2 py-1 text-xs rounded bg-white border opacity-80 hover:opacity-100">Download</button>
                <button @click.stop="openEdit(v)" class="px-2 py-1 text-xs rounded bg-white border opacity-80 hover:opacity-100">Edit</button>
                <button @click.stop="destroyVideo(v.id)" class="px-2 py-1 text-xs rounded bg-red-600 text-white opacity-80 hover:opacity-100">Delete</button>
              </div>
            </div>

            <div class="p-4 space-y-1">
              <h2 class="text-lg font-semibold line-clamp-1 text-[#111827] group-hover:text-[#0b66ff] transition">
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

    <!-- WATCH MODAL -->
    <div v-if="showWatchModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 md:p-4" @click.self="closeWatch">
      <div class="bg-white/95 text-[#111827] rounded-xl shadow-xl w-full max-w-[960px] overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
          <h3 class="font-semibold text-lg">{{ currentVideo?.title || 'Highlight' }}</h3>
          <div class="flex items-center gap-3">
            <button v-if="currentVideo" @click="downloadVideo(currentVideo)" class="text-sm px-3 py-1 border rounded hover:bg-gray-50">Download</button>
            <button v-if="currentVideo" @click="openEdit(currentVideo)" class="text-sm px-3 py-1 border rounded hover:bg-gray-50">Edit</button>
            <button v-if="currentVideo" @click="destroyVideo(currentVideo.id)" class="text-sm px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
            <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none" @click="closeWatch">×</button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3">
          <div class="lg:col-span-2 bg-black">
            <video
              v-if="currentVideo?.video"
              :src="currentVideo.video"
              controls
              autoplay
              playsinline
              class="w-full max-h-[65vh]"
            ></video>
            <div v-else class="text-white p-6">No video available.</div>
            <div class="bg-white text-[#111827] p-5 space-y-2">
              <div class="text-xl font-bold">{{ currentVideo?.title }}</div>
              <div class="text-xs text-gray-500">{{ currentVideo?.date }}</div>
              <p class="text-sm text-gray-700">{{ currentVideo?.description }}</p>
            </div>
          </div>

          <div class="border-l border-gray-200 bg-white/95 p-4 flex flex-col gap-3 overflow-y-auto max-h-[65vh]">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-semibold">Up Next</span>
              <label class="flex items-center gap-2 text-xs text-gray-600">
                <input type="checkbox" v-model="autoplay" class="rounded border-gray-300" /> Autoplay
              </label>
            </div>
            <button
              v-for="v in upNext"
              :key="v.id"
              class="flex gap-3 items-center text-left hover:bg-gray-50 rounded-lg p-2 transition"
              @click="playVideo(v)"
            >
              <img :src="v.thumbnail || '/images/default-logo.png'" class="w-32 h-20 object-cover rounded" />
              <div>
                <div class="text-[13px] font-semibold line-clamp-2">{{ v.title }}</div>
                <div class="text-[11px] text-gray-500 mt-1">{{ v.date }}</div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ADD VIDEO MODAL -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white text-[#111827] rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
          <h3 class="font-semibold text-lg">Add Video Highlight</h3>
          <button class="text-gray-500 hover:text-gray-700" @click="closeModal">×</button>
        </div>

        <form @submit.prevent="saveVideo" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input v-model="form.title" type="text" class="w-full border rounded px-3 py-2" placeholder="Title" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full border rounded px-3 py-2"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Upload Video</label>
            <input type="file" accept="video/*" @change="onVideoSelect" />
            <video v-if="videoPreview" :src="videoPreview" controls class="w-full rounded mt-2 max-h-60" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Thumbnail (optional)</label>
            <input type="file" accept="image/*" @change="onThumbnailSelect" />
            <img v-if="thumbnailPreview" :src="thumbnailPreview" class="w-full rounded mt-2 h-40 object-cover" />
          </div>

          <div class="flex justify-end gap-3 pt-3">
            <button type="button" class="px-4 py-2 border rounded" @click="closeModal">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#0b66ff] hover:bg-[#084dcc] text-white rounded">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- SMALLER EDIT VIDEO MODAL -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white text-[#111827] rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200">
          <h3 class="font-semibold text-lg">Edit Video Highlight</h3>
          <button class="text-gray-500 hover:text-gray-700" @click="closeEdit">×</button>
        </div>

        <form @submit.prevent="saveEdit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input v-model="editForm.title" type="text" class="w-full border rounded px-3 py-2" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea v-model="editForm.description" rows="3" class="w-full border rounded px-3 py-2"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Replace Video (optional)</label>
            <input type="file" accept="video/*" @change="onEditVideoSelect" />
            <video v-if="editVideoPreview" :src="editVideoPreview" controls class="rounded-lg w-full max-h-52 mt-2 object-cover"></video>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Replace Thumbnail (optional)</label>
            <input type="file" accept="image/*" @change="onEditThumbnailSelect" />
            <img v-if="editThumbPreview" :src="editThumbPreview" alt="preview" class="rounded-lg w-full h-36 mt-2 object-cover" />
          </div>

          <div class="flex justify-between items-center pt-3">
            <button type="button" @click="confirmDeleteFromEdit" class="text-sm text-red-600 hover:text-red-700">Delete</button>
            <div class="flex gap-3">
              <button type="button" class="px-4 py-2 border rounded" @click="closeEdit">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-[#0b66ff] hover:bg-[#084dcc] text-white rounded">Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const videos = ref([])
const showAddModal = ref(false)
const showEditModal = ref(false)
const showWatchModal = ref(false)
const currentVideo = ref(null)
const autoplay = ref(true)
const editingId = ref(null)
const form = ref({ title: '', description: '' })
const editForm = ref({ title: '', description: '' })
const videoPreview = ref(null)
const thumbnailPreview = ref(null)
const formVideoFile = ref(null)
const formThumbFile = ref(null)
const editVideoPreview = ref(null)
const editThumbPreview = ref(null)
const editVideoFile = ref(null)
const editThumbFile = ref(null)

const upNext = computed(() => videos.value.filter(v => v.id !== currentVideo.value?.id))

onMounted(async () => {
  const saved = localStorage.getItem('videos')
  videos.value = saved ? JSON.parse(saved) : []
  for (const item of videos.value) await hydrateItem(item)
})

function onVideoSelect(e) {
  const f = e.target.files?.[0]
  formVideoFile.value = f
  videoPreview.value = f ? URL.createObjectURL(f) : null
}
function onThumbnailSelect(e) {
  const f = e.target.files?.[0]
  formThumbFile.value = f
  thumbnailPreview.value = f ? URL.createObjectURL(f) : null
}
async function saveVideo() {
  const id = Date.now()
  if (formVideoFile.value) await idbPut('video:' + id, formVideoFile.value)
  if (formThumbFile.value) await idbPut('thumb:' + id, formThumbFile.value)
  const newVideo = { id, title: form.value.title, description: form.value.description, date: new Date().toLocaleDateString(), video: null, thumbnail: null }
  videos.value.unshift(newVideo)
  localStorage.setItem('videos', JSON.stringify(videos.value))
  await hydrateItem(newVideo)
  closeModal()
}
function closeModal() {
  showAddModal.value = false
  form.value = { title: '', description: '' }
  videoPreview.value = thumbnailPreview.value = null
}
function openWatch(v) { currentVideo.value = v; showWatchModal.value = true }
function closeWatch() { showWatchModal.value = false; currentVideo.value = null }
function playVideo(v) { currentVideo.value = v }
function openEdit(v) {
  editingId.value = v.id
  editForm.value = { title: v.title, description: v.description }
  editVideoPreview.value = v.video
  editThumbPreview.value = v.thumbnail
  showEditModal.value = true
}
function closeEdit() { showEditModal.value = false }
async function saveEdit() {
  const idx = videos.value.findIndex(v => v.id === editingId.value)
  if (idx < 0) return closeEdit()
  const updated = { ...videos.value[idx], title: editForm.value.title, description: editForm.value.description }
  if (editVideoFile.value) await idbPut('video:' + updated.id, editVideoFile.value)
  if (editThumbFile.value) await idbPut('thumb:' + updated.id, editThumbFile.value)
  await hydrateItem(updated)
  videos.value.splice(idx, 1, updated)
  localStorage.setItem('videos', JSON.stringify(videos.value))
  closeEdit()
}
function onEditVideoSelect(e) {
  const f = e.target.files?.[0]
  editVideoFile.value = f
  editVideoPreview.value = f ? URL.createObjectURL(f) : editVideoPreview.value
}
function onEditThumbnailSelect(e) {
  const f = e.target.files?.[0]
  editThumbFile.value = f
  editThumbPreview.value = f ? URL.createObjectURL(f) : editThumbPreview.value
}
function confirmDeleteFromEdit() { destroyVideo(editingId.value); closeEdit() }
async function destroyVideo(id) {
  if (!confirm('Delete this video?')) return
  const idx = videos.value.findIndex(v => v.id === id)
  if (idx >= 0) videos.value.splice(idx, 1)
  localStorage.setItem('videos', JSON.stringify(videos.value))
  await idbDel('video:' + id)
  await idbDel('thumb:' + id)
  closeWatch()
}
async function downloadVideo(item) {
  const blob = await idbGet('video:' + item.id)
  const href = blob ? URL.createObjectURL(blob) : item.video
  const a = document.createElement('a')
  a.href = href
  a.download = (item.title || 'video') + '.mp4'
  a.click()
}
function openDB() {
  return new Promise((res, rej) => {
    const req = indexedDB.open('videos-db', 1)
    req.onupgradeneeded = () => req.result.createObjectStore('media')
    req.onsuccess = () => res(req.result)
    req.onerror = () => rej(req.error)
  })
}
async function idbPut(k, b) {
  const db = await openDB()
  return new Promise((res, rej) => {
    const tx = db.transaction('media', 'readwrite')
    tx.objectStore('media').put(b, k)
    tx.oncomplete = res
    tx.onerror = () => rej(tx.error)
  })
}
async function idbGet(k) {
  const db = await openDB()
  return new Promise((res, rej) => {
    const tx = db.transaction('media', 'readonly')
    const req = tx.objectStore('media').get(k)
    req.onsuccess = () => res(req.result)
    req.onerror = () => rej(req.error)
  })
}
async function idbDel(k) {
  const db = await openDB()
  return new Promise((res, rej) => {
    const tx = db.transaction('media', 'readwrite')
    tx.objectStore('media').delete(k)
    tx.oncomplete = res
    tx.onerror = () => rej(tx.error)
  })
}
async function hydrateItem(item) {
  try {
    const vb = await idbGet('video:' + item.id)
    if (vb) item.video = URL.createObjectURL(vb)
    const tb = await idbGet('thumb:' + item.id)
    if (tb) item.thumbnail = URL.createObjectURL(tb)
  } catch {}
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
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
</style>
