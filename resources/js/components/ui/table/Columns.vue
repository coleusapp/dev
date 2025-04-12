<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { IColumn } from '@/components/ui/table/index';

const props = defineProps<{
    columns: IColumn[],
    class?: HTMLAttributes['class'],
}>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <thead v-bind="delegatedProps">
        <tr class="border-b border-gray-200 transition-colors hover:bg-gray-100/50 dark:border-zinc-700 dark:hover:bg-zinc-900/50">
            <th
                v-for="(column, index) in columns || []"
                :key="index"
                class="h-10 px-2 align-middle text-sm font-medium text-gray-500 first:ps-4 last:pe-4 dark:bg-zinc-900 dark:text-zinc-400"
            >
                <span class="font-semibold">
                    <slot>{{ column.label }}</slot>
                </span>
            </th>
            <th class="h-10 px-2 align-middle text-sm font-medium text-gray-500 first:ps-4 last:pe-4 dark:bg-zinc-900 dark:text-zinc-400" />
        </tr>
    </thead>
</template>
