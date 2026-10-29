// @vitest-environment jsdom
import { useDictation } from '@/composables/useDictation';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { createApp, defineComponent } from 'vue';

class FakeRecognition {
    static last: FakeRecognition;
    continuous = false;
    interimResults = false;
    lang = '';
    onresult: ((event: unknown) => void) | null = null;
    onerror: ((event: { error: string }) => void) | null = null;
    onend: (() => void) | null = null;
    start = vi.fn();
    stop = vi.fn(() => this.onend?.());

    constructor() {
        FakeRecognition.last = this;
    }

    hear(results: [string, boolean][], from = 0): void {
        this.onresult?.({ resultIndex: from, results: results.map(([transcript, isFinal]) => ({ isFinal, 0: { transcript } })) });
    }
}

afterEach(() => vi.unstubAllGlobals());

const mount = (insert: (text: string) => void) => {
    let dictation!: ReturnType<typeof useDictation>;
    createApp(defineComponent({ setup: () => ((dictation = useDictation(insert)), () => null) })).mount(document.createElement('div'));

    return dictation;
};

describe('useDictation', () => {
    it('is not offered where the browser cannot recognise speech', () => {
        expect(mount(() => undefined).supported).toBe(false);
    });

    it('inserts finished phrases and shows the one being heard', () => {
        vi.stubGlobal('webkitSpeechRecognition', FakeRecognition);
        const inserted: string[] = [];
        const dictation = mount((text) => inserted.push(text));

        dictation.start();
        expect(dictation.listening.value).toBe(true);
        expect(FakeRecognition.last.continuous).toBe(true);

        FakeRecognition.last.hear([
            ['buy milk', true],
            ['and eg', false],
        ]);
        expect(inserted).toEqual(['buy milk ']);
        expect(dictation.interim.value).toBe('and eg');
    });

    it('keeps listening after the browser pauses, until stopped', () => {
        vi.stubGlobal('webkitSpeechRecognition', FakeRecognition);
        const dictation = mount(() => undefined);

        dictation.start();
        FakeRecognition.last.onend?.();
        expect(FakeRecognition.last.start).toHaveBeenCalledTimes(2);

        dictation.stop();
        expect(dictation.listening.value).toBe(false);
    });

    it('stops and explains when the microphone is refused', () => {
        vi.stubGlobal('webkitSpeechRecognition', FakeRecognition);
        const dictation = mount(() => undefined);

        dictation.start();
        FakeRecognition.last.onerror?.({ error: 'not-allowed' });
        FakeRecognition.last.onend?.();

        expect(dictation.listening.value).toBe(false);
        expect(dictation.error.value).toBe('Dictation was not allowed.');
    });
});
