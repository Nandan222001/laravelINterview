<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $tags = Tag::all();

        if ($categories->isEmpty()) {
            $this->command->warn('Please run CategorySeeder first.');

            return;
        }

        foreach ($categories as $category) {
            $topics = Topic::factory(rand(3, 6))
                ->forCategory($category->id)
                ->create();

            if ($tags->isNotEmpty()) {
                foreach ($topics as $topic) {
                    $topic->tags()->attach(
                        $tags->random(rand(1, 3))->pluck('id')->toArray()
                    );
                }
            }
        }
    }
}
