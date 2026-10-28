<?php

namespace Coleus\Notes\Markdown\Attachments;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * ![[file.png]] or ![[file.png|300]] / ![[file.png|300x200]]: an attached
 * file shown in the note by its name (O3.2).
 */
class AttachmentEmbed extends AbstractInline
{
    public function __construct(public readonly string $name, public readonly ?int $width = null, public readonly ?int $height = null)
    {
        parent::__construct();
    }
}
