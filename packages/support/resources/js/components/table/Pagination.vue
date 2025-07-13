<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { Meta } from '@coleus/support/types/resource';

defineProps<{
    meta: Meta
}>()
</script>

<template>
    <UiPagination
        :default-page="meta.current_page || 1"
        :items-per-page="meta.per_page || 3"
        :total="meta.total || 0"
        @update:page="(p) => router.reload({ data: { page: p } })"
    />
</template>
