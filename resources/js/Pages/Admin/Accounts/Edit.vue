<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link, router } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
  sports: Array,
})

const form = useForm({
  name: props.user?.name || '',
  password: '',
  sports: (props.user?.sports || []).map(s => s.id),
  is_disabled: props.user?.is_disabled || false,
})

function submit() {
  form.put(`/admin/accounts/${props.user.id}`)
}

function forceLogout() {
  if (!confirm('Log out this moderator from all devices now?')) return
  router.post(`/admin/accounts/${props.user.id}/logout`)
}

function destroyUser() {
  if (!confirm('Delete this moderator account? This cannot be undone.')) return
  router.delete(`/admin/accounts/${props.user.id}`)
}
</script>

<template>
  <AppLayout title="Edit Account">
    <div class="max-w-xl mx-auto w-full px-4 py-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Edit Moderator</h1>
        <div class="space-x-2">
          <button @click="forceLogout" class="px-3 py-2 rounded border">Force Logout</button>
          <button @click="destroyUser" class="px-3 py-2 rounded border border-red-600 text-red-700">Delete</button>
        </div>
      </div>
      <form @submit.prevent="submit" class="space-y-4 bg-white p-6 border rounded-lg shadow-sm">
        <div>
          <label class="block text-sm font-medium mb-1">Name</label>
          <input v-model="form.name" class="w-full border rounded px-3 py-2" />
          <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Password</label>
          <input v-model="form.password" type="password" class="w-full border rounded px-3 py-2" placeholder="Leave blank to keep" />
          <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Sports</label>
          <select v-model="form.sports" multiple class="w-full border rounded px-3 py-2 h-32">
            <option v-for="s in props.sports" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple sports</p>
          <p v-if="form.errors.sports" class="text-sm text-red-600">{{ form.errors.sports }}</p>
        </div>

        <div class="flex items-center gap-2">
          <input id="disabled" type="checkbox" v-model="form.is_disabled" />
          <label for="disabled" class="text-sm">Disabled</label>
        </div>

        <div class="flex items-center justify-end gap-2">
          <Link href="/admin/accounts" class="px-3 py-2 rounded border">Cancel</Link>
          <button class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold" :disabled="form.processing">
            {{ form.processing ? 'Saving…' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

