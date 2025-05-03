<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import ButtonLink from '@/components/ButtonLink.vue';
import ContentColumn from '@/components/ContentColumn.vue';
import WorkoutsTable from '@health/components/workouts/Table.vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import HealthLayout from '@health/layouts/HealthLayout.vue';
import { CategoryTable } from '@health/types/category';
import { WorkoutTable } from '@health/types/workout';

defineProps<{
    table?: WorkoutTable;
    category_table?: CategoryTable;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Weights" />
        <HealthLayout>
            <ContentColumn>
                <HeadingSmall title="Workouts">
                    <ButtonLink :href="route('health.workouts.create')" icon="Plus">New Workout</ButtonLink>
                </HeadingSmall>
                <WorkoutsTable :table="table" />
                <HeadingSmall title="Categories">
                    <ButtonLink :href="route('health.workouts.create')" icon="Plus">New Category</ButtonLink>
                </HeadingSmall>
                <!--<CategoriesTable :table="category_table" />-->
            </ContentColumn>
        </HealthLayout>
    </AppLayout>
</template>
