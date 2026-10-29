<script setup lang="ts">
import ConfirmModal from '@/components/notes/ConfirmModal.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type CanvasSummary = { id: number; name: string; cards: number; updated_at: string | null };

defineProps<{
    canvases: CanvasSummary[];
}>();

/**
 * Every canvas (O10.1) by name, with how many cards each holds.
 */
const creating = ref(false);
const deleting = ref<CanvasSummary | null>(null);
const form = useForm({ name: '' });

const openCreate = (): void => {
    form.reset();
    form.clearErrors();
    creating.value = true;
};

const create = (): void => {
    form.post(route('notes.canvases.store'), { onSuccess: () => (creating.value = false) });
};

const remove = (): void => {
    if (deleting.value) {
        router.delete(route('notes.canvases.destroy', { canvas: deleting.value.id }), { preserveScroll: true });
    }
};

const edited = (iso: string | null): string =>
    iso ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '';
</script>

<template>
    <Head title="Canvases" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-canvases">
                <template #header>
                    <UiDashboardNavbar title="Canvases">
                        <template #leading>
                            <UiButton
                                icon="i-lucide-chevron-left"
                                color="neutral"
                                variant="ghost"
                                aria-label="Back to notes"
                                :to="route('notes.index')"
                            />
                        </template>
                        <template #right>
                            <UiButton icon="i-lucide-plus" size="sm" @click="openCreate">New canvas</UiButton>
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <div class="mx-auto w-full max-w-3xl">
                        <div v-if="!canvases.length" class="flex flex-col items-center gap-3 py-16 text-center">
                            <UiIcon name="i-lucide-layout-dashboard" class="text-dimmed size-10" />
                            <p class="font-medium">No canvases yet</p>
                            <p class="text-dimmed text-sm">A canvas is an endless board of cards (text, notes, files, links) joined by arrows.</p>
                            <UiButton icon="i-lucide-plus" @click="openCreate">New canvas</UiButton>
                        </div>
                        <ul v-else class="divide-default divide-y">
                            <li v-for="canvas in canvases" :key="canvas.id" class="flex items-center gap-3 py-2">
                                <UiIcon name="i-lucide-layout-dashboard" class="text-dimmed size-4 shrink-0" />
                                <Link
                                    :href="route('notes.canvases.show', { canvas: canvas.id })"
                                    class="min-w-0 flex-1 truncate font-medium hover:underline"
                                    >{{ canvas.name }}</Link
                                >
                                <span class="text-dimmed text-xs tabular-nums">{{ canvas.cards }} card{{ canvas.cards === 1 ? '' : 's' }}</span>
                                <span class="text-dimmed hidden text-xs sm:inline">{{ edited(canvas.updated_at) }}</span>
                                <UiButton
                                    icon="i-lucide-trash-2"
                                    size="xs"
                                    color="neutral"
                                    variant="ghost"
                                    :aria-label="`Delete ${canvas.name}`"
                                    @click="deleting = canvas"
                                />
                            </li>
                        </ul>
                    </div>
                </template>
            </UiDashboardPanel>
        </template>

        <UiModal v-model:open="creating" title="New canvas" description="An endless board for cards and arrows.">
            <template #body>
                <form class="flex flex-col gap-3" @submit.prevent="create">
                    <UiFormField label="Name" :error="form.errors.name">
                        <UiInput v-model="form.name" placeholder="e.g. Trip plan" class="w-full" autofocus />
                    </UiFormField>
                    <div class="flex justify-end gap-2">
                        <UiButton color="neutral" variant="ghost" @click="creating = false">Cancel</UiButton>
                        <UiButton type="submit" :loading="form.processing">Create</UiButton>
                    </div>
                </form>
            </template>
        </UiModal>

        <ConfirmModal
            :open="deleting !== null"
            :title="`Delete “${deleting?.name ?? ''}”?`"
            description="The board and its cards go; the notes and files on it stay."
            confirm-label="Delete canvas"
            @update:open="(open: boolean) => !open && (deleting = null)"
            @confirm="remove"
        />
    </NotesLayout>
</template>
