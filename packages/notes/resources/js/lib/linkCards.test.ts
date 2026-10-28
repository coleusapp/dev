// @vitest-environment jsdom
import { renderLinkCards } from '@/lib/linkCards';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

beforeEach(() => {
    vi.stubGlobal('route', (_name: string, params: { url: string }) => `/preview?url=${params.url}`);
    vi.stubGlobal(
        'fetch',
        vi.fn(async (url: string) => ({
            ok: true,
            json: async () => ({
                data: url.includes('unknown')
                    ? null
                    : {
                          url: 'https://site.test/a',
                          title: 'A <b>title</b>',
                          description: 'About <script>x</script>',
                          image: 'https://site.test/i.png',
                          site_name: 'Site',
                      },
            }),
        })),
    );
});

afterEach(() => vi.unstubAllGlobals());

describe('renderLinkCards', () => {
    it('turns marked links into cards, with the page text kept as text', async () => {
        const root = document.body.appendChild(document.createElement('div'));
        root.innerHTML = '<p><a data-link-preview href="https://site.test/a">https://site.test/a</a></p><p><a href="https://site.test/b">b</a></p>';

        await renderLinkCards(root);

        const card = root.querySelector<HTMLAnchorElement>('a.link-card')!;
        expect(card.href).toBe('https://site.test/a');
        expect(card.rel).toBe('noopener noreferrer');
        expect(card.querySelector('.link-card-title')?.textContent).toBe('A <b>title</b>');
        expect(card.querySelector('script')).toBeNull();
        expect(card.querySelector('img')?.getAttribute('referrerpolicy')).toBe('no-referrer');
        expect(root.querySelector('a[href="https://site.test/b"]')?.classList.contains('link-card')).toBe(false);
    });

    it('leaves a link alone when its page cannot be read, and asks once per address', async () => {
        const root = document.body.appendChild(document.createElement('div'));
        root.innerHTML =
            '<p><a data-link-preview href="https://unknown.test/">https://unknown.test/</a></p><p><a data-link-preview href="https://unknown.test/">again</a></p>';

        await renderLinkCards(root);
        await renderLinkCards(root);

        expect(root.querySelectorAll('a.link-card')).toHaveLength(0);
        expect(vi.mocked(fetch)).toHaveBeenCalledTimes(1);
    });
});
