<script setup lang="ts">
import FormatToolbar from '@/components/notes/FormatToolbar.vue';
import type { ChecklistAction, EditorMode, NoteDetailData, TableAction } from '@/components/notes/note';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import VisualEditor from '@/components/notes/VisualEditor.vue';
import { useAutosave } from '@/composables/useAutosave';
import { useChecklistDrag } from '@/composables/useChecklistDrag';
import { htmlToMarkdown } from '@/editor/htmlToMarkdown';
import { autoPair, deletePair } from '@/lib/autoPair';
import { jsonHeaders } from '@/lib/http';
import {
    applyEdit,
    applyParagraphStyle,
    continueList,
    duplicateLines,
    formatMarkdown,
    indentListLines,
    moveLines,
    toggleComment,
    toggleHighlight,
    type FormatCommand,
    type ParagraphStyle,
    type Selection,
    type TextEdit,
} from '@/lib/markdownFormat';
import { pasteUrlOverSelection } from '@/lib/paste';
import { formatTable, insertTable, moveToCell } from '@/lib/tables';
import { Link, router } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';

const props = defineProps<{
    note: NoteDetailData;
    readonly?: boolean;
    defaultMode?: EditorMode;
    autosaveDelay?: number;
    firstLineStyle?: 'title' | 'heading' | 'subheading' | 'body';
    checkedToBottom?: boolean;
}>();

const emit = defineEmits<{
    saved: [note: NoteDetailData];
    createLinked: [title: string];
}>();

const body = ref(props.note.body);
const html = ref(props.note.html);
const tags = ref(props.note.tags);
const backlinks = ref(props.note.backlinks);

const onServerSave = (note: NoteDetailData): void => {
    html.value = note.html;
    tags.value = note.tags;
    backlinks.value = note.backlinks;
    emit('saved', note);
};

const { status, error, flush, adopt, isDirty } = useAutosave(props.note, body, onServerSave, props.autosaveDelay);

const togglingTask = ref(false);
const taskError = ref<string | null>(null);

/**
 * Sends a checklist change made in the preview (tick or move) to the
 * server. The text is saved first so the preview's line numbers match what
 * the server has; the server rewrites the Markdown and sends the note back.
 */
const changeChecklist = async (url: string, payload: Record<string, unknown>): Promise<void> => {
    togglingTask.value = true;
    taskError.value = null;

    try {
        await flush();
        if (isDirty.value) {
            return;
        }

        const response = await fetch(url, { method: 'PUT', headers: jsonHeaders(), credentials: 'same-origin', body: JSON.stringify(payload) });
        const result = await response.json().catch(() => null);

        if (!response.ok) {
            taskError.value = result?.errors?.line?.[0] ?? result?.message ?? 'Could not update the checklist.';
            return;
        }

        adopt(result.data as NoteDetailData);
        onServerSave(result.data as NoteDetailData);
    } finally {
        togglingTask.value = false;
    }
};

const toggleTask = (checkbox: HTMLInputElement): Promise<void> => {
    const checked = checkbox.checked;
    checkbox.checked = !checked;

    return changeChecklist(route('notes.checklist.update', { note: props.note.id, line: Number(checkbox.dataset.taskLine) }), { checked });
};

const preview = useTemplateRef<HTMLElement>('preview');

useChecklistDrag(
    preview,
    html,
    () => !props.readonly && !togglingTask.value,
    (line, beforeLine) => changeChecklist(route('notes.checklist.move', { note: props.note.id }), { line, before_line: beforeLine }),
);

const onPreviewClick = (event: MouseEvent): void => {
    const target = event.target;
    const wikiLink = target instanceof Element ? target.closest<HTMLAnchorElement>('a.wiki-link') : null;

    if (wikiLink && !event.metaKey && !event.ctrlKey) {
        event.preventDefault();

        if (wikiLink.dataset.noteId) {
            router.visit(wikiLink.href);
        } else if (wikiLink.dataset.wikiTitle) {
            emit('createLinked', wikiLink.dataset.wikiTitle);
        }

        return;
    }

    if (target instanceof HTMLInputElement && target.type === 'checkbox' && target.dataset.taskLine) {
        if (togglingTask.value || props.readonly) {
            event.preventDefault();
            return;
        }
        toggleTask(target);
    }
};

const preferredMode = ref<EditorMode>(props.defaultMode ?? 'edit');
const isWide = useMediaQuery('(min-width: 1280px)');

/**
 * Whether the visual editor can keep this note's Markdown exactly (see
 * editor/markdown.ts). If not, the note opens as source, unless the user
 * chooses to edit it visually anyway.
 */
const faithful = ref(true);
const visualAnyway = ref(false);

/**
 * The mode actually shown: trashed notes are read-only previews, split
 * needs a wide screen, and notes the visual editor can't keep exactly open
 * as source.
 */
const mode = computed<EditorMode>(() => {
    if (props.readonly) {
        return 'preview';
    }

    if (preferredMode.value === 'split' && !isWide.value) {
        return 'source';
    }

    if (preferredMode.value === 'edit' && !faithful.value && !visualAnyway.value) {
        return 'source';
    }

    return preferredMode.value;
});

const showsSource = computed(() => mode.value === 'source' || mode.value === 'split');

const modeItems = computed(() => [
    { value: 'edit', icon: 'i-lucide-pencil', label: 'Visual editor' },
    { value: 'source', icon: 'i-lucide-code-xml', label: 'Markdown source' },
    ...(isWide.value ? [{ value: 'split', icon: 'i-lucide-columns-2', label: 'Source and preview' }] : []),
    { value: 'preview', icon: 'i-lucide-eye', label: 'Preview' },
]);

const setMode = async (next: EditorMode): Promise<void> => {
    if (next === 'split' || next === 'preview') {
        await flush();
    }
    if (next === 'edit' && preferredMode.value === 'edit') {
        visualAnyway.value = true;
    }
    preferredMode.value = next;
};

const visual = useTemplateRef<InstanceType<typeof VisualEditor>>('visual');

/**
 * [[Wiki links]] in the visual editor resolve on click, with the same rules
 * as the preview: open the note, or offer to create it.
 */
const openWikiLink = async (title: string): Promise<void> => {
    const response = await fetch(route('notes.links.resolve', { title, from: props.note.id }), {
        headers: jsonHeaders(),
        credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => null);
    const id = payload?.data?.id;

    if (response.ok && typeof id === 'number') {
        router.visit(route('notes.show', { note: id }));
    } else if (response.ok) {
        emit('createLinked', title);
    }
};

const openTag = (tag: string): void => {
    router.visit(route('notes.index', { tag }));
};

const linkPromptOpen = ref(false);
const linkUrl = ref('');

/**
 * Accepts web and mail addresses; anything without a scheme is treated as a web address.
 */
const normalizeUrl = (url: string): string => (/^(https?:|mailto:)/i.test(url) ? url : `https://${url}`);

const confirmLink = (): void => {
    const url = linkUrl.value.trim();
    linkPromptOpen.value = false;

    if (url) {
        visual.value?.setLink(normalizeUrl(url));
    } else {
        visual.value?.focus();
    }
};

const saveNow = (event: KeyboardEvent): void => {
    event.preventDefault();
    flush();
};

const textarea = useTemplateRef<HTMLTextAreaElement>('textarea');

const visualStyle = ref<ParagraphStyle | null>(null);
const visualInTable = ref(false);

/**
 * Table menu: Tiptap table commands in the visual editor, Markdown
 * insert / line-up helpers in source (lib/tables.ts).
 */
const tableAction = (action: TableAction): void => {
    if (togglingTask.value) {
        return;
    }

    if (mode.value === 'edit') {
        if (action !== 'format') {
            visual.value?.tableAction(action);
        }

        return;
    }

    editSource(action === 'format' ? formatTable : action === 'insert' ? insertTable : () => null);
};

/**
 * Paragraph styles act on the visual editor's blocks, or on the selected
 * source lines as Markdown.
 */
const setStyle = (style: ParagraphStyle): void => {
    if (togglingTask.value) {
        return;
    }

    if (mode.value === 'edit') {
        visual.value?.setStyle(style);
    } else if (textarea.value) {
        const { value, selectionStart, selectionEnd } = textarea.value;
        applyEdit(textarea.value, applyParagraphStyle(style, { text: value, start: selectionStart, end: selectionEnd }));
    }
};

const highlight = (color: string | null): void => {
    if (togglingTask.value) {
        return;
    }

    if (mode.value === 'edit') {
        visual.value?.highlight(color);
    } else if (textarea.value) {
        const { value, selectionStart, selectionEnd } = textarea.value;
        applyEdit(textarea.value, toggleHighlight({ text: value, start: selectionStart, end: selectionEnd }, color));
    }
};

/**
 * Whole-checklist actions go through the visual editor, which knows the
 * list structure. A note it can't keep exactly is left alone.
 */
const canRewriteVisually = computed(() => faithful.value || visualAnyway.value);

const checklistAction = (action: ChecklistAction): void => {
    if (togglingTask.value || !canRewriteVisually.value) {
        return;
    }

    visual.value?.checklistAction(action);
};

const format = (command: FormatCommand): void => {
    if (togglingTask.value) {
        return;
    }

    if (mode.value === 'edit') {
        if (command !== 'link') {
            visual.value?.run(command);
        } else if (visual.value?.isLinkActive()) {
            visual.value.unsetLink();
        } else {
            linkUrl.value = '';
            linkPromptOpen.value = true;
        }

        return;
    }

    if (!textarea.value) {
        return;
    }

    const { value, selectionStart, selectionEnd } = textarea.value;
    applyEdit(textarea.value, formatMarkdown(command, { text: value, start: selectionStart, end: selectionEnd }));
};

const shortcuts: Record<string, FormatCommand> = { b: 'bold', i: 'italic', u: 'underline', k: 'link' };

/**
 * Tab / Shift+Tab nest and un-nest list and checklist lines in Markdown
 * source (the visual editor does this itself). Elsewhere Tab keeps moving focus.
 */
const indentSourceList = (event: KeyboardEvent): void => {
    if (!textarea.value || togglingTask.value) {
        return;
    }

    const { value, selectionStart, selectionEnd } = textarea.value;
    const selection = { text: value, start: selectionStart, end: selectionEnd };
    const edit = moveToCell(selection, event.shiftKey) ?? indentListLines(selection, event.shiftKey);

    if (edit) {
        event.preventDefault();
        applyEdit(textarea.value, edit);
    }
};

/**
 * Enter continues lists, checklists and quotes in Markdown source, or ends
 * them on an empty item (the visual editor does this itself). Shift+Enter
 * always inserts a plain new line.
 */
const continueSourceList = (event: KeyboardEvent): void => {
    if (!textarea.value || togglingTask.value || event.isComposing) {
        return;
    }

    const { value, selectionStart, selectionEnd } = textarea.value;
    const edit = continueList({ text: value, start: selectionStart, end: selectionEnd });

    if (edit) {
        event.preventDefault();
        applyEdit(textarea.value, edit);
    }
};

/**
 * Applies a Markdown source edit computed from the textarea's selection.
 */
const editSource = (build: (selection: Selection) => TextEdit | null): boolean => {
    if (!textarea.value || togglingTask.value) {
        return false;
    }

    const { value, selectionStart, selectionEnd } = textarea.value;
    const edit = build({ text: value, start: selectionStart, end: selectionEnd });

    if (edit) {
        applyEdit(textarea.value, edit);
    }

    return edit !== null;
};

/**
 * Pasting in Markdown source: a URL over selected text makes a link, and
 * rich text (web pages, Word, Google Docs) arrives as Markdown instead of
 * losing its formatting. Plain-text pastes are untouched.
 */
const onSourcePaste = (event: ClipboardEvent): void => {
    const plain = event.clipboardData?.getData('text/plain') ?? '';
    const html = event.clipboardData?.getData('text/html') ?? '';

    if (editSource((selection) => pasteUrlOverSelection(selection, plain))) {
        event.preventDefault();

        return;
    }

    if (html.trim() === '') {
        return;
    }

    const markdown = htmlToMarkdown(html);

    if (markdown === '' || markdown === plain.trim()) {
        return;
    }

    event.preventDefault();
    editSource(({ start, end }) => ({
        from: start,
        to: end,
        insert: markdown,
        selectionStart: start + markdown.length,
        selectionEnd: start + markdown.length,
    }));
};

/**
 * Alt+Up / Alt+Down move the current lines, Shift+Alt+Down duplicates
 * them (the visual editor has the same shortcuts, editor/LineCommands.ts).
 */
const moveSourceLines = (event: KeyboardEvent): void => {
    event.preventDefault();

    if (event.shiftKey) {
        if (event.key === 'ArrowDown') {
            editSource(duplicateLines);
        }

        return;
    }

    editSource((selection) => moveLines(selection, event.key === 'ArrowUp' ? 'up' : 'down'));
};

/**
 * Brackets, quotes and backticks type in pairs in Markdown source, a
 * selection gets wrapped (also with * _ ~ =), and Backspace removes an
 * empty pair (lib/autoPair.ts).
 */
const pairSourceCharacters = (event: KeyboardEvent): void => {
    if (!textarea.value || togglingTask.value || event.isComposing) {
        return;
    }

    const { value, selectionStart, selectionEnd } = textarea.value;
    const selection = { text: value, start: selectionStart, end: selectionEnd };
    const edit = event.key === 'Backspace' ? deletePair(selection) : autoPair(selection, event.key);

    if (edit) {
        event.preventDefault();
        applyEdit(textarea.value, edit);
    }
};

/**
 * Shortcuts the visual editor doesn't already handle itself (Tiptap has
 * Mod-B/I/U built in); in source mode all of them go through the toolbar.
 */
const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Tab' && !event.metaKey && !event.ctrlKey && !event.altKey && event.target === textarea.value) {
        indentSourceList(event);

        return;
    }

    if (event.key === 'Enter' && !event.shiftKey && !event.metaKey && !event.ctrlKey && !event.altKey && event.target === textarea.value) {
        continueSourceList(event);

        return;
    }

    if (event.altKey && !event.metaKey && !event.ctrlKey && event.target === textarea.value && ['ArrowUp', 'ArrowDown'].includes(event.key)) {
        moveSourceLines(event);

        return;
    }

    if (
        !event.metaKey &&
        !event.ctrlKey &&
        !event.altKey &&
        event.target === textarea.value &&
        (event.key.length === 1 || event.key === 'Backspace')
    ) {
        pairSourceCharacters(event);

        return;
    }

    if (!(event.metaKey || event.ctrlKey) || event.altKey) {
        return;
    }

    const key = event.key.toLowerCase();

    if (event.shiftKey) {
        if (key === 'x') {
            event.preventDefault();
            format('strike');
        }

        return;
    }

    if (key === 's') {
        saveNow(event);
    } else if (key === '/' && event.target === textarea.value) {
        event.preventDefault();
        editSource(toggleComment);
    } else if (mode.value === 'edit' && key !== 'k') {
        return;
    } else if (shortcuts[key]) {
        event.preventDefault();
        format(shortcuts[key]);
    }
};
</script>

<template>
    <div class="flex h-full flex-col gap-2">
        <FormatToolbar
            v-if="mode !== 'preview'"
            :active-style="mode === 'edit' ? visualStyle : null"
            :in-table="mode === 'edit' && visualInTable"
            :source="mode !== 'edit'"
            @format="format"
            @style="setStyle"
            @highlight="highlight"
            @checklist-action="checklistAction"
            @table-action="tableAction"
        />
        <div
            v-if="!faithful && mode === 'source' && preferredMode === 'edit'"
            class="bg-elevated flex items-center gap-2 rounded-md px-3 py-2 text-xs"
        >
            <UiIcon name="i-lucide-info" class="text-dimmed size-4 shrink-0" />
            <span class="text-muted flex-1">This note uses Markdown the visual editor can't keep exactly, so it opened as source.</span>
            <UiButton size="xs" color="neutral" variant="link" @click="visualAnyway = true">Edit visually anyway</UiButton>
        </div>
        <div class="flex min-h-0 flex-1 gap-4" @keydown="onKeydown">
            <VisualEditor
                v-if="!readonly"
                v-show="mode === 'edit'"
                ref="visual"
                :active="mode === 'edit'"
                :first-line-style="firstLineStyle"
                :checked-to-bottom="checkedToBottom"
                v-model="body"
                @faithful="(value) => (faithful = value)"
                @style="(value) => (visualStyle = value)"
                @in-table="(value) => (visualInTable = value)"
                @open-wiki-link="openWikiLink"
                @open-tag="openTag"
            />
            <textarea
                v-if="showsSource"
                ref="textarea"
                v-model="body"
                class="h-full min-w-0 flex-1 resize-none bg-transparent font-mono text-sm leading-relaxed focus:outline-none"
                placeholder="Start writing..."
                aria-label="Note"
                :readonly="togglingTask"
                autofocus
                @paste="onSourcePaste"
            />
            <!-- The HTML is rendered on the server with raw HTML escaped and unsafe links removed (NoteMarkdown). -->
            <!-- eslint-disable-next-line vue/no-v-html -->
            <article
                v-if="mode === 'split' || mode === 'preview'"
                class="note-preview h-full min-w-0 flex-1 overflow-y-auto"
                :class="{ 'border-default border-l pl-4': mode === 'split' }"
                ref="preview"
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
        <details v-if="backlinks.length" class="border-default border-t pt-2 text-sm">
            <summary class="text-muted cursor-pointer text-xs">
                Linked from {{ backlinks.length }} note{{ backlinks.length === 1 ? '' : 's' }}
            </summary>
            <ul class="mt-1 flex flex-col gap-1">
                <li v-for="backlink in backlinks" :key="backlink.id">
                    <Link
                        :href="route('notes.show', { note: backlink.id })"
                        class="hover:bg-elevated flex items-center justify-between gap-2 rounded px-2 py-1"
                    >
                        <span class="truncate">{{ backlink.title || 'Untitled note' }}</span>
                        <span class="text-dimmed shrink-0 text-xs">{{ backlink.updated_at_for_humans }}</span>
                    </Link>
                </li>
            </ul>
        </details>
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

        <UiModal v-model:open="linkPromptOpen" title="Add link" description="Link the selected text, or insert the address as a link.">
            <template #body>
                <form class="flex gap-2" @submit.prevent="confirmLink">
                    <UiInput v-model="linkUrl" placeholder="https://example.com" aria-label="Link address" class="flex-1" autofocus />
                    <UiButton type="submit">Add</UiButton>
                </form>
            </template>
        </UiModal>
    </div>
</template>
