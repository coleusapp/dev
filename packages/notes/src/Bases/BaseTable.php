<?php

namespace Coleus\Notes\Bases;

use Carbon\CarbonImmutable;
use Closure;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteProperty;
use Coleus\Notes\Models\Tag;
use Coleus\Notes\Search\SearchQuery;
use Coleus\Notes\Services\NoteThumbnails;
use Coleus\Notes\Support\NoteText;
use Illuminate\Support\Collection;

/**
 * Builds a base's rows (O11.1): the notes its query picks, each with a
 * value per column, kept by its filters and where-formula, then sorted.
 *
 * Values come from one resolver per row (resolver()), shared by columns,
 * filters, sort and formulas: note.* fields, formula.* (worked out once
 * per row, loops reported) and otherwise properties by name in any case.
 * A formula that fails leaves its cell empty with the error beside it.
 *
 * Grouped (O11.2), rows come group by group, groups in ascending order of
 * their value with the empty group last; each group's rows keep the sort.
 * The cards view adds each note's picture or, failing that, its snippet.
 *
 * @phpstan-import-type Definition from BaseDefinition
 *
 * @phpstan-type Column array{id: string, label: string, kind: string}
 * @phpstan-type Row array{id: int, title: string, locked: bool, cells: array<string, mixed>, errors: array<string, string>, thumbnail?: string|null, snippet?: string}
 * @phpstan-type Group array{value: mixed, total: int, count: int}
 * @phpstan-type Table array{columns: list<Column>, rows: list<Row>, total: int, groups: list<Group>|null, errors: array<string, string>}
 */
final class BaseTable
{
    public const int MAX_ROWS = 500;

    /**
     * @param  array<string, Formula|FormulaError>  $formulas  by lowercased name
     */
    private function __construct(
        private readonly array $formulas,
        private readonly CarbonImmutable $now,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  int  $limit  how many rows to send (an embed shows fewer)
     * @return Table
     */
    public static function build(array $definition, ?CarbonImmutable $now = null, int $limit = self::MAX_ROWS): array
    {
        $definition = BaseDefinition::normalize($definition);
        $errors = [];
        $formulas = [];

        foreach ($definition['formulas'] as $formula) {
            $formulas[mb_strtolower($formula['name'])] = self::read($formula['expression']);

            if ($formulas[mb_strtolower($formula['name'])] instanceof FormulaError) {
                $errors['formula.'.$formula['name']] = $formulas[mb_strtolower($formula['name'])]->getMessage();
            }
        }

        $where = $definition['where'] === '' ? null : self::read($definition['where']);

        if ($where instanceof FormulaError) {
            $errors['where'] = $where->getMessage();
            $where = null;
        }

        $now ??= CarbonImmutable::now();
        $table = new self($formulas, $now);
        $search = SearchQuery::parse($definition['query']);

        $notes = $search->filter($search->apply(Note::query()->whereNull('archived_at'))
            ->with(['properties', 'tags', 'folder'])
            ->orderByDesc('updated_at')
            ->get());

        $rows = $notes
            ->map(fn (Note $note) => ['note' => $note, 'resolve' => $table->resolver($note)])
            ->filter(fn (array $row) => $table->passes($row['resolve'], $definition['filters']))
            ->filter(function (array $row) use ($where, $now, &$errors) {
                if ($where === null) {
                    return true;
                }

                try {
                    return FormulaEvaluator::truthy($where->evaluate($row['resolve'], $now));
                } catch (FormulaError $error) {
                    $errors['where'] ??= $error->getMessage();

                    return false;
                }
            });

        $rows = self::sort($rows, $definition['sort']);
        $groups = null;

        if ($definition['group_by'] !== '') {
            [$rows, $groups] = self::group($rows, $definition['group_by'], $limit);
        }

        $sent = $rows->take($limit)->values();
        $thumbnails = $definition['view'] === 'cards' ? app(NoteThumbnails::class)->for($sent->pluck('note')) : null;

        return [
            'columns' => array_map(fn (string $column) => [
                'id' => $column,
                'label' => BaseDefinition::label($column),
                'kind' => BaseDefinition::kind($column),
            ], $definition['columns']),
            'rows' => $sent->map(fn (array $row) => [
                ...$table->row($row['note'], $row['resolve'], $definition['columns']),
                ...($thumbnails === null ? [] : [
                    'thumbnail' => $thumbnails[$row['note']->id] ?? null,
                    'snippet' => $row['note']->isLocked() ? '' : NoteText::snippet($row['note']->body),
                ]),
            ])->all(),
            'total' => $rows->count(),
            'groups' => $groups,
            'errors' => $errors,
        ];
    }

    /**
     * A row's values by name. See the class comment.
     *
     * @return Closure(string): mixed
     */
    private function resolver(Note $note): Closure
    {
        $fields = [
            'note.title' => $note->title,
            'note.folder' => $note->folder?->name,
            'note.tags' => $note->tags->map(fn (Tag $tag) => $tag->name)->sort(SORT_NATURAL)->values()->all(),
            'note.created' => $note->created_at?->format('Y-m-d\TH:i'),
            'note.updated' => $note->updated_at?->format('Y-m-d\TH:i'),
        ];
        $properties = $note->properties->mapWithKeys(fn (NoteProperty $property) => [$property->key_lower => $property->value])->all();
        $worked = [];
        $working = [];

        $resolve = function (string $name) use (&$resolve, &$worked, &$working, $fields, $properties): mixed {
            $lower = mb_strtolower($name);

            if (str_starts_with($lower, 'note.')) {
                return $fields[$lower] ?? null;
            }

            if (! str_starts_with($lower, 'formula.')) {
                return $properties[$lower] ?? null;
            }

            $formulaName = mb_substr($lower, 8);

            if (array_key_exists($formulaName, $worked)) {
                return $worked[$formulaName];
            }

            $formula = $this->formulas[$formulaName] ?? new FormulaError('There is no formula named "'.mb_substr($name, 8).'".');

            if ($formula instanceof FormulaError) {
                throw $formula;
            }

            if (in_array($formulaName, $working, true)) {
                throw new FormulaError('"'.mb_substr($name, 8).'" depends on itself.');
            }

            $working[] = $formulaName;

            try {
                return $worked[$formulaName] = $formula->evaluate($resolve, $this->now);
            } finally {
                array_pop($working);
            }
        };

        return $resolve;
    }

    /**
     * A value for filtering and sorting: a failing formula counts as empty.
     *
     * @param  Closure(string): mixed  $resolve
     */
    private static function value(Closure $resolve, string $column): mixed
    {
        try {
            return $resolve($column);
        } catch (FormulaError) {
            return null;
        }
    }

    /**
     * @param  Closure(string): mixed  $resolve
     * @param  list<array{column: string, operator: string, value: string}>  $filters
     */
    private function passes(Closure $resolve, array $filters): bool
    {
        foreach ($filters as $filter) {
            if (! self::matches(self::value($resolve, $filter['column']), $filter['operator'], $filter['value'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * One filter on one value. Text matches without case; a list "is" a
     * value when an item is, and "contains" when an item does; numbers and
     * ISO dates compare in order; an empty value is never > or <.
     */
    public static function matches(mixed $value, string $operator, string $expected): bool
    {
        $empty = $value === null || $value === '' || $value === [];
        $items = is_array($value) ? $value : [$value];
        $needle = mb_strtolower($expected);
        $equals = fn (mixed $item): bool => mb_strtolower(Formula::text($item)) === $needle
            || (is_numeric($expected) && (is_int($item) || is_float($item)) && (float) $item === (float) $expected);
        $contains = fn (mixed $item): bool => str_contains(mb_strtolower(Formula::text($item)), $needle);

        return match ($operator) {
            'is' => $expected === '' ? $empty : ! $empty && array_filter($items, $equals) !== [],
            'is_not' => $expected === '' ? ! $empty : $empty || array_filter($items, $equals) === [],
            'contains' => ! $empty && array_filter($items, $contains) !== [],
            'not_contains' => $empty || array_filter($items, $contains) === [],
            'gt' => ! $empty && FormulaEvaluator::compare($value, $expected) > 0,
            'gte' => ! $empty && FormulaEvaluator::compare($value, $expected) >= 0,
            'lt' => ! $empty && FormulaEvaluator::compare($value, $expected) < 0,
            'lte' => ! $empty && FormulaEvaluator::compare($value, $expected) <= 0,
            'empty' => $empty,
            'not_empty' => ! $empty,
            default => true,
        };
    }

    /**
     * Sorted by each sort column in turn, empty values last either way,
     * then by the incoming order (last edited first).
     *
     * @param  Collection<int, array{note: Note, resolve: Closure(string): mixed}>  $rows
     * @param  list<array{column: string, direction: 'asc'|'desc'}>  $sort
     * @return Collection<int, array{note: Note, resolve: Closure(string): mixed}>
     */
    private static function sort(Collection $rows, array $sort): Collection
    {
        if ($sort === []) {
            return $rows->values();
        }

        $keyed = $rows->values()->map(fn (array $row, int $position) => [
            ...$row,
            'position' => $position,
            'keys' => array_map(fn (array $by) => self::value($row['resolve'], $by['column']), $sort),
        ])->all();

        usort($keyed, function (array $a, array $b) use ($sort): int {
            foreach ($sort as $index => $by) {
                [$x, $y] = [$a['keys'][$index], $b['keys'][$index]];
                $xEmpty = $x === null || $x === '' || $x === [];
                $yEmpty = $y === null || $y === '' || $y === [];

                $order = match (true) {
                    $xEmpty && $yEmpty => 0,
                    $xEmpty => 1,
                    $yEmpty => -1,
                    default => FormulaEvaluator::compare($x, $y) * ($by['direction'] === 'desc' ? -1 : 1),
                };

                if ($order !== 0) {
                    return $order;
                }
            }

            return $a['position'] <=> $b['position'];
        });

        return collect($keyed)->map(fn (array $row) => ['note' => $row['note'], 'resolve' => $row['resolve']]);
    }

    /**
     * Rows reordered group by group, and the groups in that order with how
     * many rows each has and how many of those are sent ($limit). A list
     * value groups as its items joined; text groups without case.
     *
     * @param  Collection<int, array{note: Note, resolve: Closure(string): mixed}>  $rows
     * @return array{0: Collection<int, array{note: Note, resolve: Closure(string): mixed}>, 1: list<Group>}
     */
    private static function group(Collection $rows, string $column, int $limit): array
    {
        $buckets = [];

        foreach ($rows as $row) {
            $value = self::value($row['resolve'], $column);
            $value = is_array($value) ? ($value === [] ? null : Formula::text($value)) : ($value === '' ? null : $value);
            $key = $value === null ? "\0" : mb_strtolower(Formula::text($value));
            $buckets[$key] ??= ['value' => $value, 'rows' => []];
            $buckets[$key]['rows'][] = $row;
        }

        uasort($buckets, fn (array $a, array $b) => match (true) {
            $a['value'] === null => 1,
            $b['value'] === null => -1,
            default => FormulaEvaluator::compare($a['value'], $b['value']),
        });

        $groups = [];
        $left = $limit;

        foreach ($buckets as $bucket) {
            $count = min(count($bucket['rows']), $left);
            $left -= $count;

            if ($count > 0) {
                $groups[] = ['value' => $bucket['value'], 'total' => count($bucket['rows']), 'count' => $count];
            }
        }

        return [collect($buckets)->flatMap(fn (array $bucket) => $bucket['rows'])->values(), $groups];
    }

    /**
     * @param  Closure(string): mixed  $resolve
     * @param  list<string>  $columns
     * @return Row
     */
    private function row(Note $note, Closure $resolve, array $columns): array
    {
        $cells = [];
        $errors = [];

        foreach ($columns as $column) {
            try {
                $cells[$column] = $resolve($column);
            } catch (FormulaError $error) {
                $cells[$column] = null;
                $errors[$column] = $error->getMessage();
            }
        }

        return ['id' => $note->id, 'title' => $note->title, 'locked' => $note->isLocked(), 'cells' => $cells, 'errors' => $errors];
    }

    private static function read(string $expression): Formula|FormulaError
    {
        try {
            return Formula::parse($expression);
        } catch (FormulaError $error) {
            return $error;
        }
    }
}
