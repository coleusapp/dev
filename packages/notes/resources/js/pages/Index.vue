<script setup lang="ts">
import {
    filterQuery,
    type NoteCollection,
    type NoteCounts,
    type NoteFilters,
    type NoteResource,
    type NoteView,
    type Resource,
} from '@/components/notes/note';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import NoteList from '@/components/notes/NoteList.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import ConfirmModal from '@coleus/support/components/ui/modal/ConfirmModal.vue';
import { Head, router } from '@inertiajs/vue3';
import type { NavigationMenuItem } from '@nuxt/ui';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    notes: NoteCollection;
    note: NoteResource | null;
    counts: Resource<NoteCounts>;
    filters: Resource<NoteFilters>;
}>();

const listQuery = computed(() => filterQuery(props.filters.data));

const views: { view: NoteView; label: string; icon: string; count?: keyof NoteCounts }[] = [
    { view: 'notes', label: 'All notes', icon: 'i-lucide-notebook', count: 'notes' },
    { view: 'trash', label: 'Trash', icon: 'i-lucide-trash-2', count: 'trash' },
];

const listTitle = computed(() => views.find(({ view }) => view === props.filters.data.view)?.label ?? 'Notes');

const viewItem = (item: (typeof views)[number]): NavigationMenuItem => ({
    label: item.label,
    icon: item.icon,
    to: route('notes.index', filterQuery({ view: item.view, q: props.filters.data.q }), false),
    active: props.filters.data.view === item.view,
    badge: item.count ? props.counts.data[item.count] : undefined,
});

const listItems = computed<NavigationMenuItem[]>(() => views.filter((item) => item.view !== 'trash').map(viewItem));

const bottomItems = computed<NavigationMenuItem[]>(() => [...views.filter((item) => item.view === 'trash').map(viewItem)]);

const search = ref(props.filters.data.q ?? '');

watch(
    () => props.filters.data.q,
    (q) => {
        if ((q ?? '') !== search.value.trim()) {
            search.value = q ?? '';
        }
    },
);

watchDebounced(
    search,
    (value) => {
        const q = value.trim();

        if (q === (props.filters.data.q ?? '')) {
            return;
        }

        const url = props.note ? route('notes.show', { note: props.note.data.id }) : route('notes.index');

        router.get(url, filterQuery({ ...props.filters.data, q }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['notes', 'filters'],
        });
    },
    { debounce: 300 },
);

const onNoteSaved = (): void => {
    router.reload({ only: ['notes'] });
};

const createNote = (): void => {
    router.post(route('notes.store'), { body: '' });
};

const togglePin = (on: boolean): void => {
    if (!props.note) {
        return;
    }

    const url = route(on ? 'notes.pin' : 'notes.unpin', { note: props.note.data.id });
    const options = { preserveScroll: true, preserveState: true };

    if (on) {
        router.post(url, {}, options);
    } else {
        router.delete(url, options);
    }
};

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
        { label: note.pinned ? 'Unpin' : 'Pin', icon: 'i-lucide-pin', pressed: note.pinned, run: () => togglePin(!note.pinned) },
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
                                v-if="filters.data.view === 'trash'"
                                icon="i-lucide-trash"
                                color="error"
                                variant="ghost"
                                aria-label="Empty trash"
                                :disabled="counts.data.trash === 0"
                                @click="confirmingEmptyTrash = true"
                            />
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
                    <NoteList :notes="notes.data" :selected-id="note?.data.id ?? null" :filters="filters.data" @create="createNote" />
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
                    <NoteEditor v-if="note" :key="note.data.id" :note="note.data" @saved="onNoteSaved" @restore="restoreNote" />
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
                        :description="`${counts.data.trash} ${counts.data.trash === 1 ? 'note is' : 'notes are'} deleted for good.`"
                        confirm-label="Empty trash"
                        @confirm="emptyTrash"
                    />
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
