<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionAttempt;
use Illuminate\Database\Seeder;

class UserProgressSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $questions = Question::published()->get();

        if ($users->isEmpty() || $questions->isEmpty()) {
            $this->command->warn('No users or questions found. Please seed users and questions first.');

            return;
        }

        foreach ($users as $user) {
            $questionCount = $questions->count();
            $attemptCount = min(rand(5, 20), $questionCount);
            $selectedQuestions = $questions->random($attemptCount);

            foreach ($selectedQuestions as $question) {
                $status = $this->getRandomStatus();

                UserQuestionAttempt::factory()
                    ->for($user)
                    ->for($question)
                    ->state([
                        'status' => $status,
                        'is_bookmarked' => rand(0, 100) < 20,
                    ])
                    ->create();
            }

            $this->command->info("Created {$attemptCount} question attempts for user: {$user->name}");
        }

        $this->command->info('User progress data seeded successfully!');
    }

    private function getRandomStatus(): string
    {
        $rand = rand(1, 100);

        if ($rand <= 60) {
            return 'completed';
        } elseif ($rand <= 80) {
            return 'attempted';
        } elseif ($rand <= 95) {
            return 'not_attempted';
        } else {
            return 'skipped';
        }
    }
}
