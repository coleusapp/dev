<?php

namespace Coleus\Notes\Markdown\Bases;

use Carbon\CarbonImmutable;
use Coleus\Notes\Bases\BaseDefinition;
use Coleus\Notes\Bases\BaseTable;
use Coleus\Notes\Bases\Formula;
use Coleus\Notes\Models\Base;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * Renders an embedded base (O11.3) as plain HTML: a title linking to the
 * base, then its first EMBED_ROWS rows as the base's table or cards, read
 * only. Values read as on the Bases page (lib/bases.ts cellText): dates
 * as "Nov 1, 2026", lists as chips, booleans as ticks.
 *
 * @phpstan-import-type Table from BaseTable
 * @phpstan-import-type Row from BaseTable
 */
class BaseEmbedRenderer implements NodeRendererInterface
{
    public const int EMBED_ROWS = 50;

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        BaseEmbed::assertInstanceOf($node);

        $base = Base::query()->whereRaw('lower(name) = ?', [mb_strtolower($node->name)])->orderBy('id')->first();

        if ($base === null) {
            return new HtmlElement('p', ['class' => 'base-embed-missing'], 'No base named “'.Xml::escape($node->name).'”.');
        }

        $definition = BaseDefinition::normalize($base->definition);
        $definition['view'] = $node->view ?? $definition['view'];
        $table = BaseTable::build($definition, null, self::EMBED_ROWS);
        $url = route('notes.bases.show', ['base' => $base->id], false);
        $shown = count($table['rows']);

        return new HtmlElement('div', ['class' => 'base-embed', 'data-base-id' => (string) $base->id], [
            new HtmlElement('div', ['class' => 'base-embed-title'], [
                new HtmlElement('a', ['href' => $url], Xml::escape($base->name)),
                new HtmlElement('span', ['class' => 'base-embed-count'], $table['total'] === 1 ? '1 note' : "{$table['total']} notes"),
            ]),
            new HtmlElement('div', ['class' => 'base-embed-body'], $shown === 0
                ? new HtmlElement('p', ['class' => 'base-embed-missing'], 'No notes match.')
                : ($definition['view'] === 'cards' ? $this->cards($table, $definition['group_by']) : $this->table($table, $definition['group_by']))),
            ...($table['total'] > $shown ? [new HtmlElement('p', ['class' => 'base-embed-more'], new HtmlElement('a', ['href' => $url], "Showing {$shown} of {$table['total']}. Open the base"))] : []),
        ]);
    }

    /**
     * @param  Table  $table
     */
    private function table(array $table, string $groupBy): HtmlElement
    {
        $headers = [new HtmlElement('th', ['scope' => 'col'], 'Title')];

        foreach ($table['columns'] as $column) {
            $headers[] = new HtmlElement('th', ['scope' => 'col'], Xml::escape($column['label']));
        }

        $bodies = [];

        foreach ($this->sections($table) as [$group, $rows]) {
            $lines = $group === false ? [] : [new HtmlElement('tr', ['class' => 'base-embed-group'], new HtmlElement('th', ['scope' => 'rowgroup', 'colspan' => (string) count($headers)], $this->groupHeading($group, $groupBy)))];

            foreach ($rows as $row) {
                $cells = [new HtmlElement('th', ['scope' => 'row'], $this->titleLink($row))];

                foreach ($table['columns'] as $column) {
                    $cells[] = new HtmlElement('td', [], $this->cell($row, $column['id']));
                }

                $lines[] = new HtmlElement('tr', [], $cells);
            }

            $bodies[] = new HtmlElement('tbody', [], $lines);
        }

        return new HtmlElement('div', ['class' => 'base-embed-scroll'], new HtmlElement('table', ['class' => 'base-embed-table'], [
            new HtmlElement('thead', [], new HtmlElement('tr', [], $headers)),
            ...$bodies,
        ]));
    }

    /**
     * @param  Table  $table
     */
    private function cards(array $table, string $groupBy): HtmlElement
    {
        $sections = [];

        foreach ($this->sections($table) as [$group, $rows]) {
            $cards = [];

            foreach ($rows as $row) {
                $fields = [];

                foreach ($table['columns'] as $column) {
                    $fields[] = new HtmlElement('div', [], [
                        new HtmlElement('dt', [], Xml::escape($column['label'])),
                        new HtmlElement('dd', [], $this->cell($row, $column['id'])),
                    ]);
                }

                $cards[] = new HtmlElement('li', ['class' => 'base-embed-card'], [
                    ($row['thumbnail'] ?? null)
                        ? new HtmlElement('img', ['src' => $row['thumbnail'], 'alt' => '', 'loading' => 'lazy'], '', true)
                        : new HtmlElement('p', ['class' => 'base-embed-snippet'], Xml::escape($row['locked'] ? 'Locked' : ($row['snippet'] ?? ''))),
                    new HtmlElement('div', ['class' => 'base-embed-card-title'], $this->titleLink($row)),
                    ...($fields === [] ? [] : [new HtmlElement('dl', [], $fields)]),
                ]);
            }

            $sections[] = new HtmlElement('section', [], [
                ...($group === false ? [] : [new HtmlElement('h4', ['class' => 'base-embed-group'], $this->groupHeading($group, $groupBy))]),
                new HtmlElement('ul', ['class' => 'base-embed-cards'], $cards),
            ]);
        }

        return new HtmlElement('div', [], $sections);
    }

    /**
     * Rows by group, as [group or false when ungrouped, rows].
     *
     * @param  Table  $table
     * @return list<array{0: array{value: mixed, total: int, count: int}|false, 1: list<Row>}>
     */
    private function sections(array $table): array
    {
        if ($table['groups'] === null) {
            return [[false, $table['rows']]];
        }

        $offset = 0;
        $sections = [];

        foreach ($table['groups'] as $group) {
            $sections[] = [$group, array_slice($table['rows'], $offset, $group['count'])];
            $offset += $group['count'];
        }

        return $sections;
    }

    /**
     * @param  array{value: mixed, total: int, count: int}  $group
     */
    private function groupHeading(array $group, string $groupBy): string
    {
        $value = $group['value'] === null ? 'None' : Xml::escape(self::text($group['value']));

        return '<span class="base-embed-group-by">'.Xml::escape(BaseDefinition::label($groupBy)).':</span> '.$value
            .' <span class="base-embed-count">'.$group['total'].'</span>';
    }

    /**
     * @param  Row  $row
     */
    private function titleLink(array $row): HtmlElement
    {
        return new HtmlElement('a', [
            'href' => route('notes.show', ['note' => $row['id']], false),
            'class' => 'wiki-link',
            'data-note-id' => (string) $row['id'],
        ], Xml::escape($row['title'] !== '' ? $row['title'] : 'Untitled note'));
    }

    /**
     * @param  Row  $row
     */
    private function cell(array $row, string $column): string
    {
        if (isset($row['errors'][$column])) {
            return (string) new HtmlElement('span', ['class' => 'base-embed-error', 'title' => $row['errors'][$column]], 'Error');
        }

        $value = $row['cells'][$column] ?? null;

        return match (true) {
            $value === null, $value === '', $value === [] => '',
            is_bool($value) => $value ? '<span aria-label="Yes">✓</span>' : '<span aria-label="No">✗</span>',
            is_array($value) => implode(' ', array_map(fn (mixed $item) => '<span class="base-embed-chip">'.Xml::escape(self::text($item)).'</span>', $value)),
            default => Xml::escape(self::text($value)),
        };
    }

    /**
     * A value as text: ISO dates as "Nov 1, 2026" (with the time when
     * there is one), everything else as Formula::text.
     */
    public static function text(mixed $value): string
    {
        if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::\d{2})?)?$/', $value, $match) === 1
            && checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
            $date = CarbonImmutable::create((int) $match[1], (int) $match[2], (int) $match[3], (int) ($match[4] ?? 0), (int) ($match[5] ?? 0));

            return isset($match[4]) ? $date->format('M j, Y, g:i A') : $date->format('M j, Y');
        }

        return is_array($value) ? implode(', ', array_map(self::text(...), $value)) : Formula::text($value);
    }
}
