<script setup lang="ts">
import type { HighlightPart } from '@/components/notes/note';

defineProps<{
    parts: HighlightPart[];
}>();
</script>

<template>
    <span
        ><template v-for="(part, index) in parts" :key="index"
            ><mark v-if="part.match" class="rounded-sm bg-yellow-200 text-inherit dark:bg-yellow-700">{{ part.text }}</mark
            ><template v-else>{{ part.text }}</template></template
        ></span
    >
</template>
