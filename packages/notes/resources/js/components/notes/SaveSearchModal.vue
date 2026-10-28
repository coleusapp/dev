<script setup lang="ts">
import type { NoteFilters } from '@/components/notes/note';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    filters: NoteFilters;
    suggestedName: string;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({ label: '' });

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.label = props.suggestedName;
    }
});

const submit = (): void => {
    form.transform((data) => ({ ...data, ...props.filters })).post(route('notes.searches.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (open.value = false),
    });
};
</script>

<template>
    <UiModal v-model:open="open" title="Save search" description="Adds this list, with its search and filters, to your bookmarks.">
        <template #body>
            <form class="flex flex-col gap-3" @submit.prevent="submit">
                <UiFormField label="Name" :error="form.errors.label">
                    <UiInput v-model="form.label" class="w-full" autofocus />
                </UiFormField>
                <div class="flex justify-end gap-2">
                    <UiButton color="neutral" variant="ghost" @click="open = false">Cancel</UiButton>
                    <UiButton type="submit" :loading="form.processing">Save</UiButton>
                </div>
            </form>
        </template>
    </UiModal>
</template>
