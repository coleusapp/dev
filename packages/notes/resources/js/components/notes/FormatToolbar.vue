<script setup lang="ts">
import EmojiPicker from '@/components/notes/EmojiPicker.vue';
import type { ChecklistAction, TableAction } from '@/components/notes/note';
import { HIGHLIGHT_COLORS, type HighlightColor } from '@/editor/Highlight';
import type { FormatCommand, ParagraphStyle } from '@/lib/markdownFormat';
import type { DropdownMenuItem } from '@nuxt/ui';
import { computed, ref } from 'vue';

const props = defineProps<{
    activeStyle?: ParagraphStyle | null;
    inTable?: boolean;
    source?: boolean;
}>();

const emit = defineEmits<{
    format: [command: FormatCommand];
    style: [style: ParagraphStyle];
    highlight: [color: HighlightColor | null];
    checklistAction: [action: ChecklistAction];
    tableAction: [action: TableAction];
    insert: [text: string];
    attach: [];
}>();

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);
const mod = isMac ? '⌘' : 'Ctrl+';

const buttons: { command: FormatCommand; icon: string; label: string }[] = [
    { command: 'bold', icon: 'i-lucide-bold', label: `Bold (${mod}B)` },
    { command: 'italic', icon: 'i-lucide-italic', label: `Italic (${mod}I)` },
    { command: 'underline', icon: 'i-lucide-underline', label: `Underline (${mod}U)` },
    { command: 'strike', icon: 'i-lucide-strikethrough', label: `Strikethrough (${mod}${isMac ? '⇧' : 'Shift+'}X)` },
    { command: 'heading', icon: 'i-lucide-heading', label: 'Heading' },
    { command: 'list', icon: 'i-lucide-list', label: 'Bulleted list' },
    { command: 'numbered', icon: 'i-lucide-list-ordered', label: 'Numbered list' },
    { command: 'checklist', icon: 'i-lucide-list-checks', label: 'Checklist' },
    { command: 'quote', icon: 'i-lucide-text-quote', label: 'Block quote' },
    { command: 'code', icon: 'i-lucide-code', label: 'Code' },
    { command: 'link', icon: 'i-lucide-link', label: `Link (${mod}K)` },
];

const styles: { style: ParagraphStyle; label: string; itemLabel?: string }[] = [
    { style: 'title', label: 'Title', itemLabel: 'text-lg font-bold' },
    { style: 'heading', label: 'Heading', itemLabel: 'font-bold' },
    { style: 'subheading', label: 'Subheading', itemLabel: 'font-semibold' },
    { style: 'body', label: 'Body' },
    { style: 'monostyled', label: 'Monostyled', itemLabel: 'font-mono' },
];

/**
 * The colour the highlighter button applies; picking one from the menu
 * also applies it, like Apple Notes. null is the default yellow.
 */
const highlightColor = ref<HighlightColor | null>(null);

const swatchClass = (color: HighlightColor | null): string => (color ? `mark-${color}` : 'mark-default');

/**
 * Act on the checklist the cursor is in, or on every checklist in the note.
 */
const checklistItems: DropdownMenuItem[][] = [
    [
        { label: 'Check all', icon: 'i-lucide-list-checks', onSelect: () => emit('checklistAction', 'check-all') },
        { label: 'Uncheck all', icon: 'i-lucide-list', onSelect: () => emit('checklistAction', 'uncheck-all') },
        {
            label: 'Move checked items to bottom',
            icon: 'i-lucide-arrow-down-wide-narrow',
            onSelect: () => emit('checklistAction', 'checked-to-bottom'),
        },
    ],
    [{ label: 'Delete checked items', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => emit('checklistAction', 'delete-checked') }],
];

/**
 * Insert is always there. In the visual editor the row/column commands
 * appear while the cursor is in a table; in Markdown source "Line up
 * columns" pads the table at the cursor.
 */
const tableItems = computed<DropdownMenuItem[][]>(() => {
    const action = (label: string, icon: string, value: TableAction, extra: Partial<DropdownMenuItem> = {}): DropdownMenuItem => ({
        label,
        icon,
        onSelect: () => emit('tableAction', value),
        ...extra,
    });
    const insert = [action('Insert table', 'i-lucide-table', 'insert')];

    if (props.source) {
        return [insert, [action('Line up columns', 'i-lucide-align-justify', 'format')]];
    }

    if (!props.inTable) {
        return [insert];
    }

    return [
        insert,
        [
            action('Add row above', 'i-lucide-arrow-up-to-line', 'row-above'),
            action('Add row below', 'i-lucide-arrow-down-to-line', 'row-below'),
            action('Add column left', 'i-lucide-arrow-left-to-line', 'column-left'),
            action('Add column right', 'i-lucide-arrow-right-to-line', 'column-right'),
            action('Header row on/off', 'i-lucide-heading', 'toggle-header'),
        ],
        [
            action('Delete row', 'i-lucide-minus', 'delete-row', { color: 'error' }),
            action('Delete column', 'i-lucide-minus', 'delete-column', { color: 'error' }),
            action('Delete table', 'i-lucide-trash-2', 'delete-table', { color: 'error' }),
        ],
    ];
});

const highlightItems = computed<DropdownMenuItem[]>(() =>
    [null, ...HIGHLIGHT_COLORS].map((color) => ({
        label: color ? color[0].toUpperCase() + color.slice(1) : 'Yellow',
        type: 'checkbox',
        checked: highlightColor.value === color,
        swatch: swatchClass(color),
        onUpdateChecked: () => {
            highlightColor.value = color;
            emit('highlight', color);
        },
    })),
);

const styleItems = computed<DropdownMenuItem[]>(() =>
    styles.map(({ style, label, itemLabel }) => ({
        label,
        type: 'checkbox',
        checked: props.activeStyle === style,
        onUpdateChecked: () => emit('style', style),
        ui: itemLabel ? { itemLabel } : undefined,
    })),
);
</script>

<template>
    <div class="flex flex-wrap items-center gap-1" role="toolbar" aria-label="Formatting">
        <UiDropdownMenu :items="styleItems" :content="{ align: 'start' }">
            <UiTooltip text="Paragraph style">
                <UiButton
                    icon="i-lucide-type"
                    trailing-icon="i-lucide-chevron-down"
                    aria-label="Paragraph style"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    @mousedown.prevent
                />
            </UiTooltip>
        </UiDropdownMenu>
        <UiTooltip v-for="button in buttons" :key="button.command" :text="button.label">
            <UiButton
                :icon="button.icon"
                :aria-label="button.label"
                color="neutral"
                variant="ghost"
                size="xs"
                @mousedown.prevent
                @click="$emit('format', button.command)"
            />
        </UiTooltip>
        <UiDropdownMenu :items="checklistItems" :content="{ align: 'start' }">
            <UiTooltip text="Checklist actions">
                <UiButton
                    icon="i-lucide-list-todo"
                    trailing-icon="i-lucide-chevron-down"
                    aria-label="Checklist actions"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    @mousedown.prevent
                />
            </UiTooltip>
        </UiDropdownMenu>
        <UiDropdownMenu :items="tableItems" :content="{ align: 'start' }">
            <UiTooltip text="Table">
                <UiButton
                    icon="i-lucide-table"
                    trailing-icon="i-lucide-chevron-down"
                    aria-label="Table"
                    :color="inTable ? 'primary' : 'neutral'"
                    variant="ghost"
                    size="xs"
                    @mousedown.prevent
                />
            </UiTooltip>
        </UiDropdownMenu>
        <EmojiPicker @pick="(emoji) => emit('insert', emoji)" />
        <UiTooltip text="Attach files">
            <UiButton
                icon="i-lucide-paperclip"
                aria-label="Attach files"
                color="neutral"
                variant="ghost"
                size="xs"
                @mousedown.prevent
                @click="emit('attach')"
            />
        </UiTooltip>
        <UiTooltip text="Highlight">
            <UiButton
                icon="i-lucide-highlighter"
                aria-label="Highlight"
                color="neutral"
                variant="ghost"
                size="xs"
                @mousedown.prevent
                @click="$emit('highlight', highlightColor)"
            />
        </UiTooltip>
        <UiDropdownMenu :items="highlightItems" :content="{ align: 'start' }">
            <UiButton square color="neutral" variant="ghost" size="xs" aria-label="Highlight colour" @mousedown.prevent>
                <span class="size-2.5 rounded-full" :class="swatchClass(highlightColor)" />
            </UiButton>
            <template #item-leading="{ item }">
                <span class="size-2.5 rounded-full" :class="(item as { swatch?: string }).swatch" />
            </template>
        </UiDropdownMenu>
    </div>
</template>
