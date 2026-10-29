<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Canvas\CanvasFiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class CanvasImportRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:'.CanvasFiles::MAX_KILOBYTES,
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || ! in_array(mb_strtolower($value->getClientOriginalExtension()), ['canvas', 'json'], true)) {
                        $fail('Choose a .canvas file.');

                        return;
                    }

                    if ($this->canvasJson() === null) {
                        $fail("This file isn't a JSON Canvas file.");
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Choose a .canvas file.',
            'file.max' => 'A .canvas file can be at most '.(CanvasFiles::MAX_KILOBYTES / 1024).' MB.',
        ];
    }

    /**
     * The file's JSON, when it has the shape of a canvas: an object with
     * a nodes and/or edges list.
     *
     * @return array<mixed>|null
     */
    public function canvasJson(): ?array
    {
        $file = $this->file('file');
        $json = $file instanceof UploadedFile ? json_decode((string) file_get_contents($file->getRealPath()), true, 64) : null;

        if (! is_array($json) || array_is_list($json) || (! is_array($json['nodes'] ?? null) && ! is_array($json['edges'] ?? null))) {
            return null;
        }

        return $json;
    }

    /**
     * The canvas's name: the file's, without ".canvas".
     */
    public function canvasName(): string
    {
        $file = $this->file('file');

        return $file instanceof UploadedFile ? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) : '';
    }
}
