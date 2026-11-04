import { TaskItem, TaskList } from '@tiptap/extension-list';
import Placeholder from '@tiptap/extension-placeholder';
import { Markdown } from '@tiptap/markdown';
import StarterKit from '@tiptap/starter-kit';
import type { AnyExtension } from '@tiptap/vue-3';

/**
 * The editor: Tiptap's starter kit and checklists, read from and written
 * back as Markdown.
 */
export const editorExtensions = (placeholder = 'Start writing...'): AnyExtension[] => [
    StarterKit.configure({ link: { openOnClick: false, autolink: false } }),
    TaskList,
    TaskItem.configure({ nested: true }),
    Placeholder.configure({ placeholder }),
    Markdown,
];
