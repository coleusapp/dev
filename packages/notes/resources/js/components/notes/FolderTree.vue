<script setup lang="ts">
import { NOTE_DRAG_TYPE, type FolderNode } from '@/components/notes/note';
import { Link } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { ref } from 'vue';

const props = defineProps<{
    folders: FolderNode[];
    selected: number | null;
    parentId?: number | null;
    depth?: number;
}>();

const emit = defineEmits<{
    action: [type: 'create' | 'rename' | 'move' | 'delete', folder: FolderNode, parentId: number | null];
    dropNote: [noteId: number, folderId: number];
}>();

const containsSelected = (folder: FolderNode): boolean =>
    props.selected !== null && folder.children.some((child) => child.id === props.selected || containsSelected(child));

/**
 * Branches start open when they contain the selected folder.
 */
const expanded = ref<Record<number, boolean>>(Object.fromEntries(props.folders.map((folder) => [folder.id, containsSelected(folder)])));

const dropTarget = ref<number | null>(null);

const menu = (folder: FolderNode): DropdownMenuItem[][] => {
    const parent = props.parentId ?? null;

    return [
        [
            { label: 'New folder inside', icon: 'i-lucide-folder-plus', onSelect: () => emit('action', 'create', folder, parent) },
            { label: 'Rename', icon: 'i-lucide-pencil', onSelect: () => emit('action', 'rename', folder, parent) },
            { label: 'Move to…', icon: 'i-lucide-folder-input', onSelect: () => emit('action', 'move', folder, parent) },
        ],
        [{ label: 'Delete folder', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => emit('action', 'delete', folder, parent) }],
    ];
};

const onDragOver = (event: DragEvent, folder: FolderNode): void => {
    if (event.dataTransfer?.types.includes(NOTE_DRAG_TYPE)) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        dropTarget.value = folder.id;
    }
};

const onDrop = (event: DragEvent, folder: FolderNode): void => {
    const noteId = Number(event.dataTransfer?.getData(NOTE_DRAG_TYPE));
    dropTarget.value = null;

    if (noteId) {
        event.preventDefault();
        emit('dropNote', noteId, folder.id);
    }
};
</script>

<template>
    <ul class="flex flex-col" :role="depth ? 'group' : 'tree'" :aria-label="depth ? undefined : 'Folders'">
        <li v-for="folder in folders" :key="folder.id" role="treeitem" :aria-expanded="folder.children.length ? !!expanded[folder.id] : undefined">
            <div
                class="group hover:bg-elevated flex items-center gap-1 rounded-md pe-1 text-sm"
                :class="{ 'bg-elevated font-medium': selected === folder.id, 'ring-primary ring-2': dropTarget === folder.id }"
                :style="{ paddingInlineStart: `${(depth ?? 0) * 12}px` }"
                @dragover="onDragOver($event, folder)"
                @dragleave="dropTarget = null"
                @drop="onDrop($event, folder)"
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
                <UiDropdownMenu :items="menu(folder)">
                    <UiButton
                        icon="i-lucide-ellipsis"
                        :aria-label="`Folder actions for ${folder.name}`"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        class="opacity-0 group-hover:opacity-100 focus-visible:opacity-100"
                    />
                </UiDropdownMenu>
            </div>
            <FolderTree
                v-if="folder.children.length && expanded[folder.id]"
                :folders="folder.children"
                :selected="selected"
                :parent-id="folder.id"
                :depth="(depth ?? 0) + 1"
                @action="(type, child, parentId) => emit('action', type, child, parentId)"
                @drop-note="(noteId, folderId) => emit('dropNote', noteId, folderId)"
            />
        </li>
    </ul>
</template>
