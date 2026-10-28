// @vitest-environment jsdom
import { useAttachmentUpload, type UploadedAttachment } from '@/composables/useAttachmentUpload';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * A stand-in XMLHttpRequest the test drives by hand.
 */
class FakeRequest {
    static all: FakeRequest[] = [];
    upload: { onprogress: ((event: { loaded: number; total: number; lengthComputable: boolean }) => void) | null } = { onprogress: null };
    onload: (() => void) | null = null;
    onerror: (() => void) | null = null;
    onabort: (() => void) | null = null;
    status = 0;
    responseText = '';
    withCredentials = false;
    headers: Record<string, string> = {};
    body: FormData | null = null;
    aborted = false;

    constructor() {
        FakeRequest.all.push(this);
    }

    open(): void {}

    setRequestHeader(name: string, value: string): void {
        this.headers[name] = value;
    }

    send(body: FormData): void {
        this.body = body;
    }

    abort(): void {
        this.aborted = true;
        this.onabort?.();
    }

    respond(status: number, payload: unknown): void {
        this.status = status;
        this.responseText = JSON.stringify(payload);
        this.onload?.();
    }
}

beforeEach(() => {
    FakeRequest.all = [];
    vi.stubGlobal('route', (name: string) => `/${name}`);
    vi.stubGlobal('XMLHttpRequest', FakeRequest);
});

afterEach(() => vi.unstubAllGlobals());

const tick = () => new Promise((resolve) => setTimeout(resolve));

describe('useAttachmentUpload', () => {
    it('uploads in order with progress, inserting each and collecting errors', async () => {
        const inserted: string[] = [];
        const uploads = useAttachmentUpload((attachment: UploadedAttachment) => inserted.push(attachment.name));

        const done = uploads.upload([new File(['aa'], 'a.txt'), new File(['x'], 'bad.exe')]);
        expect(uploads.uploading.value).toBe(2);
        expect(FakeRequest.all).toHaveLength(1);
        expect(FakeRequest.all[0].withCredentials).toBe(true);

        FakeRequest.all[0].upload.onprogress?.({ loaded: 1, total: 2, lengthComputable: true });
        expect(uploads.queue.value[0]).toMatchObject({ name: 'a.txt', loaded: 1, total: 2, started: true });

        FakeRequest.all[0].respond(201, { data: { name: 'a.txt' } });
        await tick();
        FakeRequest.all[1].respond(422, { errors: { file: ["Files of this type can't be attached."] } });
        await done;

        expect(inserted).toEqual(['a.txt']);
        expect(uploads.errors.value).toEqual(["bad.exe: Files of this type can't be attached."]);
        expect(uploads.uploading.value).toBe(0);
    });

    it('cancels a running upload and skips a queued one', async () => {
        const inserted: string[] = [];
        const uploads = useAttachmentUpload((attachment: UploadedAttachment) => inserted.push(attachment.name));

        const done = uploads.upload([new File(['a'], 'big.mov'), new File(['b'], 'next.png')]);
        const [running, queued] = uploads.queue.value;

        uploads.cancel(queued.id);
        uploads.cancel(running.id);
        await done;

        expect(FakeRequest.all).toHaveLength(1);
        expect(FakeRequest.all[0].aborted).toBe(true);
        expect(inserted).toEqual([]);
        expect(uploads.errors.value).toEqual([]);
        expect(uploads.uploading.value).toBe(0);
    });
});
