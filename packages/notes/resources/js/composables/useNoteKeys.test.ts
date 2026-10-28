// @vitest-environment jsdom
import { cachedKey, forgetKeys, keysVersion, RELOCK_AFTER_MS, setPassword, unlockWithPassword, type KeyParams } from '@/composables/useNoteKeys';
import { createLockKey } from '@/lib/noteCrypto';
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
