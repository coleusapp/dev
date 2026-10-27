<script setup lang="ts">
import type { FormatCommand } from '@/lib/markdownFormat';

defineEmits<{
    format: [command: FormatCommand];
}>();

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);
const mod = isMac ? '⌘' : 'Ctrl+';

const buttons: { command: FormatCommand; icon: string; label: string }[] = [
    { command: 'bold', icon: 'i-lucide-bold', label: `Bold (${mod}B)` },
    { command: 'italic', icon: 'i-lucide-italic', label: `Italic (${mod}I)` },
    { command: 'heading', icon: 'i-lucide-heading', label: 'Heading' },
    { command: 'list', icon: 'i-lucide-list', label: 'Bulleted list' },
    { command: 'checklist', icon: 'i-lucide-list-checks', label: 'Checklist' },
    { command: 'code', icon: 'i-lucide-code', label: 'Code' },
    { command: 'link', icon: 'i-lucide-link', label: `Link (${mod}K)` },
];
</script>

<template>
    <div class="flex flex-wrap items-center gap-1" role="toolbar" aria-label="Formatting">
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
