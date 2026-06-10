<?php

namespace Coleus\Music\Database\Factories;

use Coleus\Music\Models\Album;
use Coleus\Music\Models\Artist;
use Coleus\Music\Models\Genre;
use Coleus\Music\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackFactory extends Factory
{
    protected $model = Track::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'artist_id' => Artist::factory(),
            'album_id' => $this->faker->optional()->passthrough(Album::factory()),
            'genre_id' => $this->faker->optional()->passthrough(Genre::factory()),
            'duration' => $this->faker->numberBetween(60, 600),
            'track_number' => $this->faker->optional()->numberBetween(1, 20),
        ];
    }
}
