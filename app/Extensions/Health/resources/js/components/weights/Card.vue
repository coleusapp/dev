<script setup lang="ts">
import {Link} from "@inertiajs/vue3";
import WeightTable from '@health/components/weights/Table.vue';
import { Collection } from '@health/components/weights/type';

defineProps<{
    collection?: Collection;
}>();
</script>
<template>
    <UiCard>
        <template #header>
            <span class="text-sm">Weights</span>
            <Link :href="route('health.weights.create')">
                <UiButton icon="lucide-plus">New</UiButton>
            </Link>
        </template>
        <WeightTable :collection="collection" />
    </UiCard>
</template>
