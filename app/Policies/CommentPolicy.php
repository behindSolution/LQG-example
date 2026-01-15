<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Comment $comment): bool
    {
        if ($comment->isApproved()) {
            return true;
        }

        return $user !== null && (
            $user->id === $comment->user_id ||
            $user->id === $comment->post->user_id ||
            $this->isAdmin($user)
        );
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $this->isAdmin($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id ||
               $user->id === $comment->post->user_id ||
               $this->isAdmin($user);
    }

    public function moderate(User $user, Comment $comment): bool
    {
        return $user->id === $comment->post->user_id ||
               $this->isAdmin($user) ||
               $this->isModerator($user);
    }

    protected function isAdmin(User $user): bool
    {
        return $user->email === 'admin@example.com';
    }

    protected function isModerator(User $user): bool
    {
        return str_ends_with($user->email, '@moderator.com');
    }
}
