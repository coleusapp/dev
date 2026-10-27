<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\Hashtags;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\Tag;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Keeps a note's tags in step with the hashtags in its body.
 */
class NoteTags
{
    public static function sync(Note $note): void
    {
        $names = Hashtags::parse($note->body);

        $existing = Tag::query()->whereIn('name', $names)->get()->keyBy('name');

        $tagIds = collect($names)
            ->map(fn (string $name) => ($existing->get($name) ?? Tag::query()->create(['name' => $name]))->id)
            ->all();

        $changes = $note->tags()->sync($tagIds);

        self::deleteUnused($changes['detached']);
    }

    /**
     * Tags that no note uses any more disappear, like in Bear.
     *
     * @param  array<int, int>  $tagIds
     */
    public static function deleteUnused(array $tagIds): void
    {
        if ($tagIds === []) {
            return;
        }

        Tag::query()->whereKey($tagIds)->whereDoesntHave('notes')->get()->each->delete();
    }

    /**
     * deleteUnused() for code running without a signed-in user (console,
     * scheduler): ignores the user scope on both tags and notes, so a tag
     * still used by any note is kept.
     *
     * @param  array<int, int>  $tagIds
     */
    public static function deleteUnusedForAllUsers(array $tagIds): void
    {
        if ($tagIds === []) {
            return;
        }

        Tag::query()
            ->withoutGlobalScope(UserScope::class)
            ->whereKey($tagIds)
            ->whereDoesntHave('notes', fn (Builder $notes) => $notes->withoutGlobalScope(UserScope::class))
            ->get()
            ->each->delete();
    }
}
