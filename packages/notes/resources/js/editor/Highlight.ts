import Highlight from '@tiptap/extension-highlight';

/**
 * Apple Notes' highlight colours, matching Markdown\HighlightColors::COLORS on the server.
 */
export const HIGHLIGHT_COLORS = ['pink', 'purple', 'orange', 'mint', 'blue'] as const;

export type HighlightColor = (typeof HIGHLIGHT_COLORS)[number];

const HIGHLIGHT = new RegExp(`^==([^=]+)==(?:\\{(${HIGHLIGHT_COLORS.join('|')})\\})?`);

/**
 * ==text== highlights with an optional colour suffix: ==text=={pink}.
 */
export const NoteHighlight = Highlight.extend({
    addAttributes() {
        return {
            color: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-color'),
                renderHTML: (attributes) => (attributes.color ? { 'data-color': attributes.color, class: `mark-${attributes.color}` } : {}),
            },
        };
    },

    markdownTokenizer: {
        name: 'highlight',
        level: 'inline',
        start: (src: string) => src.indexOf('=='),
        tokenize: (src, _tokens, helpers) => {
            const match = HIGHLIGHT.exec(src);

            if (!match) {
                return undefined;
            }

            const text = match[1].trim();

            return { type: 'highlight', raw: match[0], text, color: match[2] ?? null, tokens: helpers.inlineTokens(text) };
        },
    },

    parseMarkdown: (token, helpers) =>
        helpers.applyMark('highlight', helpers.parseInline(token.tokens || []), token.color ? { color: token.color } : {}),

    renderMarkdown: (node, helpers) => `==${helpers.renderChildren(node)}==${node.attrs?.color ? `{${node.attrs.color}}` : ''}`,
});
