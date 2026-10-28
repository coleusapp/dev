import { mergeAttributes, Node } from '@tiptap/vue-3';

/**
 * Footnotes (O5.2) in the visual editor. Without these the editor would
 * save "\[^1\]", and the Markdown lexer would even drop "[^1]: text" lines
 * as link reference definitions.
 *
 * FootnoteMarker: a reference "[^label]" as one chip.
 * FootnoteDefinition: a "[^label]: text" line as its own editable block.
 */
export const FOOTNOTE_MARKER = /^\[\^([^\]\s]{1,100})\](?!:)/;

export const FOOTNOTE_DEFINITION = /^\[\^([^\]\s]{1,100})\]:[ \t]*([^\n]*)(?:\n|$)/;

export const FootnoteMarker = Node.create({
    name: 'footnoteMarker',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,

    addAttributes() {
        return {
            label: { default: '1', parseHTML: (element) => element.getAttribute('data-footnote') ?? '1' },
        };
    },

    parseHTML() {
        return [{ tag: 'sup[data-footnote]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return ['sup', mergeAttributes(HTMLAttributes, { 'data-footnote': node.attrs.label, class: 'footnote-marker' }), node.attrs.label];
    },

    renderText({ node }) {
        return `[^${node.attrs.label}]`;
    },

    markdownTokenName: 'footnoteMarker',

    markdownTokenizer: {
        name: 'footnoteMarker',
        level: 'inline',
        start: (src: string) => src.indexOf('[^'),
        tokenize: (src: string) => {
            const match = FOOTNOTE_MARKER.exec(src);

            return match ? { type: 'footnoteMarker', raw: match[0], label: match[1] } : undefined;
        },
    },

    parseMarkdown: (token) => ({ type: 'footnoteMarker', attrs: { label: token.label } }),

    renderMarkdown: (node) => `[^${node.attrs?.label ?? '1'}]`,
});

export const FootnoteDefinition = Node.create({
    name: 'footnoteDefinition',
    group: 'block',
    content: 'inline*',
    defining: true,

    addAttributes() {
        return {
            label: { default: '1', parseHTML: (element) => element.getAttribute('data-footnote-definition') ?? '1' },
        };
    },

    parseHTML() {
        return [{ tag: 'p[data-footnote-definition]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return ['p', mergeAttributes(HTMLAttributes, { 'data-footnote-definition': node.attrs.label, class: 'footnote-definition' }), 0];
    },

    markdownTokenName: 'footnoteDefinition',

    markdownTokenizer: {
        name: 'footnoteDefinition',
        level: 'block',
        // Only a definition at a line start; anything wider would cut paragraphs at every reference.
        start: (src: string) => {
            const found = /(^|\n)\[\^[^\]\s]{1,100}\]:/.exec(src);

            return found ? found.index + found[1].length : -1;
        },
        tokenize: (src, _tokens, lexer) => {
            const match = FOOTNOTE_DEFINITION.exec(src);

            return match ? { type: 'footnoteDefinition', raw: match[0], label: match[1], tokens: lexer.inlineTokens(match[2]) } : undefined;
        },
    },

    parseMarkdown: (token, helpers) => ({
        type: 'footnoteDefinition',
        attrs: { label: token.label },
        content: helpers.parseInline(token.tokens ?? []),
    }),

    renderMarkdown: (node, helpers) => `[^${node.attrs?.label ?? '1'}]: ${helpers.renderChildren(node.content ?? [])}`,
});
