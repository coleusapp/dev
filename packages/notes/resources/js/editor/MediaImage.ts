import Image from '@tiptap/extension-image';
import { mergeAttributes } from '@tiptap/vue-3';

/**
 * Tiptap's image, also showing attached video, audio and PDFs (O3.4):
 * "![clip.mp4](/notes/attachments/…)" plays in the visual editor as in the
 * reading view, instead of a broken image. The Markdown is unchanged.
 */
export const MEDIA_KIND = (src: string): 'video' | 'audio' | 'pdf' | null => {
    const path = src.split(/[?#]/)[0];

    if (!path.startsWith('/notes/attachments/')) {
        return null;
    }

    return /\.(mp4|webm|mov|m4v|ogv)$/i.test(path)
        ? 'video'
        : /\.(mp3|m4a|aac|wav|ogg|oga|flac)$/i.test(path)
          ? 'audio'
          : /\.pdf$/i.test(path)
            ? 'pdf'
            : null;
};

export const MediaImage = Image.extend({
    renderHTML({ node, HTMLAttributes }) {
        const kind = MEDIA_KIND(String(node.attrs.src ?? ''));
        const attributes = mergeAttributes(this.options.HTMLAttributes, HTMLAttributes);

        if (kind === 'video') {
            const poster = `${String(attributes.src).split(/[?#]/)[0]}?poster=1`;

            return [
                'video',
                { src: attributes.src, poster, title: attributes.alt, controls: 'true', preload: 'metadata', class: 'attachment-video' },
            ];
        }

        if (kind === 'audio') {
            return ['audio', { src: attributes.src, title: attributes.alt, controls: 'true', preload: 'metadata', class: 'attachment-audio' }];
        }

        if (kind === 'pdf') {
            return ['iframe', { src: attributes.src, title: attributes.alt, class: 'attachment-pdf', loading: 'lazy' }];
        }

        return ['img', attributes];
    },
});
