<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'learning_objectives' => fake()->paragraph(),
            'order' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(90),
            'metadata' => [
                'estimated_time' => fake()->numberBetween(10, 120),
                'prerequisites' => fake()->words(3),
            ],
        ];
    }

    public function forCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
