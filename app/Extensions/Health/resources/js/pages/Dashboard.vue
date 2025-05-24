<script setup lang="ts">
import HealthLayout from '@health/layouts/HealthLayout.vue';
import { Head } from '@inertiajs/vue3';
import WeightCard from '@health/components/weights/Card.vue';
import CategoryCard from '@health/components/categories/Card.vue';
import MuscleGroupCard from '@health/components/workouts/muscleGroups/Card.vue';
import ToothpasteTypeCard from '@health/components/toothpasteTypes/Card.vue';
import ExerciseCard from '@health/components/exercises/Card.vue';
import { Collection as WeightCollection } from '@health/components/weights/type';
import { CategoryCollection } from '@health/components/categories/category';
import { Collection as MuscleGroupCollection } from '@health/components/workouts/muscleGroups/type';
import { Collection as ToothpasteTypeCollection } from '@health/components/toothpasteTypes/type';
import { Collection as ExerciseCollection } from '@health/components/exercises/type';

defineProps<{
    weights: WeightCollection;
    categories: CategoryCollection;
    muscle_groups: MuscleGroupCollection;
    toothpaste_types: ToothpasteTypeCollection;
    exercises: ExerciseCollection;
}>();
</script>

<template>
    <Head title="Dashboard" />

    <HealthLayout>
        <div class="flex flex-wrap gap-4 w-full">
            <ExerciseCard class="w-full" :collection="exercises" />

            <WeightCard class="w-full" :collection="weights" />
            <CategoryCard class="w-full" :collection="categories" />
            <MuscleGroupCard class="w-full" :collection="muscle_groups" />
            <ToothpasteTypeCard class="w-full" :collection="toothpaste_types" />
        </div>
    </HealthLayout>
</template>
