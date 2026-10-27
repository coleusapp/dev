<script setup lang="ts">
import type { FolderDialog, FolderNode } from '@/components/notes/note';
import { router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    dialog: FolderDialog | null;
    folders: FolderNode[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const open = computed({
    get: () => props.dialog !== null,
    set: (value: boolean) => {
        if (!value) {
            emit('close');
        }
    },
});

const form = useForm<{ name: string; parent_id: number | null }>({ name: '', parent_id: null });

watch(
    () => props.dialog,
    (dialog) => {
        form.clearErrors();
        form.name = dialog?.type === 'rename' || dialog?.type === 'move' ? dialog.folder.name : '';
        form.parent_id =
            dialog?.type === 'create'
                ? (dialog.parent?.id ?? null)
                : dialog?.type === 'move-note'
                  ? dialog.folderId
                  : dialog?.type
                    ? dialog.parentId
                    : null;
    },
);

const flatten = (nodes: FolderNode[], depth = 0, excluded: number | null = null): { id: number; label: string }[] =>
    nodes
        .filter((node) => node.id !== excluded)
        .flatMap((node) => [{ id: node.id, label: `${'   '.repeat(depth)}${node.name}` }, ...flatten(node.children, depth + 1, excluded)]);

/**
 * Where something can go: the top level / no folder, then every folder.
 * A folder can't be moved into itself or its own sub-folders.
 */
const destinations = computed(() => {
    const excluded = props.dialog?.type === 'move' ? props.dialog.folder.id : null;

    return [
        { label: props.dialog?.type === 'move-note' ? 'No folder' : 'Top level', value: 0 },
        ...flatten(props.folders, 0, excluded).map(({ id, label }) => ({ label, value: id })),
    ];
});

const titles = { create: 'New folder', rename: 'Rename folder', move: 'Move folder', 'move-note': 'Move note to folder' } as const;

const submit = (): void => {
    const dialog = props.dialog;
    const options = { preserveScroll: true, preserveState: true, onSuccess: () => emit('close') };

    if (!dialog) {
        return;
    }

    if (dialog.type === 'create') {
        form.post(route('notes.folders.store'), { onSuccess: () => emit('close') });
    } else if (dialog.type === 'move-note') {
        router.put(route('notes.folder.move', { note: dialog.noteId }), { folder_id: form.parent_id }, options);
    } else {
        form.put(route('notes.folders.update', { folder: dialog.folder.id }), options);
    }
};
</script>

<template>
    <UiModal v-model:open="open" :title="dialog ? titles[dialog.type] : ''">
        <template #body>
            <form class="flex flex-col gap-3" @submit.prevent="submit">
                <UiFormField v-if="dialog?.type === 'create' || dialog?.type === 'rename'" label="Name" :error="form.errors.name">
                    <UiInput v-model="form.name" placeholder="Folder name" autofocus class="w-full" />
                </UiFormField>
                <UiFormField
                    v-if="dialog?.type !== 'rename'"
                    :label="dialog?.type === 'create' ? 'Inside' : 'Move to'"
                    :error="form.errors.parent_id"
                >
                    <UiSelect
                        :model-value="form.parent_id ?? 0"
                        :items="destinations"
                        class="w-full"
                        @update:model-value="(value: number) => (form.parent_id = value || null)"
                    />
                </UiFormField>
                <div class="flex justify-end gap-2">
                    <UiButton color="neutral" variant="ghost" @click="emit('close')">Cancel</UiButton>
                    <UiButton type="submit" :loading="form.processing">{{
                        dialog?.type === 'create' ? 'Create' : dialog?.type === 'rename' ? 'Rename' : 'Move'
                    }}</UiButton>
                </div>
            </form>
        </template>
    </UiModal>
</template>
