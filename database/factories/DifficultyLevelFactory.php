<?php

namespace Database\Factories;

use App\Models\DifficultyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DifficultyLevelFactory extends Factory
{
    protected $model = DifficultyLevel::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Beginner', 'Easy', 'Intermediate', 'Advanced', 'Expert']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'level' => fake()->numberBetween(1, 5),
            'color' => fake()->hexColor(),
            'metadata' => [
                'icon' => fake()->randomElement(['star', 'trophy', 'medal']),
                'badge_color' => fake()->safeColorName(),
            ],
        ];
    }
}
