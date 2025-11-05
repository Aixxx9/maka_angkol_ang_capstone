<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ sports: Array })

const form = useForm({
  name: '',
  email: '',
  password: '',
  sports: [],
  is_disabled: false,
})

function submit() {
  form.post('/admin/accounts', {
    onSuccess: () => {},
  })
}
</script>

<template>
  <AppLayout title="Create Account">
    <div class="max-w-xl mx-auto w-full px-4 py-8">
      <h1 class="text-2xl font-bold mb-6">Create Moderator</h1>
      <form @submit.prevent="submit" class="space-y-4 bg-white p-6 border rounded-lg shadow-sm">
        <div>
          <label class="block text-sm font-medium mb-1">Name</label>
          <input v-model="form.name" class="w-full border rounded px-3 py-2" />
          <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Email</label>
          <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2" />
          <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Password</label>
          <input v-model="form.password" type="password" class="w-full border rounded px-3 py-2" />
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

