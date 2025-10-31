<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
const form = useForm({ name:'', summary:'', location:'', logo:null })
function submit(){ form.post('/schools') }
</script>
<template>
<AppLayout>
<h1 class="text-xl font-semibold mb-4">Add School</h1>
<form @submit.prevent="submit" class="space-y-4 max-w-xl">
<input v-model="form.name" placeholder="School Name" class="w-full bg-neutral-900 rounded-lg p-3"/>
<input v-model="form.location" placeholder="Location" class="w-full bg-neutral-900 rounded-lg p-3"/>
<textarea v-model="form.summary" placeholder="Summary" class="w-full bg-neutral-900 rounded-lg p-3"/>
<input type="file" @change="e=>form.logo=e.target.files[0]" class="block"/>
<button class="bg-amber-500 text-black rounded-lg px-4 py-2">Save</button>
</form>
</AppLayout>
</template>