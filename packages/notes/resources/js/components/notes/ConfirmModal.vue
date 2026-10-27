<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
    confirmLabel: string;
}>();

const emit = defineEmits<{
    confirm: [];
}>();

const open = defineModel<boolean>('open', { required: true });

const confirm = (): void => {
    open.value = false;
    emit('confirm');
};
</script>

<template>
    <UiModal v-model:open="open" :title="title" :description="description">
        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <UiButton color="neutral" variant="ghost" @click="open = false">Cancel</UiButton>
                <UiButton color="error" @click="confirm">{{ confirmLabel }}</UiButton>
            </div>
        </template>
    </UiModal>
</template>
