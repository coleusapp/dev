import type { HighlightPart } from '@/components/notes/note';
import { jsonHeaders } from '@/lib/http';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export type Mention = { id: number; title: string; count: number; context: HighlightPart[][] };

/**
 * Unlinked mentions of a note (O2.8): loaded on demand (finding them reads
 * many notes), and linked one note at a time. After linking, the open
 * note and the list reload so the linking note moves to "Linked from".
 */
export const useUnlinkedMentions = (noteId: () => number) => {
    const mentions = ref<Mention[] | null>(null);
    const loading = ref(false);
    const failed = ref(false);
    const linking = ref<number | null>(null);

    const load = async (): Promise<void> => {
        loading.value = true;
        failed.value = false;

        try {
            const response = await fetch(route('notes.mentions.index', { note: noteId() }), { headers: jsonHeaders(), credentials: 'same-origin' });
            const payload = await response.json().catch(() => null);
            failed.value = !response.ok;
            mentions.value = response.ok && Array.isArray(payload?.data) ? payload.data : [];
        } finally {
            loading.value = false;
        }
    };

    const linkAll = async (mention: Mention): Promise<void> => {
        linking.value = mention.id;

        try {
            const response = await fetch(route('notes.mentions.store', { note: noteId(), source: mention.id }), {
                method: 'POST',
                headers: jsonHeaders(),
                credentials: 'same-origin',
            });

            if (response.ok) {
                mentions.value = (mentions.value ?? []).filter((each) => each.id !== mention.id);
                router.reload({ only: ['note', 'notes'] });
            }
        } finally {
            linking.value = null;
        }
    };

    const forget = (): void => {
        mentions.value = null;
    };

    return { mentions, loading, failed, linking, load, linkAll, forget };
};
