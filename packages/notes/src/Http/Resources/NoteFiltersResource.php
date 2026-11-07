<?php

namespace Coleus\Notes\Http\Resources;

use Coleus\Notes\Data\NoteListData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NoteListData */
class NoteFiltersResource extends JsonResource
{
    /** @return array{q: string|null, view: string} */
    public function toArray(Request $request): array
    {
        return [
            'q' => $this->q,
            'view' => $this->view->value,
        ];
    }
}
