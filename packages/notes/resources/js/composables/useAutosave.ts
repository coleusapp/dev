import type { NoteDetailData } from '@/components/notes/note';
import { jsonHeaders } from '@/lib/http';
import { router } from '@inertiajs/vue3';
import { useDebounceFn, useEventListener } from '@vueuse/core';
import { computed, onBeforeUnmount, ref, watch, type Ref } from 'vue';

export type AutosaveStatus = 'saved' | 'unsaved' | 'saving' | 'error';

export const AUTOSAVE_DELAY_MS = 750;

/**
 * Debounced, serialized saving of a note body.
 *
 * Only one request is in flight at a time; edits made while it runs are sent
 * right after it finishes, so responses can never arrive out of order and
 * overwrite newer text. Inertia navigation is held back until pending edits
 * are on the server, and closing or hiding the tab flushes with a keepalive
 * request that outlives the page.
 */
export const useAutosave = (note: NoteDetailData, body: Ref<string>, onSaved: (note: NoteDetailData) => void) => {
    const savedBody = ref(note.body);
    const inFlight = ref(false);
    const failed = ref(false);
    const error = ref<string | null>(null);

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

    let current: Promise<void> | null = null;

    const send = async (): Promise<void> => {
        const sending = body.value;
        inFlight.value = true;

        try {
            const response = await fetch(route('notes.update', { note: note.id }), {
                method: 'PUT',
                headers: jsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ body: sending }),
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => null);
                throw new Error(payload?.errors?.body?.[0] ?? payload?.message ?? `Save failed (${response.status}).`);
            }

            const { data } = (await response.json()) as { data: NoteDetailData };
            savedBody.value = sending;
            failed.value = false;
            error.value = null;
            onSaved(data);
        } catch (exception) {
            failed.value = true;
            error.value = exception instanceof Error ? exception.message : 'Save failed.';
        } finally {
            inFlight.value = false;
        }
    };

    /**
     * Save until the server has the latest text, or a request fails.
     */
    const flush = (): Promise<void> => {
        if (current) {
            return current;
        }

        current = (async () => {
            while (isDirty.value) {
                await send();
                if (failed.value) {
                    break;
                }
            }
        })().finally(() => {
            current = null;
        });

        return current;
    };

    const debouncedFlush = useDebounceFn(flush, AUTOSAVE_DELAY_MS);

    watch(body, () => {
        failed.value = false;
        debouncedFlush();
    });

    const flushWithKeepalive = (): void => {
        if (!isDirty.value) {
            return;
        }

        fetch(route('notes.update', { note: note.id }), {
            method: 'PUT',
            headers: jsonHeaders(),
            credentials: 'same-origin',
            keepalive: true,
            body: JSON.stringify({ body: body.value }),
        });
    };

    useEventListener(window, 'pagehide', flushWithKeepalive);
    useEventListener(document, 'visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flushWithKeepalive();
        }
    });

    const removeBeforeListener = router.on('before', (event) => {
        if (!isDirty.value && !inFlight.value) {
            return;
        }

        const visit = event.detail.visit;
        flush().then(() => {
            // A failed save keeps the user here with the error showing, rather than navigating away from unsaved text.
            if (isDirty.value) {
                return;
            }

            router.visit(visit.url, {
                method: visit.method,
                data: visit.data,
                replace: visit.replace,
                preserveScroll: visit.preserveScroll,
                preserveState: visit.preserveState,
                only: visit.only,
                except: visit.except,
                headers: visit.headers,
            });
        });

        return false;
    });

    onBeforeUnmount(() => {
        removeBeforeListener();
        flushWithKeepalive();
    });

    return { status, error, flush, isDirty };
};
