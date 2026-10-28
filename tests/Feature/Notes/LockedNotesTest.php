<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteLockKey;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function lockKeyPayload(array $overrides = []): array
{
    return [
        'salt' => base64_encode(str_repeat('s', 16)),
        'iterations' => 600000,
        'verifier_iv' => base64_encode(str_repeat('v', 12)),
        'verifier' => base64_encode('encrypted verifier'),
        'hint' => 'the usual',
        ...$overrides,
    ];
}

function lockPayload(int $keyId, array $overrides = []): array
{
    return ['key_id' => $keyId, 'iv' => base64_encode(str_repeat('i', 12)), 'ciphertext' => base64_encode('secret text'), 'title' => 'Bank details', ...$overrides];
}

it('sets a notes password, and resetting it retires the old one', function () {
    $this->getJson(route('notes.lock-keys.current'))->assertOk()->assertJsonPath('data', null);

    $first = $this->postJson(route('notes.lock-keys.store'), lockKeyPayload())->assertCreated()->json('data.id');
    $this->getJson(route('notes.lock-keys.current'))->assertJsonPath('data.id', $first)->assertJsonPath('data.hint', 'the usual');

    $second = $this->postJson(route('notes.lock-keys.store'), lockKeyPayload(['hint' => null]))->json('data.id');

    $this->getJson(route('notes.lock-keys.current'))->assertJsonPath('data.id', $second);
    $this->getJson(route('notes.lock-keys.show', $first))->assertJsonPath('data.current', false)->assertJsonPath('data.salt', lockKeyPayload()['salt']);
});

it('refuses weak key settings and keys of other users', function () {
    $this->postJson(route('notes.lock-keys.store'), lockKeyPayload(['iterations' => 1000]))->assertUnprocessable()->assertJsonValidationErrors('iterations');
    $this->postJson(route('notes.lock-keys.store'), lockKeyPayload(['salt' => 'not base64!']))->assertUnprocessable();

    $this->actingAs(User::factory()->create());
    $theirs = NoteLockKey::query()->create(lockKeyPayload());
    $this->actingAs($this->user);

    $this->getJson(route('notes.lock-keys.show', $theirs))->assertNotFound();
});

it('locks a note: encrypted text stored, readable text and history gone, title kept', function () {
    $key = $this->postJson(route('notes.lock-keys.store'), lockKeyPayload())->json('data.id');
    $note = Note::factory()->create(['body' => "# Bank details\naccount 123 #finance [[Other]]"]);
    $this->putJson(route('notes.update', $note), ['body' => "# Bank details\naccount 1234 #finance"]);
    expect($note->versions()->count())->toBeGreaterThan(0);

    $this->postJson(route('notes.lock.store', $note), lockPayload($key))
        ->assertOk()
        ->assertJsonPath('data.locked', true)
        ->assertJsonPath('data.title', 'Bank details')
        ->assertJsonPath('data.body', '')
        ->assertJsonPath('data.snippet', '')
        ->assertJsonPath('data.lock.key_id', $key)
        ->assertJsonPath('data.lock.ciphertext', base64_encode('secret text'));

    $note->refresh();
    expect($note->body)->toBe('')
        ->and($note->versions()->count())->toBe(0)
        ->and($note->tags()->count())->toBe(0)
        ->and($note->title)->toBe('Bank details');
});

it('saves a locked note encrypted, refuses plain edits, and unlocks it with its text', function () {
    $key = $this->postJson(route('notes.lock-keys.store'), lockKeyPayload())->json('data.id');
    $note = Note::factory()->create(['body' => '# Diary']);
    $this->postJson(route('notes.lock.store', $note), lockPayload($key, ['title' => 'Diary']));

    $this->putJson(route('notes.lock.update', $note), ['iv' => base64_encode('newiv1234567'), 'ciphertext' => base64_encode('new secret'), 'title' => 'Diary 2'])
        ->assertOk()->assertJsonPath('data.title', 'Diary 2')->assertJsonPath('data.lock.ciphertext', base64_encode('new secret'));
    expect($note->fresh()->versions()->count())->toBe(0);

    $this->putJson(route('notes.update', $note), ['body' => 'plain'])->assertStatus(409);
    $this->postJson(route('notes.lock.store', $note), lockPayload($key))->assertUnprocessable();

    $this->deleteJson(route('notes.lock.destroy', $note), ['body' => "# Diary\nopen again #home"])
        ->assertOk()->assertJsonPath('data.locked', false)->assertJsonPath('data.lock', null)->assertJsonPath('data.title', 'Diary');

    expect($note->fresh()->body)->toBe("# Diary\nopen again #home")
        ->and($note->fresh()->locked_body)->toBeNull()
        ->and($note->fresh()->tags()->pluck('name')->all())->toBe(['home']);
});

it('only locks with the current password of this user', function () {
    $old = $this->postJson(route('notes.lock-keys.store'), lockKeyPayload())->json('data.id');
    $this->postJson(route('notes.lock-keys.store'), lockKeyPayload());
    $note = Note::factory()->create();

    $this->postJson(route('notes.lock.store', $note), lockPayload($old))->assertUnprocessable()->assertJsonValidationErrors('key_id');

    $this->actingAs(User::factory()->create());
    $theirs = NoteLockKey::query()->create(lockKeyPayload());
    $this->actingAs($this->user);

    $this->postJson(route('notes.lock.store', $note), lockPayload($theirs->id))->assertUnprocessable();
});

it('keeps locked text out of search, lists and the page', function () {
    $key = $this->postJson(route('notes.lock-keys.store'), lockKeyPayload())->json('data.id');
    $note = Note::factory()->create(['body' => "# Secrets\nxylophone-code"]);
    $this->postJson(route('notes.lock.store', $note), lockPayload($key, ['title' => 'Secrets']));

    $this->get(route('notes.index', ['q' => '"xylophone"']))->assertInertia(fn (Assert $page) => $page->has('notes.data', 0));
    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page
        ->where('notes.data.0.title', 'Secrets')
        ->where('notes.data.0.locked', true)
        ->where('notes.data.0.snippet', ''));
});

it('duplicates a locked note as a locked note', function () {
    $key = $this->postJson(route('notes.lock-keys.store'), lockKeyPayload())->json('data.id');
    $note = Note::factory()->create(['body' => '# Secret']);
    $this->postJson(route('notes.lock.store', $note), lockPayload($key, ['title' => 'Secret']));

    $this->post(route('notes.duplicate', $note));

    $copy = Note::query()->whereKeyNot($note->id)->sole();
    expect($copy->isLocked())->toBeTrue()->and($copy->locked_body)->toBe(base64_encode('secret text'))->and($copy->title)->toBe('Secret');
});
