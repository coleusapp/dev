<?php

namespace Coleus\Notes\Bases;

/**
 * The shape of a base's saved definition, and its defaults.
 *
 *   query     a search box query (Search\SearchQuery) choosing the notes;
 *             empty: every note in the main list (not archive or trash)
 *   columns   what to show after the title: note fields (NOTE_FIELDS),
 *             property names as written, or "formula.<name>"
 *   formulas  named Formula expressions, usable as columns, in filters,
 *             in sort and in each other as formula.<name>
 *   filters   rows must pass every one: a column, an OPERATORS key and a value
 *   where     an optional Formula a row must make true
 *   sort      up to MAX_SORT columns, each asc or desc; empty: last edited first
 *   view      how rows show: VIEWS (O11.2)
 *   group_by  a column whose values group the rows (O11.2); empty: no groups
 *
 * @phpstan-type Definition array{
 *     query: string,
 *     columns: list<string>,
 *     formulas: list<array{name: string, expression: string}>,
 *     filters: list<array{column: string, operator: string, value: string}>,
 *     where: string,
 *     sort: list<array{column: string, direction: 'asc'|'desc'}>,
 *     view: 'table'|'cards',
 *     group_by: string,
 * }
 */
final class BaseDefinition
{
    public const int MAX_COLUMNS = 30;

    public const int MAX_FORMULAS = 20;

    public const int MAX_FILTERS = 20;

    public const int MAX_SORT = 3;

    /**
     * The note's own fields, by column id.
     */
    public const array NOTE_FIELDS = [
        'note.title' => 'Title',
        'note.folder' => 'Folder',
        'note.tags' => 'Tags',
        'note.created' => 'Created',
        'note.updated' => 'Edited',
    ];

    /**
     * Filter operators and how they read. empty/not_empty ignore the value.
     */
    public const array OPERATORS = [
        'is' => 'is',
        'is_not' => 'is not',
        'contains' => 'contains',
        'not_contains' => 'does not contain',
        'gt' => '>',
        'gte' => '≥',
        'lt' => '<',
        'lte' => '≤',
        'empty' => 'is empty',
        'not_empty' => 'is not empty',
    ];

    /**
     * The ways a base shows its rows.
     */
    public const array VIEWS = ['table', 'cards'];

    public const string FORMULA_NAME = '/^[\p{L}_][\p{L}\p{N}_]*$/u';

    /**
     * @return Definition
     */
    public static function defaults(): array
    {
        return ['query' => '', 'columns' => ['note.updated'], 'formulas' => [], 'filters' => [], 'where' => '', 'sort' => [], 'view' => 'table', 'group_by' => ''];
    }

    /**
     * Fills in what's missing and drops what doesn't fit, so a stored
     * definition always has every key in the expected shape.
     *
     * @param  array<string, mixed>  $definition
     * @return Definition
     */
    public static function normalize(array $definition): array
    {
        $entries = fn (mixed $list): array => array_values(array_filter((array) $list, fn (mixed $value) => is_array($value)));

        return [
            'query' => trim((string) ($definition['query'] ?? '')),
            'columns' => array_slice(array_values(array_unique(array_filter(
                array_map(fn (mixed $column) => trim((string) $column), (array) ($definition['columns'] ?? self::defaults()['columns'])),
                fn (string $column) => $column !== '' && $column !== 'note.title',
            ))), 0, self::MAX_COLUMNS),
            'formulas' => array_slice(array_values(array_map(
                fn (array $formula) => ['name' => trim((string) ($formula['name'] ?? '')), 'expression' => trim((string) ($formula['expression'] ?? ''))],
                $entries($definition['formulas'] ?? []),
            )), 0, self::MAX_FORMULAS),
            'filters' => array_slice(array_values(array_filter(array_map(
                fn (array $filter) => ['column' => trim((string) ($filter['column'] ?? '')), 'operator' => (string) ($filter['operator'] ?? 'is'), 'value' => trim((string) ($filter['value'] ?? ''))],
                $entries($definition['filters'] ?? []),
            ), fn (array $filter) => $filter['column'] !== '' && array_key_exists($filter['operator'], self::OPERATORS))), 0, self::MAX_FILTERS),
            'where' => trim((string) ($definition['where'] ?? '')),
            'sort' => array_slice(array_values(array_filter(array_map(
                fn (array $sort) => ['column' => trim((string) ($sort['column'] ?? '')), 'direction' => ($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc'],
                $entries($definition['sort'] ?? []),
            ), fn (array $sort) => $sort['column'] !== '')), 0, self::MAX_SORT),
            'view' => in_array($definition['view'] ?? null, self::VIEWS, true) ? $definition['view'] : 'table',
            'group_by' => trim((string) ($definition['group_by'] ?? '')),
        ];
    }

    /**
     * How a column id reads as a header.
     */
    public static function label(string $column): string
    {
        return self::NOTE_FIELDS[mb_strtolower($column)] ?? (str_starts_with(mb_strtolower($column), 'formula.') ? mb_substr($column, 8) : $column);
    }

    public static function kind(string $column): string
    {
        $lower = mb_strtolower($column);

        return match (true) {
            array_key_exists($lower, self::NOTE_FIELDS) => 'note',
            str_starts_with($lower, 'formula.') => 'formula',
            default => 'property',
        };
    }
}
