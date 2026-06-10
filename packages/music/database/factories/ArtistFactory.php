<?php

namespace Coleus\Music\Database\Factories;

use Coleus\Music\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtistFactory extends Factory
{
    protected $model = Artist::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'bio' => $this->faker->optional()->paragraph(),
        ];
    }
}
