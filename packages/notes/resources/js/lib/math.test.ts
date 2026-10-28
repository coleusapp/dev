// @vitest-environment jsdom
import { renderMath } from '@/lib/math';
import { describe, expect, it } from 'vitest';

describe('renderMath', () => {
    it('renders inline and block math once, leaving other text alone', async () => {
        const root = document.body.appendChild(document.createElement('div'));
        root.innerHTML =
            '<p>Area <span class="math math-inline" data-tex="\\pi r^2">$\\pi r^2$</span></p><div class="math math-block" data-tex="\\frac12">$$\\frac12$$</div><p>$5</p>';

        await renderMath(root);

        const [inline, block] = Array.from(root.querySelectorAll<HTMLElement>('.math'));
        expect(inline.querySelector('.katex')).not.toBeNull();
        expect(inline.querySelector('.katex-display')).toBeNull();
        expect(block.querySelector('.katex-display')).not.toBeNull();
        expect(root.lastElementChild?.textContent).toBe('$5');

        const html = root.innerHTML;
        await renderMath(root);
        expect(root.innerHTML).toBe(html);
    });

    it('shows bad TeX as an error instead of throwing, and refuses untrusted commands', async () => {
        const root = document.body.appendChild(document.createElement('div'));
        root.innerHTML =
            '<span class="math math-inline" data-tex="\\frac{"></span><span class="math math-inline" data-tex="\\href{javascript:alert(1)}{x}"></span>';

        await expect(renderMath(root)).resolves.toBeUndefined();
        expect(root.querySelectorAll('[data-rendered]')).toHaveLength(2);
        expect(root.querySelector('a[href^="javascript"]')).toBeNull();
    });

    it('does nothing without math', async () => {
        await expect(renderMath(null)).resolves.toBeUndefined();
    });
});
