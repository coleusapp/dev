<script setup lang="ts">
import type { NoteData } from '@/components/notes/note';
import { Link } from '@inertiajs/vue3';

defineProps<{
    notes: NoteData[];
    selectedId: number | null;
}>();

const firstLine = (body: string): string => body.split('\n').find((line) => line.trim() !== '') ?? '';
</script>

<template>
    <div v-if="notes.length === 0" class="text-dimmed flex h-full items-center justify-center p-4 text-center text-sm">No notes yet.</div>
    <nav v-else class="flex flex-col gap-1">
        <Link
            v-for="note in notes"
            :key="note.id"
            :href="route('notes.show', { note: note.id })"
            preserve-scroll
            class="hover:bg-elevated rounded-md px-3 py-2"
            :class="{ 'bg-elevated': note.id === selectedId }"
        >
            <span class="block truncate text-sm font-medium">{{ firstLine(note.body) || 'New note' }}</span>
            <span class="text-dimmed text-xs">{{ note.updated_at_for_humans }}</span>
        </Link>
    </nav>
</template>
