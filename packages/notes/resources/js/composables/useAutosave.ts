import type { NoteDetailData } from '@/components/notes/note';
import { jsonHeaders } from '@/lib/http';
import type { GlobalEvent } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { useDebounceFn, useEventListener } from '@vueuse/core';
import { computed, onActivated, onBeforeUnmount, onDeactivated, ref, watch, type Ref } from 'vue';

export type AutosaveStatus = 'saved' | 'unsaved' | 'saving' | 'error';

export const DEFAULT_AUTOSAVE_DELAY_MS = 750;

/**
 * Where and how a body is saved, when not as plain text to notes.update:
 * a locked note sends its text encrypted (O12.5).
 */
export type AutosaveTransport = {
    url: () => string;
    payload: (body: string) => Promise<Record<string, unknown>>;
};

/**
 * Debounced, serialized saving of a note body.
 *
 * Only one request is in flight at a time; edits made while it runs are sent
 * right after it finishes, so responses can never arrive out of order and
 * overwrite newer text. Inertia navigation is held back until pending edits
 * are on the server, and closing or hiding the tab flushes with a keepalive
 * request that outlives the page.
 */
export const useAutosave = (
    note: NoteDetailData,
    body: Ref<string>,
    onSaved: (note: NoteDetailData) => void,
    delay: number = DEFAULT_AUTOSAVE_DELAY_MS,
    transport: AutosaveTransport | null = null,
) => {
    const url = (): string => transport?.url() ?? route('notes.update', { note: note.id });
    const encode = async (text: string): Promise<Record<string, unknown>> => (transport ? transport.payload(text) : { body: text });

    /**
     * The last body already encoded, for the keepalive save on closing the
     * tab: that one can't wait for encryption to finish.
     */
    let prepared: Record<string, unknown> | null = null;
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
            const response = await fetch(url(), {
                method: 'PUT',
                headers: jsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify(await encode(sending)),
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

    const debouncedFlush = useDebounceFn(flush, delay);

    watch(body, (text) => {
        failed.value = false;
        debouncedFlush();

        if (transport) {
            void encode(text).then((payload) => {
                if (body.value === text) {
                    prepared = payload;
                }
            });
        }
    });

    const flushWithKeepalive = (): void => {
        const payload = transport ? prepared : { body: body.value };

        if (!isDirty.value || !payload) {
            return;
        }

        fetch(url(), {
            method: 'PUT',
            headers: jsonHeaders(),
            credentials: 'same-origin',
            keepalive: true,
            body: JSON.stringify(payload),
        });
    };

    useEventListener(window, 'pagehide', flushWithKeepalive);
    useEventListener(document, 'visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flushWithKeepalive();
        }
    });

    const guardNavigation = (event: GlobalEvent<'before'>) => {
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
    };

    let removeBeforeListener: (() => void) | null = router.on('before', guardNavigation);

    /**
     * Editors kept alive for undo history (KeepAlive) stop guarding
     * navigation while hidden, and start again when shown.
     */
    onDeactivated(() => {
        removeBeforeListener?.();
        removeBeforeListener = null;
        flushWithKeepalive();
    });

    onActivated(() => {
        removeBeforeListener ??= router.on('before', guardNavigation);
    });

    onBeforeUnmount(() => {
        removeBeforeListener?.();
        flushWithKeepalive();
    });

    /**
     * Take a body the server saved through another endpoint (e.g. a checklist
     * toggle) as both the current and the saved text.
     */
    const adopt = (saved: NoteDetailData): void => {
        savedBody.value = saved.body;
        body.value = saved.body;
        failed.value = false;
        error.value = null;
    };

    return { status, error, flush, adopt, isDirty };
};
