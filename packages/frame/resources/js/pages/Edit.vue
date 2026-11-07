<script setup lang="ts">
import AppLayout from '@coleus/support/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type SourceOption = {
    value: string;
    label: string;
};

type Frame = {
    id: number;
    name: string;
    search_query: string | null;
    sources: string[];
    interval_seconds: number;
    is_active: boolean;
    artworks_count: number;
};

const props = defineProps<{
    frame: Frame | null;
    /** Only present right after create or token regeneration: the plain token is never stored. */
    kioskUrl: string | null;
    sources: SourceOption[];
}>();

const toast = useToast();
const title = computed(() => props.frame?.name ?? 'New frame');

const form = useForm({
    name: props.frame?.name ?? '',
    search_query: props.frame?.search_query ?? '',
    sources: props.frame?.sources ?? props.sources.map(({ value }) => value),
    interval_seconds: props.frame?.interval_seconds ?? 60,
    is_active: props.frame?.is_active ?? true,
});

// Laravel reports a bad entry as `sources.0`, which no field is bound to.
const sourcesError = computed(() => {
    const errors: Record<string, string> = form.errors;

    return errors.sources ?? Object.entries(errors).find(([key]) => key.startsWith('sources.'))?.[1];
});

const save = (): void => {
    if (props.frame) {
        form.put(route('frame.update', { frame: props.frame.id }), { preserveScroll: true });
    } else {
        form.post(route('frame.store'));
    }
};

const copied = ref(false);

const copyKioskUrl = async (): Promise<void> => {
    if (!props.kioskUrl) {
        return;
    }

    await navigator.clipboard.writeText(props.kioskUrl);
    copied.value = true;
};

const confirming = ref<'regenerate' | 'delete' | null>(null);

const regenerateToken = (): void => {
    router.post(route('frame.token.regenerate', { frame: props.frame!.id }), {}, { onFinish: () => (confirming.value = null) });
};

const destroy = (): void => {
    router.delete(route('frame.destroy', { frame: props.frame!.id }), { onFinish: () => (confirming.value = null) });
};

const fetchNow = (): void => {
    router.post(
        route('frame.fetch', { frame: props.frame!.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.add({ title: 'Fetching new artworks', description: 'They appear on the frame as they download.' }),
        },
    );
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :title="title">
        <div class="mx-auto flex w-full max-w-xl flex-col gap-6">
            <Link :href="route('frame.index')" class="text-dimmed flex items-center gap-1 text-sm hover:underline">
                <UiIcon name="i-lucide-chevron-left" class="size-4" />
                All frames
            </Link>

            <UiAlert
                v-if="kioskUrl"
                color="warning"
                variant="subtle"
                icon="i-lucide-key-round"
                title="Kiosk URL — shown only once"
                description="Open this on the frame's browser. It can't be shown again; regenerate it if you lose it."
            >
                <template #actions>
                    <div class="flex w-full items-center gap-2">
                        <UiInput :model-value="kioskUrl" readonly class="min-w-0 flex-1" @focus="($event.target as HTMLInputElement).select()" />
                        <UiButton :icon="copied ? 'i-lucide-check' : 'i-lucide-copy'" color="neutral" variant="outline" @click="copyKioskUrl">
                            {{ copied ? 'Copied' : 'Copy' }}
                        </UiButton>
                    </div>
                </template>
            </UiAlert>

            <form class="flex flex-col gap-6" @submit.prevent="save">
                <UiFormField label="Name" :error="form.errors.name" required>
                    <UiInput v-model="form.name" placeholder="Living room" class="w-full" autofocus />
                </UiFormField>

                <UiFormField
                    label="Search"
                    description="Optional words to narrow the paintings, e.g. “impressionism” or “Monet”. Leave empty for any painting."
                    :error="form.errors.search_query"
                >
                    <UiInput v-model="form.search_query" class="w-full" />
                </UiFormField>

                <UiFormField label="Sources" description="Museums new artworks are fetched from." :error="sourcesError" required>
                    <UiCheckboxGroup v-model="form.sources" :items="sources" />
                </UiFormField>

                <UiFormField label="Show each artwork for" description="In seconds." :error="form.errors.interval_seconds">
                    <UiInputNumber v-model="form.interval_seconds" :min="10" :max="86400" class="w-40" />
                </UiFormField>

                <UiFormField
                    label="Active"
                    description="An inactive frame's kiosk URL stops working and it isn't refreshed."
                    :error="form.errors.is_active"
                >
                    <UiSwitch v-model="form.is_active" />
                </UiFormField>

                <div class="flex items-center gap-3">
                    <UiButton type="submit" :loading="form.processing" :disabled="frame !== null && !form.isDirty">
                        {{ frame ? 'Save' : 'Create frame' }}
                    </UiButton>
                    <span v-if="form.recentlySuccessful" class="text-dimmed text-sm" role="status">Saved</span>
                </div>
            </form>

            <template v-if="frame">
                <UiSeparator />

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">Artworks</p>
                            <p class="text-dimmed text-sm">{{ frame.artworks_count }} stored. New ones are fetched every 6 hours.</p>
                        </div>
                        <UiButton icon="i-lucide-download" color="neutral" variant="outline" @click="fetchNow">Fetch now</UiButton>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">Kiosk URL</p>
                            <p class="text-dimmed text-sm">Get a new URL. The current one stops working.</p>
                        </div>
                        <UiButton icon="i-lucide-refresh-cw" color="neutral" variant="outline" @click="confirming = 'regenerate'"
                            >Regenerate</UiButton
                        >
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">Delete frame</p>
                            <p class="text-dimmed text-sm">Removes the frame and all its stored artworks.</p>
                        </div>
                        <UiButton icon="i-lucide-trash-2" color="error" variant="outline" @click="confirming = 'delete'">Delete</UiButton>
                    </div>
                </div>

                <UiModal
                    :open="confirming !== null"
                    :title="confirming === 'delete' ? 'Delete this frame?' : 'Regenerate the kiosk URL?'"
                    :description="
                        confirming === 'delete'
                            ? 'The frame and its stored artworks are removed. This can’t be undone.'
                            : 'The frame stops working until you open the new URL on it.'
                    "
                    @update:open="(open: boolean) => !open && (confirming = null)"
                >
                    <template #footer>
                        <div class="flex w-full justify-end gap-2">
                            <UiButton color="neutral" variant="ghost" @click="confirming = null">Cancel</UiButton>
                            <UiButton v-if="confirming === 'delete'" color="error" @click="destroy">Delete</UiButton>
                            <UiButton v-else @click="regenerateToken">Regenerate</UiButton>
                        </div>
                    </template>
                </UiModal>
            </template>
        </div>
    </AppLayout>
</template>
