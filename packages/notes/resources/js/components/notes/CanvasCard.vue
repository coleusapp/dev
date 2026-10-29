<script setup lang="ts">
import { linkHost, type CanvasCards, type CanvasNode } from '@/lib/canvas';
import { jsonHeaders } from '@/lib/http';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    node: CanvasNode;
    cards: CanvasCards;
    editing: boolean;
}>();

const emit = defineEmits<{
    text: [text: string];
    done: [];
}>();

/**
 * What a canvas card shows inside its frame (O10.1): text (a textarea
 * while editing), a note's title and snippet, an attachment (images in
 * full), or a link with its preview. Groups draw only their frame.
 */
const textarea = ref<HTMLTextAreaElement | null>(null);

watch(
    () => props.editing,
    async (editing) => {
        if (editing) {
            await nextTick();
            textarea.value?.focus();
        }
    },
);

const note = computed(() => (props.node.type === 'file' && props.node.note ? (props.cards.notes[props.node.note] ?? null) : null));
const attachment = computed(() =>
    props.node.type === 'file' && props.node.attachment ? (props.cards.attachments[props.node.attachment] ?? null) : null,
);

type Preview = { title: string | null; description: string | null; image: string | null; site_name: string | null };
const preview = ref<Preview | null>(null);

onMounted(async () => {
    if (props.node.type !== 'link') {
        return;
    }

    try {
        const response = await fetch(route('notes.link-preview', { url: props.node.url }), { headers: jsonHeaders(), credentials: 'same-origin' });
        preview.value = response.ok ? ((await response.json())?.data ?? null) : null;
    } catch {
        preview.value = null;
    }
});
</script>

<template>
    <div v-if="node.type === 'text'" class="h-full w-full overflow-hidden">
        <textarea
            v-if="editing"
            ref="textarea"
            :value="node.text"
            class="h-full w-full resize-none bg-transparent p-3 text-sm outline-none"
            aria-label="Card text"
            @input="emit('text', ($event.target as HTMLTextAreaElement).value)"
            @blur="emit('done')"
            @keydown.esc.prevent="emit('done')"
            @pointerdown.stop
        />
        <p v-else-if="node.text" class="h-full overflow-hidden p-3 text-sm break-words whitespace-pre-wrap">{{ node.text }}</p>
        <p v-else class="text-dimmed p-3 text-sm">Double-click to write</p>
    </div>

    <div v-else-if="node.type === 'file' && node.note" class="flex h-full flex-col overflow-hidden">
        <template v-if="note">
            <p class="border-default flex items-center gap-1 truncate border-b px-3 py-1.5 text-sm font-semibold">
                <UiIcon :name="note.locked ? 'i-lucide-lock' : 'i-lucide-file-text'" class="text-dimmed size-3.5 shrink-0" />
                {{ note.title || 'Untitled note' }}<span v-if="node.subpath" class="text-dimmed font-normal">{{ node.subpath }}</span>
            </p>
            <p class="text-muted flex-1 overflow-hidden px-3 py-2 text-xs">{{ note.locked ? 'This note is locked.' : note.snippet }}</p>
        </template>
        <p v-else class="text-dimmed p-3 text-sm">Missing note{{ node.file ? `: ${node.file}` : '' }}</p>
    </div>

    <div v-else-if="node.type === 'file'" class="h-full overflow-hidden">
        <img
            v-if="attachment?.kind === 'image'"
            :src="`${attachment.url}?w=640`"
            :alt="attachment.name"
            class="h-full w-full object-contain"
            draggable="false"
            loading="lazy"
        />
        <p v-else-if="attachment" class="flex items-center gap-2 p-3 text-sm">
            <UiIcon name="i-lucide-paperclip" class="text-dimmed size-4 shrink-0" />
            <span class="truncate">{{ attachment.name }}</span>
        </p>
        <p v-else class="text-dimmed p-3 text-sm">Missing file{{ node.file ? `: ${node.file}` : '' }}</p>
    </div>

    <div v-else-if="node.type === 'link'" class="flex h-full flex-col overflow-hidden">
        <img v-if="preview?.image" :src="preview.image" alt="" class="h-1/2 w-full object-cover" draggable="false" referrerpolicy="no-referrer" />
        <div class="flex min-h-0 flex-1 flex-col gap-0.5 p-3">
            <p class="text-dimmed flex items-center gap-1 truncate text-xs">
                <UiIcon name="i-lucide-link" class="size-3 shrink-0" />{{ preview?.site_name || linkHost(node.url) }}
            </p>
            <p class="truncate text-sm font-medium">{{ preview?.title || node.url }}</p>
            <p v-if="preview?.description" class="text-muted line-clamp-2 text-xs">{{ preview.description }}</p>
        </div>
    </div>
</template>
