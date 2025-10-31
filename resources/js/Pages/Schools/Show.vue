<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const { props } = usePage()
const school = ref(props.school)
const achievements = props.achievements || []

// modal
const showEditModal = ref(false)

// preview
const preview = ref(school.value.logo_path)

// inertia form (this is what we will send)
const form = useForm({
  name: school.value.name ?? '',
  summary: school.value.summary ?? '',
  logo: null,
})

// file picker
function onPickLogo(e) {
  const file = e.target.files?.[0]
  form.logo = file || null

  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = file ? URL.createObjectURL(file) : school.value.logo_path
}

function clearLogo() {
  form.logo = null
  preview.value = school.value.logo_path
}

// ✅ THIS was the problem:
// 👉 Laravel + file + PUT = often empty
// 👉 so we send POST + _method: 'put'
function saveChanges() {
  form.post(`/schools/${school.value.slug}`, {
    method: 'put',          // ← spoof PUT
    forceFormData: true,    // ← important for file
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      // try to read what controller sent back
      const updated = page.props?.flash?.updatedSchool
      if (updated) {
        school.value = updated
        preview.value = updated.logo_path
      } else {
        // fallback: just update from form
        school.value.name = form.name
        school.value.summary = form.summary
      }

      showEditModal.value = false
      alert('✅ School updated successfully!')
    },
    onError: (errors) => {
      console.error('❌ Update failed:', errors)
      // show first error
      const first = Object.values(errors)[0]
      if (first) alert(first)
    },
  })
}

// delete
function deleteSchool() {
  if (!confirm(`Delete "${school.value.name}" ?`)) return
  router.delete(`/schools/${school.value.slug}`, {
    onSuccess: () => {
      router.visit('/schools')
    },
  })
}
</script>

<template>
  <AppLayout>
    <div class="min-h-screen bg-white text-[#111827] py-12 px-6 font-inter">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-center gap-8 mb-12">
          <div class="flex-shrink-0">
            <img
              :src="preview"
              alt="School Logo"
              class="h-40 w-40 object-cover rounded-full ring-4 ring-sky-400 shadow-lg"
            />
          </div>

          <div class="flex-1">
            <h1 class="text-3xl font-bold text-[#1f2937] mb-2">{{ school.name }}</h1>
            <p class="text-[#4b5563] text-lg leading-relaxed whitespace-pre-line">
              {{ school.summary || 'No description available for this school.' }}
            </p>
            <p class="text-sm text-[#6b7280] mt-3">
              Established: {{ school.created_at ? new Date(school.created_at).toLocaleDateString() : '—' }}
            </p>

            <div class="mt-6 flex gap-3">
              <button
                type="button"
                @click="showEditModal = true"
                class="px-4 py-2 bg-[#0b66ff] hover:bg-[#084dcc] text-white rounded-md font-semibold shadow transition"
              >
                ✏️ Edit
              </button>
              <button
                type="button"
                @click="deleteSchool"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-semibold shadow transition"
              >
                🗑️ Delete
              </button>
            </div>
          </div>
        </div>

        <hr class="border-[#e5e7eb] my-10" />

        <!-- Achievements -->
        <div>
          <h2 class="text-2xl font-semibold text-[#1f2937] mb-4">PRISAA Achievements</h2>
          <div v-if="achievements.length">
            <ul class="space-y-4">
              <li
                v-for="a in achievements"
                :key="a.title + a.year"
                class="p-4 border border-[#e5e7eb] rounded-lg shadow-sm bg-[#f9fafb]"
              >
                <div class="flex items-center justify-between mb-1">
                  <h3 class="text-lg font-semibold text-[#111827]">{{ a.title }}</h3>
                  <span class="text-sm text-[#6b7280]">{{ a.year }}</span>
                </div>
                <p class="text-[#4b5563] text-sm leading-relaxed">
                  {{ a.description }}
                </p>
              </li>
            </ul>
          </div>
          <p v-else class="text-[#6b7280]">No PRISAA achievements recorded yet.</p>
        </div>

        <!-- Back -->
        <div class="mt-12">
          <Link
            href="/schools"
            class="inline-flex items-center gap-2 bg-[#0b66ff] text-white px-4 py-2 rounded-md font-semibold hover:bg-[#084dcc] transition"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 18l-6-6 6-6" />
            </svg>
            Back
          </Link>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL ===================== -->
    <transition name="fade">
      <div
        v-if="showEditModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      >
        <div class="bg-white w-full max-w-md rounded-lg shadow-lg overflow-hidden">
          <div class="px-5 py-4 border-b flex items-center justify-between">
            <h2 class="font-semibold">Edit School</h2>
            <button @click="showEditModal = false" class="text-gray-500 hover:text-gray-700">✕</button>
          </div>

          <form @submit.prevent="saveChanges" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">School Name</label>
              <input v-model.trim="form.name" type="text" class="w-full border rounded px-3 py-2" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Short Summary</label>
              <textarea v-model="form.summary" rows="3" class="w-full border rounded px-3 py-2"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Logo</label>
              <input type="file" accept="image/*" @change="onPickLogo" />
              <div v-if="preview" class="mt-2">
                <img :src="preview" class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-200" />
                <button
                  type="button"
                  class="text-xs mt-1 text-red-500 underline"
                  @click="clearLogo"
                >
                  Remove
                </button>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
              <button type="button" class="px-3 py-2 rounded border" @click="showEditModal=false">Cancel</button>
              <button
                type="submit"
                class="px-4 py-2 rounded bg-[#0b66ff] hover:bg-[#084dcc] text-white font-semibold"
                :disabled="form.processing"
              >
                {{ form.processing ? 'Saving…' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </AppLayout>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
