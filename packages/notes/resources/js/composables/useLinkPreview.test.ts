// @vitest-environment jsdom
import { HIDE_DELAY, previewTarget, SHOW_DELAY, useLinkPreview } from '@/composables/useLinkPreview';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const urls: string[] = [];

beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal(
        'route',
        (name: string, params: Record<string, string | number>) => `/${name}?${new URLSearchParams(params as Record<string, string>)}`,
    );
    vi.stubGlobal(
        'fetch',
        vi.fn(async (url: string) => {
            urls.push(url);

            return { ok: true, json: async () => ({ data: url.includes('Nowhere') ? null : { id: 4, title: 'Trip', html: '<p>x</p>' } }) };
        }),
    );
    document.body.innerHTML = `
        <a class="wiki-link" data-note-id="4" id="resolved"><b id="inside">Trip</b></a>
        <a class="wiki-link wiki-link-missing" data-wiki-title="Someday" id="missing">Someday</a>
        <span class="wiki-link" data-wiki-link="Trip" id="chip">Trip</span>
        <span class="wiki-link" data-wiki-link="" data-wiki-heading="Top" id="own">Top</span>
        <span data-wiki-link="Nowhere" id="nowhere">Nowhere</span>
        <p id="text">text</p>`;
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    urls.length = 0;
});

const element = (id: string): Element => document.getElementById(id)!;
const over = (target: Element, relatedTarget: Element | null = null) => ({ target, relatedTarget }) as unknown as MouseEvent;

describe('previewTarget', () => {
    it('finds resolved links by id and chips by title, nothing else', () => {
        expect(previewTarget(element('inside'))).toMatchObject({ key: 'id:4', query: { note: 4 } });
        expect(previewTarget(element('chip'))).toMatchObject({ key: 'title:trip', query: { title: 'Trip' } });
        expect(previewTarget(element('missing'))).toBeNull();
        expect(previewTarget(element('own'))).toBeNull();
        expect(previewTarget(element('text'))).toBeNull();
    });
});

describe('useLinkPreview', () => {
    it('shows the card after the pointer rests on a link, once per link', async () => {
        const preview = useLinkPreview({ from: () => 9 });

        preview.onOver(over(element('chip')));
        await vi.advanceTimersByTimeAsync(SHOW_DELAY - 1);
        expect(preview.card.value).toBeNull();

        await vi.advanceTimersByTimeAsync(1);
        expect(preview.card.value).toMatchObject({ id: 4, title: 'Trip' });
        expect(urls).toEqual(['/notes.links.preview?title=Trip&from=9']);

        preview.close();
        preview.onOver(over(element('chip')));
        await vi.advanceTimersByTimeAsync(SHOW_DELAY);
        expect(urls).toHaveLength(1);
    });

    it('hides after the pointer leaves, unless it moves onto the card', async () => {
        const preview = useLinkPreview({ from: () => 9 });
        preview.onOver(over(element('resolved')));
        await vi.advanceTimersByTimeAsync(SHOW_DELAY);

        preview.onOut(over(element('inside'), element('resolved')));
        await vi.advanceTimersByTimeAsync(HIDE_DELAY);
        expect(preview.card.value).not.toBeNull();

        preview.onOut(over(element('resolved'), element('text')));
        preview.keep();
        await vi.advanceTimersByTimeAsync(HIDE_DELAY);
        expect(preview.card.value).not.toBeNull();

        preview.hide();
        await vi.advanceTimersByTimeAsync(HIDE_DELAY);
        expect(preview.card.value).toBeNull();
    });

    it('shows nothing for links that lead nowhere, or when the pointer left in time', async () => {
        const preview = useLinkPreview({ from: () => 9 });

        preview.onOver(over(element('nowhere')));
        await vi.advanceTimersByTimeAsync(SHOW_DELAY);
        expect(preview.card.value).toBeNull();

        preview.onOver(over(element('chip')));
        preview.onOut(over(element('chip'), element('text')));
        await vi.advanceTimersByTimeAsync(SHOW_DELAY);
        expect(preview.card.value).toBeNull();
    });
});
