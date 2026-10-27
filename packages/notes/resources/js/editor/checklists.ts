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
