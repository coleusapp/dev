import { filterQuery } from '@/components/notes/note';
import { describe, expect, it } from 'vitest';

describe('filterQuery', () => {
    it('keeps set filters as strings, including the folder id', () => {
        expect(filterQuery({ tag: 'work', q: '', view: 'archive', folder: 7 })).toEqual({ tag: 'work', view: 'archive', folder: '7' });
    });

    it('drops the default view and empty values', () => {
        expect(filterQuery({ tag: null, q: null, view: 'notes', folder: null })).toEqual({});
    });
});
