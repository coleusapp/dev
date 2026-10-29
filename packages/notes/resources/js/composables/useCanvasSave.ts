import type { AutosaveStatus } from '@/composables/useAutosave';
import type { CanvasCards, CanvasData } from '@/lib/canvas';
import { jsonHeaders } from '@/lib/http';
import type { GlobalEvent } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { useDebounceFn, useEventListener } from '@vueuse/core';
import { computed, onBeforeUnmount, ref, watch, type Ref } from 'vue';

/**
 * Saves a canvas board as it's edited (O10.1), like useAutosave does a
 * note: debounced, one request at a time, edits made meanwhile sent right
 * after, navigation held until saved, a keepalive save when the tab goes.
 * Every change bumps a version, so "saved" means the server has the
 * version the board shows.
 */
export const useCanvasSave = (canvasId: number, data: Ref<CanvasData>, onSaved: (cards: CanvasCards) => void, delay = 600) => {
    const url = (): string => route('notes.canvases.update', { canvas: canvasId });
    const version = ref(0);
    const savedVersion = ref(0);
    const inFlight = ref(false);
    const failed = ref(false);
    const error = ref<string | null>(null);

    const isDirty = computed(() => version.value !== savedVersion.value);

    const status = computed<AutosaveStatus>(() => {
        if (inFlight.value) {
            return 'saving';
        }

        if (failed.value && isDirty.value) {
            return 'error';
        }

        return isDirty.value ? 'unsaved' : 'saved';
    });

    const send = async (): Promise<void> => {
        const sending = version.value;
        inFlight.value = true;

        try {
            const response = await fetch(url(), {
                method: 'PUT',
                headers: jsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ data: data.value }),
            });
            const payload = await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(
                    payload?.errors
                        ? (Object.values(payload.errors as Record<string, string[]>)[0]?.[0] ?? 'Save failed.')
                        : `Save failed (${response.status}).`,
                );
            }

            savedVersion.value = sending;
            failed.value = false;
            error.value = null;
            onSaved(payload.data.cards as CanvasCards);
        } catch (exception) {
            failed.value = true;
            error.value = exception instanceof Error ? exception.message : 'Save failed.';
        } finally {
            inFlight.value = false;
        }
    };

    let current: Promise<void> | null = null;

    const flush = (): Promise<void> => {
        current ??= (async () => {
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

    watch(
        data,
        () => {
            version.value++;
            failed.value = false;
            debouncedFlush();
        },
        { deep: true },
    );

    const flushWithKeepalive = (): void => {
        if (!isDirty.value) {
            return;
        }

        fetch(url(), {
            method: 'PUT',
            headers: jsonHeaders(),
            credentials: 'same-origin',
            keepalive: true,
            body: JSON.stringify({ data: data.value }),
        });
    };

    useEventListener(window, 'pagehide', flushWithKeepalive);
    useEventListener(document, 'visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flushWithKeepalive();
        }
    });

    const removeBeforeListener = router.on('before', (event: GlobalEvent<'before'>) => {
        if (!isDirty.value && !inFlight.value) {
            return;
        }

        const visit = event.detail.visit;
        flush().then(() => {
            if (!isDirty.value) {
                router.visit(visit.url, { method: visit.method, data: visit.data, replace: visit.replace, preserveScroll: visit.preserveScroll });
            }
        });

        return false;
    });

    onBeforeUnmount(() => {
        removeBeforeListener();
        flushWithKeepalive();
    });

    return { status, error, flush };
};
