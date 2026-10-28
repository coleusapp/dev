import { xsrfToken } from '@/lib/http';
import { computed, ref } from 'vue';

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
 * A file waiting for or going through upload, with its progress (O3.1g).
 */
export type PendingUpload = { id: number; name: string; loaded: number; total: number; started: boolean; cancelled: boolean };

let nextId = 0;

/**
 * Uploads files into notes (O3.1): one request per file, in order, so they
 * land in the note in the order they were dropped or picked. Each shows
 * its progress and can be cancelled (O3.1g; XHR, since fetch can't report
 * upload progress). Errors from the server's checks are collected per file.
 */
export const useAttachmentUpload = (insert: (attachment: UploadedAttachment) => void) => {
    const queue = ref<PendingUpload[]>([]);
    const errors = ref<string[]>([]);
    const requests = new Map<number, XMLHttpRequest>();

    const update = (id: number, change: Partial<PendingUpload>): void => {
        queue.value = queue.value.map((each) => (each.id === id ? { ...each, ...change } : each));
    };

    const send = (pending: PendingUpload, file: File): Promise<void> =>
        new Promise((resolve) => {
            const request = new XMLHttpRequest();
            const form = new FormData();
            form.append('file', file);
            requests.set(pending.id, request);

            request.open('POST', route('notes.attachments.store'));
            request.withCredentials = true;
            request.setRequestHeader('Accept', 'application/json');
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            request.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
            request.upload.onprogress = (event) =>
                update(pending.id, { loaded: event.loaded, total: event.lengthComputable ? event.total : file.size });
            request.onload = () => {
                let payload: { data?: UploadedAttachment; message?: string; errors?: { file?: string[] } } | null = null;

                try {
                    payload = JSON.parse(request.responseText);
                } catch {
                    payload = null;
                }

                if (request.status >= 200 && request.status < 300 && payload?.data) {
                    insert(payload.data);
                } else {
                    errors.value = [
                        ...errors.value,
                        `${file.name}: ${payload?.errors?.file?.[0] ?? payload?.message ?? `upload failed (${request.status})`}`,
                    ];
                }

                resolve();
            };
            request.onerror = () => {
                errors.value = [...errors.value, `${file.name}: upload failed`];
                resolve();
            };
            request.onabort = () => resolve();
            update(pending.id, { started: true });
            request.send(form);
        });

    const upload = async (files: Iterable<File>): Promise<void> => {
        const list = Array.from(files).map((file) => ({
            file,
            pending: { id: nextId++, name: file.name, loaded: 0, total: file.size, started: false, cancelled: false },
        }));

        if (!list.length) {
            return;
        }

        errors.value = [];
        queue.value = [...queue.value, ...list.map(({ pending }) => pending)];

        for (const { file, pending } of list) {
            if (!queue.value.find((each) => each.id === pending.id)?.cancelled) {
                await send(pending, file);
            }

            requests.delete(pending.id);
            queue.value = queue.value.filter((each) => each.id !== pending.id);
        }
    };

    /**
     * Stops one upload, or keeps a queued one from starting.
     */
    const cancel = (id: number): void => {
        update(id, { cancelled: true });
        requests.get(id)?.abort();
    };

    return {
        queue,
        uploading: computed(() => queue.value.length),
        errors,
        upload,
        cancel,
        dismiss: () => (errors.value = []),
    };
};
