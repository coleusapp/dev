<script setup lang="ts">
import { jsonHeaders } from '@/lib/http';
import type { CommandPaletteGroup, CommandPaletteItem } from '@nuxt/ui';
import { computed, ref, watch } from 'vue';

type PickableNote = { id: number; title: string; aliases: string[]; archived: boolean };

/**
 * Chooses another note by title (e.g. the note to merge into, O9.5), from
 * the same list the quick switcher uses.
 */
const props = defineProps<{
    title: string;
    description: string;
    excludeId?: number | null;
}>();

const emit = defineEmits<{
    choose: [id: number];
}>();

const open = defineModel<boolean>('open', { required: true });
const notes = ref<PickableNote[] | null>(null);

watch(open, async (isOpen) => {
    if (isOpen) {
        notes.value = null;
        const response = await fetch(route('notes.switcher'), { headers: jsonHeaders(), credentials: 'same-origin' });
        notes.value = response.ok ? ((await response.json())?.data ?? []) : [];
    }
});

const groups = computed<CommandPaletteGroup<CommandPaletteItem>[]>(() => [
    {
        id: 'notes',
        items: (notes.value ?? [])
            .filter((note) => note.id !== props.excludeId)
            .map((note) => ({
                id: String(note.id),
                label: note.title || 'Untitled note',
                suffix: note.aliases.join(', ') || undefined,
                icon: 'i-lucide-file-text',
                onSelect: () => {
                    open.value = false;
                    emit('choose', note.id);
                },
            })),
    },
]);
</script>

<template>
    <UiModal v-model:open="open" :title="title" :description="description">
        <template #body>
            <UiCommandPalette :groups="groups" :loading="notes === null" placeholder="Find a note…" class="h-80" />
        </template>
    </UiModal>
</template>
