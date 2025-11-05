<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({ mods: Array })

function forceLogout(id) {
  if (!confirm('Log out this moderator from all devices now?')) return
  router.post(`/admin/accounts/${id}/logout`, {}, { preserveScroll: true })
}

function destroyUser(id) {
  if (!confirm('Delete this moderator account? This cannot be undone.')) return
  router.delete(`/admin/accounts/${id}`)
}
</script>

<template>
  <AppLayout title="Accounts">
    <div class="max-w-5xl mx-auto w-full px-4 py-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Moderator Accounts</h1>
        <Link href="/admin/accounts/create" class="px-3 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold">Create Account</Link>
      </div>

      <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Email</th>
              <th class="text-left px-4 py-2">Sports</th>
              <th class="text-left px-4 py-2">Status</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in props.mods" :key="u.id" class="border-t">
              <td class="px-4 py-2">{{ u.name }}</td>
              <td class="px-4 py-2">{{ u.email }}</td>
              <td class="px-4 py-2">
                <span v-if="u.sports.length" class="inline-flex flex-wrap gap-1">
                  <span v-for="s in u.sports" :key="s.id" class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ s.name }}</span>
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-4 py-2">
                <span :class="u.is_disabled ? 'text-red-600' : 'text-green-700'">{{ u.is_disabled ? 'Disabled' : 'Active' }}</span>
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <Link :href="`/admin/accounts/${u.id}/edit`" class="text-blue-600 hover:underline">Edit</Link>
                <button @click="forceLogout(u.id)" class="text-gray-700 hover:underline">Logout</button>
                <button @click="destroyUser(u.id)" class="text-red-600 hover:underline">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
  
</template>

