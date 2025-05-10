<script setup lang="ts">
import WeightTable from '@health/components/workouts/muscleGroups/Table.vue';
import { Collection } from '@health/components/workouts/muscleGroups/type';
import { Link } from '@inertiajs/vue3';

defineProps<{
    collection?: Collection;
}>();
</script>
<template>
    <UiCard>
        <template #header>
            muscleGroups
            <Link :href="route('health.workouts.muscle-groups.create')">
                <UiButton icon="lucide-plus">New</UiButton>
            </Link>
        </template>
        <WeightTable :collection="collection" />
    </UiCard>
</template>
