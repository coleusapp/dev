<script setup lang="ts">
import type { PaletteCommand, PaletteGroup } from '@/components/notes/commands';
import type { CommandPaletteGroup, CommandPaletteItem } from '@nuxt/ui';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    groups: PaletteGroup[];
}>();

const open = defineModel<boolean>('open', { required: true });

const searchTerm = ref('');

/**
 * The chosen command runs once the palette has closed and focus is back
 * where it was, so editor commands act on the editor's own selection.
 */
let pending: PaletteCommand | null = null;

watch(open, (isOpen) => {
    if (isOpen) {
        searchTerm.value = '';
        pending = null;
    }
});

const paletteGroups = computed<CommandPaletteGroup<CommandPaletteItem>[]>(() =>
    props.groups
        .filter((group) => group.commands.length)
        .map((group) => ({
            id: group.id,
            label: group.label,
            items: group.commands.map((command) => ({
                id: command.id,
                label: command.label,
                icon: command.icon,
                kbds: command.kbds,
                onSelect: () => {
                    pending = command;
                    open.value = false;
                },
            })),
        })),
);

const runPending = (): void => {
    const command = pending;
    pending = null;
    command?.run();
};
</script>

<template>
    <UiModal
        v-model:open="open"
        title="Commands"
        description="Run any action by name."
        :ui="{ header: 'sr-only', body: 'p-0 sm:p-0' }"
        @after:leave="runPending"
    >
        <template #body>
            <UiCommandPalette
                v-model:search-term="searchTerm"
                :groups="paletteGroups"
                placeholder="Type a command…"
                :fuse="{ resultLimit: 100 }"
                class="h-96"
                close
                @update:open="open = $event"
            />
        </template>
    </UiModal>
</template>
