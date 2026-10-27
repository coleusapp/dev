<script setup lang="ts">
import type { NoteData } from '@/components/notes/note';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import { useAutosave } from '@/composables/useAutosave';
import { ref } from 'vue';

const props = defineProps<{
    note: NoteData;
}>();

const emit = defineEmits<{
    saved: [note: NoteData];
}>();

const body = ref(props.note.body);

const { status, error, flush } = useAutosave(props.note, body, (note) => emit('saved', note));

const saveNow = (event: KeyboardEvent): void => {
    event.preventDefault();
    flush();
};
</script>

<template>
    <div class="flex h-full flex-col gap-2">
        <textarea
            v-model="body"
            class="h-full w-full flex-1 resize-none bg-transparent font-mono text-sm leading-relaxed focus:outline-none"
            placeholder="Start writing..."
            aria-label="Note"
            autofocus
            @keydown.meta.s="saveNow"
            @keydown.ctrl.s="saveNow"
        />
        <div class="flex items-center justify-end">
            <SaveStatus :status="status" :error="error" @retry="flush" />
        </div>
    </div>
</template>
