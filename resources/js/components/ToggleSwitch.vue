<script setup lang="ts">
defineProps<{
    modelValue: boolean;
    label: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        :aria-label="label"
        class="relative inline-flex h-[18px] w-8 shrink-0 items-center rounded-[5px] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bp-cyan/50"
        :class="modelValue ? 'bg-bp-ink dark:bg-bp-cyan' : 'bg-bp-rule dark:bg-white/15'"
        @click="emit('update:modelValue', !modelValue)"
    >
        <span
            class="inline-block size-3.5 rounded-[3px] shadow-sm transition"
            :class="modelValue ? 'translate-x-[16px] bg-bp-cyan dark:bg-bp-ink' : 'translate-x-0.5 bg-white'"
        />
    </button>
</template>
