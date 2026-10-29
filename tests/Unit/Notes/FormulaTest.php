<?php

use Carbon\CarbonImmutable;
use Coleus\Notes\Bases\Formula;
use Coleus\Notes\Bases\FormulaError;

/**
 * @param  array<string, mixed>  $values
 */
function formula(string $expression, array $values = []): mixed
{
    return Formula::parse($expression)->evaluate(fn (string $name) => $values[$name] ?? null, CarbonImmutable::parse('2026-10-29 14:30'));
}

it('does arithmetic with precedence and parentheses', function () {
    expect(formula('price * quantity + 1', ['price' => 2.5, 'quantity' => 4]))->toBe(11.0)
        ->and(formula('(1 + 2) * 3'))->toBe(9)
        ->and(formula('10 / 4'))->toBe(2.5)
        ->and(formula('10 % 3'))->toBe(1.0)
        ->and(formula('-x + 1', ['x' => 3]))->toBe(-2)
        ->and(formula('"7" * 2'))->toBe(14);
});

it('joins text with + and compares without case', function () {
    expect(formula('"Due " + due', ['due' => '2026-11-01']))->toBe('Due 2026-11-01')
        ->and(formula('status == "Done"', ['status' => 'done']))->toBeTrue()
        ->and(formula('status != "done"', ['status' => 'open']))->toBeTrue()
        ->and(formula('due < "2026-12-01"', ['due' => '2026-11-01']))->toBeTrue()
        ->and(formula('9 < 10'))->toBeTrue()
        ->and(formula('tags + "!"', ['tags' => ['a', 'b']]))->toBe('a, b!');
});

it('treats missing values as empty rather than failing', function () {
    expect(formula('price * 2'))->toBeNull()
        ->and(formula('missing == null'))->toBeTrue()
        ->and(formula('missing == ""'))->toBeTrue()
        ->and(formula('missing < 3'))->toBeFalse()
        ->and(formula('"a" + missing'))->toBe('a');
});

it('has boolean logic, with words too', function () {
    expect(formula('done && !archived', ['done' => true]))->toBeTrue()
        ->and(formula('done and not archived', ['done' => true, 'archived' => true]))->toBeFalse()
        ->and(formula('a or b', ['b' => 'x']))->toBeTrue()
        ->and(formula('!""'))->toBeTrue();
});

it('reads note fields, other formulas and odd property names', function () {
    $values = ['note.title' => 'Trip', 'formula.total' => 5, 'due date' => '2026-11-02'];

    expect(formula('note.title + "!"', $values))->toBe('Trip!')
        ->and(formula('formula.total * 2', $values))->toBe(10)
        ->and(formula('prop("due date")', $values))->toBe('2026-11-02')
        ->and(Formula::parse('a + prop("b c") + note.title + formula.x')->references())->toBe(['a', 'b c', 'note.title', 'formula.x']);
});

it('has text, list and number functions', function () {
    expect(formula('if(done, "✓", "…")', ['done' => false]))->toBe('…')
        ->and(formula('if(false, 1)'))->toBeNull()
        ->and(formula('default(owner, "nobody")'))->toBe('nobody')
        ->and(formula('upper(lower("MiX"))'))->toBe('MIX')
        ->and(formula('len(tags)', ['tags' => ['a', 'b']]))->toBe(2)
        ->and(formula('len("héllo")'))->toBe(5)
        ->and(formula('contains(tags, "B")', ['tags' => ['a', 'b']]))->toBeTrue()
        ->and(formula('contains("Lisbon trip", "TRIP")'))->toBeTrue()
        ->and(formula('starts_with("Lisbon", "lis") && ends_with("Lisbon", "BON")'))->toBeTrue()
        ->and(formula('join(tags, " / ")', ['tags' => ['a', 'b']]))->toBe('a / b')
        ->and(formula('split("a, b,,c")'))->toBe(['a', 'b', 'c'])
        ->and(formula('round(2.345, 2)'))->toBe(2.35)
        ->and(formula('floor(2.7) + ceil(2.1) + abs(-1)'))->toBe(6.0)
        ->and(formula('sum(scores)', ['scores' => [1, 2, '3']]))->toBe(6)
        ->and(formula('max(1, 5, 3) - min(scores)', ['scores' => [2, 4]]))->toBe(3)
        ->and(formula('sum(nothing)'))->toBe(0)
        ->and(formula('number("4.5") + number(true)'))->toBe(5.5)
        ->and(formula('text(3.0) + text(0.1)'))->toBe('30.1');
});

it('has date functions', function () {
    expect(formula('today()'))->toBe('2026-10-29')
        ->and(formula('now()'))->toBe('2026-10-29T14:30')
        ->and(formula('days_between(today(), due)', ['due' => '2026-11-01']))->toBe(3)
        ->and(formula('days_between(due, today())', ['due' => '2026-11-01T09:00']))->toBe(-3)
        ->and(formula('add_days("2026-12-30", 3)'))->toBe('2027-01-02')
        ->and(formula('year(d) * 10000 + month(d) * 100 + day(d)', ['d' => '2026-02-03']))->toBe(20260203)
        ->and(formula('days_between(missing, today())'))->toBeNull();
});

it('rejects formulas it cannot read', function (string $expression, string $message) {
    expect(fn () => Formula::parse($expression))->toThrow(FormulaError::class, $message);
})->with([
    'empty' => ['  ', 'The formula is empty.'],
    'unknown function' => ['shout(x)', 'Unknown function "shout".'],
    'wrong arity' => ['if(x)', 'if() takes 2 to 3 arguments.'],
    'one argument' => ['lower()', 'lower() takes 1 argument.'],
    'trailing' => ['a b', 'Unexpected "b".'],
    'unclosed' => ['(1 + 2', 'Missing ")".'],
    'dangling operator' => ['1 +', 'The formula ends too soon.'],
    'odd character' => ['a $ b', 'Unexpected "$ b".'],
    'too deep' => [str_repeat('(', 40).'1'.str_repeat(')', 40), 'nested too deeply'],
    'too long' => [str_repeat('1+', 300).'1', 'under 500 characters'],
]);

it('reports values it cannot work with', function (string $expression, string $message) {
    expect(fn () => formula($expression, ['word' => 'hello']))->toThrow(FormulaError::class, $message);
})->with([
    'division by zero' => ['1 / 0', 'Division by zero.'],
    'word as number' => ['word * 2', '* needs a number, not "hello".'],
    'not a date' => ['year(word)', 'Expected a date like 2026-10-29, not "hello".'],
]);

it('writes values as text', function () {
    expect(Formula::text(null))->toBe('')
        ->and(Formula::text(true))->toBe('true')
        ->and(Formula::text(2.0))->toBe('2')
        ->and(Formula::text(1 / 3))->toBe('0.3333333333')
        ->and(Formula::text(['a', 2]))->toBe('a, 2');
});
