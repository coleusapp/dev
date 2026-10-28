import { InputRule, mergeAttributes, Node, PasteRule } from '@tiptap/vue-3';

/**
 * A [[Note title]] link as one inline, uneditable chip. It stores the text
 * between the brackets exactly, so saving writes back the same [[…]].
 * Clicking is handled by the editor component (navigate or offer to create).
 * [[Note title|shown text]] shows "shown text" and links to "Note title".
 */
export const WIKI_LINK = /^\[\[([^[\]\n]{1,255})\]\]/;

/**
 * The parts of what's between the brackets, as the server splits them
 * (WikiLinks::split / text): "Title#Heading|shown text". The title is ""
 * for a heading in the same note ([[#Heading]]); without an alias the
 * label is "Title", "Title > Heading" or "Heading".
 */
export const splitWikiLink = (inner: string): { title: string; heading: string | null; label: string } => {
    const normalize = (text: string): string => text.replace(/\s+/g, ' ').trim();
    const bar = inner.indexOf('|');
    const target = bar === -1 ? inner : inner.slice(0, bar);
    const hash = target.indexOf('#');
    const title = normalize(hash === -1 ? target : target.slice(0, hash));
    const heading = hash === -1 ? '' : normalize(target.slice(hash + 1));
    const alias = bar === -1 ? '' : inner.slice(bar + 1).trim();
    const fallback = heading === '' ? title : title === '' ? heading : `${title} > ${heading}`;

    return { title, heading: heading || null, label: alias || fallback };
};

/**
 * Whether the text between the brackets links anywhere (a note or a heading).
 */
const isLink = (inner: string): boolean => {
    const { title, heading } = splitWikiLink(inner);

    return title !== '' || heading !== null;
};

export const WikiLink = Node.create({
    name: 'wikiLink',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,

    addAttributes() {
        return {
            title: {
                default: '',
                parseHTML: (element) => element.getAttribute('data-wiki-raw') ?? element.getAttribute('data-wiki-link') ?? '',
            },
        };
    },

    parseHTML() {
        return [{ tag: 'span[data-wiki-link]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        const { title, heading, label } = splitWikiLink(node.attrs.title);
        const target = { 'data-wiki-link': title, ...(heading ? { 'data-wiki-heading': heading } : {}) };

        return ['span', mergeAttributes(HTMLAttributes, { ...target, 'data-wiki-raw': node.attrs.title, class: 'wiki-link' }), label];
    },

    renderText({ node }) {
        return `[[${node.attrs.title}]]`;
    },

    /**
     * Typing or pasting a complete [[Title]] turns it into a link chip;
     * otherwise it would stay bracket text and be saved escaped as \[\[…\]\].
     */
    addInputRules() {
        return [
            new InputRule({
                find: /\[\[([^[\]\n]{1,255})\]\]$/,
                handler: ({ state, range, match }) => {
                    if (isLink(match[1])) {
                        state.tr.replaceWith(range.from, range.to, this.type.create({ title: match[1] }));
                    }
                },
            }),
        ];
    },

    addPasteRules() {
        return [
            new PasteRule({
                find: /\[\[([^[\]\n]{1,255})\]\]/g,
                handler: ({ state, range, match }) => {
                    if (isLink(match[1])) {
                        state.tr.replaceWith(range.from, range.to, this.type.create({ title: match[1] }));
                    }
                },
            }),
        ];
    },

    markdownTokenName: 'wikiLink',

    markdownTokenizer: {
        name: 'wikiLink',
        level: 'inline',
        start: (src: string) => src.indexOf('[['),
        tokenize: (src: string) => {
            const match = WIKI_LINK.exec(src);

            if (!match || !isLink(match[1])) {
                return undefined;
            }

            return { type: 'wikiLink', raw: match[0], title: match[1] };
        },
    },

    parseMarkdown: (token) => ({ type: 'wikiLink', attrs: { title: token.title } }),

    renderMarkdown: (node) => `[[${node.attrs?.title ?? ''}]]`,
});
