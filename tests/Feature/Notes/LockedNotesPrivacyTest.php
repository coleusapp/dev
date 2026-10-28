<?php

use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteLockKey;
use Coleus\Notes\Services\NoteLocks;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->key = NoteLockKey::query()->create(['salt' => 'c2FsdA==', 'iterations' => 600000, 'verifier_iv' => 'aXY=', 'verifier' => 'dg==']);
    $this->lock = fn (Note $note, string $title) => app(NoteLocks::class)->lock($note, $this->key, 'aXY=', base64_encode('secret'), $title);
});

it('keeps a locked note out of search content, snippets and smart rules, but finds its title', function () {
    $note = Note::factory()->create(['body' => "# Passwords\nthe wifi is quartzite-42\n- [ ] renew"]);
    ($this->lock)($note, 'Passwords');

    $ids = function (string $q): array {
        $found = [];
        $this->get(route('notes.index', ['q' => $q]))->assertInertia(function (Assert $page) use (&$found) {
            $found = collect($page->toArray()['props']['notes']['data'])->pluck('id')->all();
        });

        return $found;
    };

    expect($ids('"quartzite"'))->toBe([])
        ->and($ids('task-todo:'))->toBe([])
        ->and($ids('has:checklist'))->toBe([])
        ->and($ids('"Passwords"'))->toBe([$note->id]);
});

it('does not link out of a locked note, mention through it, or show it in previews and embeds', function () {
    $target = Note::factory()->create(['body' => '# Garden']);
    $secret = Note::factory()->create(['body' => "# Secret\nsee [[Garden]] and the garden"]);
    ($this->lock)($secret, 'Secret');

    $this->get(route('notes.show', $target))->assertInertia(fn (Assert $page) => $page->where('note.data.backlinks', []));
    $this->getJson(route('notes.mentions.index', $target))->assertJsonCount(0, 'data');

    $this->getJson(route('notes.links.preview', ['title' => 'Secret']))
        ->assertJsonPath('data.locked', true)
        ->assertJsonPath('data.html', '');

    $embedding = Note::factory()->create(['body' => "# Plan\n\n![[Secret]]"]);
    $this->get(route('notes.show', $embedding))->assertInertia(fn (Assert $page) => $page
        ->where('note.data.html', fn (string $html) => str_contains($html, 'This note is locked.') && ! str_contains($html, 'garden')));
});

it('keeps a locked note out of tags, properties and version history', function () {
    $note = Note::factory()->create(['body' => "---\nstatus: secret\n---\n# Diary #private"]);
    ($this->lock)($note, 'Diary');

    $this->get(route('notes.index'))->assertInertia(fn (Assert $page) => $page->where('tags', []));
    $this->get(route('notes.properties.index'))->assertInertia(fn (Assert $page) => $page->where('properties', []));
    $this->getJson(route('notes.versions.index', $note))->assertOk()->assertJsonCount(0, 'data');
});

it('lists locked notes with has:locked', function () {
    $locked = Note::factory()->create(['body' => '# Vault']);
    Note::factory()->create(['body' => '# Open']);
    ($this->lock)($locked, 'Vault');

    $this->get(route('notes.index', ['q' => 'has:locked']))->assertInertia(fn (Assert $page) => $page
        ->has('notes.data', 1)
        ->where('notes.data.0.id', $locked->id));
});
