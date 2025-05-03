<script setup lang="ts">
import { Table } from '@/components/ui/table';
import ActionLink from '@/components/ui/table/ActionLink.vue';

defineProps<{
    table?: any;
}>();
</script>
<template>
    <Table :table="table">
        <template #actions="{ record }">
            <ActionLink :href="route('health.workouts.edit', { workout: record })">Edit</ActionLink>
            <ActionLink :href="route('health.workouts.destroy', { workout: record })" method="delete"> Delete</ActionLink>
        </template>
    </Table>
</template>
