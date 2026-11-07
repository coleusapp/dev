<?php

use Coleus\Users\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/_test/who-am-i', fn () => ['id' => auth()->id()]);
});

function runAsEnvironment(string $environment): void
{
    app()->detectEnvironment(fn () => $environment);
}

it('signs a guest in as the oldest user on local', function () {
    runAsEnvironment('local');
    $oldestUser = User::factory()->create();
    User::factory()->create();

    $this->get('/_test/who-am-i')->assertOk()->assertExactJson(['id' => $oldestUser->id]);
    $this->assertAuthenticatedAs($oldestUser);
});

it('leaves an already signed-in user alone on local', function () {
    runAsEnvironment('local');
    User::factory()->create();
    $signedInUser = User::factory()->create();

    $this->actingAs($signedInUser)
        ->get('/_test/who-am-i')
        ->assertExactJson(['id' => $signedInUser->id]);
});

it('stays a guest on local when there are no users', function () {
    runAsEnvironment('local');

    $this->get('/_test/who-am-i')->assertOk()->assertExactJson(['id' => null]);
    $this->assertGuest();
});

it('never signs anyone in outside local', function (string $environment) {
    runAsEnvironment($environment);
    User::factory()->create();

    $this->get('/_test/who-am-i')->assertOk()->assertExactJson(['id' => null]);
    $this->assertGuest();
})->with(['production', 'staging', 'testing']);
