<?php

use Coleus\Notes\Models\Preferences;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Users\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

$defaults = fn (): array => collect(NotePreferences::DEFINITIONS)->map(fn ($definition) => $definition['default'])->all();

it('sends default preferences to the notes page', function () use ($defaults) {
    $this->get(route('notes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('preferences', $defaults()));
});

it('renders the settings page', function () {
    $this->get(route('notes.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings', false)
            ->where('definitions', fn ($definitions) => count($definitions['editor.default_mode']['options']) === 4)
            ->where('preferences', fn ($preferences) => $preferences['editor.default_mode'] === 'edit'));
});

it('saves preferences for the current user only', function () use ($defaults) {
    $this->put(route('notes.settings.update'), [
        'editor_default_mode' => 'split',
        'editor_autosave_delay' => '1500',
        'list_relative_dates' => false,
    ])->assertRedirect(route('notes.settings.edit'));

    expect(NotePreferences::all())->toBe([
        'editor.default_mode' => 'split',
        'editor.autosave_delay' => 1500,
        'editor.first_line_style' => 'body',
        'checklists.checked_to_bottom' => false,
        'list.relative_dates' => false,
        'list.show_snippets' => true,
    ]);

    $this->actingAs(User::factory()->create());
    expect(NotePreferences::all())->toBe($defaults());
});

it('keeps unspecified preferences when saving some', function () {
    $this->put(route('notes.settings.update'), ['list_show_snippets' => false]);
    $this->put(route('notes.settings.update'), ['editor_default_mode' => 'preview']);

    expect(NotePreferences::get('list.show_snippets'))->toBeFalse()
        ->and(NotePreferences::get('editor.default_mode'))->toBe('preview');
});

it('rejects values outside the allowed options', function (array $payload, string $field) {
    $this->put(route('notes.settings.update'), $payload)->assertSessionHasErrors($field);

    expect(Preferences::query()->count())->toBe(0);
})->with([
    'unknown mode' => [['editor_default_mode' => 'fullscreen'], 'editor_default_mode'],
    'unlisted delay' => [['editor_autosave_delay' => 10], 'editor_autosave_delay'],
    'not a boolean' => [['list_relative_dates' => 'maybe'], 'list_relative_dates'],
]);

it('ignores unknown keys', function () {
    $this->put(route('notes.settings.update'), ['theme' => 'neon', 'editor_default_mode' => 'split']);

    expect(Preferences::query()->sole()->values)->not->toHaveKey('theme');
});

it('falls back to defaults for stored values that are no longer valid', function () {
    Preferences::query()->create(['user_id' => $this->user->id, 'values' => ['editor.default_mode' => 'retired-mode']]);

    expect(NotePreferences::get('editor.default_mode'))->toBe('edit');
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('notes.settings.edit'))->assertRedirect(route('login'));
    $this->put(route('notes.settings.update'), [])->assertRedirect(route('login'));
});
