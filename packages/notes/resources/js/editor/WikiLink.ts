import { InputRule, mergeAttributes, Node, PasteRule } from '@tiptap/vue-3';

/**
 * A [[Note title]] link as one inline, uneditable chip. It stores the text
 * between the brackets exactly, so saving writes back the same [[…]].
 * Clicking is handled by the editor component (navigate or offer to create).
 * [[Note title|shown text]] shows "shown text" and links to "Note title".
 */
export const WIKI_LINK = /^\[\[([^[\]\n]{1,255})\]\]/;

/**
 * The target and the shown text of what's between the brackets, as the
 * server splits it (WikiLinks::split): at the first "|", spaces collapsed
 * in the title; an empty alias shows the title.
 */
export const splitWikiLink = (inner: string): { title: string; label: string } => {
    const bar = inner.indexOf('|');
    const title = (bar === -1 ? inner : inner.slice(0, bar)).replace(/\s+/g, ' ').trim();
    const alias = bar === -1 ? '' : inner.slice(bar + 1).trim();

    return { title, label: alias || title };
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
        const { title, label } = splitWikiLink(node.attrs.title);

        return ['span', mergeAttributes(HTMLAttributes, { 'data-wiki-link': title, 'data-wiki-raw': node.attrs.title, class: 'wiki-link' }), label];
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
                    if (splitWikiLink(match[1]).title !== '') {
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
                    if (splitWikiLink(match[1]).title !== '') {
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

            if (!match || splitWikiLink(match[1]).title === '') {
                return undefined;
            }

            return { type: 'wikiLink', raw: match[0], title: match[1] };
        },
    },

    parseMarkdown: (token) => ({ type: 'wikiLink', attrs: { title: token.title } }),

    renderMarkdown: (node) => `[[${node.attrs?.title ?? ''}]]`,
});
