import type { Editor } from '@tiptap/vue-3';

/**
 * One editor toolbar button: what it shows, what it does, and when it
 * shows as on (the cursor is in that block or the selection has that mark).
 */
export type ToolbarAction = {
    id: string;
    label: string;
    icon: string;
    run: (editor: Editor) => void;
    isActive: (editor: Editor) => boolean;
};

const heading = (level: 1 | 2 | 3): ToolbarAction => ({
    id: `heading-${level}`,
    label: `Heading ${level}`,
    icon: `i-lucide-heading-${level}`,
    run: (editor) => editor.chain().focus().toggleHeading({ level }).run(),
    isActive: (editor) => editor.isActive('heading', { level }),
});

const mark = (name: 'bold' | 'italic' | 'strike' | 'code', label: string, icon: string): ToolbarAction => ({
    id: name,
    label,
    icon,
    run: (editor) => {
        const chain = editor.chain().focus();
        ({ bold: () => chain.toggleBold(), italic: () => chain.toggleItalic(), strike: () => chain.toggleStrike(), code: () => chain.toggleCode() })
            [name]()
            .run();
    },
    isActive: (editor) => editor.isActive(name),
});

/**
 * The toolbar, in groups (a separator between each).
 */
export const TOOLBAR_GROUPS: ToolbarAction[][] = [
    [heading(1), heading(2), heading(3)],
    [
        mark('bold', 'Bold', 'i-lucide-bold'),
        mark('italic', 'Italic', 'i-lucide-italic'),
        mark('strike', 'Strikethrough', 'i-lucide-strikethrough'),
        mark('code', 'Code', 'i-lucide-code'),
    ],
    [
        {
            id: 'bullet-list',
            label: 'Bulleted list',
            icon: 'i-lucide-list',
            run: (editor) => editor.chain().focus().toggleBulletList().run(),
            isActive: (editor) => editor.isActive('bulletList'),
        },
        {
            id: 'ordered-list',
            label: 'Numbered list',
            icon: 'i-lucide-list-ordered',
            run: (editor) => editor.chain().focus().toggleOrderedList().run(),
            isActive: (editor) => editor.isActive('orderedList'),
        },
        {
            id: 'task-list',
            label: 'Checklist',
            icon: 'i-lucide-list-checks',
            run: (editor) => editor.chain().focus().toggleTaskList().run(),
            isActive: (editor) => editor.isActive('taskList'),
        },
    ],
    [
        {
            id: 'blockquote',
            label: 'Quote',
            icon: 'i-lucide-text-quote',
            run: (editor) => editor.chain().focus().toggleBlockquote().run(),
            isActive: (editor) => editor.isActive('blockquote'),
        },
        {
            id: 'code-block',
            label: 'Code block',
            icon: 'i-lucide-square-code',
            run: (editor) => editor.chain().focus().toggleCodeBlock().run(),
            isActive: (editor) => editor.isActive('codeBlock'),
        },
    ],
];
