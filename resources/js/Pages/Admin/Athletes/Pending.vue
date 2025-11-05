<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ athletes: Array })

function approve(id) {
  router.post(`/admin/athletes/${id}/approve`, {}, { preserveScroll: true })
}
function reject(id) {
  router.post(`/admin/athletes/${id}/reject`, {}, { preserveScroll: true })
}
</script>

<template>
  <AppLayout title="Athlete Approvals">
    <div class="max-w-4xl mx-auto w-full px-4 py-8">
      <h1 class="text-2xl font-bold mb-6">Pending Athlete Submissions</h1>
      <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600">
            <tr>
              <th class="text-left px-4 py-2">Name</th>
              <th class="text-left px-4 py-2">Sport</th>
              <th class="text-left px-4 py-2">School</th>
              <th class="text-right px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in props.athletes" :key="a.id" class="border-t">
              <td class="px-4 py-2">{{ a.first_name }} {{ a.last_name }}</td>
              <td class="px-4 py-2">{{ a.sport?.name }}</td>
              <td class="px-4 py-2">{{ a.school?.name }}</td>
              <td class="px-4 py-2 text-right space-x-2">
                <button @click="approve(a.id)" class="text-green-700 hover:underline">Approve</button>
                <button @click="reject(a.id)" class="text-red-600 hover:underline">Reject</button>
              </td>
            </tr>
            <tr v-if="!props.athletes.length">
              <td colspan="4" class="px-4 py-6 text-center text-gray-500">No pending submissions</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

