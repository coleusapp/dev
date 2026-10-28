import { AutoPair } from '@/editor/AutoPair';
import { CalloutMarker } from '@/editor/CalloutMarker';
import { CheckedToBottom } from '@/editor/checklists';
import { DateChips } from '@/editor/DateChips';
import { Find } from '@/editor/Find';
import { Folding } from '@/editor/Folding';
import { FootnoteDefinition, FootnoteMarker } from '@/editor/FootnoteMarker';
import { Hashtags } from '@/editor/Hashtags';
import { NoteHighlight } from '@/editor/Highlight';
import { LineCommands } from '@/editor/LineCommands';
import { MathBlock, MathInline } from '@/editor/Math';
import { MediaImage } from '@/editor/MediaImage';
import { TableOfContents } from '@/editor/TableOfContents';
import { WikiLink } from '@/editor/WikiLink';
import Link from '@tiptap/extension-link';
import { TaskItem, TaskList } from '@tiptap/extension-list';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table';
import { Markdown } from '@tiptap/markdown';
import StarterKit from '@tiptap/starter-kit';
import type { AnyExtension } from '@tiptap/vue-3';

/**
 * Bare URLs and e-mail addresses (GFM autolinks) stay plain text: turning
 * them into link marks would write them back as [x](x) and change the
 * note. The reading view links them anyway. Typing doesn't autolink either,
 * for the same reason; explicit [text](url) links work as usual.
 */
const NoteLink = Link.extend({
    parseMarkdown: (token, helpers) => {
        if (typeof token.raw === 'string' && !token.raw.startsWith('[')) {
            return helpers.createTextNode(token.raw);
        }

        return helpers.applyMark('link', helpers.parseInline(token.tokens || []), { href: token.href, title: token.title || null });
    },
}).configure({ openOnClick: false, autolink: false, linkOnPaste: true });

export type EditorExtensionOptions = {
    placeholder?: string;
    checkedToBottom?: () => boolean;
};

export const editorExtensions = ({
    placeholder = 'Start writing...',
    checkedToBottom = () => false,
}: EditorExtensionOptions = {}): AnyExtension[] => [
    StarterKit.configure({ link: false }),
    NoteLink,
    MediaImage.configure({ inline: true, allowBase64: false }),
    TaskList,
    TaskItem.configure({ nested: true }),
    NoteHighlight,
    TableKit,
    WikiLink,
    TableOfContents,
    CalloutMarker,
    FootnoteMarker,
    FootnoteDefinition,
    MathInline,
    MathBlock,
    Hashtags,
    DateChips,
    Placeholder.configure({ placeholder }),
    CheckedToBottom.configure({ enabled: checkedToBottom }),
    AutoPair,
    LineCommands,
    Find,
    Folding,
    Markdown,
];
