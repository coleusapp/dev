import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import { Extension } from '@tiptap/vue-3';

/**
 * The same rule the server uses (Markdown\Hashtags): a tag starts a word,
 * may nest with "/", and needs at least one letter.
 */
export const HASHTAG = /(?<![\p{L}\p{N}_&/#:.-])#([\p{L}\p{N}_-]+(?:\/[\p{L}\p{N}_-]+)*)/gu;

const decorate = (doc: ProseMirrorNode): DecorationSet => {
    const decorations: Decoration[] = [];

    doc.descendants((node, position, parent) => {
        if (!node.isText || parent?.type.spec.code || node.marks.some((mark) => mark.type.spec.code || mark.type.name === 'link')) {
            return;
        }

        for (const match of node.text?.matchAll(HASHTAG) ?? []) {
            if (/\p{L}/u.test(match[1])) {
                const from = position + (match.index ?? 0);
                decorations.push(Decoration.inline(from, from + match[0].length, { class: 'hashtag', 'data-tag': match[1].toLowerCase() }));
            }
        }
    });

    return DecorationSet.create(doc, decorations);
};

/**
 * Highlights #tags while typing. Tags stay plain text in the document (and
 * in the Markdown); this only styles them.
 */
export const Hashtags = Extension.create({
    name: 'hashtags',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('hashtags'),
                state: {
                    init: (_, { doc }) => decorate(doc),
                    apply: (transaction, decorations) => (transaction.docChanged ? decorate(transaction.doc) : decorations),
                },
                props: {
                    decorations(state) {
                        return this.getState(state);
                    },
                },
            }),
        ];
    },
});
