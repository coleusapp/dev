<?php

namespace Coleus\Notes\Preferences;

use Coleus\Notes\Facades\Settings;
use Illuminate\Validation\Rule;

/**
 * The signed-in user's Notes preferences, kept in coleus/settings (the
 * shared settings table, scoped to the Notes app and the user) under
 * "notes.<key>".
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
        'editor.autosave_delay' => ['default' => 750, 'options' => [500, 750, 1500, 3000]],
        'editor.readable_width' => ['default' => true, 'type' => 'boolean'],
        'editor.text_direction' => ['default' => 'auto', 'options' => ['auto', 'ltr', 'rtl']],
        'list.sort' => ['default' => 'modified', 'options' => ['modified', 'created']],
        'list.sort_direction' => ['default' => 'desc', 'options' => ['desc', 'asc']],
        'list.relative_dates' => ['default' => true, 'type' => 'boolean'],
        'list.show_snippets' => ['default' => true, 'type' => 'boolean'],
    ];

    /**
     * Every preference for the current user, defaults filled in.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $stored = auth()->id() === null ? [] : Settings::forUser()->all();

        return collect(self::DEFINITIONS)
            ->mapWithKeys(function (array $definition, string $key) use ($stored) {
                $value = $stored[self::settingName($key)] ?? null;

                return [$key => self::isValid($key, $value) ? $value : $definition['default']];
            })
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
        foreach (array_intersect_key($values, self::DEFINITIONS) as $key => $value) {
            Settings::forUser()->set(self::settingName($key), $value);
        }
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
     * Request field names use underscores: "editor.autosave_delay" is sent as
     * "editor_autosave_delay", because dots mean nesting in Laravel validation.
     */
    public static function inputName(string $key): string
    {
        return str_replace('.', '_', $key);
    }

    /**
     * The name in the settings table: "notes.editor.autosave_delay".
     */
    public static function settingName(string $key): string
    {
        return config('notes.settings_prefix').'.'.$key;
    }

    private static function isValid(string $key, mixed $value): bool
    {
        $definition = self::DEFINITIONS[$key];

        if (($definition['type'] ?? null) === 'boolean') {
            return is_bool($value);
        }

        return in_array($value, $definition['options'] ?? [], true);
    }
}
