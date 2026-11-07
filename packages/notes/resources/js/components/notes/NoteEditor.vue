<script setup lang="ts">
import type { NoteDetailData } from '@/components/notes/note';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import VisualEditor from '@/components/notes/VisualEditor.vue';
import { useAutosave } from '@/composables/useAutosave';
import { ref } from 'vue';

const props = defineProps<{
    note: NoteDetailData;
}>();

const emit = defineEmits<{
    saved: [note: NoteDetailData];
    restore: [];
}>();

const body = ref(props.note.body);

const { status, error, save } = useAutosave(props.note, body, (saved) => emit('saved', saved));
</script>

<template>
    <div class="flex h-full flex-col gap-2">
        <p v-if="note.trashed" class="bg-elevated text-muted flex items-center gap-2 rounded-md px-3 py-2 text-xs" role="status">
            <UiIcon name="i-lucide-trash-2" class="size-3.5" />
            In the trash. Restore it to edit.
            <UiButton size="xs" color="neutral" variant="link" class="p-0" @click="emit('restore')">Restore</UiButton>
        </p>
        <div class="readable-width min-h-0 flex-1">
            <VisualEditor v-model="body" :readonly="note.trashed" @update:model-value="save" />
        </div>
        <div v-if="!note.trashed" class="flex justify-end">
            <SaveStatus :status="status" :error="error" @retry="save" />
        </div>
    </div>
</template>
