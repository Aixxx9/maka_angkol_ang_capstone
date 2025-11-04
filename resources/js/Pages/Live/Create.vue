<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  games: { type: Array, default: () => [] },
  current: { type: Object, default: null },
})

const form = useForm({ game_id: '', title: '', url: '' })

const previewUrl = ref('')

function normalizeEmbed(u) {
  let url = String(u || '').trim()
  if (!url) return ''
  // If pasted iframe HTML, extract src
  if (/^\s*<iframe/i.test(url)) {
    const m = url.match(/src\s*=\s*['\"]([^'\"]+)/i)
    if (m) url = m[1]
  }
  // YouTube
  const yt = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([\w-]{6,})/i)
  if (yt) return `https://www.youtube.com/embed/${yt[1]}?autoplay=1&rel=0`
  // Facebook
  if (url.includes('facebook.com')) {
    let normalized = url.replace(/^https?:\/\/(m|web)\.facebook\.com/i, 'https://www.facebook.com')
    if (!/^https?:\/\//i.test(normalized)) normalized = 'https://' + normalized
    if (/facebook\.com\/(?:watch\/\?v=|.*?\/videos\/|video\.php\?v=)/i.test(normalized)) {
      return `https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(normalized)}&show_text=0&autoplay=1`
    }
    if (/facebook\.com\/.+\/live/i.test(normalized) || /facebook\.com\/[\w\.]+\/?$/i.test(normalized)) {
      return `https://www.facebook.com/plugins/live_video.php?href=${encodeURIComponent(normalized)}&show_text=0&autoplay=1`
    }
    return `https://www.facebook.com/plugins/post.php?href=${encodeURIComponent(normalized)}&show_text=true`
  }
  // Vimeo
  const vm = url.match(/vimeo\.com\/(\d+)/i)
  if (vm) return `https://player.vimeo.com/video/${vm[1]}?autoplay=1`
  return url
}

function preview() { previewUrl.value = normalizeEmbed(form.url) }

function startLive() {
  form.post(route('live.store'))
}

function stopLive(id) {
  if (!confirm('Stop current live?')) return
  // Using PUT to /live/{game}/stop
  form.transform(() => ({})).put(route('live.stop', id))
}

const gameOptions = computed(() => {
  return (props.games || []).map(g => ({
    id: g.id,
    label: `${g.label || 'Match'}${g.starts_at ? ' – ' + g.starts_at : ''}${g.status === 'live' ? ' (LIVE)' : ''}`
  }))
})
</script>

<template>
  <AppLayout title="Go Live">
    <section class="max-w-[900px] mx-auto px-4 py-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Go Live</h1>
        <Link href="/" class="text-sm text-[#0b66ff] hover:underline">← Back Home</Link>
      </div>

      <div class="bg-white border border-neutral-200 rounded-2xl p-6 space-y-6">
        <div v-if="current" class="p-4 rounded-lg bg-red-50 border border-red-200">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-red-600 font-semibold text-sm">LIVE NOW</div>
              <div class="font-bold">{{ current.live_title || 'Live Now' }}</div>
              <div class="text-xs text-neutral-500">Game #{{ current.id }}</div>
            </div>
            <div class="flex items-center gap-2">
              <Link :href="route('live.show', current.id)" class="px-3 py-1.5 rounded bg-[#0b66ff] text-white text-sm">Open Live Page</Link>
              <button class="px-3 py-1.5 rounded bg-neutral-800 text-white text-sm" @click="stopLive(current.id)">Stop Live</button>
            </div>
          </div>
        </div>

        <form @submit.prevent="startLive" class="space-y-5">
          <div>
            <label class="block text-sm font-medium mb-1">Game</label>
            <select v-model="form.game_id" required class="w-full rounded-lg border px-3 py-2">
              <option value="" disabled>Select game…</option>
              <option v-for="g in gameOptions" :key="g.id" :value="g.id">{{ g.label }}</option>
            </select>
            <p v-if="form.errors.game_id" class="text-red-600 text-sm mt-1">{{ form.errors.game_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Title</label>
            <input v-model="form.title" type="text" required class="w-full rounded-lg border px-3 py-2" placeholder="e.g. PRISAA Basketball Semifinals" />
            <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Live Source URL</label>
            <input v-model="form.url" type="url" required class="w-full rounded-lg border px-3 py-2" placeholder="Paste YouTube, Facebook, Vimeo or embed URL" />
            <p v-if="form.errors.url" class="text-red-600 text-sm mt-1">{{ form.errors.url }}</p>
            <div class="mt-2">
              <button type="button" class="text-xs text-[#0b66ff] hover:underline" @click="preview">Preview embed</button>
            </div>
          </div>

          <div v-if="previewUrl" class="rounded-lg overflow-hidden bg-black max-w-xl">
            <div class="aspect-video">
              <iframe :src="previewUrl" class="w-full h-full" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3">
            <Link href="/" class="px-3 py-2 rounded border">Cancel</Link>
            <button type="submit" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white font-semibold">Go Live</button>
          </div>
        </form>
      </div>
    </section>
  </AppLayout>
  
</template>
