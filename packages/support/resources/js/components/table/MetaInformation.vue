<script setup lang="ts">
import type { Meta } from '@coleus/support/types/resource';

defineProps<{
    meta?: Meta,
}>()
</script>

<template>
    <div class="text-sm text-gray-500 dark:text-white">
        <slot>
            <template v-if="meta">
                <span class="font-mono">{{ meta.total }}</span> records
            </template>
        </slot>
    </div>
</template>