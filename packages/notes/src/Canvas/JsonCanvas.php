<?php

namespace Coleus\Notes\Canvas;

/**
 * A canvas's data in JSON Canvas 1.0 (jsoncanvas.org), the format
 * Obsidian saves .canvas files in (O10.1, O10.2).
 *
 *   nodes  {id, type, x, y, width, height, color?} and by type:
 *          text  {text}             Markdown text
 *          file  {file, subpath?}   here: a note {note: id, file: title}
 *                                   or an attachment {attachment: uuid,
 *                                   file: name}
 *          link  {url}              an http(s) address
 *          group {label?, background?, backgroundStyle?}
 *   edges  {id, fromNode, toNode, fromSide?, toSide?, fromEnd?, toEnd?,
 *          color?, label?}; toEnd is "arrow" unless set to "none"
 *
 * Colours are a preset "1"–"6" (red, orange, yellow, green, cyan, purple)
 * or "#rrggbb". normalize() keeps what fits and drops the rest, so data
 * from the browser or an imported file is always well formed.
 *
 * @phpstan-type Node array<string, mixed>
 * @phpstan-type Edge array<string, mixed>
 * @phpstan-type Data array{nodes: list<Node>, edges: list<Edge>}
 */
final class JsonCanvas
{
    public const int MAX_NODES = 2000;

    public const int MAX_EDGES = 4000;

    public const int MAX_TEXT = 20000;

    public const int MAX_COORDINATE = 1000000;

    public const int MIN_SIZE = 20;

    public const int MAX_SIZE = 20000;

    public const array NODE_TYPES = ['text', 'file', 'link', 'group'];

    public const array SIDES = ['top', 'right', 'bottom', 'left'];

    public const array ENDS = ['none', 'arrow'];

    public const array BACKGROUND_STYLES = ['cover', 'ratio', 'repeat'];

    private const string ID = '/^[A-Za-z0-9_-]{1,64}$/';

    private const string COLOR = '/^(?:[1-6]|#[0-9a-fA-F]{6})$/';

    private const string UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    /**
     * @return Data
     */
    public static function empty(): array
    {
        return ['nodes' => [], 'edges' => []];
    }

    /**
     * @param  array<mixed>  $data
     * @return Data
     */
    public static function normalize(array $data): array
    {
        $nodes = [];
        $ids = [];

        foreach (array_slice(self::listOf($data['nodes'] ?? []), 0, self::MAX_NODES) as $node) {
            $kept = self::node($node);

            if ($kept !== null && ! isset($ids[$kept['id']])) {
                $ids[$kept['id']] = true;
                $nodes[] = $kept;
            }
        }

        $edges = [];
        $edgeIds = [];

        foreach (array_slice(self::listOf($data['edges'] ?? []), 0, self::MAX_EDGES) as $edge) {
            $kept = self::edge($edge, $ids);

            if ($kept !== null && ! isset($edgeIds[$kept['id']])) {
                $edgeIds[$kept['id']] = true;
                $edges[] = $kept;
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @param  array<mixed>  $node
     * @return Node|null
     */
    private static function node(array $node): ?array
    {
        $id = self::id($node['id'] ?? null);
        $type = $node['type'] ?? null;

        if ($id === null || ! in_array($type, self::NODE_TYPES, true)) {
            return null;
        }

        $kept = [
            'id' => $id,
            'type' => $type,
            'x' => self::number($node['x'] ?? 0, -self::MAX_COORDINATE, self::MAX_COORDINATE),
            'y' => self::number($node['y'] ?? 0, -self::MAX_COORDINATE, self::MAX_COORDINATE),
            'width' => self::number($node['width'] ?? 250, self::MIN_SIZE, self::MAX_SIZE),
            'height' => self::number($node['height'] ?? 60, self::MIN_SIZE, self::MAX_SIZE),
            ...self::color($node['color'] ?? null),
        ];

        return match ($type) {
            'text' => [...$kept, 'text' => mb_substr(self::text($node['text'] ?? ''), 0, self::MAX_TEXT)],
            'file' => self::fileNode($kept, $node),
            'link' => self::isUrl($node['url'] ?? null) ? [...$kept, 'url' => (string) $node['url']] : null,
            default => [
                ...$kept,
                ...(self::text($node['label'] ?? '') === '' ? [] : ['label' => mb_substr(self::text($node['label']), 0, 200)]),
                ...(self::text($node['background'] ?? '') === '' ? [] : ['background' => mb_substr(self::text($node['background']), 0, 500)]),
                ...(in_array($node['backgroundStyle'] ?? null, self::BACKGROUND_STYLES, true) ? ['backgroundStyle' => $node['backgroundStyle']] : []),
            ],
        };
    }

    /**
     * A file card: a note (by id), an attachment (by uuid) or, from an
     * imported file not found here, just the path it named.
     *
     * @param  Node  $kept
     * @param  array<mixed>  $node
     * @return Node|null
     */
    private static function fileNode(array $kept, array $node): ?array
    {
        $file = mb_substr(self::text($node['file'] ?? ''), 0, 500);
        $note = filter_var($node['note'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $attachment = is_string($node['attachment'] ?? null) && preg_match(self::UUID, $node['attachment']) === 1 ? $node['attachment'] : null;
        $subpath = self::text($node['subpath'] ?? '');

        if ($file === '' && $note === false && $attachment === null) {
            return null;
        }

        return [
            ...$kept,
            'file' => $file,
            ...($note !== false ? ['note' => $note] : ($attachment !== null ? ['attachment' => $attachment] : [])),
            ...(str_starts_with($subpath, '#') && mb_strlen($subpath) <= 200 ? ['subpath' => $subpath] : []),
        ];
    }

    /**
     * @param  array<mixed>  $edge
     * @param  array<string, true>  $nodeIds
     * @return Edge|null
     */
    private static function edge(array $edge, array $nodeIds): ?array
    {
        $id = self::id($edge['id'] ?? null);
        $from = self::id($edge['fromNode'] ?? null);
        $to = self::id($edge['toNode'] ?? null);

        if ($id === null || $from === null || $to === null || ! isset($nodeIds[$from], $nodeIds[$to])) {
            return null;
        }

        $label = mb_substr(self::text($edge['label'] ?? ''), 0, 200);

        return [
            'id' => $id,
            'fromNode' => $from,
            ...(in_array($edge['fromSide'] ?? null, self::SIDES, true) ? ['fromSide' => $edge['fromSide']] : []),
            ...(in_array($edge['fromEnd'] ?? null, self::ENDS, true) ? ['fromEnd' => $edge['fromEnd']] : []),
            'toNode' => $to,
            ...(in_array($edge['toSide'] ?? null, self::SIDES, true) ? ['toSide' => $edge['toSide']] : []),
            ...(in_array($edge['toEnd'] ?? null, self::ENDS, true) ? ['toEnd' => $edge['toEnd']] : []),
            ...self::color($edge['color'] ?? null),
            ...($label === '' ? [] : ['label' => $label]),
        ];
    }

    /**
     * @return list<array<mixed>>
     */
    private static function listOf(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }

    private static function id(mixed $value): ?string
    {
        return is_string($value) && preg_match(self::ID, $value) === 1 ? $value : null;
    }

    private static function number(mixed $value, int $min, int $max): int
    {
        return is_numeric($value) ? (int) max($min, min($max, round((float) $value))) : max($min, min($max, 0));
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? $value : (is_int($value) || is_float($value) ? (string) $value : '');
    }

    /**
     * @return array{color?: string}
     */
    private static function color(mixed $value): array
    {
        return is_string($value) && preg_match(self::COLOR, $value) === 1 ? ['color' => mb_strtolower($value)] : [];
    }

    private static function isUrl(mixed $value): bool
    {
        return is_string($value) && mb_strlen($value) <= 2000 && preg_match('#^https?://[^\s]+$#i', $value) === 1 && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
