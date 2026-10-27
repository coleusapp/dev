// @vitest-environment jsdom
import { useFindReplace } from '@/composables/useFindReplace';
import type { Selection, TextEdit } from '@/lib/markdownFormat';
import { describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';

const setup = (text: string) => {
    const body = ref(text);
    const textarea = ref(document.createElement('textarea'));
    textarea.value.value = text;
    const editSource = (build: (selection: Selection) => TextEdit | null): boolean => {
        const edit = build({ text: body.value, start: 0, end: 0 });
        if (edit) {
            body.value = body.value.slice(0, edit.from) + edit.insert + body.value.slice(edit.to);
            textarea.value.value = body.value;
        }
        return edit !== null;
    };
    const finder = useFindReplace({ visualActive: () => false, visual: () => undefined, textarea, body, editSource });

    return { body, textarea, finder };
};

describe('find and replace in Markdown source', () => {
    it('counts matches, selects the current one and steps around', async () => {
        const { finder, textarea } = setup('cat, Cat and cat');
        finder.show();
        finder.query.value = 'cat';
        await nextTick();

        expect(finder.count.value).toBe(3);
        expect([textarea.value.selectionStart, textarea.value.selectionEnd]).toEqual([0, 3]);

        finder.step(-1);
        expect(finder.current.value).toBe(2);
        expect(textarea.value.selectionStart).toBe(13);
    });

    it('respects match case and reports bad regular expressions', async () => {
        const { finder } = setup('cat Cat');
        finder.show();
        finder.query.value = 'Cat';
        finder.caseSensitive.value = true;
        await nextTick();
        expect(finder.count.value).toBe(1);

        finder.regex.value = true;
        finder.query.value = '(';
        await nextTick();
        expect(finder.error.value).not.toBeNull();
        expect(finder.count.value).toBe(0);
    });

    it('replaces one match, then all', async () => {
        const { finder, body } = setup('cat and cat and cat');
        finder.show(true);
        finder.query.value = 'cat';
        finder.replacement.value = 'dog';
        await nextTick();

        finder.replace();
        expect(body.value).toBe('dog and cat and cat');

        finder.replaceAll();
        expect(body.value).toBe('dog and dog and dog');
    });
});
