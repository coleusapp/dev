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
    $zip = Attachment::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Photos.pdf.bak', 'mime' => 'application/zip', 'kind' => 'document', 'size' => 10, 'path' => 'x']);

    expect(NoteMarkdown::toHtml('[Photos.pdf.bak]('.$zip->url().')'))->toContain('<a href="/notes/attachments/'.$zip->uuid.'/Photos.pdf.bak">')
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

it('plays attached video and audio and shows PDFs in place', function () {
    $video = Attachment::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Clip.mp4', 'mime' => 'video/mp4', 'kind' => 'video', 'size' => 10, 'path' => 'x']);
    $audio = Attachment::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Memo.m4a', 'mime' => 'audio/mp4', 'kind' => 'audio', 'size' => 10, 'path' => 'x']);
    $pdf = attachment('Report.pdf', 'document');

    expect(NoteMarkdown::toHtml('![[clip.mp4|640]]'))->toContain('<video src="/notes/attachments/'.$video->uuid.'/Clip.mp4" controls="" preload="metadata" class="attachment-video" title="Clip.mp4" width="640"></video>')
        ->and(NoteMarkdown::toHtml('![[Memo.m4a]]'))->toContain('<audio src="/notes/attachments/'.$audio->uuid.'/Memo.m4a" controls="" preload="metadata" class="attachment-audio"')
        ->and(NoteMarkdown::toHtml('![[Report.pdf]]'))->toContain('<iframe src="/notes/attachments/'.$pdf->uuid.'/Report.pdf" class="attachment-pdf" title="Report.pdf" loading="lazy"></iframe>')
        ->and(NoteMarkdown::toHtml($video->markdown()))->toContain('<video src="/notes/attachments/'.$video->uuid.'/Clip.mp4"')
        ->and($pdf->markdown())->toStartWith('![Report.pdf]')
        ->and(NoteMarkdown::toHtml('![x](https://elsewhere.test/a.mp4)'))->toContain('<img');
});
