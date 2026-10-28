import { xsrfToken } from '@/lib/http';
import { ref } from 'vue';

export type UploadedAttachment = {
    uuid: string;
    name: string;
    mime: string;
    kind: 'image' | 'video' | 'audio' | 'document';
    size: number;
    url: string;
    markdown: string;
};

/**
 * Uploads files into notes (O3.1): one request per file, in order, so
 * they land in the note in the order they were dropped or picked. Errors
 * from the server's checks (type, size) are collected per file.
 */
export const useAttachmentUpload = (insert: (attachment: UploadedAttachment) => void) => {
    const uploading = ref(0);
    const errors = ref<string[]>([]);

    const uploadOne = async (file: File): Promise<void> => {
        const form = new FormData();
        form.append('file', file);

        const response = await fetch(route('notes.attachments.store'), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrfToken() },
            credentials: 'same-origin',
            body: form,
        });
        const payload = await response.json().catch(() => null);

        if (response.ok && payload?.data) {
            insert(payload.data as UploadedAttachment);
        } else {
            errors.value = [
                ...errors.value,
                `${file.name}: ${payload?.errors?.file?.[0] ?? payload?.message ?? `upload failed (${response.status})`}`,
            ];
        }
    };

    const upload = async (files: Iterable<File>): Promise<void> => {
        const list = Array.from(files);

        if (!list.length) {
            return;
        }

        errors.value = [];
        uploading.value += list.length;

        for (const file of list) {
            try {
                await uploadOne(file);
            } catch {
                errors.value = [...errors.value, `${file.name}: upload failed`];
            } finally {
                uploading.value--;
            }
        }
    };

    return { uploading, errors, upload, dismiss: () => (errors.value = []) };
};
