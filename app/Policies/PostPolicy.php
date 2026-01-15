<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        if ($post->isPublished()) {
            return true;
        }

        return $user !== null && ($user->id === $post->user_id || $this->isAdmin($user));
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $this->isAdmin($user);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $this->isAdmin($user);
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $this->isAdmin($user);
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $this->isAdmin($user);
    }

    public function feature(User $user, Post $post): bool
    {
        return $this->isAdmin($user) || $this->isEditor($user);
    }

    public function publish(User $user, Post $post): bool
    {
        if ($user->id === $post->user_id) {
            return true;
        }

        return $this->isAdmin($user) || $this->isEditor($user);
    }

    protected function isAdmin(User $user): bool
    {
        return $user->email === 'admin@example.com';
    }

    protected function isEditor(User $user): bool
    {
        return str_ends_with($user->email, '@editor.com');
    }
}
