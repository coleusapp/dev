<?php

use Coleus\Health\Models\MusicUser;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->actingAs(MusicUser::factory()->create());
});

test('the dashboard works', function () {
    return test()->get(route('music.dashboard'))
        ->assertStatus(Response::HTTP_OK);
});
