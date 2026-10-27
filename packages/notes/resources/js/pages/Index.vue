<script setup lang="ts">
import ConfirmModal from '@/components/notes/ConfirmModal.vue';
import {
    compareNotes,
    filterQuery,
    type NoteCollection,
    type NoteData,
    type NoteDetailData,
    type NoteFilters,
    type NoteResource,
    type TagNode,
} from '@/components/notes/note';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import NoteList from '@/components/notes/NoteList.vue';
import TagTree from '@/components/notes/TagTree.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    notes: NoteCollection;
    note: NoteResource | null;
    tags: TagNode[];
    filters: NoteFilters;
}>();

/**
 * Query parameters that keep the current list filter on every link and redirect.
 */
const listQuery = computed(() => filterQuery(props.filters));

const search = ref(props.filters.q ?? '');

watch(
    () => props.filters.q,
    (q) => {
        if ((q ?? '') !== search.value.trim()) {
            search.value = q ?? '';
        }
    },
);

/**
 * Typing in the search box filters the list in place; the open note stays open.
 */
watchDebounced(
    search,
    (value) => {
        const q = value.trim();

        if (q === (props.filters.q ?? '')) {
            return;
        }

        const url = props.note ? route('notes.show', { note: props.note.data.id }) : route('notes.index');

        router.get(url, filterQuery({ ...props.filters, q }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['notes', 'filters'],
        });
    },
    { debounce: 300 },
);

const listedNotes = ref<NoteData[]>(props.notes.data);
const title = ref(props.note?.data.title ?? '');

watch(
    () => props.notes,
    (notes) => {
        listedNotes.value = notes.data;
    },
);

watch(
    () => props.note,
    (note) => {
        title.value = note?.data.title ?? '';
    },
);

/**
 * The open note's hashtags as the sidebar last saw them.
 */
const sidebarTags = ref(props.note?.data.tags.join(' ') ?? '');

watch(
    () => props.note,
    (note) => {
        sidebarTags.value = note?.data.tags.join(' ') ?? '';
    },
);

/**
 * Autosave doesn't reload the page, so the saved note's row is refreshed in
 * place and re-sorted the way the server orders the list. The tag sidebar is
 * reloaded only when the note's hashtags changed.
 */
const onNoteSaved = (saved: NoteDetailData): void => {
    listedNotes.value = [saved, ...listedNotes.value.filter((listed) => listed.id !== saved.id)].sort(compareNotes);
    title.value = saved.title;

    const savedTags = saved.tags.join(' ');

    if (savedTags !== sidebarTags.value) {
        sidebarTags.value = savedTags;
        router.reload({ only: ['tags'] });
    }
};

/**
 * A note made while a tag is selected starts with that tag, like Bear. The
 * search is dropped: a new, empty note would never match it.
 */
const createNote = (): void => {
    const tag = props.filters.tag;

    router.post(route('notes.store', tag ? { tag } : {}), { body: tag ? `\n\n#${tag}` : '' });
};

const deleteNote = (): void => {
    if (props.note) {
        router.delete(route('notes.destroy', { note: props.note.data.id, ...listQuery.value }));
    }
};

const viewTitles = { notes: 'Notes', archive: 'Archive', trash: 'Trash' } as const;

const listTitle = computed(() => {
    const base = viewTitles[props.filters.view];

    return props.filters.tag ? `${base === 'Notes' ? '' : `${base} · `}#${props.filters.tag}` : base;
});

const flattenTags = (nodes: TagNode[]): TagNode[] => nodes.flatMap((node) => [node, ...flattenTags(node.children)]);

const tagFilterItems = computed<DropdownMenuItem[]>(() => [
    { label: 'All notes', icon: 'i-lucide-notebook', to: route('notes.index', filterQuery({ q: props.filters.q })) },
    { label: 'Archive', icon: 'i-lucide-archive', to: route('notes.index', filterQuery({ view: 'archive', q: props.filters.q })) },
    { label: 'Trash', icon: 'i-lucide-trash-2', to: route('notes.index', filterQuery({ view: 'trash', q: props.filters.q })) },
    ...flattenTags(props.tags).map((tag) => ({
        label: `${tag.path} (${tag.count})`,
        icon: 'i-lucide-hash',
        to: route('notes.index', filterQuery({ tag: tag.path, q: props.filters.q })),
    })),
]);

const isPinned = computed(() => listedNotes.value.find((listed) => listed.id === props.note?.data.id)?.pinned ?? false);

const togglePin = (): void => {
    if (!props.note) {
        return;
    }

    router.visit(route(isPinned.value ? 'notes.unpin' : 'notes.pin', { note: props.note.data.id }), {
        method: isPinned.value ? 'delete' : 'post',
        preserveScroll: true,
        preserveState: true,
    });
};

const archiveNote = (): void => {
    if (props.note) {
        router.post(route('notes.archive', { note: props.note.data.id }));
    }
};

const unarchiveNote = (): void => {
    if (props.note) {
        router.delete(route('notes.unarchive', { note: props.note.data.id }));
    }
};

const restoreNote = (): void => {
    if (props.note) {
        router.post(route('notes.restore', { note: props.note.data.id }));
    }
};

/**
 * Clicking a [[wiki link]] to a note that doesn't exist offers to create it.
 */
const linkedTitle = ref<string | null>(null);

const creatingLinked = computed({
    get: () => linkedTitle.value !== null,
    set: (open: boolean) => {
        if (!open) {
            linkedTitle.value = null;
        }
    },
});

const createLinkedNote = (): void => {
    const title = linkedTitle.value;

    if (title) {
        router.post(route('notes.store'), { body: `# ${title}\n` });
    }
};

const confirmingForceDelete = ref(false);
const confirmingEmptyTrash = ref(false);

const forceDeleteNote = (): void => {
    if (props.note) {
        router.delete(route('notes.force-delete', { note: props.note.data.id }));
    }
};

const emptyTrash = (): void => {
    router.delete(route('notes.trash.empty'));
};

const noteMenuItems = computed<DropdownMenuItem[][]>(() => {
    if (props.note?.data.trashed) {
        return [
            [{ label: 'Restore', icon: 'i-lucide-undo-2', onSelect: restoreNote }],
            [{ label: 'Delete forever', icon: 'i-lucide-trash', color: 'error', onSelect: () => (confirmingForceDelete.value = true) }],
        ];
    }

    return [
        [
            { label: isPinned.value ? 'Unpin note' : 'Pin note', icon: isPinned.value ? 'i-lucide-pin-off' : 'i-lucide-pin', onSelect: togglePin },
            props.note?.data.archived
                ? { label: 'Unarchive', icon: 'i-lucide-archive-restore', onSelect: unarchiveNote }
                : { label: 'Archive', icon: 'i-lucide-archive', onSelect: archiveNote },
        ],
        [{ label: 'Move to trash', icon: 'i-lucide-trash-2', color: 'error', onSelect: deleteNote }],
    ];
});
</script>

<template>
    <Head :title="title || 'Notes'" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-0" class="hidden lg:flex" :default-size="14" :min-size="10" :max-size="20" resizable>
                <template #header>
                    <UiDashboardNavbar title="Tags">
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" />
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <nav class="flex flex-col gap-1" aria-label="Filter notes">
                        <Link
                            :href="route('notes.index', filters.q ? { q: filters.q } : {})"
                            class="hover:bg-elevated flex items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                            :class="{ 'bg-elevated font-medium': !filters.tag && filters.view === 'notes' }"
                        >
                            <UiIcon name="i-lucide-notebook" class="text-dimmed size-4" />
                            All notes
                        </Link>
                        <TagTree
                            v-if="tags.length"
                            :key="filters.tag ?? ''"
                            :tags="tags"
                            :selected="filters.view === 'notes' ? filters.tag : null"
                            :search="filters.q"
                        />
                        <p v-else class="text-dimmed px-2 py-1.5 text-xs">Add #tags to a note to organize it.</p>
                        <UiSeparator class="my-2" />
                        <Link
                            :href="route('notes.index', filterQuery({ view: 'archive', q: filters.q }))"
                            class="hover:bg-elevated flex items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                            :class="{ 'bg-elevated font-medium': filters.view === 'archive' }"
                        >
                            <UiIcon name="i-lucide-archive" class="text-dimmed size-4" />
                            Archive
                        </Link>
                        <Link
                            :href="route('notes.index', filterQuery({ view: 'trash', q: filters.q }))"
                            class="hover:bg-elevated flex items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                            :class="{ 'bg-elevated font-medium': filters.view === 'trash' }"
                        >
                            <UiIcon name="i-lucide-trash-2" class="text-dimmed size-4" />
                            Trash
                        </Link>
                    </nav>
                </template>
            </UiDashboardPanel>

            <UiDashboardPanel id="notes-1" :class="{ 'hidden lg:flex': note }" :default-size="18" :min-size="14" :max-size="24" resizable>
                <template #header>
                    <UiDashboardNavbar>
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" class="lg:hidden" />
                        </template>
                        <template #title>
                            <div class="flex flex-col">
                                <span>{{ listTitle }}</span>
                                <small class="text-dimmed text-xs font-normal">{{ listedNotes.length }} notes</small>
                            </div>
                        </template>
                        <template #right>
                            <UiDropdownMenu :items="tagFilterItems" class="lg:hidden">
                                <UiButton icon="i-lucide-hash" color="neutral" variant="ghost" aria-label="Filter by tag" class="lg:hidden" />
                            </UiDropdownMenu>
                            <UiButton
                                v-if="filters.view === 'trash'"
                                color="error"
                                variant="ghost"
                                size="sm"
                                :disabled="listedNotes.length === 0 && !filters.q && !filters.tag"
                                @click="confirmingEmptyTrash = true"
                                >Empty trash</UiButton
                            >
                            <UiButton v-else icon="i-lucide-file-plus" color="neutral" variant="ghost" aria-label="New note" @click="createNote" />
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <UiInput
                        v-model="search"
                        icon="i-lucide-search"
                        type="search"
                        placeholder="Search notes"
                        aria-label="Search notes"
                        class="mb-2 w-full"
                        size="sm"
                        :ui="{ trailing: 'pe-1' }"
                    >
                        <template v-if="search" #trailing>
                            <UiButton icon="i-lucide-x" color="neutral" variant="link" size="xs" aria-label="Clear search" @click="search = ''" />
                        </template>
                    </UiInput>
                    <NoteList :notes="listedNotes" :selected-id="note?.data.id ?? null" :filters="filters" @create="createNote" />
                </template>
            </UiDashboardPanel>

            <UiDashboardPanel id="notes-2" :class="note ? 'flex' : 'hidden lg:flex'">
                <template #header>
                    <UiDashboardNavbar :toggle="false">
                        <template #leading>
                            <UiButton
                                v-if="note"
                                class="lg:hidden"
                                icon="i-lucide-chevron-left"
                                color="neutral"
                                variant="ghost"
                                aria-label="Back to notes"
                                :to="route('notes.index', listQuery)"
                            />
                            <UiButton icon="i-lucide-file-plus" color="neutral" variant="subtle" @click="createNote">New note</UiButton>
                        </template>

                        <template #right>
                            <UiDropdownMenu v-if="note" :items="noteMenuItems">
                                <UiButton icon="i-lucide-ellipsis" color="neutral" variant="ghost" aria-label="Note actions" />
                            </UiDropdownMenu>
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <NoteEditor
                        v-if="note"
                        :key="note.data.id"
                        :note="note.data"
                        :readonly="note.data.trashed"
                        @saved="onNoteSaved"
                        @create-linked="(title) => (linkedTitle = title)"
                    />
                    <div v-else class="text-dimmed flex h-full items-center justify-center p-4 text-center text-sm">
                        Select a note or create a new one.
                    </div>
                </template>
            </UiDashboardPanel>
        </template>

        <ConfirmModal
            v-model:open="creatingLinked"
            :title="`Create “${linkedTitle ?? ''}”?`"
            description="There's no note with this title yet."
            confirm-label="Create note"
            :destructive="false"
            @confirm="createLinkedNote"
        />
        <ConfirmModal
            v-model:open="confirmingForceDelete"
            title="Delete this note forever?"
            description="It will be gone for good. This can't be undone."
            confirm-label="Delete forever"
            @confirm="forceDeleteNote"
        />
        <ConfirmModal
            v-model:open="confirmingEmptyTrash"
            title="Empty the trash?"
            description="Every note in the trash will be deleted for good, including any hidden by the current search. This can't be undone."
            confirm-label="Empty trash"
            @confirm="emptyTrash"
        />
    </NotesLayout>
</template>
