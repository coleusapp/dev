<?php

use Coleus\Notes\Canvas\JsonCanvas;
use Coleus\Notes\Models\Attachment;
use Coleus\Notes\Models\Canvas;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteLockKey;
use Coleus\Notes\Services\NoteLocks;
use Coleus\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('keeps well-formed JSON Canvas and drops the rest', function () {
    $data = JsonCanvas::normalize([
        'nodes' => [
            ['id' => 'a', 'type' => 'text', 'x' => 10.6, 'y' => -5, 'width' => 5, 'height' => 99999, 'color' => '#FF0000', 'text' => 'Hello'],
            ['id' => 'b', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'note' => 7, 'file' => 'Trip', 'subpath' => '#Day 1'],
            ['id' => 'c', 'type' => 'link', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'url' => 'https://example.com/a?b=1', 'color' => '9'],
            ['id' => 'g', 'type' => 'group', 'x' => -100, 'y' => -100, 'width' => 900, 'height' => 600, 'label' => 'Ideas', 'backgroundStyle' => 'tile'],
            ['id' => 'bad link', 'type' => 'text'],
            ['id' => 'd', 'type' => 'link', 'url' => 'javascript:alert(1)'],
            ['id' => 'e', 'type' => 'file', 'file' => ''],
            ['id' => 'f', 'type' => 'sticker'],
            ['id' => 'a', 'type' => 'text', 'text' => 'duplicate id'],
            'not a node',
        ],
        'edges' => [
            ['id' => 'e1', 'fromNode' => 'a', 'fromSide' => 'right', 'toNode' => 'b', 'toSide' => 'left', 'toEnd' => 'none', 'color' => '3', 'label' => 'leads to'],
            ['id' => 'e2', 'fromNode' => 'a', 'toNode' => 'gone'],
            ['id' => 'e3', 'fromNode' => 'b', 'fromSide' => 'middle', 'toNode' => 'c', 'fromEnd' => 'dot'],
        ],
    ]);

    expect($data)->toBe([
        'nodes' => [
            ['id' => 'a', 'type' => 'text', 'x' => 11, 'y' => -5, 'width' => 20, 'height' => 20000, 'color' => '#ff0000', 'text' => 'Hello'],
            ['id' => 'b', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'Trip', 'note' => 7, 'subpath' => '#Day 1'],
            ['id' => 'c', 'type' => 'link', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'url' => 'https://example.com/a?b=1'],
            ['id' => 'g', 'type' => 'group', 'x' => -100, 'y' => -100, 'width' => 900, 'height' => 600, 'label' => 'Ideas'],
        ],
        'edges' => [
            ['id' => 'e1', 'fromNode' => 'a', 'fromSide' => 'right', 'toNode' => 'b', 'toSide' => 'left', 'toEnd' => 'none', 'color' => '3', 'label' => 'leads to'],
            ['id' => 'e3', 'fromNode' => 'b', 'toNode' => 'c'],
        ],
    ])
        ->and(JsonCanvas::normalize(['nodes' => 'x']))->toBe(JsonCanvas::empty());
});

it('lists canvases and creates one', function () {
    $this->get(route('notes.canvases.index'))->assertInertia(fn (Assert $page) => $page->component('Canvases', false)->where('canvases', []));

    $this->post(route('notes.canvases.store'), ['name' => ' Trip board '])->assertRedirect();
    $canvas = Canvas::query()->sole();

    expect($canvas->name)->toBe('Trip board')->and($canvas->data)->toBe(JsonCanvas::empty());

    $this->get(route('notes.canvases.index'))->assertInertia(fn (Assert $page) => $page
        ->where('canvases.0.name', 'Trip board')
        ->where('canvases.0.cards', 0));

    $this->post(route('notes.canvases.store'), ['name' => ''])->assertSessionHasErrors(['name' => 'Give the canvas a name.']);
});

it('saves the board and answers with what its cards show', function () {
    Storage::fake('local');
    config(['notes.attachments.disk' => 'local']);
    $note = Note::factory()->create(['body' => "# Trip\nPack **light**"]);
    $attachment = $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->image('map.png', 40, 30)])->json('data');
    $canvas = Canvas::query()->create(['name' => 'Board', 'data' => JsonCanvas::empty()]);

    $response = $this->putJson(route('notes.canvases.update', $canvas), ['data' => [
        'nodes' => [
            ['id' => 'n1', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'note' => $note->id, 'file' => 'Trip'],
            ['id' => 'n2', 'type' => 'file', 'x' => 400, 'y' => 0, 'width' => 300, 'height' => 200, 'attachment' => $attachment['uuid'], 'file' => 'map.png'],
            ['id' => 'n3', 'type' => 'file', 'x' => 0, 'y' => 300, 'width' => 300, 'height' => 200, 'note' => 999999, 'file' => 'Gone'],
        ],
        'edges' => [['id' => 'e1', 'fromNode' => 'n1', 'toNode' => 'n2']],
    ]])->assertOk();

    expect($response->json('data.cards'))->toBe([
        'notes' => [$note->id => ['title' => 'Trip', 'snippet' => 'Pack light', 'locked' => false]],
        'attachments' => [$attachment['uuid'] => ['name' => 'map.png', 'kind' => 'image', 'url' => $attachment['url']]],
    ])
        ->and($canvas->fresh()->data['edges'])->toBe([['id' => 'e1', 'fromNode' => 'n1', 'toNode' => 'n2']])
        ->and($canvas->fresh()->data['nodes'])->toHaveCount(3);

    $this->putJson(route('notes.canvases.update', $canvas), ['name' => 'Renamed'])->assertOk()->assertJsonPath('data.name', 'Renamed');
    expect($canvas->fresh()->data['nodes'])->toHaveCount(3);
});

it('shows a canvas with its cards, locked notes by title only', function () {
    $key = NoteLockKey::query()->create(['salt' => 'c2FsdA==', 'iterations' => 600000, 'verifier_iv' => 'aXY=', 'verifier' => 'dg==']);
    $note = Note::factory()->create(['body' => "# Vault\nsecret"]);
    app(NoteLocks::class)->lock($note, $key, 'aXY=', base64_encode('secret'), 'Vault');
    $canvas = Canvas::query()->create(['name' => 'Board', 'data' => ['nodes' => [
        ['id' => 'n1', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'note' => $note->id, 'file' => 'Vault'],
        ['id' => 'x', 'type' => 'nonsense'],
    ], 'edges' => []]]);

    $this->get(route('notes.canvases.show', $canvas))->assertInertia(fn (Assert $page) => $page
        ->component('Canvas', false)
        ->where('canvas.name', 'Board')
        ->has('canvas.data.nodes', 1)
        ->where('cards.notes.'.$note->id, ['title' => 'Vault', 'snippet' => '', 'locked' => true]));
});

it('rejects a board too big to keep', function () {
    $canvas = Canvas::query()->create(['name' => 'Board', 'data' => JsonCanvas::empty()]);

    $this->putJson(route('notes.canvases.update', $canvas), ['data' => ['nodes' => array_fill(0, JsonCanvas::MAX_NODES + 1, []), 'edges' => []]])
        ->assertJsonValidationErrors(['data.nodes' => 'A canvas holds at most 2000 cards.']);
});

it("keeps other people's canvases and notes out of reach", function () {
    $canvas = Canvas::query()->create(['name' => 'Mine', 'data' => JsonCanvas::empty()]);
    $note = Note::factory()->create(['body' => '# Private']);
    $this->actingAs(User::factory()->create());

    $this->get(route('notes.canvases.show', $canvas))->assertNotFound();
    $this->putJson(route('notes.canvases.update', $canvas), ['name' => 'x'])->assertNotFound();
    $this->delete(route('notes.canvases.destroy', $canvas))->assertNotFound();

    $own = Canvas::query()->create(['name' => 'Theirs', 'data' => JsonCanvas::empty()]);
    $this->putJson(route('notes.canvases.update', $own), ['data' => ['nodes' => [
        ['id' => 'n1', 'type' => 'file', 'note' => $note->id, 'file' => 'Private'],
    ], 'edges' => []]])->assertJsonPath('data.cards.notes', []);
});

it('deletes a canvas', function () {
    $canvas = Canvas::query()->create(['name' => 'Board', 'data' => JsonCanvas::empty()]);

    $this->delete(route('notes.canvases.destroy', $canvas))->assertRedirect(route('notes.canvases.index'));
    expect(Canvas::query()->count())->toBe(0)
        ->and(Attachment::query()->count())->toBe(0);
});
