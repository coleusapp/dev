/**
 * Renders the server's math placeholders (.math[data-tex], see
 * Markdown\Math) inside root with KaTeX (O5.4). KaTeX and its styles load
 * on first use, so notes without math never fetch them. Untrusted
 * commands (\href, \url…) stay off; a formula KaTeX can't read shows in
 * red instead of throwing.
 */
export const renderMath = async (root: HTMLElement | null | undefined): Promise<void> => {
    const pending = Array.from(root?.querySelectorAll<HTMLElement>('.math[data-tex]:not([data-rendered])') ?? []);

    if (!pending.length) {
        return;
    }

    const [{ default: katex }] = await Promise.all([import('katex'), import('katex/dist/katex.min.css')]);

    for (const element of pending) {
        if (!element.isConnected || element.hasAttribute('data-rendered')) {
            continue;
        }

        katex.render(element.dataset.tex ?? '', element, {
            displayMode: element.classList.contains('math-block'),
            throwOnError: false,
            trust: false,
        });
        element.setAttribute('data-rendered', '');
    }
};
