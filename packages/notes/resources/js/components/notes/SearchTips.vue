<script setup lang="ts">
/**
 * The search box's operators (Search\SearchQuery on the server); picking
 * one adds it to the search.
 */
const emit = defineEmits<{
    insert: [text: string];
}>();

const tips: { text: string; description: string }[] = [
    { text: '"', description: '"exact phrase"' },
    { text: 'tag:', description: 'tag:work, includes work/…' },
    { text: 'folder:', description: 'folder:Trips, or folder:"Trip plans"' },
    { text: 'task:', description: 'a checklist item mentioning a word' },
    { text: 'task-todo:', description: 'an unticked item (alone: any)' },
    { text: 'task-done:', description: 'a ticked item (alone: any)' },
];
</script>

<template>
    <UiPopover>
        <UiButton icon="i-lucide-circle-help" color="neutral" variant="link" size="xs" aria-label="Search tips" />
        <template #content>
            <div class="flex w-72 flex-col gap-0.5 p-2 text-xs">
                <p class="text-dimmed px-2 pb-1">Combine words with these. Everything must match.</p>
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
