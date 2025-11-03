<script setup>
import { ref, computed, nextTick } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  sports: { type: Array, default: () => [] },
})

// Auth/role helpers
const page = usePage()
const isAdmin = computed(() => {
  const roles = page.props.auth?.user?.roles || []
  return Array.isArray(roles) ? roles.includes('admin') || roles.includes('super-admin') : false
})

// filters and sorting
const q = ref('')
const typeFilter = ref('all')
const sortKey = ref('name')
const sortDir = ref('asc')
const gridEl = ref(null)

const safeImg = (p) => (p ? (p.startsWith('/storage') ? p : `/storage/${p}`) : null)

const filteredSports = computed(() => {
  const search = q.value.trim().toLowerCase()
  let list = props.sports.slice()

  if (typeFilter.value !== 'all') list = list.filter(s => (s.type || '').toLowerCase() === typeFilter.value)
  if (search) list = list.filter(s => (s.name || '').toLowerCase().includes(search) || (s.description || '').toLowerCase().includes(search))

  const dir = sortDir.value === 'asc' ? 1 : -1
  list.sort((a,b) => (a.name || '').localeCompare(b.name || '') * dir)
  return list
})

function focusGrid() {
  nextTick(() => gridEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
}
function applyFilter(val) { typeFilter.value = val; focusGrid() }
function applySort(key) {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
  focusGrid()
}

/* -----------------------------
   MODAL LOGIC
--------------------------------*/
const showModal = ref(false)
const selectedSport = ref(null)

function openModal(sport) {
  selectedSport.value = sport
  showModal.value = true
  document.body.style.overflow = 'hidden'
}

function closeModal() {
  showModal.value = false
  selectedSport.value = null
  document.body.style.overflow = ''
}

function deleteSport(id) {
  if (!confirm('Are you sure you want to delete this sport?')) return
  router.delete(`/sports/${id}`, {
    onSuccess: () => closeModal(),
  })
}
</script>

<template>
  <AppLayout>
    <!-- =================== HERO =================== -->
    <section class="bg-[radial-gradient(ellipse_at_top,rgba(2,132,199,0.06),transparent_60%)] border-b border-neutral-100">
      <div class="max-w-[1200px] mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
          <div>
            <h1 class="text-3xl md:text-4xl font-semibold tracking-tight text-neutral-900">
              Explore Sports
            </h1>
            <p class="text-neutral-600 mt-2 leading-relaxed">
              Find disciplines, scan schedules, and discover standout athletes.
            </p>
          </div>

          <!-- Search -->
          <div class="w-full md:w-[520px]">
            <div class="relative">
              <input
                v-model="q"
                type="text"
                placeholder="Search a sport (e.g., Basketball, Taekwondo)"
                class="w-full rounded-xl border border-neutral-200 bg-white/80 backdrop-blur px-4 py-3 pl-12 shadow-sm placeholder:text-neutral-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-200/70"
                @keydown.enter="focusGrid"
                aria-label="Search sports"
              />
              <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-neutral-400" viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 18a8 8 0 1 1 6.32-3.09l4.39 4.39-1.41 1.41-4.39-4.39A7.98 7.98 0 0 1 10 18Zm0-2a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Add Sport Button -->
        <div class="mt-6 flex justify-end">
          <Link
            v-if="isAdmin"
            href="/sports/create"
            class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white font-semibold px-4 py-2.5 rounded-lg shadow-sm transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Sport
          </Link>
        </div>
      </div>
    </section>

    <!-- =================== BODY =================== -->
    <section class="max-w-[1200px] mx-auto px-4 py-8">
      <div ref="gridEl" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
        <div
          v-for="s in filteredSports"
          :key="s.id"
          @click="openModal(s)"
          class="bg-white rounded-2xl border border-neutral-200 shadow-sm hover:shadow-md transition p-4 group cursor-pointer"
        >
          <div class="aspect-square rounded-xl overflow-hidden bg-neutral-100 mb-3">
            <img
              v-if="s.icon_path"
              :src="safeImg(s.icon_path)"
              class="w-full h-full object-cover"
              alt=""
            />
            <div
              v-else
              class="flex items-center justify-center h-full w-full text-sky-600 text-xl font-semibold"
            >
              {{ s.name.charAt(0).toUpperCase() }}
            </div>
          </div>

          <h3 class="font-semibold text-neutral-900 text-lg group-hover:text-sky-600">
            {{ s.name }}
          </h3>
          <p class="text-sm text-neutral-600 line-clamp-2">
            {{ s.description || 'No description available.' }}
          </p>
          <span class="text-xs mt-1 text-neutral-500 block">{{ s.type }}</span>
        </div>

        <div v-if="!filteredSports.length" class="col-span-full text-center text-neutral-500 py-10">
          No sports found.
        </div>
      </div>
    </section>

    <!-- =================== MODAL =================== -->
    <transition name="fade">
      <div
        v-if="showModal && selectedSport"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
      >
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full relative p-6">
          <button @click="closeModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700">
            ✕
          </button>

          <div class="text-center">
            <img
              v-if="selectedSport.icon_path"
              :src="safeImg(selectedSport.icon_path)"
              class="w-24 h-24 rounded-xl object-cover mx-auto mb-4"
            />
            <div
              v-else
              class="w-24 h-24 rounded-xl bg-sky-100 flex items-center justify-center text-sky-600 text-3xl font-semibold mx-auto mb-4"
            >
              {{ selectedSport.name.charAt(0).toUpperCase() }}
            </div>

            <h2 class="text-2xl font-semibold text-neutral-900">{{ selectedSport.name }}</h2>
            <p class="text-sm text-neutral-600 mt-2">{{ selectedSport.description || 'No description.' }}</p>
            <span class="text-xs text-neutral-500 mt-1 block">Type: {{ selectedSport.type }}</span>
          </div>

          <div v-if="isAdmin" class="mt-6 flex justify-center gap-3">
            <Link
              :href="`/sports/${selectedSport.id}/edit`"
              class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg font-medium transition"
            >
              Edit
            </Link>
            <button
              @click="deleteSport(selectedSport.id)"
              class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </transition>
  </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
