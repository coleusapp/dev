<script setup lang="ts">
import type { NoteData } from '@/components/notes/note';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    note: NoteData;
}>();

const form = useForm({ body: props.note.body });

const save = (): void => {
    form.put(route('notes.update', { note: props.note.id }), { preserveScroll: true, preserveState: true });
};
</script>

<template>
    <form class="flex h-full flex-col gap-2" @submit.prevent="save">
        <textarea
            v-model="form.body"
            class="h-full w-full flex-1 resize-none bg-transparent font-mono text-sm leading-relaxed focus:outline-none"
            placeholder="Start writing..."
            autofocus
        />
        <div class="flex items-center justify-end gap-2">
            <span v-if="form.errors.body" class="text-error text-sm">{{ form.errors.body }}</span>
            <UiButton type="submit" color="neutral" variant="subtle" :loading="form.processing" :disabled="!form.isDirty">Save</UiButton>
        </div>
    </form>
</template>
