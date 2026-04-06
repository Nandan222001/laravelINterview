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
                    ['name' => 'PHP', 'slug' => 'php', 'order' => 1, 'description' => 'Server-side scripting language'],
                    ['name' => 'JavaScript', 'slug' => 'javascript', 'order' => 2, 'description' => 'Client and server-side programming'],
                    ['name' => 'Python', 'slug' => 'python', 'order' => 3, 'description' => 'General-purpose programming'],
                    ['name' => 'Java', 'slug' => 'java', 'order' => 4, 'description' => 'Object-oriented programming'],
                ],
            ],
            [
                'name' => 'Frameworks',
                'slug' => 'frameworks',
                'description' => 'Web application frameworks',
                'order' => 2,
                'is_active' => true,
                'metadata' => ['icon' => 'layers', 'color' => 'red'],
                'children' => [
                    ['name' => 'Laravel', 'slug' => 'laravel', 'order' => 1, 'description' => 'PHP web framework'],
                    ['name' => 'React', 'slug' => 'react', 'order' => 2, 'description' => 'JavaScript library'],
                    ['name' => 'Vue', 'slug' => 'vue', 'order' => 3, 'description' => 'Progressive framework'],
                    ['name' => 'Angular', 'slug' => 'angular', 'order' => 4, 'description' => 'TypeScript framework'],
                ],
            ],
            [
                'name' => 'Databases',
                'slug' => 'databases',
                'description' => 'Database systems and management',
                'order' => 3,
                'is_active' => true,
                'metadata' => ['icon' => 'database', 'color' => 'green'],
                'children' => [
                    ['name' => 'MySQL', 'slug' => 'mysql', 'order' => 1, 'description' => 'Relational database'],
                    ['name' => 'PostgreSQL', 'slug' => 'postgresql', 'order' => 2, 'description' => 'Advanced RDBMS'],
                    ['name' => 'MongoDB', 'slug' => 'mongodb', 'order' => 3, 'description' => 'NoSQL database'],
                    ['name' => 'Redis', 'slug' => 'redis', 'order' => 4, 'description' => 'In-memory data store'],
                ],
            ],
            [
                'name' => 'DevOps',
                'slug' => 'devops',
                'description' => 'Development and operations practices',
                'order' => 4,
                'is_active' => true,
                'metadata' => ['icon' => 'server', 'color' => 'purple'],
                'children' => [
                    ['name' => 'Docker', 'slug' => 'docker', 'order' => 1, 'description' => 'Containerization'],
                    ['name' => 'Kubernetes', 'slug' => 'kubernetes', 'order' => 2, 'description' => 'Container orchestration'],
                    ['name' => 'CI/CD', 'slug' => 'cicd', 'order' => 3, 'description' => 'Continuous integration'],
                    ['name' => 'AWS', 'slug' => 'aws', 'order' => 4, 'description' => 'Cloud platform'],
                ],
            ],
            [
                'name' => 'Software Engineering',
                'slug' => 'software-engineering',
                'description' => 'Software engineering principles',
                'order' => 5,
                'is_active' => true,
                'metadata' => ['icon' => 'settings', 'color' => 'orange'],
                'children' => [
                    ['name' => 'Design Patterns', 'slug' => 'design-patterns', 'order' => 1, 'description' => 'Reusable solutions'],
                    ['name' => 'Testing', 'slug' => 'testing', 'order' => 2, 'description' => 'Software testing'],
                    ['name' => 'Security', 'slug' => 'security', 'order' => 3, 'description' => 'Application security'],
                    ['name' => 'Performance', 'slug' => 'performance', 'order' => 4, 'description' => 'Optimization'],
                ],
            ],
            [
                'name' => 'Data Structures',
                'slug' => 'data-structures',
                'description' => 'Fundamental data structures',
                'order' => 6,
                'is_active' => true,
                'metadata' => ['icon' => 'grid', 'color' => 'teal'],
                'children' => [
                    ['name' => 'Arrays', 'slug' => 'arrays', 'order' => 1, 'description' => 'Linear data structure'],
                    ['name' => 'Linked Lists', 'slug' => 'linked-lists', 'order' => 2, 'description' => 'Dynamic structure'],
                    ['name' => 'Trees', 'slug' => 'trees', 'order' => 3, 'description' => 'Hierarchical structure'],
                    ['name' => 'Graphs', 'slug' => 'graphs', 'order' => 4, 'description' => 'Network structure'],
                ],
            ],
            [
                'name' => 'Algorithms',
                'slug' => 'algorithms',
                'description' => 'Common algorithms',
                'order' => 7,
                'is_active' => true,
                'metadata' => ['icon' => 'cpu', 'color' => 'indigo'],
                'children' => [
                    ['name' => 'Sorting', 'slug' => 'sorting', 'order' => 1, 'description' => 'Sorting algorithms'],
                    ['name' => 'Searching', 'slug' => 'searching', 'order' => 2, 'description' => 'Search algorithms'],
                    ['name' => 'Dynamic Programming', 'slug' => 'dynamic-programming', 'order' => 3, 'description' => 'Optimization'],
                    ['name' => 'Graph Algorithms', 'slug' => 'graph-algorithms', 'order' => 4, 'description' => 'Graph traversal'],
                ],
            ],
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Web development concepts',
                'order' => 8,
                'is_active' => true,
                'metadata' => ['icon' => 'globe', 'color' => 'pink'],
                'children' => [
                    ['name' => 'Frontend', 'slug' => 'frontend', 'order' => 1, 'description' => 'Client-side development'],
                    ['name' => 'Backend', 'slug' => 'backend', 'order' => 2, 'description' => 'Server-side development'],
                    ['name' => 'APIs', 'slug' => 'apis', 'order' => 3, 'description' => 'API development'],
                    ['name' => 'REST', 'slug' => 'rest', 'order' => 4, 'description' => 'RESTful services'],
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
