<script setup lang="ts">
import type { NoteData } from '@/components/notes/note';
import { Link } from '@inertiajs/vue3';

defineProps<{
    notes: NoteData[];
    selectedId: number | null;
    tag: string | null;
}>();

defineEmits<{
    create: [];
}>();
</script>

<template>
    <div v-if="notes.length === 0" class="flex h-full flex-col items-center justify-center gap-3 p-4 text-center">
        <UiIcon name="i-lucide-notebook-pen" class="text-dimmed size-8" />
        <p class="text-dimmed text-sm">{{ tag ? `No notes tagged #${tag}.` : 'No notes yet.' }}</p>
        <UiButton icon="i-lucide-file-plus" color="neutral" variant="subtle" size="sm" @click="$emit('create')">{{
            tag ? 'New note with this tag' : 'Create your first note'
        }}</UiButton>
    </div>
    <nav v-else class="divide-default flex flex-col divide-y">
        <Link
            v-for="note in notes"
            :key="note.id"
            :href="route('notes.show', { note: note.id, ...(tag ? { tag } : {}) })"
            preserve-scroll
            class="hover:bg-elevated flex flex-col gap-1 px-3 py-3"
            :class="{ 'bg-elevated': note.id === selectedId }"
        >
            <span class="flex items-center gap-1">
                <span class="truncate text-sm font-semibold" :class="{ 'text-dimmed': !note.title }">{{ note.title || 'Empty note' }}</span>
                <UiIcon v-if="note.pinned" name="i-lucide-pin" class="text-primary size-3 shrink-0" aria-label="Pinned" />
            </span>
            <span v-if="note.snippet" class="text-muted line-clamp-2 text-xs">{{ note.snippet }}</span>
            <time class="text-dimmed text-xs" :datetime="note.updated_at">{{ note.updated_at_for_humans }}</time>
        </Link>
    </nav>
</template>
