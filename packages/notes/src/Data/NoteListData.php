<?php

namespace Coleus\Notes\Data;

use Coleus\Notes\Enums\NoteView;
use Spatie\LaravelData\Data;

class NoteListData extends Data
{
    public function __construct(
        public ?string $q = null,
        public NoteView $view = NoteView::Notes,
    ) {
        //
    }
}
