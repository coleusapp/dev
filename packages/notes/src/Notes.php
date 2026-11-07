<?php

namespace Coleus\Notes;

use Coleus\Notes\Services\NoteService;

class Notes
{
    public static function note(): NoteService
    {
        return new NoteService;
    }
}
