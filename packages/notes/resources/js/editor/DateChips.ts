import { Typeahead, type TypeaheadState } from '@/editor/Typeahead';
import { DATE_CHIP, dateQuery, isRealDate } from '@/lib/dates';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import { Extension } from '@tiptap/vue-3';

export type DateMenuState = TypeaheadState<'@'>;

const decorate = (doc: ProseMirrorNode): DecorationSet => {
    const decorations: Decoration[] = [];

    doc.descendants((node, position, parent) => {
        if (!node.isText || parent?.type.spec.code || node.marks.some((mark) => mark.type.spec.code || mark.type.name === 'link')) {
            return;
        }

        for (const match of node.text?.matchAll(DATE_CHIP) ?? []) {
            if (isRealDate(match[1])) {
                const from = position + (match.index ?? 0);
                decorations.push(Decoration.inline(from, from + match[0].length, { class: 'date-chip', 'data-date': match[1] }));
            }
        }
    });

    return DecorationSet.create(doc, decorations);
};

/**
 * Styles "@2026-10-28" dates as chips while writing (B5.4). They stay plain
 * text in the document and the Markdown.
 */
export const DateChips = Extension.create({
    name: 'dateChips',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('dateChips'),
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

/**
 * Date suggestions while "@…" is typed.
 */
export const DateMenu = Typeahead.extend({
    name: 'dateMenu',

    addOptions() {
        return { ...this.parent?.(), detect: (before: string) => dateQuery(before) };
    },
});
