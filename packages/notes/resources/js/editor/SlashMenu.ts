import { slashQuery } from '@/editor/slashCommands';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import type { EditorView } from '@tiptap/pm/view';
import { Extension } from '@tiptap/vue-3';

export type SlashState = {
    query: string;
    range: { from: number; to: number };
    position: { left: number; top: number };
};

export type SlashMenuOptions = {
    onState: (state: SlashState | null) => void;
    onKey: (key: string) => boolean;
};

const KEYS = ['ArrowUp', 'ArrowDown', 'Enter', 'Tab', 'Escape'];

/**
 * Screen position just below `position`; falls back to the editor's own
 * top-left corner when the text can't be measured (not laid out yet).
 */
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
 * Watches for "/query" typed at the start of a line or after a space (not
 * in code) and reports it with its screen position, so NoteEditor can show
 * the command menu. While the menu is open, arrow keys, Enter, Tab and
 * Escape go to it. Escape dismisses it until the "/" is retyped.
 */
export const SlashMenu = Extension.create<SlashMenuOptions>({
    name: 'slashMenu',

    addOptions() {
        return { onState: () => undefined, onKey: () => false };
    },

    addProseMirrorPlugins() {
        let open = false;
        let dismissedAt: number | null = null;

        const detect = (view: EditorView): SlashState | null => {
            const { selection } = view.state;
            const { $from } = selection;

            if (!selection.empty || !$from.parent.isTextblock || $from.parent.type.spec.code) {
                return null;
            }

            const found = slashQuery($from.parent.textBetween(0, $from.parentOffset, undefined, '￼'));

            if (!found) {
                dismissedAt = null;
                return null;
            }

            const from = $from.pos - found.length;

            if (dismissedAt === from) {
                return null;
            }

            return { query: found.query, range: { from, to: $from.pos }, position: positionOf(view, from) };
        };

        return [
            new Plugin({
                key: new PluginKey('slashMenu'),
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
