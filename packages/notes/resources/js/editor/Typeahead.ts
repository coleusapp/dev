import { Plugin, PluginKey } from '@tiptap/pm/state';
import type { EditorView } from '@tiptap/pm/view';
import { Extension } from '@tiptap/vue-3';

/**
 * What a typeahead found before (and right after) the caret: the query,
 * how many characters before the caret it spans (trigger included) and how
 * many after it to replace too (an auto-paired closer).
 */
export type TypeaheadMatch<Trigger extends string = string> = { trigger: Trigger; query: string; before: number; after: number };

export type TypeaheadState<Trigger extends string = string> = {
    trigger: Trigger;
    query: string;
    range: { from: number; to: number };
    position: { left: number; top: number };
};

export type TypeaheadOptions = {
    detect: (textBefore: string, textAfter: string) => TypeaheadMatch | null;
    onState: (state: TypeaheadState | null) => void;
    onKey: (key: string) => boolean;
};

const KEYS = ['ArrowUp', 'ArrowDown', 'Enter', 'Tab', 'Escape'];

const positionOf = (view: EditorView, position: number): { left: number; top: number } => {
    try {
        const coords = view.coordsAtPos(position);

        return { left: coords.left, top: coords.bottom };
    } catch {
        const box = view.dom.getBoundingClientRect?.();

        return { left: box?.left ?? 0, top: box?.top ?? 0 };
    }
};

/**
 * A suggestion menu driven by what's typed at the caret (outside code
 * blocks and `code`):
 * `detect` decides whether one is open and for what, NoteEditor shows it.
 * While it's open, arrow keys, Enter, Tab and Escape go to it; Escape
 * dismisses it until the trigger is typed again. Extend it with a name for
 * each menu (LinkMenu, TagMenu).
 */
export const Typeahead = Extension.create<TypeaheadOptions>({
    name: 'typeahead',

    addOptions() {
        return { detect: () => null, onState: () => undefined, onKey: () => false };
    },

    addProseMirrorPlugins() {
        let open = false;
        let dismissedAt: number | null = null;

        const detect = (view: EditorView): TypeaheadState | null => {
            const { selection } = view.state;
            const { $from } = selection;

            const inCode = $from.parent.type.spec.code || $from.marks().some((mark) => mark.type.spec.code);

            if (!selection.empty || !$from.parent.isTextblock || inCode) {
                return null;
            }

            const before = $from.parent.textBetween(0, $from.parentOffset, undefined, '￼');
            const after = $from.parent.textBetween($from.parentOffset, Math.min($from.parentOffset + 2, $from.parent.content.size), undefined, '￼');
            const found = this.options.detect(before, after);

            if (!found) {
                dismissedAt = null;

                return null;
            }

            const from = $from.pos - found.before;

            if (dismissedAt === from) {
                return null;
            }

            return { trigger: found.trigger, query: found.query, range: { from, to: $from.pos + found.after }, position: positionOf(view, from) };
        };

        return [
            new Plugin({
                key: new PluginKey(this.name),
                view: () => ({
                    update: (view) => {
                        const state = detect(view);
                        open = state !== null;
                        this.options.onState(state);
                    },
                    destroy: () => this.options.onState(null),
                }),
                props: {
                    handleKeyDown: (view, event) => {
                        if (!open || !KEYS.includes(event.key)) {
                            return false;
                        }

                        if (event.key === 'Escape') {
                            dismissedAt = detect(view)?.range.from ?? null;
                            open = false;
                            this.options.onState(null);

                            return true;
                        }

                        return this.options.onKey(event.key);
                    },
                },
            }),
        ];
    },
});
