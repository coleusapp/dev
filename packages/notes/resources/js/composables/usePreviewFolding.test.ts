// @vitest-environment jsdom
import { applyPreviewFolds } from '@/composables/usePreviewFolding';
import { describe, expect, it } from 'vitest';

const render = (html: string): HTMLElement => {
    const container = document.createElement('article');
    container.innerHTML = html;
    return container;
};

const hidden = (container: HTMLElement): string[] =>
    Array.from(container.querySelectorAll('.folded-hidden')).map((element) => element.textContent ?? '');

describe('preview folding', () => {
    it('adds toggles to headings and items with sub-lists', () => {
        const container = render('<h1>A</h1><p>x</p><ul><li>trip<ul><li>pack</li></ul></li><li>plain</li></ul>');

        applyPreviewFolds(container, new Set());

        expect(container.querySelectorAll('h1 > .fold-toggle')).toHaveLength(1);
        expect(container.querySelectorAll('li > .fold-toggle')).toHaveLength(1);
    });

    it('hides a folded section up to the next heading of the same level', () => {
        const container = render('<h1>A</h1><p>one</p><h2>A1</h2><p>two</p><h1>B</h1><p>three</p>');
        applyPreviewFolds(container, new Set());
        const key = container.querySelector<HTMLElement>('h1 .fold-toggle')!.dataset.foldKey!;

        applyPreviewFolds(container, new Set([key]));

        expect(hidden(container)).toEqual(['one', 'A1', 'two']);
    });

    it('keeps the same keys after re-rendering, so folds survive', () => {
        const html = '<h2>Same</h2><p>a</p><h2>Same</h2><p>b</p>';
        const first = render(html);
        applyPreviewFolds(first, new Set());
        const secondKey = first.querySelectorAll<HTMLElement>('.fold-toggle')[1].dataset.foldKey!;

        const again = render(html);
        applyPreviewFolds(again, new Set([secondKey]));

        expect(hidden(again)).toEqual(['b']);
    });

    it('re-applying does not add duplicate toggles', () => {
        const container = render('<h1>A</h1><p>x</p>');
        applyPreviewFolds(container, new Set());
        applyPreviewFolds(container, new Set());

        expect(container.querySelectorAll('.fold-toggle')).toHaveLength(1);
    });
});
