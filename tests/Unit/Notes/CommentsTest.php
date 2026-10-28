<?php

use Coleus\Notes\Markdown\Comments;
use Coleus\Notes\Markdown\NoteMarkdown;
use Tests\TestCase;

uses(TestCase::class);

it('strips inline and multi-line comments, keeping line breaks', function () {
    expect(Comments::strip('keep %%hidden%% this'))->toBe('keep  this')
        ->and(Comments::strip("a %%one\ntwo\nthree%% b\nc"))->toBe("a \n\n b\nc")
        ->and(Comments::strip('%%a%% x %%b%%'))->toBe(' x ');
});

it('leaves code, fences and unclosed comments alone', function () {
    expect(Comments::strip('use `a %% b` %%gone%%'))->toBe('use `a %% b` ')
        ->and(Comments::strip("```\n%%not a comment%%\n```\n%%x%%y"))->toBe("```\n%%not a comment%%\n```\ny")
        ->and(Comments::strip("open %% never\nclosed"))->toBe("open %% never\nclosed")
        ->and(Comments::strip('100%% sure'))->toBe('100%% sure');
});

it('hides comments in the rendered note, and checklists still find their lines', function () {
    $html = NoteMarkdown::toHtml("# Plan %%draft%%\n%%\nsecret\n%%\n- [ ] task");

    expect($html)->not->toContain('draft')->not->toContain('secret')
        ->toContain('data-task-line="5"');
});
