import { linkQuery, type LinkTrigger } from '@/editor/linkQuery';
import { Typeahead, type TypeaheadState } from '@/editor/Typeahead';

export type LinkMenuState = TypeaheadState<LinkTrigger>;

/**
 * The note picker while a link is typed ("[[…" or ">>…", see linkQuery),
 * for link autocomplete (O2.4 / A6.1).
 */
export const LinkMenu = Typeahead.extend({
    name: 'linkMenu',

    addOptions() {
        return { ...this.parent?.(), detect: linkQuery };
    },
});
