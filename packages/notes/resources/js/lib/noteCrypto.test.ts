import { createLockKey, decryptText, encryptText, fromBase64, toBase64, unlockKey } from '@/lib/noteCrypto';
import { describe, expect, it } from 'vitest';

// Fewer iterations than real use, to keep the tests quick.
const ITERATIONS = 1000;

describe('noteCrypto', () => {
    it('round-trips text, with a fresh IV each time', async () => {
        const { key } = await createLockKey('correct horse', null, ITERATIONS);
        const text = '# Bank\naccount 123 — ünïcode ✓';

        const first = await encryptText(key, text);
        const second = await encryptText(key, text);

        expect(await decryptText(key, first)).toBe(text);
        expect(first.iv).not.toBe(second.iv);
        expect(first.ciphertext).not.toContain('Bank');
        expect(fromBase64(first.iv)).toHaveLength(12);
    });

    it('accepts the right password and refuses a wrong one', async () => {
        const { params } = await createLockKey('correct horse', '  the usual  ', ITERATIONS);

        expect(params.hint).toBe('the usual');
        expect(params.iterations).toBe(ITERATIONS);
        expect(fromBase64(params.salt)).toHaveLength(16);
        expect(await unlockKey('correct horse', params)).not.toBeNull();
        expect(await unlockKey('wrong horse', params)).toBeNull();
    });

    it('a key from the same password and salt opens what the first one locked', async () => {
        const { key, params } = await createLockKey('pass phrase', null, ITERATIONS);
        const locked = await encryptText(key, 'hello');
        const again = await unlockKey('pass phrase', params);

        expect(await decryptText(again!, locked)).toBe('hello');
    });

    it('refuses changed ciphertext', async () => {
        const { key } = await createLockKey('pass', null, ITERATIONS);
        const locked = await encryptText(key, 'hello');
        const bytes = fromBase64(locked.ciphertext);
        bytes[0] ^= 1;

        await expect(decryptText(key, { iv: locked.iv, ciphertext: toBase64(bytes) })).rejects.toThrow();
    });
});
