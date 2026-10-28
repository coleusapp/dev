import { linkQuery, type LinkTrigger } from '@/editor/linkQuery';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import type { EditorView } from '@tiptap/pm/view';
import { Extension } from '@tiptap/vue-3';

export type LinkMenuState = {
    trigger: LinkTrigger;
    query: string;
    range: { from: number; to: number };
    position: { left: number; top: number };
};

export type LinkMenuOptions = {
    onState: (state: LinkMenuState | null) => void;
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
 * Watches for a link being typed ("[[…" or ">>…", see linkQuery) outside
 * code and reports it with its range and screen position, so NoteEditor can
 * show the note picker (O2.4 / A6.1). While it's open, arrow keys, Enter,
 * Tab and Escape go to it; Escape dismisses it until the trigger is retyped.
 */
export const LinkMenu = Extension.create<LinkMenuOptions>({
    name: 'linkMenu',

    addOptions() {
        return { onState: () => undefined, onKey: () => false };
    },

    addProseMirrorPlugins() {
        let open = false;
        let dismissedAt: number | null = null;

        const detect = (view: EditorView): LinkMenuState | null => {
            const { selection } = view.state;
            const { $from } = selection;

            if (!selection.empty || !$from.parent.isTextblock || $from.parent.type.spec.code) {
                return null;
            }

            const before = $from.parent.textBetween(0, $from.parentOffset, undefined, '￼');
            const after = $from.parent.textBetween($from.parentOffset, Math.min($from.parentOffset + 2, $from.parent.content.size), undefined, '￼');
            const found = linkQuery(before, after);

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
                key: new PluginKey('linkMenu'),
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
