<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Models\Note;
use Illuminate\Http\Request;

/** @mixin Note */
class NoteDetailResource extends NoteResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'body' => $this->body,
        ];
    }
}
