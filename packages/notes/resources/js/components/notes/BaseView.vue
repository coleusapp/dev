<script setup lang="ts">
import BaseCell from '@/components/notes/BaseCell.vue';
import { cellText, sections, sortDirection, type BaseSort, type BaseTable, type BaseViewKind } from '@/lib/bases';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        table: BaseTable;
        view: BaseViewKind;
        sort?: BaseSort[];
        groupLabel?: string;
        /** Headers re-sort when set (the Bases page); not in an embed. */
        sortable?: boolean;
        busy?: boolean;
    }>(),
    { sort: () => [], groupLabel: '', sortable: false, busy: false },
);

const emit = defineEmits<{ sort: [column: string] }>();

/**
 * A base's rows (O11.1 table, O11.2 cards), group by group when grouped.
 */
const grouped = computed(() => props.table.groups !== null);
const parts = computed(() => sections(props.table));
const headers = computed(() => [{ id: 'note.title', label: 'Title' }, ...props.table.columns]);

const groupName = (value: unknown): string => (value === null || value === undefined || value === '' ? 'None' : cellText(value));

const sortIcon = (column: string): string | undefined => {
    const direction = sortDirection(props.sort, column);

    return direction === 'asc' ? 'i-lucide-arrow-up' : direction === 'desc' ? 'i-lucide-arrow-down' : undefined;
};

const ariaSort = (column: string): 'ascending' | 'descending' | undefined => {
    const direction = sortDirection(props.sort, column);

    return direction === 'asc' ? 'ascending' : direction === 'desc' ? 'descending' : undefined;
};
</script>

<template>
    <div v-if="view === 'table'" class="border-default overflow-x-auto rounded-md border">
        <table class="w-full text-sm">
            <thead class="bg-elevated/50 text-muted text-xs">
                <tr>
                    <th
                        v-for="column in headers"
                        :key="column.id"
                        scope="col"
                        class="border-default border-b px-3 py-2 text-start font-medium whitespace-nowrap"
                        :aria-sort="ariaSort(column.id)"
                    >
                        <button
                            v-if="sortable"
                            type="button"
                            class="hover:text-default inline-flex items-center gap-1"
                            :disabled="busy"
                            :title="`Sort by ${column.label}`"
                            @click="emit('sort', column.id)"
                        >
                            {{ column.label }}
                            <UiIcon v-if="sortIcon(column.id)" :name="sortIcon(column.id)!" class="size-3" />
                        </button>
                        <template v-else>{{ column.label }}</template>
                    </th>
                </tr>
            </thead>
            <tbody v-for="part in parts" :key="part.key" class="divide-default divide-y">
                <tr v-if="grouped" class="bg-elevated/30">
                    <th scope="rowgroup" :colspan="headers.length" class="px-3 py-1.5 text-start text-xs font-semibold">
                        <span v-if="groupLabel" class="text-dimmed font-normal">{{ groupLabel }}: </span>{{ groupName(part.value) }}
                        <span class="text-dimmed ms-1 font-normal tabular-nums">{{ part.total }}</span>
                    </th>
                </tr>
                <tr v-for="row in part.rows" :key="row.id" class="hover:bg-elevated/30">
                    <th scope="row" class="px-3 py-1.5 text-start font-medium">
                        <Link :href="route('notes.show', { note: row.id })" class="inline-flex items-center gap-1 hover:underline">
                            <UiIcon v-if="row.locked" name="i-lucide-lock" class="text-dimmed size-3.5" aria-label="Locked" />
                            {{ row.title || 'Untitled note' }}
                        </Link>
                    </th>
                    <td v-for="column in table.columns" :key="column.id" class="px-3 py-1.5 align-top">
                        <BaseCell :value="row.cells[column.id]" :error="row.errors[column.id]" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-else class="flex flex-col gap-4">
        <section v-for="part in parts" :key="part.key" :aria-label="grouped ? groupName(part.value) : undefined">
            <h3 v-if="grouped" class="mb-2 text-xs font-semibold">
                <span v-if="groupLabel" class="text-dimmed font-normal">{{ groupLabel }}: </span>{{ groupName(part.value) }}
                <span class="text-dimmed ms-1 font-normal tabular-nums">{{ part.total }}</span>
            </h3>
            <ul class="grid grid-cols-[repeat(auto-fill,minmax(12rem,1fr))] gap-3">
                <li v-for="row in part.rows" :key="row.id">
                    <Link
                        :href="route('notes.show', { note: row.id })"
                        class="border-default hover:border-accented flex h-full flex-col overflow-hidden rounded-md border text-sm"
                    >
                        <div class="bg-elevated/50 aspect-video overflow-hidden">
                            <img v-if="row.thumbnail" :src="row.thumbnail" alt="" class="size-full object-cover" loading="lazy" draggable="false" />
                            <p v-else class="text-dimmed line-clamp-4 p-2 text-xs">
                                <UiIcon v-if="row.locked" name="i-lucide-lock" class="size-4" aria-label="Locked" />
                                <template v-else>{{ row.snippet }}</template>
                            </p>
                        </div>
                        <div class="flex flex-col gap-1 p-2">
                            <p class="truncate font-medium">{{ row.title || 'Untitled note' }}</p>
                            <dl class="flex flex-col gap-0.5 text-xs">
                                <div v-for="column in table.columns" :key="column.id" class="flex min-w-0 items-baseline gap-2">
                                    <dt class="text-dimmed shrink-0">{{ column.label }}</dt>
                                    <dd class="min-w-0 truncate"><BaseCell :value="row.cells[column.id]" :error="row.errors[column.id]" /></dd>
                                </div>
                            </dl>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
