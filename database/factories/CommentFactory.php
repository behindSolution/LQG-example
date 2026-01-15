<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $hasUser = fake()->boolean(70);

        return [
            'post_id' => Post::factory(),
            'user_id' => $hasUser ? User::factory() : null,
            'parent_id' => null,
            'author_name' => $hasUser ? null : fake()->name(),
            'author_email' => $hasUser ? null : fake()->safeEmail(),
            'content' => fake()->paragraphs(rand(1, 3), true),
            'status' => fake()->randomElement([
                Comment::STATUS_APPROVED,
                Comment::STATUS_APPROVED,
                Comment::STATUS_APPROVED,
                Comment::STATUS_PENDING,
                Comment::STATUS_SPAM,
            ]),
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_APPROVED,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_SPAM,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_REJECTED,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
        ]);
    }

    public function reply(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);
    }
}
