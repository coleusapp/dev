<?php

use Coleus\Notes\Canvas\CanvasFiles;
use Coleus\Notes\Models\Canvas;
use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Storage::fake('local');
    config(['notes.attachments.disk' => 'local']);
});

/**
 * @param  array<string, mixed>  $json
 */
function canvasUpload(array|string $json, string $name = 'Trip board.canvas'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, is_string($json) ? $json : (string) json_encode($json));
}

it('exports plain JSON Canvas with notes and files named by path', function () {
    $note = Note::factory()->create(['body' => '# Trip: plan/v2']);
    $attachment = $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->image('map.png', 40, 30)])->json('data');
    $canvas = Canvas::query()->create(['name' => 'Board: one', 'data' => ['nodes' => [
        ['id' => 'a', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'note' => $note->id, 'file' => '', 'subpath' => '#Day 1'],
        ['id' => 'b', 'type' => 'file', 'x' => 400, 'y' => 0, 'width' => 300, 'height' => 200, 'attachment' => $attachment['uuid'], 'file' => 'old.png'],
        ['id' => 'c', 'type' => 'file', 'x' => 0, 'y' => 300, 'width' => 300, 'height' => 200, 'note' => 999999, 'file' => 'Gone.md'],
        ['id' => 't', 'type' => 'text', 'x' => 0, 'y' => 600, 'width' => 300, 'height' => 100, 'text' => 'Hi', 'color' => '2'],
    ], 'edges' => [['id' => 'e', 'fromNode' => 'a', 'toNode' => 't', 'toEnd' => 'none']]]]);

    $response = $this->get(route('notes.canvases.export', $canvas))->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('filename="Board one.canvas"')
        ->and(json_decode($response->streamedContent(), true))->toBe([
            'nodes' => [
                ['id' => 'a', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'Trip plan v2.md', 'subpath' => '#Day 1'],
                ['id' => 'b', 'type' => 'file', 'x' => 400, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'map.png'],
                ['id' => 'c', 'type' => 'file', 'x' => 0, 'y' => 300, 'width' => 300, 'height' => 200, 'file' => 'Gone.md'],
                ['id' => 't', 'type' => 'text', 'x' => 0, 'y' => 600, 'width' => 300, 'height' => 100, 'color' => '2', 'text' => 'Hi'],
            ],
            'edges' => [['id' => 'e', 'fromNode' => 'a', 'toNode' => 't', 'toEnd' => 'none']],
        ]);
});

it('imports a .canvas file, matching notes and files by name', function () {
    $trip = Note::factory()->create(['body' => '# Trip plan']);
    $aliased = Note::factory()->create(['body' => "---\naliases: [Packing]\n---\n# Packing list"]);
    $attachment = $this->postJson(route('notes.attachments.store'), ['file' => UploadedFile::fake()->image('Map.PNG', 40, 30)])->json('data');

    $this->post(route('notes.canvases.import'), ['file' => canvasUpload([
        'nodes' => [
            ['id' => 'a', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'Projects/Trip Plan.md'],
            ['id' => 'b', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'packing.md'],
            ['id' => 'c', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'assets/map.png'],
            ['id' => 'd', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'Nowhere.md'],
            ['id' => 'e', 'type' => 'link', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'url' => 'javascript:alert(1)'],
            ['id' => 'f', 'type' => 'text', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'text' => '# Hello', 'extra' => 'dropped'],
        ],
        'edges' => [['id' => 'x', 'fromNode' => 'a', 'toNode' => 'e']],
    ])])->assertRedirect();

    $canvas = Canvas::query()->sole();

    expect($canvas->name)->toBe('Trip board')
        ->and(collect($canvas->data['nodes'])->keyBy('id')->map(fn (array $node) => array_intersect_key($node, array_flip(['note', 'attachment', 'file', 'text'])))->all())->toBe([
            'a' => ['file' => 'Projects/Trip Plan.md', 'note' => $trip->id],
            'b' => ['file' => 'packing.md', 'note' => $aliased->id],
            'c' => ['file' => 'assets/map.png', 'attachment' => $attachment['uuid']],
            'd' => ['file' => 'Nowhere.md'],
            'f' => ['text' => '# Hello'],
        ])
        ->and($canvas->data['edges'])->toBe([]);
});

it('round-trips its own export', function () {
    $note = Note::factory()->create(['body' => '# Trip']);
    $data = ['nodes' => [
        ['id' => 'a', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => '', 'note' => $note->id],
        ['id' => 'g', 'type' => 'group', 'x' => -50, 'y' => -50, 'width' => 500, 'height' => 400, 'label' => 'Box'],
    ], 'edges' => []];
    $canvas = Canvas::query()->create(['name' => 'Board', 'data' => $data]);

    $exported = $this->get(route('notes.canvases.export', $canvas))->streamedContent();
    $this->post(route('notes.canvases.import'), ['file' => canvasUpload($exported, 'Board.canvas')])->assertRedirect();

    expect(Canvas::query()->latest('id')->first()->data['nodes'][0])->toMatchArray(['note' => $note->id, 'file' => 'Trip.md'])
        ->and(Canvas::query()->latest('id')->first()->data['nodes'][1])->toBe($data['nodes'][1]);
});

it('rejects files that are not canvases', function (UploadedFile $file, string $message) {
    $this->post(route('notes.canvases.import'), ['file' => $file])->assertSessionHasErrors(['file' => $message]);
    expect(Canvas::query()->count())->toBe(0);
})->with([
    'wrong ending' => [fn () => canvasUpload(['nodes' => []], 'board.txt'), 'Choose a .canvas file.'],
    'not JSON' => [fn () => canvasUpload('{nope', 'board.canvas'), "This file isn't a JSON Canvas file."],
    'a list' => [fn () => canvasUpload('[1, 2]', 'board.canvas'), "This file isn't a JSON Canvas file."],
    'no nodes or edges' => [fn () => canvasUpload(['title' => 'x'], 'board.canvas'), "This file isn't a JSON Canvas file."],
    'too big' => [fn () => UploadedFile::fake()->create('board.canvas', CanvasFiles::MAX_KILOBYTES + 1), 'A .canvas file can be at most 5 MB.'],
]);

it("doesn't export other people's canvases or match their notes on import", function () {
    $theirs = Canvas::query()->create(['name' => 'Theirs', 'data' => ['nodes' => [], 'edges' => []]]);
    Note::factory()->create(['body' => '# Secret']);
    $this->actingAs(User::factory()->create());

    $this->get(route('notes.canvases.export', $theirs))->assertNotFound();
    $this->post(route('notes.canvases.import'), ['file' => canvasUpload(['nodes' => [
        ['id' => 'a', 'type' => 'file', 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 200, 'file' => 'Secret.md'],
    ]])])->assertRedirect();

    expect(Canvas::query()->latest('id')->first()->data['nodes'][0])->not->toHaveKey('note');
});
