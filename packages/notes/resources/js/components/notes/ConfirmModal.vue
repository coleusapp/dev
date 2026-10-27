<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description: string;
        confirmLabel: string;
        destructive?: boolean;
    }>(),
    { destructive: true },
);

const emit = defineEmits<{
    confirm: [];
}>();

const open = defineModel<boolean>('open', { required: true });

/**
 * Emits before closing so a parent that clears its state on close still has it.
 */
const confirm = (): void => {
    emit('confirm');
    open.value = false;
};
</script>

<template>
    <UiModal v-model:open="open" :title="title" :description="description">
        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <UiButton color="neutral" variant="ghost" @click="open = false">Cancel</UiButton>
                <UiButton :color="destructive ? 'error' : 'primary'" @click="confirm">{{ confirmLabel }}</UiButton>
            </div>
        </template>
    </UiModal>
</template>
