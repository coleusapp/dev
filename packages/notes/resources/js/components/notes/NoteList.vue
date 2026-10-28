<script setup lang="ts">
import HighlightedText from '@/components/notes/HighlightedText.vue';
import { describeProperty, describeTags, filterQuery, NOTE_DRAG_TYPE, type NoteData, type NoteFilters } from '@/components/notes/note';
import { groupNotesByDate } from '@/lib/dateGroups';
import { Link } from '@inertiajs/vue3';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

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

const list = useTemplateRef<HTMLElement>('list');

/**
 * Keeps the open note's row in view when it changes from elsewhere (the
 * next / previous note shortcuts, the quick switcher).
 */
watch(
    () => props.selectedId,
    (id) => void nextTick(() => list.value?.querySelector(`[data-note-id="${id}"]`)?.scrollIntoView({ block: 'nearest' })),
);

/**
 * Matching lines shown per search result before "Show more".
 */
const COLLAPSED_MATCHES = 3;

const expanded = ref(new Set<number>());

const toggleExpanded = (id: number): void => {
    const next = new Set(expanded.value);

    if (!next.delete(id)) {
        next.add(id);
    }

    expanded.value = next;
};

const visibleMatches = (note: NoteData) => (note.matches?.lines ?? []).slice(0, expanded.value.has(note.id) ? undefined : COLLAPSED_MATCHES);

/**
 * "4 notes · 9 matching lines" above search results.
 */
const resultSummary = computed(() => {
    const lines = props.notes.reduce((sum, note) => sum + (note.matches?.total ?? 0), 0);
    const notes = `${props.notes.length} ${props.notes.length === 1 ? 'note' : 'notes'}`;

    return lines ? `${notes} · ${lines} matching ${lines === 1 ? 'line' : 'lines'}` : notes;
});

watch(
    () => props.filters.q,
    () => (expanded.value = new Set()),
);

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
    <nav v-else ref="list" class="flex flex-col">
        <p v-if="filters.q" class="text-dimmed px-3 pb-1 text-xs" role="status">{{ resultSummary }}</p>
        <section v-for="group in groups" :key="group.label" class="divide-default flex flex-col divide-y" :aria-label="group.label || undefined">
            <h3 v-if="group.label" class="bg-default text-dimmed sticky top-0 z-10 px-3 pt-3 pb-1 text-xs font-semibold">{{ group.label }}</h3>
            <div v-for="note in group.notes" :key="note.id" class="flex flex-col" :class="{ 'bg-elevated': note.id === selectedId }">
                <Link
                    :data-note-id="note.id"
                    :href="route('notes.show', { note: note.id, ...query })"
                    preserve-state
                    preserve-scroll
                    draggable="true"
                    @dragstart="(event: DragEvent) => event.dataTransfer?.setData(NOTE_DRAG_TYPE, String(note.id))"
                    class="hover:bg-elevated flex flex-col gap-1 px-3 py-3"
                >
                    <span class="flex items-center gap-1">
                        <span class="truncate text-sm font-semibold" :class="{ 'text-dimmed': !note.title }">
                            <HighlightedText v-if="note.highlights && note.title" :parts="note.highlights.title" />
                            <template v-else>{{ note.title || 'Empty note' }}</template>
                        </span>
                        <UiIcon v-if="note.pinned" name="i-lucide-pin" class="text-primary size-3 shrink-0" aria-label="Pinned" />
                        <UiIcon v-if="note.read_only" name="i-lucide-lock" class="text-dimmed size-3 shrink-0" aria-label="Read-only" />
                    </span>
                    <ul v-if="note.matches?.lines.length" class="flex flex-col gap-0.5" aria-label="Matching lines">
                        <li
                            v-for="match in visibleMatches(note)"
                            :key="match.line"
                            class="text-muted border-default line-clamp-1 border-s-2 ps-2 text-xs"
                        >
                            <HighlightedText :parts="match.parts" />
                        </li>
                    </ul>
                    <span v-else-if="showSnippets && note.snippet" class="text-muted line-clamp-2 text-xs">
                        <HighlightedText v-if="note.highlights" :parts="note.highlights.snippet" />
                        <template v-else>{{ note.snippet }}</template>
                    </span>
                    <time class="text-dimmed text-xs" :datetime="note.updated_at">{{
                        relativeDates ? note.updated_at_for_humans : absoluteDate(note.updated_at)
                    }}</time>
                </Link>
                <button
                    v-if="note.matches && note.matches.total > COLLAPSED_MATCHES"
                    type="button"
                    class="text-dimmed hover:text-default -mt-2 px-3 pb-2 text-start text-xs"
                    :aria-expanded="expanded.has(note.id)"
                    @click="toggleExpanded(note.id)"
                >
                    <template v-if="!expanded.has(note.id)">Show {{ note.matches.total - COLLAPSED_MATCHES }} more</template>
                    <template v-else-if="note.matches.total > note.matches.lines.length">
                        Show fewer ({{ note.matches.total - note.matches.lines.length }} more in the note)
                    </template>
                    <template v-else>Show fewer</template>
                </button>
            </div>
        </section>
    </nav>
</template>
