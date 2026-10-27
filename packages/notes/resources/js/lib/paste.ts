import type { Selection, TextEdit } from '@/lib/markdownFormat';

const URL_ONLY = /^(https?:\/\/|mailto:)\S+$/i;

/**
 * Pasting a single URL while text is selected links the text:
 * "[selected](url)", with the cursor after the link.
 */
export const pasteUrlOverSelection = ({ text, start, end }: Selection, pasted: string): TextEdit | null => {
    const url = pasted.trim();

    if (start === end || !URL_ONLY.test(url) || text.slice(start, end).includes('\n')) {
        return null;
    }

    const insert = `[${text.slice(start, end)}](${url})`;

    return { from: start, to: end, insert, selectionStart: start + insert.length, selectionEnd: start + insert.length };
};
