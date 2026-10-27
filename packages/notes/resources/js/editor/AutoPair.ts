import { canPairAfter, canPairBefore, deletePair } from '@/lib/autoPair';
import { Plugin, PluginKey, TextSelection } from '@tiptap/pm/state';
import type { EditorView } from '@tiptap/pm/view';
import { Extension } from '@tiptap/vue-3';

/**
 * Brackets pair up in the visual editor. Quotes, backticks and emphasis
 * markers don't: there they would fight Tiptap's own Markdown input rules
 * (`code`, **bold**), which already format as you type.
 */
const VISUAL_PAIRS: Record<string, string> = { '(': ')', '[': ']', '{': '}' };
const VISUAL_CLOSERS = new Set(Object.values(VISUAL_PAIRS));

const charAt = (view: EditorView, position: number): string => {
    const { doc } = view.state;

    return position < 0 || position >= doc.content.size ? '' : doc.textBetween(position, position + 1, '\n', '￼');
};

export const AutoPair = Extension.create({
    name: 'autoPair',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('autoPair'),
                props: {
                    handleTextInput: (view, from, to, text) => {
                        const closer = VISUAL_PAIRS[text];
                        const { state } = view;

                        if (closer && from !== to) {
                            const tr = state.tr.insertText(closer, to).insertText(text, from);
                            view.dispatch(tr.setSelection(TextSelection.create(tr.doc, from + 1, to + 1)));

                            return true;
                        }

                        if (from !== to) {
                            return false;
                        }

                        if (VISUAL_CLOSERS.has(text) && charAt(view, from) === text) {
                            const tr = state.tr.setSelection(TextSelection.create(state.doc, from + 1));
                            const $cursor = tr.doc.resolve(from + 1);
                            const before = $cursor.parent.textBetween(0, $cursor.parentOffset, undefined, '\ufffc');
                            const link = /\[\[([^[\]\n\ufffc]{1,255})\]\]$/.exec(before);
                            const wikiLink = state.schema.nodes.wikiLink;

                            // Stepping over the last "]" of [[Title]] completes a wiki link,
                            // which the input rule would otherwise never see.
                            if (link && link[1].trim() !== '' && wikiLink) {
                                tr.replaceWith(from + 1 - link[0].length, from + 1, wikiLink.create({ title: link[1] }));
                            }

                            view.dispatch(tr);

                            return true;
                        }

                        if (closer && canPairBefore(charAt(view, from)) && canPairAfter(text, charAt(view, from - 1))) {
                            const tr = state.tr.insertText(text + closer, from);
                            view.dispatch(tr.setSelection(TextSelection.create(tr.doc, from + 1)));

                            return true;
                        }

                        return false;
                    },
                    handleKeyDown: (view, event) => {
                        const { from, to } = view.state.selection;

                        if (event.key !== 'Backspace' || from !== to || !VISUAL_PAIRS[charAt(view, from - 1)]) {
                            return false;
                        }

                        const edit = deletePair({ text: charAt(view, from - 1) + charAt(view, from), start: 1, end: 1 });

                        if (!edit) {
                            return false;
                        }

                        view.dispatch(view.state.tr.delete(from - 1, from + 1));

                        return true;
                    },
                },
            }),
        ];
    },
});
