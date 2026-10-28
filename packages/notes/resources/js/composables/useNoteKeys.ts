import { jsonHeaders } from '@/lib/http';
import { createLockKey, unlockKey, type LockKeyParams } from '@/lib/noteCrypto';
import { ref } from 'vue';

/**
 * How long an unlocked notes password stays in memory without being used
 * before locked notes lock again (Apple relocks after a few minutes).
 */
export const RELOCK_AFTER_MS = 10 * 60 * 1000;

/**
 * Keys of unlocked notes passwords, by key id. Memory only (gone on
 * reload), shared by every locked note on the page.
 */
const keys = new Map<number, CryptoKey>();

/**
 * Bumped whenever keys are forgotten, so open locked notes can lock again.
 */
export const keysVersion = ref(0);

let relockTimer: ReturnType<typeof setTimeout> | undefined;

export const forgetKeys = (): void => {
    clearTimeout(relockTimer);
    keys.clear();
    keysVersion.value++;
};

/**
 * Counts as using the keys: pushes the automatic relock back.
 */
export const touchKeys = (): void => {
    clearTimeout(relockTimer);

    if (keys.size) {
        relockTimer = setTimeout(forgetKeys, RELOCK_AFTER_MS);
    }
};

export const cachedKey = (id: number): CryptoKey | null => {
    const key = keys.get(id) ?? null;

    if (key) {
        touchKeys();
    }

    return key;
};

const rememberKey = (id: number, key: CryptoKey): void => {
    keys.set(id, key);
    touchKeys();
};

const getJson = async <T>(url: string): Promise<T | null> => {
    const response = await fetch(url, { headers: jsonHeaders(), credentials: 'same-origin' });
    const payload = await response.json().catch(() => null);

    return response.ok ? (payload?.data ?? null) : null;
};

export type KeyParams = LockKeyParams & { id: number; current: boolean };

export const currentKeyParams = (): Promise<KeyParams | null> => getJson<KeyParams>(route('notes.lock-keys.current'));

export const keyParams = (id: number): Promise<KeyParams | null> => getJson<KeyParams>(route('notes.lock-keys.show', { lockKey: id }));

/**
 * The key for a typed password, remembered while in use; null when wrong.
 */
export const unlockWithPassword = async (params: KeyParams, password: string): Promise<CryptoKey | null> => {
    const key = await unlockKey(password, params);

    if (key) {
        rememberKey(params.id, key);
    }

    return key;
};

/**
 * Sets (or resets) the notes password; the new key is remembered.
 */
export const setPassword = async (password: string, hint: string | null): Promise<{ id: number; key: CryptoKey } | { error: string }> => {
    const { key, params } = await createLockKey(password, hint);
    const response = await fetch(route('notes.lock-keys.store'), {
        method: 'POST',
        headers: jsonHeaders(),
        credentials: 'same-origin',
        body: JSON.stringify(params),
    });
    const payload = await response.json().catch(() => null);

    if (!response.ok || typeof payload?.data?.id !== 'number') {
        return { error: payload?.message ?? "Couldn't save the password." };
    }

    rememberKey(payload.data.id, key);

    return { id: payload.data.id, key };
};
