<script setup lang="ts">
import ConfirmModal from '@/components/notes/ConfirmModal.vue';
import {
    compareNotes,
    filterQuery,
    type ListSort,
    type NoteCollection,
    type NoteData,
    type NoteDetailData,
    type NoteFilters,
    type NotePreferences,
    type NoteResource,
    type NoteView,
    type SortField,
} from '@/components/notes/note';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import NoteList from '@/components/notes/NoteList.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import type { DropdownMenuItem, NavigationMenuItem } from '@nuxt/ui';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    notes: NoteCollection;
    note: NoteResource | null;
    counts: { notes: number; bookmarks: number; archive: number; trash: number };
    filters: NoteFilters;
    preferences: NotePreferences;
    sort: ListSort;
}>();

/**
 * Query parameters that keep the current list filter on every link and redirect.
 */
const listQuery = computed(() => filterQuery(props.filters));

const views: { view: NoteView; label: string; icon: string; count?: keyof typeof props.counts }[] = [
    { view: 'notes', label: 'All notes', icon: 'i-lucide-notebook', count: 'notes' },
    { view: 'bookmarks', label: 'Bookmarks', icon: 'i-lucide-bookmark', count: 'bookmarks' },
    { view: 'recent', label: 'Recents', icon: 'i-lucide-clock' },
    { view: 'archive', label: 'Archive', icon: 'i-lucide-archive', count: 'archive' },
    { view: 'trash', label: 'Trash', icon: 'i-lucide-trash-2', count: 'trash' },
];

const listTitle = computed(() => views.find(({ view }) => view === props.filters.view)?.label ?? 'Notes');

/**
 * Nuxt UI treats absolute URLs as external links (opening a new tab), so its
 * links get relative ones: route(…, …, false), like the app sidebar's.
 */
const viewItem = (item: (typeof views)[number]): NavigationMenuItem => ({
    label: item.label,
    icon: item.icon,
    to: route('notes.index', filterQuery({ view: item.view, q: props.filters.q }), false),
    active: props.filters.view === item.view,
    badge: item.count ? props.counts[item.count] : undefined,
});

/**
 * The lists at the top of the sidebar; Trash and Settings sit at the bottom.
 */
const listItems = computed<NavigationMenuItem[]>(() => views.filter((item) => item.view !== 'trash').map(viewItem));

const bottomItems = computed<NavigationMenuItem[]>(() => [
    ...views.filter((item) => item.view === 'trash').map(viewItem),
    { label: 'Settings', icon: 'i-lucide-settings', to: route('notes.settings.edit', {}, false) },
]);

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

watch(
    () => props.notes,
    (notes) => (listedNotes.value = notes.data),
);

/**
 * Autosave doesn't reload the page, so the saved note's row is refreshed in
 * place and re-sorted the way the server orders the list (Recents keeps
 * its own order: most recently edited first).
 */
const onNoteSaved = (saved: NoteDetailData): void => {
    const others = listedNotes.value.filter((listed) => listed.id !== saved.id);

    listedNotes.value = props.filters.view === 'recent' ? [saved, ...others] : [saved, ...others].sort(compareNotes(props.sort));
};

const createNote = (): void => {
    router.post(route('notes.store'), { body: '' });
};

const setSort = (sort: SortField, direction: 'asc' | 'desc'): void => {
    router.put(route('notes.sort.update'), { sort, direction }, { preserveScroll: true, preserveState: true });
};

const sortFields: [SortField, string][] = [
    ['modified', 'Date edited'],
    ['created', 'Date created'],
];

const sortDirections: ['desc' | 'asc', string][] = [
    ['desc', 'Newest first'],
    ['asc', 'Oldest first'],
];

const sortItems = computed<DropdownMenuItem[][]>(() => [
    [{ type: 'label', label: 'Sort by' }],
    sortFields.map(([value, label]) => ({
        label,
        type: 'checkbox' as const,
        checked: props.sort.sort === value,
        onUpdateChecked: () => setSort(value, props.sort.direction),
    })),
    sortDirections.map(([value, label]) => ({
        label,
        type: 'checkbox' as const,
        checked: props.sort.direction === value,
        onUpdateChecked: () => setSort(props.sort.sort, value),
    })),
]);

/**
 * Pin, bookmark and archive turn a timestamp on the note on or off.
 */
const toggle = (action: 'pin' | 'bookmark' | 'archive', on: boolean): void => {
    if (!props.note) {
        return;
    }

    const url = route(`notes.${on ? action : `un${action}`}`, { note: props.note.data.id });
    const options = { preserveScroll: true, preserveState: true };

    if (on) {
        router.post(url, {}, options);
    } else {
        router.delete(url, options);
    }
};

/**
 * Delete moves a note to the trash; there it can be restored or deleted for good.
 */
const deleteNote = (): void => {
    if (props.note) {
        router.delete(route('notes.destroy', { note: props.note.data.id, ...listQuery.value }));
    }
};

const restoreNote = (): void => {
    if (props.note) {
        router.post(route('notes.restore', { note: props.note.data.id }));
    }
};

const confirmingForceDelete = ref(false);

const forceDeleteNote = (): void => {
    if (props.note) {
        router.delete(route('notes.force-delete', { note: props.note.data.id }));
    }
};

const confirmingEmptyTrash = ref(false);

const emptyTrash = (): void => {
    router.delete(route('notes.trash.empty'));
};

type NoteAction = { label: string; icon: string; pressed?: boolean; color?: 'neutral' | 'error'; run: () => void };

/**
 * The open note's actions, as buttons in its header. Pin, bookmark and
 * archive show as pressed while on; a trashed note offers restore and
 * delete forever instead.
 */
const noteActions = computed<NoteAction[]>(() => {
    const note = props.note?.data;

    if (!note) {
        return [];
    }

    if (note.trashed) {
        return [
            { label: 'Restore', icon: 'i-lucide-undo-2', run: restoreNote },
            { label: 'Delete forever', icon: 'i-lucide-trash', color: 'error', run: () => (confirmingForceDelete.value = true) },
        ];
    }

    return [
        { label: note.pinned ? 'Unpin' : 'Pin', icon: 'i-lucide-pin', pressed: note.pinned, run: () => toggle('pin', !note.pinned) },
        {
            label: note.bookmarked ? 'Remove bookmark' : 'Bookmark',
            icon: 'i-lucide-bookmark',
            pressed: note.bookmarked,
            run: () => toggle('bookmark', !note.bookmarked),
        },
        {
            label: note.archived ? 'Unarchive' : 'Archive',
            icon: 'i-lucide-archive',
            pressed: note.archived,
            run: () => toggle('archive', !note.archived),
        },
        { label: 'Move to trash', icon: 'i-lucide-trash-2', color: 'error', run: deleteNote },
    ];
});
</script>

<template>
    <Head :title="note?.data.title || listTitle" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-0" class="hidden lg:flex" :default-size="14" :min-size="10" :max-size="20" resizable>
                <template #header>
                    <UiDashboardNavbar title="Notes">
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" />
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <UiNavigationMenu :items="listItems" orientation="vertical" />
                    <UiNavigationMenu :items="bottomItems" orientation="vertical" class="mt-auto" />
                </template>
            </UiDashboardPanel>

            <!-- The body spaces its children gap-4 sm:gap-6; the list sits right under the search. -->
            <UiDashboardPanel
                id="notes-1"
                :class="{ 'hidden lg:flex': note }"
                :default-size="18"
                :min-size="14"
                :max-size="24"
                resizable
                :ui="{ body: 'gap-2 sm:gap-2' }"
            >
                <template #header>
                    <UiDashboardNavbar :title="listTitle">
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" class="lg:hidden" />
                        </template>
                        <template #right>
                            <UiButton
                                v-if="filters.view === 'trash'"
                                icon="i-lucide-trash"
                                color="error"
                                variant="ghost"
                                aria-label="Empty trash"
                                :disabled="counts.trash === 0"
                                @click="confirmingEmptyTrash = true"
                            />
                            <UiDropdownMenu v-if="filters.view !== 'recent'" :items="sortItems">
                                <UiButton icon="i-lucide-arrow-up-down" color="neutral" variant="ghost" aria-label="Sort notes" />
                            </UiDropdownMenu>
                            <UiButton icon="i-lucide-file-plus" color="neutral" variant="ghost" aria-label="New note" @click="createNote" />
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
                        class="w-full"
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
                                :to="route('notes.index', listQuery, false)"
                            />
                        </template>

                        <template #right>
                            <UiTooltip v-for="action in noteActions" :key="action.label" :text="action.label">
                                <UiButton
                                    :icon="action.icon"
                                    :aria-label="action.label"
                                    :aria-pressed="action.pressed"
                                    :color="action.color ?? 'neutral'"
                                    :variant="action.pressed ? 'soft' : 'ghost'"
                                    @click="action.run"
                                />
                            </UiTooltip>
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <NoteEditor
                        v-if="note"
                        :key="note.data.id"
                        :note="note.data"
                        :autosave-delay="preferences['editor.autosave_delay']"
                        :text-direction="preferences['editor.text_direction']"
                        :readable-width="preferences['editor.readable_width']"
                        @saved="onNoteSaved"
                        @restore="restoreNote"
                    />
                    <div v-else class="text-dimmed flex h-full items-center justify-center p-4 text-center text-sm">
                        Select a note or create a new one.
                    </div>

                    <!-- Inside a panel: NotesLayout only renders its panels, not its default slot. -->
                    <ConfirmModal
                        v-model:open="confirmingForceDelete"
                        :title="`Delete “${note?.data.title || 'Untitled note'}” forever?`"
                        description="It can't be restored afterwards."
                        confirm-label="Delete forever"
                        @confirm="forceDeleteNote"
                    />
                    <ConfirmModal
                        v-model:open="confirmingEmptyTrash"
                        title="Empty the trash?"
                        :description="`${counts.trash} ${counts.trash === 1 ? 'note is' : 'notes are'} deleted for good.`"
                        confirm-label="Empty trash"
                        @confirm="emptyTrash"
                    />
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
