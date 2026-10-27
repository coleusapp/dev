<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\Database\Factories\NoteFactory;
use Coleus\Notes\NotesModelDefaults;
use Coleus\Notes\Support\NoteText;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $pinned_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Users\Models\User> $users
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note user($users)
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Note extends NotesModelDefaults
{
    /** @uses \Coleus\Notes\Database\Factories\NoteFactory */
    use HasFactory;

    use HasUser;

    protected $fillable = [
        'body',
    ];

    protected $attributes = [
        'title' => '',
        'body' => '',
    ];

    public function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Note $note) {
            $note->title = NoteText::title($note->body);
        });
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->users()->whereKey($userId)->exists();
    }

    protected static function newFactory(): NoteFactory|Factory
    {
        return NoteFactory::new();
    }
}
