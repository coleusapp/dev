import { mergeAttributes, Node } from '@tiptap/vue-3';

/**
 * A [[Note title]] link as one inline, uneditable chip. It stores the text
 * between the brackets exactly, so saving writes back the same [[…]].
 * Clicking is handled by the editor component (navigate or offer to create).
 */
export const WIKI_LINK = /^\[\[([^[\]\n]{1,255})\]\]/;

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
                parseHTML: (element) => element.getAttribute('data-wiki-link') ?? '',
            },
        };
    },

    parseHTML() {
        return [{ tag: 'span[data-wiki-link]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { 'data-wiki-link': node.attrs.title, class: 'wiki-link' }), node.attrs.title.trim()];
    },

    renderText({ node }) {
        return `[[${node.attrs.title}]]`;
    },

    markdownTokenName: 'wikiLink',

    markdownTokenizer: {
        name: 'wikiLink',
        level: 'inline',
        start: (src: string) => src.indexOf('[['),
        tokenize: (src: string) => {
            const match = WIKI_LINK.exec(src);

            if (!match || match[1].trim() === '') {
                return undefined;
            }

            return { type: 'wikiLink', raw: match[0], title: match[1] };
        },
    },

    parseMarkdown: (token) => ({ type: 'wikiLink', attrs: { title: token.title } }),

    renderMarkdown: (node) => `[[${node.attrs?.title ?? ''}]]`,
});
