/**
 * Bases (O11.1): the page's types and the logic it shows them with. The
 * server builds the rows (Bases\BaseTable); see Bases\BaseDefinition for
 * the definition's shape.
 */

export type BaseFormula = { name: string; expression: string };
export type BaseFilter = { column: string; operator: string; value: string };
export type BaseSort = { column: string; direction: 'asc' | 'desc' };

export type BaseDefinition = {
    query: string;
    columns: string[];
    formulas: BaseFormula[];
    filters: BaseFilter[];
    where: string;
    sort: BaseSort[];
};

export type BaseColumn = { id: string; label: string; kind: 'note' | 'property' | 'formula' };
export type BaseRow = { id: number; title: string; locked: boolean; cells: Record<string, unknown>; errors: Record<string, string> };
export type BaseTable = { columns: BaseColumn[]; rows: BaseRow[]; total: number; errors: Record<string, string> };

export type BaseOptions = {
    fields: Record<string, string>;
    properties: string[];
    operators: Record<string, string>;
};

export type ColumnChoice = { label: string; value: string };

/**
 * How a cell shows: text, a tick, chips for a list, or nothing.
 */
export type CellDisplay =
    { kind: 'empty' } | { kind: 'text'; text: string; numeric: boolean } | { kind: 'check'; checked: boolean } | { kind: 'list'; items: string[] };

const DATE = /^(\d{4})-(\d{2})-(\d{2})$/;
const DATETIME = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::\d{2})?$/;

export const cellText = (value: unknown, locale?: string): string => {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'number') {
        return new Intl.NumberFormat(locale, { maximumFractionDigits: 10 }).format(value);
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (Array.isArray(value)) {
        return value.map((item) => cellText(item, locale)).join(', ');
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    const text = String(value);
    const date = DATE.exec(text);

    if (date) {
        const [, year, month, day] = date.map(Number);

        return new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(year, month - 1, day));
    }

    const datetime = DATETIME.exec(text);

    if (datetime) {
        const [, year, month, day, hour, minute] = datetime.map(Number);

        return new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }).format(
            new Date(year, month - 1, day, hour, minute),
        );
    }

    return text;
};

export const cellDisplay = (value: unknown, locale?: string): CellDisplay => {
    if (value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) {
        return { kind: 'empty' };
    }

    if (typeof value === 'boolean') {
        return { kind: 'check', checked: value };
    }

    if (Array.isArray(value)) {
        return { kind: 'list', items: value.map((item) => cellText(item, locale)) };
    }

    return { kind: 'text', text: cellText(value, locale), numeric: typeof value === 'number' };
};

/**
 * Clicking a header: sort by it ascending, then descending, then not at
 * all (back to last edited first). Other sort columns are dropped.
 */
export const nextSort = (sort: BaseSort[], column: string): BaseSort[] => {
    const current = sort.length === 1 && sort[0].column === column ? sort[0].direction : null;

    if (current === null) {
        return [{ column, direction: 'asc' }];
    }

    return current === 'asc' ? [{ column, direction: 'desc' }] : [];
};

export const sortDirection = (sort: BaseSort[], column: string): 'asc' | 'desc' | null =>
    sort.find((each) => each.column === column)?.direction ?? null;

/**
 * Everything a column, filter or sort can use: note fields, every property
 * in use (plus ones already chosen that no note has any more) and the
 * base's formulas. withTitle: the title is always the first column, so
 * it's only offered for filters and sort.
 */
export const columnChoices = (options: BaseOptions, definition: BaseDefinition, withTitle: boolean): ColumnChoice[] => {
    const fields = Object.entries(options.fields)
        .filter(([id]) => withTitle || id !== 'note.title')
        .map(([value, label]) => ({ label, value }));
    const formulas = definition.formulas.filter((formula) => formula.name.trim() !== '').map((formula) => `formula.${formula.name.trim()}`);
    const used = [...definition.columns, ...definition.filters.map((filter) => filter.column), ...definition.sort.map((sort) => sort.column)];
    const known = new Set([...Object.keys(options.fields), ...formulas].map((id) => id.toLowerCase()));
    const properties = [...options.properties];

    for (const id of used) {
        const lower = id.toLowerCase();

        if (id !== '' && !lower.startsWith('formula.') && !known.has(lower) && !properties.some((each) => each.toLowerCase() === lower)) {
            properties.push(id);
        }
    }

    return [
        ...fields,
        ...properties.map((property) => ({ label: property, value: property })),
        ...formulas.map((value) => ({ label: `ƒ ${value.slice(8)}`, value })),
    ];
};

/**
 * The operators that need a value typed after them.
 */
export const operatorTakesValue = (operator: string): boolean => operator !== 'empty' && operator !== 'not_empty';

/**
 * Renaming a formula keeps the columns, filters and sort that use it.
 */
export const renameFormula = (definition: BaseDefinition, from: string, to: string): BaseDefinition => {
    const swap = (column: string): string => (from !== '' && column.toLowerCase() === `formula.${from}`.toLowerCase() ? `formula.${to}` : column);

    return {
        ...definition,
        columns: definition.columns.map(swap),
        filters: definition.filters.map((filter) => ({ ...filter, column: swap(filter.column) })),
        sort: definition.sort.map((sort) => ({ ...sort, column: swap(sort.column) })),
    };
};

/**
 * Moves an item up or down a list, returning a new list.
 */
export const move = <T>(list: T[], index: number, by: -1 | 1): T[] => {
    const target = index + by;

    if (target < 0 || target >= list.length) {
        return list;
    }

    const next = [...list];
    [next[index], next[target]] = [next[target], next[index]];

    return next;
};
