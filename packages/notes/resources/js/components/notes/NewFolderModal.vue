<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    parentId: number | null;
    parentName: string | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({ name: '', parent_id: props.parentId });

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
        form.parent_id = props.parentId;
    }
});

const submit = (): void => {
    form.post(route('notes.folders.store'), { onSuccess: () => (open.value = false) });
};
</script>

<template>
    <UiModal v-model:open="open" title="New folder" :description="parentName ? `Inside “${parentName}”` : 'At the top level'">
        <template #body>
            <form class="flex flex-col gap-2" @submit.prevent="submit">
                <UiFormField label="Name" :error="form.errors.name ?? form.errors.parent_id">
                    <UiInput v-model="form.name" placeholder="Folder name" autofocus class="w-full" />
                </UiFormField>
                <div class="flex justify-end gap-2">
                    <UiButton color="neutral" variant="ghost" @click="open = false">Cancel</UiButton>
                    <UiButton type="submit" :loading="form.processing">Create</UiButton>
                </div>
            </form>
        </template>
    </UiModal>
</template>
