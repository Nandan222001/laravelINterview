<?php

namespace Database\Seeders;

use App\Models\CodeSnippet;
use App\Models\DifficultyLevel;
use App\Models\Question;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $difficultyLevels = DifficultyLevel::all();
        $topics = Topic::all();
        $tags = Tag::all();

        if ($users->isEmpty() || $difficultyLevels->isEmpty() || $topics->isEmpty()) {
            $this->command->warn('Please run UserSeeder, DifficultyLevelSeeder, and TopicSeeder first.');

            return;
        }

        Question::factory(50)
            ->recycle($users)
            ->recycle($difficultyLevels)
            ->create()
            ->each(function (Question $question) use ($topics, $tags) {
                $question->topics()->attach(
                    $topics->random(rand(1, 3))->pluck('id')->toArray(),
                    ['order' => 0]
                );

                if ($tags->isNotEmpty()) {
                    $question->tags()->attach(
                        $tags->random(rand(1, 5))->pluck('id')->toArray()
                    );
                }

                CodeSnippet::factory(rand(1, 3))
                    ->forQuestion($question->id)
                    ->create();
            });
    }
}
