<script setup lang="ts">
import { Table } from '@/components/ui/table';
import ActionLink from '@/components/ui/table/ActionLink.vue';
import { WeightTable } from '@health/types/weight';

defineProps<{
    table?: any;
}>();
</script>
<template>
    <Table :table="table">
        <template #actions="{ record }">
            <ActionLink :href="route('health.weights.edit', { weight: record })">Edit</ActionLink>
            <ActionLink :href="route('health.weights.destroy', { weight: record })" method="delete"> Delete</ActionLink>
        </template>
    </Table>
</template>
