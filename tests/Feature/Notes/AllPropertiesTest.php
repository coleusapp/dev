<?php

use Coleus\Notes\Markdown\FrontMatter;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Models\NoteProperty;
use Coleus\Users\Models\User;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists every property used, with types, counts and examples', function () {
    Note::factory()->create(['body' => "---\nstatus: draft\ndue: 2026-11-01\n---\na"]);
    Note::factory()->create(['body' => "---\nStatus: done\ntags: [x]\n---\nb"]);
    Note::factory()->create(['body' => "---\nstatus: draft\n---\nc", 'archived_at' => now()]);
    Note::factory()->trashed()->create(['body' => "---\nsecret: 1\n---\nd"]);

    $this->get(route('notes.properties.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Properties', false)
            ->where('properties', [
                ['key' => 'due', 'types' => ['date'], 'count' => 1, 'examples' => ['2026-11-01']],
                ['key' => 'status', 'types' => ['text'], 'count' => 3, 'examples' => ['draft', 'done']],
                ['key' => 'tags', 'types' => ['list'], 'count' => 1, 'examples' => ['x']],
            ]));
});

it('renames a property in every note, keeping values and formatting', function () {
    $a = Note::factory()->create(['body' => "---\nstatus: draft # wip\nother: 1\n---\n# A"]);
    $b = Note::factory()->create(['body' => "---\nStatus: done\n---\n# B"]);
    $clash = Note::factory()->create(['body' => "---\nstatus: x\nstate: y\n---\n# C"]);
    $untouched = Note::factory()->create(['body' => "---\nstatusbar: 1\n---\n# D"]);

    $this->from(route('notes.properties.index'))->put(route('notes.properties.rename'), ['from' => 'status', 'to' => 'state'])->assertRedirect();

    expect($a->refresh()->body)->toBe("---\nstate: draft # wip\nother: 1\n---\n# A")
        ->and($b->refresh()->body)->toBe("---\nstate: done\n---\n# B")
        ->and($clash->refresh()->body)->toBe("---\nstatus: x\nstate: y\n---\n# C")
        ->and($untouched->refresh()->body)->toBe("---\nstatusbar: 1\n---\n# D")
        ->and(NoteProperty::query()->where('note_id', $a->id)->pluck('key')->all())->toBe(['state', 'other']);
});

it('validates the new name', function (array $payload, string $field) {
    $this->put(route('notes.properties.rename'), $payload)->assertSessionHasErrors($field);
})->with([
    'missing' => [['from' => 'a'], 'to'],
    'bad characters' => [['from' => 'a', 'to' => 'b:c'], 'to'],
    'same name' => [['from' => 'a', 'to' => 'a'], 'to'],
]);

it('renames keys only inside the front matter', function () {
    expect(FrontMatter::renameKey("---\na: 1\n---\na: not front matter", 'a', 'b'))->toBe("---\nb: 1\n---\na: not front matter");
});

it('rebuilds tags, aliases and properties for older notes with notes:reindex', function () {
    $note = Note::factory()->create(['body' => "---\naliases: [X]\nkind: memo\n---\n#tagged"]);
    $note->tags()->detach();
    $note->aliases()->delete();
    $note->properties()->delete();
    auth()->logout();

    Artisan::call('notes:reindex');

    $this->actingAs($this->user);
    expect($note->tags()->pluck('name')->all())->toBe(['tagged'])
        ->and($note->aliases()->pluck('alias')->all())->toBe(['X'])
        ->and($note->properties()->pluck('key')->all())->toBe(['aliases', 'kind']);
});

it('keeps properties private', function () {
    Note::factory()->create(['body' => "---\nmine: 1\n---\nx"]);
    $this->actingAs(User::factory()->create());
    Note::factory()->create(['body' => "---\nmine: 1\n---\nx"]);

    $this->put(route('notes.properties.rename'), ['from' => 'mine', 'to' => 'theirs']);

    $this->actingAs($this->user);
    $this->get(route('notes.properties.index'))->assertInertia(fn (Assert $page) => $page->where('properties.0.key', 'mine'));
});
