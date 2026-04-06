<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserQuestionAttemptFactory extends Factory
{
    protected $model = UserQuestionAttempt::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['not_attempted', 'attempted', 'completed', 'skipped']);
        $isCompleted = $status === 'completed';
        $isAttempted = in_array($status, ['attempted', 'completed']);

        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'status' => $status,
            'is_bookmarked' => $this->faker->boolean(20),
            'is_correct' => $isCompleted ? $this->faker->boolean(70) : null,
            'attempts_count' => $isAttempted ? $this->faker->numberBetween(1, 5) : 0,
            'time_spent' => $isAttempted ? $this->faker->numberBetween(30, 600) : null,
            'user_answer' => $isAttempted ? [$this->faker->word, $this->faker->word] : null,
            'first_attempted_at' => $isAttempted ? $this->faker->dateTimeBetween('-30 days', '-1 day') : null,
            'last_attempted_at' => $isAttempted ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'completed_at' => $isCompleted ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
        ];
    }

    public function notAttempted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'not_attempted',
            'is_correct' => null,
            'attempts_count' => 0,
            'time_spent' => null,
            'user_answer' => null,
            'first_attempted_at' => null,
            'last_attempted_at' => null,
            'completed_at' => null,
        ]);
    }

    public function attempted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'attempted',
            'is_correct' => null,
            'attempts_count' => $this->faker->numberBetween(1, 3),
            'time_spent' => $this->faker->numberBetween(30, 600),
            'user_answer' => [$this->faker->word],
            'first_attempted_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'last_attempted_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'is_correct' => $this->faker->boolean(70),
            'attempts_count' => $this->faker->numberBetween(1, 5),
            'time_spent' => $this->faker->numberBetween(30, 600),
            'user_answer' => [$this->faker->word],
            'first_attempted_at' => $this->faker->dateTimeBetween('-30 days', '-7 days'),
            'last_attempted_at' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
            'completed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function correct(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }

    public function incorrect(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'is_correct' => false,
        ]);
    }

    public function bookmarked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bookmarked' => true,
        ]);
    }
}
