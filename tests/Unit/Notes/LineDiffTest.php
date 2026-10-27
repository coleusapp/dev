<?php

use Coleus\Notes\Support\LineDiff;

$render = fn (array $diff): string => implode("\n", array_map(fn (array $line) => match ($line['type']) {
    'same' => ' ',
    'add' => '+',
    'remove' => '-',
}.$line['text'], $diff));

it('diffs lines', function (string $old, string $new, string $expected) use ($render) {
    expect($render(LineDiff::compare($old, $new)))->toBe($expected);
})->with([
    'identical' => ["a\nb", "a\nb", " a\n b"],
    'both empty' => ['', '', ''],
    'from empty' => ['', "a\nb", "+a\n+b"],
    'to empty' => ["a\nb", '', "-a\n-b"],
    'change in middle' => ["a\nb\nc", "a\nx\nc", " a\n-b\n+x\n c"],
    'insert' => ["a\nc", "a\nb\nc", " a\n+b\n c"],
    'delete' => ["a\nb\nc", "a\nc", " a\n-b\n c"],
    'append' => ['a', "a\nb", " a\n+b"],
    'crlf treated like lf' => ["a\r\nb", "a\nb", " a\n b"],
]);

it('reconstructs both sides', function () {
    $old = implode("\n", range(1, 50));
    $new = implode("\n", [...range(1, 10), 'x', ...range(12, 40), 'y', 'z', ...range(41, 50)]);
    $diff = LineDiff::compare($old, $new);

    $oldSide = array_values(array_map(fn ($l) => $l['text'], array_filter($diff, fn ($l) => $l['type'] !== 'add')));
    $newSide = array_values(array_map(fn ($l) => $l['text'], array_filter($diff, fn ($l) => $l['type'] !== 'remove')));

    expect(implode("\n", $oldSide))->toBe($old)
        ->and(implode("\n", $newSide))->toBe($new)
        ->and(LineDiff::stats($diff))->toBe(['added' => 3, 'removed' => 1]);
});

it('stays fast on long notes with few changes', function () {
    $old = implode("\n", range(1, 20000));
    $new = str_replace("\n500\n", "\nchanged\n", $old);

    $start = microtime(true);
    $stats = LineDiff::stats(LineDiff::compare($old, $new));

    expect($stats)->toBe(['added' => 1, 'removed' => 1])
        ->and(microtime(true) - $start)->toBeLessThan(1.0);
});
