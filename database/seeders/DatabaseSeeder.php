<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create editor user
        $editor = User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@editor.com',
        ]);

        // Create regular users
        $users = User::factory(5)->create();
        $allUsers = $users->push($admin, $editor);

        // Create categories
        $categories = Category::factory(8)->create();

        // Create tags
        $tags = Tag::factory(15)->create();

        // Create featured posts
        Post::factory(3)
            ->featured()
            ->recycle($allUsers)
            ->recycle($categories)
            ->create()
            ->each(function (Post $post) use ($tags) {
                $post->tags()->attach($tags->random(rand(2, 5)));

                Comment::factory(rand(5, 15))
                    ->approved()
                    ->recycle(User::all())
                    ->create(['post_id' => $post->id])
                    ->each(function (Comment $comment) {
                        if (fake()->boolean(30)) {
                            Comment::factory(rand(1, 3))
                                ->approved()
                                ->recycle(User::all())
                                ->create([
                                    'post_id' => $comment->post_id,
                                    'parent_id' => $comment->id,
                                ]);
                        }
                    });
            });

        // Create published posts
        Post::factory(20)
            ->published()
            ->recycle($allUsers)
            ->recycle($categories)
            ->create()
            ->each(function (Post $post) use ($tags) {
                $post->tags()->attach($tags->random(rand(1, 4)));

                Comment::factory(rand(0, 10))
                    ->approved()
                    ->recycle(User::all())
                    ->create(['post_id' => $post->id]);

                Comment::factory(rand(0, 3))
                    ->pending()
                    ->anonymous()
                    ->create(['post_id' => $post->id]);
            });

        // Create draft posts
        Post::factory(5)
            ->draft()
            ->recycle($allUsers)
            ->recycle($categories)
            ->create()
            ->each(function (Post $post) use ($tags) {
                $post->tags()->attach($tags->random(rand(1, 3)));
            });

        // Create pending posts
        Post::factory(3)
            ->pending()
            ->recycle($allUsers)
            ->recycle($categories)
            ->create()
            ->each(function (Post $post) use ($tags) {
                $post->tags()->attach($tags->random(rand(1, 3)));
            });

        // Create some popular posts
        Post::factory(2)
            ->published()
            ->popular()
            ->recycle($allUsers)
            ->recycle($categories)
            ->create()
            ->each(function (Post $post) use ($tags) {
                $post->tags()->attach($tags->random(rand(3, 5)));

                Comment::factory(rand(20, 40))
                    ->approved()
                    ->recycle(User::all())
                    ->create(['post_id' => $post->id]);
            });

        // Create some spam comments
        Comment::factory(5)
            ->spam()
            ->anonymous()
            ->recycle(Post::published()->get())
            ->create();
    }
}
