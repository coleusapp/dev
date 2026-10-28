<script setup lang="ts">
import { SUGGESTED_SEARCHES } from '@/components/notes/note';

/**
 * Suggested searches (run on click) and the search box's operators (added
 * to the search on click), from Search\SearchQuery on the server.
 */
const emit = defineEmits<{
    insert: [text: string];
    search: [query: string];
}>();

const tips: { text: string; description: string }[] = [
    { text: '"', description: '"exact phrase"' },
    { text: 'tag:', description: 'tag:work, includes work/…' },
    { text: 'folder:', description: 'folder:Trips, or folder:"Trip plans"' },
    { text: 'task:', description: 'a checklist item mentioning a word' },
    { text: 'task-todo:', description: 'an unticked item (alone: any)' },
    { text: 'task-done:', description: 'a ticked item (alone: any)' },
    { text: 'has:', description: 'checklist, tags, links, properties, locked or attachments' },
];
</script>

<template>
    <UiPopover>
        <UiButton icon="i-lucide-circle-help" color="neutral" variant="link" size="xs" aria-label="Search tips" />
        <template #content>
            <div class="flex w-72 flex-col gap-0.5 p-2 text-xs">
                <p class="text-dimmed px-2 pb-1 font-medium">Suggested</p>
                <button
                    v-for="suggestion in SUGGESTED_SEARCHES"
                    :key="suggestion.query"
                    type="button"
                    class="hover:bg-elevated flex items-center gap-2 rounded px-2 py-1 text-start"
                    @click="emit('search', suggestion.query)"
                >
                    <UiIcon :name="suggestion.icon" class="text-dimmed size-3.5 shrink-0" />
                    {{ suggestion.label }}
                </button>
                <p class="text-dimmed mt-2 px-2 pb-1 font-medium">Operators: combine with words, everything must match</p>
                <button
                    v-for="tip in tips"
                    :key="tip.text"
                    type="button"
                    class="hover:bg-elevated flex items-baseline gap-2 rounded px-2 py-1 text-start"
                    @click="emit('insert', tip.text)"
                >
                    <code class="font-mono font-semibold">{{ tip.text }}</code>
                    <span class="text-muted">{{ tip.description }}</span>
                </button>
            </div>
        </template>
    </UiPopover>
</template>
