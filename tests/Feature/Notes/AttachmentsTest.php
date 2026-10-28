<?php

use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Services\Attachments;
use Coleus\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

const PNG = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR\0\0\0\x01\0\0\0\x01\x08\x06\0\0\0\x1f\x15\xc4\x89\0\0\0\rIDATx\x9cc\xf8\x0f\0\0\x01\x01\0\x05\x18\xd8N\0\0\0\0IEND\xaeB`\x82";

it('uploads an image and gives back Markdown that shows it', function () {
    $response = $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->createWithContent('Lisbon [day 1].png', PNG)])
        ->assertCreated()
        ->assertJsonPath('data.kind', 'image')
        ->assertJsonPath('data.mime', 'image/png')
        ->assertJsonPath('data.name', 'Lisbon day 1.png');

    $attachment = Attachment::query()->sole();

    expect($response->json('data.markdown'))->toBe('![Lisbon day 1.png](/notes/attachments/'.$attachment->uuid.'/Lisbon%20day%201.png)')
        ->and($attachment->path)->toBe('notes/attachments/'.$attachment->uuid);
    Storage::disk('local')->assertExists($attachment->path);
});

it('serves an attachment to its owner, inline for media and as a sandboxed download otherwise', function () {
    $image = app(Attachments::class)->store(UploadedFile::fake()->createWithContent('pic.png', PNG));
    $text = app(Attachments::class)->store(UploadedFile::fake()->createWithContent('notes.txt', 'plain words'));

    $this->get(route('notes.attachments.show', ['attachment' => $image->uuid, 'name' => 'pic.png']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=pic.png');

    $this->get(route('notes.attachments.show', ['attachment' => $text->uuid]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=notes.txt')
        ->assertHeader('Content-Security-Policy', 'sandbox');

    $this->actingAs(User::factory()->create());
    $this->get(route('notes.attachments.show', ['attachment' => $image->uuid]))->assertNotFound();
});

it('decides the type from the content, not the name or what the browser says', function () {
    $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->createWithContent('photo.png', '<html><script>alert(1)</script></html>')])
        ->assertUnprocessable()->assertJsonValidationErrors('file');

    $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->createWithContent('readme.md', "# Title\ntext")])
        ->assertCreated()->assertJsonPath('data.kind', 'document');
});

it('refuses page, script and program names whatever the content', function (string $name) {
    $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->createWithContent($name, 'plain text')])
        ->assertUnprocessable()->assertJsonValidationErrors('file');
})->with(['page.html', 'script.js', 'run.sh', 'setup.exe', 'shell.php']);

it('enforces the size limit of each kind', function () {
    config(['notes.attachments.max_kb.image' => 1]);

    $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->createWithContent('big.png', PNG.str_repeat("\0", 2048))])
        ->assertUnprocessable()->assertJsonValidationErrors('file');
});

it('serves SVG only as a download', function () {
    $svg = app(Attachments::class)->store(UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'));

    expect($svg->kind->value)->toBe('document');
    $this->get(route('notes.attachments.show', ['attachment' => $svg->uuid]))
        ->assertHeader('Content-Disposition', 'attachment; filename=logo.svg')
        ->assertHeader('Content-Security-Policy', 'sandbox');
});
