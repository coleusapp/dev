<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
function registerColeusPestHelpers(): void
{
    function assertSuccessfulGet(string $route, array $parameters = [])
    {
        return test()->get($route)
            ->assertStatus(Response::HTTP_OK);
    }

    function assertSuccessfulPost(string $route, Factory $factory, int $status = Response::HTTP_FOUND): void
    {
        test()->post($route, $factory->make()->toArray())
            ->assertStatus($status);
    }

    function assertSuccessfulPut(string $route, Factory $factory, int $status = Response::HTTP_FOUND): void
    {
        test()->put($route, $factory->make()->toArray())
            ->assertStatus($status);
    }

    function assertSuccessfulDelete(string $route, int $status = Response::HTTP_FOUND): void
    {
        test()->delete($route)
->assertStatus($status);
    }
}
