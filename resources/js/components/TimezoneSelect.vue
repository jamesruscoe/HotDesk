<script setup lang="ts">
defineProps<{
    id: string;
    timezones: string[];
}>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <select
        :id="id"
        v-model="model"
        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 md:text-sm"
    >
        <option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone.replace(/_/g, ' ') }}</option>
    </select>
</template>
