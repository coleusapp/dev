<script setup lang="ts">
import type { FormatCommand, ParagraphStyle } from '@/lib/markdownFormat';
import type { DropdownMenuItem } from '@nuxt/ui';
import { computed } from 'vue';

const props = defineProps<{
    activeStyle?: ParagraphStyle | null;
}>();

const emit = defineEmits<{
    format: [command: FormatCommand];
    style: [style: ParagraphStyle];
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
    </div>
</template>
