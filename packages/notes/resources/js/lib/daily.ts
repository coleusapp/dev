import { joinFrontMatter, splitFrontMatter } from '@/editor/frontMatter';
import { fillTemplate, formatDate } from '@/lib/templates';
import { titleOf } from '@/lib/title';

/**
 * What a new daily note starts with (O9.1): the "Daily note" template
 * from the Templates folder filled in for that day, under a title in the
 * chosen format (added unless the template already makes it the title),
 * or just the title.
 */
export const dailyBody = (date: Date, titleFormat: string, template: string | null, locale?: string): string => {
    const title = formatDate(date, titleFormat, locale);

    if (template === null) {
        return `# ${title}\n`;
    }

    const filled = fillTemplate(template, { title, now: date, locale });

    if (titleOf(filled) === title) {
        return filled;
    }

    const { frontMatter, body } = splitFrontMatter(filled);

    return joinFrontMatter(frontMatter, `# ${title}\n\n${body.replace(/^\n+/, '')}`);
};

/**
 * The template a daily note uses: the one titled "Daily note" (any case).
 */
export const dailyTemplate = (templates: { title: string; body: string }[]): string | null =>
    templates.find((template) => template.title.trim().toLowerCase() === 'daily note')?.body ?? null;
