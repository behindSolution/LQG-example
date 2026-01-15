<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Services\CommentService;
use App\Services\PostService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PostRepository::class);
        $this->app->singleton(CommentRepository::class);

        $this->app->singleton(PostService::class, function ($app) {
            return new PostService($app->make(PostRepository::class));
        });

        $this->app->singleton(CommentService::class, function ($app) {
            return new CommentService($app->make(CommentRepository::class));
        });
    }

    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
    }
}
