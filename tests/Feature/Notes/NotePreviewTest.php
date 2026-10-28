<?php

use Coleus\Notes\Models\Note;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('sends the rendered preview of the open note', function () {
    $note = Note::factory()->create(['body' => "# Title\n\n**bold**"]);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note.data.html', fn (string $html) => str_contains($html, '<h1 id="heading-title">Title') && str_contains($html, '<strong>bold</strong>')));
});

it('does not render previews for every note in the list', function () {
    $note = Note::factory()->create();

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page->missing('notes.data.0.html'));
});

it('returns the fresh preview after an autosave', function () {
    $note = Note::factory()->create(['body' => 'old']);

    $this->putJson(route('notes.update', ['note' => $note]), ['body' => '## New'])
        ->assertJsonPath('data.html', '<h2 id="heading-new">New<a href="#heading-new" class="heading-anchor" aria-hidden="true" title="Link to this heading"></a></h2>'."\n");
});

it('neutralizes script tags and javascript links in the preview', function () {
    $note = Note::factory()->create(['body' => "<script>alert('x')</script>\n\n[click](javascript:alert(1))"]);

    $this->get(route('notes.show', ['note' => $note]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('note.data.html', fn (string $html) => ! str_contains($html, '<script')
                && str_contains($html, '&lt;script&gt;')
                && ! str_contains(strtolower($html), 'javascript:')));
});
