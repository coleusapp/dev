<?php

namespace Coleus\Notes\Preferences;

use Coleus\Notes\Models\Preferences;
use Illuminate\Validation\Rule;

/**
 * The signed-in user's Notes preferences.
 *
 * DEFINITIONS is the single list of preference keys: each has a default and
 * either a list of allowed options or a "boolean" type. Validation rules,
 * defaults and the settings page all come from it, so adding a preference
 * means adding one entry here and a field on the Settings page.
 */
class NotePreferences
{
    /**
     * @var array<string, array{default: string|int|bool, options?: list<string|int>, type?: 'boolean'}>
     */
    public const array DEFINITIONS = [
        'editor.default_mode' => ['default' => 'edit', 'options' => ['edit', 'source', 'split', 'preview']],
        'editor.autosave_delay' => ['default' => 750, 'options' => [500, 750, 1500, 3000]],
        'editor.readable_width' => ['default' => true, 'type' => 'boolean'],
        'editor.spellcheck' => ['default' => true, 'type' => 'boolean'],
        'editor.text_direction' => ['default' => 'auto', 'options' => ['auto', 'ltr', 'rtl']],
        'editor.first_line_style' => ['default' => 'body', 'options' => ['title', 'heading', 'subheading', 'body']],
        'checklists.checked_to_bottom' => ['default' => false, 'type' => 'boolean'],
        'list.sort' => ['default' => 'modified', 'options' => ['modified', 'created', 'title']],
        'list.sort_direction' => ['default' => 'desc', 'options' => ['desc', 'asc']],
        'list.group_by_date' => ['default' => false, 'type' => 'boolean'],
        'list.relative_dates' => ['default' => true, 'type' => 'boolean'],
        'list.show_snippets' => ['default' => true, 'type' => 'boolean'],
        'search.sort' => ['default' => 'relevance', 'options' => ['relevance', 'list']],
        'reading.line_breaks' => ['default' => 'soft', 'options' => ['soft', 'hard']],
    ];

    /**
     * Every preference for the current user, defaults filled in.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $stored = self::row()?->values ?? [];

        return collect(self::DEFINITIONS)
            ->mapWithKeys(fn (array $definition, string $key) => [
                $key => self::isValid($key, $stored[$key] ?? null) ? $stored[$key] : $definition['default'],
            ])
            ->all();
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Stores the given preferences for the current user; unknown keys are ignored.
     *
     * @param  array<string, mixed>  $values
     */
    public static function save(array $values): void
    {
        $known = array_intersect_key($values, self::DEFINITIONS);

        Preferences::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['values' => [...self::all(), ...$known]],
        );
    }

    /**
     * Validation rules for a partial update (each key optional).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return collect(self::DEFINITIONS)
            ->mapWithKeys(fn (array $definition, string $key) => [
                self::inputName($key) => ($definition['type'] ?? null) === 'boolean'
                    ? ['sometimes', 'boolean']
                    : ['sometimes', Rule::in($definition['options'] ?? [])],
            ])
            ->all();
    }

    /**
     * Request field names use underscores: "editor.default_mode" is sent as
     * "editor_default_mode", because dots mean nesting in Laravel validation.
     */
    public static function inputName(string $key): string
    {
        return str_replace('.', '_', $key);
    }

    private static function isValid(string $key, mixed $value): bool
    {
        $definition = self::DEFINITIONS[$key];

        if (($definition['type'] ?? null) === 'boolean') {
            return is_bool($value);
        }

        return in_array($value, $definition['options'] ?? [], true);
    }

    private static function row(): ?Preferences
    {
        $userId = auth()->id();

        return $userId === null ? null : Preferences::query()->where('user_id', $userId)->first();
    }
}
