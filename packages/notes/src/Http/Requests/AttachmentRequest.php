<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Enums\AttachmentKind;
use Illuminate\Foundation\Http\FormRequest;

/**
 * An uploaded file. Its type and kind-specific size are checked by
 * Services\Attachments on the sniffed content; here only that it is a
 * file within the largest limit.
 */
class AttachmentRequest extends FormRequest
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
        $largest = max(array_map(fn (AttachmentKind $kind) => $kind->maxKilobytes(), AttachmentKind::cases()));

        return [
            'file' => ['required', 'file', 'max:'.$largest],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'This file is too big to attach.',
            'file.uploaded' => 'The upload failed; the file may be bigger than the server allows.',
        ];
    }
}
