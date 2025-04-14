<script setup lang="ts">
interface Props {
    title?: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header class="flex items-center justify-between">
        <div>
            <h3 v-if="title" class="text-sm font-semibold">{{ title }}</h3>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.default">
            <slot />
        </div>
    </header>
</template>
