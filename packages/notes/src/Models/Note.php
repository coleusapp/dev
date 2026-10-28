<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\Database\Factories\NoteFactory;
use Coleus\Notes\NotesModelDefaults;
use Coleus\Notes\Search\NoteSearch;
use Coleus\Notes\Services\NoteAliases;
use Coleus\Notes\Services\NoteProperties;
use Coleus\Notes\Services\NoteTags;
use Coleus\Notes\Services\NoteVersions;
use Coleus\Notes\Support\NoteText;
use Coleus\Users\Concerns\HasUser;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $folder_id
 * @property string $title
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $pinned_at
 * @property \Illuminate\Support\Carbon|null $read_only_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Users\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NoteVersion> $versions
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
        'title' => '',
        'body' => '',
    ];

    public function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'read_only_at' => 'datetime',
            'archived_at' => 'datetime',
            'opened_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Note $note) {
            $note->title = NoteText::title($note->body);
        });

        static::saved(function (Note $note) {
            if ($note->wasRecentlyCreated || $note->wasChanged('body')) {
                NoteTags::sync($note);
                NoteAliases::sync($note);
                NoteProperties::sync($note);
                NoteVersions::record($note);
            }
        });
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, config('notes.table_prefix').'note_tag');
    }

    /**
     * @return HasMany<NoteProperty, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(NoteProperty::class);
    }

    /**
     * @return HasMany<NoteAlias, $this>
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(NoteAlias::class);
    }

    /**
     * @return HasMany<NoteVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(NoteVersion::class);
    }

    /**
     * Notes whose title or body contain every word of $terms (see NoteSearch).
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
