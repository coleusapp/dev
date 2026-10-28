import { jsonHeaders } from '@/lib/http';

type Preview = { url: string; title: string | null; description: string | null; image: string | null; site_name: string | null };

/**
 * Asked once per page load per address, whoever shows it.
 */
const previews = new Map<string, Promise<Preview | null>>();

const load = (url: string): Promise<Preview | null> => {
    if (!previews.has(url)) {
        previews.set(
            url,
            fetch(route('notes.link-preview', { url }), { headers: jsonHeaders(), credentials: 'same-origin' })
                .then((response) => (response.ok ? response.json() : null))
                .then((payload) => payload?.data ?? null)
                .catch(() => null),
        );
    }

    return previews.get(url)!;
};

const element = (tag: string, className: string, text?: string | null): HTMLElement => {
    const node = document.createElement(tag);
    node.className = className;

    if (text) {
        node.textContent = text;
    }

    return node;
};

/**
 * Turns links the server marked data-link-preview (an address alone on its
 * line) into cards with the page's title, description, image and site
 * (A4.1). Everything from the other site goes in as text, never HTML; its
 * image loads without a referrer. A link whose page can't be read stays
 * a plain link.
 */
export const renderLinkCards = async (root: HTMLElement | null | undefined): Promise<void> => {
    const links = Array.from(root?.querySelectorAll<HTMLAnchorElement>('a[data-link-preview]:not([data-card])') ?? []);

    await Promise.all(
        links.map(async (link) => {
            link.setAttribute('data-card', '');
            const preview = await load(link.href);

            if (!preview || !link.isConnected) {
                return;
            }

            const card = element('a', 'link-card') as HTMLAnchorElement;
            card.href = link.href;
            card.target = '_blank';
            card.rel = 'noopener noreferrer';
            card.setAttribute('data-card', '');

            if (preview.image) {
                const image = element('img', 'link-card-image') as HTMLImageElement;
                image.src = preview.image;
                image.alt = '';
                image.loading = 'lazy';
                image.setAttribute('referrerpolicy', 'no-referrer');
                image.addEventListener('error', () => image.remove());
                card.append(image);
            }

            const text = element('span', 'link-card-text');
            text.append(element('span', 'link-card-title', preview.title ?? link.href));

            if (preview.description) {
                text.append(element('span', 'link-card-description', preview.description));
            }

            text.append(element('span', 'link-card-site', preview.site_name ?? new URL(link.href).host));
            card.append(text);
            link.replaceWith(card);
        }),
    );
};
