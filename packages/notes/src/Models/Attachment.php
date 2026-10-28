<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\Enums\AttachmentKind;
use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * A file uploaded into notes (O3.1). Notes refer to it by its URL
 * (/notes/attachments/{uuid}/{name}); the file lives on the attachments
 * disk at `path`, never in a public place.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $mime
 * @property AttachmentKind $kind
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string $path
 * @property array<string, string>|null $variants
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment query()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Attachment extends NotesModelDefaults
{
    use HasUser;

    protected $fillable = [
        'uuid',
        'name',
        'mime',
        'kind',
        'size',
        'path',
    ];

    public function casts(): array
    {
        return [
            'kind' => AttachmentKind::class,
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Where notes link to it; the name is only there to read well.
     */
    public function url(): string
    {
        return route('notes.attachments.show', ['attachment' => $this->uuid, 'name' => $this->name], false);
    }

    /**
     * The Markdown a note gets for it: images, video, audio and PDFs show
     * in place (![…](…), rendered as players for media, O3.4); other files link.
     */
    public function markdown(): string
    {
        $label = str_replace(['[', ']'], ['(', ')'], $this->name);
        $url = str_replace([' ', '(', ')'], ['%20', '%28', '%29'], $this->url());

        $shows = $this->kind !== AttachmentKind::Document || $this->mime === 'application/pdf';

        return $shows ? "![{$label}]({$url})" : "[{$label}]({$url})";
    }

    /**
     * @return array{uuid: string, name: string, mime: string, kind: string, size: int, url: string, markdown: string}
     */
    public function toClient(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'mime' => $this->mime,
            'kind' => $this->kind->value,
            'size' => $this->size,
            'url' => $this->url(),
            'markdown' => $this->markdown(),
        ];
    }
}
