<script setup lang="ts">
import { Table, TableActionLink } from '@/components/ui/table';
import { CategoryTable } from '@health/types/category';

defineProps<{
    table?: CategoryTable;
}>();
</script>
<template>
    <Table :headers="table?.headers || []" :records="table?.records || []" :columns="table?.columns || []" #default="{ record }">
        <TableActionLink :href="route('health.categories.edit', { category: record })">Edit</TableActionLink>
        <TableActionLink :href="route('health.categories.destroy', { category: record })" method="delete"> Delete </TableActionLink>
    </Table>
</template>
