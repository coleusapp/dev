<script setup lang="ts">
import { bookmarkHref, type BookmarkGroup, type BookmarkItem } from '@/components/notes/note';
import { Link, router } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';

defineProps<{
    groups: BookmarkGroup[];
}>();

const emit = defineEmits<{
    edit: [bookmark: BookmarkItem];
}>();

const icons = { note: 'i-lucide-file-text', heading: 'i-lucide-heading', search: 'i-lucide-search' } as const;

const visitOptions = { preserveScroll: true, preserveState: true };

const menu = (bookmark: BookmarkItem): DropdownMenuItem[][] => [
    [
        { label: 'Rename or group…', icon: 'i-lucide-pencil', onSelect: () => emit('edit', bookmark) },
        {
            label: 'Move up',
            icon: 'i-lucide-arrow-up',
            onSelect: () => router.post(route('notes.bookmarks.move', { bookmark: bookmark.id }), { step: -1 }, visitOptions),
        },
        {
            label: 'Move down',
            icon: 'i-lucide-arrow-down',
            onSelect: () => router.post(route('notes.bookmarks.move', { bookmark: bookmark.id }), { step: 1 }, visitOptions),
        },
    ],
    [
        {
            label: 'Remove bookmark',
            icon: 'i-lucide-bookmark-x',
            color: 'error',
            onSelect: () => router.delete(route('notes.bookmarks.destroy', { bookmark: bookmark.id }), visitOptions),
        },
    ],
];
</script>

<template>
    <div class="flex flex-col gap-1" role="list" aria-label="Bookmarks">
        <section v-for="group in groups" :key="group.group ?? ''" class="flex flex-col">
            <h3 v-if="group.group" class="text-dimmed px-2 pt-1 text-xs">{{ group.group }}</h3>
            <div
                v-for="bookmark in group.bookmarks"
                :key="bookmark.id"
                class="group hover:bg-elevated flex items-center gap-1 rounded-md pe-1 text-sm"
                role="listitem"
            >
                <Link
                    :href="bookmarkHref(bookmark)"
                    :preserve-state="bookmark.type !== 'search'"
                    class="flex min-w-0 flex-1 items-center gap-2 px-2 py-1.5"
                >
                    <UiIcon :name="icons[bookmark.type]" class="text-dimmed size-3.5 shrink-0" />
                    <span class="truncate">{{ bookmark.title }}</span>
                </Link>
                <UiDropdownMenu :items="menu(bookmark)">
                    <UiButton
                        icon="i-lucide-ellipsis"
                        :aria-label="`Bookmark actions for ${bookmark.title}`"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        class="opacity-0 group-hover:opacity-100 focus-visible:opacity-100"
                    />
                </UiDropdownMenu>
            </div>
        </section>
    </div>
</template>
