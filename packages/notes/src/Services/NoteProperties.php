<?php

namespace Coleus\Notes\Services;

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteProperty;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mirrors notes' front-matter properties into notes_note_properties and
 * answers questions across notes: which properties exist, renaming one
 * everywhere, filtering by a property's value.
 */
class NoteProperties
{
    public static function typeOf(mixed $value): string
    {
        return match (true) {
            is_array($value) => 'list',
            is_bool($value) => 'checkbox',
            is_int($value), is_float($value) => 'number',
            is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $value) === 1 => 'datetime',
            is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 => 'date',
            default => 'text',
        };
    }

    public static function textOf(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_array($value) => mb_strtolower(implode("\n", array_map(fn (mixed $item) => (string) $item, $value))),
            is_bool($value) => $value ? 'true' : 'false',
            default => mb_strtolower((string) $value),
        };
    }

    public static function sync(Note $note): void
    {
        $note->properties()->delete();
        $note->properties()->createMany(collect(FrontMatter::parse($note->body) ?? [])
            ->map(fn (mixed $value, string $key) => [
                'key' => $key,
                'key_lower' => mb_strtolower($key),
                'type' => self::typeOf($value),
                'value' => $value,
                'value_text' => self::textOf($value),
            ])
            ->values()
            ->all());
    }

    /**
     * Every property used in the user's notes (main list and archive, not
     * trash): its name as most often written, the types seen, how many notes
     * use it and a few example values.
     *
     * @return list<array{key: string, types: list<string>, count: int, examples: list<string>}>
     */
    public function overview(): array
    {
        return NoteProperty::query()
            ->whereHas('note')
            ->get(['note_id', 'key', 'key_lower', 'type', 'value_text'])
            ->groupBy('key_lower')
            ->map(fn ($rows) => [
                'key' => $rows->countBy('key')->sortDesc()->keys()->first(),
                'types' => $rows->pluck('type')->unique()->sort()->values()->all(),
                'count' => $rows->pluck('note_id')->unique()->count(),
                'examples' => $rows->pluck('value_text')->filter()->flatMap(fn (string $text) => explode("\n", $text))->unique()->take(5)->values()->all(),
            ])
            ->sortBy(fn (array $property) => mb_strtolower($property['key']), SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * Renames a property in every note that has it (any case), keeping
     * values and formatting. Notes are rewritten through the normal update
     * path, so versions and indexes follow. Returns how many notes changed.
     */
    public function rename(string $from, string $to): int
    {
        $notes = Note::query()
            ->withTrashed()
            ->whereHas('properties', fn (Builder $query) => $query->where('key_lower', mb_strtolower($from)))
            ->get();

        foreach ($notes as $note) {
            $renamed = FrontMatter::renameKey($note->body, $from, $to);

            if ($renamed !== $note->body) {
                $note->trashed()
                    ? Note::withoutTimestamps(fn () => $note->forceFill(['body' => $renamed])->save())
                    : app(NoteService::class)->update($note, ['body' => $renamed]);
            }
        }

        return $notes->count();
    }

    /**
     * Notes having the property, optionally with a value: lists match an
     * item, other values match exactly (case-insensitive).
     *
     * @param  Builder<Note>  $query
     * @return Builder<Note>
     */
    public static function whereProperty(Builder $query, string $key, ?string $value): Builder
    {
        return $query->whereHas('properties', function (Builder $properties) use ($key, $value) {
            $properties->where('key_lower', mb_strtolower($key));

            if ($value !== null) {
                $needle = mb_strtolower($value);
                $escaped = NoteService::escapeLike($needle);
                $properties->where(fn (Builder $match) => $match
                    ->where('value_text', $needle)
                    ->orWhere(fn (Builder $list) => $list->where('type', 'list')->where(fn (Builder $items) => $items
                        ->whereRaw("value_text like ? escape '!'", [$escaped."\n%"])
                        ->orWhereRaw("value_text like ? escape '!'", ["%\n".$escaped])
                        ->orWhereRaw("value_text like ? escape '!'", ["%\n".$escaped."\n%"]))));
            }
        });
    }
}
