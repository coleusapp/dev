<script setup lang="ts">
import BaseEditor from '@/components/notes/BaseEditor.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { cellDisplay, nextSort, sortDirection, type BaseDefinition, type BaseOptions, type BaseTable } from '@/lib/bases';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { computed, ref } from 'vue';

const props = defineProps<{
    bases: { id: number; name: string }[];
    base: { id: number; name: string; definition: BaseDefinition } | null;
    table: BaseTable | null;
    options: BaseOptions;
}>();

/**
 * Bases (O11.1): a table over notes, one row per note, the title first
 * and then the base's columns. Clicking a header re-sorts and saves it.
 */
const editing = ref(false);
const creating = ref(false);
const sorting = ref(false);
const createForm = useForm({ name: '' });

const baseItems = computed<DropdownMenuItem[][]>(() => [
    props.bases.map((each) => ({
        label: each.name,
        icon: 'i-lucide-table-2',
        active: each.id === props.base?.id,
        onSelect: () => router.visit(route('notes.bases.show', { base: each.id })),
    })),
    [{ label: 'New base', icon: 'i-lucide-plus', onSelect: () => openCreate() }],
]);

const openCreate = (): void => {
    createForm.reset();
    createForm.clearErrors();
    creating.value = true;
};

const create = (): void => {
    createForm.post(route('notes.bases.store'), { onSuccess: () => (creating.value = false) });
};

const sortBy = (column: string): void => {
    const base = props.base;

    if (!base || sorting.value) {
        return;
    }

    sorting.value = true;
    router.put(
        route('notes.bases.update', { base: base.id }),
        { name: base.name, definition: { ...base.definition, sort: nextSort(base.definition.sort, column) } },
        { preserveScroll: true, preserveState: true, onFinish: () => (sorting.value = false) },
    );
};

const sortIcon = (column: string): string | undefined => {
    const direction = props.base ? sortDirection(props.base.definition.sort, column) : null;

    return direction === 'asc' ? 'i-lucide-arrow-up' : direction === 'desc' ? 'i-lucide-arrow-down' : undefined;
};

const ariaSort = (column: string): 'ascending' | 'descending' | undefined => {
    const direction = props.base ? sortDirection(props.base.definition.sort, column) : null;

    return direction === 'asc' ? 'ascending' : direction === 'desc' ? 'descending' : undefined;
};

const tableErrors = computed(() =>
    Object.entries(props.table?.errors ?? {}).map(
        ([where, message]) => `${where === 'where' ? 'Only where' : where.replace(/^formula\./, 'ƒ ')}: ${message}`,
    ),
);
</script>

<template>
    <Head :title="base?.name ?? 'Bases'" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-bases">
                <template #header>
                    <UiDashboardNavbar :title="base?.name ?? 'Bases'">
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
                            <UiDropdownMenu v-if="bases.length" :items="baseItems" :content="{ align: 'end' }">
                                <UiButton icon="i-lucide-table-2" trailing-icon="i-lucide-chevron-down" color="neutral" variant="ghost" size="sm"
                                    >Bases</UiButton
                                >
                            </UiDropdownMenu>
                            <UiButton v-if="base" icon="i-lucide-settings-2" color="neutral" variant="soft" size="sm" @click="editing = true"
                                >Edit</UiButton
                            >
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <div v-if="!base || !table" class="mx-auto flex max-w-md flex-col items-center gap-3 py-16 text-center">
                        <UiIcon name="i-lucide-table-2" class="text-dimmed size-10" />
                        <p class="font-medium">No bases yet</p>
                        <p class="text-dimmed text-sm">
                            A base is a table over your notes: their properties as columns, with filters, sorting and formulas.
                        </p>
                        <UiButton icon="i-lucide-plus" @click="openCreate">New base</UiButton>
                    </div>

                    <div v-else class="flex min-w-0 flex-col gap-3">
                        <div v-if="tableErrors.length" class="bg-error/10 text-error rounded-md px-3 py-2 text-sm" role="alert">
                            <p v-for="message in tableErrors" :key="message">{{ message }}</p>
                        </div>
                        <p class="text-dimmed text-xs">
                            {{ table.total }} note{{ table.total === 1 ? '' : 's' }}
                            <template v-if="table.total > table.rows.length"> · showing the first {{ table.rows.length }}</template>
                        </p>

                        <p v-if="!table.rows.length" class="text-dimmed text-sm">No notes match. Change the base's notes or filters with Edit.</p>

                        <div v-else class="border-default overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-elevated/50 text-muted text-xs">
                                    <tr>
                                        <th
                                            v-for="column in [{ id: 'note.title', label: 'Title' }, ...table.columns]"
                                            :key="column.id"
                                            scope="col"
                                            class="border-default border-b px-3 py-2 text-start font-medium whitespace-nowrap"
                                            :aria-sort="ariaSort(column.id)"
                                        >
                                            <button
                                                type="button"
                                                class="hover:text-default inline-flex items-center gap-1"
                                                :disabled="sorting"
                                                :title="`Sort by ${column.label}`"
                                                @click="sortBy(column.id)"
                                            >
                                                {{ column.label }}
                                                <UiIcon v-if="sortIcon(column.id)" :name="sortIcon(column.id)!" class="size-3" />
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-default divide-y">
                                    <tr v-for="row in table.rows" :key="row.id" class="hover:bg-elevated/30">
                                        <th scope="row" class="px-3 py-1.5 text-start font-medium">
                                            <Link
                                                :href="route('notes.show', { note: row.id })"
                                                class="inline-flex items-center gap-1 hover:underline"
                                            >
                                                <UiIcon v-if="row.locked" name="i-lucide-lock" class="text-dimmed size-3.5" aria-label="Locked" />
                                                {{ row.title || 'Untitled note' }}
                                            </Link>
                                        </th>
                                        <td v-for="column in table.columns" :key="column.id" class="px-3 py-1.5 align-top">
                                            <UiTooltip v-if="row.errors[column.id]" :text="row.errors[column.id]">
                                                <span class="text-error inline-flex items-center gap-1 text-xs">
                                                    <UiIcon name="i-lucide-triangle-alert" class="size-3.5" />
                                                    <span class="sr-only">{{ row.errors[column.id] }}</span>
                                                    Error
                                                </span>
                                            </UiTooltip>
                                            <template v-else>
                                                <template v-for="display in [cellDisplay(row.cells[column.id])]" :key="display.kind">
                                                    <UiIcon
                                                        v-if="display.kind === 'check'"
                                                        :name="display.checked ? 'i-lucide-square-check' : 'i-lucide-square'"
                                                        class="text-muted size-4"
                                                        :aria-label="display.checked ? 'Yes' : 'No'"
                                                    />
                                                    <span v-else-if="display.kind === 'list'" class="flex flex-wrap gap-1">
                                                        <UiBadge
                                                            v-for="item in display.items"
                                                            :key="item"
                                                            color="neutral"
                                                            variant="subtle"
                                                            size="sm"
                                                            >{{ item }}</UiBadge
                                                        >
                                                    </span>
                                                    <span v-else-if="display.kind === 'text'" :class="{ 'tabular-nums': display.numeric }">{{
                                                        display.text
                                                    }}</span>
                                                </template>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </UiDashboardPanel>
        </template>

        <BaseEditor v-if="base" v-model:open="editing" :base="base" :options="options" />

        <UiModal v-model:open="creating" title="New base" description="A table over your notes. Choose its columns and filters next.">
            <template #body>
                <form class="flex flex-col gap-3" @submit.prevent="create">
                    <UiFormField label="Name" :error="createForm.errors.name">
                        <UiInput v-model="createForm.name" placeholder="e.g. Reading list" class="w-full" autofocus />
                    </UiFormField>
                    <div class="flex justify-end gap-2">
                        <UiButton color="neutral" variant="ghost" @click="creating = false">Cancel</UiButton>
                        <UiButton type="submit" :loading="createForm.processing">Create</UiButton>
                    </div>
                </form>
            </template>
        </UiModal>
    </NotesLayout>
</template>
