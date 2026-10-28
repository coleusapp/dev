<script setup lang="ts">
import type { FolderNode } from '@/components/notes/note';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { jsonHeaders } from '@/lib/http';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type TaskItem = { line: number; checked: boolean; text: string; depth: number };
type TaskGroup = { note: { id: number; title: string; archived: boolean }; items: TaskItem[] };
type TaskFilters = { state: 'open' | 'done' | 'all'; tag: string | null; folder: number | null };

const props = defineProps<{
    groups: TaskGroup[];
    filters: TaskFilters;
    folders: FolderNode[];
}>();

/**
 * Checklist items across notes (O9.6). Ticking one here changes the note
 * itself (the checklist endpoint the editor uses), then the list reloads.
 */
const tag = ref(props.filters.tag ?? '');
const busy = ref<string | null>(null);
const error = ref<string | null>(null);

const flatten = (nodes: FolderNode[], depth = 0): { id: number; label: string }[] =>
    nodes.flatMap((node) => [{ id: node.id, label: `${'— '.repeat(depth)}${node.name}` }, ...flatten(node.children, depth + 1)]);

const folderOptions = computed(() => [{ id: 0, label: 'All folders' }, ...flatten(props.folders)]);

const count = computed(() => props.groups.reduce((sum, group) => sum + group.items.length, 0));

const filter = (change: Partial<TaskFilters>): void => {
    const next = { ...props.filters, ...change };

    router.get(
        route('notes.tasks.index'),
        Object.fromEntries(
            Object.entries({ state: next.state === 'open' ? null : next.state, tag: next.tag || null, folder: next.folder || null }).filter(
                ([, value]) => value !== null,
            ),
        ),
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const toggle = async (group: TaskGroup, item: TaskItem): Promise<void> => {
    const key = `${group.note.id}:${item.line}`;
    busy.value = key;
    error.value = null;

    try {
        const response = await fetch(route('notes.checklist.update', { note: group.note.id, line: item.line }), {
            method: 'PUT',
            headers: jsonHeaders(),
            credentials: 'same-origin',
            body: JSON.stringify({ checked: !item.checked }),
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            error.value = payload?.errors?.line?.[0] ?? payload?.message ?? "Couldn't change that item.";
        }

        router.reload({ only: ['groups'] });
    } finally {
        busy.value = null;
    }
};
</script>

<template>
    <Head title="Tasks" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-tasks">
                <template #header>
                    <UiDashboardNavbar title="Tasks">
                        <template #leading>
                            <UiButton
                                icon="i-lucide-chevron-left"
                                color="neutral"
                                variant="ghost"
                                aria-label="Back to notes"
                                :to="route('notes.index')"
                            />
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex gap-1" role="group" aria-label="Show">
                                <UiButton
                                    v-for="state in ['open', 'done', 'all'] as const"
                                    :key="state"
                                    size="sm"
                                    color="neutral"
                                    :variant="filters.state === state ? 'soft' : 'ghost'"
                                    :aria-pressed="filters.state === state"
                                    @click="filter({ state })"
                                    >{{ { open: 'Open', done: 'Done', all: 'All' }[state] }}</UiButton
                                >
                            </div>
                            <select
                                class="border-default bg-default rounded-md border px-2 py-1 text-sm"
                                aria-label="Folder"
                                :value="filters.folder ?? 0"
                                @change="filter({ folder: Number(($event.target as HTMLSelectElement).value) || null })"
                            >
                                <option v-for="option in folderOptions" :key="option.id" :value="option.id">{{ option.label }}</option>
                            </select>
                            <form class="flex items-center gap-1" @submit.prevent="filter({ tag: tag.replace(/^#/, '').trim() || null })">
                                <UiInput v-model="tag" size="sm" placeholder="#tag" aria-label="Tag" class="w-32" />
                                <UiButton
                                    v-if="filters.tag"
                                    size="xs"
                                    color="neutral"
                                    variant="ghost"
                                    icon="i-lucide-x"
                                    aria-label="Any tag"
                                    @click="((tag = ''), filter({ tag: null }))"
                                />
                            </form>
                            <span class="text-dimmed ms-auto text-xs">{{ count }} item{{ count === 1 ? '' : 's' }}</span>
                        </div>

                        <p v-if="error" class="text-error text-sm" role="alert">{{ error }}</p>
                        <p v-if="!groups.length" class="text-dimmed text-sm">
                            No {{ filters.state === 'all' ? '' : filters.state }} checklist items here.
                        </p>

                        <section v-for="group in groups" :key="group.note.id" :aria-label="group.note.title || 'Untitled note'">
                            <Link
                                :href="route('notes.show', { note: group.note.id })"
                                class="mb-1 flex items-center gap-2 text-sm font-semibold hover:underline"
                            >
                                {{ group.note.title || 'Untitled note' }}
                                <UiBadge v-if="group.note.archived" color="neutral" variant="subtle" size="sm">Archived</UiBadge>
                            </Link>
                            <ul class="flex flex-col gap-1">
                                <li
                                    v-for="item in group.items"
                                    :key="item.line"
                                    class="flex items-start gap-2 text-sm"
                                    :style="{ paddingInlineStart: `${item.depth * 1.25}rem` }"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-1"
                                        :checked="item.checked"
                                        :disabled="busy === `${group.note.id}:${item.line}`"
                                        :aria-label="item.text"
                                        @change="toggle(group, item)"
                                    />
                                    <span :class="{ 'text-dimmed line-through': item.checked }">{{ item.text }}</span>
                                </li>
                            </ul>
                        </section>
                    </div>
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
