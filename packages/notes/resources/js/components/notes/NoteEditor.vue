<script setup lang="ts">
import type { PaletteCommand } from '@/components/notes/commands';
import DateMenu from '@/components/notes/DateMenu.vue';
import FindBar from '@/components/notes/FindBar.vue';
import FormatToolbar from '@/components/notes/FormatToolbar.vue';
import HighlightedText from '@/components/notes/HighlightedText.vue';
import LinkMenu from '@/components/notes/LinkMenu.vue';
import LinkPreviewCard from '@/components/notes/LinkPreviewCard.vue';
import type { ChecklistAction, EditorMode, NoteDetailData, TableAction } from '@/components/notes/note';
import NoteInfo from '@/components/notes/NoteInfo.vue';
import NoteTitle from '@/components/notes/NoteTitle.vue';
import OutlinePanel from '@/components/notes/OutlinePanel.vue';
import PropertiesPanel from '@/components/notes/PropertiesPanel.vue';
import SaveStatus from '@/components/notes/SaveStatus.vue';
import SlashMenu from '@/components/notes/SlashMenu.vue';
import TagMenu from '@/components/notes/TagMenu.vue';
import UnlinkedMentions from '@/components/notes/UnlinkedMentions.vue';
import VisualEditor from '@/components/notes/VisualEditor.vue';
import { useAutosave } from '@/composables/useAutosave';
import { useChecklistDrag } from '@/composables/useChecklistDrag';
import { useDateMenu, type OpenDateMenu } from '@/composables/useDateMenu';
import { useFindReplace } from '@/composables/useFindReplace';
import { useLinkMenu, type OpenLinkMenu } from '@/composables/useLinkMenu';
import { useLinkPreview } from '@/composables/useLinkPreview';
import { usePreviewFolding } from '@/composables/usePreviewFolding';
import { useRelink } from '@/composables/useRelink';
import { useTagMenu, type OpenTagMenu } from '@/composables/useTagMenu';
import type { DateMenuState } from '@/editor/DateChips';
import { htmlToMarkdown } from '@/editor/htmlToMarkdown';
import type { LinkMenuState } from '@/editor/LinkMenu';
import { linkQuery } from '@/editor/linkQuery';
import { filterSlashCommands, slashQuery, todayIso, type SlashCommand } from '@/editor/slashCommands';
import type { SlashState } from '@/editor/SlashMenu';
import type { TagMenuState } from '@/editor/TagMenu';
import { tagQuery } from '@/editor/tagQuery';
import { autoPair, deletePair } from '@/lib/autoPair';
import { textareaCaretPosition } from '@/lib/caret';
import { dateQuery } from '@/lib/dates';
import { renderDiagrams } from '@/lib/diagrams';
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
import { renderMath } from '@/lib/math';
import { noteOutline, type OutlineHeading } from '@/lib/outline';
import { pasteUrlOverSelection } from '@/lib/paste';
import { matchesShortcut, shortcutKeys, type ShortcutId } from '@/lib/shortcuts';
import { formatTable, insertTable, moveToCell } from '@/lib/tables';
import { renameFirstLine } from '@/lib/title';
import { Link, router } from '@inertiajs/vue3';
import { useLocalStorage, useMediaQuery } from '@vueuse/core';
import { computed, nextTick, onActivated, onMounted, ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    note: NoteDetailData;
    readonly?: boolean;
    defaultMode?: EditorMode;
    autosaveDelay?: number;
    firstLineStyle?: 'title' | 'heading' | 'subheading' | 'body';
    checkedToBottom?: boolean;
    spellcheck?: boolean;
    textDirection?: 'auto' | 'ltr' | 'rtl';
    readableWidth?: boolean;
    focusHeading?: string | null;
    tags?: string[];
}>();

const emit = defineEmits<{
    saved: [note: NoteDetailData];
    createLinked: [title: string];
}>();

const body = ref(props.note.body);
const html = ref(props.note.html);
const tags = ref(props.note.tags);
const backlinks = ref(props.note.backlinks);
const outgoing = ref(props.note.outgoing);

const updatedAt = ref(props.note.updated_at);

const root = useTemplateRef<HTMLElement>('root');

/**
 * Scrolls a heading of the visual editor or the reading view into view: the
 * nth one (0-based) with that text, among those currently shown.
 */
const scrollToHeading = (heading: string, occurrence = 0): void => {
    const shown = Array.from(
        root.value?.querySelectorAll<HTMLElement>('.tiptap :is(h1,h2,h3,h4,h5,h6), .note-preview > :is(h1,h2,h3,h4,h5,h6)') ?? [],
    ).filter((element) => element.offsetParent !== null);
    const loose = (text: string): string => text.replace(/\s+/g, ' ').trim().toLowerCase();

    // Exact text first (outline, bookmarks); a typed [[Note#heading]] may differ in case or spacing.
    const target =
        shown.filter((element) => element.textContent?.trim() === heading)[occurrence] ??
        shown.find((element) => loose(element.textContent ?? '') === loose(heading));

    target?.scrollIntoView({ block: 'start' });
};

/**
 * Opened from a heading bookmark: scroll that heading into view once the
 * editor or the reading view has rendered.
 */

const focusRequestedHeading = (): void => {
    if (props.focusHeading) {
        const heading = props.focusHeading;
        setTimeout(() => scrollToHeading(heading), 50);
    }
};

onMounted(focusRequestedHeading);
onActivated(focusRequestedHeading);
watch(() => props.focusHeading, focusRequestedHeading);
const savedTitle = ref(props.note.title);
const propertiesPanel = useTemplateRef<InstanceType<typeof PropertiesPanel>>('propertiesPanel');

/**
 * Renaming rewrites the note's first line (titles come from it); the
 * visual editor reloads the new text and autosave stores it.
 */
const renameNote = (title: string): void => {
    body.value = renameFirstLine(body.value, title);
    savedTitle.value = title;
};

/**
 * Coming back to a note kept alive (for its undo history): if the note
 * changed elsewhere meanwhile and nothing here is unsaved, take the newer
 * text. Otherwise keep what's on screen.
 */
onActivated(() => {
    if (!isDirty.value && props.note.body !== body.value && props.note.updated_at > updatedAt.value) {
        adopt(props.note);
        onServerSave(props.note);
    }
});

/**
 * Links to this note's old title in other notes, after a rename (O2.6).
 */
/**
 * Hovering a link previews the linked note (O2.10), in both the visual
 * editor and the reading view; typing, clicking or scrolling closes it.
 */
const linkPreview = useLinkPreview({ from: () => props.note.id });

const relink = useRelink({ noteId: () => props.note.id, title: () => savedTitle.value });

const unlinkedMentions = useTemplateRef<InstanceType<typeof UnlinkedMentions>>('unlinkedMentions');

/**
 * Links change when other notes change (one starts linking here, or a
 * linked note is created or renamed) and the note is reloaded, not only
 * when this one is saved.
 */
watch(
    () => props.note.backlinks,
    (next) => (backlinks.value = next),
);
watch(
    () => props.note.outgoing,
    (next) => (outgoing.value = next),
);

const onServerSave = (note: NoteDetailData): void => {
    if (note.title !== savedTitle.value) {
        unlinkedMentions.value?.reset();
    }

    updatedAt.value = note.updated_at;
    savedTitle.value = note.title;
    void relink.check();
    html.value = note.html;
    tags.value = note.tags;
    backlinks.value = note.backlinks;
    outgoing.value = note.outgoing;
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

/**
 * Math and diagrams in the reading view and split preview are drawn after each render.
 */
const typesetPreview = (): void =>
    void nextTick(() => {
        void renderMath(preview.value);
        void renderDiagrams(preview.value);
    });

onMounted(typesetPreview);
onActivated(typesetPreview);

/**
 * In-note links ("#heading-day-one" from the table of contents or a
 * heading's anchor, or a hand-written "#day-one") scroll the reading view
 * instead of changing the page URL.
 */
const scrollToAnchor = (id: string): void => {
    if (id === '') {
        return;
    }

    const escaped = CSS.escape(id);

    preview.value?.querySelector<HTMLElement>(`[id="${escaped}"], [id="heading-${escaped}"]`)?.scrollIntoView({ block: 'start' });
};

usePreviewFolding(preview, html);

useChecklistDrag(
    preview,
    html,
    () => !props.readonly && !togglingTask.value,
    (line, beforeLine) => changeChecklist(route('notes.checklist.move', { note: props.note.id }), { line, before_line: beforeLine }),
);

const onPreviewClick = (event: MouseEvent): void => {
    const target = event.target;
    const wikiLink = target instanceof Element ? target.closest<HTMLAnchorElement>('a.wiki-link') : null;
    const inNoteLink = target instanceof Element ? target.closest<HTMLAnchorElement>('a[href^="#"]:not(.wiki-link)') : null;

    if (wikiLink && !event.metaKey && !event.ctrlKey) {
        event.preventDefault();

        if (wikiLink.dataset.noteId) {
            router.visit(wikiLink.href);
        } else if (wikiLink.dataset.wikiTitle) {
            emit('createLinked', wikiLink.dataset.wikiTitle);
        } else if (wikiLink.dataset.heading) {
            scrollToHeading(wikiLink.dataset.heading);
        }

        return;
    }

    if (inNoteLink) {
        event.preventDefault();
        scrollToAnchor(decodeURIComponent(inNoteLink.getAttribute('href')?.slice(1) ?? ''));

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

watch([html, mode], typesetPreview);

const modeItems = computed(() => [
    { value: 'edit', icon: 'i-lucide-pencil', label: 'Visual editor' },
    { value: 'source', icon: 'i-lucide-code-xml', label: 'Markdown source' },
    ...(isWide.value ? [{ value: 'split', icon: 'i-lucide-columns-2', label: 'Source and preview' }] : []),
    { value: 'preview', icon: 'i-lucide-book-open', label: 'Reading view' },
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
const openWikiLink = async (title: string, heading: string | null = null): Promise<void> => {
    if (title === '') {
        if (heading) {
            scrollToHeading(heading);
        }

        return;
    }

    const response = await fetch(route('notes.links.resolve', { title, from: props.note.id }), {
        headers: jsonHeaders(),
        credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => null);
    const id = payload?.data?.id;

    if (response.ok && typeof id === 'number') {
        router.visit(route('notes.show', { note: id, ...(heading ? { heading } : {}) }), { preserveState: true });
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

const outline = computed(() => noteOutline(body.value));
const isLarge = useMediaQuery('(min-width: 1024px)');

/**
 * The outline sits beside the note on large screens, where it stays open
 * across notes and visits; on smaller ones it slides over and closes after
 * a jump.
 */
const outlinePinned = useLocalStorage('notes.outline.open', false);
const outlineSheetOpen = ref(false);
const showsOutline = computed(() => (isLarge.value ? outlinePinned.value : outlineSheetOpen.value));

const toggleOutline = (): void => {
    if (isLarge.value) {
        outlinePinned.value = !outlinePinned.value;
    } else {
        outlineSheetOpen.value = !outlineSheetOpen.value;
    }
};

/**
 * Puts the caret at a Markdown position and scrolls that line to the top
 * of the source textarea.
 */
const revealSourceOffset = (offset: number): void => {
    const area = textarea.value;

    if (!area) {
        return;
    }

    area.focus({ preventScroll: true });
    area.setSelectionRange(offset, offset);

    const lineHeight = parseFloat(getComputedStyle(area).lineHeight) || 24;
    area.scrollTop += textareaCaretPosition(area, offset).top - area.getBoundingClientRect().top - lineHeight;
};

const jumpToHeading = (heading: OutlineHeading): void => {
    outlineSheetOpen.value = false;

    if (showsSource.value) {
        revealSourceOffset(heading.offset);
    }

    if (mode.value !== 'source') {
        scrollToHeading(heading.text, heading.occurrence);
    }
};

/**
 * The "/" command menu, open in either editor. `range` is the typed
 * "/query" (ProseMirror positions in the visual editor, string offsets in
 * source), replaced by the chosen command.
 */
type OpenSlash = { source: boolean; query: string; range: { from: number; to: number }; position: { left: number; top: number } };

const slash = ref<OpenSlash | null>(null);
const slashSelected = ref(0);
const slashCommands = computed(() => (slash.value ? filterSlashCommands(slash.value.query) : []));
let sourceSlashDismissedAt: number | null = null;

watch(
    () => slash.value?.query,
    () => (slashSelected.value = 0),
);

const onVisualSlash = (state: SlashState | null): void => {
    slash.value = state ? { source: false, ...state } : null;
};

const detectSourceSlash = (): void => {
    if (!textarea.value || textarea.value.selectionStart !== textarea.value.selectionEnd) {
        slash.value = null;
        return;
    }

    const cursor = textarea.value.selectionStart;
    const lineStart = textarea.value.value.lastIndexOf('\n', cursor - 1) + 1;
    const found = slashQuery(textarea.value.value.slice(lineStart, cursor));
    const from = found ? cursor - found.length : null;

    if (from === null || from === sourceSlashDismissedAt) {
        sourceSlashDismissedAt = from === null ? null : sourceSlashDismissedAt;
        slash.value = null;
        return;
    }

    slash.value = { source: true, query: found!.query, range: { from, to: cursor }, position: textareaCaretPosition(textarea.value, from) };
};

const closeSourceSlash = (): void => {
    if (slash.value?.source) {
        slash.value = null;
    }
};

/**
 * Link autocomplete: "[[…" or ">>…" opens a note picker in either editor
 * (composables/useLinkMenu.ts); choosing writes [[Note]] or [[Note#Heading]].
 */
const linkMenu = useLinkMenu({
    currentNoteId: () => props.note.id,
    currentHeadings: () => [...new Set(outline.value.map((heading) => heading.text))],
    insert: (open: OpenLinkMenu, link: string) => {
        const text = `[[${link}]]`;

        if (!open.source) {
            visual.value?.insertWikiLink(open.range, link);

            return;
        }

        editSource(() => ({
            from: open.range.from,
            to: open.range.to,
            insert: text,
            selectionStart: open.range.from + text.length,
            selectionEnd: open.range.from + text.length,
        }));
    },
});
let sourceLinkDismissedAt: number | null = null;

const onVisualLink = (state: LinkMenuState | null): void => {
    linkMenu.show(state ? { source: false, ...state } : null);
};

const detectSourceLink = (): void => {
    const area = textarea.value;

    if (!area || area.selectionStart !== area.selectionEnd) {
        linkMenu.show(null);
        return;
    }

    const cursor = area.selectionStart;
    const lineStart = area.value.lastIndexOf('\n', cursor - 1) + 1;
    const found = linkQuery(area.value.slice(lineStart, cursor), area.value.slice(cursor, cursor + 2));
    const from = found ? cursor - found.before : null;

    if (!found || from === null || from === sourceLinkDismissedAt) {
        sourceLinkDismissedAt = found ? sourceLinkDismissedAt : null;
        linkMenu.show(null);
        return;
    }

    linkMenu.show({
        source: true,
        trigger: found.trigger,
        query: found.query,
        range: { from, to: cursor + found.after },
        position: textareaCaretPosition(area, from),
    });
};

/**
 * Tag autocomplete: "#…" suggests the user's tags (composables/useTagMenu.ts).
 */
const tagMenu = useTagMenu({
    tags: () => props.tags ?? [],
    insert: (open: OpenTagMenu, text: string) => {
        if (!open.source) {
            visual.value?.replaceRange(open.range, text);

            return;
        }

        editSource(() => ({
            from: open.range.from,
            to: open.range.to,
            insert: text,
            selectionStart: open.range.from + text.length,
            selectionEnd: open.range.from + text.length,
        }));
    },
});
let sourceTagDismissedAt: number | null = null;

const onVisualTag = (state: TagMenuState | null): void => {
    tagMenu.show(state ? { source: false, query: state.query, range: state.range, position: state.position } : null);
};

const detectSourceTag = (): void => {
    const area = textarea.value;

    if (!area || area.selectionStart !== area.selectionEnd) {
        tagMenu.show(null);
        return;
    }

    const cursor = area.selectionStart;
    const lineStart = area.value.lastIndexOf('\n', cursor - 1) + 1;
    const found = tagQuery(area.value.slice(lineStart, cursor));
    const from = found ? cursor - found.before : null;

    if (!found || from === null || from === sourceTagDismissedAt) {
        sourceTagDismissedAt = found ? sourceTagDismissedAt : null;
        tagMenu.show(null);
        return;
    }

    tagMenu.show({ source: true, query: found.query, range: { from, to: cursor }, position: textareaCaretPosition(area, from) });
};

/**
 * Date suggestions: "@…" offers today, tomorrow, weekdays (composables/useDateMenu.ts).
 */
const dateMenu = useDateMenu({
    insert: (open: OpenDateMenu, text: string) => {
        if (!open.source) {
            visual.value?.replaceRange(open.range, text);

            return;
        }

        editSource(() => ({
            from: open.range.from,
            to: open.range.to,
            insert: text,
            selectionStart: open.range.from + text.length,
            selectionEnd: open.range.from + text.length,
        }));
    },
});
let sourceDateDismissedAt: number | null = null;

const onVisualDate = (state: DateMenuState | null): void => {
    dateMenu.show(state ? { source: false, query: state.query, range: state.range, position: state.position } : null);
};

const detectSourceDate = (): void => {
    const area = textarea.value;

    if (!area || area.selectionStart !== area.selectionEnd) {
        dateMenu.show(null);
        return;
    }

    const cursor = area.selectionStart;
    const lineStart = area.value.lastIndexOf('\n', cursor - 1) + 1;
    const found = dateQuery(area.value.slice(lineStart, cursor));
    const from = found ? cursor - found.before : null;

    if (!found || from === null || from === sourceDateDismissedAt) {
        sourceDateDismissedAt = found ? sourceDateDismissedAt : null;
        dateMenu.show(null);
        return;
    }

    dateMenu.show({ source: true, query: found.query, range: { from, to: cursor }, position: textareaCaretPosition(area, from) });
};

const detectSourceMenus = (): void => {
    detectSourceSlash();
    detectSourceLink();
    detectSourceTag();
    detectSourceDate();
};

const closeSourceMenus = (): void => {
    closeSourceSlash();

    if (linkMenu.open.value?.source) {
        linkMenu.show(null);
    }

    if (tagMenu.open.value?.source) {
        tagMenu.show(null);
    }

    if (dateMenu.open.value?.source) {
        dateMenu.show(null);
    }
};

/**
 * Arrow keys pick, Enter/Tab run, Escape closes (until the "/" is retyped).
 */
const handleSlashKey = (key: string): boolean => {
    if (!slash.value) {
        return false;
    }

    const count = slashCommands.value.length;

    if (key === 'Escape') {
        if (slash.value.source) {
            sourceSlashDismissedAt = slash.value.range.from;
        }
        slash.value = null;
    } else if (key === 'ArrowDown' && count) {
        slashSelected.value = (slashSelected.value + 1) % count;
    } else if (key === 'ArrowUp' && count) {
        slashSelected.value = (slashSelected.value - 1 + count) % count;
    } else if ((key === 'Enter' || key === 'Tab') && count) {
        chooseSlash(slashCommands.value[slashSelected.value]);
    } else {
        return false;
    }

    return true;
};

const chooseSlash = (command: SlashCommand): void => {
    const open = slash.value;
    slash.value = null;

    if (!open) {
        return;
    }

    if (!open.source) {
        visual.value?.runSlash(command, open.range);
        return;
    }

    editSource(({ start }) => ({
        from: open.range.from,
        to: open.range.to,
        insert: '',
        selectionStart: start - (open.range.to - open.range.from),
        selectionEnd: start - (open.range.to - open.range.from),
    }));

    const insertText =
        (text: string, cursorBack = 0) =>
        ({ start, end }: Selection): TextEdit => ({
            from: start,
            to: end,
            insert: text,
            selectionStart: start + text.length - cursorBack,
            selectionEnd: start + text.length - cursorBack,
        });
    const { action } = command;

    switch (action.type) {
        case 'style':
            editSource((selection) => applyParagraphStyle(action.style, selection));
            break;
        case 'format':
            editSource((selection) => formatMarkdown(action.command, selection));
            break;
        case 'table':
            editSource(insertTable);
            break;
        case 'divider':
            editSource(insertText('---\n'));
            break;
        case 'tableOfContents':
            editSource(insertText('[TOC]\n'));
            break;
        case 'callout':
            editSource(insertText('> [!note] '));
            break;
        case 'wikiLink':
            editSource(insertText('[[]]', 2));
            break;
        case 'date':
            editSource(insertText(todayIso()));
            break;
    }
};

/**
 * Inserts plain text (an emoji from the picker) at the cursor of whichever editor is showing.
 */
const insertAtCursor = (text: string): void => {
    if (togglingTask.value) {
        return;
    }

    if (mode.value === 'edit') {
        visual.value?.insertText(text);
    } else {
        editSource(({ start, end }) => ({
            from: start,
            to: end,
            insert: text,
            selectionStart: start + text.length,
            selectionEnd: start + text.length,
        }));
    }
};

const finder = useFindReplace({
    visualActive: () => mode.value === 'edit',
    visual: () => visual.value?.find,
    textarea,
    body,
    editSource: (build) => editSource(build),
});

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
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'f' && mode.value !== 'preview') {
        event.preventDefault();
        finder.show(event.altKey);
        return;
    }

    if (linkMenu.open.value?.source && event.target === textarea.value && ['ArrowUp', 'ArrowDown', 'Enter', 'Tab', 'Escape'].includes(event.key)) {
        if (event.key === 'Escape') {
            sourceLinkDismissedAt = linkMenu.open.value.range.from;
        }

        if (linkMenu.handleKey(event.key)) {
            event.preventDefault();
        }

        return;
    }

    if (dateMenu.open.value?.source && event.target === textarea.value && ['ArrowUp', 'ArrowDown', 'Enter', 'Tab', 'Escape'].includes(event.key)) {
        if (event.key === 'Escape') {
            sourceDateDismissedAt = dateMenu.open.value.range.from;
        }

        if (dateMenu.handleKey(event.key)) {
            event.preventDefault();

            return;
        }
    }

    if (tagMenu.open.value?.source && event.target === textarea.value && ['ArrowUp', 'ArrowDown', 'Enter', 'Tab', 'Escape'].includes(event.key)) {
        if (event.key === 'Escape') {
            sourceTagDismissedAt = tagMenu.open.value.range.from;
        }

        if (tagMenu.handleKey(event.key)) {
            event.preventDefault();

            return;
        }
    }

    if (slash.value?.source && event.target === textarea.value && ['ArrowUp', 'ArrowDown', 'Enter', 'Tab', 'Escape'].includes(event.key)) {
        if (handleSlashKey(event.key)) {
            event.preventDefault();
        }

        return;
    }

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

const formatCommands: { command: FormatCommand; label: string; icon: string; kbds?: string[] }[] = [
    { command: 'bold', label: 'Bold', icon: 'i-lucide-bold', kbds: shortcutKeys('bold') },
    { command: 'italic', label: 'Italic', icon: 'i-lucide-italic', kbds: shortcutKeys('italic') },
    { command: 'underline', label: 'Underline', icon: 'i-lucide-underline', kbds: shortcutKeys('underline') },
    { command: 'strike', label: 'Strikethrough', icon: 'i-lucide-strikethrough', kbds: shortcutKeys('strike') },
    { command: 'code', label: 'Code', icon: 'i-lucide-code' },
    { command: 'link', label: 'Link', icon: 'i-lucide-link', kbds: shortcutKeys('link') },
    { command: 'list', label: 'Bulleted list', icon: 'i-lucide-list' },
    { command: 'numbered', label: 'Numbered list', icon: 'i-lucide-list-ordered' },
    { command: 'checklist', label: 'Checklist', icon: 'i-lucide-list-checks', kbds: shortcutKeys('checklist') },
    { command: 'quote', label: 'Block quote', icon: 'i-lucide-text-quote' },
];

const styleCommands: { style: ParagraphStyle; label: string; kbds?: string[] }[] = [
    { style: 'title', label: 'Title', kbds: shortcutKeys('title') },
    { style: 'heading', label: 'Heading', kbds: shortcutKeys('heading') },
    { style: 'subheading', label: 'Subheading', kbds: shortcutKeys('subheading') },
    { style: 'body', label: 'Body', kbds: shortcutKeys('body') },
    { style: 'monostyled', label: 'Monostyled' },
];

const styleShortcuts: [ShortcutId, ParagraphStyle][] = [
    ['title', 'title'],
    ['heading', 'heading'],
    ['subheading', 'subheading'],
    ['body', 'body'],
    ['heading-apple', 'heading'],
    ['subheading-apple', 'subheading'],
];

/**
 * The checklist and paragraph-style shortcuts (lib/shortcuts.ts), caught
 * on the way down so the visual editor never sees them: both modes then
 * behave alike, and Tiptap's own Mod-Alt-1… doesn't toggle a second time.
 */
const onShortcutKeydown = (event: KeyboardEvent): void => {
    if (mode.value === 'preview' || event.isComposing) {
        return;
    }

    const style = styleShortcuts.find(([id]) => matchesShortcut(event, id));

    if (!style && !matchesShortcut(event, 'checklist')) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (style) {
        setStyle(style[1]);
    } else {
        format('checklist');
    }
};

/**
 * This editor's actions for the command palette (O7.2): modes always,
 * editing commands only when the note can be edited in the current mode.
 */
const paletteCommands = computed<PaletteCommand[]>(() => {
    const outlineCommand: PaletteCommand = {
        id: 'outline',
        label: showsOutline.value ? 'Hide outline' : 'Show outline',
        icon: 'i-lucide-table-of-contents',
        run: toggleOutline,
    };

    if (props.readonly) {
        return [outlineCommand];
    }

    const modes: PaletteCommand[] = modeItems.value
        .filter((item) => item.value !== mode.value)
        .map((item) => ({
            id: `mode.${item.value}`,
            label: `Switch to ${item.label.toLowerCase()}`,
            icon: item.icon,
            run: () => void setMode(item.value as EditorMode),
        }));

    if (mode.value === 'preview') {
        return [...modes, outlineCommand];
    }

    return [
        ...modes,
        outlineCommand,
        { id: 'find', label: 'Find in note', icon: 'i-lucide-search', kbds: shortcutKeys('find'), run: () => finder.show(false) },
        { id: 'replace', label: 'Find and replace', icon: 'i-lucide-replace', kbds: shortcutKeys('replace'), run: () => finder.show(true) },
        ...formatCommands.map(({ command, label, icon, kbds }) => ({ id: `format.${command}`, label, icon, kbds, run: () => format(command) })),
        ...styleCommands.map(({ style, label, kbds }) => ({
            id: `style.${style}`,
            label: `Style: ${label}`,
            icon: 'i-lucide-heading',
            kbds,
            run: () => setStyle(style),
        })),
        { id: 'highlight', label: 'Highlight', icon: 'i-lucide-highlighter', run: () => highlight(null) },
        { id: 'table', label: 'Insert table', icon: 'i-lucide-table', run: () => tableAction('insert') },
        ...(mode.value === 'edit' && visualInTable.value
            ? [
                  { id: 'table.row', label: 'Table: add row below', icon: 'i-lucide-table', run: () => tableAction('row-below') },
                  { id: 'table.column', label: 'Table: add column right', icon: 'i-lucide-table', run: () => tableAction('column-right') },
                  { id: 'table.delete', label: 'Table: delete table', icon: 'i-lucide-table', run: () => tableAction('delete-table') },
              ]
            : []),
        ...(mode.value === 'source' || mode.value === 'split'
            ? [{ id: 'table.format', label: 'Table: line up columns', icon: 'i-lucide-table', run: () => tableAction('format') }]
            : []),
        ...(canRewriteVisually.value
            ? [
                  { id: 'checklist.check-all', label: 'Checklist: check all', icon: 'i-lucide-list-checks', run: () => checklistAction('check-all') },
                  { id: 'checklist.uncheck-all', label: 'Checklist: uncheck all', icon: 'i-lucide-list', run: () => checklistAction('uncheck-all') },
                  {
                      id: 'checklist.checked-to-bottom',
                      label: 'Checklist: move checked items to bottom',
                      icon: 'i-lucide-arrow-down-to-line',
                      run: () => checklistAction('checked-to-bottom'),
                  },
                  {
                      id: 'checklist.delete-checked',
                      label: 'Checklist: delete checked items',
                      icon: 'i-lucide-trash-2',
                      run: () => checklistAction('delete-checked'),
                  },
              ]
            : []),
        { id: 'property', label: 'Add property', icon: 'i-lucide-list-plus', run: () => propertiesPanel.value?.startAdding() },
    ];
});

defineExpose({ paletteCommands });
</script>

<template>
    <div ref="root" class="flex h-full flex-col gap-2">
        <div class="flex items-center gap-1">
            <NoteTitle :title="savedTitle" :readonly="readonly" @rename="renameNote" />
            <UiTooltip text="Outline">
                <UiButton
                    icon="i-lucide-table-of-contents"
                    aria-label="Outline"
                    :aria-pressed="showsOutline"
                    color="neutral"
                    :variant="showsOutline ? 'soft' : 'ghost'"
                    size="xs"
                    @click="toggleOutline"
                />
            </UiTooltip>
            <UiTooltip v-if="!readonly" text="Add property">
                <UiButton
                    icon="i-lucide-list-plus"
                    aria-label="Add property"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    @click="propertiesPanel?.startAdding()"
                />
            </UiTooltip>
        </div>
        <PropertiesPanel ref="propertiesPanel" :markdown="body" :readonly="readonly" @update="(markdown) => (body = markdown)" />
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
            @insert="insertAtCursor"
        />
        <FindBar
            v-if="finder.open.value && mode !== 'preview'"
            v-model:query="finder.query.value"
            v-model:replacement="finder.replacement.value"
            v-model:case-sensitive="finder.caseSensitive.value"
            v-model:regex="finder.regex.value"
            v-model:show-replace="finder.showReplace.value"
            :count="finder.count.value"
            :current="finder.current.value"
            :error="finder.error.value"
            @next="finder.step(1)"
            @previous="finder.step(-1)"
            @replace="finder.replace"
            @replace-all="finder.replaceAll"
            @close="finder.close"
        />
        <div v-if="relink.offer.value && !readonly" class="bg-elevated flex items-center gap-2 rounded-md px-3 py-2 text-xs" role="status">
            <UiIcon name="i-lucide-link" class="text-dimmed size-4 shrink-0" />
            <span class="text-muted flex-1">
                {{ relink.offer.value.count }} {{ relink.offer.value.count === 1 ? 'note links' : 'notes link' }} to “{{ relink.offer.value.from }}”.
                Point {{ relink.offer.value.count === 1 ? 'it' : 'them' }} at “{{ savedTitle }}”?
            </span>
            <UiButton size="xs" :loading="relink.working.value" @click="relink.relink">Update links</UiButton>
            <UiButton size="xs" color="neutral" variant="ghost" @click="relink.keep">Keep</UiButton>
        </div>
        <div
            v-if="!faithful && mode === 'source' && preferredMode === 'edit'"
            class="bg-elevated flex items-center gap-2 rounded-md px-3 py-2 text-xs"
        >
            <UiIcon name="i-lucide-info" class="text-dimmed size-4 shrink-0" />
            <span class="text-muted flex-1">This note uses Markdown the visual editor can't keep exactly, so it opened as source.</span>
            <UiButton size="xs" color="neutral" variant="link" @click="visualAnyway = true">Edit visually anyway</UiButton>
        </div>
        <div
            class="flex min-h-0 flex-1 gap-4"
            :class="{ 'readable-width': readableWidth !== false && mode !== 'split' }"
            @keydown.capture="onShortcutKeydown"
            @keydown="onKeydown"
            @mouseover="linkPreview.onOver"
            @mouseout="linkPreview.onOut"
            @mousedown="linkPreview.close"
            @scroll.capture="linkPreview.close"
            @keydown.passive="linkPreview.close"
        >
            <VisualEditor
                v-if="!readonly"
                v-show="mode === 'edit'"
                ref="visual"
                :active="mode === 'edit'"
                :first-line-style="firstLineStyle"
                :checked-to-bottom="checkedToBottom"
                :spellcheck="spellcheck"
                :text-direction="textDirection"
                v-model="body"
                @faithful="(value) => (faithful = value)"
                @style="(value) => (visualStyle = value)"
                @in-table="(value) => (visualInTable = value)"
                @slash="onVisualSlash"
                @link="onVisualLink"
                :on-link-key="linkMenu.handleKey"
                @tag="onVisualTag"
                :on-tag-key="tagMenu.handleKey"
                @date="onVisualDate"
                :on-date-key="dateMenu.handleKey"
                @find="(state) => (finder.visualState.value = state)"
                :on-slash-key="handleSlashKey"
                @open-wiki-link="openWikiLink"
                @open-tag="openTag"
            />
            <SlashMenu v-if="slash" :commands="slashCommands" :selected="slashSelected" :position="slash.position" @choose="chooseSlash" />
            <DateMenu
                v-if="dateMenu.open.value"
                :choices="dateMenu.choices.value"
                :selected="dateMenu.selected.value"
                :position="dateMenu.open.value.position"
                @choose="dateMenu.choose"
            />
            <TagMenu
                v-if="tagMenu.open.value"
                :choices="tagMenu.choices.value"
                :selected="tagMenu.selected.value"
                :position="tagMenu.open.value.position"
                @choose="tagMenu.choose"
            />
            <LinkMenu
                v-if="linkMenu.open.value"
                :choices="linkMenu.choices.value"
                :selected="linkMenu.selected.value"
                :position="linkMenu.open.value.position"
                @choose="linkMenu.choose"
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
                :spellcheck="spellcheck ?? true"
                :dir="textDirection ?? 'auto'"
                @input="detectSourceMenus"
                @click="detectSourceMenus"
                @blur="closeSourceMenus"
            />
            <!-- The HTML is rendered on the server with raw HTML escaped and unsafe links removed (NoteMarkdown). -->
            <!-- eslint-disable-next-line vue/no-v-html -->
            <article
                v-if="mode === 'split' || mode === 'preview'"
                class="note-preview h-full min-w-0 flex-1 overflow-y-auto"
                :class="{ 'border-default border-l pl-4': mode === 'split' }"
                ref="preview"
                :dir="textDirection ?? 'auto'"
                :aria-label="mode === 'preview' ? 'Reading view' : 'Preview'"
                @click="onPreviewClick"
                v-html="html"
            />
            <aside v-if="isLarge && outlinePinned" class="border-default w-52 shrink-0 overflow-y-auto border-s ps-3">
                <OutlinePanel :headings="outline" @jump="jumpToHeading" />
            </aside>
        </div>
        <!-- Outside the editing area, so pressing on the card doesn't close it before its link is followed. -->
        <LinkPreviewCard v-if="linkPreview.card.value" :preview="linkPreview.card.value" @enter="linkPreview.keep" @leave="linkPreview.hide" />
        <UiSlideover v-if="!isLarge" v-model:open="outlineSheetOpen" title="Outline" description="Jump to a heading in this note.">
            <template #body>
                <OutlinePanel :headings="outline" @jump="jumpToHeading" />
            </template>
        </UiSlideover>
        <ul v-if="tags.length" class="flex flex-wrap gap-1" aria-label="Tags">
            <li v-for="tag in tags" :key="tag">
                <UiBadge color="neutral" variant="soft" size="sm" icon="i-lucide-hash">{{ tag }}</UiBadge>
            </li>
        </ul>
        <details v-if="outgoing.length" class="border-default border-t pt-2 text-sm">
            <summary class="text-muted cursor-pointer text-xs">Links to {{ outgoing.length }} note{{ outgoing.length === 1 ? '' : 's' }}</summary>
            <ul class="mt-1 flex flex-col gap-1">
                <li v-for="link in outgoing" :key="link.title.toLowerCase()">
                    <Link
                        v-if="link.note"
                        :href="route('notes.show', { note: link.note.id })"
                        preserve-state
                        class="hover:bg-elevated flex items-center justify-between gap-2 rounded px-2 py-1"
                    >
                        <span class="truncate">{{ link.note.title || 'Untitled note' }}</span>
                        <span v-if="link.title.toLowerCase() !== link.note.title.toLowerCase()" class="text-dimmed shrink-0 truncate text-xs"
                            >as “{{ link.title }}”</span
                        >
                    </Link>
                    <button
                        v-else
                        type="button"
                        class="hover:bg-elevated text-muted flex w-full items-center justify-between gap-2 rounded px-2 py-1 text-start"
                        :title="`Create “${link.title}”`"
                        @click="emit('createLinked', link.title)"
                    >
                        <span class="truncate underline decoration-dashed underline-offset-2">{{ link.title }}</span>
                        <span class="text-dimmed shrink-0 text-xs">No note yet</span>
                    </button>
                </li>
            </ul>
        </details>
        <details v-if="backlinks.length" class="border-default border-t pt-2 text-sm">
            <summary class="text-muted cursor-pointer text-xs">
                Linked from {{ backlinks.length }} note{{ backlinks.length === 1 ? '' : 's' }}
            </summary>
            <ul class="mt-1 flex flex-col gap-1">
                <li v-for="backlink in backlinks" :key="backlink.id">
                    <Link
                        :href="route('notes.show', { note: backlink.id })"
                        preserve-state
                        class="hover:bg-elevated flex flex-col gap-0.5 rounded px-2 py-1"
                    >
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate">{{ backlink.title || 'Untitled note' }}</span>
                            <span class="text-dimmed shrink-0 text-xs">{{ backlink.updated_at_for_humans }}</span>
                        </span>
                        <span
                            v-for="(line, index) in backlink.context"
                            :key="index"
                            class="text-muted border-default line-clamp-2 border-s-2 ps-2 text-xs"
                        >
                            <HighlightedText :parts="line" />
                        </span>
                    </Link>
                </li>
            </ul>
        </details>
        <UnlinkedMentions v-if="!readonly" ref="unlinkedMentions" :note-id="note.id" />
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
            <NoteInfo :body="body" :created-at="note.created_at" :updated-at="updatedAt" />
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
