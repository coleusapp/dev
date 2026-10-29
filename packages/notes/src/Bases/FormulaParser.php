<?php

namespace Coleus\Notes\Bases;

/**
 * Reads Formula's tokens into its tree, by precedence, lowest first:
 * || then && then == != then < <= > >= then + - then * / % then ! and -.
 * "and", "or" and "not" are words for &&, || and !.
 *
 * @phpstan-import-type Node from Formula
 */
final class FormulaParser
{
    /**
     * Operator levels, lowest precedence first.
     */
    private const array LEVELS = [
        ['||', 'or'],
        ['&&', 'and'],
        ['==', '!='],
        ['<', '<=', '>', '>='],
        ['+', '-'],
        ['*', '/', '%'],
    ];

    private int $position = 0;

    private int $depth = 0;

    /**
     * @param  list<array{0: string, 1: string, 2: int}>  $tokens
     */
    public function __construct(private readonly array $tokens)
    {
        //
    }

    /**
     * @return Node
     */
    public function parse(): array
    {
        if ($this->tokens === []) {
            throw new FormulaError('The formula is empty.');
        }

        $tree = $this->binary(0);

        if (($token = $this->peek()) !== null) {
            throw new FormulaError('Unexpected "'.$token[1].'".');
        }

        return $tree;
    }

    /**
     * @return Node
     */
    private function binary(int $level): array
    {
        if ($level === count(self::LEVELS)) {
            return $this->unary();
        }

        $left = $this->binary($level + 1);

        while (($token = $this->peek()) !== null && $token[0] !== 'str' && in_array(mb_strtolower($token[1]), self::LEVELS[$level], true)) {
            $this->position++;
            $operator = match (mb_strtolower($token[1])) {
                'or' => '||',
                'and' => '&&',
                default => $token[1],
            };
            $left = ['bin', $operator, $left, $this->binary($level + 1)];
        }

        return $left;
    }

    /**
     * @return Node
     */
    private function unary(): array
    {
        $token = $this->peek();

        if ($token !== null && $token[0] !== 'str' && in_array(mb_strtolower($token[1]), ['!', 'not', '-'], true)) {
            $this->position++;
            Formula::checkDepth(++$this->depth);
            $operand = $this->unary();
            $this->depth--;

            return [$token[1] === '-' ? 'neg' : 'not', $operand];
        }

        return $this->primary();
    }

    /**
     * @return Node
     */
    private function primary(): array
    {
        $token = $this->next();

        if ($token === null) {
            throw new FormulaError('The formula ends too soon.');
        }

        [$kind, $text] = $token;

        if ($kind === 'num') {
            return ['num', str_contains($text, '.') ? (float) $text : (int) $text];
        }

        if ($kind === 'str') {
            return ['str', (string) preg_replace('/\\\\(.)/su', '$1', substr($text, 1, -1))];
        }

        if ($text === '(') {
            Formula::checkDepth(++$this->depth);
            $inner = $this->binary(0);
            $this->depth--;
            $this->expect(')');

            return $inner;
        }

        if ($kind !== 'name') {
            throw new FormulaError('Unexpected "'.$text.'".');
        }

        $lower = mb_strtolower($text);

        if (in_array($lower, ['true', 'false', 'null'], true)) {
            return ['lit', match ($lower) {
                'true' => true,
                'false' => false,
                default => null,
            }];
        }

        $after = $this->peek();

        if ($after !== null && $after[0] === 'op' && $after[1] === '(') {
            return $this->call($lower);
        }

        if (in_array($lower, ['and', 'or', 'not'], true)) {
            throw new FormulaError('Unexpected "'.$text.'".');
        }

        return ['var', $text];
    }

    /**
     * @return Node
     */
    private function call(string $name): array
    {
        if (! array_key_exists($name, Formula::FUNCTIONS)) {
            throw new FormulaError('Unknown function "'.$name.'".');
        }

        $this->expect('(');
        Formula::checkDepth(++$this->depth);
        $arguments = [];

        if (($this->peek()[1] ?? null) !== ')') {
            do {
                $arguments[] = $this->binary(0);
            } while ($this->accept(','));
        }

        $this->depth--;
        $this->expect(')');

        [$fewest, $most] = Formula::FUNCTIONS[$name];

        if (count($arguments) < $fewest || ($most !== null && count($arguments) > $most)) {
            $expected = $fewest === $most ? (string) $fewest : ($most === null ? "at least {$fewest}" : "{$fewest} to {$most}");

            throw new FormulaError("{$name}() takes {$expected} argument".($expected === '1' ? '' : 's').'.');
        }

        return ['call', $name, $arguments];
    }

    /**
     * @return array{0: string, 1: string, 2: int}|null
     */
    private function peek(): ?array
    {
        return $this->tokens[$this->position] ?? null;
    }

    /**
     * @return array{0: string, 1: string, 2: int}|null
     */
    private function next(): ?array
    {
        return $this->tokens[$this->position++] ?? null;
    }

    private function accept(string $operator): bool
    {
        if (($token = $this->peek()) !== null && $token[0] === 'op' && $token[1] === $operator) {
            $this->position++;

            return true;
        }

        return false;
    }

    private function expect(string $operator): void
    {
        if (! $this->accept($operator)) {
            $token = $this->peek();

            throw new FormulaError($token === null ? "Missing \"{$operator}\"." : "Expected \"{$operator}\" before \"{$token[1]}\".");
        }
    }
}
