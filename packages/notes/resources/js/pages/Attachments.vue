<script setup lang="ts">
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type AttachmentRow = {
    uuid: string;
    name: string;
    mime: string;
    kind: 'image' | 'video' | 'audio' | 'document';
    size: number;
    url: string;
    width: number | null;
    height: number | null;
    duration_ms: number | null;
    poster: boolean;
    created_at: string | null;
    notes: { id: number; title: string; trashed: boolean }[];
};

const props = defineProps<{
    attachments: AttachmentRow[];
    hasLockedNotes: boolean;
}>();

/**
 * The attachment manager (O3.5): files grouped as Apple Notes does
 * (photos & videos, audio, documents), each with the notes that use it.
 * Unused files can be deleted one by one or all at once.
 */
const groups = computed(() =>
    [
        { label: 'Photos & videos', kinds: ['image', 'video'] },
        { label: 'Audio', kinds: ['audio'] },
        { label: 'Documents', kinds: ['document'] },
    ]
        .map((group) => ({ ...group, items: props.attachments.filter((attachment) => group.kinds.includes(attachment.kind)) }))
        .filter((group) => group.items.length),
);

const unused = computed(() => props.attachments.filter((attachment) => attachment.notes.length === 0));
const deleting = ref(false);
const confirmingAll = ref(false);
const page = usePage();
const error = computed(() => (page.props.errors as Record<string, string> | undefined)?.attachment ?? null);

const size = (bytes: number): string =>
    bytes < 1024 ? `${bytes} B` : bytes < 1024 ** 2 ? `${Math.round(bytes / 1024)} KB` : `${(bytes / 1024 ** 2).toFixed(1)} MB`;

const duration = (ms: number): string => `${Math.floor(ms / 60000)}:${String(Math.round((ms % 60000) / 1000)).padStart(2, '0')}`;

const date = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });

const thumbnail = (attachment: AttachmentRow): string | null =>
    attachment.kind === 'image' ? `${attachment.url}?w=320` : attachment.kind === 'video' && attachment.poster ? `${attachment.url}?poster=1` : null;

const remove = (attachment: AttachmentRow): void => {
    router.delete(route('notes.attachments.destroy', { attachment: attachment.uuid }), { preserveScroll: true });
};

/**
 * Deletes every unused file, one request each (each is checked again on the server).
 */
const removeUnused = async (): Promise<void> => {
    deleting.value = true;

    for (const attachment of unused.value) {
        await new Promise<void>((resolve) =>
            router.delete(route('notes.attachments.destroy', { attachment: attachment.uuid }), { preserveScroll: true, onFinish: () => resolve() }),
        );
    }

    deleting.value = false;
    confirmingAll.value = false;
};
</script>

<template>
    <Head title="Attachments" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-attachments">
                <template #header>
                    <UiDashboardNavbar title="Attachments">
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
                            <UiButton
                                v-if="unused.length"
                                color="error"
                                variant="soft"
                                size="sm"
                                icon="i-lucide-trash-2"
                                :loading="deleting"
                                @click="confirmingAll = true"
                                >Delete {{ unused.length }} unused</UiButton
                            >
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
                        <p v-if="hasLockedNotes" class="bg-elevated text-muted rounded-md px-3 py-2 text-xs">
                            Locked notes can't be read here, so a file used only in a locked note shows as unused. Check before deleting.
                        </p>
                        <p v-if="error" class="text-error text-sm" role="alert">{{ error }}</p>
                        <p v-if="!attachments.length" class="text-dimmed text-sm">No attachments yet. Paste, drop or attach files in a note.</p>

                        <section v-for="group in groups" :key="group.label" :aria-label="group.label">
                            <h2 class="text-dimmed mb-2 text-xs font-semibold">{{ group.label }} ({{ group.items.length }})</h2>
                            <ul class="divide-default flex flex-col divide-y">
                                <li v-for="attachment in group.items" :key="attachment.uuid" class="flex items-start gap-3 py-2">
                                    <a
                                        :href="attachment.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="bg-elevated flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-md"
                                    >
                                        <img
                                            v-if="thumbnail(attachment)"
                                            :src="thumbnail(attachment)!"
                                            :alt="attachment.name"
                                            class="size-full object-cover"
                                            loading="lazy"
                                        />
                                        <UiIcon
                                            v-else
                                            :name="
                                                attachment.kind === 'audio'
                                                    ? 'i-lucide-audio-lines'
                                                    : attachment.kind === 'video'
                                                      ? 'i-lucide-video'
                                                      : 'i-lucide-file-text'
                                            "
                                            class="text-dimmed size-6"
                                        />
                                    </a>
                                    <div class="flex min-w-0 flex-1 flex-col gap-0.5 text-sm">
                                        <a :href="attachment.url" target="_blank" rel="noopener" class="truncate font-medium hover:underline">{{
                                            attachment.name
                                        }}</a>
                                        <span class="text-dimmed text-xs">
                                            {{ size(attachment.size) }}
                                            <template v-if="attachment.width && attachment.height">
                                                · {{ attachment.width }}×{{ attachment.height }}</template
                                            >
                                            <template v-if="attachment.duration_ms"> · {{ duration(attachment.duration_ms) }}</template>
                                            <template v-if="attachment.created_at"> · {{ date.format(new Date(attachment.created_at)) }}</template>
                                        </span>
                                        <span v-if="attachment.notes.length" class="text-muted text-xs">
                                            In
                                            <template v-for="(note, index) in attachment.notes" :key="note.id"
                                                >{{ index ? ', ' : ''
                                                }}<Link :href="route('notes.show', { note: note.id })" class="hover:underline"
                                                    >{{ note.title || 'Untitled note' }}<template v-if="note.trashed"> (trash)</template></Link
                                                ></template
                                            >
                                        </span>
                                        <span v-else class="text-dimmed text-xs">Not used in any note</span>
                                    </div>
                                    <UiButton
                                        v-if="!attachment.notes.length"
                                        icon="i-lucide-trash-2"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        :aria-label="`Delete ${attachment.name}`"
                                        @click="remove(attachment)"
                                    />
                                </li>
                            </ul>
                        </section>
                    </div>
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>

    <UiModal
        v-model:open="confirmingAll"
        title="Delete unused attachments?"
        :description="`${unused.length} file(s) not used in any note will be deleted for good.`"
    >
        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <UiButton color="neutral" variant="ghost" @click="confirmingAll = false">Cancel</UiButton>
                <UiButton color="error" :loading="deleting" @click="removeUnused">Delete</UiButton>
            </div>
        </template>
    </UiModal>
</template>
