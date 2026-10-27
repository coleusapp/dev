import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import type { EditorState, Transaction } from '@tiptap/pm/state';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Extension } from '@tiptap/vue-3';

/**
 * Reorders every checklist so its unticked items come first and ticked ones
 * last, keeping the relative order within each group (and each item's
 * sub-items with it). Returns null when nothing would move.
 */
export const checkedToBottomTransaction = (state: EditorState): Transaction | null => {
    const tr = state.tr;
    let moved = false;

    state.doc.descendants((node, position) => {
        if (node.type.name !== 'taskList') {
            return true;
        }

        const items: ProseMirrorNode[] = [];
        node.forEach((child) => items.push(child));
        const sorted = [...items.filter((item) => !item.attrs.checked), ...items.filter((item) => item.attrs.checked)];

        if (sorted.some((item, index) => item !== items[index])) {
            const sortedList = node.type.create(node.attrs, sorted, node.marks);
            tr.replaceWith(tr.mapping.map(position), tr.mapping.map(position + node.nodeSize), sortedList);
            moved = true;
        }

        return true;
    });

    return moved ? tr : null;
};

/**
 * The checklist the cursor is in (the outermost one, so sub-items count),
 * as [position, node], or null when the cursor isn't in a checklist.
 */
const checklistAtCursor = (state: EditorState): [number, ProseMirrorNode] | null => {
    const { $from } = state.selection;

    for (let depth = 1; depth <= $from.depth; depth++) {
        const node = $from.node(depth);

        if (node.type.name === 'taskList') {
            return [$from.before(depth), node];
        }
    }

    return null;
};

/**
 * Every task item in scope: the checklist at the cursor, or the whole note.
 */
const taskItemsInScope = (state: EditorState): { position: number; node: ProseMirrorNode }[] => {
    const scope = checklistAtCursor(state);
    const items: { position: number; node: ProseMirrorNode }[] = [];
    const collect = (node: ProseMirrorNode, offset: number): void => {
        node.descendants((child, position) => {
            if (child.type.name === 'taskItem') {
                items.push({ position: offset + position, node: child });
            }
        });
    };

    if (scope) {
        collect(scope[1], scope[0] + 1);
    } else {
        collect(state.doc, 0);
    }

    return items;
};

/**
 * Ticks or unticks every item of the checklist at the cursor (or of every
 * checklist when the cursor isn't in one). Null when nothing changes.
 */
export const setAllCheckedTransaction = (state: EditorState, checked: boolean): Transaction | null => {
    const tr = state.tr;
    let changed = false;

    for (const { position, node } of taskItemsInScope(state)) {
        if (node.attrs.checked !== checked) {
            tr.setNodeAttribute(position, 'checked', checked);
            changed = true;
        }
    }

    return changed ? tr : null;
};

/**
 * Deletes the ticked items (with their sub-items) of the checklist at the
 * cursor, or of every checklist. A checklist left empty is removed too.
 */
export const deleteCheckedTransaction = (state: EditorState): Transaction | null => {
    const tr = state.tr;
    const ticked = taskItemsInScope(state).filter(({ node }) => node.attrs.checked);

    if (ticked.length === 0) {
        return null;
    }

    // Outermost ticked items only: deleting a parent already deletes its sub-items.
    const outermost = ticked.filter(
        ({ position }) => !ticked.some((other) => other.position < position && position < other.position + other.node.nodeSize),
    );

    for (const { position, node } of [...outermost].reverse()) {
        const $item = tr.doc.resolve(position);
        const list = $item.parent;

        if (list.childCount === 1) {
            tr.delete($item.before(), $item.after());
        } else {
            tr.delete(position, position + node.nodeSize);
        }
    }

    return tr;
};

/**
 * Whether a transaction ticked at least one checklist item.
 */
const tickedSomething = (transactions: readonly Transaction[], before: ProseMirrorNode, after: ProseMirrorNode): boolean => {
    // Loading a note (setContent without an update) isn't the user ticking anything.
    if (!transactions.some((transaction) => transaction.docChanged) || transactions.some((transaction) => transaction.getMeta('preventUpdate'))) {
        return false;
    }

    const count = (doc: ProseMirrorNode): number => {
        let checked = 0;
        doc.descendants((node) => {
            if (node.type.name === 'taskItem' && node.attrs.checked) {
                checked++;
            }
        });
        return checked;
    };

    return count(after) > count(before);
};

export type CheckedToBottomOptions = {
    enabled: () => boolean;
};

/**
 * Apple Notes' "Sort checked items automatically": when an item is ticked
 * (and the setting is on), ticked items move below the unticked ones.
 */
export const CheckedToBottom = Extension.create<CheckedToBottomOptions>({
    name: 'checkedToBottom',

    addOptions() {
        return { enabled: () => false };
    },

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('checkedToBottom'),
                appendTransaction: (transactions, oldState, newState) => {
                    if (!this.options.enabled() || !tickedSomething(transactions, oldState.doc, newState.doc)) {
                        return null;
                    }

                    return checkedToBottomTransaction(newState);
                },
            }),
        ];
    },
});
