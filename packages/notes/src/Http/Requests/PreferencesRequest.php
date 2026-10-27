<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Preferences\NotePreferences;
use Illuminate\Foundation\Http\FormRequest;

class PreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return NotePreferences::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.in' => 'Pick one of the listed options.',
            '*.boolean' => 'This setting is either on or off.',
        ];
    }

    /**
     * The validated input keyed by preference name, with types normalized.
     *
     * @return array<string, mixed>
     */
    public function preferences(): array
    {
        $values = [];

        foreach (NotePreferences::DEFINITIONS as $key => $definition) {
            $input = NotePreferences::inputName($key);

            if (! array_key_exists($input, $this->validated())) {
                continue;
            }

            $value = $this->validated($input);
            $values[$key] = match (true) {
                ($definition['type'] ?? null) === 'boolean' => (bool) $value,
                is_int($definition['default']) => (int) $value,
                default => (string) $value,
            };
        }

        return $values;
    }
}
