<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CommentRepository extends BaseRepository
{
    protected function resolveModel(): Model
    {
        return new Comment();
    }

    public function getByPost(Post $post, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->where('post_id', $post->id)
            ->approved()
            ->rootLevel()
            ->with(['author:id,name', 'replies' => fn ($q) => $q->approved()->with('author:id,name')])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByAuthor(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->where('user_id', $user->id)
            ->with(['post:id,title,slug'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getPending(int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->pending()
            ->with(['post:id,title,slug', 'author:id,name'])
            ->orderBy('created_at')
            ->paginate($perPage);
    }

    public function getSpam(int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', Comment::STATUS_SPAM)
            ->with(['post:id,title,slug'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function countPendingByPost(Post $post): int
    {
        return $this->query()
            ->where('post_id', $post->id)
            ->pending()
            ->count();
    }

    public function countApprovedByPost(Post $post): int
    {
        return $this->query()
            ->where('post_id', $post->id)
            ->approved()
            ->count();
    }

    public function getRecentByPost(Post $post, int $limit = 5): Collection
    {
        return $this->query()
            ->where('post_id', $post->id)
            ->approved()
            ->with(['author:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getReplies(Comment $comment): Collection
    {
        return $this->query()
            ->where('parent_id', $comment->id)
            ->approved()
            ->with(['author:id,name'])
            ->orderBy('created_at')
            ->get();
    }

    public function approve(Comment $comment): bool
    {
        return $comment->update(['status' => Comment::STATUS_APPROVED]);
    }

    public function reject(Comment $comment): bool
    {
        return $comment->update(['status' => Comment::STATUS_REJECTED]);
    }

    public function markAsSpam(Comment $comment): bool
    {
        return $comment->update(['status' => Comment::STATUS_SPAM]);
    }

    public function bulkApprove(array $ids): int
    {
        return $this->model
            ->whereIn('id', $ids)
            ->pending()
            ->update(['status' => Comment::STATUS_APPROVED]);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model
            ->whereIn('id', $ids)
            ->delete();
    }

    public function getStats(): array
    {
        return [
            'total' => $this->model->count(),
            'pending' => $this->model->pending()->count(),
            'approved' => $this->model->approved()->count(),
            'spam' => $this->model->where('status', Comment::STATUS_SPAM)->count(),
            'rejected' => $this->model->where('status', Comment::STATUS_REJECTED)->count(),
        ];
    }
}
