<script setup lang="ts">
import EditorToolbar from '@/components/notes/EditorToolbar.vue';
import { prepareFirstLine } from '@/editor/commands';
import { editorExtensions } from '@/editor/extensions';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';

const props = defineProps<{
    modelValue: string | null;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [html: string];
}>();

const editor = useEditor({
    extensions: editorExtensions(),
    content: props.modelValue,
    editable: !props.readonly,
    textDirection: 'auto',
    editorProps: {
        attributes: { class: 'note-content tiptap h-full focus:outline-none', 'aria-label': 'Note' },
    },
    onCreate: ({ editor: created }) => prepareFirstLine(created),
    onUpdate: ({ editor: updated }) => emit('update:modelValue', updated.getHTML()),
});

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
