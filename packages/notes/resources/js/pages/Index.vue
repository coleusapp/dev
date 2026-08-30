<script setup lang="ts">
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import Highlight from '@tiptap/extension-highlight';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, ref } from 'vue';

const editor = useEditor({
    extensions: [StarterKit, Underline, Highlight.configure({ multicolor: true }), Placeholder.configure({ placeholder: 'Start writing...' })],
    autofocus: true,
    editorProps: {
        attributes: {
            class: 'tiptap h-full focus:outline-none',
        },
    },
});

const groupByDate = ref(false);

const menuItems = computed<DropdownMenuItem[][]>(() => [
    [
        {
            label: 'Sort By',
            icon: 'i-lucide-arrow-up-down',
            children: [
                { label: 'Name', icon: 'i-lucide-case-sensitive' },
                { label: 'Date created', icon: 'i-lucide-calendar-plus' },
                { label: 'Date modified', icon: 'i-lucide-calendar-clock' },
            ],
        },
        {
            label: 'Group by Date',
            icon: 'i-lucide-calendar-days',
            type: 'checkbox',
            checked: groupByDate.value,
            onUpdateChecked: (checked: boolean) => {
                groupByDate.value = checked;
            },
        },
    ],
    [
        {
            label: 'Export',
            icon: 'i-lucide-download',
        },
    ],
]);

type TextStyleKey = 'title' | 'heading' | 'subheading' | 'body' | 'monostyled' | 'bulletList' | 'dashedList' | 'orderedList' | 'blockQuote';

const textStyleConfig: Record<TextStyleKey, { label: string; ui?: DropdownMenuItem['ui'] }> = {
    title: { label: 'Title', ui: { itemLabel: 'text-lg font-bold' } },
    heading: { label: 'Heading', ui: { itemLabel: 'font-bold' } },
    subheading: { label: 'Subheading', ui: { itemLabel: 'font-semibold' } },
    body: { label: 'Body' },
    monostyled: { label: 'Monostyled', ui: { itemLabel: 'font-mono' } },
    bulletList: { label: '•  Bulleted List' },
    dashedList: { label: '–  Dashed List' },
    orderedList: { label: '1. Numbered List' },
    blockQuote: { label: 'Block Quote' },
};

const isTextStyleActive = (style: TextStyleKey): boolean => {
    if (!editor.value) {
        return false;
    }

    switch (style) {
        case 'title':
            return editor.value.isActive('heading', { level: 1 });
        case 'heading':
            return editor.value.isActive('heading', { level: 2 });
        case 'subheading':
            return editor.value.isActive('heading', { level: 3 });
        case 'body':
            return editor.value.isActive('paragraph') && !editor.value.isActive('bulletList') && !editor.value.isActive('orderedList');
        case 'monostyled':
            return editor.value.isActive('codeBlock');
        case 'bulletList':
        case 'dashedList':
            return editor.value.isActive('bulletList');
        case 'orderedList':
            return editor.value.isActive('orderedList');
        case 'blockQuote':
            return editor.value.isActive('blockquote');
    }
};

const applyTextStyle = (style: TextStyleKey): void => {
    if (!editor.value) {
        return;
    }

    const chain = editor.value.chain().focus();

    switch (style) {
        case 'title':
            chain.setHeading({ level: 1 }).run();
            break;
        case 'heading':
            chain.setHeading({ level: 2 }).run();
            break;
        case 'subheading':
            chain.setHeading({ level: 3 }).run();
            break;
        case 'body':
            chain.setParagraph().run();
            break;
        case 'monostyled':
            chain.toggleCodeBlock().run();
            break;
        case 'bulletList':
        case 'dashedList':
            chain.toggleBulletList().run();
            break;
        case 'orderedList':
            chain.toggleOrderedList().run();
            break;
        case 'blockQuote':
            chain.toggleBlockquote().run();
            break;
    }
};

const textStyleItem = (style: TextStyleKey): DropdownMenuItem => {
    const { label, ui } = textStyleConfig[style];

    return {
        label,
        type: 'checkbox',
        checked: isTextStyleActive(style),
        onUpdateChecked: () => applyTextStyle(style),
        ui,
    };
};

const textStyleItems = computed<DropdownMenuItem[][]>(() => [
    [
        textStyleItem('title'),
        textStyleItem('heading'),
        textStyleItem('subheading'),
        textStyleItem('body'),
        textStyleItem('monostyled'),
        textStyleItem('bulletList'),
        textStyleItem('dashedList'),
        textStyleItem('orderedList'),
    ],
    [textStyleItem('blockQuote')],
]);

const colorOptions = [
    { label: 'Red', value: 'red', hex: '#ef4444', swatchClass: 'bg-red-500', textClass: 'text-red-500' },
    { label: 'Orange', value: 'orange', hex: '#f97316', swatchClass: 'bg-orange-500', textClass: 'text-orange-500' },
    { label: 'Yellow', value: 'yellow', hex: '#eab308', swatchClass: 'bg-yellow-500', textClass: 'text-yellow-500' },
    { label: 'Green', value: 'green', hex: '#22c55e', swatchClass: 'bg-green-500', textClass: 'text-green-500' },
    { label: 'Blue', value: 'blue', hex: '#3b82f6', swatchClass: 'bg-blue-500', textClass: 'text-blue-500' },
    { label: 'Purple', value: 'purple', hex: '#a855f7', swatchClass: 'bg-purple-500', textClass: 'text-purple-500' },
] as const;

const selectedColor = ref<(typeof colorOptions)[number]['value']>('red');

const selectedColorHex = computed(() => colorOptions.find((option) => option.value === selectedColor.value)?.hex ?? colorOptions[0].hex);

const selectedColorSwatchClass = computed(() => colorOptions.find((option) => option.value === selectedColor.value)?.swatchClass);

const toggleHighlight = (): void => {
    editor.value?.chain().focus().toggleHighlight({ color: selectedColorHex.value }).run();
};

const colorItems = computed<DropdownMenuItem[]>(() =>
    colorOptions.map((option) => ({
        label: option.label,
        type: 'checkbox',
        checked: selectedColor.value === option.value,
        onUpdateChecked: () => {
            selectedColor.value = option.value;

            if (editor.value?.isActive('highlight')) {
                editor.value.chain().focus().setHighlight({ color: option.hex }).run();
            }
        },
        swatchClass: option.swatchClass,
        ui: { itemLabel: option.textClass },
    })),
);

const noteMenuItems = computed<DropdownMenuItem[][]>(() => [
    [
        { label: 'Duplicate', icon: 'i-lucide-copy' },
        { label: 'Lock note', icon: 'i-lucide-lock' },
    ],
    [{ label: 'Move to trash', icon: 'i-lucide-trash-2', color: 'error' }],
]);
</script>

<template>
    <Head title="Notes" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-1" :default-size="18" :min-size="14" :max-size="24" resizable>
                <template #header>
                    <UiDashboardNavbar>
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" />
                        </template>
                        <template #title>
                            <div class="flex flex-col">
                                <span>Notes</span>
                                <small class="text-dimmed text-xs font-normal">0 notes</small>
                            </div>
                        </template>
                        <template #right>
                            <UiDropdownMenu :items="menuItems">
                                <UiButton icon="i-lucide-ellipsis" color="neutral" variant="ghost" />
                            </UiDropdownMenu>
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <div class="text-dimmed flex h-full items-center justify-center p-4 text-center text-sm">No notes yet.</div>
                </template>
            </UiDashboardPanel>

            <UiDashboardPanel id="notes-2" class="hidden lg:flex">
                <template #header>
                    <UiDashboardNavbar :toggle="false">
                        <template #leading>
                            <UiButton icon="i-lucide-file-plus" color="neutral" variant="subtle">New note</UiButton>
                        </template>

                        <div class="flex items-center justify-center gap-2">
                            <UiDropdownMenu :items="textStyleItems">
                                <template #content-top>
                                    <div class="border-default flex items-center gap-1 border-b px-2 py-2">
                                        <UiButton
                                            icon="i-lucide-bold"
                                            :color="editor?.isActive('bold') ? 'primary' : 'neutral'"
                                            variant="ghost"
                                            size="sm"
                                            @click="editor?.chain().focus().toggleBold().run()"
                                        />
                                        <UiButton
                                            icon="i-lucide-italic"
                                            :color="editor?.isActive('italic') ? 'primary' : 'neutral'"
                                            variant="ghost"
                                            size="sm"
                                            @click="editor?.chain().focus().toggleItalic().run()"
                                        />
                                        <UiButton
                                            icon="i-lucide-underline"
                                            :color="editor?.isActive('underline') ? 'primary' : 'neutral'"
                                            variant="ghost"
                                            size="sm"
                                            @click="editor?.chain().focus().toggleUnderline().run()"
                                        />
                                        <UiButton
                                            icon="i-lucide-strikethrough"
                                            :color="editor?.isActive('strike') ? 'primary' : 'neutral'"
                                            variant="ghost"
                                            size="sm"
                                            @click="editor?.chain().focus().toggleStrike().run()"
                                        />
                                        <UiButton
                                            icon="i-lucide-highlighter"
                                            :color="editor?.isActive('highlight') ? 'primary' : 'neutral'"
                                            variant="ghost"
                                            size="sm"
                                            @click="toggleHighlight()"
                                        />
                                        <UiDropdownMenu :items="colorItems">
                                            <template #item-leading="{ item }">
                                                <span class="size-2.5 rounded-full" :class="item.swatchClass" />
                                            </template>
                                            <UiButton square color="neutral" variant="ghost" size="sm">
                                                <span class="size-2.5 rounded-full" :class="selectedColorSwatchClass" />
                                            </UiButton>
                                        </UiDropdownMenu>
                                    </div>
                                </template>
                                <UiButton icon="i-lucide-type" color="neutral" variant="ghost" />
                            </UiDropdownMenu>
                            <UiButton icon="i-lucide-list-checks" color="neutral" variant="ghost" />
                            <UiButton icon="i-lucide-table" color="neutral" variant="ghost" />
                            <UiButton icon="i-lucide-paperclip" color="neutral" variant="ghost" />
                        </div>

                        <template #right>
                            <UiButton label="Share" icon="i-lucide-share-2" color="neutral" variant="subtle" />
                            <UiDropdownMenu :items="noteMenuItems">
                                <UiButton icon="i-lucide-ellipsis" color="neutral" variant="ghost" />
                            </UiDropdownMenu>
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <EditorContent :editor="editor" class="h-full w-full flex-1 overflow-y-auto p-4" />
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
