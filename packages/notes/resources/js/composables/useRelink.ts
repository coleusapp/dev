import { jsonHeaders } from '@/lib/http';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Offers to update links after a rename (O2.6). The title the note had
 * when opened is the baseline; once a save gives it another title, the
 * server says how many other notes still link to the baseline title and
 * lead nowhere now. "Update links" rewrites them; "Keep" leaves them. Either
 * way the new title becomes the baseline.
 */
export const useRelink = (options: { noteId: () => number; title: () => string }) => {
    const baseline = ref(options.title());
    const offer = ref<{ from: string; count: number } | null>(null);
    const working = ref(false);
    let latest = 0;

    const request = async (method: 'GET' | 'POST', from: string): Promise<number | null> => {
        const note = options.noteId();
        const url = method === 'GET' ? route('notes.relink.show', { note, title: from }) : route('notes.relink.store', { note });
        const response = await fetch(url, {
            method,
            headers: jsonHeaders(),
            credentials: 'same-origin',
            ...(method === 'POST' ? { body: JSON.stringify({ title: from }) } : {}),
        });
        const payload = await response.json().catch(() => null);

        return response.ok && typeof payload?.data?.count === 'number' ? payload.data.count : null;
    };

    /**
     * Call after each save: checks again whenever the title differs from the baseline.
     */
    const check = async (): Promise<void> => {
        const from = baseline.value;
        const call = ++latest;

        if (from.trim() === '' || options.title() === from) {
            offer.value = null;

            return;
        }

        const count = await request('GET', from);

        if (call === latest) {
            offer.value = count ? { from, count } : null;
        }
    };

    const keep = (): void => {
        baseline.value = options.title();
        offer.value = null;
    };

    const relink = async (): Promise<void> => {
        if (!offer.value || working.value) {
            return;
        }

        working.value = true;

        try {
            const count = await request('POST', offer.value.from);

            if (count !== null) {
                keep();
                router.reload({ only: ['notes'] });
            }
        } finally {
            working.value = false;
        }
    };

    return { offer, working, check, keep, relink };
};
