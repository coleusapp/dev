<?php

namespace Coleus\Music\Database\Factories;

use Coleus\Music\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

class GenreFactory extends Factory
{
    protected $model = Genre::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
