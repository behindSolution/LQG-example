<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'PHP',
            'Laravel',
            'JavaScript',
            'TypeScript',
            'React',
            'Vue.js',
            'Node.js',
            'Python',
            'Django',
            'Docker',
            'Kubernetes',
            'AWS',
            'Azure',
            'PostgreSQL',
            'MySQL',
            'Redis',
            'GraphQL',
            'REST API',
            'Microservices',
            'CI/CD',
            'Git',
            'TDD',
            'Clean Code',
            'Design Patterns',
            'Security',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
