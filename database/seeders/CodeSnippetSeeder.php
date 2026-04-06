<?php

namespace Database\Seeders;

use App\Models\CodeSnippet;
use App\Models\Question;
use Illuminate\Database\Seeder;

class CodeSnippetSeeder extends Seeder
{
    public function run(): void
    {
        $questions = Question::all();

        if ($questions->isEmpty()) {
            $this->command->warn('No questions found. Please run QuestionSeeder first.');

            return;
        }

        $questions->each(function ($question) {
            $snippetCount = rand(1, 3);

            for ($i = 0; $i < $snippetCount; $i++) {
                CodeSnippet::factory()
                    ->for($question)
                    ->create([
                        'order' => $i,
                    ]);
            }
        });

        $this->command->info('Code snippets seeded successfully!');
    }
}
