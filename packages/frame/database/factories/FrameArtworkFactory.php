<?php

namespace Coleus\Frame\Database\Factories;

use Coleus\Frame\Models\Frame;
use Coleus\Frame\Models\FrameArtwork;
use Illuminate\Database\Eloquent\Factories\Factory;

class FrameArtworkFactory extends Factory
{
    protected $model = FrameArtwork::class;

    public function definition(): array
    {
        $sourceId = $this->faker->unique()->numberBetween(1000, 999999);

        return [
            'frame_id' => Frame::factory(),
            'source' => 'aic',
            'source_id' => $sourceId,
            'title' => $this->faker->randomElement(['Water Lilies', 'The Bedroom', 'A Sunday on La Grande Jatte', 'Nighthawks']),
            'artist_display' => $this->faker->randomElement(['Claude Monet', 'Vincent van Gogh', 'Georges Seurat', 'Edward Hopper']),
            'date_display' => (string) $this->faker->numberBetween(1850, 1950),
            'path' => 'frame/artworks/'.$sourceId.'.jpg',
            'last_shown_at' => null,
        ];
    }

    public function shownAt(\DateTimeInterface $at): static
    {
        return $this->state(fn () => ['last_shown_at' => $at]);
    }
}
