<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { IColumn, IRecords } from '@/components/ui/table/index';

const props = defineProps<{
    records: IRecords,
    columns: IColumn[],
    class?: HTMLAttributes['class'],
}>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <tbody>
    <tr
        v-for="(record, recordIndex) in records"
        :key="recordIndex"
        v-bind="delegatedProps"
        class="border-b border-gray-200 transition-colors hover:bg-gray-100/50 data-[state=selected]:bg-gray-100 dark:border-zinc-700 dark:hover:bg-zinc-950 dark:data-[state=selected]:bg-zinc-800"
    >
        <td
            v-for="(column, columnIndex) in columns || []"
            :key="columnIndex"
            data-column="id"
            class="whitespace-pre p-2 align-middle first:ps-4 last:pe-4 dark:text-zinc-300"
        >
            <div
                v-bind="delegatedProps"
                :class="cn('flex items-center justify-start text-sm font-medium leading-none', props.class)"
            >
                {{ record[column.value] }}
            </div>
        </td>
        <td>
            <div class="flex items-center justify-end">
                <slot name="actions" :record="record"></slot>
            </div>
        </td>
    </tr>
    </tbody>
</template>
