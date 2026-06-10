<?php

namespace Coleus\Music\Http\Requests;

use Coleus\Music\Models\Track;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaylistRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'tracks.*.track_id' => [
                'nullable',
                'numeric',
                'gte:0',
                Rule::exists(Track::class, 'id'),
            ],
        ];
    }
}
