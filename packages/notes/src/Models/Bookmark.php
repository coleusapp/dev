<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\Enums\BookmarkType;
use Coleus\Notes\NotesModelDefaults;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property BookmarkType $type
 * @property int|null $note_id
 * @property string|null $heading
 * @property array<string, mixed>|null $filters
 * @property string|null $label
 * @property string|null $group
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Note|null $note
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bookmark query()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Bookmark extends NotesModelDefaults
{
    use HasUser;

    public const int MAX_LABEL_LENGTH = 100;

    public const int MAX_GROUP_LENGTH = 50;

    protected $fillable = [
        'label',
        'group',
    ];

    public function casts(): array
    {
        return [
            'type' => BookmarkType::class,
            'filters' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Note, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
