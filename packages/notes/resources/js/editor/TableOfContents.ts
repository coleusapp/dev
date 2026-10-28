import { mergeAttributes, Node } from '@tiptap/vue-3';

/**
 * A "[TOC]" line: the reading view puts the note's table of contents there
 * (NoteMarkdown). In the visual editor it's one uneditable block, so saving
 * writes back "[TOC]" rather than the escaped text "\[TOC\]".
 */
export const TABLE_OF_CONTENTS = /^ {0,3}\[TOC\][ \t]*(?:\n|$)/;

export const TableOfContents = Node.create({
    name: 'tableOfContents',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    parseHTML() {
        return [{ tag: 'div[data-table-of-contents]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(HTMLAttributes, { 'data-table-of-contents': '', class: 'table-of-contents-placeholder' }),
            'Table of contents',
        ];
    },

    renderText() {
        return '[TOC]';
    },

    markdownTokenName: 'tableOfContents',

    markdownTokenizer: {
        name: 'tableOfContents',
        level: 'block',
        start: (src: string) => src.indexOf('[TOC]'),
        tokenize: (src: string) => {
            const match = TABLE_OF_CONTENTS.exec(src);

            return match ? { type: 'tableOfContents', raw: match[0] } : undefined;
        },
    },

    parseMarkdown: () => ({ type: 'tableOfContents' }),

    renderMarkdown: () => '[TOC]',
});
