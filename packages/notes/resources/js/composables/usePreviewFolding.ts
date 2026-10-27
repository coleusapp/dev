import { nextTick, ref, watch, type Ref } from 'vue';

const HEADING = /^H([1-6])$/;

/**
 * A stable name for a foldable element, so folds survive the preview
 * being re-rendered after each autosave: tag + text + which occurrence.
 */
const foldKeyOf = (element: Element, seen: Map<string, number>): string => {
    const base = `${element.tagName}:${(element.firstChild?.textContent ?? element.textContent ?? '').trim()}`;
    const occurrence = seen.get(base) ?? 0;
    seen.set(base, occurrence + 1);

    return `${base}#${occurrence}`;
};

const makeToggle = (key: string, folded: boolean, label: string): HTMLButtonElement => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `fold-toggle${folded ? ' is-folded' : ''}`;
    button.dataset.foldKey = key;
    button.setAttribute('aria-label', folded ? `Expand ${label}` : `Collapse ${label}`);
    button.setAttribute('aria-expanded', String(!folded));

    return button;
};

/**
 * Adds fold toggles to the rendered note and applies the folds in
 * `folded`: blocks under a folded heading (until the next heading of the
 * same or a higher level) and sub-lists of folded items are hidden.
 */
export const applyPreviewFolds = (container: HTMLElement, folded: Set<string>): void => {
    container.querySelectorAll('.fold-toggle').forEach((toggle) => toggle.remove());
    container.querySelectorAll('.folded-hidden, .is-folded').forEach((element) => element.classList.remove('folded-hidden', 'is-folded'));

    const seen = new Map<string, number>();
    let hideUntilLevel: number | null = null;

    Array.from(container.children).forEach((child) => {
        const level = HEADING.exec(child.tagName)?.[1];

        if (level && (hideUntilLevel === null || Number(level) <= hideUntilLevel)) {
            hideUntilLevel = null;
            const key = foldKeyOf(child, seen);
            const isFolded = folded.has(key);
            child.prepend(makeToggle(key, isFolded, 'section'));

            if (isFolded) {
                child.classList.add('is-folded');
                hideUntilLevel = Number(level);
            }

            return;
        }

        if (hideUntilLevel !== null) {
            child.classList.add('folded-hidden');
        }
    });

    container.querySelectorAll('li').forEach((item) => {
        if (!item.querySelector(':scope > ul, :scope > ol')) {
            return;
        }

        const key = foldKeyOf(item, seen);
        const isFolded = folded.has(key);
        item.prepend(makeToggle(key, isFolded, 'item'));
        item.classList.toggle('is-folded', isFolded);
    });
};

/**
 * Folding for the server-rendered preview. Folds are kept for the session
 * while the note is open and re-applied whenever the preview re-renders.
 */
export const usePreviewFolding = (preview: Readonly<Ref<HTMLElement | null>>, html: Ref<string>) => {
    const folded = ref(new Set<string>());

    const apply = (): void => {
        if (preview.value) {
            applyPreviewFolds(preview.value, folded.value);
        }
    };

    const onClick = (event: Event): void => {
        const toggle = event.target instanceof Element ? event.target.closest<HTMLElement>('.fold-toggle') : null;

        if (!toggle?.dataset.foldKey) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        const next = new Set(folded.value);
        if (!next.delete(toggle.dataset.foldKey)) {
            next.add(toggle.dataset.foldKey);
        }
        folded.value = next;
        apply();
    };

    watch(
        preview,
        (element, previous) => {
            previous?.removeEventListener('click', onClick, true);
            element?.addEventListener('click', onClick, true);
            nextTick(apply);
        },
        { immediate: true },
    );

    watch(html, () => nextTick(apply));

    return { folded };
};
