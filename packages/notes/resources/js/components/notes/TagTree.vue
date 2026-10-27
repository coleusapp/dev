<script setup lang="ts">
import type { TagNode } from '@/components/notes/note';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    tags: TagNode[];
    selected: string | null;
    search: string | null;
    depth?: number;
}>();

/**
 * Branches start open when they contain the selected tag.
 */
const isOnSelectedPath = (tag: TagNode): boolean => props.selected !== null && props.selected.startsWith(`${tag.path}/`);

const expanded = ref<Record<string, boolean>>(Object.fromEntries(props.tags.map((tag) => [tag.path, isOnSelectedPath(tag)])));

const toggle = (tag: TagNode): void => {
    expanded.value[tag.path] = !expanded.value[tag.path];
};
</script>

<template>
    <ul class="flex flex-col" :role="depth ? 'group' : 'tree'" :aria-label="depth ? undefined : 'Tags'">
        <li v-for="tag in tags" :key="tag.path" role="treeitem" :aria-expanded="tag.children.length ? !!expanded[tag.path] : undefined">
            <div
                class="hover:bg-elevated flex items-center gap-1 rounded-md pr-2 text-sm"
                :class="{ 'bg-elevated font-medium': selected === tag.path }"
                :style="{ paddingLeft: `${(depth ?? 0) * 12}px` }"
            >
                <UiButton
                    v-if="tag.children.length"
                    :icon="expanded[tag.path] ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                    :aria-label="expanded[tag.path] ? `Collapse ${tag.name}` : `Expand ${tag.name}`"
                    color="neutral"
                    variant="link"
                    size="xs"
                    @click="toggle(tag)"
                />
                <span v-else class="w-6 shrink-0" />
                <Link
                    :href="route('notes.index', { tag: tag.path, ...(search ? { q: search } : {}) })"
                    preserve-scroll
                    class="flex min-w-0 flex-1 items-center gap-1 py-1.5"
                >
                    <UiIcon name="i-lucide-hash" class="text-dimmed size-3.5 shrink-0" />
                    <span class="truncate">{{ tag.name }}</span>
                    <span class="text-dimmed ml-auto text-xs">{{ tag.count }}</span>
                </Link>
            </div>
            <TagTree
                v-if="tag.children.length && expanded[tag.path]"
                :tags="tag.children"
                :selected="selected"
                :search="search"
                :depth="(depth ?? 0) + 1"
            />
        </li>
    </ul>
</template>
