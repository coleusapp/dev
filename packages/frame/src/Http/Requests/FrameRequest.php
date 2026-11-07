<?php

namespace Coleus\Frame\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FrameRequest extends FormRequest
{
    public const int MIN_INTERVAL_SECONDS = 10;

    public const int MAX_INTERVAL_SECONDS = 86_400;

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
            'name' => ['required', 'string', 'max:255'],
            'search_query' => ['nullable', 'string', 'max:255'],
            'interval_seconds' => ['required', 'integer', 'min:'.self::MIN_INTERVAL_SECONDS, 'max:'.self::MAX_INTERVAL_SECONDS],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the frame a name.',
            'interval_seconds.min' => 'Show each artwork for at least '.self::MIN_INTERVAL_SECONDS.' seconds.',
            'interval_seconds.max' => 'Show each artwork for at most a day ('.self::MAX_INTERVAL_SECONDS.' seconds).',
        ];
    }

    /**
     * @return array{name: string, search_query: ?string, interval_seconds: int, is_active: bool}
     */
    public function frameData(): array
    {
        return [
            'name' => (string) $this->validated('name'),
            'search_query' => filled($this->validated('search_query')) ? trim($this->validated('search_query')) : null,
            'interval_seconds' => (int) $this->validated('interval_seconds'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
