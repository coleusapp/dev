import { jsonHeaders } from '@/lib/http';
import { ref } from 'vue';

export type LinkPreview = { id: number; title: string; html: string };

export type OpenLinkPreview = LinkPreview & { left: number; top: number };

/**
 * How long a pointer rests on a link before its preview shows, and how long
 * the card stays after the pointer leaves (to let it move onto the card).
 */
export const SHOW_DELAY = 400;
export const HIDE_DELAY = 200;

/**
 * Which note a hovered element links to: a reading-view link carries the
 * resolved note's id, a visual-editor chip its title. Links to missing
 * notes and to headings in the same note have nothing to preview.
 */
export const previewTarget = (element: Element | null): { key: string; query: Record<string, string | number>; anchor: HTMLElement } | null => {
    const link = element?.closest<HTMLElement>('a.wiki-link[data-note-id], [data-wiki-link]') ?? null;

    if (!link) {
        return null;
    }

    if (link.dataset.noteId) {
        return { key: `id:${link.dataset.noteId}`, query: { note: Number(link.dataset.noteId) }, anchor: link };
    }

    const title = link.dataset.wikiLink ?? '';

    return title === '' ? null : { key: `title:${title.toLowerCase()}`, query: { title }, anchor: link };
};

/**
 * Hover previews of linked notes (O2.10): resting the pointer on a wiki
 * link shows the target note rendered in a card under it. Previews are
 * cached per editor (null for links that lead nowhere).
 */
export const useLinkPreview = (options: { from: () => number }) => {
    const card = ref<OpenLinkPreview | null>(null);
    const cache = new Map<string, LinkPreview | null>();
    let showTimer: ReturnType<typeof setTimeout> | undefined;
    let hideTimer: ReturnType<typeof setTimeout> | undefined;
    let pending: string | null = null;

    const fetchPreview = async (key: string, query: Record<string, string | number>): Promise<LinkPreview | null> => {
        if (!cache.has(key)) {
            const response = await fetch(route('notes.links.preview', { ...query, from: options.from() }), {
                headers: jsonHeaders(),
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => null);
            cache.set(key, response.ok ? (payload?.data ?? null) : null);
        }

        return cache.get(key) ?? null;
    };

    const keep = (): void => clearTimeout(hideTimer);

    const hide = (): void => {
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        pending = null;
        hideTimer = setTimeout(() => (card.value = null), HIDE_DELAY);
    };

    const onOver = (event: MouseEvent): void => {
        const target = previewTarget(event.target instanceof Element ? event.target : null);

        if (!target) {
            return;
        }

        keep();

        if (pending === target.key) {
            return;
        }

        clearTimeout(showTimer);
        pending = target.key;
        showTimer = setTimeout(async () => {
            const preview = await fetchPreview(target.key, target.query);

            if (preview && pending === target.key) {
                const box = target.anchor.getBoundingClientRect();
                // Card is 24rem wide: keep it on screen.
                card.value = { ...preview, left: Math.max(16, Math.min(box.left, window.innerWidth - 400)), top: box.bottom };
            }
        }, SHOW_DELAY);
    };

    const onOut = (event: MouseEvent): void => {
        const from = previewTarget(event.target instanceof Element ? event.target : null);
        const to = previewTarget(event.relatedTarget instanceof Element ? event.relatedTarget : null);

        if (from && from.key !== to?.key) {
            hide();
        }
    };

    /**
     * Closes at once (a click, a scroll, a key).
     */
    const close = (): void => {
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        pending = null;
        card.value = null;
    };

    return { card, onOver, onOut, keep, hide, close };
};
