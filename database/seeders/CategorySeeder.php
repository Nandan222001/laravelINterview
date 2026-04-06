<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Programming Languages',
                'slug' => 'programming-languages',
                'description' => 'Various programming languages and their concepts',
                'order' => 1,
                'is_active' => true,
                'metadata' => ['icon' => 'code', 'color' => 'blue'],
                'children' => [
                    ['name' => 'PHP', 'slug' => 'php', 'order' => 1],
                    ['name' => 'JavaScript', 'slug' => 'javascript', 'order' => 2],
                    ['name' => 'Python', 'slug' => 'python', 'order' => 3],
                    ['name' => 'Java', 'slug' => 'java', 'order' => 4],
                ],
            ],
            [
                'name' => 'Data Structures',
                'slug' => 'data-structures',
                'description' => 'Fundamental data structures',
                'order' => 2,
                'is_active' => true,
                'metadata' => ['icon' => 'database', 'color' => 'green'],
                'children' => [
                    ['name' => 'Arrays', 'slug' => 'arrays', 'order' => 1],
                    ['name' => 'Linked Lists', 'slug' => 'linked-lists', 'order' => 2],
                    ['name' => 'Trees', 'slug' => 'trees', 'order' => 3],
                    ['name' => 'Graphs', 'slug' => 'graphs', 'order' => 4],
                ],
            ],
            [
                'name' => 'Algorithms',
                'slug' => 'algorithms',
                'description' => 'Common algorithms and problem-solving techniques',
                'order' => 3,
                'is_active' => true,
                'metadata' => ['icon' => 'cpu', 'color' => 'purple'],
                'children' => [
                    ['name' => 'Sorting', 'slug' => 'sorting', 'order' => 1],
                    ['name' => 'Searching', 'slug' => 'searching', 'order' => 2],
                    ['name' => 'Dynamic Programming', 'slug' => 'dynamic-programming', 'order' => 3],
                ],
            ],
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Web development concepts and frameworks',
                'order' => 4,
                'is_active' => true,
                'metadata' => ['icon' => 'globe', 'color' => 'orange'],
                'children' => [
                    ['name' => 'Frontend', 'slug' => 'frontend', 'order' => 1],
                    ['name' => 'Backend', 'slug' => 'backend', 'order' => 2],
                    ['name' => 'APIs', 'slug' => 'apis', 'order' => 3],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create($categoryData);

            foreach ($children as $childData) {
                Category::create(array_merge($childData, [
                    'parent_id' => $category->id,
                    'is_active' => true,
                    'metadata' => ['parent_name' => $category->name],
                ]));
            }
        }
    }
}
