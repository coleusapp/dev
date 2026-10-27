<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('requires authentication', function (string $method, string $route) {
    auth()->logout();
    $note = Note::factory()->create();

    $this->{$method}(route($route, ['note' => $note]))->assertRedirect(route('login'));
})->with([
    'index' => ['get', 'notes.index'],
    'show' => ['get', 'notes.show'],
    'store' => ['post', 'notes.store'],
    'update' => ['put', 'notes.update'],
    'destroy' => ['delete', 'notes.destroy'],
]);

it('lists only the current user notes', function () {
    $mine = Note::factory()->create();
    $this->actingAs(User::factory()->create());
    Note::factory()->create();
    $this->actingAs($this->user);

    $this->get(route('notes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Index', false)
            ->has('notes.data', 1)
            ->where('notes.data.0.id', $mine->id)
            ->where('note', null));
});

it('shows a note', function () {
    $note = Note::factory()->create(['body' => 'Hello']);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Index', false)
            ->where('note.data.id', $note->id)
            ->where('note.data.body', 'Hello'));
});

it('creates a note owned by the current user', function () {
    $response = $this->post(route('notes.store'), ['body' => '# First']);

    $note = Note::query()->sole();
    $response->assertRedirect(route('notes.show', ['note' => $note]));
    expect($note->body)->toBe('# First')
        ->and($note->isOwnedBy($this->user->id))->toBeTrue();
});

it('creates an empty note when the body is empty', function () {
    $this->post(route('notes.store'), ['body' => ''])->assertRedirect();

    expect(Note::query()->sole()->body)->toBe('');
});

it('updates a note', function () {
    $note = Note::factory()->create();

    $this->put(route('notes.update', ['note' => $note]), ['body' => 'Changed'])
        ->assertRedirect(route('notes.show', ['note' => $note]));

    expect($note->refresh()->body)->toBe('Changed');
});

it('deletes a note', function () {
    $note = Note::factory()->create();

    $this->delete(route('notes.destroy', ['note' => $note]))->assertRedirect(route('notes.index'));

    expect(Note::query()->whereKey($note->id)->exists())->toBeFalse();
});

it('hides other users notes from every note route', function (string $method) {
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create(['body' => 'secret']);
    $this->actingAs($this->user);

    $this->{$method}(route('notes.'.match ($method) {
        'get' => 'show',
        'put' => 'update',
        'delete' => 'destroy',
    }, ['note' => $foreign]), ['body' => 'hijack'])->assertNotFound();

    $this->actingAs(User::factory()->create());
    expect(Note::query()->withoutGlobalScopes()->find($foreign->id)->body)->toBe('secret');
})->with(['get', 'put', 'delete']);

it('denies access through the policy even without the user scope', function () {
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create();
    $this->actingAs($this->user);

    expect($this->user->can('view', $foreign))->toBeFalse()
        ->and($this->user->can('update', $foreign))->toBeFalse()
        ->and($this->user->can('delete', $foreign))->toBeFalse();
});

it('validates the body', function (array $payload, string $error) {
    $note = Note::factory()->create(['body' => 'keep']);

    $this->put(route('notes.update', ['note' => $note]), $payload)->assertSessionHasErrors($error);

    expect($note->refresh()->body)->toBe('keep');
})->with([
    'missing' => [[], 'body'],
    'not a string' => [['body' => ['x']], 'body'],
    'too long' => [['body' => str_repeat('a', 1_000_001)], 'body'],
]);

it('ignores attributes that are not fillable', function () {
    $other = User::factory()->create();

    $this->post(route('notes.store'), ['body' => 'x', 'id' => 999, 'created_at' => '2000-01-01', 'user_id' => $other->id]);

    $note = Note::query()->sole();
    expect($note->id)->not->toBe(999)
        ->and($note->created_at->year)->not->toBe(2000)
        ->and($note->isOwnedBy($other->id))->toBeFalse();
});
