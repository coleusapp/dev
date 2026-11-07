import type { Editor } from '@tiptap/vue-3';

const firstBlockRange = (editor: Editor, empty: boolean): { from: number; to: number } | number => {
    if (empty) {
        return { from: 0, to: editor.state.doc.content.size };
    }

    const first = editor.state.doc.firstChild;

    return first && first.isTextblock && first.content.size === 0 ? { from: 0, to: first.nodeSize } : 0;
};

export const prepareFirstLine = (editor: Editor): void => {
    const empty = editor.isEmpty;
    const first = editor.state.doc.firstChild;

    if (!empty && !(first?.isTextblock && first.content.size === 0)) {
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
