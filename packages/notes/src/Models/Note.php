<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\Database\Factories\NoteFactory;
use Coleus\Notes\NotesModelDefaults;
use Coleus\Notes\Search\NoteSearch;
use Coleus\Notes\Support\NoteText;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A note: Markdown text, filed by pinning, bookmarking or archiving it.
 * Deleting moves it to the trash (soft delete), where it can be restored.
 * Its title is the first line with text (NoteText::title), not stored.
 *
 * @property int $id
 * @property string $body
 * @property-read string $title
 * @property \Illuminate\Support\Carbon|null $pinned_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $bookmarked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Users\Models\User> $users
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note user($users)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note search(string $terms)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note onlyTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([UserScope::class])]
class Note extends NotesModelDefaults
{
    /** @uses \Coleus\Notes\Database\Factories\NoteFactory */
    use HasFactory;

    use HasUser;
    use SoftDeletes;

    protected $fillable = [
        'body',
    ];

    protected $attributes = [
        'body' => '',
    ];

    public function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'archived_at' => 'datetime',
            'bookmarked_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => NoteText::title($this->body));
    }

    /**
     * Notes whose body contains every word of $terms (see NoteSearch).
     *
     * @param  Builder<Note>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $terms): void
    {
        NoteSearch::apply($query, $terms);
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
