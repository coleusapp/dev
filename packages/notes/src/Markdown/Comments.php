<?php

namespace Coleus\Notes\Markdown;

/**
 * %%Comments%% (O5.3): kept in the note, left out when it's rendered.
 * They can span lines; line breaks inside are kept so line numbers (used
 * by checklist toggles) stay right. "%%" inside `code` or fenced code is
 * just text, and an unclosed "%%" hides nothing.
 */
class Comments
{
    public static function strip(string $body): string
    {
        if (! str_contains($body, '%%')) {
            return $body;
        }

        $out = '';
        $commentStart = null;
        $fence = null;

        foreach (preg_split('/(?<=\n)/', $body) ?: [] as $line) {
            if ($commentStart === null && preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $marker) === 1) {
                $fence = $fence === null ? $marker[1] : ($marker[1][0] === $fence[0] && strlen($marker[1]) >= strlen($fence) ? null : $fence);
                $out .= $line;

                continue;
            }

            if ($fence !== null) {
                $out .= $line;

                continue;
            }

            $length = strlen($line);

            for ($i = 0; $i < $length; $i++) {
                if ($commentStart === null && $line[$i] === '`') {
                    $run = strspn($line, '`', $i);
                    $close = strpos($line, str_repeat('`', $run), $i + $run);

                    if ($close !== false) {
                        $out .= substr($line, $i, $close + $run - $i);
                        $i = $close + $run - 1;

                        continue;
                    }
                }

                if ($line[$i] === '%' && ($line[$i + 1] ?? '') === '%') {
                    if ($commentStart === null) {
                        $commentStart = strlen($out);
                        $out .= '%%';
                    } else {
                        $out = substr($out, 0, $commentStart).str_repeat("\n", substr_count($out, "\n", $commentStart));
                        $commentStart = null;
                    }

                    $i++;

                    continue;
                }

                $out .= $line[$i];
            }
        }

        return $out;
    }
}
