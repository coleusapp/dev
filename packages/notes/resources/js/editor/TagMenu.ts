import { tagQuery } from '@/editor/tagQuery';
import { Typeahead, type TypeaheadState } from '@/editor/Typeahead';

export type TagMenuState = TypeaheadState<'#'>;

/**
 * Tag autocomplete (O2.5) while a #tag is typed.
 */
export const TagMenu = Typeahead.extend({
    name: 'tagMenu',

    addOptions() {
        return { ...this.parent?.(), detect: (before: string) => tagQuery(before) };
    },
});
