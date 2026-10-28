<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('finds plain-text mentions of the title or an alias, not links, code or URLs', function () {
    $target = Note::factory()->create(['body' => "---\naliases: [Lisbon]\n---\n# Trip to Lisbon"]);
    $mentions = Note::factory()->create(['body' => "# Plans\nBook the trip to  lisbon soon.\nAlso lisbon hotels.\nnot lisboner or #lisbon"]);
    Note::factory()->create(['body' => "# Linked\n[[Trip to Lisbon]] and [[Lisbon|there]]"]);
    Note::factory()->create(['body' => "# Code\n`Lisbon` and https://x.test/lisbon and [Lisbon](https://x.test)\n```\nLisbon\n```"]);

    $this->getJson(route('notes.mentions.index', $target))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mentions->id)
        ->assertJsonPath('data.0.count', 2)
        ->assertJsonPath('data.0.context.0', [['text' => 'Book the ', 'match' => false], ['text' => 'trip to lisbon', 'match' => true], ['text' => ' soon.', 'match' => false]])
        ->assertJsonPath('data.0.context.1.0', ['text' => 'Also ', 'match' => false]);
});

it('links every mention in a note, keeping the text as written', function () {
    $target = Note::factory()->create(['body' => '# Trip to Lisbon']);
    $source = Note::factory()->create(['body' => "# Plans\nthe Trip to Lisbon, then trip to lisbon again\n`Trip to Lisbon`"]);

    $this->postJson(route('notes.mentions.store', ['note' => $target, 'source' => $source]))
        ->assertOk()
        ->assertJsonPath('data.count', 2);

    expect($source->fresh()->body)->toBe("# Plans\nthe [[Trip to Lisbon]], then [[trip to lisbon]] again\n`Trip to Lisbon`");

    $this->getJson(route('notes.mentions.index', $target))->assertJsonCount(0, 'data');
    $this->get(route('notes.show', $target))->assertInertia(fn ($page) => $page->where('note.data.backlinks.0.id', $source->id));
});

it('skips very short names and other users notes', function () {
    $short = Note::factory()->create(['body' => '# A']);
    Note::factory()->create(['body' => 'a b c A']);

    $this->getJson(route('notes.mentions.index', $short))->assertJsonCount(0, 'data');

    $target = Note::factory()->create(['body' => '# Garden']);
    $this->actingAs(User::factory()->create());
    $theirs = Note::factory()->create(['body' => 'my garden']);
    $this->actingAs($this->user);

    $this->getJson(route('notes.mentions.index', $target))->assertJsonCount(0, 'data');
    $this->postJson(route('notes.mentions.store', ['note' => $target, 'source' => $theirs->id]))->assertNotFound();
});
