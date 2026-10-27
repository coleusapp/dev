<script setup lang="ts">
import { compareNotes, type NoteCollection, type NoteData, type NoteResource } from '@/components/notes/note';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import NoteList from '@/components/notes/NoteList.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    notes: NoteCollection;
    note: NoteResource | null;
}>();

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
 * Autosave doesn't reload the page, so the saved note's row is refreshed in
 * place and re-sorted the way the server orders the list.
 */
const onNoteSaved = (saved: NoteData): void => {
    listedNotes.value = [saved, ...listedNotes.value.filter((listed) => listed.id !== saved.id)].sort(compareNotes);
    title.value = saved.title;
};

const createNote = (): void => {
    router.post(route('notes.store'), { body: '' });
};

const deleteNote = (): void => {
    if (props.note) {
        router.delete(route('notes.destroy', { note: props.note.data.id }));
    }
};

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

const noteMenuItems = computed<DropdownMenuItem[][]>(() => [
    [{ label: isPinned.value ? 'Unpin note' : 'Pin note', icon: isPinned.value ? 'i-lucide-pin-off' : 'i-lucide-pin', onSelect: togglePin }],
    [{ label: 'Delete note', icon: 'i-lucide-trash-2', color: 'error', onSelect: deleteNote }],
]);
</script>

<template>
    <Head :title="title || 'Notes'" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-1" :class="{ 'hidden lg:flex': note }" :default-size="18" :min-size="14" :max-size="24" resizable>
                <template #header>
                    <UiDashboardNavbar>
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" />
                        </template>
                        <template #title>
                            <div class="flex flex-col">
                                <span>Notes</span>
                                <small class="text-dimmed text-xs font-normal">{{ listedNotes.length }} notes</small>
                            </div>
                        </template>
                        <template #right>
                            <UiButton icon="i-lucide-file-plus" color="neutral" variant="ghost" aria-label="New note" @click="createNote" />
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <NoteList :notes="listedNotes" :selected-id="note?.data.id ?? null" @create="createNote" />
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
                                :to="route('notes.index')"
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
                    <NoteEditor v-if="note" :key="note.data.id" :note="note.data" @saved="onNoteSaved" />
                    <div v-else class="text-dimmed flex h-full items-center justify-center p-4 text-center text-sm">
                        Select a note or create a new one.
                    </div>
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
