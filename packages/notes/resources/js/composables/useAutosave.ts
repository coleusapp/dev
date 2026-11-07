import type { NoteDetailData } from '@/components/notes/note';
import { useHttp } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, type Ref } from 'vue';

export type AutosaveStatus = 'saved' | 'unsaved' | 'saving' | 'error';

export const DEFAULT_AUTOSAVE_DELAY_MS = 750;

export const useAutosave = (
    note: NoteDetailData,
    body: Ref<string | null>,
    onSaved: (note: NoteDetailData) => void,
    delay: number = DEFAULT_AUTOSAVE_DELAY_MS,
) => {
    const savedBody = ref(note.body);
    const inFlight = ref(false);
    const failed = ref(false);
    const error = ref<string | null>(null);
    const http = useHttp<{ body: string | null }, { data: NoteDetailData }>({ body: note.body });

    const url = (): string => route('notes.update', { note: note.id });

    const isDirty = computed(() => body.value !== savedBody.value);

    const status = computed<AutosaveStatus>(() => {
        if (inFlight.value) {
            return 'saving';
        }
        if (failed.value && isDirty.value) {
            return 'error';
        }

        return isDirty.value ? 'unsaved' : 'saved';
    });

    const save = useDebounceFn(async (): Promise<void> => {
        const sending = body.value;
        http.body = sending;
        inFlight.value = true;

        try {
            const response = await http.put(url());

            if (!response) {
                throw new Error(http.errors.body ?? 'Save failed.');
            }

            savedBody.value = sending;
            failed.value = false;
            error.value = null;
            onSaved(response.data);
        } catch (exception) {
            failed.value = true;
            error.value = exception instanceof Error ? exception.message : 'Save failed.';
        } finally {
            inFlight.value = false;
        }
    }, delay);

    return { status, error, save };
};
