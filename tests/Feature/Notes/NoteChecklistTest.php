<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('checks an item and stores the Markdown', function () {
    $note = Note::factory()->create(['body' => "# Groceries\n- [ ] milk\n- [ ] eggs"]);

    $this->putJson(route('notes.checklist.update', ['note' => $note, 'line' => 3]), ['checked' => true])
        ->assertOk()
        ->assertJsonPath('data.body', "# Groceries\n- [ ] milk\n- [x] eggs")
        ->assertJsonPath('data.html', fn (string $html) => str_contains($html, 'checked="" data-task-line="3"'));

    expect($note->refresh()->body)->toBe("# Groceries\n- [ ] milk\n- [x] eggs");
});

it('unchecks an item', function () {
    $note = Note::factory()->create(['body' => '- [x] done']);

    $this->putJson(route('notes.checklist.update', ['note' => $note, 'line' => 1]), ['checked' => false])->assertOk();

    expect($note->refresh()->body)->toBe('- [ ] done');
});

it('rejects a line that is not a checklist item', function (string $body, int $line) {
    $note = Note::factory()->create(['body' => $body]);

    $this->putJson(route('notes.checklist.update', ['note' => $note, 'line' => $line]), ['checked' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('line');

    expect($note->refresh()->body)->toBe($body);
})->with([
    'plain text' => ["T\nplain", 2],
    'inside code' => ["```\n- [ ] x\n```", 2],
    'past the end' => ['- [ ] x', 5],
]);

it('validates checked', function (array $payload) {
    $note = Note::factory()->create(['body' => '- [ ] x']);

    $this->putJson(route('notes.checklist.update', ['note' => $note, 'line' => 1]), $payload)
        ->assertJsonValidationErrors('checked');
})->with([
    'missing' => [[]],
    'not boolean' => [['checked' => 'yes please']],
]);

it('cannot toggle another user note', function () {
    $this->actingAs(User::factory()->create());
    $foreign = Note::factory()->create(['body' => '- [ ] x']);
    $this->actingAs($this->user);

    $this->putJson(route('notes.checklist.update', ['note' => $foreign, 'line' => 1]), ['checked' => true])->assertNotFound();
});
