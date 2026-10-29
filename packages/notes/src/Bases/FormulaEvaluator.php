<?php

namespace Coleus\Notes\Bases;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Throwable;

/**
 * Works out a Formula tree for one row. See Formula for the language.
 *
 * Falsy: null, false, 0, "" and []. Arithmetic with a null gives null, so
 * a missing property leaves the cell empty instead of failing.
 *
 * @phpstan-import-type Node from Formula
 */
final class FormulaEvaluator
{
    /**
     * @param  Closure(string): mixed  $resolve
     */
    public function __construct(private readonly Closure $resolve, private readonly CarbonImmutable $now)
    {
        //
    }

    /**
     * @param  Node  $node
     */
    public function evaluate(array $node): mixed
    {
        return match ($node[0]) {
            'num', 'str', 'lit' => $node[1],
            'var' => ($this->resolve)($node[1]),
            'not' => ! self::truthy($this->evaluate($node[1])),
            'neg' => ($value = $this->evaluate($node[1])) === null ? null : -self::number($value, '-'),
            'bin' => $this->binary($node[1], $node[2], $node[3]),
            'call' => $this->call($node[1], $node[2]),
            default => throw new FormulaError('Unreadable formula.'),
        };
    }

    public static function truthy(mixed $value): bool
    {
        return ! ($value === null || $value === false || $value === 0 || $value === 0.0 || $value === '' || $value === []);
    }

    /**
     * Compares like a person would: numbers as numbers, everything else as
     * text without case (ISO dates order correctly that way).
     */
    public static function compare(mixed $left, mixed $right): int
    {
        if (self::isNumeric($left) && self::isNumeric($right)) {
            return (float) $left <=> (float) $right;
        }

        return strcmp(mb_strtolower(Formula::text($left)), mb_strtolower(Formula::text($right)));
    }

    /**
     * @param  Node  $left
     * @param  Node  $right
     */
    private function binary(string $operator, array $left, array $right): mixed
    {
        if ($operator === '&&') {
            return self::truthy($this->evaluate($left)) && self::truthy($this->evaluate($right));
        }

        if ($operator === '||') {
            return self::truthy($this->evaluate($left)) || self::truthy($this->evaluate($right));
        }

        $a = $this->evaluate($left);
        $b = $this->evaluate($right);

        return match ($operator) {
            '==' => $this->equal($a, $b),
            '!=' => ! $this->equal($a, $b),
            '<' => $a !== null && $b !== null && self::compare($a, $b) < 0,
            '<=' => $a !== null && $b !== null && self::compare($a, $b) <= 0,
            '>' => $a !== null && $b !== null && self::compare($a, $b) > 0,
            '>=' => $a !== null && $b !== null && self::compare($a, $b) >= 0,
            '+' => $this->plus($a, $b),
            default => $this->arithmetic($operator, $a, $b),
        };
    }

    private function equal(mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b || Formula::text($a) === '' && Formula::text($b) === '';
        }

        return self::compare($a, $b) === 0;
    }

    private function plus(mixed $a, mixed $b): mixed
    {
        if (is_array($a) && is_array($b)) {
            return [...$a, ...$b];
        }

        if (is_string($a) || is_string($b) || is_array($a) || is_array($b)) {
            return Formula::text($a).Formula::text($b);
        }

        return $this->arithmetic('+', $a, $b);
    }

    private function arithmetic(string $operator, mixed $a, mixed $b): int|float|null
    {
        if ($a === null || $b === null) {
            return null;
        }

        $x = self::number($a, $operator);
        $y = self::number($b, $operator);

        if (($operator === '/' || $operator === '%') && (float) $y === 0.0) {
            throw new FormulaError('Division by zero.');
        }

        return match ($operator) {
            '+' => $x + $y,
            '-' => $x - $y,
            '*' => $x * $y,
            '/' => $x / $y,
            default => fmod((float) $x, (float) $y),
        };
    }

    /**
     * @param  list<Node>  $arguments
     */
    private function call(string $name, array $arguments): mixed
    {
        if ($name === 'if') {
            return self::truthy($this->evaluate($arguments[0]))
                ? $this->evaluate($arguments[1])
                : (isset($arguments[2]) ? $this->evaluate($arguments[2]) : null);
        }

        $values = array_map($this->evaluate(...), $arguments);
        $first = $values[0] ?? null;

        return match ($name) {
            'default' => self::truthy($first) ? $first : $values[1],
            'prop' => ($this->resolve)(Formula::text($first)),
            'lower' => mb_strtolower(Formula::text($first)),
            'upper' => mb_strtoupper(Formula::text($first)),
            'trim' => trim(Formula::text($first)),
            'len' => is_array($first) ? count($first) : mb_strlen(Formula::text($first)),
            'contains' => is_array($first)
                ? in_array(mb_strtolower(Formula::text($values[1])), array_map(fn (mixed $item) => mb_strtolower(Formula::text($item)), $first), true)
                : str_contains(mb_strtolower(Formula::text($first)), mb_strtolower(Formula::text($values[1]))),
            'starts_with' => str_starts_with(mb_strtolower(Formula::text($first)), mb_strtolower(Formula::text($values[1]))),
            'ends_with' => str_ends_with(mb_strtolower(Formula::text($first)), mb_strtolower(Formula::text($values[1]))),
            'join' => implode(isset($values[1]) ? Formula::text($values[1]) : ', ', array_map(Formula::text(...), (array) ($first ?? []))),
            'split' => $first === null ? [] : array_values(array_filter(array_map('trim', explode(isset($values[1]) && Formula::text($values[1]) !== '' ? Formula::text($values[1]) : ',', Formula::text($first))), fn (string $part) => $part !== '')),
            'number' => self::isNumeric($first) ? $first + 0 : (is_bool($first) ? (int) $first : null),
            'text' => Formula::text($first),
            'round' => $first === null ? null : round(self::number($first, 'round'), (int) self::number($values[1] ?? 0, 'round')),
            'floor' => $first === null ? null : floor(self::number($first, 'floor')),
            'ceil' => $first === null ? null : ceil(self::number($first, 'ceil')),
            'abs' => $first === null ? null : abs(self::number($first, 'abs')),
            'min', 'max', 'sum' => $this->aggregate($name, $values),
            'today' => $this->now->format('Y-m-d'),
            'now' => $this->now->format('Y-m-d\TH:i'),
            'days_between' => $first === null || $values[1] === null ? null : (int) self::date($first)->startOfDay()->diffInDays(self::date($values[1])->startOfDay(), false),
            'add_days' => $first === null ? null : self::date($first)->addDays((int) self::number($values[1] ?? 0, 'add_days'))->format('Y-m-d'),
            'year' => $first === null ? null : self::date($first)->year,
            'month' => $first === null ? null : self::date($first)->month,
            'day' => $first === null ? null : self::date($first)->day,
            default => throw new FormulaError('Unknown function "'.$name.'".'),
        };
    }

    /**
     * min/max/sum over their arguments, a list argument counting as its
     * items; empty values are skipped.
     *
     * @param  list<mixed>  $values
     */
    private function aggregate(string $name, array $values): int|float|null
    {
        $numbers = array_map(
            fn (mixed $value) => self::number($value, $name),
            array_values(array_filter(array_merge(...array_map(fn (mixed $value) => is_array($value) ? $value : [$value], $values)), fn (mixed $value) => $value !== null && $value !== '')),
        );

        if ($numbers === []) {
            return $name === 'sum' ? 0 : null;
        }

        return match ($name) {
            'min' => min($numbers),
            'max' => max($numbers),
            default => array_sum($numbers),
        };
    }

    private static function isNumeric(mixed $value): bool
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)));
    }

    private static function number(mixed $value, string $where): int|float
    {
        if (is_bool($value)) {
            return (int) $value;
        }

        if (! self::isNumeric($value)) {
            throw new FormulaError("{$where} needs a number, not \"".mb_substr(Formula::text($value), 0, 30).'".');
        }

        return is_string($value) ? trim($value) + 0 : $value;
    }

    private static function date(mixed $value): CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        $text = Formula::text($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2})?)?$/', $text) === 1) {
            try {
                return CarbonImmutable::parse($text);
            } catch (Throwable) {
                //
            }
        }

        throw new FormulaError('Expected a date like 2026-10-29, not "'.mb_substr($text, 0, 30).'".');
    }
}
