<?php

namespace Coleus\Frame\Database\Factories;

use Coleus\Frame\Models\Frame;
use Illuminate\Database\Eloquent\Factories\Factory;

class FrameFactory extends Factory
{
    protected $model = Frame::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Living room', 'Office', 'Hallway', 'Studio']),
            'token_hash' => Frame::hashToken(Frame::generateToken()),
            'search_query' => null,
            'interval_seconds' => 60,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withToken(string $token): static
    {
        return $this->state(fn () => ['token_hash' => Frame::hashToken($token)]);
    }
}
