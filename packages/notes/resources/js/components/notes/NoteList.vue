<script setup lang="ts">
import HighlightedText from '@/components/notes/HighlightedText.vue';
import { describeProperty, describeTags, filterQuery, NOTE_DRAG_TYPE, type NoteData, type NoteFilters } from '@/components/notes/note';
import { groupNotesByDate } from '@/lib/dateGroups';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        notes: NoteData[];
        selectedId: number | null;
        filters: NoteFilters;
        relativeDates?: boolean;
        showSnippets?: boolean;
        groupBy?: 'updated_at' | 'created_at' | null;
    }>(),
    { relativeDates: true, showSnippets: true, groupBy: null },
);

defineEmits<{
    create: [];
}>();

const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' });

const absoluteDate = (iso: string): string => dateFormat.format(new Date(iso));

const query = computed(() => filterQuery(props.filters));

/**
 * Date sections when grouping is on, otherwise one unlabeled section.
 */
const groups = computed(() => (props.groupBy ? groupNotesByDate(props.notes, props.groupBy) : [{ label: '', notes: props.notes }]));

const emptyMessage = computed(() => {
    if (props.filters.q) {
        return `No notes match “${props.filters.q}”${props.filters.tag ? ` in ${describeTags(props.filters.tag)}` : ''}.`;
    }

    if (props.filters.view === 'trash') {
        return 'The trash is empty.';
    }

    if (props.filters.view === 'recent') {
        return 'Notes you open show up here.';
    }

    if (props.filters.view === 'archive') {
        return props.filters.tag ? `No archived notes tagged ${describeTags(props.filters.tag)}.` : 'The archive is empty.';
    }

    if (props.filters.prop) {
        return `No notes with ${describeProperty(props.filters)}.`;
    }

    return props.filters.tag ? `No notes tagged ${describeTags(props.filters.tag)}.` : 'No notes yet.';
});
</script>

<template>
    <div v-if="notes.length === 0" class="flex h-full flex-col items-center justify-center gap-3 p-4 text-center">
        <UiIcon name="i-lucide-notebook-pen" class="text-dimmed size-8" />
        <p class="text-dimmed text-sm">{{ emptyMessage }}</p>
        <UiButton v-if="filters.view === 'notes'" icon="i-lucide-file-plus" color="neutral" variant="subtle" size="sm" @click="$emit('create')">{{
            filters.tag ? 'New note with this tag' : 'Create your first note'
        }}</UiButton>
    </div>
    <nav v-else class="flex flex-col">
        <section v-for="group in groups" :key="group.label" class="divide-default flex flex-col divide-y" :aria-label="group.label || undefined">
            <h3 v-if="group.label" class="bg-default text-dimmed sticky top-0 z-10 px-3 pt-3 pb-1 text-xs font-semibold">{{ group.label }}</h3>
            <Link
                v-for="note in group.notes"
                :key="note.id"
                :href="route('notes.show', { note: note.id, ...query })"
                preserve-state
                preserve-scroll
                draggable="true"
                @dragstart="(event: DragEvent) => event.dataTransfer?.setData(NOTE_DRAG_TYPE, String(note.id))"
                class="hover:bg-elevated flex flex-col gap-1 px-3 py-3"
                :class="{ 'bg-elevated': note.id === selectedId }"
            >
                <span class="flex items-center gap-1">
                    <span class="truncate text-sm font-semibold" :class="{ 'text-dimmed': !note.title }">
                        <HighlightedText v-if="note.highlights && note.title" :parts="note.highlights.title" />
                        <template v-else>{{ note.title || 'Empty note' }}</template>
                    </span>
                    <UiIcon v-if="note.pinned" name="i-lucide-pin" class="text-primary size-3 shrink-0" aria-label="Pinned" />
                </span>
                <span v-if="showSnippets && note.snippet" class="text-muted line-clamp-2 text-xs">
                    <HighlightedText v-if="note.highlights" :parts="note.highlights.snippet" />
                    <template v-else>{{ note.snippet }}</template>
                </span>
                <time class="text-dimmed text-xs" :datetime="note.updated_at">{{
                    relativeDates ? note.updated_at_for_humans : absoluteDate(note.updated_at)
                }}</time>
            </Link>
        </section>
    </nav>
</template>
