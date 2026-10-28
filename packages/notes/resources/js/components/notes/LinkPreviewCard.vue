<script setup lang="ts">
import type { OpenLinkPreview } from '@/composables/useLinkPreview';
import { renderDiagrams } from '@/lib/diagrams';
import { renderMath } from '@/lib/math';
import { Link } from '@inertiajs/vue3';
import { nextTick, onMounted, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    preview: OpenLinkPreview;
}>();

const body = useTemplateRef<HTMLElement>('body');
const typeset = (): void =>
    void nextTick(() => {
        void renderMath(body.value);
        void renderDiagrams(body.value);
    });

onMounted(typeset);
watch(() => props.preview.html, typeset);

defineEmits<{
    enter: [];
    leave: [];
}>();
</script>

<template>
    <!-- The HTML is rendered on the server with raw HTML escaped and unsafe links removed (NoteMarkdown). -->
    <div
        class="bg-default ring-default fixed z-50 flex max-h-80 w-96 max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-md shadow-lg ring"
        :style="{ left: `${preview.left}px`, top: `${preview.top + 6}px` }"
        role="dialog"
        :aria-label="`Preview of ${preview.title || 'note'}`"
        @mouseenter="$emit('enter')"
        @mouseleave="$emit('leave')"
    >
        <Link
            :href="route('notes.show', { note: preview.id })"
            preserve-state
            class="border-default hover:bg-elevated truncate border-b px-3 py-2 text-xs font-semibold"
            >{{ preview.title || 'Untitled note' }}</Link
        >
        <p v-if="preview.locked" class="text-dimmed flex items-center gap-1 px-3 py-2 text-xs">
            <UiIcon name="i-lucide-lock" class="size-3" />
            This note is locked.
        </p>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-else ref="body" class="note-preview pointer-events-none overflow-y-auto px-3 py-2" v-html="preview.html" />
    </div>
</template>
