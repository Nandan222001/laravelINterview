<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'usage_count' => fake()->numberBetween(0, 100),
            'metadata' => [
                'category' => fake()->randomElement(['language', 'framework', 'concept', 'tool']),
                'featured' => fake()->boolean(20),
            ],
        ];
    }
}
