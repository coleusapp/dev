import { splitFrontMatter } from '@/editor/frontMatter';

/**
 * A heading in a note's Markdown: its level (1–6), its plain text, where its
 * line starts in the Markdown, and which occurrence of that same text it is
 * (so two "Notes" headings can be told apart in the rendered note).
 */
export type OutlineHeading = { level: number; text: string; offset: number; occurrence: number };

/**
 * The note's ATX headings ("## Text") in order, skipping front matter and
 * fenced code. Inline markers (**, _, `, ~~, ==, ++) are dropped from the text.
 */
export const noteOutline = (markdown: string): OutlineHeading[] => {
    const { frontMatter } = splitFrontMatter(markdown);
    const headings: OutlineHeading[] = [];
    const seen = new Map<string, number>();
    let offset = frontMatter.length;
    let fence: string | null = null;

    for (const line of markdown.slice(frontMatter.length).split('\n')) {
        const fenceMatch = /^\s{0,3}(`{3,}|~{3,})/.exec(line);

        if (fenceMatch) {
            const marker = fenceMatch[1];

            if (fence === null) {
                fence = marker;
            } else if (marker[0] === fence[0] && marker.length >= fence.length) {
                fence = null;
            }
        } else if (fence === null) {
            const match = /^\s{0,3}(#{1,6})\s+(.+?)\s*#*\s*$/.exec(line);
            const text = match?.[2].replace(/[*_`~=]|\+\+/g, '').trim();

            if (match && text) {
                const occurrence = seen.get(text) ?? 0;
                seen.set(text, occurrence + 1);
                headings.push({ level: match[1].length, text, offset, occurrence });
            }
        }

        offset += line.length + 1;
    }

    return headings;
};
