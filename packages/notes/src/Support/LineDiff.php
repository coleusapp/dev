<?php

namespace Coleus\Notes\Support;

/**
 * Line-based diff using Myers' O((N+M)·D) algorithm, so comparing two
 * versions of a long note stays fast when they differ in a few places.
 */
class LineDiff
{
    /**
     * @return list<array{type: 'same'|'add'|'remove', text: string}>
     */
    public static function compare(string $old, string $new): array
    {
        $a = self::lines($old);
        $b = self::lines($new);

        return self::backtrack($a, $b, self::trace($a, $b));
    }

    /**
     * @param  list<array{type: string, text: string}>  $diff
     * @return array{added: int, removed: int}
     */
    public static function stats(array $diff): array
    {
        $added = count(array_filter($diff, fn (array $line) => $line['type'] === 'add'));
        $removed = count(array_filter($diff, fn (array $line) => $line['type'] === 'remove'));

        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        return $text === '' ? [] : (preg_split('/\R/', $text) ?: []);
    }

    /**
     * The furthest-reaching x on each diagonal k, recorded for each edit distance d.
     *
     * @param  list<string>  $a
     * @param  list<string>  $b
     * @return list<array<int, int>>
     */
    private static function trace(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);
        $max = $n + $m;
        $v = [1 => 0];
        $trace = [];

        for ($d = 0; $d <= $max; $d++) {
            $trace[] = $v;

            for ($k = -$d; $k <= $d; $k += 2) {
                $down = $k === -$d || ($k !== $d && ($v[$k - 1] ?? -1) < ($v[$k + 1] ?? -1));
                $x = $down ? ($v[$k + 1] ?? 0) : ($v[$k - 1] ?? 0) + 1;
                $y = $x - $k;

                while ($x < $n && $y < $m && $a[$x] === $b[$y]) {
                    $x++;
                    $y++;
                }

                $v[$k] = $x;

                if ($x >= $n && $y >= $m) {
                    $trace[] = $v;

                    return $trace;
                }
            }
        }

        return $trace;
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     * @param  list<array<int, int>>  $trace
     * @return list<array{type: 'same'|'add'|'remove', text: string}>
     */
    private static function backtrack(array $a, array $b, array $trace): array
    {
        $x = count($a);
        $y = count($b);
        $diff = [];

        for ($d = count($trace) - 2; $d >= 0; $d--) {
            $v = $trace[$d];
            $k = $x - $y;
            $down = $k === -$d || ($k !== $d && ($v[$k - 1] ?? -1) < ($v[$k + 1] ?? -1));
            $prevK = $down ? $k + 1 : $k - 1;
            $prevX = $v[$prevK] ?? 0;
            $prevY = $prevX - $prevK;

            while ($x > $prevX && $y > $prevY) {
                $diff[] = ['type' => 'same', 'text' => $a[$x - 1]];
                $x--;
                $y--;
            }

            if ($d > 0) {
                if ($down) {
                    $diff[] = ['type' => 'add', 'text' => $b[$y - 1]];
                } else {
                    $diff[] = ['type' => 'remove', 'text' => $a[$x - 1]];
                }
            }

            $x = $prevX;
            $y = $prevY;
        }

        return array_reverse($diff);
    }
}
