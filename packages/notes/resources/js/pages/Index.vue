<script setup lang="ts">
import BookmarkList from '@/components/notes/BookmarkList.vue';
import BookmarkModal from '@/components/notes/BookmarkModal.vue';
import CommandPalette from '@/components/notes/CommandPalette.vue';
import { menuCommands, type PaletteGroup } from '@/components/notes/commands';
import ConfirmModal from '@/components/notes/ConfirmModal.vue';
import FolderModal from '@/components/notes/FolderModal.vue';
import FolderTree from '@/components/notes/FolderTree.vue';
import LockedNote from '@/components/notes/LockedNote.vue';
import {
    compareNotes,
    describeProperty,
    describeTags,
    filterQuery,
    NOTE_DRAG_TYPE,
    noteHeadings,
    SUGGESTED_SEARCHES,
    type BookmarkGroup,
    type BookmarkItem,
    type FolderDialog,
    type FolderNode,
    type ListSort,
    type NoteCollection,
    type NoteData,
    type NoteDetailData,
    type NoteFilters,
    type NotePreferences,
    type NoteResource,
    type SmartFolder,
    type SortField,
    type TagNode,
} from '@/components/notes/note';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import NoteList from '@/components/notes/NoteList.vue';
import PasswordModal from '@/components/notes/PasswordModal.vue';
import QuickSwitcher from '@/components/notes/QuickSwitcher.vue';
import SaveSearchModal from '@/components/notes/SaveSearchModal.vue';
import SearchTips from '@/components/notes/SearchTips.vue';
import ShortcutsModal from '@/components/notes/ShortcutsModal.vue';
import SmartFolderModal from '@/components/notes/SmartFolderModal.vue';
import TagTree from '@/components/notes/TagTree.vue';
import VersionHistory from '@/components/notes/VersionHistory.vue';
import { useNoteHistory } from '@/composables/useNoteHistory';
import { cachedKey, currentKeyParams, keyParams, setPassword, unlockWithPassword } from '@/composables/useNoteKeys';
import { useSearchScope } from '@/composables/useSearchScope';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { jsonHeaders } from '@/lib/http';
import { encryptText } from '@/lib/noteCrypto';
import { matchesShortcut, shortcutKeys, type ShortcutId } from '@/lib/shortcuts';
import { titleOf } from '@/lib/title';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { useEventListener, watchDebounced } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    notes: NoteCollection;
    note: NoteResource | null;
    tags: TagNode[];
    folders: FolderNode[];
    counts: { notes: number; archive: number; trash: number };
    bookmarks: BookmarkGroup[];
    smartFolders: SmartFolder[];
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

const page = usePage();

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
    // Recents and best-match search results keep their order; other lists re-sort like the server.
    listedNotes.value =
        props.filters.view === 'recent' || bestMatchFirst.value
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

    router.post(route('notes.store', filterQuery({ tag, folder })), {
        body: tag ? `\n\n${describeTags(tag).replace(/ \+ /g, ' ')}` : '',
        folder_id: folder,
    });
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

/**
 * Date sections need a date sort; Recents has its own order.
 */
const groupBy = computed(() =>
    props.preferences['list.group_by_date'] && props.sort.sort !== 'title' && props.filters.view !== 'recent'
        ? props.sort.sort === 'created'
            ? 'created_at'
            : 'updated_at'
        : null,
);

const toggleGroupByDate = (on: boolean): void => {
    router.put(route('notes.settings.update'), { list_group_by_date: on }, { preserveScroll: true, preserveState: true });
};

/**
 * Search results ranked by how well they match (search.sort preference).
 */
const bestMatchFirst = computed(
    () => Boolean(props.filters.q) && props.filters.view !== 'recent' && props.preferences['search.sort'] === 'relevance',
);

const setBestMatchFirst = (on: boolean): void => {
    router.put(route('notes.settings.update'), { search_sort: on ? 'relevance' : 'list' }, { preserveScroll: true, preserveState: true });
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
        ...(props.filters.q && props.filters.view !== 'recent'
            ? [
                  [
                      {
                          label: 'Best match first',
                          type: 'checkbox' as const,
                          checked: bestMatchFirst.value,
                          onUpdateChecked: (checked: boolean) => setBestMatchFirst(checked),
                      },
                  ],
              ]
            : []),
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
        [
            {
                label: 'Group by date',
                type: 'checkbox',
                checked: props.preferences['list.group_by_date'],
                disabled: props.sort.sort === 'title',
                onUpdateChecked: (checked: boolean) => toggleGroupByDate(checked),
            },
        ],
        ...(inFolder && props.sort.scope === 'folder'
            ? [[{ label: 'Use the default order', icon: 'i-lucide-rotate-ccw', onSelect: () => setSort(null, 'desc') }]]
            : []),
    ];
});

const noteBookmark = computed(() =>
    props.bookmarks.flatMap((group) => group.bookmarks).find((bookmark) => bookmark.type === 'note' && bookmark.note_id === props.note?.data.id),
);

const editingBookmark = ref<BookmarkItem | null>(null);
const editingSmartFolder = ref<SmartFolder | 'new' | null>(null);
const currentSmartFolder = computed(() => props.smartFolders.find((smart) => smart.id === props.filters.smart) ?? null);
const savingSearch = ref(false);
const switching = ref(false);
const showingCommands = ref(false);
const showingShortcuts = ref(false);
const searchInput = useTemplateRef<{ inputRef: HTMLInputElement | null }>('searchInput');

const focusSearch = (): void => {
    searchInput.value?.inputRef?.focus();
    searchInput.value?.inputRef?.select();
};

/**
 * Adds a search operator from the tips and puts the caret after it.
 */
const insertSearchOperator = (text: string): void => {
    search.value = `${search.value.trimEnd()} ${text}`.trimStart();
    void nextTick(() => {
        const input = searchInput.value?.inputRef;
        input?.focus();
        input?.setSelectionRange(search.value.length, search.value.length);
    });
};

/**
 * Opens the next or previous note in the list as it's shown (the first or
 * last one when none is open).
 */
const stepNote = (step: 1 | -1): void => {
    const notes = listedNotes.value;
    const index = notes.findIndex((listed) => listed.id === props.note?.data.id);
    const target = index === -1 ? notes[step === 1 ? 0 : notes.length - 1] : notes[index + step];

    if (target) {
        router.visit(route('notes.show', { note: target.id, ...filterQuery(props.filters) }), { preserveState: true, preserveScroll: true });
    }
};

const noteHistory = useNoteHistory(
    () => props.note?.data.id ?? null,
    (id) => route('notes.show', { note: id, ...filterQuery(props.filters) }),
);

/**
 * The page-wide part of the shortcut set (lib/shortcuts.ts); the editor
 * handles its own. These work from anywhere, including the editor: the
 * browser's "Open file" (Cmd+O) and "Print" (Cmd+P) aren't useful here.
 */
const pageShortcuts: [ShortcutId, () => void][] = [
    ['open-note', () => ((showingCommands.value = false), (switching.value = true))],
    ['commands', () => ((switching.value = false), (showingCommands.value = true))],
    ['new-note', () => createNote()],
    ['new-folder', () => (folderDialog.value = { type: 'create', parent: currentFolder.value })],
    ['search', focusSearch],
    ['next-note', () => stepNote(1)],
    ['previous-note', () => stepNote(-1)],
    ['back', noteHistory.back],
    ['forward', noteHistory.forward],
    ['shortcuts', () => (showingShortcuts.value = true)],
];

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (event.defaultPrevented || event.isComposing) {
        return;
    }

    const shortcut = pageShortcuts.find(([id]) => matchesShortcut(event, id));

    if (shortcut) {
        event.preventDefault();
        shortcut[1]();
    }
});

/**
 * A list can be saved as a search when something narrows it down.
 */
const canSaveSearch = computed(() =>
    Boolean(props.filters.q || props.filters.tag || props.filters.folder || props.filters.prop || props.filters.view !== 'notes'),
);

const suggestedSearchName = computed(() =>
    [props.filters.q ? `“${props.filters.q}”` : null, listTitle.value !== 'Notes' ? listTitle.value : null].filter(Boolean).join(' in '),
);
const headingsToBookmark = ref<{ noteId: number; headings: string[] } | null>(null);

const toggleBookmark = (): void => {
    if (!props.note) {
        return;
    }

    const options = { preserveScroll: true, preserveState: true };

    if (noteBookmark.value) {
        router.delete(route('notes.bookmarks.destroy', { bookmark: noteBookmark.value.id }), options);
    } else {
        router.post(route('notes.bookmarks.store', { note: props.note.data.id }), {}, options);
    }
};

/**
 * The heading to scroll to when the note was opened from a heading bookmark.
 */
const focusHeading = computed(() => new URL(page.url, window.location.origin).searchParams.get('heading'));

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
    const base =
        currentSmartFolder.value && props.filters.view === 'notes'
            ? currentSmartFolder.value.name
            : currentFolder.value && props.filters.view === 'notes'
              ? currentFolder.value.name
              : viewTitles[props.filters.view];

    const narrowing = [props.filters.tag ? describeTags(props.filters.tag) : null, describeProperty(props.filters) || null]
        .filter(Boolean)
        .join(' · ');

    return narrowing ? `${base === 'Notes' ? '' : `${base} · `}${narrowing}` : base;
});

const searchScope = useSearchScope({
    filters: () => props.filters,
    listTitle: () => listTitle.value,
    url: () => (props.note ? route('notes.show', { note: props.note.data.id }) : route('notes.index')),
});

const flattenTags = (nodes: TagNode[]): TagNode[] => nodes.flatMap((node) => [node, ...flattenTags(node.children)]);

/**
 * Every tag's full path, for tag autocomplete in the editor.
 */
const tagPaths = computed(() => flattenTags(props.tags).map((tag) => tag.path));

const tagFilterItems = computed<DropdownMenuItem[]>(() => [
    { label: 'All notes', icon: 'i-lucide-notebook', to: route('notes.index', filterQuery({ q: props.filters.q })) },
    { label: 'Recents', icon: 'i-lucide-clock', to: route('notes.index', filterQuery({ view: 'recent' })) },
    { label: 'Archive', icon: 'i-lucide-archive', to: route('notes.index', filterQuery({ view: 'archive', q: props.filters.q })) },
    { label: 'Trash', icon: 'i-lucide-trash-2', to: route('notes.index', filterQuery({ view: 'trash', q: props.filters.q })) },
    { label: 'Properties', icon: 'i-lucide-list-tree', to: route('notes.properties.index') },
    { label: 'Settings', icon: 'i-lucide-settings', to: route('notes.settings.edit') },
    ...props.smartFolders.map((smart) => ({
        label: `${smart.name} (${smart.count})`,
        icon: 'i-lucide-folder-search',
        to: route('notes.index', { smart: smart.id }),
    })),
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

/**
 * The read-only lock (B8.7): the note opens in the reading view and can't be changed until unlocked.
 */
const setReadOnly = (on: boolean): void => {
    if (!props.note) {
        return;
    }

    const url = route(on ? 'notes.read-only.store' : 'notes.read-only.destroy', { note: props.note.data.id });
    const options = { preserveScroll: true, preserveState: true };

    if (on) {
        router.post(url, {}, options);
    } else {
        router.delete(url, options);
    }
};

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
            props.note?.data.locked
                ? {
                      label: lockedEditor.value?.isOpen ? 'Remove password lock' : 'Remove password lock (open it first)',
                      icon: 'i-lucide-key-round',
                      disabled: !lockedEditor.value?.isOpen,
                      onSelect: () => void lockedEditor.value?.removeLock(),
                  }
                : { label: 'Lock with password…', icon: 'i-lucide-key-round', onSelect: () => void lockNote() },
            props.note?.data.read_only
                ? { label: 'Unlock editing', icon: 'i-lucide-lock-open', onSelect: () => setReadOnly(false) }
                : { label: 'Lock editing (read-only)', icon: 'i-lucide-lock', onSelect: () => setReadOnly(true) },
            props.note?.data.archived
                ? { label: 'Unarchive', icon: 'i-lucide-archive-restore', onSelect: unarchiveNote }
                : { label: 'Archive', icon: 'i-lucide-archive', onSelect: archiveNote },
            historyItem(),
            { label: 'Duplicate', icon: 'i-lucide-copy', onSelect: duplicateNote },
            {
                label: noteBookmark.value ? 'Remove bookmark' : 'Bookmark',
                icon: noteBookmark.value ? 'i-lucide-bookmark-x' : 'i-lucide-bookmark',
                onSelect: toggleBookmark,
            },
            {
                label: 'Bookmark a heading…',
                icon: 'i-lucide-bookmark-plus',
                onSelect: () => {
                    if (props.note) {
                        headingsToBookmark.value = { noteId: props.note.data.id, headings: noteHeadings(props.note.data.body) };
                    }
                },
            },
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

const editor = useTemplateRef<InstanceType<typeof NoteEditor>>('editor');
const lockedEditor = useTemplateRef<InstanceType<typeof LockedNote>>('lockedEditor');

/**
 * The editor settings, shared by plain and locked notes.
 */
const editorProps = computed(() => ({
    readonly: Boolean(props.note?.data.trashed || props.note?.data.read_only),
    defaultMode: props.preferences['editor.default_mode'],
    autosaveDelay: props.preferences['editor.autosave_delay'],
    firstLineStyle: props.preferences['editor.first_line_style'],
    checkedToBottom: props.preferences['checklists.checked_to_bottom'],
    spellcheck: props.preferences['editor.spellcheck'],
    textDirection: props.preferences['editor.text_direction'],
    readableWidth: props.preferences['editor.readable_width'],
    focusHeading: focusHeading.value,
    tags: tagPaths.value,
}));

/**
 * Locking a note with the notes password (O12.5): set the password the
 * first time, or enter it unless it's remembered; then the note's text is
 * encrypted here and only the result is sent. Kept-alive editors are
 * dropped afterwards so the readable text doesn't stay in memory.
 */
const keepAliveGeneration = ref(0);
const passwordPrompt = ref<{ mode: 'set' | 'enter'; keyId: number | null; hint: string | null; error: string | null; busy: boolean } | null>(null);

const lockWith = async (keyId: number, key: CryptoKey): Promise<void> => {
    if (!props.note || !editor.value) {
        return;
    }

    await editor.value.flush();
    const text = editor.value.currentBody();
    const response = await fetch(route('notes.lock.store', { note: props.note.data.id }), {
        method: 'POST',
        headers: jsonHeaders(),
        credentials: 'same-origin',
        body: JSON.stringify({ key_id: keyId, ...(await encryptText(key, text)), title: titleOf(text) }),
    });

    if (response.ok) {
        passwordPrompt.value = null;
        keepAliveGeneration.value++;
        router.reload({ only: ['note', 'notes'] });
    } else if (passwordPrompt.value) {
        passwordPrompt.value = { ...passwordPrompt.value, busy: false, error: "Couldn't lock the note." };
    }
};

const lockNote = async (): Promise<void> => {
    const params = await currentKeyParams();
    const remembered = params ? cachedKey(params.id) : null;

    if (params && remembered) {
        await lockWith(params.id, remembered);
    } else {
        passwordPrompt.value = { mode: params ? 'enter' : 'set', keyId: params?.id ?? null, hint: params?.hint ?? null, error: null, busy: false };
    }
};

const onPassword = async ({ password, hint }: { password: string; hint: string | null }): Promise<void> => {
    const prompt = passwordPrompt.value;

    if (!prompt) {
        return;
    }

    passwordPrompt.value = { ...prompt, busy: true, error: null };

    if (prompt.mode === 'set') {
        const created = await setPassword(password, hint);

        if ('error' in created) {
            passwordPrompt.value = { ...prompt, busy: false, error: created.error };
        } else {
            await lockWith(created.id, created.key);
        }

        return;
    }

    const params = prompt.keyId === null ? null : await keyParams(prompt.keyId);
    const key = params ? await unlockWithPassword(params, password) : null;

    if (key && prompt.keyId !== null) {
        await lockWith(prompt.keyId, key);
    } else {
        passwordPrompt.value = { ...prompt, busy: false, error: 'Wrong password.' };
    }
};

const visitList = (filters: Partial<NoteFilters>): void => router.visit(route('notes.index', filterQuery(filters)));

/**
 * Everything the command palette (Cmd/Ctrl+P) offers: the note's menu, the
 * open editor's actions, going places, creating things and sorting. The
 * note and sort groups come from the same menus the page shows.
 */
const commandGroups = computed<PaletteGroup[]>(() => [
    {
        id: 'create',
        label: 'Create',
        commands: [
            { id: 'note.new', label: 'New note', icon: 'i-lucide-file-plus', kbds: shortcutKeys('new-note'), run: createNote },
            {
                id: 'folder.new',
                label: 'New folder',
                icon: 'i-lucide-folder-plus',
                kbds: shortcutKeys('new-folder'),
                run: () => (folderDialog.value = { type: 'create', parent: currentFolder.value }),
            },
            { id: 'smart.new', label: 'New smart folder', icon: 'i-lucide-folder-search', run: () => (editingSmartFolder.value = 'new') },
            ...(canSaveSearch.value
                ? [{ id: 'search.save', label: 'Save this list as a search', icon: 'i-lucide-bookmark-plus', run: () => (savingSearch.value = true) }]
                : []),
        ],
    },
    { id: 'note', label: 'Note', commands: props.note ? menuCommands('note', noteMenuItems.value) : [] },
    { id: 'editor', label: 'Editor', commands: props.note ? ((editor.value ?? lockedEditor.value)?.paletteCommands ?? []) : [] },
    {
        id: 'go',
        label: 'Go to',
        commands: [
            { id: 'go.open', label: 'Open note…', icon: 'i-lucide-search', kbds: shortcutKeys('open-note'), run: () => (switching.value = true) },
            ...(noteHistory.canGoBack.value
                ? [{ id: 'go.back', label: 'Go back', icon: 'i-lucide-arrow-left', kbds: shortcutKeys('back'), run: noteHistory.back }]
                : []),
            ...(noteHistory.canGoForward.value
                ? [{ id: 'go.forward', label: 'Go forward', icon: 'i-lucide-arrow-right', kbds: shortcutKeys('forward'), run: noteHistory.forward }]
                : []),
            { id: 'go.search', label: 'Search notes', icon: 'i-lucide-text-search', kbds: shortcutKeys('search'), run: focusSearch },
            ...SUGGESTED_SEARCHES.map((suggestion) => ({
                id: `go.suggested.${suggestion.query}`,
                label: `Search: ${suggestion.label.toLowerCase()}`,
                icon: suggestion.icon,
                run: () => (search.value = suggestion.query),
            })),
            ...(listedNotes.value.length
                ? [
                      {
                          id: 'go.next',
                          label: 'Next note in the list',
                          icon: 'i-lucide-arrow-down',
                          kbds: shortcutKeys('next-note'),
                          run: () => stepNote(1),
                      },
                      {
                          id: 'go.previous',
                          label: 'Previous note in the list',
                          icon: 'i-lucide-arrow-up',
                          kbds: shortcutKeys('previous-note'),
                          run: () => stepNote(-1),
                      },
                  ]
                : []),
            { id: 'go.notes', label: 'All notes', icon: 'i-lucide-notebook', run: () => visitList({}) },
            { id: 'go.recent', label: 'Recents', icon: 'i-lucide-clock', run: () => visitList({ view: 'recent' }) },
            { id: 'go.archive', label: 'Archive', icon: 'i-lucide-archive', run: () => visitList({ view: 'archive' }) },
            { id: 'go.trash', label: 'Trash', icon: 'i-lucide-trash-2', run: () => visitList({ view: 'trash' }) },
            { id: 'go.properties', label: 'Properties', icon: 'i-lucide-list-tree', run: () => router.visit(route('notes.properties.index')) },
            { id: 'go.settings', label: 'Settings', icon: 'i-lucide-settings', run: () => router.visit(route('notes.settings.edit')) },
            {
                id: 'go.shortcuts',
                label: 'Keyboard shortcuts',
                icon: 'i-lucide-keyboard',
                kbds: shortcutKeys('shortcuts'),
                run: () => (showingShortcuts.value = true),
            },
            ...props.smartFolders.map((smart) => ({
                id: `go.smart.${smart.id}`,
                label: `Smart folder: ${smart.name}`,
                icon: 'i-lucide-folder-search',
                run: () => visitList({ smart: smart.id }),
            })),
            ...flattenFolders(props.folders).map((folder) => ({
                id: `go.folder.${folder.id}`,
                label: `Folder: ${folder.name}`,
                icon: 'i-lucide-folder',
                run: () => visitList({ folder: folder.id }),
            })),
        ],
    },
    {
        id: 'list',
        label: 'List',
        commands: [
            ...(props.filters.view === 'recent' ? [] : menuCommands('sort', sortItems.value, 'Sort: ')),
            ...(props.filters.view === 'trash'
                ? [{ id: 'trash.empty', label: 'Empty trash', icon: 'i-lucide-trash', run: () => (confirmingEmptyTrash.value = true) }]
                : []),
        ],
    },
]);
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
                            :class="{
                                'bg-elevated font-medium':
                                    !filters.tag && !filters.folder && !filters.smart && !filters.prop && filters.view === 'notes',
                            }"
                            @dragover="acceptNoteDrag"
                            @drop="onAllNotesDrop"
                        >
                            <UiIcon name="i-lucide-notebook" class="text-dimmed size-4" />
                            All notes
                            <span class="text-dimmed ms-auto text-xs">{{ counts.notes }}</span>
                        </Link>
                        <template v-if="bookmarks.length">
                            <div class="text-dimmed mt-2 px-2 text-xs font-medium">Bookmarks</div>
                            <BookmarkList :groups="bookmarks" @edit="(bookmark) => (editingBookmark = bookmark)" />
                        </template>
                        <div class="text-dimmed mt-2 flex items-center justify-between px-2 text-xs font-medium">
                            Smart folders
                            <UiButton
                                icon="i-lucide-folder-search"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                aria-label="New smart folder"
                                @click="editingSmartFolder = 'new'"
                            />
                        </div>
                        <div
                            v-for="smart in smartFolders"
                            :key="smart.id"
                            class="group hover:bg-elevated flex items-center gap-1 rounded-md pe-1 text-sm"
                            :class="{ 'bg-elevated font-medium': filters.smart === smart.id }"
                        >
                            <Link :href="route('notes.index', { smart: smart.id })" class="flex min-w-0 flex-1 items-center gap-2 px-2 py-1.5">
                                <UiIcon name="i-lucide-folder-search" class="text-dimmed size-3.5 shrink-0" />
                                <span class="truncate">{{ smart.name }}</span>
                                <span class="text-dimmed ms-auto text-xs">{{ smart.count }}</span>
                            </Link>
                            <UiButton
                                icon="i-lucide-settings-2"
                                :aria-label="`Edit ${smart.name}`"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                class="opacity-0 group-hover:opacity-100 focus-visible:opacity-100"
                                @click="editingSmartFolder = smart"
                            />
                        </div>
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
                            <span class="text-dimmed ms-auto text-xs">{{ counts.archive }}</span>
                        </Link>
                        <Link
                            :href="route('notes.index', filterQuery({ view: 'trash', q: filters.q }))"
                            class="hover:bg-elevated flex items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                            :class="{ 'bg-elevated font-medium': filters.view === 'trash' }"
                        >
                            <UiIcon name="i-lucide-trash-2" class="text-dimmed size-4" />
                            Trash
                            <span class="text-dimmed ms-auto text-xs">{{ counts.trash }}</span>
                        </Link>
                        <Link
                            :href="route('notes.properties.index')"
                            class="hover:bg-elevated flex items-center gap-2 rounded-md px-2 py-1.5 text-sm"
                        >
                            <UiIcon name="i-lucide-list-tree" class="text-dimmed size-4" />
                            Properties
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
                            <UiButton
                                v-if="canSaveSearch"
                                icon="i-lucide-bookmark-plus"
                                color="neutral"
                                variant="ghost"
                                aria-label="Save search"
                                @click="savingSearch = true"
                            />
                            <UiDropdownMenu v-if="filters.view !== 'recent'" :items="sortItems">
                                <UiButton icon="i-lucide-arrow-up-down" color="neutral" variant="ghost" aria-label="Sort notes" />
                            </UiDropdownMenu>
                            <UiTooltip text="Open note" :kbds="shortcutKeys('open-note')">
                                <UiButton icon="i-lucide-search" color="neutral" variant="ghost" aria-label="Open note" @click="switching = true" />
                            </UiTooltip>
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
                        ref="searchInput"
                        v-model="search"
                        icon="i-lucide-search"
                        type="search"
                        placeholder="Search notes"
                        aria-label="Search notes"
                        class="mb-2 w-full"
                        size="sm"
                        :ui="{ trailing: 'pe-1' }"
                    >
                        <template #trailing>
                            <UiButton
                                v-if="search"
                                icon="i-lucide-x"
                                color="neutral"
                                variant="link"
                                size="xs"
                                aria-label="Clear search"
                                @click="search = ''"
                            />
                            <SearchTips v-else @insert="insertSearchOperator" @search="(query) => (search = query)" />
                        </template>
                    </UiInput>
                    <div v-if="searchScope.label.value" class="mb-2 flex gap-1" role="group" aria-label="Search in">
                        <UiButton
                            size="xs"
                            color="neutral"
                            :variant="searchScope.narrowed.value ? 'soft' : 'ghost'"
                            :aria-pressed="searchScope.narrowed.value"
                            class="min-w-0"
                            :ui="{ label: 'truncate' }"
                            @click="searchScope.searchInScope"
                            >{{ searchScope.label.value }}</UiButton
                        >
                        <UiButton
                            size="xs"
                            color="neutral"
                            :variant="searchScope.narrowed.value ? 'ghost' : 'soft'"
                            :aria-pressed="!searchScope.narrowed.value"
                            class="shrink-0"
                            @click="searchScope.searchEverywhere"
                            >All notes</UiButton
                        >
                    </div>
                    <NoteList
                        :notes="listedNotes"
                        :selected-id="note?.data.id ?? null"
                        :filters="filters"
                        :relative-dates="preferences['list.relative_dates']"
                        :show-snippets="preferences['list.show_snippets']"
                        :group-by="groupBy"
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
                            <div class="hidden items-center lg:flex">
                                <UiTooltip text="Back" :kbds="shortcutKeys('back')">
                                    <UiButton
                                        icon="i-lucide-arrow-left"
                                        color="neutral"
                                        variant="ghost"
                                        aria-label="Back"
                                        :disabled="!noteHistory.canGoBack.value"
                                        @click="noteHistory.back"
                                    />
                                </UiTooltip>
                                <UiTooltip text="Forward" :kbds="shortcutKeys('forward')">
                                    <UiButton
                                        icon="i-lucide-arrow-right"
                                        color="neutral"
                                        variant="ghost"
                                        aria-label="Forward"
                                        :disabled="!noteHistory.canGoForward.value"
                                        @click="noteHistory.forward"
                                    />
                                </UiTooltip>
                            </div>
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
                    <KeepAlive :key="keepAliveGeneration" :max="10">
                        <NoteEditor
                            v-if="note && !note.data.locked"
                            ref="editor"
                            :key="note.data.id"
                            :note="note.data"
                            v-bind="editorProps"
                            @saved="onNoteSaved"
                            @create-linked="(title) => (linkedTitle = title)"
                        />
                    </KeepAlive>
                    <!-- Locked notes are never kept alive: their text leaves memory when another note opens. -->
                    <LockedNote
                        v-if="note && note.data.locked"
                        ref="lockedEditor"
                        :key="note.data.id"
                        :note="note.data"
                        v-bind="editorProps"
                        @saved="onNoteSaved"
                        @create-linked="(title) => (linkedTitle = title)"
                    />
                    <div v-if="!note" class="text-dimmed flex h-full items-center justify-center p-4 text-center text-sm">
                        Select a note or create a new one.
                    </div>
                </template>
            </UiDashboardPanel>
        </template>

        <SmartFolderModal :editing="editingSmartFolder" :folders="folders" @close="editingSmartFolder = null" />
        <QuickSwitcher v-model:open="switching" :filters="filters" />
        <CommandPalette v-model:open="showingCommands" :groups="commandGroups" />
        <ShortcutsModal v-model:open="showingShortcuts" />
        <PasswordModal
            :open="passwordPrompt !== null"
            :mode="passwordPrompt?.mode ?? 'enter'"
            :title="passwordPrompt?.mode === 'set' ? 'Set a notes password' : 'Lock with password'"
            :description="
                passwordPrompt?.mode === 'set'
                    ? 'Locked notes are encrypted in this browser with this password before they are saved.'
                    : 'Enter your notes password to lock this note.'
            "
            :hint="passwordPrompt?.hint"
            :error="passwordPrompt?.error"
            :busy="passwordPrompt?.busy"
            @update:open="(open) => !open && (passwordPrompt = null)"
            @submit="onPassword"
        />
        <SaveSearchModal v-model:open="savingSearch" :filters="filters" :suggested-name="suggestedSearchName" />
        <BookmarkModal
            :editing="editingBookmark"
            :headings-of="headingsToBookmark"
            :groups="bookmarks"
            @close="
                editingBookmark = null;
                headingsToBookmark = null;
            "
        />
        <FolderModal :dialog="folderDialog" :folders="folders" @close="folderDialog = null" />
        <ConfirmModal
            v-model:open="confirmingFolderDelete"
            :title="`Delete “${deletingFolder?.name ?? ''}”?`"
            description="The folder and its sub-folders are deleted, and every note in them moves to the trash (restore puts notes back at the top level)."
            confirm-label="Delete folder"
            @confirm="deleteFolder"
        />
        <VersionHistory
            v-if="note"
            :key="note.data.id"
            v-model:open="showingHistory"
            :note-id="note.data.id"
            :readonly="note.data.trashed || note.data.read_only"
        />
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
