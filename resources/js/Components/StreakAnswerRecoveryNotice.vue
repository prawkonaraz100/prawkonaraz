<script setup lang="ts">
defineProps<{ message: string | null; canRetry: boolean; busy: boolean }>();
defineEmits<{ retry: [] }>();
</script>

<template>
    <div v-if="message" class="rounded-xl bg-[#fff1ef] px-4 py-3 text-sm text-[#a43228]">
        <p role="alert">{{ message }}</p>
        <button v-if="canRetry" type="button" :disabled="busy" class="mt-3 min-h-11 rounded-xl bg-[#ffc644] px-5 font-semibold text-[#071b45] disabled:opacity-60" @click="$emit('retry')">Ponów zapis odpowiedzi</button>
    </div>
</template>
