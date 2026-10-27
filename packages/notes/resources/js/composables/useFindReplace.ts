import { findMatches, findPattern, replaceAll as replaceAllText, replacementFor } from '@/lib/find';
import type { Selection, TextEdit } from '@/lib/markdownFormat';
import { computed, ref, watch, type Ref } from 'vue';

type VisualFind = {
    search: (pattern: RegExp | null) => void;
    step: (step: 1 | -1) => void;
    replace: (replacement: string, regex: boolean) => void;
    replaceAll: (replacement: string, regex: boolean) => void;
};

/**
 * Find & replace for the open note. In the visual editor the work is done
 * by editor/Find.ts (highlighted matches); in Markdown source the matches
 * are found in the text and the current one is selected in the textarea.
 */
export const useFindReplace = (options: {
    visualActive: () => boolean;
    visual: () => VisualFind | undefined;
    textarea: Readonly<Ref<HTMLTextAreaElement | null>>;
    body: Ref<string>;
    editSource: (build: (selection: Selection) => TextEdit | null) => boolean;
}) => {
    const open = ref(false);
    const query = ref('');
    const replacement = ref('');
    const caseSensitive = ref(false);
    const regex = ref(false);
    const showReplace = ref(false);
    const sourceCurrent = ref(0);
    const visualState = ref({ count: 0, current: 0 });

    const pattern = computed<RegExp | string | null>(() =>
        open.value && query.value !== '' ? findPattern(query.value, { caseSensitive: caseSensitive.value, regex: regex.value }) : null,
    );
    const validPattern = computed(() => (pattern.value instanceof RegExp ? pattern.value : null));
    const error = computed(() => (typeof pattern.value === 'string' ? pattern.value : null));
    const sourceMatches = computed(() => (validPattern.value && !options.visualActive() ? findMatches(options.body.value, validPattern.value) : []));

    const count = computed(() => (options.visualActive() ? visualState.value.count : sourceMatches.value.length));
    const current = computed(() =>
        options.visualActive() ? visualState.value.current : Math.min(sourceCurrent.value, Math.max(0, sourceMatches.value.length - 1)),
    );

    const selectSourceMatch = (): void => {
        const match = sourceMatches.value[current.value];
        const textarea = options.textarea.value;

        if (!match || !textarea) {
            return;
        }

        textarea.setSelectionRange(match.start, match.end);
        const line = options.body.value.slice(0, match.start).split('\n').length - 1;
        const lineHeight = parseFloat(window.getComputedStyle(textarea).lineHeight) || 20;
        textarea.scrollTop = Math.max(0, line * lineHeight - textarea.clientHeight / 2);
    };

    const search = (): void => {
        sourceCurrent.value = 0;

        if (options.visualActive()) {
            options.visual()?.search(validPattern.value);
        } else {
            options.visual()?.search(null);
            selectSourceMatch();
        }
    };

    watch([validPattern, () => options.visualActive()], search);

    const step = (direction: 1 | -1): void => {
        if (options.visualActive()) {
            options.visual()?.step(direction);
            return;
        }

        const total = sourceMatches.value.length;

        if (total) {
            sourceCurrent.value = (current.value + direction + total) % total;
            selectSourceMatch();
        }
    };

    const replace = (): void => {
        if (!validPattern.value) {
            return;
        }

        if (options.visualActive()) {
            options.visual()?.replace(replacement.value, regex.value);
            return;
        }

        const match = sourceMatches.value[current.value];
        const pattern = validPattern.value;

        if (match) {
            const text = replacementFor(options.body.value.slice(match.start, match.end), pattern, replacement.value, regex.value);
            options.editSource(() => ({
                from: match.start,
                to: match.end,
                insert: text,
                selectionStart: match.start,
                selectionEnd: match.start + text.length,
            }));
            selectSourceMatch();
        }
    };

    const replaceAll = (): void => {
        const pattern = validPattern.value;

        if (!pattern) {
            return;
        }

        if (options.visualActive()) {
            options.visual()?.replaceAll(replacement.value, regex.value);
            return;
        }

        const text = options.body.value;
        const replaced = replaceAllText(text, pattern, replacement.value, regex.value);
        options.editSource(() => ({ from: 0, to: text.length, insert: replaced, selectionStart: 0, selectionEnd: 0 }));
    };

    const show = (withReplace = false): void => {
        open.value = true;
        showReplace.value = showReplace.value || withReplace;
    };

    const close = (): void => {
        open.value = false;
        options.visual()?.search(null);
    };

    return {
        open,
        query,
        replacement,
        caseSensitive,
        regex,
        showReplace,
        count,
        current,
        error,
        visualState,
        show,
        close,
        step,
        replace,
        replaceAll,
    };
};
