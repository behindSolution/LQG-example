<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = fake()->sentence(rand(4, 8));
        $status = fake()->randomElement([
            Post::STATUS_DRAFT,
            Post::STATUS_PENDING,
            Post::STATUS_PUBLISHED,
            Post::STATUS_PUBLISHED,
            Post::STATUS_PUBLISHED,
        ]);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->randomNumber(5),
            'excerpt' => fake()->paragraph(2),
            'content' => fake()->paragraphs(rand(5, 15), true),
            'featured_image' => fake()->optional(0.7)->imageUrl(800, 600, 'technology'),
            'status' => $status,
            'is_featured' => fake()->boolean(15),
            'views_count' => fake()->numberBetween(0, 10000),
            'likes_count' => fake()->numberBetween(0, 500),
            'published_at' => $status === Post::STATUS_PUBLISHED
                ? fake()->dateTimeBetween('-6 months', 'now')
                : null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_PENDING,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_ARCHIVED,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => fake()->numberBetween(5000, 50000),
            'likes_count' => fake()->numberBetween(200, 2000),
        ]);
    }
}
