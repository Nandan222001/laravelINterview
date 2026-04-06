<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'framework'],
            ['name' => 'React', 'slug' => 'react', 'category' => 'framework'],
            ['name' => 'Vue', 'slug' => 'vue', 'category' => 'framework'],
            ['name' => 'Node.js', 'slug' => 'nodejs', 'category' => 'framework'],
            ['name' => 'OOP', 'slug' => 'oop', 'category' => 'concept'],
            ['name' => 'Functional Programming', 'slug' => 'functional-programming', 'category' => 'concept'],
            ['name' => 'Design Patterns', 'slug' => 'design-patterns', 'category' => 'concept'],
            ['name' => 'Testing', 'slug' => 'testing', 'category' => 'concept'],
            ['name' => 'Security', 'slug' => 'security', 'category' => 'concept'],
            ['name' => 'Performance', 'slug' => 'performance', 'category' => 'concept'],
            ['name' => 'MySQL', 'slug' => 'mysql', 'category' => 'tool'],
            ['name' => 'PostgreSQL', 'slug' => 'postgresql', 'category' => 'tool'],
            ['name' => 'Redis', 'slug' => 'redis', 'category' => 'tool'],
            ['name' => 'Docker', 'slug' => 'docker', 'category' => 'tool'],
            ['name' => 'Git', 'slug' => 'git', 'category' => 'tool'],
        ];

        foreach ($tags as $tagData) {
            $category = $tagData['category'];
            unset($tagData['category']);

            Tag::create(array_merge($tagData, [
                'description' => "Tag for {$tagData['name']}",
                'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                'usage_count' => 0,
                'metadata' => ['category' => $category, 'featured' => false],
            ]));
        }
    }
}
