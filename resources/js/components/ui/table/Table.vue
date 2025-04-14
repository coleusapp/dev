<script setup lang="ts">
import { cn } from '@/lib/utils';
import Empty from '@/components/Empty.vue';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/vue3';
import { IColumn, IRecords } from '@/components/ui/table/index';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import RemoveAllFilters from '@/components/ui/table/RemoveAllFilters.vue';
import SortLinks from '@/components/ui/table/SortLinks.vue';
import ButtonLink from '@/components/ButtonLink.vue';

const props = defineProps<{
    table: {
        columns: IColumn[],
        records: IRecords,
        config: any[],
    }
}>();

// const delegatedProps = computed(() => {
//     const { class: _, ...delegated } = props;
//
//     return delegated;
// });
</script>

<template>
    <fieldset class="min-w-0 space-y-4 transition-opacity">
        <slot name="header">
            <div class="flex items-center gap-4 justify-between">
                <Input
                    autocomplete=""
                    placeholder="Search..."
                    class="flex-1 max-w-sm w-full"
                    name="search"
                    @input="(event: EventTarget) => router.reload({only: ['table'], data: {[table.config.search_query]: event.target.value}})"
                />
                <div class="flex-1 flex items-center justify-start md:justify-end">
                    <RemoveAllFilters />
                </div>
            </div>
        </slot>
        <slot name="subheader" v-if="$slots.subheader">
            <div class="flex items-center gap-4 justify-between">
                <div></div>
                <div class="text-xs text-gray-700 dark:text-gray-200"></div>
            </div>
        </slot>
        <div class="rounded-md border border-gray-200 dark:border-zinc-700">
            <div class="relative w-full overflow-x-auto overflow-y-hidden rounded-md dark:bg-zinc-900">
                <table
                    v-if="(table.records.data || []).length > 0"
                    :class="cn('w-full caption-bottom text-left transition-opacity dark:text-zinc-300', props.class)"
                >
                    <thead>
                    <tr class="border-b border-gray-200 transition-colors hover:bg-gray-100/50 dark:border-zinc-700 dark:hover:bg-zinc-900/50">
                        <th
                            v-for="(column, index) in table.columns || []"
                            :key="index"
                            class="h-10 px-2 align-middle text-sm font-medium text-gray-500 first:ps-4 last:pe-4 dark:bg-zinc-900 dark:text-zinc-400"
                        >
                            <span class="font-semibold">
                                {{ column.label }}
                            </span>
                            <SortLinks :items="column?.sort || []" :query="table.config.sort_query" />
                        </th>
                        <th v-if="$slots.actions"
                            class="h-10 px-2 align-middle text-sm font-medium text-gray-500 first:ps-4 last:pe-4 dark:bg-zinc-900 dark:text-zinc-400" />
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="(record, recordIndex) in table.records.data"
                        :key="recordIndex"

                        class="border-b border-gray-200 transition-colors hover:bg-gray-100/50 data-[state=selected]:bg-gray-100 dark:border-zinc-700 dark:hover:bg-zinc-950 dark:data-[state=selected]:bg-zinc-800"
                    >
                        <td
                            v-for="(column, columnIndex) in table.columns || []"
                            :key="columnIndex"
                            data-column="id"
                            class="whitespace-pre p-2 align-middle first:ps-4 last:pe-4 dark:text-zinc-300"
                        >
                            <div
                                :class="cn('flex items-center justify-start text-sm font-medium leading-none', props.class)"
                            >
                                {{ record[column.value] }}
                            </div>
                        </td>
                        <td v-if="$slots.actions">
                            <div class="flex items-center justify-end">
                                <slot name="actions" :record="record"></slot>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <Empty v-else />
            </div>
        </div>
        <slot name="footer">
            <div class="flex items-center gap-4 justify-between text-xs text-gray-700 dark:text-gray-200">
                <div>
                    <span class="font-mono font-bold">{{ table.records.meta.total }}</span> Records
                </div>
                <div class="text-xs text-gray-700 dark:text-gray-200">
                    <template v-for="(link, index) in table.records.meta.links" :key="index">
                        <ButtonLink v-if="link.url && !link.active" :href="link.url" variant="link" size="sm"><span
                            v-html="link.label"></span></ButtonLink>
                        <Button v-else variant="link" size="sm" disabled><span v-html="link.label"></span></Button>
                    </template>
                </div>
            </div>
        </slot>
    </fieldset>
</template>
