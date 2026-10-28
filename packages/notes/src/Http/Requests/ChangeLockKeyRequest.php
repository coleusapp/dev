<?php

namespace Coleus\Notes\Http\Requests;

/**
 * A changed notes password: new key parameters, plus every note locked
 * with the old one, re-encrypted with the new one in the browser.
 */
class ChangeLockKeyRequest extends LockKeyRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'notes' => ['present', 'array'],
            'notes.*.id' => ['required', 'integer', 'distinct'],
            'notes.*.iv' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9+\/=]+$/'],
            'notes.*.ciphertext' => ['required', 'string', 'max:'.LockedBodyRequest::MAX_CIPHERTEXT, 'regex:/^[A-Za-z0-9+\/=]+$/'],
        ];
    }

    /**
     * @return array<int, array{iv: string, ciphertext: string}>
     */
    public function reencrypted(): array
    {
        return collect((array) $this->validated('notes'))->mapWithKeys(fn (array $note) => [(int) $note['id'] => ['iv' => $note['iv'], 'ciphertext' => $note['ciphertext']]])->all();
    }
}
