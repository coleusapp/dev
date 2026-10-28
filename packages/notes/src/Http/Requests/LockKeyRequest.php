<?php

namespace Coleus\Notes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A new notes password, as the browser describes it: never the password,
 * only the PBKDF2 salt and iterations and the encrypted verifier.
 */
class LockKeyRequest extends FormRequest
{
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
            'salt' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9+\/=]+$/'],
            'iterations' => ['required', 'integer', 'min:100000', 'max:10000000'],
            'verifier_iv' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9+\/=]+$/'],
            'verifier' => ['required', 'string', 'max:1000', 'regex:/^[A-Za-z0-9+\/=]+$/'],
            'hint' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'iterations.min' => 'The key must be derived with at least 100,000 iterations.',
        ];
    }
}
