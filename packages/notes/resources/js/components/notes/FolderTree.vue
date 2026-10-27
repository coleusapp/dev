<script setup lang="ts">
import type { FolderNode } from '@/components/notes/note';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    folders: FolderNode[];
    selected: number | null;
    depth?: number;
}>();

const containsSelected = (folder: FolderNode): boolean =>
    props.selected !== null && folder.children.some((child) => child.id === props.selected || containsSelected(child));

/**
 * Branches start open when they contain the selected folder.
 */
const expanded = ref<Record<number, boolean>>(Object.fromEntries(props.folders.map((folder) => [folder.id, containsSelected(folder)])));
</script>

<template>
    <ul class="flex flex-col" :role="depth ? 'group' : 'tree'" :aria-label="depth ? undefined : 'Folders'">
        <li v-for="folder in folders" :key="folder.id" role="treeitem" :aria-expanded="folder.children.length ? !!expanded[folder.id] : undefined">
            <div
                class="hover:bg-elevated flex items-center gap-1 rounded-md pe-2 text-sm"
                :class="{ 'bg-elevated font-medium': selected === folder.id }"
                :style="{ paddingInlineStart: `${(depth ?? 0) * 12}px` }"
            >
                <UiButton
                    v-if="folder.children.length"
                    :icon="expanded[folder.id] ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                    :aria-label="expanded[folder.id] ? `Collapse ${folder.name}` : `Expand ${folder.name}`"
                    color="neutral"
                    variant="link"
                    size="xs"
                    @click="expanded[folder.id] = !expanded[folder.id]"
                />
                <span v-else class="w-6 shrink-0" />
                <Link :href="route('notes.index', { folder: folder.id })" preserve-scroll class="flex min-w-0 flex-1 items-center gap-1 py-1.5">
                    <UiIcon :name="selected === folder.id ? 'i-lucide-folder-open' : 'i-lucide-folder'" class="text-dimmed size-3.5 shrink-0" />
                    <span class="truncate">{{ folder.name }}</span>
                    <span class="text-dimmed ms-auto text-xs">{{ folder.count }}</span>
                </Link>
            </div>
            <FolderTree
                v-if="folder.children.length && expanded[folder.id]"
                :folders="folder.children"
                :selected="selected"
                :depth="(depth ?? 0) + 1"
            />
        </li>
    </ul>
</template>
