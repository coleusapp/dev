<?php

namespace Coleus\Notes\Database\Factories;

use Coleus\Notes\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        $titles = [
            'Grocery list',
            'Weekly review',
            'Book notes: Deep Work',
            'Trip to Lisbon',
            'Meeting with the design team',
            'Ideas for the garden',
            'Recipe: lentil soup',
            'Reading list',
            'Release checklist',
            'Journal',
        ];

        return [
            'body' => '# '.$this->faker->randomElement($titles)."\n\n".$this->faker->paragraph(),
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['pinned_at' => now()]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }

    public function bookmarked(): static
    {
        return $this->state(fn () => ['bookmarked_at' => now()]);
    }

    public function trashed(): static
    {
        return $this->state(fn () => ['deleted_at' => now()]);
    }
}
