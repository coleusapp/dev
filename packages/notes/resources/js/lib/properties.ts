import { joinFrontMatter, splitFrontMatter } from '@/editor/frontMatter';

export type PropertyValue = string | number | boolean | null | string[];

export type PropertyType = 'text' | 'list' | 'number' | 'checkbox' | 'date' | 'datetime';

export type Properties = Record<string, PropertyValue>;

const DATE = /^\d{4}-\d{2}-\d{2}$/;
const DATETIME = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/;

/**
 * Reads the same YAML subset as the server (Markdown\FrontMatter::parse):
 * scalars and lists. Returns {} without front matter and null when the
 * block uses YAML this doesn't read (it's then left untouched).
 */
export const parseProperties = (markdown: string): Properties | null => {
    const { frontMatter } = splitFrontMatter(markdown);

    if (frontMatter === '') {
        return {};
    }

    const lines = frontMatter.replace(/\r\n/g, '\n').split('\n').slice(1, -2);
    const properties: Properties = {};
    let listKey: string | null = null;

    for (const line of lines) {
        if (line.trim() === '' || line.trimStart().startsWith('#')) {
            continue;
        }

        const item = /^\s+-\s*(.*)$/.exec(line);

        if (listKey !== null && item) {
            (properties[listKey] as string[]).push(String(scalar(item[1]) ?? ''));
            continue;
        }

        const pair = /^([A-Za-z0-9_][A-Za-z0-9 _.-]*?)\s*:(?:\s+(.*))?$/.exec(line);

        if (!pair) {
            return null;
        }

        const value = (pair[2] ?? '').trim();
        listKey = null;

        if (value === '') {
            properties[pair[1]] = [];
            listKey = pair[1];
        } else if (/^\[(.*)\]$/.test(value)) {
            properties[pair[1]] = splitInline(value.slice(1, -1)).map((entry) => String(scalar(entry) ?? ''));
        } else if (/^[|>{&*!]/.test(value)) {
            return null;
        } else {
            properties[pair[1]] = scalar(value);
        }
    }

    for (const [key, value] of Object.entries(properties)) {
        if (Array.isArray(value) && value.length === 0) {
            properties[key] = null;
        }
    }

    return properties;
};

const scalar = (raw: string): string | number | boolean | null => {
    const value = raw.trim();
    const double = /^"((?:[^"\\]|\\.)*)"(?:\s+#.*)?$/.exec(value);
    const single = /^'((?:[^']|'')*)'(?:\s+#.*)?$/.exec(value);

    if (double) {
        return double[1].replace(/\\(.)/g, (_, character: string) => ({ n: '\n', t: '\t' })[character] ?? character);
    }
    if (single) {
        return single[1].replace(/''/g, "'");
    }

    const plain = value.replace(/\s+#.*$/, '').trim();

    if (plain === '' || plain === '~' || /^null$/i.test(plain)) {
        return null;
    }
    if (/^true$/i.test(plain)) {
        return true;
    }
    if (/^false$/i.test(plain)) {
        return false;
    }
    if (/^-?\d+$/.test(plain) || /^-?\d*\.\d+$/.test(plain)) {
        return Number(plain);
    }

    return plain;
};

const splitInline = (list: string): string[] =>
    Array.from(list.matchAll(/\s*("(?:[^"\\]|\\.)*"|'(?:[^']|'')*'|[^,]*)\s*(?:,|$)/g))
        .map((match) => match[1])
        .filter((entry) => entry.trim() !== '');

/**
 * The kind of field a value is edited with.
 */
export const propertyType = (value: PropertyValue): PropertyType => {
    if (Array.isArray(value)) {
        return 'list';
    }
    if (typeof value === 'boolean') {
        return 'checkbox';
    }
    if (typeof value === 'number') {
        return 'number';
    }
    if (typeof value === 'string' && DATETIME.test(value)) {
        return 'datetime';
    }
    if (typeof value === 'string' && DATE.test(value)) {
        return 'date';
    }

    return 'text';
};

/**
 * Strings are quoted when YAML would otherwise read them as something
 * else (numbers, booleans, null, lists…) or they contain ": " / " #".
 */
const yamlString = (value: string): string => {
    const needsQuotes =
        value === '' ||
        value !== value.trim() ||
        /^(true|false|null|~|-?\d+(\.\d+)?|-?\.\d+)$/i.test(value) ||
        /^[-?:,[\]{}#&*!|>'"%@`]/.test(value) ||
        /: |\s#|\n/.test(value);

    return needsQuotes ? `"${value.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\n/g, '\\n')}"` : value;
};

const yamlLines = (key: string, value: PropertyValue): string[] => {
    if (Array.isArray(value)) {
        return value.length ? [`${key}:`, ...value.map((item) => `  - ${yamlString(item)}`)] : [`${key}:`];
    }
    if (value === null) {
        return [`${key}:`];
    }
    if (typeof value === 'boolean' || typeof value === 'number') {
        return [`${key}: ${value}`];
    }

    return [`${key}: ${yamlString(value)}`];
};

/**
 * The note with its front matter replaced by these properties (removed
 * entirely when there are none). The rest of the note is untouched.
 */
export const setProperties = (markdown: string, properties: Properties): string => {
    const { body } = splitFrontMatter(markdown);
    const lines = Object.entries(properties).flatMap(([key, value]) => yamlLines(key, value));

    return lines.length ? joinFrontMatter(`---\n${lines.join('\n')}\n---\n`, body) : body;
};

/**
 * Property names are kept to what the parser reads back.
 */
export const isValidPropertyName = (name: string): boolean => /^[A-Za-z0-9_][A-Za-z0-9 _.-]*$/.test(name) && name.trim() === name;
