import { findMatches, replacementFor } from '@/lib/find';
import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey, TextSelection } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import { Extension, type Editor } from '@tiptap/vue-3';

type Match = { from: number; to: number };

type FindState = { pattern: RegExp | null; matches: Match[]; current: number };

export const findKey = new PluginKey<FindState>('find');

/**
 * Matches in every text block (paragraphs, headings, cells, code). Inline
 * atoms like wiki-link chips count as one character so offsets map
 * straight onto document positions.
 */
const matchesIn = (doc: ProseMirrorNode, pattern: RegExp | null): Match[] => {
    if (!pattern) {
        return [];
    }

    const matches: Match[] = [];

    doc.descendants((node, position) => {
        if (!node.isTextblock) {
            return true;
        }

        const text = node.textBetween(0, node.content.size, undefined, '￼');
        findMatches(text, pattern).forEach(({ start, end }) => matches.push({ from: position + 1 + start, to: position + 1 + end }));

        return false;
    });

    return matches;
};

/**
 * Highlights find matches (the current one more strongly) and keeps them
 * up to date while the note changes.
 */
export const Find = Extension.create({
    name: 'find',

    addProseMirrorPlugins() {
        return [
            new Plugin<FindState>({
                key: findKey,
                state: {
                    init: () => ({ pattern: null, matches: [], current: 0 }),
                    apply: (tr, previous) => {
                        const meta = tr.getMeta(findKey) as { pattern: RegExp | null; current?: number } | undefined;
                        const pattern = meta ? meta.pattern : previous.pattern;

                        if (!meta && !tr.docChanged) {
                            return previous;
                        }

                        const matches = matchesIn(tr.doc, pattern);
                        const current = Math.min(meta?.current ?? previous.current, Math.max(0, matches.length - 1));

                        return { pattern, matches, current };
                    },
                },
                props: {
                    decorations(state) {
                        const { matches, current } = findKey.getState(state) ?? { matches: [], current: 0 };

                        return DecorationSet.create(
                            state.doc,
                            matches.map((match, index) =>
                                Decoration.inline(match.from, match.to, { class: index === current ? 'find-match find-current' : 'find-match' }),
                            ),
                        );
                    },
                },
            }),
        ];
    },
});

export const findState = (editor: Editor): FindState => findKey.getState(editor.state) ?? { pattern: null, matches: [], current: 0 };

/**
 * Sets (or clears, with null) the search and selects the current match.
 */
export const setFind = (editor: Editor, pattern: RegExp | null, current = 0): void => {
    editor.view.dispatch(editor.state.tr.setMeta(findKey, { pattern, current }));
    revealCurrent(editor);
};

export const revealCurrent = (editor: Editor): void => {
    const { matches, current } = findState(editor);
    const match = matches[current];

    if (match) {
        editor.view.dispatch(editor.state.tr.setSelection(TextSelection.create(editor.state.doc, match.from, match.to)).scrollIntoView());
    }
};

export const stepFind = (editor: Editor, step: 1 | -1): void => {
    const { pattern, matches, current } = findState(editor);

    if (matches.length) {
        setFind(editor, pattern, (current + step + matches.length) % matches.length);
    }
};

/**
 * Replaces the current match and moves on to the next one.
 */
export const replaceCurrent = (editor: Editor, replacement: string, regex: boolean): void => {
    const { pattern, matches, current } = findState(editor);
    const match = matches[current];

    if (!pattern || !match) {
        return;
    }

    const text = replacementFor(editor.state.doc.textBetween(match.from, match.to, undefined, '￼'), pattern, replacement, regex);
    editor.view.dispatch(text === '' ? editor.state.tr.delete(match.from, match.to) : editor.state.tr.insertText(text, match.from, match.to));
    revealCurrent(editor);
};

/**
 * Replaces every match in one undoable step; returns how many were replaced.
 */
export const replaceEvery = (editor: Editor, replacement: string, regex: boolean): number => {
    const { pattern, matches } = findState(editor);

    if (!pattern || !matches.length) {
        return 0;
    }

    const tr = editor.state.tr;

    [...matches].reverse().forEach(({ from, to }) => {
        const text = replacementFor(editor.state.doc.textBetween(from, to, undefined, '￼'), pattern, replacement, regex);

        if (text === '') {
            tr.delete(from, to);
        } else {
            tr.insertText(text, from, to);
        }
    });
    editor.view.dispatch(tr);

    return matches.length;
};
