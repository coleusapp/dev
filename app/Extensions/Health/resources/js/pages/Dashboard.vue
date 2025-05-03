<script setup lang="ts">
import HealthLayout from '@health/layouts/HealthLayout.vue';
import { Head } from '@inertiajs/vue3';
import WeightCard from '@health/components/weights/Card.vue';

defineProps<{
    weights?: any;
}>();
</script>

<template>
    <Head title="Dashboard" />

    <HealthLayout>
        <div class="flex flex-wrap gap-4 w-full">
            <WeightCard class="w-full" :collection="weights" />
        </div>
    </HealthLayout>
</template>
