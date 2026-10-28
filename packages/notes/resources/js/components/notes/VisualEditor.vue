<script setup lang="ts">
import type { ChecklistAction, TableAction } from '@/components/notes/note';
import { checkedToBottomTransaction, deleteCheckedTransaction, setAllCheckedTransaction } from '@/editor/checklists';
import { applyParagraphStyle, currentParagraphStyle, prepareFirstLine } from '@/editor/commands';
import { editorExtensions } from '@/editor/extensions';
import { findState, replaceCurrent, replaceEvery, setFind, stepFind } from '@/editor/Find';
import { splitFrontMatter } from '@/editor/frontMatter';
import { editorMarkdown, loadMarkdown } from '@/editor/markdown';
import { todayIso, type SlashCommand } from '@/editor/slashCommands';
import { SlashMenu, type SlashState } from '@/editor/SlashMenu';
import type { FormatCommand, ParagraphStyle } from '@/lib/markdownFormat';
import type { EditorState, Transaction } from '@tiptap/pm/state';
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
    checkedToBottom?: boolean;
    onSlashKey?: (key: string) => boolean;
    spellcheck?: boolean;
    textDirection?: 'auto' | 'ltr' | 'rtl';
}>();

const emit = defineEmits<{
    'update:modelValue': [markdown: string];
    faithful: [faithful: boolean];
    style: [style: ParagraphStyle | null];
    inTable: [inTable: boolean];
    slash: [state: SlashState | null];
    find: [state: { count: number; current: number }];
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
    extensions: [
        ...editorExtensions({ checkedToBottom: () => props.checkedToBottom ?? false }),
        SlashMenu.configure({ onState: (state) => emit('slash', state), onKey: (key) => props.onSlashKey?.(key) ?? false }),
    ],
    editable: !props.readonly,
    textDirection: props.textDirection ?? 'auto',
    editorProps: {
        attributes: { class: 'note-preview tiptap h-full focus:outline-none', 'aria-label': 'Note', spellcheck: String(props.spellcheck ?? true) },
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
        emit('inTable', changed.isActive('table'));
        const found = findState(changed);
        emit('find', { count: found.matches.length, current: found.current });
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

const checklistTransactions: Record<ChecklistAction, (state: EditorState) => Transaction | null> = {
    'checked-to-bottom': checkedToBottomTransaction,
    'check-all': (state) => setAllCheckedTransaction(state, true),
    'uncheck-all': (state) => setAllCheckedTransaction(state, false),
    'delete-checked': deleteCheckedTransaction,
};

/**
 * Runs a whole-checklist action (editor/checklists.ts). Pending changes
 * from source mode are loaded first so they aren't lost.
 */
const checklistAction = (action: ChecklistAction): void => {
    if (!editor.value) {
        return;
    }

    if (pendingMarkdown !== null) {
        load(pendingMarkdown);
        pendingMarkdown = null;
    }

    const tr = checklistTransactions[action](editor.value.state);

    if (tr) {
        editor.value.view.dispatch(tr);
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

/**
 * Table commands from the toolbar's Table menu (Tab / Shift+Tab between
 * cells is built into Tiptap's table extension).
 */
const tableAction = (action: Exclude<TableAction, 'format'>): void => {
    const chain = editor.value?.chain().focus();

    if (!chain) {
        return;
    }

    const commands: Record<Exclude<TableAction, 'format'>, () => boolean> = {
        insert: () => chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        'row-above': () => chain.addRowBefore().run(),
        'row-below': () => chain.addRowAfter().run(),
        'column-left': () => chain.addColumnBefore().run(),
        'column-right': () => chain.addColumnAfter().run(),
        'delete-row': () => chain.deleteRow().run(),
        'delete-column': () => chain.deleteColumn().run(),
        'toggle-header': () => chain.toggleHeaderRow().run(),
        'delete-table': () => chain.deleteTable().run(),
    };

    commands[action]();
};

/**
 * Carries out a "/" menu command: removes the typed "/query", then inserts
 * or formats like the toolbar would.
 */
const runSlash = (command: SlashCommand, range: { from: number; to: number }): void => {
    const current = editor.value;

    if (!current) {
        return;
    }

    current.chain().focus().deleteRange(range).run();

    const { action } = command;

    switch (action.type) {
        case 'style':
            applyParagraphStyle(current, action.style);
            break;
        case 'format':
            if (action.command !== 'link') {
                run(action.command);
            }
            break;
        case 'table':
            tableAction('insert');
            break;
        case 'divider':
            current.chain().focus().setHorizontalRule().run();
            break;
        case 'tableOfContents':
            current.chain().focus().insertContent({ type: 'tableOfContents' }).run();
            break;
        case 'wikiLink':
            current.chain().focus().insertContent('[[]]').run();
            current.commands.setTextSelection(current.state.selection.from - 2);
            break;
        case 'date':
            current.chain().focus().insertContent(todayIso()).run();
            break;
    }
};

const insertText = (text: string): void => {
    editor.value?.chain().focus().insertContent(text).run();
};

/**
 * Find and replace (editor/Find.ts) on the visual editor; pending source
 * edits are loaded first so the search sees the latest text.
 */
const find = {
    search: (pattern: RegExp | null): void => {
        if (!editor.value) {
            return;
        }
        if (pendingMarkdown !== null) {
            load(pendingMarkdown);
            pendingMarkdown = null;
        }
        setFind(editor.value, pattern);
    },
    step: (step: 1 | -1): void => {
        if (editor.value) {
            stepFind(editor.value, step);
        }
    },
    replace: (replacement: string, regex: boolean): void => {
        if (editor.value) {
            replaceCurrent(editor.value, replacement, regex);
        }
    },
    replaceAll: (replacement: string, regex: boolean): void => {
        if (editor.value) {
            replaceEvery(editor.value, replacement, regex);
        }
    },
};

defineExpose({ run, setStyle, highlight, checklistAction, tableAction, runSlash, insertText, find, setLink, unsetLink, isLinkActive, focus });
</script>

<template>
    <EditorContent :editor="editor" class="h-full min-w-0 flex-1 overflow-y-auto" />
</template>
