import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { TextSelection, type EditorState, type Transaction } from '@tiptap/pm/state';
import { Extension } from '@tiptap/vue-3';

type Block = { position: number; node: ProseMirrorNode };

/**
 * The "line" the cursor is on in the visual editor: the list or checklist
 * item it's in, otherwise the top-level block (paragraph, heading, table…).
 */
const currentBlock = (state: EditorState): Block | null => {
    const { $from } = state.selection;

    for (let depth = $from.depth; depth > 0; depth--) {
        const node = $from.node(depth);

        if (depth === 1 || node.type.name === 'listItem' || node.type.name === 'taskItem') {
            return { position: $from.before(depth), node };
        }
    }

    return null;
};

/**
 * Keeps the selection at the same place inside a block that moved from `from` to `to`.
 */
const followBlock = (state: EditorState, tr: Transaction, from: number, to: number): Transaction => {
    const { selection } = state;

    return tr.setSelection(TextSelection.create(tr.doc, selection.from - from + to, selection.to - from + to));
};

/**
 * Alt+Up / Alt+Down: swaps the current line (block or list item) with its
 * previous or next sibling. Null when there's nowhere to go.
 */
export const moveBlockTransaction = (state: EditorState, direction: 'up' | 'down'): Transaction | null => {
    const block = currentBlock(state);

    if (!block) {
        return null;
    }

    const $block = state.doc.resolve(block.position);
    const index = $block.index();
    const parent = $block.parent;
    const end = block.position + block.node.nodeSize;

    if (direction === 'up') {
        if (index === 0) {
            return null;
        }

        const target = block.position - parent.child(index - 1).nodeSize;
        const tr = state.tr.delete(block.position, end).insert(target, block.node);

        return followBlock(state, tr, block.position, target);
    }

    if (index >= parent.childCount - 1) {
        return null;
    }

    const below = parent.child(index + 1);
    const tr = state.tr.insert(end + below.nodeSize, block.node).delete(block.position, end);

    return followBlock(state, tr, block.position, block.position + below.nodeSize);
};

/**
 * Shift+Alt+Down: puts a copy of the current line below it and moves the cursor into the copy.
 */
export const duplicateBlockTransaction = (state: EditorState): Transaction | null => {
    const block = currentBlock(state);

    if (!block) {
        return null;
    }

    const end = block.position + block.node.nodeSize;

    return followBlock(state, state.tr.insert(end, block.node), block.position, end);
};

/**
 * Cmd/Ctrl+/: wraps the selection (or the current paragraph's text) in
 * %%…%%, a comment hidden from the rendered note, or unwraps it.
 */
export const toggleCommentTransaction = (state: EditorState): Transaction => {
    const { $from, $to, empty } = state.selection;
    const from = empty ? $from.start() : state.selection.from;
    const to = empty ? $from.end() : state.selection.to;
    const text = state.doc.textBetween(from, to, '\n', '￼');
    const before = state.doc.textBetween(Math.max($from.start(), from - 2), from);
    const after = state.doc.textBetween(to, Math.min($to.end(), to + 2));

    if (text.startsWith('%%') && text.endsWith('%%') && text.length >= 4) {
        return state.tr.delete(to - 2, to).delete(from, from + 2);
    }

    if (before === '%%' && after === '%%') {
        return state.tr.delete(to, to + 2).delete(from - 2, from);
    }

    return state.tr.insertText('%%', to).insertText('%%', from);
};

/**
 * Editor shortcuts for working with lines, matching Markdown source mode.
 */
export const LineCommands = Extension.create({
    name: 'lineCommands',

    addKeyboardShortcuts() {
        const run = (build: (state: EditorState) => Transaction | null): boolean => {
            const tr = build(this.editor.state);

            if (tr) {
                this.editor.view.dispatch(tr.scrollIntoView());
            }

            return tr !== null;
        };

        return {
            'Alt-ArrowUp': () => run((state) => moveBlockTransaction(state, 'up')),
            'Alt-ArrowDown': () => run((state) => moveBlockTransaction(state, 'down')),
            'Shift-Alt-ArrowDown': () => run(duplicateBlockTransaction),
            'Mod-/': () => run(toggleCommentTransaction),
        };
    },
});
