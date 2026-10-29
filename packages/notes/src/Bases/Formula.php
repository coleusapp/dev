<?php

namespace Coleus\Notes\Bases;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;

/**
 * A base's formula column (O11.1): a small expression over one note's
 * values, read once and evaluated per row. Nothing in it can reach PHP.
 *
 *   price * quantity                 numbers: + - * / %, ( )
 *   "Due " + due                     + joins when either side is text
 *   status == "done" && !archived    == != < <= > >=, && || ! (and, or, not)
 *   note.title  note.folder          the note's own fields (see BaseTable)
 *   prop("due date")                 a property whose name isn't a plain word
 *   formula.total                    another formula of the same base
 *   if(done, "✓", "")                functions: see FUNCTIONS
 *
 * Bare words are properties; a missing one is null. Text compares without
 * case. Dates are ISO text ("2026-10-29"), so they sort and compare as is.
 *
 * Read into a tree of arrays: ['num', n] ['str', s] ['lit', true|false|null]
 * ['var', name] ['call', name, args] ['bin', op, left, right] ['not', x] ['neg', x].
 *
 * @phpstan-type Node array{0: string, 1?: mixed, 2?: mixed, 3?: mixed}
 */
final class Formula
{
    public const int MAX_LENGTH = 500;

    private const int MAX_DEPTH = 32;

    private const string TOKEN = '/\G\s*(?:(?<num>\d+(?:\.\d+)?)|(?<str>"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')|(?<name>[\p{L}_][\p{L}\p{N}_]*(?:\.[\p{L}_][\p{L}\p{N}_]*)*)|(?<op>&&|\|\||==|!=|<=|>=|[-+*\/%<>!(),]))/Au';

    /**
     * Function name => [fewest arguments, most arguments (null: any)].
     * if() is listed for its arity but evaluated lazily.
     */
    public const array FUNCTIONS = [
        'if' => [2, 3],
        'default' => [2, 2],
        'prop' => [1, 1],
        'lower' => [1, 1],
        'upper' => [1, 1],
        'trim' => [1, 1],
        'len' => [1, 1],
        'contains' => [2, 2],
        'starts_with' => [2, 2],
        'ends_with' => [2, 2],
        'join' => [1, 2],
        'split' => [1, 2],
        'number' => [1, 1],
        'text' => [1, 1],
        'round' => [1, 2],
        'floor' => [1, 1],
        'ceil' => [1, 1],
        'abs' => [1, 1],
        'min' => [1, null],
        'max' => [1, null],
        'sum' => [1, null],
        'today' => [0, 0],
        'now' => [0, 0],
        'days_between' => [2, 2],
        'add_days' => [2, 2],
        'year' => [1, 1],
        'month' => [1, 1],
        'day' => [1, 1],
    ];

    /**
     * @param  Node  $tree
     */
    private function __construct(private readonly array $tree)
    {
        //
    }

    /**
     * @throws FormulaError when the expression can't be read
     */
    public static function parse(string $expression): self
    {
        if (mb_strlen($expression) > self::MAX_LENGTH) {
            throw new FormulaError('Keep a formula under '.self::MAX_LENGTH.' characters.');
        }

        $parser = new FormulaParser(self::tokenize($expression));

        return new self($parser->parse());
    }

    /**
     * The names the formula reads: property names as written, plus
     * "note.*" and "formula.*" names.
     *
     * @return list<string>
     */
    public function references(): array
    {
        $found = [];
        $walk = function (array $node) use (&$walk, &$found): void {
            if ($node[0] === 'var') {
                $found[] = $node[1];
            } elseif ($node[0] === 'call') {
                if ($node[1] === 'prop' && ($node[2][0][0] ?? null) === 'str') {
                    $found[] = $node[2][0][1];
                }

                array_map($walk, $node[2]);
            } elseif ($node[0] === 'bin') {
                $walk($node[2]);
                $walk($node[3]);
            } elseif ($node[0] === 'not' || $node[0] === 'neg') {
                $walk($node[1]);
            }
        };
        $walk($this->tree);

        return array_values(array_unique($found));
    }

    /**
     * @param  Closure(string): mixed  $resolve  a name's value: a property, note.* or formula.*
     *
     * @throws FormulaError when a value doesn't fit (dividing by zero, a word where a number goes, …)
     */
    public function evaluate(Closure $resolve, ?CarbonImmutable $now = null): mixed
    {
        return (new FormulaEvaluator($resolve, $now ?? CarbonImmutable::now()))->evaluate($this->tree);
    }

    /**
     * How a value reads as text: lists joined with ", ", whole floats
     * without ".0", null as nothing.
     */
    public static function text(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_float($value) => floor($value) === $value && abs($value) < 1e15 ? (string) (int) $value : rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.'),
            is_array($value) => implode(', ', array_map(self::text(...), $value)),
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            default => (string) $value,
        };
    }

    /**
     * @return list<array{0: string, 1: string, 2: int}> [kind, text, offset]
     */
    private static function tokenize(string $expression): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($expression);

        while ($offset < $length) {
            if (preg_match('/\G\s*$/A', $expression, $end, 0, $offset) === 1) {
                break;
            }

            if (preg_match(self::TOKEN, $expression, $match, PREG_UNMATCHED_AS_NULL, $offset) !== 1) {
                throw new FormulaError('Unexpected "'.mb_substr(ltrim(substr($expression, $offset)), 0, 10).'".');
            }

            $kind = match (true) {
                $match['num'] !== null => 'num',
                $match['str'] !== null => 'str',
                $match['name'] !== null => 'name',
                default => 'op',
            };
            $tokens[] = [$kind, (string) $match[$kind], $offset];
            $offset += strlen($match[0]);
        }

        if (count($tokens) > self::MAX_LENGTH) {
            throw new FormulaError('This formula is too long.');
        }

        return $tokens;
    }

    /**
     * Guards nesting so a deep expression can't exhaust the stack.
     */
    public static function checkDepth(int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new FormulaError('This formula is nested too deeply.');
        }
    }
}
