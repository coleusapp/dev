<script setup lang="ts">
import type { NoteDetailData } from '@/components/notes/note';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import VisualEditor from '@/components/notes/VisualEditor.vue';
import { useAutosave } from '@/composables/useAutosave';
import { ref, watch } from 'vue';

/**
 * One note open for editing: the Tiptap editor, saved as you type.
 */
const props = defineProps<{
    note: NoteDetailData;
    autosaveDelay?: number;
    textDirection?: 'auto' | 'ltr' | 'rtl';
    readableWidth?: boolean;
}>();

const emit = defineEmits<{
    saved: [note: NoteDetailData];
    restore: [];
}>();

const body = ref(props.note.body);
const updatedAt = ref(props.note.updated_at);

const { status, error, flush, adopt, isDirty } = useAutosave(
    props.note,
    body,
    (saved) => {
        updatedAt.value = saved.updated_at;
        emit('saved', saved);
    },
    props.autosaveDelay,
);

/**
 * A newer version of the note from the server (another tab) replaces the
 * text, unless there are unsaved edits here.
 */
watch(
    () => props.note,
    (note) => {
        if (note.updated_at !== updatedAt.value && !isDirty.value) {
            adopt(note);
            updatedAt.value = note.updated_at;
        }
    },
);

defineExpose({ flush });
</script>

<template>
    <div class="flex h-full flex-col gap-2">
        <p v-if="note.trashed" class="bg-elevated text-muted flex items-center gap-2 rounded-md px-3 py-2 text-xs" role="status">
            <UiIcon name="i-lucide-trash-2" class="size-3.5" />
            In the trash. Restore it to edit.
            <UiButton size="xs" color="neutral" variant="link" class="p-0" @click="emit('restore')">Restore</UiButton>
        </p>
        <div class="min-h-0 flex-1" :class="{ 'readable-width': readableWidth }">
            <VisualEditor v-model="body" :readonly="note.trashed" :text-direction="textDirection" />
        </div>
        <div v-if="!note.trashed" class="flex justify-end">
            <SaveStatus :status="status" :error="error" @retry="flush" />
        </div>
    </div>
</template>
