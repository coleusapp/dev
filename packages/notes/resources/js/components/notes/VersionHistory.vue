<script setup lang="ts">
import { jsonHeaders } from '@/lib/http';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type Version = {
    id: number;
    name: string | null;
    title: string;
    characters: number;
    saved_at: string;
    saved_at_for_humans: string;
};

type DiffLine = { type: 'same' | 'add' | 'remove'; text: string };

type VersionDetail = Version & {
    body: string;
    compared_with: Version | null;
    diff: DiffLine[];
    stats: { added: number; removed: number };
};

const props = defineProps<{
    noteId: number;
    readonly?: boolean;
}>();

const open = defineModel<boolean>('open', { required: true });

const versions = ref<Version[]>([]);
const selected = ref<VersionDetail | null>(null);
const compareId = ref<number | null>(null);
const name = ref('');
const loading = ref(false);
const error = ref<string | null>(null);

const getJson = async <T,>(url: string, init: RequestInit = {}): Promise<T> => {
    const response = await fetch(url, { credentials: 'same-origin', headers: jsonHeaders(), ...init });
    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new Error(payload?.errors?.name?.[0] ?? payload?.message ?? `Request failed (${response.status}).`);
    }

    return payload as T;
};

const load = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
        versions.value = (await getJson<{ data: Version[] }>(route('notes.versions.index', { note: props.noteId }))).data;
        if (versions.value.length) {
            await select(versions.value[0].id);
        }
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Could not load the history.';
    } finally {
        loading.value = false;
    }
};

const select = async (id: number, compare: number | null = null): Promise<void> => {
    error.value = null;
    compareId.value = compare;

    try {
        const params = { note: props.noteId, version: id, ...(compare ? { compare } : {}) };
        selected.value = (await getJson<{ data: VersionDetail }>(route('notes.versions.show', params))).data;
        name.value = selected.value.name ?? '';
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Could not load that version.';
    }
};

const saveName = async (): Promise<void> => {
    if (!selected.value || props.readonly || name.value.trim() === (selected.value.name ?? '')) {
        return;
    }

    try {
        const { data } = await getJson<{ data: Version }>(route('notes.versions.update', { note: props.noteId, version: selected.value.id }), {
            method: 'PUT',
            body: JSON.stringify({ name: name.value }),
        });
        selected.value = { ...selected.value, name: data.name };
        versions.value = versions.value.map((version) => (version.id === data.id ? data : version));
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Could not rename the version.';
    }
};

const restore = (): void => {
    if (selected.value && !props.readonly) {
        router.post(
            route('notes.versions.restore', { note: props.noteId, version: selected.value.id }),
            {},
            { onSuccess: () => (open.value = false) },
        );
    }
};

const compareItems = computed(() => [
    { label: 'Current note', value: 0 },
    ...versions.value
        .filter((version) => version.id !== selected.value?.id)
        .map((version) => ({ label: version.name ?? version.saved_at_for_humans, value: version.id })),
]);

const onCompareChange = (value: number): void => {
    if (selected.value) {
        select(selected.value.id, value || null);
    }
};

watch(open, (isOpen) => {
    if (isOpen) {
        load();
    }
});
</script>

<template>
    <UiSlideover v-model:open="open" title="Version history" description="Snapshots of this note, newest first." :ui="{ content: 'max-w-3xl' }">
        <template #body>
            <p v-if="error" class="text-error mb-2 text-sm" role="alert">{{ error }}</p>
            <p v-if="loading" class="text-dimmed text-sm">Loading…</p>
            <p v-else-if="!versions.length" class="text-dimmed text-sm">No versions yet.</p>

            <div v-else class="flex min-h-0 gap-4">
                <ul class="flex w-48 shrink-0 flex-col gap-1" aria-label="Versions">
                    <li v-for="version in versions" :key="version.id">
                        <button
                            type="button"
                            class="hover:bg-elevated w-full rounded-md px-2 py-1.5 text-left text-sm"
                            :class="{ 'bg-elevated font-medium': selected?.id === version.id }"
                            @click="select(version.id)"
                        >
                            <span class="block truncate">{{ version.name ?? version.saved_at_for_humans }}</span>
                            <time class="text-dimmed block text-xs" :datetime="version.saved_at">
                                {{ version.name ? `${version.saved_at_for_humans} · ` : '' }}{{ version.characters }} chars
                            </time>
                        </button>
                    </li>
                </ul>

                <section v-if="selected" class="flex min-w-0 flex-1 flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <UiInput
                            v-model="name"
                            placeholder="Name this version"
                            aria-label="Version name"
                            size="sm"
                            class="min-w-40 flex-1"
                            :disabled="readonly"
                            @blur="saveName"
                            @keydown.enter.prevent="saveName"
                        />
                        <UiSelect
                            :model-value="compareId ?? 0"
                            :items="compareItems"
                            aria-label="Compare with"
                            size="sm"
                            class="w-44"
                            @update:model-value="onCompareChange"
                        />
                        <UiButton v-if="!readonly" icon="i-lucide-history" color="neutral" variant="subtle" size="sm" @click="restore"
                            >Restore</UiButton
                        >
                    </div>

                    <p class="text-dimmed text-xs">
                        {{
                            selected.compared_with
                                ? 'Changes from the compared version to this one'
                                : 'Changes from this version to the current note'
                        }}:
                        <span class="text-success">+{{ selected.stats.added }}</span>
                        <span class="text-error">−{{ selected.stats.removed }}</span>
                    </p>

                    <pre class="bg-elevated overflow-auto rounded-md p-3 font-mono text-xs leading-relaxed"><template
                        v-for="(line, index) in selected.diff"
                        :key="index"
                        ><span
                            class="block whitespace-pre-wrap"
                            :class="{
                                'bg-green-500/15 text-green-700 dark:text-green-300': line.type === 'add',
                                'bg-red-500/15 text-red-700 line-through dark:text-red-300': line.type === 'remove',
                            }"
                            >{{ line.type === 'add' ? '+ ' : line.type === 'remove' ? '− ' : '  ' }}{{ line.text }}</span
                        ></template
                    ></pre>
                </section>
            </div>
        </template>
    </UiSlideover>
</template>
