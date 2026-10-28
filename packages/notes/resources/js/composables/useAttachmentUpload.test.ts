// @vitest-environment jsdom
import { useAttachmentUpload, type UploadedAttachment } from '@/composables/useAttachmentUpload';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

beforeEach(() => {
    vi.stubGlobal('route', (name: string) => `/${name}`);
    vi.stubGlobal(
        'fetch',
        vi.fn(async (_url: string, init: { body: FormData }) => {
            const file = init.body.get('file') as File;

            return file.name.endsWith('.exe')
                ? { ok: false, status: 422, json: async () => ({ errors: { file: ["Files of this type can't be attached."] } }) }
                : { ok: true, status: 201, json: async () => ({ data: { name: file.name, markdown: `[${file.name}](/a)` } }) };
        }),
    );
});

afterEach(() => vi.unstubAllGlobals());

describe('useAttachmentUpload', () => {
    it('uploads files in order, inserting each and collecting errors', async () => {
        const inserted: string[] = [];
        const uploads = useAttachmentUpload((attachment: UploadedAttachment) => inserted.push(attachment.name));

        const done = uploads.upload([new File(['a'], 'a.txt'), new File(['x'], 'bad.exe'), new File(['b'], 'b.png')]);
        expect(uploads.uploading.value).toBe(3);
        await done;

        expect(inserted).toEqual(['a.txt', 'b.png']);
        expect(uploads.errors.value).toEqual(["bad.exe: Files of this type can't be attached."]);
        expect(uploads.uploading.value).toBe(0);

        uploads.dismiss();
        expect(uploads.errors.value).toEqual([]);
    });
});
