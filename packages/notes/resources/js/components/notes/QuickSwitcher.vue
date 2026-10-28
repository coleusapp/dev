<script setup lang="ts">
import HighlightedText from '@/components/notes/HighlightedText.vue';
import { filterQuery, type NoteFilters } from '@/components/notes/note';
import { fuzzyRank, highlightPositions } from '@/lib/fuzzy';
import { jsonHeaders } from '@/lib/http';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

type SwitcherNote = { id: number; title: string; aliases: string[]; archived: boolean };

type Row = { type: 'note'; note: SwitcherNote; name: string; positions: number[] } | { type: 'create'; title: string };

const MAX_RESULTS = 50;

const props = defineProps<{
    filters: NoteFilters;
}>();

const open = defineModel<boolean>('open', { required: true });

const notes = ref<SwitcherNote[]>([]);
const loading = ref(false);
const query = ref('');
const selected = ref(0);
const list = ref<HTMLElement | null>(null);

/**
 * Fetched fresh on every open, so new and renamed notes are always there.
 */
const load = async (): Promise<void> => {
    loading.value = true;

    try {
        const response = await fetch(route('notes.switcher'), { headers: jsonHeaders(), credentials: 'same-origin' });
        const payload = await response.json().catch(() => null);
        notes.value = response.ok && Array.isArray(payload?.data) ? payload.data : [];
    } finally {
        loading.value = false;
    }
};

watch(open, (isOpen) => {
    if (isOpen) {
        query.value = '';
        selected.value = 0;
        void load();
    }
});

const title = computed(() => query.value.trim());

const rows = computed<Row[]>(() => {
    const found: Row[] = fuzzyRank(notes.value, title.value, (note) => [note.title, ...note.aliases])
        .slice(0, MAX_RESULTS)
        .map(({ item, name, match }) => ({ type: 'note', note: item, name, positions: match.positions }));
    const lower = title.value.toLowerCase();
    const exists = notes.value.some((note) => [note.title, ...note.aliases].some((name) => name.toLowerCase() === lower));

    return title.value && !exists ? [...found, { type: 'create', title: title.value }] : found;
});

watch(rows, () => (selected.value = 0));

const move = (step: 1 | -1): void => {
    if (!rows.value.length) {
        return;
    }

    selected.value = (selected.value + step + rows.value.length) % rows.value.length;
    void nextTick(() => list.value?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' }));
};

const create = (newTitle: string): void => {
    open.value = false;
    router.post(route('notes.store'), { body: `# ${newTitle}\n` });
};

const choose = (row: Row | undefined): void => {
    if (!row) {
        return;
    }

    if (row.type === 'create') {
        create(row.title);

        return;
    }

    open.value = false;
    router.visit(route('notes.show', { note: row.note.id, ...filterQuery(props.filters) }), { preserveState: true });
};

/**
 * Up/Down move, Enter opens, Shift+Enter always creates a note with the
 * typed title (even when one by that name exists).
 */
const onKeydown = (event: KeyboardEvent): void => {
    if (event.isComposing) {
        return;
    }

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        move(event.key === 'ArrowDown' ? 1 : -1);
    } else if (event.key === 'Enter' && event.shiftKey) {
        event.preventDefault();
        if (title.value) {
            create(title.value);
        }
    } else if (event.key === 'Enter') {
        event.preventDefault();
        choose(rows.value[selected.value]);
    }
};
</script>

<template>
    <UiModal v-model:open="open" title="Open note" description="Jump to a note by title or alias." :ui="{ header: 'sr-only', body: 'p-0 sm:p-0' }">
        <template #body>
            <div class="flex flex-col">
                <div class="border-default flex items-center gap-2 border-b px-3">
                    <UiIcon name="i-lucide-search" class="text-dimmed size-4 shrink-0" />
                    <input
                        v-model="query"
                        class="placeholder:text-dimmed h-12 w-full bg-transparent text-sm focus:outline-none"
                        placeholder="Find or create a note…"
                        aria-label="Note title"
                        role="combobox"
                        aria-controls="quick-switcher-results"
                        aria-expanded="true"
                        :aria-activedescendant="rows.length ? `quick-switcher-row-${selected}` : undefined"
                        autofocus
                        @keydown="onKeydown"
                    />
                    <UiIcon v-if="loading" name="i-lucide-loader-circle" class="text-dimmed size-4 shrink-0 animate-spin" />
                </div>
                <ul id="quick-switcher-results" ref="list" role="listbox" aria-label="Notes" class="max-h-80 overflow-y-auto p-1">
                    <li
                        v-for="(row, index) in rows"
                        :id="`quick-switcher-row-${index}`"
                        :key="row.type === 'note' ? row.note.id : 'create'"
                        role="option"
                        :aria-selected="index === selected"
                        class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                        :class="{ 'bg-elevated': index === selected }"
                        @mousemove="selected = index"
                        @click="choose(row)"
                    >
                        <template v-if="row.type === 'note'">
                            <UiIcon name="i-lucide-file-text" class="text-dimmed size-4 shrink-0" />
                            <span class="truncate" :class="{ 'text-dimmed': !row.note.title }">
                                <HighlightedText
                                    v-if="row.name === row.note.title && row.note.title"
                                    :parts="highlightPositions(row.name, row.positions)"
                                />
                                <template v-else>{{ row.note.title || 'Empty note' }}</template>
                            </span>
                            <span v-if="row.name !== row.note.title" class="text-dimmed flex min-w-0 items-center gap-1 truncate text-xs">
                                <UiIcon name="i-lucide-corner-down-right" class="size-3 shrink-0" />
                                <HighlightedText :parts="highlightPositions(row.name, row.positions)" />
                            </span>
                            <UiBadge v-if="row.note.archived" color="neutral" variant="subtle" size="sm" class="ms-auto shrink-0">Archived</UiBadge>
                        </template>
                        <template v-else>
                            <UiIcon name="i-lucide-file-plus" class="text-primary size-4 shrink-0" />
                            <span class="truncate">Create “{{ row.title }}”</span>
                        </template>
                    </li>
                    <li v-if="!rows.length && !loading" class="text-dimmed px-2 py-6 text-center text-sm">
                        {{ notes.length ? 'No matching notes.' : 'No notes yet. Type a title to create one.' }}
                    </li>
                </ul>
                <p class="border-default text-dimmed flex gap-3 border-t px-3 py-2 text-xs">
                    <span><kbd>↑</kbd> <kbd>↓</kbd> to move</span>
                    <span><kbd>Enter</kbd> to open</span>
                    <span><kbd>Shift</kbd>+<kbd>Enter</kbd> to create</span>
                </p>
            </div>
        </template>
    </UiModal>
</template>
