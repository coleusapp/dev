import { onBeforeUnmount, ref } from 'vue';

type RecognitionResult = { isFinal: boolean; 0: { transcript: string } };
type RecognitionEvent = { resultIndex: number; results: ArrayLike<RecognitionResult> };

type Recognition = {
    continuous: boolean;
    interimResults: boolean;
    lang: string;
    onresult: ((event: RecognitionEvent) => void) | null;
    onerror: ((event: { error: string }) => void) | null;
    onend: (() => void) | null;
    start: () => void;
    stop: () => void;
};

type RecognitionConstructor = new () => Recognition;

const recognitionClass = (): RecognitionConstructor | null => {
    const scope = globalThis as unknown as { SpeechRecognition?: RecognitionConstructor; webkitSpeechRecognition?: RecognitionConstructor };

    return scope.SpeechRecognition ?? scope.webkitSpeechRecognition ?? null;
};

/**
 * Voice dictation (A9.4) with the browser's speech recognition, where it
 * exists (Chrome, Edge, Safari). Each finished phrase is handed to
 * `insert` (with a space between phrases); the phrase still being heard is
 * shown as `interim`. It keeps listening until stopped, and restarts when
 * the browser ends a session on its own after a pause.
 */
export const useDictation = (insert: (text: string) => void) => {
    const supported = recognitionClass() !== null;
    const listening = ref(false);
    const interim = ref('');
    const error = ref<string | null>(null);
    let recognition: Recognition | null = null;
    let wanted = false;

    const start = (): void => {
        const Class = recognitionClass();

        if (!Class || listening.value) {
            return;
        }

        error.value = null;
        wanted = true;
        recognition = new Class();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = document.documentElement.lang || navigator.language;
        recognition.onresult = (event) => {
            let pending = '';

            for (let index = event.resultIndex; index < event.results.length; index++) {
                const result = event.results[index];

                if (result.isFinal) {
                    const text = result[0].transcript.trim();

                    if (text) {
                        insert(`${text} `);
                    }
                } else {
                    pending += result[0].transcript;
                }
            }

            interim.value = pending.trim();
        };
        recognition.onerror = (event) => {
            if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                wanted = false;
                error.value = 'Dictation was not allowed.';
            } else if (event.error !== 'no-speech' && event.error !== 'aborted') {
                error.value = "Dictation stopped: couldn't hear or understand.";
            }
        };
        recognition.onend = () => {
            interim.value = '';

            if (wanted) {
                recognition?.start();
            } else {
                listening.value = false;
            }
        };
        recognition.start();
        listening.value = true;
    };

    const stop = (): void => {
        wanted = false;
        recognition?.stop();
        listening.value = false;
        interim.value = '';
    };

    const toggle = (): void => (listening.value ? stop() : start());

    onBeforeUnmount(stop);

    return { supported, listening, interim, error, start, stop, toggle };
};
