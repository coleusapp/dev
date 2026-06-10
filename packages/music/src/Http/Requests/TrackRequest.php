<?php

namespace Coleus\Music\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackRequest extends FormRequest
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
            'title' => ['required', 'string'],
            'artist_id' => ['required', 'integer'],
            'album_id' => ['nullable', 'integer'],
            'genre_id' => ['nullable', 'integer'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'track_number' => ['nullable', 'integer', 'min:1'],
            'file' => ['nullable', 'file', 'mimes:mp3,flac,wav,aac,m4a,ogg'],
        ];
    }
}
