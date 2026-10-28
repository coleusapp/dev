/**
 * Encryption for password-locked notes (O12.5), all in the browser with
 * Web Crypto: the notes password goes through PBKDF2-SHA-256 (600,000
 * iterations, random 16-byte salt) to an AES-256-GCM key that can't be
 * exported. Each save uses a fresh 12-byte IV. The server only ever sees
 * the salt, the iteration count, ciphertexts and a "verifier" (a known
 * text encrypted with the key), which tells a right password from a wrong
 * one without revealing anything about it.
 */
export const PBKDF2_ITERATIONS = 600_000;

const VERIFIER_TEXT = 'coleus-notes-lock-v1';

export type LockKeyParams = { id?: number; salt: string; iterations: number; verifier_iv: string; verifier: string; hint: string | null };

export type Encrypted = { iv: string; ciphertext: string };

export const toBase64 = (bytes: Uint8Array): string => {
    let binary = '';

    bytes.forEach((byte) => (binary += String.fromCharCode(byte)));

    return btoa(binary);
};

export const fromBase64 = (text: string): Uint8Array<ArrayBuffer> => Uint8Array.from(atob(text), (character) => character.charCodeAt(0));

const random = (length: number): Uint8Array<ArrayBuffer> => crypto.getRandomValues(new Uint8Array(length));

export const deriveKey = async (password: string, salt: string, iterations: number): Promise<CryptoKey> => {
    const material = await crypto.subtle.importKey('raw', new TextEncoder().encode(password), 'PBKDF2', false, ['deriveKey']);

    return crypto.subtle.deriveKey(
        { name: 'PBKDF2', salt: fromBase64(salt), iterations, hash: 'SHA-256' },
        material,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt'],
    );
};

export const encryptText = async (key: CryptoKey, text: string): Promise<Encrypted> => {
    const iv = random(12);
    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, new TextEncoder().encode(text));

    return { iv: toBase64(iv), ciphertext: toBase64(new Uint8Array(ciphertext)) };
};

/**
 * Throws when the key is wrong or the ciphertext was changed (GCM checks it).
 */
export const decryptText = async (key: CryptoKey, encrypted: Encrypted): Promise<string> => {
    const plain = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: fromBase64(encrypted.iv) }, key, fromBase64(encrypted.ciphertext));

    return new TextDecoder().decode(plain);
};

/**
 * A new notes password: the key, and the parameters the server keeps.
 */
export const createLockKey = async (
    password: string,
    hint: string | null,
    iterations = PBKDF2_ITERATIONS,
): Promise<{ key: CryptoKey; params: LockKeyParams }> => {
    const salt = toBase64(random(16));
    const key = await deriveKey(password, salt, iterations);
    const verifier = await encryptText(key, VERIFIER_TEXT);

    return { key, params: { salt, iterations, verifier_iv: verifier.iv, verifier: verifier.ciphertext, hint: hint?.trim() || null } };
};

/**
 * The key for a typed password, or null when it isn't the right one.
 */
export const unlockKey = async (password: string, params: LockKeyParams): Promise<CryptoKey | null> => {
    const key = await deriveKey(password, params.salt, params.iterations);

    try {
        return (await decryptText(key, { iv: params.verifier_iv, ciphertext: params.verifier })) === VERIFIER_TEXT ? key : null;
    } catch {
        return null;
    }
};
