import { currentEntry, emptyHistory, forgetNote, stepHistory, visitNote, type NoteHistory } from '@/lib/noteHistory';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

/**
 * Shared by every mount of the notes page, so the history survives a trip
 * to Settings and back (it starts over on a full reload).
 */
const history = ref<NoteHistory>(emptyHistory());

/**
 * The step being taken, applied once that note is actually open.
 */
let arriving: { id: number; history: NoteHistory } | null = null;

/**
 * Back / forward between opened notes (lib/noteHistory.ts). Opening a note
 * any other way (list, link, switcher, browser history) is recorded as a new
 * visit. Notes deleted for good since are dropped and skipped over.
 */
export const useNoteHistory = (openNoteId: () => number | null, noteUrl: (id: number) => string) => {
    watch(
        openNoteId,
        (id) => {
            if (id === null) {
                return;
            }

            history.value = arriving?.id === id ? arriving.history : visitNote(history.value, id);
            arriving = null;
        },
        { immediate: true },
    );

    const go = (step: 1 | -1): void => {
        const next = stepHistory(history.value, step);
        const id = next ? currentEntry(next) : null;

        if (!next || id === null) {
            return;
        }

        arriving = { id, history: next };

        const stopListening = router.on('invalid', (event) => {
            if (event.detail.response.status !== 404) {
                return;
            }

            event.preventDefault();
            arriving = null;
            history.value = forgetNote(history.value, id);
            stopListening();
            setTimeout(() => go(step));
        });

        router.visit(noteUrl(id), { preserveState: true, preserveScroll: true, onFinish: stopListening });
    };

    return {
        canGoBack: computed(() => stepHistory(history.value, -1) !== null),
        canGoForward: computed(() => stepHistory(history.value, 1) !== null),
        back: () => go(-1),
        forward: () => go(1),
    };
};
