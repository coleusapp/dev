<script setup lang="ts">
import type { NoteData } from '@/components/notes/note';
import { Link } from '@inertiajs/vue3';

defineProps<{
    notes: NoteData[];
    selectedId: number | null;
}>();

defineEmits<{
    create: [];
}>();

const firstLine = (body: string): string => body.split('\n').find((line) => line.trim() !== '') ?? '';
</script>

<template>
    <div v-if="notes.length === 0" class="flex h-full flex-col items-center justify-center gap-3 p-4 text-center">
        <UiIcon name="i-lucide-notebook-pen" class="text-dimmed size-8" />
        <p class="text-dimmed text-sm">No notes yet.</p>
        <UiButton icon="i-lucide-file-plus" color="neutral" variant="subtle" size="sm" @click="$emit('create')">Create your first note</UiButton>
    </div>
    <nav v-else class="divide-default flex flex-col divide-y">
        <Link
            v-for="note in notes"
            :key="note.id"
            :href="route('notes.show', { note: note.id })"
            preserve-scroll
            class="hover:bg-elevated flex flex-col gap-1 px-3 py-3"
            :class="{ 'bg-elevated': note.id === selectedId }"
        >
            <span class="truncate text-sm font-semibold" :class="{ 'text-dimmed': !firstLine(note.body) }">{{
                firstLine(note.body) || 'Empty note'
            }}</span>
            <span v-if="note.snippet" class="text-muted line-clamp-2 text-xs">{{ note.snippet }}</span>
            <time class="text-dimmed text-xs" :datetime="note.updated_at">{{ note.updated_at_for_humans }}</time>
        </Link>
    </nav>
</template>
