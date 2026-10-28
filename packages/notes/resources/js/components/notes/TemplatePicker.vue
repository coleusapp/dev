<script setup lang="ts">
import { jsonHeaders } from '@/lib/http';
import type { CommandPaletteGroup, CommandPaletteItem } from '@nuxt/ui';
import { computed, ref, watch } from 'vue';

export type NoteTemplate = { id: number; title: string; body: string };

/**
 * Picks a template (O9.3): the notes in the "Templates" folder, searched by title.
 */
const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{
    choose: [template: NoteTemplate];
}>();

const templates = ref<NoteTemplate[] | null>(null);

watch(open, async (isOpen) => {
    if (isOpen) {
        templates.value = null;
        const response = await fetch(route('notes.templates.index'), { headers: jsonHeaders(), credentials: 'same-origin' });
        templates.value = response.ok ? ((await response.json())?.data ?? []) : [];
    }
});

const groups = computed<CommandPaletteGroup<CommandPaletteItem>[]>(() => [
    {
        id: 'templates',
        items: (templates.value ?? []).map((template) => ({
            id: String(template.id),
            label: template.title || 'Untitled template',
            icon: 'i-lucide-file-text',
            onSelect: () => {
                open.value = false;
                emit('choose', template);
            },
        })),
    },
]);
</script>

<template>
    <UiModal
        v-model:open="open"
        title="Insert template"
        description="Notes in your Templates folder."
        :ui="{ header: 'sr-only', body: 'p-0 sm:p-0' }"
    >
        <template #body>
            <p v-if="templates !== null && !templates.length" class="text-dimmed p-4 text-sm">
                No templates yet. Put notes in a folder named “Templates”; <code>&#123;&#123;title&#125;&#125;</code>,
                <code>&#123;&#123;date&#125;&#125;</code> and <code>&#123;&#123;time&#125;&#125;</code> in them are filled in.
            </p>
            <UiCommandPalette v-else :groups="groups" :loading="templates === null" placeholder="Find a template…" class="h-80" />
        </template>
    </UiModal>
</template>
