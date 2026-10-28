<?php

namespace Coleus\Notes\Enums;

/**
 * What a bookmark points at: a whole note, a heading inside a note, or a
 * saved search (a set of list filters).
 */
enum BookmarkType: string
{
    case Note = 'note';
    case Heading = 'heading';
    case Search = 'search';
}
