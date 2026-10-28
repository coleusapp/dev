<script setup lang="ts">
import HighlightedText from '@/components/notes/HighlightedText.vue';
import { useUnlinkedMentions } from '@/composables/useUnlinkedMentions';
import { Link } from '@inertiajs/vue3';
import { useTemplateRef, watch } from 'vue';

const props = defineProps<{
    noteId: number;
}>();

const { mentions, loading, failed, linking, load, linkAll, forget } = useUnlinkedMentions(() => props.noteId);
const details = useTemplateRef<HTMLDetailsElement>('details');

const onToggle = (): void => {
    if (details.value?.open && mentions.value === null) {
        void load();
    }
};

/**
 * Forgets the mentions (another note, or this one renamed): looked up again
 * now if the section is open, otherwise when it's next opened.
 */
const reset = (): void => {
    forget();
    onToggle();
};

watch(() => props.noteId, reset);

defineExpose({ reset });
</script>

<template>
    <details ref="details" class="border-default border-t pt-2 text-sm" @toggle="onToggle">
        <summary class="text-muted cursor-pointer text-xs">
            Unlinked mentions<template v-if="mentions?.length"> ({{ mentions.length }})</template>
        </summary>
        <p v-if="loading" class="text-dimmed mt-1 px-2 text-xs">Looking for mentions…</p>
        <p v-else-if="failed" class="text-error mt-1 px-2 text-xs" role="alert">Couldn't look for mentions.</p>
        <p v-else-if="mentions && !mentions.length" class="text-dimmed mt-1 px-2 text-xs">No other note mentions this one without linking it.</p>
        <ul v-else-if="mentions" class="mt-1 flex flex-col gap-1">
            <li v-for="mention in mentions" :key="mention.id" class="flex items-start gap-2">
                <Link
                    :href="route('notes.show', { note: mention.id })"
                    preserve-state
                    class="hover:bg-elevated flex min-w-0 flex-1 flex-col gap-0.5 rounded px-2 py-1"
                >
                    <span class="truncate">{{ mention.title || 'Untitled note' }}</span>
                    <span
                        v-for="(line, index) in mention.context"
                        :key="index"
                        class="text-muted border-default line-clamp-2 border-s-2 ps-2 text-xs"
                    >
                        <HighlightedText :parts="line" />
                    </span>
                </Link>
                <UiButton
                    size="xs"
                    color="neutral"
                    variant="soft"
                    class="mt-1 shrink-0"
                    :loading="linking === mention.id"
                    :aria-label="`Link ${mention.count} mention${mention.count === 1 ? '' : 's'} in ${mention.title || 'this note'}`"
                    @click="linkAll(mention)"
                    >Link{{ mention.count > 1 ? ` ${mention.count}` : '' }}</UiButton
                >
            </li>
        </ul>
    </details>
</template>
