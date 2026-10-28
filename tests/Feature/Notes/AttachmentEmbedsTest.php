<?php

use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Markdown\WikiLinks\WikiLinks;
use Coleus\Notes\Models\Attachment;
use Coleus\Users\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function attachment(string $name, string $kind = 'image'): Attachment
{
    return Attachment::query()->create(['uuid' => (string) Str::uuid(), 'name' => $name, 'mime' => $kind === 'image' ? 'image/png' : 'application/pdf', 'kind' => $kind, 'size' => 10, 'path' => 'x']);
}

it('shows an attached image by name, sized, with its copies', function () {
    $image = attachment('Lisbon.png');
    $image->forceFill(['width' => 2000, 'variants' => ['320' => 'a', '960' => 'b']])->save();
    $src = '/notes/attachments/'.$image->uuid.'/Lisbon.png';

    expect(NoteMarkdown::toHtml('Here: ![[lisbon.png|300]] and'))
        ->toContain('<img src="'.$src.'" alt="Lisbon.png" class="attachment-embed" loading="lazy" width="300" srcset="'.$src.'?w=320 320w, '.$src.'?w=960 960w, '.$src.' 2000w" sizes="300px" />')
        ->and(NoteMarkdown::toHtml('![[Lisbon.png|300x200]]'))->toContain('width="300" height="200"')
        ->and(NoteMarkdown::toHtml("before\n\n![[Lisbon.png]]\n\nafter"))->toContain('<p><img src="'.$src.'"');
});

it('links other attachments, marks missing ones, and still embeds notes', function () {
    $pdf = attachment('Report.pdf', 'document');

    expect(NoteMarkdown::toHtml('![[Report.pdf]]'))->toContain('<a href="/notes/attachments/'.$pdf->uuid.'/Report.pdf" class="attachment-link">Report.pdf</a>')
        ->and(NoteMarkdown::toHtml('![[nowhere.jpg]]'))->toContain('<span class="attachment-missing" title="No attachment named &quot;nowhere.jpg&quot;">nowhere.jpg</span>')
        ->and(NoteMarkdown::toHtml('![[Some note]]'))->toContain('wiki-link-missing')
        ->and(WikiLinks::titles('![[Lisbon.png]] [[Real note]]'))->toBe(['Real note']);
});

it('only finds the users own attachments', function () {
    $this->actingAs(User::factory()->create());
    attachment('theirs.png');
    $this->actingAs($this->user);

    expect(NoteMarkdown::toHtml('![[theirs.png]]'))->toContain('attachment-missing');
});
