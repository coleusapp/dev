// @vitest-environment jsdom
import {
    cachedKey,
    changeHint,
    changePassword,
    forgetKeys,
    keysVersion,
    RELOCK_AFTER_MS,
    setPassword,
    unlockWithPassword,
    type KeyParams,
} from '@/composables/useNoteKeys';
import { createLockKey, decryptText, encryptText, unlockKey } from '@/lib/noteCrypto';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const posted: unknown[] = [];

beforeEach(() => {
    vi.stubGlobal('route', (name: string) => `/${name}`);
    vi.stubGlobal(
        'fetch',
        vi.fn(async (_url: string, init: { body?: string }) => {
            posted.push(JSON.parse(init.body ?? 'null'));

            return { ok: true, json: async () => ({ data: { id: 7 } }) };
        }),
    );
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    forgetKeys();
    posted.length = 0;
});

describe('notes password keys', () => {
    it('remembers a right password, not a wrong one', async () => {
        const { params } = await createLockKey('right', null, 1000);
        const withId: KeyParams = { ...params, id: 3, current: true };

        expect(await unlockWithPassword(withId, 'wrong')).toBeNull();
        expect(cachedKey(3)).toBeNull();

        expect(await unlockWithPassword(withId, 'right')).not.toBeNull();
        expect(cachedKey(3)).not.toBeNull();
    });

    it('sends only the salt, iterations, verifier and hint when setting a password', async () => {
        const result = await setPassword('secret password', 'hint');

        expect(result).toMatchObject({ id: 7 });
        expect(Object.keys(posted[0] as object).sort()).toEqual(['hint', 'iterations', 'salt', 'verifier', 'verifier_iv']);
        expect(JSON.stringify(posted[0])).not.toContain('secret password');
        expect(cachedKey(7)).not.toBeNull();
    });

    it('forgets keys after a while unused, or when asked', async () => {
        vi.useFakeTimers();
        const { params } = await createLockKey('right', null, 1000);
        await unlockWithPassword({ ...params, id: 3, current: true }, 'right');
        const version = keysVersion.value;

        vi.advanceTimersByTime(RELOCK_AFTER_MS - 1);
        expect(cachedKey(3)).not.toBeNull();

        vi.advanceTimersByTime(RELOCK_AFTER_MS);
        expect(cachedKey(3)).toBeNull();
        expect(keysVersion.value).toBe(version + 1);
    });
});

describe('changing the password', () => {
    it('re-encrypts every locked note for the new password and sends no passwords', async () => {
        const old = await createLockKey('old password', 'old hint', 1000);
        const locked = [
            { id: 4, ...(await encryptText(old.key, 'first secret')) },
            { id: 5, ...(await encryptText(old.key, 'second')) },
        ];
        const sent: { url: string; method: string; body: Record<string, unknown> | null }[] = [];

        vi.stubGlobal(
            'fetch',
            vi.fn(async (url: string, init: { method?: string; body?: string }) => {
                sent.push({ url, method: init.method ?? 'GET', body: init.body ? JSON.parse(init.body) : null });
                const data =
                    url === '/notes.lock-keys.current'
                        ? { ...old.params, id: 2, current: true }
                        : url === '/notes.lock-keys.notes'
                          ? locked
                          : { id: 2 };

                return { ok: true, json: async () => ({ data }) };
            }),
        );

        expect(await changePassword('wrong', 'new password', null)).toEqual({ error: 'The current password is wrong.' });
        expect(await changePassword('old password', 'new password', 'new hint')).toEqual({ error: null });

        const update = sent.find((each) => each.method === 'PUT')!;
        const body = update.body as { notes: ({ id: number } & { iv: string; ciphertext: string })[]; hint: string } & Parameters<
            typeof unlockKey
        >[1];
        const newKey = await unlockKey('new password', body);

        expect(body.hint).toBe('new hint');
        expect(await Promise.all(body.notes.map((note) => decryptText(newKey!, note)))).toEqual(['first secret', 'second']);
        expect(JSON.stringify(sent)).not.toContain('password');
        expect(cachedKey(2)).not.toBeNull();
    });

    it('reports the server error for a hint change', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({ ok: false, json: async () => ({ errors: { hint: ['Too long.'] } }) })),
        );

        expect(await changeHint(2, 'x')).toEqual({ error: 'Too long.' });
    });
});
