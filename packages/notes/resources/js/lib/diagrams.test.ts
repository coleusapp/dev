// @vitest-environment jsdom
import { renderDiagrams } from '@/lib/diagrams';
import { describe, expect, it, vi } from 'vitest';

const initialize = vi.fn();

vi.mock('mermaid', () => ({
    default: {
        initialize: (options: unknown) => initialize(options),
        render: async (_id: string, source: string) => {
            if (source.includes('broken')) {
                throw new Error('Parse error');
            }

            return { svg: `<svg data-source="${source.trim()}"></svg>` };
        },
    },
}));

describe('renderDiagrams', () => {
    it('replaces mermaid blocks with diagrams, strictly, leaving other code alone', async () => {
        const root = document.body.appendChild(document.createElement('div'));
        root.innerHTML = '<pre><code class="language-mermaid">graph TD; A--&gt;B</code></pre><pre><code class="language-js">x</code></pre>';

        await renderDiagrams(root);

        expect(root.querySelector('.mermaid-diagram svg')?.getAttribute('data-source')).toBe('graph TD; A-->B');
        expect(root.querySelector('code.language-js')).not.toBeNull();
        expect(initialize).toHaveBeenCalledWith(expect.objectContaining({ securityLevel: 'strict', startOnLoad: false }));
    });

    it('keeps the source and explains when a diagram cannot be drawn', async () => {
        const root = document.body.appendChild(document.createElement('div'));
        root.innerHTML = '<pre><code class="language-mermaid">broken</code></pre>';

        await renderDiagrams(root);
        await renderDiagrams(root);

        expect(root.querySelector('code.language-mermaid')?.textContent).toBe('broken');
        expect(root.querySelectorAll('.mermaid-error')).toHaveLength(1);
        expect(root.querySelector('.mermaid-error')?.textContent).toContain('Parse error');
    });

    it('does nothing without diagrams', async () => {
        initialize.mockClear();

        await renderDiagrams(document.createElement('div'));

        expect(initialize).not.toHaveBeenCalled();
    });
});
