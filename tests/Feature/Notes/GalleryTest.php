<?php

use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\FolderService;
use Coleus\Users\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('switches the default layout, and a folder keeps its own', function () {
    $folder = app(FolderService::class)->create('Recipes', null);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('layout', ['layout' => 'list', 'scope' => 'default']));

    $this->from(route('notes.index'))->put(route('notes.layout.update'), ['layout' => 'gallery', 'folder_id' => $folder->id])->assertRedirect();

    $this->get(route('notes.index', ['folder' => $folder->id]))->assertInertia(fn (Assert $page) => $page->where('layout', ['layout' => 'gallery', 'scope' => 'folder']));
    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('layout.layout', 'list'));

    $this->from(route('notes.index'))->put(route('notes.layout.update'), ['layout' => 'gallery']);
    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('layout', ['layout' => 'gallery', 'scope' => 'default']));

    $this->put(route('notes.layout.update'), ['layout' => 'grid'])->assertSessionHasErrors('layout');
});

it('gives gallery cards the first image of each note', function () {
    $this->put(route('notes.layout.update'), ['layout' => 'gallery']);
    $byUrl = Attachment::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'a.png', 'mime' => 'image/png', 'kind' => 'image', 'size' => 1, 'path' => 'x']);
    $byName = Attachment::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Cake.jpg', 'mime' => 'image/jpeg', 'kind' => 'image', 'size' => 1, 'path' => 'y']);
    $withUrl = Note::factory()->create(['body' => "# Soup\ntext ![[cake.jpg]] later ".$byUrl->markdown()]);
    $withEmbed = Note::factory()->create(['body' => "# Cake\n![[cake.jpg|200]]"]);
    $plain = Note::factory()->create(['body' => '# Plain']);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('notes.data', function ($notes) use ($withUrl, $withEmbed, $plain, $byName) {
        $thumbs = collect($notes)->pluck('thumbnail', 'id');

        return $thumbs[$withUrl->id] === '/notes/attachments/'.$byName->uuid.'/Cake.jpg?w=320'
            && $thumbs[$withEmbed->id] === '/notes/attachments/'.$byName->uuid.'/Cake.jpg?w=320'
            && $thumbs[$plain->id] === null;
    }));
});

it('sends no pictures in the list layout', function () {
    Note::factory()->create(['body' => '# x ![[cake.jpg]]']);

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('notes.data.0.thumbnail', null));
});
