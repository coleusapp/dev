<script setup lang="ts">
import { Table } from '@/components/ui/table';
import { WeightTable } from '@health/types/weight';

defineProps<{
    table?: WeightTable;
}>();
</script>
<template>
    <Table :records="table?.records || []" :columns="table?.columns || []" #default="{ record }">
        <TableActionLink :href="route('health.weights.edit', { weight: record })">Edit</TableActionLink>
        <TableActionLink :href="route('health.weights.destroy', { weight: record })" method="delete"> Delete </TableActionLink>
    </Table>
</template>
