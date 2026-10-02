<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <!-- HotDesk mark: a desk outline on the plan, with your booking in amber. -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" :class="className" v-bind="$attrs" aria-hidden="true">
        <rect x="2.75" y="2.75" width="18.5" height="18.5" rx="3.25" stroke="#4CC3E6" stroke-width="2.5" />
        <rect x="12.5" y="6.5" width="5" height="5" rx="1.25" fill="#FFC24B" />
    </svg>
</template>
