import type { Editor } from '@tiptap/vue-3';

/**
 * Where the first line goes: over the whole document when it's empty, over
 * the first block when that's an empty paragraph (blank lines at the top
 * parse into one), otherwise in front of everything.
 */
const firstBlockRange = (editor: Editor, empty: boolean): { from: number; to: number } | number => {
    if (empty) {
        return { from: 0, to: editor.state.doc.content.size };
    }

    const first = editor.state.doc.firstChild;

    return first && first.isTextblock && first.content.size === 0 ? { from: 0, to: first.nodeSize } : 0;
};

/**
 * Gives a new note an empty title line (a level-1 heading) to type its
 * title into. Applies when the note is empty or its first line is blank.
 * Not saved by itself: the change doesn't fire an update, so nothing is
 * written until the user types.
 */
export const prepareFirstLine = (editor: Editor, markdownBody: string): void => {
    const empty = markdownBody.trim() === '';

    if (!empty && !/^[ \t]*\r?\n/.test(markdownBody)) {
        return;
    }

    editor
        .chain()
        .command(({ tr }) => {
            tr.setMeta('preventUpdate', true);
            return true;
        })
        .insertContentAt(firstBlockRange(editor, empty), { type: 'heading', attrs: { level: 1 } })
        .setTextSelection(1)
        .run();
};
