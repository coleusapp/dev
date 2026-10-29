import {
    cellDisplay,
    cellText,
    columnChoices,
    move,
    nextSort,
    operatorTakesValue,
    renameFormula,
    sortDirection,
    type BaseDefinition,
} from '@/lib/bases';
import { describe, expect, it } from 'vitest';

const definition = (change: Partial<BaseDefinition> = {}): BaseDefinition => ({
    query: '',
    columns: [],
    formulas: [],
    filters: [],
    where: '',
    sort: [],
    ...change,
});

const options = { fields: { 'note.title': 'Title', 'note.updated': 'Edited' }, properties: ['author', 'year'], operators: {} };

describe('cellText', () => {
    it('writes numbers, booleans, lists and dates for people', () => {
        expect(cellText(null)).toBe('');
        expect(cellText(1234.5, 'en-US')).toBe('1,234.5');
        expect(cellText(true)).toBe('Yes');
        expect(cellText(['a', 2], 'en-US')).toBe('a, 2');
        expect(cellText('2026-11-01', 'en-US')).toBe('Nov 1, 2026');
        expect(cellText('2026-11-01T09:05', 'en-US')).toBe('Nov 1, 2026, 9:05 AM');
        expect(cellText('2026-13-45x')).toBe('2026-13-45x');
        expect(cellText({ a: 1 })).toBe('{"a":1}');
    });
});

describe('cellDisplay', () => {
    it('picks a way to show each value', () => {
        expect(cellDisplay('')).toEqual({ kind: 'empty' });
        expect(cellDisplay([])).toEqual({ kind: 'empty' });
        expect(cellDisplay(false)).toEqual({ kind: 'check', checked: false });
        expect(cellDisplay(['x', 'y'])).toEqual({ kind: 'list', items: ['x', 'y'] });
        expect(cellDisplay(3, 'en-US')).toEqual({ kind: 'text', text: '3', numeric: true });
        expect(cellDisplay('hi')).toEqual({ kind: 'text', text: 'hi', numeric: false });
    });
});

describe('nextSort', () => {
    it('cycles ascending, descending, off, and replaces other columns', () => {
        expect(nextSort([], 'year')).toEqual([{ column: 'year', direction: 'asc' }]);
        expect(nextSort([{ column: 'year', direction: 'asc' }], 'year')).toEqual([{ column: 'year', direction: 'desc' }]);
        expect(nextSort([{ column: 'year', direction: 'desc' }], 'year')).toEqual([]);
        expect(
            nextSort(
                [
                    { column: 'year', direction: 'desc' },
                    { column: 'author', direction: 'asc' },
                ],
                'year',
            ),
        ).toEqual([{ column: 'year', direction: 'asc' }]);
        expect(sortDirection([{ column: 'year', direction: 'desc' }], 'year')).toBe('desc');
        expect(sortDirection([], 'year')).toBeNull();
    });
});

describe('columnChoices', () => {
    it('offers fields, properties, chosen properties no note has, and formulas', () => {
        const choices = columnChoices(
            options,
            definition({
                columns: ['Gone', 'AUTHOR', 'formula.total'],
                formulas: [
                    { name: 'total', expression: '1' },
                    { name: ' ', expression: '' },
                ],
            }),
            false,
        );

        expect(choices).toEqual([
            { label: 'Edited', value: 'note.updated' },
            { label: 'author', value: 'author' },
            { label: 'year', value: 'year' },
            { label: 'Gone', value: 'Gone' },
            { label: 'ƒ total', value: 'formula.total' },
        ]);
        expect(columnChoices(options, definition(), true)[0]).toEqual({ label: 'Title', value: 'note.title' });
    });
});

describe('renameFormula', () => {
    it('follows a renamed formula in columns, filters and sort', () => {
        const renamed = renameFormula(
            definition({
                columns: ['formula.Total', 'author'],
                filters: [{ column: 'formula.total', operator: 'gt', value: '1' }],
                sort: [{ column: 'formula.total', direction: 'asc' }],
            }),
            'total',
            'sum',
        );

        expect(renamed.columns).toEqual(['formula.sum', 'author']);
        expect(renamed.filters[0].column).toBe('formula.sum');
        expect(renamed.sort[0].column).toBe('formula.sum');
        expect(renameFormula(definition({ columns: ['author'] }), '', 'x').columns).toEqual(['author']);
    });
});

describe('helpers', () => {
    it('moves items and knows which operators take a value', () => {
        expect(move(['a', 'b', 'c'], 0, 1)).toEqual(['b', 'a', 'c']);
        expect(move(['a', 'b'], 0, -1)).toEqual(['a', 'b']);
        expect(operatorTakesValue('is')).toBe(true);
        expect(operatorTakesValue('empty')).toBe(false);
    });
});
