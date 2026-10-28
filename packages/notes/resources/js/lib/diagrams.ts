let nextId = 0;

/**
 * Draws ```mermaid code blocks inside root as diagrams (O5.5). Mermaid
 * loads on first use (notes without diagrams never fetch it) and runs at
 * its "strict" security level, which sanitises labels and disables
 * clicks and scripts. A diagram Mermaid can't read keeps its source, with
 * the error under it.
 */
export const renderDiagrams = async (root: HTMLElement | null | undefined): Promise<void> => {
    const pending = Array.from(root?.querySelectorAll<HTMLElement>('pre > code.language-mermaid:not([data-rendered])') ?? []);

    if (!pending.length) {
        return;
    }

    const { default: mermaid } = await import('mermaid');
    const dark = document.documentElement.classList.contains('dark');

    mermaid.initialize({ startOnLoad: false, securityLevel: 'strict', theme: dark ? 'dark' : 'default' });

    for (const code of pending) {
        const block = code.parentElement;

        if (!block?.isConnected || code.hasAttribute('data-rendered')) {
            continue;
        }

        code.setAttribute('data-rendered', '');

        try {
            const { svg } = await mermaid.render(`note-diagram-${nextId++}`, code.textContent ?? '');
            const figure = document.createElement('div');
            figure.className = 'mermaid-diagram';
            figure.innerHTML = svg;
            block.replaceWith(figure);
        } catch (error) {
            const message = document.createElement('p');
            message.className = 'mermaid-error';
            message.textContent = `Couldn't draw this diagram: ${error instanceof Error ? error.message : String(error)}`;
            block.after(message);
        }
    }
};
