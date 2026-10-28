const pad = (value: number, length = 2): string => String(value).padStart(length, '0');

/**
 * Formats a date with Moment-style tokens (as Obsidian templates use):
 * YYYY YY, MMMM MMM MM M, DD D, dddd ddd, HH H, hh h, mm, ss, A a.
 * Text in [brackets] is kept as written.
 */
export const formatDate = (date: Date, format: string, locale?: string): string => {
    const month = (style: 'long' | 'short') => new Intl.DateTimeFormat(locale, { month: style }).format(date);
    const weekday = (style: 'long' | 'short') => new Intl.DateTimeFormat(locale, { weekday: style }).format(date);
    const hours12 = date.getHours() % 12 || 12;
    const tokens: Record<string, () => string> = {
        YYYY: () => String(date.getFullYear()),
        YY: () => String(date.getFullYear()).slice(-2),
        MMMM: () => month('long'),
        MMM: () => month('short'),
        MM: () => pad(date.getMonth() + 1),
        M: () => String(date.getMonth() + 1),
        DD: () => pad(date.getDate()),
        D: () => String(date.getDate()),
        dddd: () => weekday('long'),
        ddd: () => weekday('short'),
        HH: () => pad(date.getHours()),
        H: () => String(date.getHours()),
        hh: () => pad(hours12),
        h: () => String(hours12),
        mm: () => pad(date.getMinutes()),
        ss: () => pad(date.getSeconds()),
        A: () => (date.getHours() < 12 ? 'AM' : 'PM'),
        a: () => (date.getHours() < 12 ? 'am' : 'pm'),
    };

    return format.replace(/\[([^\]]*)\]|YYYY|YY|MMMM|MMM|MM|M|DD|D|dddd|ddd|HH|H|hh|h|mm|ss|A|a/g, (token, literal) => literal ?? tokens[token]());
};

/**
 * Fills a template's variables (O9.3): {{title}}, {{date}} (YYYY-MM-DD),
 * {{time}} (HH:mm), and {{date:FORMAT}} / {{time:FORMAT}} with formatDate
 * tokens. Unknown {{names}} are left as written.
 */
export const fillTemplate = (template: string, values: { title: string; now?: Date; locale?: string }): string => {
    const now = values.now ?? new Date();

    return template.replace(/\{\{\s*(title|date|time)(?::([^}]*))?\s*\}\}/g, (_match, name: string, format?: string) => {
        if (name === 'title') {
            return values.title;
        }

        return formatDate(now, format?.trim() || (name === 'date' ? 'YYYY-MM-DD' : 'HH:mm'), values.locale);
    });
};
