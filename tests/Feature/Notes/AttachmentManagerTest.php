<?php

use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\Attachments;
use Coleus\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->upload = fn (string $name, string $content = 'text') => app(Attachments::class)->store(UploadedFile::fake()->createWithContent($name, $content));
});

it('lists attachments with the notes that use them, by link or by name', function () {
    $linked = ($this->upload)('report.txt');
    $embedded = ($this->upload)('Plan.txt');
    $unused = ($this->upload)('old.txt');
    $byLink = Note::factory()->create(['body' => "# Trip\n".$linked->markdown()]);
    $byName = Note::factory()->create(['body' => "# Work\n![[plan.txt]]"]);
    $trashed = Note::factory()->create(['body' => "# Gone\n".$linked->markdown()]);
    $trashed->delete();

    $this->get(route('notes.attachments.index'))->assertInertia(fn (Assert $page) => $page
        ->component('Attachments', false)
        ->has('attachments', 3)
        ->where('attachments', fn ($attachments) => collect($attachments)->mapWithKeys(fn ($each) => [$each['name'] => collect($each['notes'])->pluck('title')->sort()->values()->all()])->all() === [
            'old.txt' => [],
            'Plan.txt' => ['Work'],
            'report.txt' => ['Gone', 'Trip'],
        ])
        ->where('hasLockedNotes', false));

    expect($unused)->not->toBeNull()->and($byLink)->not->toBeNull()->and($byName)->not->toBeNull()->and($embedded)->not->toBeNull();
});

it('deletes an unused attachment with its files, and refuses one in use', function () {
    $unused = ($this->upload)('old.txt');
    $used = ($this->upload)('kept.txt');
    Note::factory()->create(['body' => $used->markdown()]);
    Storage::disk('local')->put($unused->path.'-poster.jpg', 'x');
    $unused->forceFill(['poster_path' => $unused->path.'-poster.jpg', 'variants' => ['320' => $unused->path.'-320.webp']])->save();
    Storage::disk('local')->put($unused->path.'-320.webp', 'x');

    $this->from(route('notes.attachments.index'))->delete(route('notes.attachments.destroy', $unused->uuid))->assertRedirect();
    expect(Attachment::query()->whereKey($unused->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing([$unused->path, $unused->path.'-poster.jpg', $unused->path.'-320.webp']);

    $this->from(route('notes.attachments.index'))->delete(route('notes.attachments.destroy', $used->uuid))->assertSessionHasErrors('attachment');
    Storage::disk('local')->assertExists($used->path);
});

it('never shows or deletes another users attachments', function () {
    $this->actingAs(User::factory()->create());
    $theirs = ($this->upload)('theirs.txt');
    $this->actingAs($this->user);

    $this->get(route('notes.attachments.index'))->assertInertia(fn (Assert $page) => $page->has('attachments', 0));
    $this->delete(route('notes.attachments.destroy', $theirs->uuid))->assertNotFound();
});
