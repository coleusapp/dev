<?php

use Coleus\Notes\Facades\Settings;
use Coleus\Notes\Preferences\NotePreferences;
use Coleus\Settings\Models\Settings as SettingsRow;
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
            ->where('definitions', fn ($definitions) => count($definitions['list.sort']['options']) === 2)
            ->where('preferences', fn ($preferences) => $preferences['list.sort'] === 'modified'));
});

it('saves preferences in coleus/settings for the current user only', function () use ($defaults) {
    $this->from(route('notes.settings.edit'))->put(route('notes.settings.update'), [
        'editor_autosave_delay' => '1500',
        'list_relative_dates' => false,
    ])->assertRedirect(route('notes.settings.edit'));

    expect(NotePreferences::all())->toBe([...$defaults(), 'editor.autosave_delay' => 1500, 'list.relative_dates' => false])
        ->and(Settings::forUser()->get('notes.editor.autosave_delay'))->toBe(1500)
        ->and(Settings::get('notes.editor.autosave_delay'))->toBe(1500);

    $this->actingAs(User::factory()->create());
    expect(NotePreferences::all())->toBe($defaults());
});

it('keeps unspecified preferences when saving some', function () {
    $this->put(route('notes.settings.update'), ['list_show_snippets' => false]);
    $this->put(route('notes.settings.update'), ['list_sort' => 'created']);

    expect(NotePreferences::get('list.show_snippets'))->toBeFalse()
        ->and(NotePreferences::get('list.sort'))->toBe('created');
});

it('rejects values outside the allowed options', function (array $payload, string $field) {
    $this->put(route('notes.settings.update'), $payload)->assertSessionHasErrors($field);

    expect(SettingsRow::query()->count())->toBe(0);
})->with([
    'unknown sort' => [['list_sort' => 'title'], 'list_sort'],
    'unlisted delay' => [['editor_autosave_delay' => 10], 'editor_autosave_delay'],
    'not a boolean' => [['list_relative_dates' => 'maybe'], 'list_relative_dates'],
]);

it('ignores unknown keys', function () {
    $this->put(route('notes.settings.update'), ['theme' => 'neon', 'list_sort' => 'created']);

    expect(SettingsRow::query()->pluck('name')->all())->toBe(['notes.list.sort']);
});

it('falls back to defaults for stored values that are no longer valid', function () {
    Settings::forUser()->set('notes.list.sort', 'title');

    expect(NotePreferences::get('list.sort'))->toBe('modified');
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('notes.settings.edit'))->assertRedirect(route('login'));
    $this->put(route('notes.settings.update'), [])->assertRedirect(route('login'));
});
