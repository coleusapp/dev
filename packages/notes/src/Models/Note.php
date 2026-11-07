<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\Database\Factories\NoteFactory;
use Coleus\Notes\NotesModelDefaults;
use Coleus\Notes\Support\NoteText;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Coleus\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $body
 * @property-read string $title
 * @property Carbon|null $pinned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, User> $users
 *
 * @method static Builder<static>|Note newModelQuery()
 * @method static Builder<static>|Note newQuery()
 * @method static Builder<static>|Note query()
 * @method static Builder<static>|Note user($users)
 * @method static Builder<static>|Note withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Note onlyTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Note extends NotesModelDefaults
{
    /** @uses NoteFactory */
    use HasFactory;

    use HasUser;
    use SoftDeletes;

    protected $fillable = [
        'body',
    ];

    public function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return Attribute<string, never> */
    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => NoteText::title($this->body));
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
