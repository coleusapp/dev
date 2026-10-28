<?php

use Coleus\Notes\Markdown\FrontMatter;

it('parses common property types', function () {
    $body = "---\ntitle: Trip plan\nrating: 4\nprice: 12.5\ndone: false\nempty:\nnothing: null\ndate: 2026-10-28\nquoted: \"a: b # c\"\nsingle: 'it''s'\ntags: [travel, \"2026, q4\"]\naliases:\n  - Lisbon\n  - LIS\n# a comment\n---\n# Body";

    expect(FrontMatter::parse($body))->toBe([
        'title' => 'Trip plan',
        'rating' => 4,
        'price' => 12.5,
        'done' => false,
        'empty' => null,
        'nothing' => null,
        'date' => '2026-10-28',
        'quoted' => 'a: b # c',
        'single' => "it's",
        'tags' => ['travel', '2026, q4'],
        'aliases' => ['Lisbon', 'LIS'],
    ]);
});

it('returns no properties without front matter, and null for YAML it does not read', function () {
    expect(FrontMatter::parse("# Note\n---\na: 1\n---"))->toBe([])
        ->and(FrontMatter::parse("---\nmap:\n  nested: value\n---"))->toBeNull()
        ->and(FrontMatter::parse("---\ntext: |\n  multi\n---"))->toBeNull()
        ->and(FrontMatter::parse("---\nnot yaml at all\n---"))->toBeNull();
});

it('splits off the block and blanks it out keeping line numbers', function () {
    $body = "---\na: 1\nb: 2\n---\n# Title\n- [ ] task";

    expect(FrontMatter::split($body))->toBe(["---\na: 1\nb: 2\n---\n", "# Title\n- [ ] task"])
        ->and(FrontMatter::blankOut($body))->toBe("\n\n\n\n# Title\n- [ ] task")
        ->and(FrontMatter::blankOut('no front matter'))->toBe('no front matter');
});

it('keeps front matter out of the title, snippet and rendered note', function () {
    $body = "---\ntitle: Ignored\ntags: [a]\n---\n# Real title\n\nBody text";

    expect(Coleus\Notes\Support\NoteText::title($body))->toBe('Real title')
        ->and(Coleus\Notes\Support\NoteText::snippet($body))->toBe('Body text')
        ->and(Coleus\Notes\Markdown\NoteMarkdown::toHtml($body))->toBe("<h1>Real title</h1>\n<p>Body text</p>\n");
});

it('keeps checklist line numbers right below front matter', function () {
    $body = "---\na: 1\n---\n- [ ] task";

    expect(Coleus\Notes\Markdown\Checklist::taskLines($body))->toBe([4])
        ->and(Coleus\Notes\Markdown\Checklist::toggle($body, 4, true))->toBe("---\na: 1\n---\n- [x] task")
        ->and(Coleus\Notes\Markdown\NoteMarkdown::toHtml($body))->toContain('data-task-line="4"');
});

it('does not read hashtags or wiki links from front matter', function () {
    $body = "---\nnote: \"#notatag [[Not a link]]\"\n---\n#real";

    expect(Coleus\Notes\Markdown\Hashtags::parse($body))->toBe(['real'])
        ->and(Coleus\Notes\Markdown\WikiLinks\WikiLinks::titles($body))->toBe([]);
});
