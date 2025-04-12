<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';
import Columns from '@/components/ui/table/Columns.vue';
import Empty from '@/components/Empty.vue';
import { Records as RecordsType } from '@/types';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/vue3';
import Records from '@/components/ui/table/Records.vue';

const props = defineProps<{
    columns: Array<any>,
    records: RecordsType,
    class?: HTMLAttributes['class'],
}>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <fieldset class="min-w-0 space-y-4 transition-opacity">
        <div class="flex items-center gap-4 justify-between">
            <Input
                autocomplete=""
                placeholder="Search..."
                class="flex-1 max-w-sm w-full"
                name="search"
                @input="(event: EventTarget) => router.reload({only: ['table'], data: {search: event.target.value}})"
            />
            <div class="flex-1"></div>
        </div>
        <div class="rounded-md border border-gray-200 dark:border-zinc-700">
            <div class="relative w-full overflow-x-auto overflow-y-hidden rounded-md dark:bg-zinc-900">
                <table
                    v-if="(records.data || []).length > 0"
                    v-bind="delegatedProps"
                    :class="cn('w-full caption-bottom text-left transition-opacity dark:text-zinc-300', props.class)"
                >
                    <Columns :columns="columns || []" />
                    <Records :records="records.data || []" :columns="columns || []" #default="{ record }">
                        <slot :record="record" />
                    </Records>
                </table>
                <Empty v-else />
            </div>
        </div>
        <div class="text-xs text-gray-700 dark:text-gray-200">
            <span class="font-mono font-bold">{{ records.meta.total }}</span> Records
        </div>
    </fieldset>
</template>
