import type { TypeaheadMatch } from '@/editor/Typeahead';

/**
 * "@YYYY-MM-DD" dates in notes (B5.4), as the server reads them
 * (Markdown\Dates\DateChipExtension): starting a word, a real date.
 */
export const DATE_CHIP = /(?<![\p{L}\p{N}_.@])@(\d{4}-\d{2}-\d{2})(?![\w-])/gu;

export const isoDate = (date: Date): string =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

export const isRealDate = (iso: string): boolean => {
    const [year, month, day] = iso.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
};

const addDays = (date: Date, days: number): Date => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);

const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

export type DateSuggestion = { label: string; iso: string };

/**
 * Dates offered after "@": today, tomorrow, yesterday, next week and the
 * coming weekdays (a weekday that is today means next week's), filtered by
 * what's typed (the start of any word of the label).
 */
export const dateSuggestions = (query: string, today: Date = new Date()): DateSuggestion[] => {
    const all: DateSuggestion[] = [
        { label: 'Today', iso: isoDate(today) },
        { label: 'Tomorrow', iso: isoDate(addDays(today, 1)) },
        { label: 'Yesterday', iso: isoDate(addDays(today, -1)) },
        { label: 'Next week', iso: isoDate(addDays(today, 7)) },
        ...WEEKDAYS.map((day, index) => {
            const ahead = (index - today.getDay() + 7) % 7 || 7;

            return { label: day, iso: isoDate(addDays(today, ahead)) };
        }).sort((a, b) => a.iso.localeCompare(b.iso)),
    ];
    const needle = query.trim().toLowerCase();

    return needle === ''
        ? all
        : all.filter(
              (each) =>
                  each.label.toLowerCase().startsWith(needle) ||
                  each.label
                      .toLowerCase()
                      .split(' ')
                      .some((word) => word.startsWith(needle)),
          );
};

/**
 * A date being typed after "@" (letters only, so a typed-out date closes it).
 */
export const dateQuery = (textBefore: string): TypeaheadMatch<'@'> | null => {
    const match = /(?<![\p{L}\p{N}_.@])@([A-Za-z]{0,12}(?: [A-Za-z]{0,8})?)$/u.exec(textBefore);

    return match ? { trigger: '@', query: match[1], before: match[1].length + 1, after: 0 } : null;
};
