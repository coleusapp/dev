<?php

namespace Coleus\Music\Database\Factories;

use Coleus\Music\Models\Album;
use Coleus\Music\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'artist_id' => Artist::factory(),
            'release_date' => $this->faker->optional()->date(),
        ];
    }
}
