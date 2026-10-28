<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Models\NoteLockKey;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Encrypted note text from the browser: base64 IV and ciphertext, the
 * readable title, and (when locking) which notes password it used.
 */
class LockedBodyRequest extends FormRequest
{
    /**
     * Ciphertext is base64 of the text plus a 16-byte tag, so it may be a
     * third longer than the longest note body.
     */
    public const int MAX_CIPHERTEXT = 2_000_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key_id' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'integer'],
            'iv' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9+\/=]+$/'],
            'ciphertext' => ['required', 'string', 'max:'.self::MAX_CIPHERTEXT, 'regex:/^[A-Za-z0-9+\/=]+$/'],
            'title' => ['present', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The user's own key; the user scope keeps other users' keys out.
     */
    public function lockKey(): ?NoteLockKey
    {
        return NoteLockKey::query()->find($this->integer('key_id'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ciphertext.regex' => 'The locked text must be base64.',
        ];
    }
}
