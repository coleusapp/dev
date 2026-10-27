import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { Plugin, PluginKey, type EditorState } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import { Extension } from '@tiptap/vue-3';

type FoldState = { folded: number[] };

export const foldKey = new PluginKey<FoldState>('folding');

const FOLDABLE_ITEMS = ['listItem', 'taskItem'];

const hasNestedList = (item: ProseMirrorNode): boolean => {
    let nested = false;
    item.forEach((child) => {
        nested ||= child.type.name === 'bulletList' || child.type.name === 'orderedList' || child.type.name === 'taskList';
    });
    return nested;
};

const toggleButton = (position: number, folded: boolean, label: string): HTMLElement => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `fold-toggle${folded ? ' is-folded' : ''}`;
    button.setAttribute('aria-label', folded ? `Expand ${label}` : `Collapse ${label}`);
    button.setAttribute('aria-expanded', String(!folded));
    button.contentEditable = 'false';
    button.dataset.foldPosition = String(position);

    return button;
};

/**
 * Decorations for the current folds: a toggle on every heading and every
 * list item with sub-items; blocks under a folded heading (up to the next
 * heading of the same or a higher level) and sub-lists of folded items
 * are hidden. Only how the note looks changes, never its text.
 */
const decorate = (state: EditorState, folded: number[]): DecorationSet => {
    const decorations: Decoration[] = [];
    let hideUntilLevel: number | null = null;

    state.doc.forEach((node, offset) => {
        if (node.type.name === 'heading') {
            const level = node.attrs.level as number;

            if (hideUntilLevel !== null && level <= hideUntilLevel) {
                hideUntilLevel = null;
            }

            if (hideUntilLevel === null) {
                const isFolded = folded.includes(offset);
                decorations.push(
                    Decoration.widget(offset + 1, () => toggleButton(offset, isFolded, 'section'), { side: -1, key: `fold-${offset}-${isFolded}` }),
                );

                if (isFolded) {
                    decorations.push(Decoration.node(offset, offset + node.nodeSize, { class: 'is-folded' }));
                    hideUntilLevel = level;
                }

                return;
            }
        }

        if (hideUntilLevel !== null) {
            decorations.push(Decoration.node(offset, offset + node.nodeSize, { class: 'folded-hidden' }));
        }
    });

    state.doc.descendants((node, position) => {
        if (FOLDABLE_ITEMS.includes(node.type.name) && hasNestedList(node)) {
            const isFolded = folded.includes(position);
            decorations.push(
                Decoration.widget(position + 1, () => toggleButton(position, isFolded, 'item'), { side: -1, key: `fold-${position}-${isFolded}` }),
            );

            if (isFolded) {
                decorations.push(Decoration.node(position, position + node.nodeSize, { class: 'is-folded' }));
            }
        }
    });

    return DecorationSet.create(state.doc, decorations);
};

/**
 * Fold/unfold toggle position (a heading's or list item's start).
 */
export const toggleFoldMeta = (position: number) => ({ toggle: position });

export const Folding = Extension.create({
    name: 'folding',

    addProseMirrorPlugins() {
        return [
            new Plugin<FoldState>({
                key: foldKey,
                state: {
                    init: () => ({ folded: [] }),
                    apply: (tr, previous) => {
                        const mapped = previous.folded
                            .map((position) => tr.mapping.map(position))
                            .filter((position, index, all) => all.indexOf(position) === index);
                        const meta = tr.getMeta(foldKey) as { toggle: number } | undefined;

                        if (!meta) {
                            return { folded: mapped };
                        }

                        return {
                            folded: mapped.includes(meta.toggle) ? mapped.filter((position) => position !== meta.toggle) : [...mapped, meta.toggle],
                        };
                    },
                },
                props: {
                    decorations(state) {
                        return decorate(state, foldKey.getState(state)?.folded ?? []);
                    },
                    handleDOMEvents: {
                        mousedown: (view, event) => {
                            const button = event.target instanceof Element ? event.target.closest<HTMLElement>('.fold-toggle') : null;

                            if (!button?.dataset.foldPosition) {
                                return false;
                            }

                            event.preventDefault();
                            view.dispatch(view.state.tr.setMeta(foldKey, toggleFoldMeta(Number(button.dataset.foldPosition))));

                            return true;
                        },
                    },
                },
            }),
        ];
    },
});
