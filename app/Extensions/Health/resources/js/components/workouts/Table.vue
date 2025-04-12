<script setup lang="ts">
import { Table, TableActionLink } from '@/components/ui/table';
import { WorkoutTable } from '@health/types/workout';

defineProps<{
    table?: WorkoutTable;
}>();
</script>
<template>
    <Table :headers="table?.headers || []" :records="table?.records || []" :columns="table?.columns || []" #default="{ record }">
        <TableActionLink :href="route('health.workouts.edit', { workout: record })">Edit</TableActionLink>
        <TableActionLink :href="route('health.workouts.destroy', { workout: record })" method="delete">Delete </TableActionLink>
    </Table>
</template>
