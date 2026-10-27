<script setup lang="ts">
import FormatToolbar from '@/components/notes/FormatToolbar.vue';
import type { NoteDetailData } from '@/components/notes/note';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import { useAutosave } from '@/composables/useAutosave';
import { jsonHeaders } from '@/lib/http';
import { applyEdit, formatMarkdown, type FormatCommand } from '@/lib/markdownFormat';
import { useLocalStorage, useMediaQuery } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';

type EditorMode = 'edit' | 'split' | 'preview';

const props = defineProps<{
    note: NoteDetailData;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    saved: [note: NoteDetailData];
}>();

const body = ref(props.note.body);
const html = ref(props.note.html);
const tags = ref(props.note.tags);

const onServerSave = (note: NoteDetailData): void => {
    html.value = note.html;
    tags.value = note.tags;
    emit('saved', note);
};

const { status, error, flush, adopt, isDirty } = useAutosave(props.note, body, onServerSave);

const togglingTask = ref(false);
const taskError = ref<string | null>(null);

/**
 * Checkboxes carry the source line they were rendered from. The text is
 * saved first so those line numbers match what the server has, then the
 * server flips that one line and sends the note back.
 */
const toggleTask = async (checkbox: HTMLInputElement): Promise<void> => {
    const line = Number(checkbox.dataset.taskLine);
    const checked = checkbox.checked;
    checkbox.checked = !checked;

    togglingTask.value = true;
    taskError.value = null;

    try {
        await flush();
        if (isDirty.value) {
            return;
        }

        const response = await fetch(route('notes.checklist.update', { note: props.note.id, line }), {
            method: 'PUT',
            headers: jsonHeaders(),
            credentials: 'same-origin',
            body: JSON.stringify({ checked }),
        });
        const payload = await response.json().catch(() => null);

        if (!response.ok) {
            taskError.value = payload?.errors?.line?.[0] ?? payload?.message ?? 'Could not update the checklist.';
            return;
        }

        adopt(payload.data as NoteDetailData);
        onServerSave(payload.data as NoteDetailData);
    } finally {
        togglingTask.value = false;
    }
};

const onPreviewClick = (event: MouseEvent): void => {
    const target = event.target;

    if (target instanceof HTMLInputElement && target.type === 'checkbox' && target.dataset.taskLine) {
        if (togglingTask.value || props.readonly) {
            event.preventDefault();
            return;
        }
        toggleTask(target);
    }
};

const preferredMode = useLocalStorage<EditorMode>('notes.editor-mode', 'edit');
const isWide = useMediaQuery('(min-width: 1280px)');

/**
 * Split view needs the room; on narrow screens it falls back to the editor.
 */
const mode = computed<EditorMode>(() => {
    if (props.readonly) {
        return 'preview';
    }

    return preferredMode.value === 'split' && !isWide.value ? 'edit' : preferredMode.value;
});

const modeItems = computed(() => [
    { value: 'edit', icon: 'i-lucide-pencil', label: 'Edit' },
    ...(isWide.value ? [{ value: 'split', icon: 'i-lucide-columns-2', label: 'Split' }] : []),
    { value: 'preview', icon: 'i-lucide-eye', label: 'Preview' },
]);

const setMode = async (next: EditorMode): Promise<void> => {
    if (next !== 'edit') {
        await flush();
    }
    preferredMode.value = next;
};

const saveNow = (event: KeyboardEvent): void => {
    event.preventDefault();
    flush();
};

const textarea = useTemplateRef<HTMLTextAreaElement>('textarea');

const format = (command: FormatCommand): void => {
    if (!textarea.value || togglingTask.value) {
        return;
    }

    const { value, selectionStart, selectionEnd } = textarea.value;
    applyEdit(textarea.value, formatMarkdown(command, { text: value, start: selectionStart, end: selectionEnd }));
};

const shortcuts: Record<string, FormatCommand> = { b: 'bold', i: 'italic', k: 'link' };

const onKeydown = (event: KeyboardEvent): void => {
    if (!(event.metaKey || event.ctrlKey) || event.altKey || event.shiftKey) {
        return;
    }

    const key = event.key.toLowerCase();

    if (key === 's') {
        saveNow(event);
    } else if (shortcuts[key]) {
        event.preventDefault();
        format(shortcuts[key]);
    }
};
</script>

<template>
    <div class="flex h-full flex-col gap-2">
        <FormatToolbar v-if="mode !== 'preview'" @format="format" />
        <div class="flex min-h-0 flex-1 gap-4">
            <textarea
                v-if="mode !== 'preview'"
                ref="textarea"
                v-model="body"
                class="h-full min-w-0 flex-1 resize-none bg-transparent font-mono text-sm leading-relaxed focus:outline-none"
                placeholder="Start writing..."
                aria-label="Note"
                :readonly="togglingTask"
                autofocus
                @keydown="onKeydown"
            />
            <!-- The HTML is rendered on the server with raw HTML escaped and unsafe links removed (NoteMarkdown). -->
            <!-- eslint-disable-next-line vue/no-v-html -->
            <article
                v-if="mode !== 'edit'"
                class="note-preview h-full min-w-0 flex-1 overflow-y-auto"
                :class="{ 'border-default border-l pl-4': mode === 'split' }"
                aria-label="Preview"
                @click="onPreviewClick"
                v-html="html"
            />
        </div>
        <ul v-if="tags.length" class="flex flex-wrap gap-1" aria-label="Tags">
            <li v-for="tag in tags" :key="tag">
                <UiBadge color="neutral" variant="soft" size="sm" icon="i-lucide-hash">{{ tag }}</UiBadge>
            </li>
        </ul>
        <div class="flex items-center justify-between gap-2">
            <p v-if="readonly" class="text-dimmed flex items-center gap-1 text-xs">
                <UiIcon name="i-lucide-trash-2" class="size-3" />
                In the trash. Restore it to edit.
            </p>
            <div v-else class="flex items-center gap-1" role="group" aria-label="Editor mode">
                <UiButton
                    v-for="item in modeItems"
                    :key="item.value"
                    :icon="item.icon"
                    :aria-label="item.label"
                    :aria-pressed="mode === item.value"
                    :color="mode === item.value ? 'primary' : 'neutral'"
                    variant="ghost"
                    size="xs"
                    @click="setMode(item.value as EditorMode)"
                />
            </div>
            <span v-if="taskError" class="text-error text-xs" role="alert">{{ taskError }}</span>
            <SaveStatus v-if="!readonly" :status="status" :error="error" @retry="flush" />
        </div>
    </div>
</template>
