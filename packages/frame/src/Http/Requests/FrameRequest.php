<?php

namespace Coleus\Frame\Http\Requests;

use Coleus\Frame\Facades\Frames;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FrameRequest extends FormRequest
{
    public const int MIN_INTERVAL_SECONDS = 10;

    public const int MAX_INTERVAL_SECONDS = 86_400;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'search_query' => ['nullable', 'string', 'max:255'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['required', 'string', 'distinct', Rule::in(Frames::artwork()->sourceKeys())],
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
            'sources.required' => 'Pick at least one source.',
            'sources.min' => 'Pick at least one source.',
            'sources.*.in' => 'Pick sources from the list.',
            'interval_seconds.min' => 'Show each artwork for at least '.self::MIN_INTERVAL_SECONDS.' seconds.',
            'interval_seconds.max' => 'Show each artwork for at most a day ('.self::MAX_INTERVAL_SECONDS.' seconds).',
        ];
    }
}
