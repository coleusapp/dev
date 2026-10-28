import { mergeAttributes, Node } from '@tiptap/vue-3';

/**
 * Math (O5.4) in the visual editor: $inline$ and $$block$$ TeX kept as
 * written, as uneditable chips showing the TeX (KaTeX renders it in the
 * reading view; edit it in source). Without these the editor escaped "_",
 * "*" and "\" and quietly broke formulas. Same rules as the server
 * (Markdown\Math): no space just inside the dollars, no digit after the
 * closing one, so prices stay text.
 */
export const MATH_INLINE = /^\$(?![\s$])((?:\\.|[^$\\\n])+?)(?<![\s\\])\$(?!\d)/;

export const MATH_BLOCK = /^\$\$[ \t]*\n([\s\S]*?)\n\$\$[ \t]*(?:\n|$)|^\$\$(.+?)\$\$[ \t]*(?:\n|$)/;

export const MathInline = Node.create({
    name: 'mathInline',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,

    addAttributes() {
        return { tex: { default: '', parseHTML: (element) => element.getAttribute('data-tex') ?? '' } };
    },

    parseHTML() {
        return [{ tag: 'span[data-tex]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { 'data-tex': node.attrs.tex, class: 'math-source' }), node.attrs.tex];
    },

    renderText({ node }) {
        return `$${node.attrs.tex}$`;
    },

    markdownTokenName: 'mathInline',

    markdownTokenizer: {
        name: 'mathInline',
        level: 'inline',
        start: (src: string) => src.indexOf('$'),
        tokenize: (src: string) => {
            const match = MATH_INLINE.exec(src);

            return match ? { type: 'mathInline', raw: match[0], tex: match[1] } : undefined;
        },
    },

    parseMarkdown: (token) => ({ type: 'mathInline', attrs: { tex: token.tex } }),

    renderMarkdown: (node) => `$${node.attrs?.tex ?? ''}$`,
});

export const MathBlock = Node.create({
    name: 'mathBlock',
    group: 'block',
    atom: true,
    selectable: true,

    addAttributes() {
        return {
            tex: { default: '', parseHTML: (element) => element.getAttribute('data-tex') ?? '' },
            oneLine: { default: false, parseHTML: (element) => element.hasAttribute('data-one-line') },
        };
    },

    parseHTML() {
        return [{ tag: 'pre[data-tex]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return [
            'pre',
            mergeAttributes(HTMLAttributes, {
                'data-tex': node.attrs.tex,
                ...(node.attrs.oneLine ? { 'data-one-line': '' } : {}),
                class: 'math-source math-source-block',
            }),
            node.attrs.tex,
        ];
    },

    markdownTokenName: 'mathBlock',

    markdownTokenizer: {
        name: 'mathBlock',
        level: 'block',
        // Only at a line start, so paragraphs aren't cut at every "$$".
        start: (src: string) => {
            const found = /(^|\n)\$\$/.exec(src);

            return found ? found.index + found[1].length : -1;
        },
        tokenize: (src: string) => {
            const match = MATH_BLOCK.exec(src);

            return match ? { type: 'mathBlock', raw: match[0], tex: match[1] ?? match[2], oneLine: match[1] === undefined } : undefined;
        },
    },

    parseMarkdown: (token) => ({ type: 'mathBlock', attrs: { tex: token.tex, oneLine: token.oneLine } }),

    renderMarkdown: (node) => (node.attrs?.oneLine ? `$$${node.attrs?.tex ?? ''}$$` : `$$\n${node.attrs?.tex ?? ''}\n$$`),
});
