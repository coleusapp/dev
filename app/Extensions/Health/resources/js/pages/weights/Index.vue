<script setup lang="ts">
import ButtonLink from '@/components/ButtonLink.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Table as TableType } from '@/types';
import { type BreadcrumbItem } from '@/types';
import HealthLayout from '@health/Layouts/HealthLayout.vue';
import { Head } from '@inertiajs/vue3';
import { getBreadcrumbs, weights } from '@health/composables/useBreadcrumb';
import WeightTable from "@health/components/weights/Table.vue";

const breadcrumbs: BreadcrumbItem[] = getBreadcrumbs([weights]);

defineProps<{
    table?: TableType;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Weights" />
        <HealthLayout>
            <div class="flex flex-col gap-4">
                <HeadingSmall title="Weights">
                    <ButtonLink :href="route('health.weights.create')" icon="Plus" size="sm">New Weight</ButtonLink>
                </HeadingSmall>
                <WeightTable :table="table" />
            </div>
        </HealthLayout>
    </AppLayout>
</template>
