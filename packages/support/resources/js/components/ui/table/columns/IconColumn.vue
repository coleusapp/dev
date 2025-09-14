<script lang="ts" setup>
withDefaults(defineProps<{
    name: string;
    class?: string;
}>(), {
});
</script>
<template>
    <UiIcon :name="name" class="size-5" :class="$props?.class" />
</template>