<script setup lang="ts">
import HealthLayout from '@health/layouts/HealthLayout.vue';
import { Head } from '@inertiajs/vue3';
import WeightCard from '@health/components/weights/Card.vue';
import WorkoutCategoryCard from '@health/components/workoutCategories/Card.vue';
import MuscleGroupCard from '@health/components/workouts/muscleGroups/Card.vue';
import ToothpasteTypeCard from '@health/components/toothpasteTypes/Card.vue';
import { Collection as WeightCollection } from '@health/components/weights/type';
import { Collection as WorkoutCategoryCollection } from '@health/components/workoutCategories/type';
import { Collection as MuscleGroupCollection } from '@health/components/workouts/muscleGroups/type';
import { Collection as ToothpasteTypeCollection } from '@health/components/toothpasteTypes/type';

defineProps<{
    weights: WeightCollection;
    workout_categories: WorkoutCategoryCollection;
    muscle_groups: MuscleGroupCollection;
    toothpaste_types: ToothpasteTypeCollection;
}>();
</script>

<template>
    <Head title="Dashboard" />

    <HealthLayout>
        <div class="flex flex-wrap gap-4 w-full">
            <WeightCard class="w-full" :collection="weights" />
            <WorkoutCategoryCard class="w-full" :collection="workout_categories" />
            <MuscleGroupCard class="w-full" :collection="muscle_groups" />
            <ToothpasteTypeCard class="w-full" :collection="toothpaste_types" />
        </div>
    </HealthLayout>
</template>
