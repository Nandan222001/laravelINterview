<?php

namespace Database\Factories;

use App\Models\CodeSnippet;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodeSnippetFactory extends Factory
{
    protected $model = CodeSnippet::class;

    private array $codeExamples = [
        'javascript' => [
            'const greeting = "Hello, World!";\nconsole.log(greeting);',
            'function sum(a, b) {\n  return a + b;\n}\nconsole.log(sum(5, 3));',
            'const arr = [1, 2, 3, 4, 5];\nconst doubled = arr.map(x => x * 2);',
            'async function fetchData() {\n  const response = await fetch("/api/data");\n  return response.json();\n}',
        ],
        'python' => [
            'def greet(name):\n    return f"Hello, {name}!"\n\nprint(greet("World"))',
            'numbers = [1, 2, 3, 4, 5]\nsquares = [x**2 for x in numbers]\nprint(squares)',
            'class Person:\n    def __init__(self, name):\n        self.name = name\n\n    def greet(self):\n        return f"Hello, {self.name}"',
            'import asyncio\n\nasync def main():\n    await asyncio.sleep(1)\n    print("Done")\n\nasyncio.run(main())',
        ],
        'php' => [
            '<?php\n$greeting = "Hello, World!";\necho $greeting;',
            '<?php\nfunction sum($a, $b) {\n    return $a + $b;\n}\necho sum(5, 3);',
            '<?php\n$arr = [1, 2, 3, 4, 5];\n$doubled = array_map(fn($x) => $x * 2, $arr);',
            '<?php\nclass User {\n    public function __construct(\n        public string $name,\n        public string $email\n    ) {}\n}',
        ],
        'java' => [
            'public class Hello {\n    public static void main(String[] args) {\n        System.out.println("Hello, World!");\n    }\n}',
            'public int sum(int a, int b) {\n    return a + b;\n}',
            'List<Integer> numbers = Arrays.asList(1, 2, 3, 4, 5);\nList<Integer> doubled = numbers.stream()\n    .map(x -> x * 2)\n    .collect(Collectors.toList());',
        ],
        'cpp' => [
            '#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << "Hello, World!" << endl;\n    return 0;\n}',
            'int sum(int a, int b) {\n    return a + b;\n}',
            'vector<int> arr = {1, 2, 3, 4, 5};\nfor (auto& num : arr) {\n    cout << num << " ";\n}',
        ],
    ];

    public function definition(): array
    {
        $language = fake()->randomElement(['javascript', 'python', 'php', 'java', 'cpp']);
        $codeExamples = $this->codeExamples[$language];
        $code = fake()->randomElement($codeExamples);

        return [
            'question_id' => Question::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'code' => $code,
            'language' => $language,
            'type' => fake()->randomElement(['example', 'solution', 'test', 'template', 'starter']),
            'order' => fake()->numberBetween(0, 10),
            'is_executable' => fake()->boolean(60),
            'expected_output' => fake()->optional()->sentence(),
            'metadata' => [
                'created_via' => 'factory',
                'complexity' => fake()->randomElement(['simple', 'moderate', 'complex']),
            ],
        ];
    }

    public function javascript(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'javascript',
            'code' => fake()->randomElement($this->codeExamples['javascript']),
        ]);
    }

    public function python(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'python',
            'code' => fake()->randomElement($this->codeExamples['python']),
        ]);
    }

    public function php(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'php',
            'code' => fake()->randomElement($this->codeExamples['php']),
        ]);
    }

    public function example(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'example',
        ]);
    }

    public function solution(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'solution',
        ]);
    }

    public function executable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_executable' => true,
            'expected_output' => fake()->sentence(),
        ]);
    }

    public function nonExecutable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_executable' => false,
            'expected_output' => null,
        ]);
    }
}
