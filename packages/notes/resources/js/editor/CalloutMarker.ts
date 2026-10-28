import { mergeAttributes, Node } from '@tiptap/vue-3';

/**
 * The "[!type]" (plus "+"/"-" to fold) that starts a callout blockquote
 * (O5.1), as one uneditable chip. Without it the visual editor would save
 * "\[!note\]" and the callout would turn back into a quote.
 */
export const CALLOUT_MARKER = /^\[!([A-Za-z][\w-]*)\]([+-]?)/;

export const CalloutMarker = Node.create({
    name: 'calloutMarker',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,

    addAttributes() {
        return {
            type: { default: 'note', parseHTML: (element) => element.getAttribute('data-callout') ?? 'note' },
            fold: { default: '', parseHTML: (element) => element.getAttribute('data-fold') ?? '' },
        };
    },

    parseHTML() {
        return [{ tag: 'span[data-callout]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        const label = node.attrs.type.charAt(0).toUpperCase() + node.attrs.type.slice(1).toLowerCase();
        const fold = node.attrs.fold === '-' ? ' ▸' : node.attrs.fold === '+' ? ' ▾' : '';

        return [
            'span',
            mergeAttributes(HTMLAttributes, { 'data-callout': node.attrs.type, 'data-fold': node.attrs.fold, class: 'callout-marker' }),
            `${label}${fold}`,
        ];
    },

    renderText({ node }) {
        return `[!${node.attrs.type}]${node.attrs.fold}`;
    },

    markdownTokenName: 'calloutMarker',

    markdownTokenizer: {
        name: 'calloutMarker',
        level: 'inline',
        start: (src: string) => src.indexOf('[!'),
        tokenize: (src: string) => {
            const match = CALLOUT_MARKER.exec(src);

            return match ? { type: 'calloutMarker', raw: match[0], calloutType: match[1], fold: match[2] } : undefined;
        },
    },

    parseMarkdown: (token) => ({ type: 'calloutMarker', attrs: { type: token.calloutType, fold: token.fold } }),

    renderMarkdown: (node) => `[!${node.attrs?.type ?? 'note'}]${node.attrs?.fold ?? ''}`,
});
