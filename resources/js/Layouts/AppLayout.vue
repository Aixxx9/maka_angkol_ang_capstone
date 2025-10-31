<script setup>
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const page = usePage()
const schools = page.props.schools ?? []

// ✅ Normalize logo paths (avoid /schools/schools/... issue)
function normalizeLogo(path) {
  if (!path) return '/images/default-logo.png'
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  if (path.startsWith('/storage/')) return path
  return `/storage/${path}`
}
// Add School modal state + form
const showAddSchool = ref(false)
const preview = ref(null)
const schoolForm = useForm({
  name: '',
  slug: '',
  summary: '',
  logo: null,
})

// Sidebar state
const showSidebar = ref(false)

// Handle file upload
function onPickLogo(e) {
  const f = e.target.files?.[0]
  schoolForm.logo = f || null
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = f ? URL.createObjectURL(f) : null
}
function clearLogo() {
  schoolForm.logo = null
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = null
}

function submitSchool() {
  schoolForm.post('/schools', {
    forceFormData: true,
    onSuccess: () => {
      showAddSchool.value = false
      schoolForm.reset('name', 'slug', 'summary', 'logo')
      window.location.reload()
    },
  })
}


</script>

<template>
  <div class="min-h-screen bg-[#f7f8fc] text-[#111827] flex flex-col font-inter">
    
    <!-- ================= SCHOOLS STRIP ================= -->
    <div class="bg-[#cfe1fb] border-b border-[#bcd3f5] px-4 py-4">
  <div class="flex items-center w-full gap-4">
    <!-- Left Label -->
    <div class="flex items-center shrink-0">
      <span class="text-[15px] font-semibold text-[#1f2937]">Schools</span>
    </div>

    <!-- Add School Button -->
    <button
      type="button"
      class="h-12 w-12 flex items-center justify-center rounded-full bg-white text-[#0b66ff] ring-2 ring-[#a8c7f3] hover:bg-[#0b66ff] hover:text-white transition shrink-0"
      title="Add School"
      @click="showAddSchool = true"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2h6z"/>
      </svg>
    </button>

    <!-- Divider line -->
    <div class="h-10 w-px bg-[#bcd3f5]" />

    <!-- School logos: fill the rest of the row evenly -->
    <div class="flex-1 flex justify-evenly items-center flex-wrap gap-y-3">
      <template v-for="s in schools" :key="s.id">
        <Link
          :href="`/schools/${s.slug}`"
          class="flex flex-col items-center group shrink-0"
          :title="s.name"
        >
          <div
            class="logo-btn overflow-hidden rounded-full ring-2 ring-[#a8c7f3] group-hover:ring-[#0b66ff] hover:scale-110 transition"
          >
            <img
              :src="normalizeLogo(s.logo_path)"
              class="h-full w-full object-cover"
              :alt="s.name"
              @error="(e) => (e.target.src = '/images/default-logo.png')"
            />
          </div>
        </Link>
      </template>
    </div>
  </div>
</div>


    <!-- ================= NAV ROW ================= -->
    <header class="relative bg-[#eaf1ff] border-b border-[#d7e5ff]">
      <div class="w-full px-4 py-2 relative flex items-center">
        <!-- Burger Button -->
        <button
          class="mr-4 text-[#354b7d] hover:text-[#0b66ff] transition"
          @click="showSidebar = !showSidebar"
        >
          <svg v-if="!showSidebar" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <nav class="flex-1 flex justify-evenly text-[15px] font-semibold text-[#354b7d]">
          <Link href="/sports" class="hover:text-[#0b66ff]">Sports</Link>
          <Link href="/athletes" class="hover:text-[#0b66ff]">Athletes</Link>
          <Link href="/schedule" class="hover:text-[#0b66ff]">Schedule</Link>
        </nav>

        <!-- Center logo -->
        <Link
          href="/"
          aria-label="PRISAA Home"
          class="absolute left-1/2 -translate-x-1/2 -translate-y-2 z-30 block cursor-pointer"
        >
          <img
            src="/images/logo.png"
            class="h-20 w-20 rounded-full bg-white shadow-md ring-2 ring-white"
            alt="PRISAA"
          />
        </Link>

        <nav class="flex-1 flex justify-evenly text-[15px] font-semibold text-[#354b7d]">
          <Link href="/matches" class="hover:text-[#0b66ff]">Matches</Link>
          <Link href="/news" class="hover:text-[#0b66ff]">News</Link>
          <Link href="/videos" class="hover:text-[#0b66ff]">Videos</Link>
        </nav>
      </div>
    </header>

    <!-- Sidebar -->
    <transition name="slide">
      <div
        v-if="showSidebar"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-white/85 shadow-2xl border-r border-[#d7e5ff] backdrop-blur-md flex flex-col"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <h2 class="text-lg font-semibold text-[#1f2937]">Menu</h2>
          <button @click="showSidebar = false" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>
        <nav class="flex flex-col p-4 space-y-3 text-[#354b7d] font-semibold">
          <Link href="/sports/create" class="hover:text-[#0b66ff]" @click="showSidebar = false">Add Sports</Link>
          <Link href="/athletes" class="hover:text-[#0b66ff]" @click="showSidebar = false">Athletes</Link>
          <Link href="/games/create" class="hover:text-[#0b66ff]" @click="showSidebar = false">Create Schedule</Link>
          <Link href="/matches" class="hover:text-[#0b66ff]" @click="showSidebar = false">Matches</Link>
          <Link href="/news" class="hover:text-[#0b66ff]" @click="showSidebar = false">News</Link>
          <Link href="/videos" class="hover:text-[#0b66ff]" @click="showSidebar = false">Videos</Link>
        </nav>
      </div>
    </transition>

    <!-- LIVE BUTTON -->
    <div class="max-w-[1200px] mx-auto w-full px-4 mt-2">
      <Link
        :href="$page.props.live?.url ?? ($page.props.live?.game_id ? `/live/${$page.props.live.game_id}` : '/schedule')"
        class="inline-flex items-center gap-2 bg-[#e60000] hover:bg-[#c40000] text-white text-[14px] font-bold px-3 py-1 rounded-[4px] shadow transition"
        title="Watch Live"
      >
        <span class="relative flex h-2.5 w-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-60"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
        </span>
        LIVE
      </Link>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- FOOTER -->
    <footer class="bg-[#0a0a0a] text-[#d1d5db] mt-10">
      <div class="max-w-[1200px] mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div class="text-[14px] leading-relaxed">
            <span class="uppercase tracking-wide text-[#f04242] font-bold">PH</span>
            <span class="font-bold ml-1 text-white">PinoySportsHub</span><br>
            Your ultimate destination for Philippine sports news, updates,<br class="hidden sm:block">
            and celebrating Filipino sports excellence.
          </div>
          <div class="flex items-center gap-3">
            <a href="#" class="h-8 w-8 grid place-items-center rounded-full bg-[#111111]">f</a>
            <a href="#" class="h-8 w-8 grid place-items-center rounded-full bg-[#111111]">x</a>
            <a href="#" class="h-8 w-8 grid place-items-center rounded-full bg-[#111111]">▶</a>
            <a href="#" class="h-8 w-8 grid place-items-center rounded-full bg-[#111111]">in</a>
          </div>
        </div>

        <div class="mt-8 grid grid-cols-2 sm:grid-cols-6 gap-3 text-sm">
          <div class="font-semibold col-span-2 sm:col-span-1 text-white">Sports</div>
          <div>Basketball</div>
          <div>Boxing</div>
          <div>Volleyball</div>
          <div>Football</div>
          <div>Swimming</div>
        </div>

        <div class="mt-8 text-center text-xs text-[#9ca3af]">
          © 2024 PRISAA. Proudly celebrating Filipino sports excellence.
        </div>
      </div>
    </footer>

    <!-- ADD SCHOOL MODAL -->
    <div
      v-if="showAddSchool"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"
    >
      <div class="bg-white w-full max-w-md rounded-lg shadow-lg overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
          <div class="font-semibold">Add School</div>
          <button class="text-gray-500 hover:text-gray-700" @click="showAddSchool = false">✕</button>
        </div>

        <form @submit.prevent="submitSchool" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">School Name</label>
            <input v-model="schoolForm.name" type="text" class="w-full border rounded px-3 py-2" placeholder="e.g. Saint Michael University"/>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Slug</label>
            <input v-model="schoolForm.slug" type="text" class="w-full border rounded px-3 py-2" placeholder="e.g. saint-michael-university"/>
            <p class="text-xs text-gray-500 mt-1">Used in the URL (e.g. /schools/saint-michael-university)</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Short Summary</label>
            <textarea v-model="schoolForm.summary" rows="3" class="w-full border rounded px-3 py-2" placeholder="1–2 sentence summary shown on the school page"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Logo</label>
            <input type="file" accept="image/*" @change="onPickLogo" />
            <div v-if="preview" class="relative mt-2">
              <img :src="preview" class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-200" alt="logo preview"/>
              <button type="button" class="absolute top-1 left-24 text-xs bg-black/60 text-white px-2 py-0.5 rounded" @click="clearLogo">Remove</button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Recommended: square PNG/JPG, ~512×512.</p>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" class="px-3 py-2 rounded border" @click="showAddSchool=false">Cancel</button>
            <button type="submit" class="px-4 py-2 rounded bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold" :disabled="schoolForm.processing">
              {{ schoolForm.processing ? 'Saving…' : 'Save' }}
            </button>
          </div>

          <div v-if="schoolForm.errors && Object.keys(schoolForm.errors).length" class="text-sm text-red-600">
            <div v-for="(msg, key) in schoolForm.errors" :key="key">{{ msg }}</div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style>
.scrollbar-none::-webkit-scrollbar { display: none; }
.scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }

.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(-100%); opacity: 0; }
.logo-btn {
  height: 64px;   /* 👈 Change this to adjust logo size */
  width: 64px;    /* 👈 Keep width same as height for circle */
}

</style>
