<script setup lang="ts">
import ConfirmModal from '@/components/notes/ConfirmModal.vue';
import FolderModal from '@/components/notes/FolderModal.vue';
import FolderTree from '@/components/notes/FolderTree.vue';
import {
    compareNotes,
    filterQuery,
    NOTE_DRAG_TYPE,
    type FolderDialog,
    type FolderNode,
    type ListSort,
    type NoteCollection,
    type NoteData,
    type NoteDetailData,
    type NoteFilters,
    type NotePreferences,
    type NoteResource,
    type SortField,
    type TagNode,
} from '@/components/notes/note';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import NoteList from '@/components/notes/NoteList.vue';
import TagTree from '@/components/notes/TagTree.vue';
import VersionHistory from '@/components/notes/VersionHistory.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    notes: NoteCollection;
    note: NoteResource | null;
    tags: TagNode[];
    folders: FolderNode[];
    filters: NoteFilters;
    preferences: NotePreferences;
    sort: ListSort;
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
    // Recents stay in the order they were opened; other lists re-sort like the server.
    listedNotes.value =
        props.filters.view === 'recent'
            ? listedNotes.value.map((listed) => (listed.id === saved.id ? saved : listed))
            : [saved, ...listedNotes.value.filter((listed) => listed.id !== saved.id)].sort(compareNotes(props.sort));
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
    const { tag, folder } = props.filters;

    router.post(route('notes.store', filterQuery({ tag, folder })), { body: tag ? `\n\n#${tag}` : '', folder_id: folder });
};

const flattenFolders = (nodes: FolderNode[]): FolderNode[] => nodes.flatMap((node) => [node, ...flattenFolders(node.children)]);

const currentFolder = computed(() => flattenFolders(props.folders).find((folder) => folder.id === props.filters.folder) ?? null);

/**
 * The list's sort menu. In a folder the choice becomes that folder's own
 * order (Apple Notes style); elsewhere it changes the default.
 */
const setSort = (sort: SortField | null, direction: 'asc' | 'desc'): void => {
    const folderId = props.filters.view === 'notes' ? props.filters.folder : null;

    router.put(route('notes.sort.update'), { sort, direction, folder_id: folderId }, { preserveScroll: true, preserveState: true });
};

const sortItems = computed<DropdownMenuItem[][]>(() => {
    const fields: [SortField, string][] = [
        ['modified', 'Date edited'],
        ['created', 'Date created'],
        ['title', 'Title'],
    ];
    const directions: ['desc' | 'asc', string][] =
        props.sort.sort === 'title'
            ? [
                  ['asc', 'A to Z'],
                  ['desc', 'Z to A'],
              ]
            : [
                  ['desc', 'Newest first'],
                  ['asc', 'Oldest first'],
              ];
    const inFolder = props.filters.view === 'notes' && props.filters.folder !== null;

    return [
        [{ type: 'label', label: inFolder ? 'Sort this folder by' : 'Sort by' }],
        fields.map(([value, label]) => ({
            label,
            type: 'checkbox',
            checked: props.sort.sort === value,
            onUpdateChecked: () => setSort(value, value === 'title' ? 'asc' : 'desc'),
        })),
        directions.map(([value, label]) => ({
            label,
            type: 'checkbox',
            checked: props.sort.direction === value,
            onUpdateChecked: () => setSort(props.sort.sort, value),
        })),
        ...(inFolder && props.sort.scope === 'folder'
            ? [[{ label: 'Use the default order', icon: 'i-lucide-rotate-ccw', onSelect: () => setSort(null, 'desc') }]]
            : []),
    ];
});

const duplicateNote = (): void => {
    if (props.note) {
        router.post(route('notes.duplicate', { note: props.note.data.id, ...listQuery.value }));
    }
};

const folderDialog = ref<FolderDialog | null>(null);
const deletingFolder = ref<FolderNode | null>(null);

const confirmingFolderDelete = computed({
    get: () => deletingFolder.value !== null,
    set: (open: boolean) => {
        if (!open) {
            deletingFolder.value = null;
        }
    },
});

const onFolderAction = (type: 'create' | 'rename' | 'move' | 'delete', folder: FolderNode, parentId: number | null): void => {
    if (type === 'delete') {
        deletingFolder.value = folder;
    } else if (type === 'create') {
        folderDialog.value = { type, parent: folder };
    } else {
        folderDialog.value = { type, folder, parentId };
    }
};

const deleteFolder = (): void => {
    if (deletingFolder.value) {
        router.delete(route('notes.folders.destroy', { folder: deletingFolder.value.id }));
    }
};

/**
 * Notes dropped on a folder (or on "All notes" for no folder) move there.
 */
const moveNoteToFolder = (noteId: number, folderId: number | null): void => {
    router.put(route('notes.folder.move', { note: noteId }), { folder_id: folderId }, { preserveScroll: true, preserveState: true });
};

const onAllNotesDrop = (event: DragEvent): void => {
    const noteId = Number(event.dataTransfer?.getData(NOTE_DRAG_TYPE));

    if (noteId) {
        event.preventDefault();
        moveNoteToFolder(noteId, null);
    }
};

const acceptNoteDrag = (event: DragEvent): void => {
    if (event.dataTransfer?.types.includes(NOTE_DRAG_TYPE)) {
        event.preventDefault();
    }
};

const deleteNote = (): void => {
    if (props.note) {
        router.delete(route('notes.destroy', { note: props.note.data.id, ...listQuery.value }));
    }
};

const viewTitles = { notes: 'Notes', archive: 'Archive', trash: 'Trash', recent: 'Recents' } as const;

const listTitle = computed(() => {
    const base = currentFolder.value && props.filters.view === 'notes' ? currentFolder.value.name : viewTitles[props.filters.view];

    return props.filters.tag ? `${base === 'Notes' ? '' : `${base} · `}#${props.filters.tag}` : base;
});

const flattenTags = (nodes: TagNode[]): TagNode[] => nodes.flatMap((node) => [node, ...flattenTags(node.children)]);

const tagFilterItems = computed<DropdownMenuItem[]>(() => [
    { label: 'All notes', icon: 'i-lucide-notebook', to: route('notes.index', filterQuery({ q: props.filters.q })) },
    { label: 'Recents', icon: 'i-lucide-clock', to: route('notes.index', filterQuery({ view: 'recent' })) },
    { label: 'Archive', icon: 'i-lucide-archive', to: route('notes.index', filterQuery({ view: 'archive', q: props.filters.q })) },
    { label: 'Trash', icon: 'i-lucide-trash-2', to: route('notes.index', filterQuery({ view: 'trash', q: props.filters.q })) },
    { label: 'Settings', icon: 'i-lucide-settings', to: route('notes.settings.edit') },
    ...flattenFolders(props.folders).map((folder) => ({
        label: `${folder.name} (${folder.count})`,
        icon: 'i-lucide-folder',
        to: route('notes.index', { folder: folder.id }),
    })),
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

const showingHistory = ref(false);

const historyItem = (): DropdownMenuItem => ({ label: 'Version history', icon: 'i-lucide-history', onSelect: () => (showingHistory.value = true) });

const noteMenuItems = computed<DropdownMenuItem[][]>(() => {
    if (props.note?.data.trashed) {
        return [
            [{ label: 'Restore', icon: 'i-lucide-undo-2', onSelect: restoreNote }, historyItem()],
            [{ label: 'Delete forever', icon: 'i-lucide-trash', color: 'error', onSelect: () => (confirmingForceDelete.value = true) }],
        ];
    }

    return [
        [
            { label: isPinned.value ? 'Unpin note' : 'Pin note', icon: isPinned.value ? 'i-lucide-pin-off' : 'i-lucide-pin', onSelect: togglePin },
            props.note?.data.archived
                ? { label: 'Unarchive', icon: 'i-lucide-archive-restore', onSelect: unarchiveNote }
                : { label: 'Archive', icon: 'i-lucide-archive', onSelect: archiveNote },
            historyItem(),
            { label: 'Duplicate', icon: 'i-lucide-copy', onSelect: duplicateNote },
            {
                label: 'Move to folder…',
                icon: 'i-lucide-folder-input',
                onSelect: () => {
                    if (props.note) {
                        folderDialog.value = { type: 'move-note', noteId: props.note.data.id, folderId: props.note.data.folder_id };
                    }
                },
            },
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
                            :class="{ 'bg-elevated font-medium': !filters.tag && !filters.folder && filters.view === 'notes' }"
                            @dragover="acceptNoteDrag"
                            @drop="onAllNotesDrop"
                        >
                            <UiIcon name="i-lucide-notebook" class="text-dimmed size-4" />
                            All notes
                        </Link>
                        <div class="text-dimmed mt-2 flex items-center justify-between px-2 text-xs font-medium">
                            Folders
                            <UiButton
                                icon="i-lucide-folder-plus"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                aria-label="New folder"
                                @click="folderDialog = { type: 'create', parent: currentFolder }"
                            />
                        </div>
                        <FolderTree
                            v-if="folders.length"
                            :key="filters.folder ?? 0"
                            :folders="folders"
                            :selected="filters.folder"
                            @action="onFolderAction"
                            @drop-note="moveNoteToFolder"
                        />
                        <p v-else class="text-dimmed px-2 py-1 text-xs">No folders yet.</p>
                        <div class="text-dimmed mt-2 px-2 text-xs font-medium">Tags</div>
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
                            :href="route('notes.index', filterQuery({ view: 'recent' }))"
                            class="hover:bg-elevated flex items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                            :class="{ 'bg-elevated font-medium': filters.view === 'recent' }"
                        >
                            <UiIcon name="i-lucide-clock" class="text-dimmed size-4" />
                            Recents
                        </Link>
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
                        <Link :href="route('notes.settings.edit')" class="hover:bg-elevated flex items-center gap-2 rounded-md px-2 py-1.5 text-sm">
                            <UiIcon name="i-lucide-settings" class="text-dimmed size-4" />
                            Settings
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
                            <UiDropdownMenu v-if="filters.view !== 'recent'" :items="sortItems">
                                <UiButton icon="i-lucide-arrow-up-down" color="neutral" variant="ghost" aria-label="Sort notes" />
                            </UiDropdownMenu>
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
                    <NoteList
                        :notes="listedNotes"
                        :selected-id="note?.data.id ?? null"
                        :filters="filters"
                        :relative-dates="preferences['list.relative_dates']"
                        :show-snippets="preferences['list.show_snippets']"
                        @create="createNote"
                    />
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
                    <!-- Recently opened notes stay alive so each keeps its undo history while you switch between them. -->
                    <KeepAlive :max="10">
                        <NoteEditor
                            v-if="note"
                            :key="note.data.id"
                            :note="note.data"
                            :readonly="note.data.trashed"
                            :default-mode="preferences['editor.default_mode']"
                            :autosave-delay="preferences['editor.autosave_delay']"
                            :first-line-style="preferences['editor.first_line_style']"
                            :checked-to-bottom="preferences['checklists.checked_to_bottom']"
                            :spellcheck="preferences['editor.spellcheck']"
                            :text-direction="preferences['editor.text_direction']"
                            :readable-width="preferences['editor.readable_width']"
                            @saved="onNoteSaved"
                            @create-linked="(title) => (linkedTitle = title)"
                        />
                    </KeepAlive>
                    <div v-if="!note" class="text-dimmed flex h-full items-center justify-center p-4 text-center text-sm">
                        Select a note or create a new one.
                    </div>
                </template>
            </UiDashboardPanel>
        </template>

        <FolderModal :dialog="folderDialog" :folders="folders" @close="folderDialog = null" />
        <ConfirmModal
            v-model:open="confirmingFolderDelete"
            :title="`Delete “${deletingFolder?.name ?? ''}”?`"
            description="The folder and its sub-folders are deleted, and every note in them moves to the trash (restore puts notes back at the top level)."
            confirm-label="Delete folder"
            @confirm="deleteFolder"
        />
        <VersionHistory v-if="note" :key="note.data.id" v-model:open="showingHistory" :note-id="note.data.id" :readonly="note.data.trashed" />
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
