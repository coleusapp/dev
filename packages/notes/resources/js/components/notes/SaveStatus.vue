<script setup lang="ts">
import type { AutosaveStatus } from '@/composables/useAutosave';
import { computed } from 'vue';

const props = defineProps<{
    status: AutosaveStatus;
    error: string | null;
}>();

defineEmits<{
    retry: [];
}>();

const label = computed(
    () =>
        ({
            saved: 'Saved',
            unsaved: 'Unsaved changes',
            saving: 'Saving...',
            error: props.error ?? 'Could not save',
        })[props.status],
);
</script>

<template>
    <span class="flex items-center gap-1 text-xs" :class="status === 'error' ? 'text-error' : 'text-dimmed'" role="status" aria-live="polite">
        <UiIcon v-if="status === 'saving'" name="i-lucide-loader-circle" class="size-3 animate-spin" />
        <UiIcon v-else-if="status === 'saved'" name="i-lucide-check" class="size-3" />
        <UiIcon v-else-if="status === 'error'" name="i-lucide-circle-alert" class="size-3" />
        {{ label }}
        <UiButton v-if="status === 'error'" size="xs" color="error" variant="link" @click="$emit('retry')">Retry</UiButton>
    </span>
</template>
