<script setup lang="ts">
import EditorToolbar from '@/components/notes/EditorToolbar.vue';
import { prepareFirstLine } from '@/editor/commands';
import { editorExtensions } from '@/editor/extensions';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';

/**
 * The Tiptap editor. Markdown goes in through v-model and comes back out on
 * every edit; the database keeps the Markdown.
 */
const props = defineProps<{
    modelValue: string;
    readonly?: boolean;
    textDirection?: 'auto' | 'ltr' | 'rtl';
}>();

const emit = defineEmits<{
    'update:modelValue': [markdown: string];
}>();

let lastMarkdown = props.modelValue;

const editor = useEditor({
    extensions: editorExtensions(),
    editable: !props.readonly,
    textDirection: props.textDirection ?? 'auto',
    editorProps: {
        attributes: { class: 'note-content tiptap h-full focus:outline-none', 'aria-label': 'Note' },
    },
    onCreate: ({ editor: created }) => {
        loadMarkdown(created, props.modelValue);
        prepareFirstLine(created, props.modelValue);
    },
    onUpdate: ({ editor: updated }) => {
        lastMarkdown = editorMarkdown(updated);
        emit('update:modelValue', lastMarkdown);
    },
});

/**
 * Text set from outside (a newer version from the server) replaces what's shown.
 */
watch(
    () => props.modelValue,
    (markdown) => {
        if (editor.value && markdown !== lastMarkdown) {
            lastMarkdown = markdown;
            loadMarkdown(editor.value, markdown);
        }
    },
);

watch(
    () => props.readonly,
    (readonly) => editor.value?.setEditable(!readonly),
);

defineExpose({ focus: () => editor.value?.commands.focus() });
</script>

<template>
    <div class="flex h-full flex-col gap-2">
        <EditorToolbar v-if="editor && !readonly" :editor="editor" />
        <EditorContent :editor="editor" class="min-h-0 flex-1 overflow-y-auto" />
    </div>
</template>
