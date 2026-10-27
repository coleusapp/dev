/**
 * YAML front matter ("---" block at the very top) is kept out of the visual
 * editor and put back unchanged when the note is saved.
 */
export type SplitMarkdown = {
    frontMatter: string;
    body: string;
};

const FRONT_MATTER = /^---\r?\n[\s\S]*?\r?\n(?:---|\.\.\.)[ \t]*(?:\r?\n|$)/;

export const splitFrontMatter = (markdown: string): SplitMarkdown => {
    const match = FRONT_MATTER.exec(markdown);

    return match ? { frontMatter: match[0], body: markdown.slice(match[0].length) } : { frontMatter: '', body: markdown };
};

export const joinFrontMatter = (frontMatter: string, body: string): string => {
    if (frontMatter === '') {
        return body;
    }

    return frontMatter.endsWith('\n') ? frontMatter + body : `${frontMatter}\n${body}`;
};
