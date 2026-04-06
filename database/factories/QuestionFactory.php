<?php

namespace Database\Factories;

use App\Models\DifficultyLevel;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'difficulty_level_id' => DifficultyLevel::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(),
            'question_text' => fake()->paragraph(3),
            'explanation' => fake()->paragraph(2),
            'options' => [
                'A' => fake()->sentence(),
                'B' => fake()->sentence(),
                'C' => fake()->sentence(),
                'D' => fake()->sentence(),
            ],
            'correct_answer' => ['A'],
            'hints' => [
                fake()->sentence(),
                fake()->sentence(),
            ],
            'points' => fake()->numberBetween(1, 100),
            'time_limit' => fake()->numberBetween(30, 600),
            'is_published' => fake()->boolean(70),
            'is_verified' => fake()->boolean(60),
            'view_count' => fake()->numberBetween(0, 1000),
            'attempt_count' => fake()->numberBetween(0, 500),
            'success_count' => fake()->numberBetween(0, 300),
            'metadata' => [
                'source' => fake()->randomElement(['user_generated', 'imported', 'ai_generated']),
                'version' => '1.0',
            ],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
