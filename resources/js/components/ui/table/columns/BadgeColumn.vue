<script lang="ts" setup>
import type { VariantProps } from 'tailwind-variants';

withDefaults(defineProps<{
    color?: string,
    variant?: string,
}>(), {
    color: 'neutral',
    variant: 'soft',
})
</script>
<template>
    <UiBadge v-if="$slots.default" :color="color" :variant="variant">
        <slot />
    </UiBadge>
</template>