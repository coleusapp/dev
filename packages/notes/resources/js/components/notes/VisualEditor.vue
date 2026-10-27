<script setup lang="ts">
import { applyParagraphStyle, currentParagraphStyle, prepareFirstLine } from '@/editor/commands';
import { editorExtensions } from '@/editor/extensions';
import { splitFrontMatter } from '@/editor/frontMatter';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import type { FormatCommand, ParagraphStyle } from '@/lib/markdownFormat';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';

/**
 * The visual (Tiptap) editor. Markdown goes in through v-model and comes
 * back out on every edit; front matter is kept aside and re-attached.
 */
const props = defineProps<{
    modelValue: string;
    readonly?: boolean;
    active?: boolean;
    firstLineStyle?: 'title' | 'heading' | 'subheading' | 'body';
}>();

const emit = defineEmits<{
    'update:modelValue': [markdown: string];
    faithful: [faithful: boolean];
    style: [style: ParagraphStyle | null];
    openWikiLink: [title: string];
    openTag: [tag: string];
}>();

let frontMatter = '';
let lastMarkdown = props.modelValue;
let pendingMarkdown: string | null = null;

const load = (markdown: string): void => {
    if (!editor.value) {
        return;
    }

    const result = loadMarkdown(editor.value, markdown);
    frontMatter = result.frontMatter;
    lastMarkdown = markdown;
    emit('faithful', result.faithful);
};

const editor = useEditor({
    extensions: editorExtensions(),
    editable: !props.readonly,
    editorProps: {
        attributes: { class: 'note-preview tiptap h-full focus:outline-none', 'aria-label': 'Note', spellcheck: 'true' },
        handleDOMEvents: {
            click: (_view, event) => {
                const target = event.target instanceof Element ? event.target : null;
                const wikiLink = target?.closest<HTMLElement>('[data-wiki-link]');
                const tag = target?.closest<HTMLElement>('.hashtag');

                if (wikiLink?.dataset.wikiLink) {
                    event.preventDefault();
                    emit('openWikiLink', wikiLink.dataset.wikiLink);

                    return true;
                }

                if (tag?.dataset.tag && (event.metaKey || event.ctrlKey)) {
                    event.preventDefault();
                    emit('openTag', tag.dataset.tag);

                    return true;
                }

                return false;
            },
        },
    },
    onCreate: ({ editor: created }) => {
        const result = loadMarkdown(created, props.modelValue);
        frontMatter = result.frontMatter;
        emit('faithful', result.faithful);
        prepareFirstLine(created, splitFrontMatter(props.modelValue).body, props.firstLineStyle ?? 'body');
    },
    onUpdate: ({ editor: updated }) => {
        lastMarkdown = editorMarkdown(updated, frontMatter);
        emit('update:modelValue', lastMarkdown);
    },
    onTransaction: ({ editor: changed }) => {
        emit('style', currentParagraphStyle(changed));
    },
});

/**
 * Changes made elsewhere (source mode, a checklist toggled in the preview,
 * a restore) are loaded when the visual editor is shown, not on every
 * keystroke while it's hidden.
 */
watch(
    () => props.modelValue,
    (markdown) => {
        if (markdown === lastMarkdown) {
            return;
        }

        if (props.active) {
            load(markdown);
        } else {
            pendingMarkdown = markdown;
        }
    },
);

watch(
    () => props.active,
    (active) => {
        if (active && pendingMarkdown !== null) {
            load(pendingMarkdown);
            pendingMarkdown = null;
        }
    },
);

watch(
    () => props.readonly,
    (readonly) => editor.value?.setEditable(!readonly),
);

/**
 * Applies a toolbar command to the selection.
 */
const run = (command: Exclude<FormatCommand, 'link'>): void => {
    const chain = editor.value?.chain().focus();

    if (!chain || !editor.value) {
        return;
    }

    const { from, to, $from, $to } = editor.value.state.selection;

    switch (command) {
        case 'bold':
            chain.toggleBold().run();
            break;
        case 'italic':
            chain.toggleItalic().run();
            break;
        case 'underline':
            chain.toggleUnderline().run();
            break;
        case 'strike':
            chain.toggleStrike().run();
            break;
        case 'heading':
            chain.toggleHeading({ level: 2 }).run();
            break;
        case 'list':
            chain.toggleBulletList().run();
            break;
        case 'checklist':
            chain.toggleTaskList().run();
            break;
        case 'numbered':
            chain.toggleOrderedList().run();
            break;
        case 'quote':
            chain.toggleBlockquote().run();
            break;
        case 'code':
            if (from !== to && !$from.sameParent($to)) {
                chain.toggleCodeBlock().run();
            } else {
                chain.toggleCode().run();
            }
            break;
    }
};

const isLinkActive = (): boolean => editor.value?.isActive('link') ?? false;

/**
 * Links the selection to a URL, or inserts the URL as a link when nothing is selected.
 */
const setLink = (href: string): void => {
    const chain = editor.value?.chain().focus().extendMarkRange('link');

    if (!chain || !editor.value) {
        return;
    }

    if (editor.value.state.selection.empty) {
        chain.insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] }).run();
    } else {
        chain.setLink({ href }).run();
    }
};

const unsetLink = (): void => {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
};

const focus = (): void => {
    editor.value?.commands.focus();
};

const setStyle = (style: ParagraphStyle): void => {
    if (editor.value) {
        applyParagraphStyle(editor.value, style);
    }
};

/**
 * Highlights the selection in a colour, or removes the highlight when it already has that colour.
 */
const highlight = (color: string | null): void => {
    const chain = editor.value?.chain().focus();

    if (!chain || !editor.value) {
        return;
    }

    if (editor.value.isActive('highlight', { color })) {
        chain.unsetHighlight().run();
    } else {
        chain.setHighlight(color ? { color } : undefined).run();
    }
};

defineExpose({ run, setStyle, highlight, setLink, unsetLink, isLinkActive, focus });
</script>

<template>
    <EditorContent :editor="editor" class="h-full min-w-0 flex-1 overflow-y-auto" />
</template>
